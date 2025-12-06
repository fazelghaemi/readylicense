<?php
/**
 * مدیریت REST API برای اتصال از راه دور
 *
 * @package ReadyLicense
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReadyLicense_API {

	/**
	 * فضای نام API
	 * این قسمت باید با آدرس درخواستی شما هماهنگ باشد: wp-json/readylicense/v1
	 */
	const NAMESPACE = 'readylicense/v1';

	/**
	 * راه‌اندازی کلاس
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * ثبت مسیرهای (Endpoints) ای‌پی‌آی
	 */
	public function register_routes() {
		
		// 1. بررسی وضعیت لایسنس (Check)
		// آدرس: POST /wp-json/readylicense/v1/check
		register_rest_route( self::NAMESPACE, '/check', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'check_license' ],
			'permission_callback' => '__return_true', // عمومی است چون سایت مشتری کوکی ادمین ندارد
		] );

		// 2. فعال‌سازی لایسنس (Activate)
		// آدرس: POST /wp-json/readylicense/v1/activate
		register_rest_route( self::NAMESPACE, '/activate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'activate_license' ],
			'permission_callback' => '__return_true',
		] );

		// 3. غیرفعال‌سازی لایسنس (Deactivate)
		// آدرس: POST /wp-json/readylicense/v1/deactivate
		register_rest_route( self::NAMESPACE, '/deactivate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'deactivate_license' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * متد: بررسی وضعیت لایسنس
	 * پارامترها: license_key, domain
	 */
	public function check_license( $request ) {
		$params = $request->get_params();
		
		// پشتیبانی از هر دو حالت ارسال (JSON Body یا Form Data)
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		// 1. بررسی پارامترها
		if ( empty( $license_key ) || empty( $domain ) ) {
			return $this->response( false, 'missing_params', 'کلید لایسنس و آدرس دامنه الزامی است.' );
		}

		// 2. یافتن لایسنس
		$license = rl_get_license( $license_key );

		if ( ! $license ) {
			return $this->response( false, 'invalid_license', 'لایسنس یافت نشد یا نامعتبر است.' );
		}

		// 3. بررسی وضعیت کلی لایسنس
		if ( $license->status !== 'active' ) {
			return $this->response( false, 'license_blocked', 'این لایسنس مسدود یا غیرفعال شده است.' );
		}

		// 4. بررسی تاریخ انقضا
		if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
			return $this->response( false, 'license_expired', 'مدت اعتبار لایسنس به پایان رسیده است.' );
		}

		// 5. بررسی تطابق دامنه
		$clean_domain = rl_normalize_domain( $domain );
		global $wpdb;
		$activation = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}rl_activations WHERE license_id = %d AND domain = %s",
			$license->id,
			$clean_domain
		) );

		if ( ! $activation ) {
			return $this->response( false, 'domain_mismatch', 'این لایسنس برای دامنه ارسالی فعال نشده است.' );
		}

		// 6. آپدیت زمان آخرین بررسی (برای آمارگیری مدیر)
		$wpdb->update(
			$wpdb->prefix . 'rl_activations',
			[ 
				'last_check_at' => current_time( 'mysql' ), 
				'ip_address'    => rl_get_ip() 
			],
			[ 'id' => $activation->id ]
		);

		return $this->response( true, 'valid', 'لایسنس معتبر است.', [
			'expires_at' => $license->expires_at,
			'holder'     => 'Customer',
		] );
	}

	/**
	 * متد: فعال‌سازی لایسنس (از راه دور)
	 */
	public function activate_license( $request ) {
		$params = $request->get_params();
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		if ( empty( $license_key ) || empty( $domain ) ) {
			return $this->response( false, 'missing_params', 'پارامترهای ضروری ارسال نشده‌اند.' );
		}

		// استفاده از تابع مرکزی فعال‌سازی
		$result = rl_activate_license( $license_key, $domain );

		if ( is_wp_error( $result ) ) {
			return $this->response( false, $result->get_error_code(), $result->get_error_message() );
		}

		return $this->response( true, 'active', 'لایسنس با موفقیت فعال شد.' );
	}

	/**
	 * متد: غیرفعال‌سازی لایسنس (از راه دور)
	 */
	public function deactivate_license( $request ) {
		$params = $request->get_params();
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
	 * ساخت پاسخ استاندارد JSON
	 */
	private function response( $success, $code, $message, $extra_data = [] ) {
		$data = array_merge( [
			'success' => $success,
			'code'    => $code,
			'message' => $message,
			'data'    => [
				'status' => $success ? 200 : 400
			]
		], $extra_data );

		return new WP_REST_Response( $data, 200 );
	}
}