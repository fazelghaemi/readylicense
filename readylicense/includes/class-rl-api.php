<?php
/**
 * مدیریت REST API برای ارتباط با سایت‌های کلاینت
 *
 * این کلاس درخواست‌های از راه دور (Remote Requests) را دریافت، اعتبارسنجی و پردازش می‌کند.
 *
 * @package ReadyLicense
 * @version 2.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReadyLicense_API {

	/**
	 * فضای نام API (Namespace)
	 * تمام درخواست‌ها با پیشوند /wp-json/readylicense/v1/ شروع می‌شوند.
	 */
	const NAMESPACE = 'readylicense/v1';

	/**
	 * راه‌اندازی کلاس و ثبت هوک‌ها
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * ثبت مسیرهای (Endpoints) ای‌پی‌آی
	 */
	public function register_routes() {
		
		// 1. بررسی وضعیت لایسنس (Check)
		// متد: POST
		// مسیر: /wp-json/readylicense/v1/check
		register_rest_route( self::NAMESPACE, '/check', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'check_license' ],
			'permission_callback' => '__return_true', // عمومی (لایسنس خودش کلید احراز هویت است)
		] );

		// 2. فعال‌سازی لایسنس (Activate)
		// متد: POST
		// مسیر: /wp-json/readylicense/v1/activate
		register_rest_route( self::NAMESPACE, '/activate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'activate_license' ],
			'permission_callback' => '__return_true',
		] );

		// 3. غیرفعال‌سازی لایسنس (Deactivate)
		// متد: POST
		// مسیر: /wp-json/readylicense/v1/deactivate
		register_rest_route( self::NAMESPACE, '/deactivate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'deactivate_license' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * پردازش درخواست بررسی لایسنس (Check)
	 * این متد بررسی می‌کند که آیا لایسنس معتبر است و آیا روی دامنه درخواست‌دهنده فعال شده است یا خیر.
	 */
	public function check_license( $request ) {
		$params      = $request->get_params();
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		// ۱. اعتبارسنجی ورودی‌ها
		if ( empty( $license_key ) || empty( $domain ) ) {
			return $this->response( false, 'missing_params', 'کلید لایسنس و آدرس دامنه الزامی است.' );
		}

		// ۲. دریافت اطلاعات لایسنس از دیتابیس
		$license = rl_get_license( $license_key );

		if ( ! $license ) {
			return $this->response( false, 'invalid_license', 'لایسنس یافت نشد یا نامعتبر است.', [], 404 );
		}

		// ۳. بررسی وضعیت کلی (Status)
		if ( $license->status !== 'active' ) {
			return $this->response( false, 'license_inactive', 'این لایسنس مسدود یا غیرفعال شده است.' );
		}

		// ۴. بررسی تاریخ انقضا
		if ( ! empty( $license->expires_at ) && strtotime( $license->expires_at ) < time() ) {
			return $this->response( false, 'license_expired', 'مدت اعتبار لایسنس به پایان رسیده است.' );
		}

		// ۵. بررسی تطابق دامنه (مهم‌ترین بخش)
		// آیا این لایسنس قبلاً برای این دامنه در سیستم ثبت شده است؟
		$clean_domain = rl_normalize_domain( $domain );
		global $wpdb;
		$activation = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}rl_activations WHERE license_id = %d AND domain = %s",
			$license->id,
			$clean_domain
		) );

		if ( ! $activation ) {
			// اگر ثبت نشده باشد، یعنی لایسنس معتبر است اما روی این سایت فعال نشده
			return $this->response( false, 'domain_mismatch', 'این لایسنس برای دامنه فعلی فعال نشده است. لطفاً ابتدا آن را فعال کنید.' );
		}

		// ۶. به‌روزرسانی زمان آخرین بررسی (Telemetry)
		$wpdb->update(
			$wpdb->prefix . 'rl_activations',
			[ 
				'last_check_at' => current_time( 'mysql' ), 
				'ip_address'    => rl_get_ip() 
			],
			[ 'id' => $activation->id ]
		);

		// ۷. ارسال پاسخ موفقیت‌آمیز
		return $this->response( true, 'valid', 'لایسنس معتبر است.', [
			'expires_at' => $license->expires_at,
			'holder'     => 'Customer', // می‌توان نام کاربر را هم برگرداند
		] );
	}

	/**
	 * پردازش درخواست فعال‌سازی (Activate)
	 */
	public function activate_license( $request ) {
		$params      = $request->get_params();
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		if ( empty( $license_key ) || empty( $domain ) ) {
			return $this->response( false, 'missing_params', 'پارامترهای ضروری ارسال نشده‌اند.' );
		}

		// استفاده از تابع مرکزی rl_activate_license که همه قوانین را چک می‌کند
		$result = rl_activate_license( $license_key, $domain );

		if ( is_wp_error( $result ) ) {
			// در صورت خطا، کد خطا و پیام فارسی را برمی‌گردانیم
			return $this->response( false, $result->get_error_code(), $result->get_error_message() );
		}

		return $this->response( true, 'active', 'لایسنس با موفقیت روی این دامنه فعال شد.' );
	}

	/**
	 * پردازش درخواست غیرفعال‌سازی (Deactivate)
	 */
	public function deactivate_license( $request ) {
		$params      = $request->get_params();
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		$license = rl_get_license( $license_key );

		if ( ! $license ) {
			return $this->response( false, 'invalid_license', 'لایسنس یافت نشد.' );
		}

		$result = rl_deactivate_license( $license->id, $domain );

		if ( $result ) {
			return $this->response( true, 'deactivated', 'لایسنس از روی دامنه حذف شد.' );
		} else {
			return $this->response( false, 'failed', 'عملیات ناموفق بود یا دامنه یافت نشد.' );
		}
	}

	/**
	 * ساختار استاندارد پاسخ JSON
	 * تمام پاسخ‌های API (موفق یا ناموفق) باید این فرمت را داشته باشند.
	 *
	 * @param bool $success وضعیت موفقیت
	 * @param string $code کد وضعیت (برای توسعه‌دهنده)
	 * @param string $message پیام قابل نمایش به کاربر
	 * @param array $data داده‌های اضافی
	 * @param int $http_code کد HTTP
	 * @return WP_REST_Response
	 */
	private function response( $success, $code, $message, $data = [], $http_code = 200 ) {
		$response_data = array_merge( [
			'success' => $success,
			'code'    => $code,
			'message' => $message,
			'data'    => [
				'status' => $success ? 200 : 400
			]
		], $data );

		return new WP_REST_Response( $response_data, $http_code );
	}
}