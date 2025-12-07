<?php
/**
 * توابع کمکی و هسته منطقی ReadyLicense
 * تمام تعاملات با دیتابیس، تولید کلید و منطق‌های تجاری در این فایل متمرکز شده‌اند.
 *
 * @package ReadyLicense
 * @version 2.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * تولید یک کلید لایسنس منحصر به فرد، طولانی و ایمن (API Key Style)
 * فرمت خروجی: RL-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX (32 کاراکتر هگز)
 *
 * @param string $prefix پیشوند اختیاری برای کلید
 * @return string کلید لایسنس تولید شده
 */
function rl_generate_license_key( $prefix = '' ) {
	// اگر پیشوندی داده نشده، از تنظیمات بگیر
	if ( empty( $prefix ) ) {
		$prefix = get_option( 'readylicense_license_prefix', 'RL-' );
	}
	
	try {
		// استفاده از random_bytes برای امنیت کریپتوگرافی بالا
		$random_bytes = random_bytes( 16 );
	} catch ( Exception $e ) {
		// فال‌بک برای سرورهای قدیمی که random_bytes ندارند
		$random_bytes = openssl_random_pseudo_bytes( 16 );
	}

	$random_hex = bin2hex( $random_bytes ); // تبدیل به رشته ۳۲ کاراکتری
	
	return strtoupper( $prefix . $random_hex );
}

/**
 * ایجاد لایسنس جدید در دیتابیس
 *
 * @param array $args آرایه اطلاعات لایسنس (محصول، کاربر، انقضا و...)
 * @return int|WP_Error شناسه لایسنس ساخته شده یا آبجکت خطا
 */
function rl_create_license( $args = [] ) {
	global $wpdb;

	$defaults = [
		'license_key'      => rl_generate_license_key(),
		'product_id'       => 0,
		'user_id'          => get_current_user_id(),
		'order_id'         => 0,
		'parent_license_id'=> 0,
		'status'           => 'active',
		'activation_limit' => get_option( 'readylicense_max_domains', 1 ),
		'expires_at'       => null, // null به معنای مادام‌العمر است
	];

	$data = wp_parse_args( $args, $defaults );

	// اعتبارسنجی داده‌های ورودی
	if ( empty( $data['product_id'] ) || empty( $data['user_id'] ) ) {
		return new WP_Error( 'rl_invalid_data', __( 'شناسه محصول یا کاربر نامعتبر است.', 'readylicense' ) );
	}

	$table = $wpdb->prefix . 'rl_licenses';
	
	// اطمینان از یکتا بودن کلید لایسنس (در موارد بسیار نادر ممکن است تکراری باشد)
	$key_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE license_key = %s", $data['license_key'] ) );
	if ( $key_exists ) {
		$data['license_key'] = rl_generate_license_key(); // تولید مجدد کلید
	}

	// درج در دیتابیس
	$inserted = $wpdb->insert(
		$table,
		[
			'license_key'       => $data['license_key'],
			'product_id'        => $data['product_id'],
			'user_id'           => $data['user_id'],
			'order_id'          => $data['order_id'],
			'parent_license_id' => $data['parent_license_id'],
			'status'            => $data['status'],
			'activation_limit'  => $data['activation_limit'],
			'created_at'        => current_time( 'mysql' ),
			'expires_at'        => $data['expires_at'],
		],
		[ '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s' ]
	);

	if ( ! $inserted ) {
		return new WP_Error( 'rl_db_insert_error', __( 'خطا در ذخیره‌سازی لایسنس در پایگاه داده.', 'readylicense' ) );
	}

	$license_id = $wpdb->insert_id;
	
	// ثبت لاگ سیستمی
	rl_log( $license_id, 'created', 'لایسنس جدید با موفقیت صادر شد.' );

	return $license_id;
}

/**
 * دریافت اطلاعات کامل یک لایسنس
 *
 * @param string|int $key_or_id کلید لایسنس یا شناسه عددی آن
 * @return object|null آبجکت لایسنس یا null در صورت عدم وجود
 */
