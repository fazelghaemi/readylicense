<?php
/**
 * مدیریت بخش کاربری (Frontend) در حساب کاربری ووکامرس
 *
 * @package ReadyLicense
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReadyLicense_Frontend {

	public function __construct() {
		// اضافه کردن تب جدید به منوی حساب کاربری
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_license_tab' ] );
		
		// ثبت Endpoint برای آدرس‌دهی (my-account/licenses)
		add_action( 'init', [ $this, 'add_endpoint' ] );
		
		// نمایش محتوای تب
		add_action( 'woocommerce_account_licenses_endpoint', [ $this, 'render_licenses_page' ] );
		
		// تغییر عنوان صفحه در تب لایسنس‌ها
		add_filter( 'the_title', [ $this, 'change_page_title' ] );

		// شورت‌کد برای نمایش در صفحات دلخواه
		add_shortcode( 'ready_license_dashboard', [ $this, 'render_shortcode' ] );
	}

	/**
	 * افزودن آیتم به منوی حساب کاربری
	 */
	public function add_license_tab( $items ) {
		$label = get_option( 'readylicense_label_menu', 'لایسنس‌های من' );
		
		// جایگذاری در موقعیت مناسب (معمولاً بعد از سفارش‌ها)
		$new_items = [];
		foreach ( $items as $key => $item ) {
			$new_items[ $key ] = $item;
			if ( $key === 'orders' ) {
				$new_items['licenses'] = $label;
			}
		}
		return $new_items;
	}

	/**
	 * ثبت Endpoint
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'licenses', EP_ROOT | EP_PAGES );
	}

	/**
	 * تغییر عنوان صفحه
	 */
	public function change_page_title( $title ) {
		global $wp_query;
		if ( isset( $wp_query->query_vars['licenses'] ) && is_main_query() && in_the_loop() && is_account_page() ) {
			$title = get_option( 'readylicense_label_menu', 'لایسنس‌های من' );
		}
		return $title;
	}

	/**
	 * نمایش شورت‌کد
	 */
	public function render_shortcode() {
		ob_start();
		$this->render_licenses_page();
		return ob_get_clean();
	}

	/**
	 * رندر کردن محتوای صفحه لایسنس‌ها
	 */
	public function render_licenses_page() {
		if ( ! is_user_logged_in() ) {
			echo '<div class="woocommerce-message">' . __( 'لطفاً ابتدا وارد حساب کاربری خود شوید.', 'readylicense' ) . '</div>';
			return;
		}

		$user_id = get_current_user_id();
		
		// دریافت لیست لایسنس‌های کاربر از دیتابیس اختصاصی
		$licenses = $this->get_user_licenses( $user_id );

		// بارگذاری تمپلت (ویو)
		// ما متغیر $licenses را به تمپلت پاس می‌دهیم
		include RL_PLUGIN_DIR . 'templates/dashboard.php';
	}

	/**
	 * دریافت لایسنس‌های کاربر
	 * این متد از جدول rl_licenses دیتابیس استفاده می‌کند.
	 */
	private function get_user_licenses( $user_id ) {
		global $wpdb;
		
		// کوئری برای گرفتن لایسنس‌ها + اطلاعات محصول (اختیاری)
		// ما فقط لایسنس‌هایی را می‌خواهیم که وضعیتشان inactive یا مسدود نباشد (مگر اینکه بخواهیم همه را نشان دهیم)
		// در اینجا همه لایسنس‌های متصل به کاربر را می‌گیریم.
		$table_licenses = $wpdb->prefix . 'rl_licenses';
		
		$results = $wpdb->get_results( $wpdb->prepare( 
			"SELECT * FROM $table_licenses WHERE user_id = %d ORDER BY created_at DESC", 
			$user_id 
		) );

		$data = [];

		if ( $results ) {
			foreach ( $results as $license ) {
				$product = wc_get_product( $license->product_id );
				
				// اگر محصول حذف شده باشد، لایسنس را نمایش نده (یا مدیریت کن)
				if ( ! $product ) continue;

				// دریافت لیست دامین‌های فعال برای این لایسنس
				$activations = $this->get_license_activations( $license->id );

				$data[] = [
					'id'               => $license->id,
					'license_key'      => $license->license_key,
					'product_name'     => $product->get_name(),
					'product_url'      => $product->get_permalink(),
					'product_image'    => $product->get_image( 'thumbnail' ), // تصویر محصول
					'status'           => $license->status,
					'activations'      => $activations,
					'activation_limit' => $license->activation_limit,
					'activation_count' => count( $activations ),
					'expires_at'       => $license->expires_at,
					'is_expired'       => $license->expires_at && strtotime( $license->expires_at ) < time(),
				];
			}
		}

		return $data;
	}

	/**
	 * دریافت لیست فعال‌سازی‌ها (دامین‌ها) برای یک لایسنس خاص
	 */
	private function get_license_activations( $license_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rl_activations';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE license_id = %d", $license_id ) );
	}
}