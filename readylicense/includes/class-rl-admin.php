<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class ReadyLicense_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'wp_ajax_rl_admin_action', [ $this, 'handle_ajax' ] );
    }

    public function add_menu() {
        add_menu_page(
            __( 'ReadyLicense', 'readylicense' ),
            __( 'ReadyLicense', 'readylicense' ),
            'manage_options',
            'readylicense',
            [ $this, 'render_dashboard' ],
            'dashicons-shield-alt', // آیکون مدرن‌تر
            56
        );
    }

    public function render_dashboard() {
        require_once RL_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }

    public function handle_ajax() {
        check_ajax_referer( 'rl_admin_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $type = sanitize_text_field( $_POST['request_type'] );

        switch ( $type ) {
            case 'get_users':
                $this->get_users_list();
                break;
            case 'get_user_details':
                $this->get_user_details();
                break;
            case 'get_products':
                $this->get_products_list();
                break;
            case 'generate_merchant':
                $this->generate_merchant();
                break;
            case 'save_settings':
                $this->save_settings();
                break;
            case 'encode_code':
                $this->encode_php();
                break;
            default:
                wp_send_json_error( 'Invalid Request' );
        }
    }

    // --- Logic Methods ---

    private function get_users_list() {
        $paged  = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $number = 10;
        $offset = ($paged - 1) * $number;

        $args = [
            'number' => $number,
            'offset' => $offset,
            'fields' => ['ID', 'user_login', 'display_name', 'user_email'],
            'search' => $search ? "*{$search}*" : '',
            'search_columns' => ['user_login', 'user_email', 'display_name']
        ];

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();

        ob_start();
        if ( $users ) {
            foreach ( $users as $user ) {
                // شمارش لایسنس‌های فعال کاربر (برای نمایش در جدول)
                $active_licenses = $this->count_user_licenses($user->ID);
                ?>
                <tr>
                    <td>
                        <div class="rl-user-info">
                            <strong><?php echo esc_html($user->display_name ?: $user->user_login); ?></strong>
                            <span><?php echo esc_html($user->user_email); ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="rl-badge <?php echo $active_licenses > 0 ? 'success' : 'neutral'; ?>">
                            <?php echo $active_licenses; ?> <?php _e('فعال', 'readylicense'); ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <button class="rl-btn rl-btn-small rl-btn-secondary view-user-licenses" data-id="<?php echo $user->ID; ?>">
                            <?php _e('مدیریت لایسنس‌ها', 'readylicense'); ?>
                        </button>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="3" class="text-center">' . __('کاربری یافت نشد.', 'readylicense') . '</td></tr>';
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'total_pages' => ceil($total_users / $number)
        ]);
    }

    private function count_user_licenses($user_id) {
        // یک کوئری ساده برای شمارش تعداد محصولاتی که کاربر خریده و لایسنس دارند
        // این فقط یک مثال است، منطق دقیق بستگی به ساختار ذخیره‌سازی شما دارد
        return count(wc_get_orders(['customer_id' => $user_id, 'status' => 'completed']));
    }

    private function get_products_list() {
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 10,
            'paged' => $paged,
            's' => $search
        ];

        $query = new WP_Query($args);
        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pid = get_the_ID();
                $code = get_post_meta($pid, '_rl_license_code', true) ?: '-';
                ?>
                <tr>
                    <td>
                        <strong><?php the_title(); ?></strong>
                        <a href="<?php echo get_edit_post_link($pid); ?>" target="_blank" class="rl-link-tiny"><?php _e('ویرایش محصول', 'readylicense'); ?></a>
                    </td>
                    <td>
                        <div class="code-box-wrapper">
                            <span class="code-box"><?php echo esc_html($code); ?></span>
                            <button class="rl-icon-btn copy-btn" title="Copy"><span class="dashicons dashicons-clipboard"></span></button>
                        </div>
                    </td>
                    <td class="text-end">
                        <button class="rl-btn rl-btn-small rl-btn-primary generate-merchant" data-id="<?php echo $pid; ?>">
                            <?php echo ($code === '-') ? __('تولید کد', 'readylicense') : __('تغییر کد', 'readylicense'); ?>
                        </button>
                    </td>
                </tr>
                <?php
            }
            wp_reset_postdata();
        } else {
            echo '<tr><td colspan="3" class="text-center">' . __('محصولی یافت نشد.', 'readylicense') . '</td></tr>';
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'total_pages' => $query->max_num_pages
        ]);
    }

    private function generate_merchant() {
        $pid = intval($_POST['product_id']);
        // تولید یک کد یونیک و خوانا
        $code = 'RL-' . strtoupper(substr(md5(time() . rand()), 0, 12));
        update_post_meta($pid, '_rl_license_code', $code);
        wp_send_json_success(['code' => $code]);
    }

    private function save_settings() {
        $data = $_POST['settings'];
        foreach ($data as $key => $val) {
            update_option(sanitize_key($key), sanitize_textarea_field($val));
        }
        wp_send_json_success();
    }

    private function encode_php() {
        $code = wp_unslash($_POST['code']);
        if (empty($code)) wp_send_json_error('کد خالی است.');

        // حذف تگ PHP
        $code = preg_replace('/^<\?php\s*/i', '', $code);
        $code = preg_replace('/^<\?=\s*/i', '', $code);
        $code = preg_replace('/\s*\?>$/', '', $code);

        // اینکودینگ پایه (برای محافظت واقعی نیاز به IonCube است، اما این برای سطح افزونه وردپرس کافیست)
        $encoded = base64_encode(gzdeflate($code, 9));
        $output = "<?php\n/* Protected by ReadyLicense */\neval(gzinflate(base64_decode('$encoded')));";

        wp_send_json_success(['encoded' => $output]);
    }
}