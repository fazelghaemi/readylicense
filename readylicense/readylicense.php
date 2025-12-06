<?php
/**
 * Plugin Name:       ReadyLicense
 * Plugin URI:        https://readylicense.com
 * Description:       سامانه جامع و پیشرفته مدیریت لایسنس محصولات دیجیتال (نسخه حرفه‌ای و سازمانی).
 * Version:           2.0.0
 * Author:            ReadyStudio
 * Author URI:        https://readystudio.com
 * Text Domain:       readylicense
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// امنیت: جلوگیری از دسترسی مستقیم به فایل
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس اصلی افزونه ReadyLicense
 * * این کلاس مسئول راه‌اندازی تمام اجزای سیستم، مدیریت وابستگی‌ها و
 * اطمینان از اجرای صحیح افزونه در محیط وردپرس است.
 *
 * @since 2.0.0
 */
final class ReadyLicense {

	/**
	 * نسخه فعلی افزونه
	 * @var string
	 */
	public $version = '2.0.0';

	/**
	 * نمونه یگانه کلاس (Singleton Instance)
	 * @var ReadyLicense
	 */
	protected static $_instance = null;

	/**
	 * دسترسی به نمونه اصلی کلاس
	 *
	 * این متد تضمین می‌کند که تنها یک نمونه از این کلاس در حافظه وجود دارد.
	 * * @return ReadyLicense
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * سازنده کلاس (Constructor)
	 * اجرای فرآیندهای اولیه هنگام بارگذاری افزونه.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
		$this->instantiate_classes();
	}

	/**
	 * تعریف ثابت‌های سراسری افزونه
	 * استفاده از ثابت‌ها برای جلوگیری از هاردکد کردن مسیرها.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir();

		define( 'RL_VERSION', $this->version );
		define( 'RL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'RL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'RL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'RL_API_NAMESPACE', 'readylicense/v1' ); // فضای نام API
	}

	/**
	 * فراخوانی و بارگذاری فایل‌های هسته
	 * ترتیب بارگذاری فایل‌ها بر اساس وابستگی‌ها بسیار مهم است.
	 */
	private function includes() {
		/**
		 * 1. توابع کمکی و ابزارهای پایه
		 * این فایل شامل توابع گلوبال برای کار با دیتابیس و لایسنس‌ها است.
		 */
		require_once RL_PLUGIN_DIR . 'includes/rl-functions.php';

		/**
		 * 2. مدیریت فعال‌سازی و دیتابیس
		 * شامل کلاس‌هایی برای ساخت جداول و مدیریت نسخه‌ها.
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-activator.php';

		/**
		 * 3. هسته API (REST API)
		 * برای ارتباط امن با سایت‌های مشتریان.
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-api.php';

		/**
		 * 4. مدیریت درخواست‌های AJAX
		 * برای پردازش درخواست‌های ناهمگام در پنل ادمین و کاربری.
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-ajax.php';

		/**
		 * 5. کلاس‌های سمت کاربر (Frontend)
		 * مدیریت نمایش در حساب کاربری ووکامرس.
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-frontend.php';

		/**
		 * 6. کلاس‌های سمت مدیریت (Admin)
		 * فقط در محیط ادمین بارگذاری می‌شوند تا سربار کاهش یابد.
		 */
		if ( is_admin() ) {
			require_once RL_PLUGIN_DIR . 'includes/class-rl-admin.php';
		}
	}

	/**
	 * نمونه‌سازی کلاس‌های اصلی
	 * اجرای منطق برنامه بعد از لود شدن فایل‌ها.
	 */
	private function instantiate_classes() {
		// راه‌اندازی API
		new ReadyLicense_API();

		// راه‌اندازی AJAX Handler
		new ReadyLicense_Ajax();

		// راه‌اندازی Frontend Logic
		new ReadyLicense_Frontend();

		// راه‌اندازی Admin Logic (فقط در ادمین)
		if ( is_admin() ) {
			new ReadyLicense_Admin();
		}
	}

