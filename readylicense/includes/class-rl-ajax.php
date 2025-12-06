<?php
/**
 * مدیریت درخواست‌های AJAX (سمت کاربر و ادمین)
 *
 * @package ReadyLicense
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReadyLicense_Ajax {

	public function __construct() {
		// هندلر درخواست‌های فرانت‌اند (کاربر لاگین شده)
		add_action( 'wp_ajax_rl_handle_frontend_request', [ $this, 'handle_frontend_request' ] );
		
		// اگر نیاز باشد کاربر لاگین نکرده هم درخواست بفرستد (مثلاً برای چک کردن وضعیت لایسنس در صفحه محصول)
		// add_action( 'wp_ajax_nopriv_rl_handle_frontend_request', [ $this, 'handle_frontend_request' ] );
	}

	/**
	 * مدیریت درخواست‌های فرانت‌اند
	 */
	public function handle_frontend_request() {
		// 1. بررسی امنیت (Nonce)
		if ( ! check_ajax_referer( 'rl_user_nonce', 'security', false ) ) {
			wp_send_json_error( [ 'message' => __( 'نشست شما منقضی شده است. لطفاً صفحه را رفرش کنید.', 'readylicense' ) ] );
		}

		// 2. بررسی اینکه کاربر لاگین است
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'لطفاً وارد حساب کاربری خود شوید.', 'readylicense' ) ] );
		}

		$request_type = isset( $_POST['request_type'] ) ? sanitize_text_field( $_POST['request_type'] ) : '';

		try {
			switch ( $request_type ) {
				case 'add_domain':
					$this->process_add_domain();
					break;

				case 'deactivate_domain':
					$this->process_deactivate_domain();
					break;

				default:
					throw new Exception( __( 'نوع درخواست نامعتبر است.', 'readylicense' ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * پردازش افزودن دامنه جدید
	 */
	private function process_add_domain() {
		$license_id = isset( $_POST['license_id'] ) ? intval( $_POST['license_id'] ) : 0;
		$domain     = isset( $_POST['domain'] ) ? sanitize_text_field( $_POST['domain'] ) : '';

		if ( empty( $license_id ) || empty( $domain ) ) {
			throw new Exception( __( 'اطلاعات ناقص است.', 'readylicense' ) );
		}

		// دریافت لایسنس
		$license = rl_get_license( $license_id );

		// بررسی مالکیت لایسنس
		if ( ! $license || intval( $license->user_id ) !== get_current_user_id() ) {
			throw new Exception( __( 'شما اجازه دسترسی به این لایسنس را ندارید.', 'readylicense' ) );
		}

		// بررسی وضعیت لایسنس
		if ( $license->status !== 'active' ) {
			throw new Exception( __( 'این لایسنس فعال نیست.', 'readylicense' ) );
		}

		// تلاش برای فعال‌سازی
		$result = rl_activate_license( $license->license_key, $domain );

		if ( is_wp_error( $result ) ) {
			throw new Exception( $result->get_error_message() );
		}

		wp_send_json_success( [
			'message' => __( 'دامنه با موفقیت ثبت و فعال شد.', 'readylicense' ),
			'reload'  => true // دستور به فرانت برای رفرش صفحه
		] );
	}

	/**
	 * پردازش غیرفعال‌سازی (حذف) دامنه
	 */
	private function process_deactivate_domain() {
		$license_id = isset( $_POST['license_id'] ) ? intval( $_POST['license_id'] ) : 0;
		$domain     = isset( $_POST['domain'] ) ? sanitize_text_field( $_POST['domain'] ) : '';

		if ( empty( $license_id ) || empty( $domain ) ) {
			throw new Exception( __( 'اطلاعات ناقص است.', 'readylicense' ) );
		}

		// دریافت لایسنس
		$license = rl_get_license( $license_id );

		// بررسی مالکیت
		if ( ! $license || intval( $license->user_id ) !== get_current_user_id() ) {
			throw new Exception( __( 'شما اجازه دسترسی به این لایسنس را ندارید.', 'readylicense' ) );
		}

		// انجام عملیات حذف
		$result = rl_deactivate_license( $license_id, $domain );

		if ( $result ) {
			wp_send_json_success( [
				'message' => __( 'دامنه با موفقیت حذف شد.', 'readylicense' ),
				'reload'  => true
			] );
		} else {
			throw new Exception( __( 'عملیات ناموفق بود. ممکن است این دامنه قبلاً حذف شده باشد.', 'readylicense' ) );
		}
	}
}