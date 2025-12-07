<?php
/**
 * Fired during plugin activation
 *
 * @link       https://readystudio.ir
 * @since      2.0.2
 *
 * @package    ReadyLicense
 * @subpackage ReadyLicense/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس فعال‌ساز افزونه ReadyLicense
 * این کلاس مسئول راه‌اندازی اولیه دیتابیس، کرون‌جاب‌ها و تنظیمات هنگام فعال‌سازی افزونه است.
 *
 * @since      2.0.2
 */
class ReadyLicense_Activator {

	/**
	 * متد اصلی فعال‌سازی
	 * این متد توسط فایل اصلی افزونه (readylicense.php) فراخوانی می‌شود.
	 */
	public static function activate() {
		// ۱. ساخت یا بروزرسانی جداول دیتابیس
		self::create_tables();

		// ۲. تنظیم مقادیر پیش‌فرض
		self::set_default_options();

		// ۳. زمان‌بندی وظایف پس‌زمینه (Cron Jobs)
		self::schedule_cron_jobs();

		// ۴. فلاش کردن قوانین بازنویسی (برای رفع خطای 404 در API)
		flush_rewrite_rules();
	}

	/**
	 * ساختار دیتابیس رابطه‌ای (Relational Database)
	 * استفاده از dbDelta برای مدیریت هوشمند تغییرات ساختار جدول در آپدیت‌ها.
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// --- جدول ۱: لایسنس‌ها (Master Table) ---
		// نگهداری اطلاعات اصلی لایسنس‌ها، وضعیت، مالک و تاریخ انقضا
		$table_licenses = $wpdb->prefix . 'rl_licenses';
		$sql_licenses = "CREATE TABLE $table_licenses (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_key varchar(191) NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			order_id bigint(20) unsigned DEFAULT 0,
			parent_license_id bigint(20) unsigned DEFAULT 0,
			status varchar(20) DEFAULT 'active',
			activation_limit int(11) DEFAULT 1,
			activation_count int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY license_key (license_key),
			KEY user_id (user_id),
			KEY product_id (product_id),
			KEY status (status)
		) $charset_collate;";

		// --- جدول ۲: فعال‌سازی‌ها (Activations Table) ---
		// لیست دامین‌هایی که لایسنس روی آن‌ها فعال است.
		$table_activations = $wpdb->prefix . 'rl_activations';
		$sql_activations = "CREATE TABLE $table_activations (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_id bigint(20) unsigned NOT NULL,
			domain varchar(255) NOT NULL,
			ip_address varchar(45) DEFAULT '',
			platform varchar(100) DEFAULT '',
			activated_at datetime DEFAULT CURRENT_TIMESTAMP,
			last_check_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY license_id (license_id),
			KEY domain (domain(191))
		) $charset_collate;";

		// --- جدول ۳: لاگ‌های سیستمی (Audit Logs) ---
		// ثبت تمام وقایع برای امنیت و رهگیری مشکلات.
		$table_logs = $wpdb->prefix . 'rl_logs';
		$sql_logs = "CREATE TABLE $table_logs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_id bigint(20) unsigned DEFAULT 0,
			user_id bigint(20) unsigned DEFAULT 0,
			action varchar(50) NOT NULL,
			message text DEFAULT '',
			ip_address varchar(45) DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY license_id (license_id),
			KEY action (action)
		) $charset_collate;";

		// اجرای کوئری‌ها توسط dbDelta
		dbDelta( $sql_licenses );
		dbDelta( $sql_activations );
		dbDelta( $sql_logs );
	}

	/**
	 * تنظیم مقادیر پیش‌فرض در wp_options
	 * این کار از خطاهای احتمالی هنگام خواندن تنظیمات جلوگیری می‌کند.
	 */
	private static function set_default_options() {
		$defaults = [
			'readylicense_max_domains'    => 1,
			'readylicense_license_prefix' => 'RL-',
			'readylicense_label_menu'     => 'لایسنس‌های من',
			'readylicense_label_product'  => 'محصول',
			'readylicense_label_status'   => 'وضعیت',
			'readylicense_label_btn'      => 'مدیریت دامنه',
		];

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * زمان‌بندی کرون جاب‌ها
	 * مثلاً برای بررسی روزانه لایسنس‌های منقضی شده.
	 */
	private static function schedule_cron_jobs() {
		if ( ! wp_next_scheduled( 'rl_daily_license_check' ) ) {
			wp_schedule_event( time(), 'daily', 'rl_daily_license_check' );
		}
	}

	/**
	 * پاکسازی هنگام غیرفعال‌سازی افزونه (Deactivation)
	 * توجه: جداول را حذف نمی‌کنیم تا اطلاعات کاربر از دست نرود.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'rl_daily_license_check' );
		flush_rewrite_rules();
	}
}