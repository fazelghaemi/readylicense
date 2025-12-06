<?php
/**
 * توابع کمکی و هسته منطقی ReadyLicense
 * تمام تعاملات با دیتابیس از طریق این توابع انجام می‌شود.
 *
 * @package ReadyLicense
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * تولید یک کلید لایسنس منحصر به فرد و ایمن
 * فرمت: RL-XXXX-XXXX-XXXX-XXXX
 */
function rl_generate_license_key( $prefix = '' ) {
	if ( empty( $prefix ) ) {
		$prefix = get_option( 'readylicense_license_prefix', 'RL-' );
	}
	
	// تولید بایت‌های تصادفی و تبدیل به هگز
	$random_hex = bin2hex( random_bytes( 8 ) ); // 16 کاراکتر
	$formatted  = strtoupper( $prefix . $random_hex );
	
	return $formatted;
}

/**
 * ایجاد لایسنس جدید در دیتابیس
 *
 * @param array $args اطلاعات لایسنس
 * @return int|WP_Error شناسه لایسنس یا خطا
 */
function rl_create_license( $args = [] ) {
	global $wpdb;

	$defaults = [
		'license_key'      => rl_generate_license_key(),
		'product_id'       => 0,
		'user_id'          => get_current_user_id(),
		'order_id'         => 0,
		'status'           => 'active',
		'activation_limit' => get_option( 'readylicense_max_domains', 1 ),
		'expires_at'       => null, // null یعنی مادام‌العمر
	];

	$data = wp_parse_args( $args, $defaults );

	// اعتبارسنجی اولیه
	if ( empty( $data['product_id'] ) || empty( $data['user_id'] ) ) {
		return new WP_Error( 'rl_invalid_data', __( 'اطلاعات محصول یا کاربر نامعتبر است.', 'readylicense' ) );
	}

	$table = $wpdb->prefix . 'rl_licenses';
	
	// بررسی تکراری نبودن کلید
	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE license_key = %s", $data['license_key'] ) );
	if ( $exists ) {
		$data['license_key'] = rl_generate_license_key(); // تلاش مجدد با کلید جدید
	}

	$inserted = $wpdb->insert(
		$table,
		[
			'license_key'      => $data['license_key'],
			'product_id'       => $data['product_id'],
			'user_id'          => $data['user_id'],
			'order_id'         => $data['order_id'],
			'status'           => $data['status'],
			'activation_limit' => $data['activation_limit'],
			'created_at'       => current_time( 'mysql' ),
			'expires_at'       => $data['expires_at'],
		],
		[ '%s', '%d', '%d', '%d', '%s', '%d', '%s', '%s' ]
	);

	if ( ! $inserted ) {
		return new WP_Error( 'rl_db_error', __( 'خطا در ذخیره لایسنس در دیتابیس.', 'readylicense' ) );
	}

	$license_id = $wpdb->insert_id;
	
	// ثبت لاگ
	rl_log( $license_id, 'created', 'لایسنس جدید ایجاد شد.' );

	return $license_id;
}

/**
 * دریافت اطلاعات کامل یک لایسنس
 *
 * @param string|int $key_or_id کلید لایسنس یا شناسه
 * @return object|null
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
 * فعال‌سازی لایسنس روی یک دامنه (Activation Logic)
 * این تابع توسط API صدا زده می‌شود.
 *
 * @param string $license_key کلید لایسنس
 * @param string $domain دامنه سایت کاربر
 * @return true|WP_Error
 */
