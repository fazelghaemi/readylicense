<?php
/**
 * مدیریت API های REST برای ارتباط با سایت‌های مشتریان
 *
 * @package ReadyLicense
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReadyLicense_API {

	/**
	 * فضای نام API
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
		register_rest_route( self::NAMESPACE, '/check', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'check_license' ],
			'permission_callback' => '__return_true', // امنیت داخل تابع بررسی می‌شود
		] );

		// 2. فعال‌سازی لایسنس (Activate)
		register_rest_route( self::NAMESPACE, '/activate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'activate_license' ],
			'permission_callback' => '__return_true',
		] );

		// 3. غیرفعال‌سازی لایسنس (Deactivate)
		register_rest_route( self::NAMESPACE, '/deactivate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'deactivate_license' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * متد: بررسی وضعیت لایسنس
	 * این متد توسط قالب/افزونه در سایت مشتری هر ۲۴ ساعت یکبار صدا زده می‌شود.
	 */
	public function check_license( $request ) {
		$params = $request->get_params();
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		if ( empty( $license_key ) || empty( $domain ) ) {
			return $this->error( 'missing_params', 'پارامترهای ضروری ارسال نشده‌اند.' );
		}

		$license = rl_get_license( $license_key );

		if ( ! $license ) {
			return $this->error( 'invalid_license', 'لایسنس نامعتبر است.', 404 );
		}

		// بررسی وضعیت کلی لایسنس (مثلاً اگر مدیر آن را مسدود کرده باشد)
		if ( $license->status !== 'active' ) {
			return $this->error( 'license_blocked', 'این لایسنس مسدود یا منقضی شده است.' );
		}

		// بررسی دامین
		$clean_domain = rl_normalize_domain( $domain );
		global $wpdb;
		$activation = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}rl_activations WHERE license_id = %d AND domain = %s",
			$license->id,
			$clean_domain
		) );

		if ( ! $activation ) {
			return $this->error( 'domain_mismatch', 'این لایسنس برای این دامنه فعال نشده است.' );
		}

		// آپدیت زمان آخرین بررسی (برای آمارگیری)
		$wpdb->update(
			$wpdb->prefix . 'rl_activations',
			[ 'last_check_at' => current_time( 'mysql' ), 'ip_address' => rl_get_ip() ],
			[ 'id' => $activation->id ]
		);

		return $this->success( [
			'status' => 'valid',
			'message' => 'لایسنس معتبر است.',
			'holder'  => 'کاربر محترم', // می‌توان نام کاربر را هم فرستاد
		] );
	}

	/**
	 * متد: فعال‌سازی لایسنس (اولین بار)
	 */
	public function activate_license( $request ) {
		$params = $request->get_params();
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		if ( empty( $license_key ) || empty( $domain ) ) {
			return $this->error( 'missing_params', 'کلید لایسنس و دامنه الزامی است.' );
		}

		// استفاده از تابع کمکی هوشمند که قبلاً نوشتیم
		$result = rl_activate_license( $license_key, $domain );

		if ( is_wp_error( $result ) ) {
			// لاگ کردن تلاش ناموفق
			$license = rl_get_license( $license_key );
			if ($license) {
				rl_log( $license->id, 'activation_failed', $result->get_error_message() . " - IP: " . rl_get_ip() );
			}
			return $this->error( $result->get_error_code(), $result->get_error_message() );
		}

		return $this->success( [
			'status' => 'active',
			'message' => 'لایسنس با موفقیت روی این دامنه فعال شد.',
		] );
	}

	/**
	 * متد: حذف لایسنس (مثلاً وقتی کاربر می‌خواهد دامین را عوض کند)
	 */
	public function deactivate_license( $request ) {
		$params = $request->get_params();
		$license_key = sanitize_text_field( $params['license_key'] ?? '' );
		$domain      = esc_url_raw( $params['domain'] ?? '' );

		$license = rl_get_license( $license_key );

		if ( ! $license ) {
			return $this->error( 'invalid_license', 'لایسنس یافت نشد.' );
		}

		$result = rl_deactivate_license( $license->id, $domain );

		if ( $result ) {
			return $this->success( [
				'status' => 'deactivated',
				'message' => 'لایسنس از روی این دامنه حذف شد.',
			] );
		} else {
			return $this->error( 'deactivation_failed', 'عملیات ناموفق بود یا دامین پیدا نشد.' );
		}
	}

	/**
	 * پاسخ موفقیت‌آمیز استاندارد
	 */
	private function success( $data = [] ) {
		return new WP_REST_Response( array_merge( [ 'success' => true ], $data ), 200 );
	}

	/**
	 * پاسخ خطای استاندارد
	 */
	private function error( $code, $message, $status = 400 ) {
		return new WP_REST_Response( [
			'success' => false,
			'code'    => $code,
			'message' => $message,
		], $status );
	}
}