	/**
	 * اتصال هوک‌های اصلی وردپرس
	 */
	private function init_hooks() {
		// بارگذاری فایل‌های ترجمه
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// بارگذاری استایل‌ها و اسکریپت‌های ادمین
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// بارگذاری استایل‌ها و اسکریپت‌های فرانت‌اند
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

		// هوک‌های چرخه حیات افزونه (فعال‌سازی / غیرفعال‌سازی)
		register_activation_hook( __FILE__, [ 'ReadyLicense_Activator', 'activate' ] );
		register_deactivation_hook( __FILE__, [ 'ReadyLicense_Activator', 'deactivate' ] );
	}

	/**
	 * بارگذاری فایل‌های ترجمه (.mo / .po)
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'readylicense',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * بارگذاری دارایی‌های سمت مدیریت (CSS/JS)
	 * به صورت هوشمند فقط در صفحات مربوط به افزونه بارگذاری می‌شود.
	 */
	public function enqueue_admin_assets( $hook ) {
		// بررسی دقیق صفحه جاری برای جلوگیری از تداخل با سایر افزونه‌ها
		if ( strpos( $hook, 'readylicense' ) === false ) {
			return;
		}

		// استایل‌های متریال دیزاین پنل ادمین
		wp_enqueue_style( 
			'rl-admin-css', 
			RL_PLUGIN_URL . 'assets/css/admin-styles.css', 
			[], 
			RL_VERSION 
		);

		// اسکریپت‌های پنل ادمین
		wp_enqueue_script( 
			'rl-admin-js', 
			RL_PLUGIN_URL . 'assets/js/admin-scripts.js', 
			['jquery'], 
			RL_VERSION, 
			true 
		);

		// ارسال متغیرها به JS برای استفاده در AJAX
		wp_localize_script( 'rl-admin-js', 'rl_obj', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'rl_admin_nonce' ), // Nonce امنیتی اختصاصی ادمین
			'strings'  => [
				'success' => __( 'عملیات با موفقیت انجام شد.', 'readylicense' ),
				'error'   => __( 'خطایی رخ داد. لطفاً مجدداً تلاش کنید.', 'readylicense' ),
				'loading' => __( 'در حال پردازش...', 'readylicense' ),
				'confirm' => __( 'آیا از انجام این عملیات اطمینان دارید؟', 'readylicense' )
			]
		]);
	}

	/**
	 * بارگذاری دارایی‌های سمت کاربر (CSS/JS)
	 * فقط در حساب کاربری ووکامرس یا صفحاتی که شورت‌کد دارند.
	 */
	public function enqueue_frontend_assets() {
		global $post;

		$is_account_page = function_exists('is_account_page') && is_account_page();
		$has_shortcode   = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ready_license_dashboard' );

		// اگر در صفحه مرتبط نیستیم، هیچ فایلی لود نشود (افزایش سرعت سایت)
		if ( ! $is_account_page && ! $has_shortcode ) {
			return;
		}

		// استایل‌های داشبورد کاربری
		wp_enqueue_style( 
			'rl-front-css', 
			RL_PLUGIN_URL . 'assets/css/style.css', 
			[], 
			RL_VERSION 
		);

		// اسکریپت‌های داشبورد کاربری
		wp_enqueue_script( 
			'rl-user-js', 
			RL_PLUGIN_URL . 'assets/js/user-script.js', 
			['jquery'], // وابستگی به jQuery (چون ووکامرس هم استفاده می‌کند)
			RL_VERSION, 
			true 
		);
		
		// ارسال متغیرها به JS فرانت‌اند
		wp_localize_script( 'rl-user-js', 'rl_front', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'rl_user_nonce' ), // Nonce امنیتی اختصاصی کاربر
			'strings'  => [
				'copied' => __( 'کپی شد!', 'readylicense' ),
				'error'  => __( 'خطا در ارتباط با سرور.', 'readylicense' )
			]
		]);
	}

	/**
	 * جلوگیری از کلون کردن کلاس (Singleton Protection)
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'readylicense' ), '1.0' );
	}

	/**
	 * جلوگیری از unserialize کردن کلاس (Singleton Protection)
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'readylicense' ), '1.0' );
	}
}

/**
 * تابع سراسری برای دسترسی آسان به نمونه کلاس
 * توسعه‌دهندگان می‌توانند از RL() برای هوک زدن یا دریافت اطلاعات استفاده کنند.
 * * @return ReadyLicense
 */
function RL() {
	return ReadyLicense::instance();
}

// استارت موتور افزونه
RL();