function rl_activate_license( $license_key, $domain ) {
	global $wpdb;

	$license = rl_get_license( $license_key );

	if ( ! $license ) {
		return new WP_Error( 'rl_invalid_key', __( 'کلید لایسنس نامعتبر است.', 'readylicense' ) );
	}

	if ( $license->status !== 'active' ) {
		return new WP_Error( 'rl_inactive', __( 'این لایسنس غیرفعال یا مسدود شده است.', 'readylicense' ) );
	}

	// بررسی انقضا
	if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
		return new WP_Error( 'rl_expired', __( 'مدت اعتبار لایسنس به پایان رسیده است.', 'readylicense' ) );
	}

	// نرمال‌سازی دامنه (حذف http و www)
	$clean_domain = rl_normalize_domain( $domain );

	// بررسی اینکه آیا قبلاً روی این دامنه فعال شده؟
	$table_activations = $wpdb->prefix . 'rl_activations';
	$existing = $wpdb->get_row( $wpdb->prepare( 
		"SELECT * FROM $table_activations WHERE license_id = %d AND domain = %s", 
		$license->id, 
		$clean_domain 
	) );

	if ( $existing ) {
		// آپدیت زمان آخرین بررسی
		$wpdb->update( 
			$table_activations, 
			[ 'last_check_at' => current_time( 'mysql' ), 'ip_address' => rl_get_ip() ], 
			[ 'id' => $existing->id ] 
		);
		return true; // قبلاً فعال بوده، مشکلی نیست
	}

	// بررسی محدودیت تعداد نصب
	// نکته: ما تعداد را از جدول اکتیویشن می‌شماریم تا دقیق باشد
	$current_activations = $wpdb->get_var( $wpdb->prepare( 
		"SELECT COUNT(*) FROM $table_activations WHERE license_id = %d", 
		$license->id 
	) );

	if ( $current_activations >= $license->activation_limit ) {
		return new WP_Error( 'rl_limit_reached', __( 'تعداد نصب مجاز برای این لایسنس تکمیل شده است.', 'readylicense' ) );
	}

	// ثبت فعال‌سازی جدید
	$inserted = $wpdb->insert(
		$table_activations,
		[
			'license_id'    => $license->id,
			'domain'        => $clean_domain,
			'ip_address'    => rl_get_ip(),
			'platform'      => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 99) : 'Unknown',
			'activated_at'  => current_time( 'mysql' ),
			'last_check_at' => current_time( 'mysql' ),
		]
	);

	if ( $inserted ) {
		// آپدیت تعداد استفاده در جدول اصلی (برای سرعت بیشتر در نمایش لیست‌ها)
		$wpdb->update( 
			$wpdb->prefix . 'rl_licenses', 
			[ 'activation_count' => $current_activations + 1 ], 
			[ 'id' => $license->id ] 
		);

		rl_log( $license->id, 'activated', "فعال‌سازی روی دامنه $clean_domain" );
		return true;
	}

	return new WP_Error( 'rl_db_error', __( 'خطای پایگاه داده هنگام فعال‌سازی.', 'readylicense' ) );
}

/**
 * غیرفعال‌سازی لایسنس (Deactivation) - حذف دامنه
 */
function rl_deactivate_license( $license_id, $domain ) {
	global $wpdb;
	
	$clean_domain = rl_normalize_domain( $domain );
	$table = $wpdb->prefix . 'rl_activations';

	// حذف رکورد فعال‌سازی
	$deleted = $wpdb->delete( 
		$table, 
		[ 'license_id' => $license_id, 'domain' => $clean_domain ], 
		[ '%d', '%s' ] 
	);

	if ( $deleted ) {
		// کاهش کانتر در جدول اصلی
		$wpdb->query( $wpdb->prepare( 
			"UPDATE {$wpdb->prefix}rl_licenses SET activation_count = activation_count - 1 WHERE id = %d AND activation_count > 0", 
			$license_id 
		) );
		
		rl_log( $license_id, 'deactivated', "حذف دامنه $clean_domain توسط کاربر" );
		return true;
	}

	return false;
}

/**
 * ابزار: تمیز کردن آدرس دامنه
 * تبدیل https://www.example.com/folder به example.com
 */
function rl_normalize_domain( $url ) {
	$url = strtolower( trim( $url ) );
	// حذف پروتکل
	$url = preg_replace( '#^https?://#', '', $url );
	// حذف www
	$url = preg_replace( '#^www\.#', '', $url );
	// حذف مسیرهای اضافی و کوئری استرینگ
	$url = explode( '/', $url )[0];
	$url = explode( ':', $url )[0]; // حذف پورت اگر باشد
	
	return $url;
}

/**
 * ابزار: دریافت IP واقعی کاربر
 */
function rl_get_ip() {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		return $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// ممکن است چند IP باشد، اولی را برمی‌داریم
		$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		return trim( $ips[0] );
	} else {
		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
}

/**
 * سیستم لاگ‌برداری حرفه‌ای
 */
function rl_log( $license_id, $action, $message = '', $ip = '' ) {
	global $wpdb;
	
	if ( empty( $ip ) ) {
		$ip = rl_get_ip();
	}

	$wpdb->insert(
		$wpdb->prefix . 'rl_logs',
		[
			'license_id' => $license_id,
			'user_id'    => get_current_user_id(),
			'action'     => $action,
			'message'    => $message,
			'ip_address' => $ip,
			'created_at' => current_time( 'mysql' ),
		]
	);
}