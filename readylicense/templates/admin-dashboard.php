<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap rl-admin-wrapper">
    
    <!-- Top Header -->
    <header class="rl-header">
        <div class="rl-header-left">
            <div class="rl-brand">
                <img src="<?php echo RL_PLUGIN_URL . 'assets/img/readystudio-logo.png'; ?>" alt="ReadyStudio" class="rl-logo-img">
                <div class="rl-brand-text">
                    <h1>ReadyLicense</h1>
                    <span class="rl-version">v<?php echo RL_VERSION; ?> Pro</span>
                </div>
            </div>
        </div>
        <div class="rl-header-right">
            <a href="https://readystudio.com/docs" target="_blank" class="rl-btn rl-btn-text">
                <span class="dashicons dashicons-book"></span> <?php _e('مستندات', 'readylicense'); ?>
            </a>
            <a href="https://readystudio.com/support" target="_blank" class="rl-btn rl-btn-text">
                <span class="dashicons dashicons-sos"></span> <?php _e('پشتیبانی', 'readylicense'); ?>
            </a>
        </div>
    </header>

    <div class="rl-main-container">
        
        <!-- Sidebar Navigation -->
        <aside class="rl-sidebar">
            <nav>
                <ul>
                    <li class="active" data-tab="users">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span class="text"><?php _e('مدیریت کاربران', 'readylicense'); ?></span>
                    </li>
                    <li data-tab="products">
                        <span class="dashicons dashicons-products"></span>
                        <span class="text"><?php _e('لایسنس محصولات', 'readylicense'); ?></span>
                    </li>
                    <li data-tab="settings">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <span class="text"><?php _e('تنظیمات و متون', 'readylicense'); ?></span>
                    </li>
                    <li data-tab="encoder">
                        <span class="dashicons dashicons-lock"></span>
                        <span class="text"><?php _e('محافظت از کد (Encoder)', 'readylicense'); ?></span>
                    </li>
                    <li data-tab="api-help">
                        <span class="dashicons dashicons-rest-api"></span>
                        <span class="text"><?php _e('راهنمای اتصال API', 'readylicense'); ?></span>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Content Area -->
        <main class="rl-content">
            
            <!-- Users Tab -->
            <section id="tab-users" class="rl-tab-pane active">
                <div class="rl-section-header">
                    <h2><?php _e('کاربران دارای لایسنس', 'readylicense'); ?></h2>
                    <div class="rl-search-box">
                        <span class="dashicons dashicons-search"></span>
                        <input type="text" id="rl-user-search" placeholder="<?php _e('جستجو در کاربران...', 'readylicense'); ?>">
                    </div>
                </div>
                
                <div class="rl-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('نام و ایمیل کاربر', 'readylicense'); ?></th>
                                <th><?php _e('وضعیت', 'readylicense'); ?></th>
                                <th class="text-end"><?php _e('عملیات', 'readylicense'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rl-users-list-body">
                            <!-- AJAX Load -->
                        </tbody>
                    </table>
                </div>
                <div id="rl-users-pagination" class="rl-pagination"></div>
            </section>

            <!-- Products Tab -->
            <section id="tab-products" class="rl-tab-pane">
                <div class="rl-section-header">
                    <h2><?php _e('مدیریت محصولات ووکامرس', 'readylicense'); ?></h2>
                    <div class="rl-search-box">
                        <span class="dashicons dashicons-search"></span>
                        <input type="text" id="rl-product-search" placeholder="<?php _e('جستجوی محصول...', 'readylicense'); ?>">
                    </div>
                </div>

                <div class="rl-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('نام محصول', 'readylicense'); ?></th>
                                <th><?php _e('کد مرچنت (Merchant ID)', 'readylicense'); ?></th>
                                <th class="text-end"><?php _e('عملیات', 'readylicense'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rl-products-list-body"></tbody>
                    </table>
                </div>
                <div id="rl-products-pagination" class="rl-pagination"></div>
            </section>

            <!-- Settings Tab -->
            <section id="tab-settings" class="rl-tab-pane">
                <div class="rl-section-header">
                    <h2><?php _e('پیکربندی سیستم', 'readylicense'); ?></h2>
                </div>
                <form id="rl-settings-form">
                    <div class="rl-card">
                        <h3><?php _e('تنظیمات عمومی', 'readylicense'); ?></h3>
                        <div class="rl-form-group">
                            <label><?php _e('حداکثر تعداد دامنه مجاز', 'readylicense'); ?></label>
                            <input type="number" name="settings[readylicense_max_domains]" value="<?php echo get_option('readylicense_max_domains', 1); ?>" class="rl-input-small">
                            <p class="description"><?php _e('تعداد دامنه‌هایی که کاربر می‌تواند برای یک خرید ثبت کند.', 'readylicense'); ?></p>
                        </div>
                    </div>

                    <div class="rl-card">
                        <h3><?php _e('شخصی‌سازی متن‌ها (Labels)', 'readylicense'); ?></h3>
                        <div class="rl-grid-2">
                            <div class="rl-form-group">
                                <label><?php _e('عنوان منو', 'readylicense'); ?></label>
                                <input type="text" name="settings[readylicense_label_menu]" value="<?php echo get_option('readylicense_label_menu', 'لایسنس‌های من'); ?>">
                            </div>
                            <div class="rl-form-group">
                                <label><?php _e('عنوان دکمه مدیریت', 'readylicense'); ?></label>
                                <input type="text" name="settings[readylicense_label_btn]" value="<?php echo get_option('readylicense_label_btn', 'مدیریت دامنه'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="rl-form-actions">
                        <button type="submit" class="rl-btn rl-btn-primary"><?php _e('ذخیره تغییرات', 'readylicense'); ?></button>
                        <span class="spinner"></span>
                    </div>
                </form>
            </section>

            <!-- Encoder Tab -->
            <section id="tab-encoder" class="rl-tab-pane">
                <div class="rl-section-header">
                    <h2><?php _e('PHP Encoder', 'readylicense'); ?></h2>
                </div>
                <div class="rl-card">
                    <p class="description"><?php _e('کد PHP خود را در اینجا قرار دهید تا نسخه محافظت شده را دریافت کنید. این روش کد را فشرده و مبهم می‌کند.', 'readylicense'); ?></p>
                    <textarea id="rl-raw-code" rows="10" class="rl-code-editor" placeholder="<?php _e('کد خود را اینجا پیست کنید...', 'readylicense'); ?>"></textarea>
                    
                    <div class="rl-form-actions">
                        <button type="button" id="rl-btn-encode" class="rl-btn rl-btn-primary"><?php _e('رمزنگاری کن', 'readylicense'); ?></button>
                    </div>

                    <div id="rl-encoded-result" style="display:none; margin-top:20px;">
                        <label><?php _e('کد خروجی:', 'readylicense'); ?></label>
                        <textarea id="rl-encoded-code" rows="10" class="rl-code-editor" readonly></textarea>
                    </div>
                </div>
            </section>

            <!-- API Help Tab -->
            <section id="tab-api-help" class="rl-tab-pane">
                <div class="rl-section-header">
                    <h2><?php _e('راهنمای اتصال به API', 'readylicense'); ?></h2>
                </div>
                <div class="rl-card">
                    <p><?php _e('برای بررسی وضعیت لایسنس در قالب یا افزونه خود، از قطعه کد استاندارد زیر استفاده کنید. این کد به طور خودکار به REST API سایت شما متصل می‌شود.', 'readylicense'); ?></p>
                    
                    <div class="rl-code-block" dir="ltr">
<pre>
// ReadyLicense Client Code
function check_my_license( $license_key ) {
    $api_url = '<?php echo home_url('/wp-json/readylicense/v1/check'); ?>';
    $domain  = $_SERVER['HTTP_HOST'];

    $response = wp_remote_post( $api_url, [
        'body' => [
            'license_key' => $license_key,
            'domain'      => $domain
        ]
    ]);

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    
    if ( isset( $body['success'] ) && $body['success'] === true ) {
        return true; // License is Active
    }

    return false; // License is Invalid or Expired
}
</pre>
                    </div>
                </div>
            </section>

        </main>
    </div>
</div>