function rl_get_license( $key_or_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'rl_licenses';

	if ( is_numeric( $key_or_id ) ) {
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $key_or_id ) );
	} else {
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE license_key = %s", $key_or_id ) );
	}
}

/**
 * منطق اصلی فعال‌سازی لایسنس روی یک دامنه
 * این تابع تمام قوانین (انقضا، وضعیت، تعداد نصب) را بررسی می‌کند.
 *
 * @param string $license_key کلید لایسنس
 * @param string $domain دامنه سایت مقصد
 * @return true|WP_Error در صورت موفقیت true و در غیر این صورت WP_Error
 */
function rl_activate_license( $license_key, $domain ) {
	global $wpdb;

	// ۱. یافتن لایسنس
	$license = rl_get_license( $license_key );

	if ( ! $license ) {
		return new WP_Error( 'rl_invalid_key', __( 'لایسنس وارد شده نامعتبر است یا وجود ندارد.', 'readylicense' ) );
	}

	// ۲. بررسی وضعیت (Status)
	if ( $license->status !== 'active' ) {
		return new WP_Error( 'rl_license_inactive', __( 'این لایسنس غیرفعال، مسدود یا تعلیق شده است.', 'readylicense' ) );
	}

	// ۳. بررسی تاریخ انقضا (Expiration)
	if ( ! empty( $license->expires_at ) && strtotime( $license->expires_at ) < time() ) {
		return new WP_Error( 'rl_license_expired', __( 'مهلت اعتبار این لایسنس به پایان رسیده است.', 'readylicense' ) );
	}

	// ۴. نرمال‌سازی و استانداردسازی دامنه
	$clean_domain = rl_normalize_domain( $domain );
	if ( empty( $clean_domain ) ) {
		return new WP_Error( 'rl_invalid_domain', __( 'آدرس دامنه نامعتبر است.', 'readylicense' ) );
	}

	$table_activations = $wpdb->prefix . 'rl_activations';

	// ۵. بررسی اینکه آیا قبلاً روی همین دامنه فعال شده؟
	$existing_activation = $wpdb->get_row( $wpdb->prepare( 
		"SELECT id FROM $table_activations WHERE license_id = %d AND domain = %s", 
		$license->id, 
		$clean_domain 
	) );

	if ( $existing_activation ) {
		// اگر قبلاً فعال بوده، فقط زمان آخرین چک را آپدیت کن و موفقیت برگردان
		$wpdb->update( 
			$table_activations, 
			[ 
				'last_check_at' => current_time( 'mysql' ), 
				'ip_address'    => rl_get_ip() 
			], 
			[ 'id' => $existing_activation->id ] 
		);
		return true;
	}

	// ۶. بررسی محدودیت تعداد نصب (Activation Limit)
	// نکته: تعداد را مستقیماً از جدول فعال‌سازی‌ها می‌شماریم تا دقیق باشد
	$active_count = $wpdb->get_var( $wpdb->prepare( 
		"SELECT COUNT(id) FROM $table_activations WHERE license_id = %d", 
		$license->id 
	) );

	if ( $active_count >= $license->activation_limit ) {
		return new WP_Error( 'rl_limit_reached', sprintf( __( 'سقف نصب این لایسنس (%d دامنه) تکمیل شده است.', 'readylicense' ), $license->activation_limit ) );
	}

	// ۷. ثبت فعال‌سازی جدید
	$inserted = $wpdb->insert(
		$table_activations,
		[
			'license_id'    => $license->id,
			'domain'        => $clean_domain,
			'ip_address'    => rl_get_ip(),
			'platform'      => isset($_SERVER['HTTP_USER_AGENT']) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 99 ) : 'Unknown',
			'activated_at'  => current_time( 'mysql' ),
			'last_check_at' => current_time( 'mysql' ),
		],
		[ '%d', '%s', '%s', '%s', '%s', '%s' ]
	);

	if ( $inserted ) {
		// به‌روزرسانی شمارنده کش شده در جدول لایسنس‌ها (برای سرعت نمایش در پنل)
		$wpdb->update( 
			$wpdb->prefix . 'rl_licenses', 
			[ 'activation_count' => $active_count + 1 ], 
			[ 'id' => $license->id ] 
		);

		rl_log( $license->id, 'activated', "فعال‌سازی موفق روی دامنه: $clean_domain" );
		return true;
	}

	return new WP_Error( 'rl_activation_failed', __( 'خطای سیستمی در ثبت فعال‌سازی.', 'readylicense' ) );
}

