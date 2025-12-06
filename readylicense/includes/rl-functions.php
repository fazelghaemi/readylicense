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
	
	return strtoupper( $prefix . bin2hex( random_bytes( 8 ) ) );
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
 * فعال‌سازی لایسنس روی یک دامنه (Activation)
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
		return true; // قبلاً فعال بوده، مشکلی نیست
	}

	// بررسی محدودیت تعداد نصب
	$current_activations = $wpdb->get_var( $wpdb->prepare( 
		"SELECT COUNT(*) FROM $table_activations WHERE license_id = %d", 
		$license->id 
	) );

	if ( $current_activations >= $license->activation_limit ) {
		return new WP_Error( 'rl_limit_reached', __( 'تعداد نصب مجاز برای این لایسنس تکمیل شده است.', 'readylicense' ) );
	}

	// ثبت فعال‌سازی جدید
	$wpdb->insert(
		$table_activations,
		[
			'license_id' => $license->id,
			'domain'     => $clean_domain,
			'ip_address' => rl_get_ip(),
			'platform'   => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 99) : 'Unknown',
		]
	);

	// آپدیت تعداد استفاده در جدول اصلی (برای سرعت بیشتر در نمایش)
	$wpdb->update( 
		$wpdb->prefix . 'rl_licenses', 
		[ 'activation_count' => $current_activations + 1 ], 
		[ 'id' => $license->id ] 
	);

	rl_log( $license->id, 'activated', "فعال‌سازی روی دامنه $clean_domain" );

	return true;
}

/**
 * غیرفعال‌سازی لایسنس (Deactivation) - حذف دامنه
 */
function rl_deactivate_license( $license_id, $domain ) {
	global $wpdb;
	
	$clean_domain = rl_normalize_domain( $domain );
	$table = $wpdb->prefix . 'rl_activations';

	$deleted = $wpdb->delete( 
		$table, 
		[ 'license_id' => $license_id, 'domain' => $clean_domain ], 
		[ '%d', '%s' ] 
	);

	if ( $deleted ) {
		// کاهش کانتر
		$wpdb->query( $wpdb->prepare( 
			"UPDATE {$wpdb->prefix}rl_licenses SET activation_count = activation_count - 1 WHERE id = %d", 
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
	$url = preg_replace( '#^https?://#', '', $url );
	$url = preg_replace( '#^www\.#', '', $url );
	$url = explode( '/', $url )[0]; // حذف مسیرهای اضافی
	return $url;
}

/**
 * ابزار: دریافت IP واقعی کاربر
 */
function rl_get_ip() {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		return $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		return $_SERVER['REMOTE_ADDR'];
	}
}

/**
 * سیستم لاگ‌برداری حرفه‌ای
 */
function rl_log( $license_id, $action, $message = '', $data = null ) {
	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'rl_logs',
		[
			'license_id' => $license_id,
			'user_id'    => get_current_user_id(),
			'action'     => $action,
			'message'    => $message,
			'data'       => is_array($data) ? json_encode($data) : $data,
			'ip_address' => rl_get_ip(),
		]
	);
}