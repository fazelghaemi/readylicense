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
		include RL_PLUGIN_DIR . 'templates/dashboard.php';
	}

	/**
	 * دریافت لایسنس‌های کاربر
	 * این متد از جدول rl_licenses دیتابیس استفاده می‌کند.
	 */
	private function get_user_licenses( $user_id ) {
		global $wpdb;
		
		$table_licenses = $wpdb->prefix . 'rl_licenses';
		
		// کوئری برای گرفتن لایسنس‌های کاربر
		$results = $wpdb->get_results( $wpdb->prepare( 
			"SELECT * FROM $table_licenses WHERE user_id = %d ORDER BY created_at DESC", 
			$user_id 
		) );

		if ( empty( $results ) ) {
			return [];
		}

		// بهینه‌سازی: دریافت دامین‌ها به صورت یکجا (جلوگیری از N+1 Query)
		$license_ids = wp_list_pluck( $results, 'id' );
		$activations_map = $this->get_bulk_activations( $license_ids );

		$data = [];

		foreach ( $results as $license ) {
			// استفاده از کش آبجکت ووکامرس
			$product = wc_get_product( $license->product_id );
			
			// اگر محصول حذف شده باشد، ادامه نده
			if ( ! $product ) continue;

			// دریافت دامین از مپ آماده شده
			$domain_name = isset( $activations_map[ $license->id ] ) ? $activations_map[ $license->id ] : null;

			// محاسبه روزهای پشتیبانی باقی‌مانده
			$support_days = 0;
			$support_text = __( 'مادام‌العمر', 'readylicense' );
			$is_expired = false;

			if ( ! empty( $license->expires_at ) ) {
				$expiry_timestamp = strtotime( $license->expires_at );
				$now = time();
				$is_expired = $expiry_timestamp < $now;
				
				if ( ! $is_expired ) {
					$diff = $expiry_timestamp - $now;
					$support_days = ceil( $diff / 86400 );
					$support_text = sprintf( __( '%d روز', 'readylicense' ), $support_days );
				} else {
					$support_text = __( 'منقضی شده', 'readylicense' );
				}
			}

			// دریافت لینک‌های دانلود اختصاصی از متای محصول
			$fast_install_url = $product->get_meta( '_rl_fast_install_url' );
			$edu_file_url     = $product->get_meta( '_rl_edu_file_url' );

			// دریافت فایل‌های دانلودی استاندارد ووکامرس
			$downloads = $product->get_downloads();
			$product_download_url = '';
			if ( ! empty( $downloads ) ) {
				// لینک اولین فایل دانلودی را برمی‌داریم
				$first_download = reset( $downloads );
				$product_download_url = $first_download->get_file();
			}

			$data[] = [
				'id'               => $license->id,
				'license_key'      => $license->license_key,
				'product_name'     => $product->get_name(),
				'product_url'      => $product->get_permalink(),
				'product_image'    => $product->get_image( [60, 60] ), // اندازه تامنیل کوچک
				'product_version'  => $product->get_version(),
				'status'           => $license->status,
				'domain'           => $domain_name,
				'has_domain'       => ! empty( $domain_name ),
				'support_days'     => $support_days,
				'support_text'     => $support_text,
				'is_expired'       => $is_expired,
				'expires_at'       => $license->expires_at,
				'links'            => [
					'fast_install' => $fast_install_url,
					'edu_file'     => $edu_file_url,
					'product'      => $product_download_url
				]
			];
		}

		return $data;
	}

	/**
	 * دریافت فعال‌سازی‌ها به صورت گروهی برای مجموعه‌ای از لایسنس‌ها
	 * 
	 * @param array $license_ids لیست شناسه‌های لایسنس
	 * @return array آرایه انجمنی [license_id => domain]
	 */
	private function get_bulk_activations( $license_ids ) {
		if ( empty( $license_ids ) ) {
			return [];
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rl_activations';
		
		// تبدیل آرایه به رشته امن برای SQL IN
		$ids_placeholder = implode( ',', array_map( 'intval', $license_ids ) );
		
		// کوئری برای گرفتن همه فعال‌سازی‌های مربوط به این لایسنس‌ها
		$results = $wpdb->get_results( "SELECT license_id, domain FROM $table WHERE license_id IN ($ids_placeholder)" );

		$map = [];
		if ( $results ) {
			foreach ( $results as $row ) {
				// فرض بر این است که هر لایسنس (در این ویو) فقط یک دامین اصلی دارد
				// اگر قبلاً ست نشده باشد، ست می‌کنیم (اولین دامین یافت شده)
				if ( ! isset( $map[ $row->license_id ] ) ) {
					$map[ $row->license_id ] = $row->domain;
				}
			}
		}

		return $map;
	}
}