/**
 * غیرفعال‌سازی لایسنس (حذف دامنه)
 * معمولاً توسط کاربر از پنل کاربری یا توسط ادمین انجام می‌شود.
 *
 * @param int $license_id شناسه لایسنس
 * @param string $domain دامنه مورد نظر برای حذف
 * @return bool نتیجه عملیات
 */
function rl_deactivate_license( $license_id, $domain ) {
	global $wpdb;
	
	$clean_domain = rl_normalize_domain( $domain );
	$table = $wpdb->prefix . 'rl_activations';

	// حذف رکورد
	$deleted = $wpdb->delete( 
		$table, 
		[ 'license_id' => $license_id, 'domain' => $clean_domain ], 
		[ '%d', '%s' ] 
	);

	if ( $deleted ) {
		// کاهش شمارنده
		$wpdb->query( $wpdb->prepare( 
			"UPDATE {$wpdb->prefix}rl_licenses SET activation_count = GREATEST(0, activation_count - 1) WHERE id = %d", 
			$license_id 
		) );
		
		rl_log( $license_id, 'deactivated', "دامنه $clean_domain غیرفعال (حذف) شد." );
		return true;
	}

	return false;
}

/**
 * ابزار: نرمال‌سازی دامنه
 * ورودی‌های مختلف (http, https, www, subfolder) را به فرمت استاندارد domain.com تبدیل می‌کند.
 */
function rl_normalize_domain( $url ) {
	$url = strtolower( trim( $url ) );
	
	// حذف پروتکل
	if ( strpos( $url, 'http://' ) === 0 ) {
		$url = substr( $url, 7 );
	} elseif ( strpos( $url, 'https://' ) === 0 ) {
		$url = substr( $url, 8 );
	}

	// حذف www
	if ( strpos( $url, 'www.' ) === 0 ) {
		$url = substr( $url, 4 );
	}

	// حذف مسیرهای اضافی (فقط هاست اصلی مهم است)
	$url_parts = explode( '/', $url );
	$domain = $url_parts[0];

	// حذف پورت (اگر وجود داشته باشد مثل localhost:8000)
	$domain_parts = explode( ':', $domain );
	$domain = $domain_parts[0];

	return $domain;
}

/**
 * ابزار: دریافت آدرس IP واقعی کاربر
 * با پشتیبانی از کلودفلر و پروکسی‌ها.
 */
function rl_get_ip() {
	$ip = '0.0.0.0';

	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP']; // کلودفلر
	} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$ip = trim( $ips[0] );
	} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * ابزار: سیستم لاگ‌برداری مرکزی
 * ثبت وقایع در جدول rl_logs برای پیگیری‌های بعدی.
 */
function rl_log( $license_id, $action, $message = '', $ip = '' ) {
	global $wpdb;
	
	if ( empty( $ip ) ) {
		$ip = rl_get_ip();
	}

	$user_id = is_user_logged_in() ? get_current_user_id() : 0;

	$wpdb->insert(
		$wpdb->prefix . 'rl_logs',
		[
			'license_id' => $license_id,
			'user_id'    => $user_id,
			'action'     => $action,
			'message'    => $message,
			'ip_address' => $ip,
			'created_at' => current_time( 'mysql' ),
		],
		[ '%d', '%d', '%s', '%s', '%s', '%s' ]
	);
}