<?php

/**
 * Fired during plugin activation
 *
 * @link       https://readystudio.com
 * @since      2.0.0
 *
 * @package    ReadyLicense
 * @subpackage ReadyLicense/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 * This class defines all code necessary to run during the plugin's activation.
 */
class ReadyLicense_Activator {

	/**
	 * متد اصلی فعال‌سازی
	 * این متد تنها زمانی اجرا می‌شود که دکمه "فعال‌سازی" افزونه زده شود.
	 */
	public static function activate() {
		// 1. ساخت جداول دیتابیس
		self::create_tables();

		// 2. تنظیم مقادیر پیش‌فرض (اگر وجود ندارند)
		self::set_default_options();

		// 3. زمان‌بندی کرون جاب‌ها (برای بررسی انقضای لایسنس‌ها)
		self::schedule_cron_jobs();

		// 4. فلاش کردن قوانین بازنویسی (برای API Endpoint)
		flush_rewrite_rules();
	}

	/**
	 * ساختار دیتابیس پیشرفته
	 * ما ۳ جدول جداگانه نیاز داریم تا سرعت کوئری‌ها بالا بماند.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// --- جدول ۱: لایسنس‌ها (The Licenses) ---
		// نگهداری اطلاعات کلیدی هر لایسنس
		$table_licenses = $wpdb->prefix . 'rl_licenses';
		$sql_licenses = "CREATE TABLE $table_licenses (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			license_key varchar(191) NOT NULL,
			product_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			order_id bigint(20) NOT NULL,
			parent_license_id bigint(20) DEFAULT 0,
			status varchar(50) DEFAULT 'inactive',
			activation_limit int(11) DEFAULT 1,
			activation_count int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY license_key (license_key),
			KEY user_id (user_id),
			KEY order_id (order_id),
			KEY status (status)
		) $charset_collate;";

		// --- جدول ۲: فعال‌سازی‌ها (Activations) ---
		// هر بار که لایسنس روی یک دامین فعال می‌شود، اینجا ثبت می‌شود.
		// مشابه ژاکت که لیست دامین‌ها را نشان می‌دهد.
		$table_activations = $wpdb->prefix . 'rl_activations';
		$sql_activations = "CREATE TABLE $table_activations (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			license_id bigint(20) NOT NULL,
			domain varchar(255) NOT NULL,
			ip_address varchar(100) DEFAULT '',
			platform varchar(100) DEFAULT '',
			activated_at datetime DEFAULT CURRENT_TIMESTAMP,
			last_check_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY license_id (license_id),
			KEY domain (domain(191))
		) $charset_collate;";

		// --- جدول ۳: لاگ‌ها (Logs) ---
		// برای دیباگ و امنیت (چه کسی، کی، چه کاری کرد)
		$table_logs = $wpdb->prefix . 'rl_logs';
		$sql_logs = "CREATE TABLE $table_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			license_id bigint(20) DEFAULT 0,
			user_id bigint(20) DEFAULT 0,
			action varchar(100) NOT NULL,
			message text DEFAULT '',
			data longtext DEFAULT NULL,
			ip_address varchar(100) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY license_id (license_id)
		) $charset_collate;";

		// اجرای dbDelta برای ساخت یا آپدیت جداول
		dbDelta( $sql_licenses );
		dbDelta( $sql_activations );
		dbDelta( $sql_logs );
	}

	/**
	 * تنظیمات پیش‌فرض برای جلوگیری از ارورهای Undefined Option
	 */
	private static function set_default_options() {
		$defaults = [
			'readylicense_max_domains'    => 1,
			'readylicense_label_menu'     => 'لایسنس‌های من',
			'readylicense_label_product'  => 'محصول',
			'readylicense_label_status'   => 'وضعیت',
			'readylicense_label_btn'      => 'مدیریت لایسنس',
			'readylicense_license_prefix' => 'RL-',
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * زمان‌بندی کارهای پس‌زمینه (Cron)
	 */
	private static function schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'rl_daily_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'rl_daily_license_check' );
		}
	}

	/**
	 * پاکسازی هنگام حذف افزونه (اختیاری)
	 * فعلاً جداول را پاک نمی‌کنیم تا دیتای مشتری نپرد.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'rl_daily_license_check' );
	}
}