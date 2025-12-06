<?php
/**
 * Plugin Name:       ReadyLicense
 * Plugin URI:        https://readystudio.ir/readylicense/
 * Description:       سامانه جامع مدیریت لایسنس، محافظت از کد و فروش محصولات دیجیتال در ووکامرس (نسخه حرفه‌ای).
 * Version:           2.0.1
 * Author:            ReadyStudio
 * Author URI:        https://readystudio.ir
 * Text Domain:       readylicense
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// جلوگیری از دسترسی مستقیم برای امنیت
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس اصلی افزونه ReadyLicense
 * پیاده‌سازی شده با الگوی Singleton برای اطمینان از پایداری و بهینگی مصرف حافظه.
 *
 * @package ReadyLicense
 * @since 2.0.1
 */
final class ReadyLicense {

	/**
	 * نسخه فعلی افزونه
	 * @var string
	 */
	public $version = '2.0.1';

	/**
	 * نمونه یگانه کلاس (Singleton Instance)
	 * @var ReadyLicense
	 */
	protected static $_instance = null;

	/**
	 * دسترسی به نمونه اصلی کلاس
	 * این متد تضمین می‌کند که تنها یک نمونه از این کلاس در کل طول عمر اجرای اسکریپت وجود داشته باشد.
	 *
	 * @return ReadyLicense
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * سازنده کلاس
	 * اجرای متدهای راه‌اندازی به ترتیب اولویت و وابستگی.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
		$this->instantiate_classes();
	}

	/**
	 * تعریف ثابت‌های سراسری
	 * استفاده از ثابت‌ها برای جلوگیری از هاردکد کردن مسیرها و لینک‌ها.
	 */
	private function define_constants() {
		define( 'RL_VERSION', $this->version );
		define( 'RL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'RL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'RL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		define( 'RL_API_NAMESPACE', 'readylicense/v1' ); // فضای نام API
		
		// لینک‌های پشتیبانی و مستندات طبق درخواست شما
		define( 'RL_DOCS_URL', 'https://readystudio.ir/readylicense/doc' );
		define( 'RL_SUPPORT_URL', 'https://readystudio.ir/support' );
	}

	/**
	 * بارگذاری فایل‌های هسته و کلاس‌ها
	 * فایل‌ها فقط زمانی که نیاز باشند لود می‌شوند (Performance Tuning).
	 */
	private function includes() {
		/**
		 * 1. توابع کمکی (Helper Functions)
		 * توابعی که در سراسر افزونه (ادمین و فرانت) استفاده می‌شوند.
		 */
		require_once RL_PLUGIN_DIR . 'includes/rl-functions.php';

		/**
		 * 2. کلاس فعال‌سازی (Activator)
		 * مدیریت ساخت جداول دیتابیس و به‌روزرسانی‌ها.
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-activator.php';

		/**
		 * 3. هسته API (REST API Handler)
		 * مدیریت درخواست‌های از راه دور برای چک کردن لایسنس.
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-api.php';

		/**
		 * 4. مدیریت درخواست‌های AJAX
		 * پردازش عملیات بدون رفرش (مثل تولید لایسنس، مدیریت دامین).
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-ajax.php';

		/**
		 * 5. کلاس‌های سمت کاربر (Frontend)
		 * مدیریت نمایش تب لایسنس‌ها در حساب کاربری ووکامرس.
		 */
		require_once RL_PLUGIN_DIR . 'includes/class-rl-frontend.php';

		/**
		 * 6. کلاس‌های سمت مدیریت (Admin)
		 * فقط در محیط ادمین بارگذاری می‌شوند تا سربار سرور کاهش یابد.
		 */
		if ( is_admin() ) {
			require_once RL_PLUGIN_DIR . 'includes/class-rl-admin.php';
		}
	}

	/**
	 * نمونه‌سازی کلاس‌های منطقی
	 * این بخش موتورهای داخلی افزونه را روشن می‌کند.
	 */
	private function instantiate_classes() {
		// راه‌اندازی مسیرهای API (همیشه باید اجرا شود تا 404 ندهد)
		new ReadyLicense_API();

		// راه‌اندازی هندلر AJAX (برای پردازش درخواست‌های ناهمگام)
		new ReadyLicense_Ajax();

		// راه‌اندازی بخش کاربری (My Account)
		new ReadyLicense_Frontend();

		// راه‌اندازی بخش ادمین (فقط در پنل مدیریت)
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

		// هوک‌های چرخه حیات افزونه (نصب / حذف)
		// این هوک‌ها کلاس Activator را صدا می‌زنند تا جداول دیتابیس ساخته شود
		register_activation_hook( __FILE__, [ 'ReadyLicense_Activator', 'activate' ] );
		register_deactivation_hook( __FILE__, [ 'ReadyLicense_Activator', 'deactivate' ] );
	}

	/**
	 * بارگذاری تکست‌دامین برای ترجمه
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
	 * فقط در صفحه مربوط به ReadyLicense اجرا می‌شود تا تداخل ایجاد نکند.
	 */
	public function enqueue_admin_assets( $hook ) {
		// فقط در صفحه خود افزونه لود شو! (برای جلوگیری از کندی پیشخوان)
		if ( strpos( $hook, 'readylicense' ) === false ) {
			return;
		}

		// استایل‌های ادمین (نسخه جدید و بهینه)
		wp_enqueue_style( 
			'rl-admin-css', 
			RL_PLUGIN_URL . 'assets/css/admin-styles.css', 
			[], 
			RL_VERSION 
		);

		// اسکریپت‌های ادمین
		wp_enqueue_script( 
			'rl-admin-js', 
			RL_PLUGIN_URL . 'assets/js/admin-scripts.js', 
			['jquery'], 
			RL_VERSION, 
			true 
		);

		// ارسال داده‌ها به JS (برای AJAX و ترجمه‌ها)
		// این آبجکت rl_obj در فایل admin-scripts.js استفاده می‌شود
		wp_localize_script( 'rl-admin-js', 'rl_obj', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'rl_admin_nonce' ),
			'strings'  => [
				'success' => __( 'عملیات با موفقیت انجام شد.', 'readylicense' ),
				'error'   => __( 'خطایی رخ داد. لطفاً دوباره تلاش کنید.', 'readylicense' ),
				'loading' => __( 'در حال پردازش...', 'readylicense' ),
				'copied'  => __( 'کپی شد!', 'readylicense' )
			]
		]);
	}

	/**
	 * بارگذاری دارایی‌های سمت کاربر (CSS/JS)
	 * فقط در صفحات حساب کاربری ووکامرس یا صفحاتی که شورت‌کد دارند.
	 */
	public function enqueue_frontend_assets() {
		global $post;

		// شرط هوشمند برای لود اسکریپت‌ها
		$is_account_page = function_exists('is_account_page') && is_account_page();
		$has_shortcode   = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ready_license_dashboard' );

		if ( ! $is_account_page && ! $has_shortcode ) {
			return;
		}

		// استایل‌های فرانت
		wp_enqueue_style( 
			'rl-front-css', 
			RL_PLUGIN_URL . 'assets/css/style.css', 
			[], 
			RL_VERSION 
		);

		// اسکریپت‌های فرانت
		wp_enqueue_script( 
			'rl-user-js', 
			RL_PLUGIN_URL . 'assets/js/user-script.js', 
			['jquery'], // وابستگی به jQuery (چون ووکامرس هم استفاده می‌کند)
			RL_VERSION, 
			true 
		);
		
		// ارسال داده‌ها به JS فرانت
		// این آبجکت rl_front در فایل user-script.js استفاده می‌شود
		wp_localize_script( 'rl-user-js', 'rl_front', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'rl_user_nonce' ),
			'strings'  => [
				'copied' => __( 'کپی شد!', 'readylicense' ),
				'error'  => __( 'ارتباط با سرور برقرار نشد.', 'readylicense' )
			]
		]);
	}

	/**
	 * جلوگیری از کلون شدن کلاس (Singleton Protection)
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'readylicense' ), '2.0.1' );
	}

	/**
	 * جلوگیری از Unserialize شدن کلاس (Singleton Protection)
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'readylicense' ), '2.0.1' );
	}
}

/**
 * تابع دسترسی سریع به نمونه کلاس
 * @return ReadyLicense
 */
function RL() {
	return ReadyLicense::instance();
}

// استارت موتور افزونه
RL();