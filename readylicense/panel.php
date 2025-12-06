<?php

if (!defined("ABSPATH")) {
    exit;
}
add_action("admin_menu", "add_license_admin_page");
add_action("wp_ajax_get_user_licenses", "get_user_licenses");
add_action("wp_ajax_get_users_list", "get_users_list");
add_action("wp_ajax_get_domain_history", "get_domain_history");
add_action("wp_ajax_edit_user_domain", "edit_user_domain");
add_action("wp_ajax_toggle_license_status", "toggle_license_status");
add_action("wp_ajax_update_license_days", "update_license_days");
add_action("wp_ajax_get_products_list", "get_products_list");
add_action("wp_ajax_get_product_details", "get_product_details");
add_action("admin_enqueue_scripts", "enqueue_admin_license_scripts");
require_once plugin_dir_path(__FILE__) . "setting.php";
function add_license_admin_page()
{
    add_menu_page("لایسنس‌ها", "لایسنس‌ها", "manage_options", "user_licenses", "display_license_admin_page", "dashicons-shield", 60);
}
function get_user_licenses()
{
    if (!isset($_POST["user_id"]) || !is_numeric($_POST["user_id"])) {
        wp_send_json_error("Invalid user ID.");
    }
    $user_id = intval($_POST["user_id"]);
    $user = get_userdata($user_id);
    if (!$user) {
        wp_send_json_error("کاربری یافت نشد !!");
    }
    ob_start();
    display_user_licenses($user_id);
    $output = ob_get_clean();
    wp_send_json_success($output);
}
function get_users_list()
{
    $paged = isset($_POST["paged"]) ? intval($_POST["paged"]) : 1;
    $search = isset($_POST["search"]) ? sanitize_text_field($_POST["search"]) : "";
    $offset = ($paged - 1) * 10;
    $args = ["status" => "completed", "paginate" => true, "limit" => -1];
    $orders = wc_get_orders($args);
    $user_ids = [];
    foreach ($orders->orders as $order) {
        if (is_a($order, "WC_Order") && !is_a($order, "WC_Order_Refund")) {
            $user_ids[] = $order->get_customer_id();
        }
    }
    $user_ids = array_unique($user_ids);
    if (!empty($search)) {
        $search_user_ids = get_users(["search" => "*" . esc_attr($search) . "*", "fields" => "ID"]);
        $user_ids = array_intersect($user_ids, $search_user_ids);
    }
    $total_users = count($user_ids);
    $total_pages = ceil($total_users / 10);
    $user_ids = array_slice($user_ids, $offset, 10);
    ob_start();
    if ($user_ids) {
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            echo "<tr>";
            echo "<td>" . esc_html($user->display_name) . " (" . esc_html($user->user_email) . ")</td>";
            echo "<td><a href=\"#\" class=\"uk-button uk-button-primary\" data-user-id=\"" . esc_attr($user_id) . "\">مشاهده لایسنس‌ها</a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan=\"2\">کاربری یافت نشد!!</td></tr>";
    }
    $output = ob_get_clean();
    wp_send_json_success(["html" => $output, "total_pages" => $total_pages]);
}
function display_user_licenses($user_id)
{
    $user = get_userdata($user_id);
    $orders = wc_get_orders(["customer_id" => $user_id, "status" => "completed"]);
    echo "<h5>کاربر: " . esc_html($user->display_name) . " (" . esc_html($user->user_email) . ")</h5>";
    if (!empty($orders)) {
        echo "<div class=\"uk-overflow-auto\"><table class=\"uk-table uk-table-divider\"><thead><tr><th>محصول</th><th>تعداد ثبت دامنه</th><th>دامنه فعال</th><th>وضعیت</th><th>روزهای باقی‌مانده</th><th>عملیات</th><th>تاریخچه</th></tr></thead><tbody>";
        foreach ($orders as $order) {
            $is_renewal = $order->get_meta("_is_license_renewal", true);
            if ($is_renewal === "yes") {
            } else {
                foreach ($order->get_items() as $item_id => $item) {
                    $product_name = $item->get_name();
                    $product_id = $item->get_product_id();
                    $domains_count = get_user_domain_count($user_id, $product_id, $item_id);
                    $domains = get_user_domains($user_id, $product_id, $item_id);
                    echo "<tr>";
                    echo "<td><a href=\"" . get_permalink($product_id) . "\">" . esc_html($product_name) . "</a></td>";
                    echo "<td>" . esc_html($domains_count) . "</td>";
                    echo "<td class=\"domains-column\">";
                    foreach ($domains as $domain) {
                        echo "<span class=\"domain-name\">" . esc_html($domain) . "</span><br>";
                    }
                    echo "</td><td>";
                    foreach ($domains as $domain) {
                        $meta_key = "license_status_" . $product_id . "_" . $domain;
                        $status = get_user_meta($user_id, $meta_key, true) === "active" ? "فعال" : "غیرفعال";
                        $button_class = $status === "فعال" ? "uk-button-primary" : "uk-button-danger";
                        echo "<button class=\"toggle-license uk-button uk-button-small " . esc_attr($button_class) . "\" data-user-id=\"" . esc_attr($user_id) . "\" data-product-id=\"" . esc_attr($product_id) . "\" data-order-item-id=\"" . esc_attr($item_id) . "\" data-domain=\"" . esc_attr($domain) . "\">" . esc_html(ucfirst($status)) . "</button><br>";
                    }
                    echo "</td><td>";
                    foreach ($domains as $domain) {
                        $meta_key_expiry = "license_expiry_" . $product_id . "_" . $item_id . "_" . $domain;
                        $expiry_date = get_user_meta($user_id, $meta_key_expiry, true);
                        if ($expiry_date) {
                            $days_remaining = calculate_days_remaining($expiry_date);
                            echo "<input type=\"number\" class=\"edit-days\" data-user-id=\"" . esc_attr($user_id) . "\" data-product-id=\"" . esc_attr($product_id) . "\" data-order-item-id=\"" . esc_attr($item_id) . "\" data-domain=\"" . esc_attr($domain) . "\" value=\"" . $days_remaining . "\" style=\"width: 60px;\"> روز<br>";
                        } else {
                            echo "<span>نامحدود</span><br>";
                        }
                    }
                    echo "</td><td>";
                    foreach ($domains as $domain) {
                        echo "<div class=\"domain-container\">";
                        echo "<button class=\"edit-domain uk-button uk-button-small uk-button-primary\" data-user-id=\"" . esc_attr($user_id) . "\" data-product-id=\"" . esc_attr($product_id) . "\" data-order-item-id=\"" . esc_attr($item_id) . "\" data-domain=\"" . esc_attr($domain) . "\">ویرایش دامنه</button><br>";
                        echo "</div>";
                    }
                    echo "</td><td>";
                    echo "<button class=\"view-history uk-button uk-button-small uk-button-secondary\" data-user-id=\"" . esc_attr($user_id) . "\" data-product-id=\"" . esc_attr($product_id) . "\" data-order-item-id=\"" . esc_attr($item_id) . "\">مشاهده تاریخچه</button>";
                    echo "</td></tr>";
                }
            }
        }
        echo "</tbody></table></div>";
    } else {
        echo "<p>این کاربر هنوز هیچ محصولی خریداری نکرده است.</p>";
    }
    echo "\r\n    <div class=\"uk-modal\" id=\"history-modal\">\r\n        <div class=\"uk-modal-dialog\">\r\n            <button class=\"uk-modal-close-default\" type=\"button\" uk-close></button>\r\n            <div class=\"uk-modal-header\">\r\n                <h2 class=\"uk-modal-title\">تاریخچه دامنه‌ها</h2>\r\n            </div>\r\n            <div class=\"uk-modal-body\" id=\"history-content\"></div>\r\n        </div>\r\n    </div>";
}
function get_domain_history()
{
    if (!isset($_POST["security"]) || !wp_verify_nonce($_POST["security"], "toggle-license-nonce")) {
        wp_send_json_error("Invalid nonce.");
    }
    if (!isset($_POST["user_id"]) || !isset($_POST["product_id"]) || !isset($_POST["order_item_id"])) {
        wp_send_json_error("Missing parameters.");
    }
    $user_id = intval($_POST["user_id"]);
    $product_id = intval($_POST["product_id"]);
    $order_item_id = intval($_POST["order_item_id"]);
    $history_meta_key = "domain_history_" . $product_id . "_" . $order_item_id;
    $domain_history = get_user_meta($user_id, $history_meta_key, true);
    if (!is_array($domain_history) || empty($domain_history)) {
        wp_send_json_error("تاریخچه‌ای برای این دامنه وجود ندارد.");
    }
    $output = "<table class=\"uk-table uk-table-small\">";
    $output .= "<thead><tr><th>دامنه قبلی</th><th>تاریخ تغییر</th><th>جایگزین شده با</th></tr></thead>";
    $output .= "<tbody>";
    foreach ($domain_history as $entry) {
        $output .= "<tr>";
        $output .= "<td>" . esc_html($entry["domain"]) . "</td>";
        $output .= "<td>" . esc_html($entry["updated_at"]) . "</td>";
        $output .= "<td>" . esc_html($entry["replaced_by"]) . "</td>";
        $output .= "</tr>";
    }
    $output .= "</tbody>";
    $output .= "</table>";
    wp_send_json_success($output);
}
function edit_user_domain()
{
    if (!isset($_POST["security"]) || !wp_verify_nonce($_POST["security"], "toggle-license-nonce")) {
        wp_send_json_error("Invalid nonce.");
    }
    if (!isset($_POST["user_id"]) || !isset($_POST["product_id"]) || !isset($_POST["order_item_id"]) || !isset($_POST["old_domain"]) || !isset($_POST["new_domain"])) {
        wp_send_json_error("Missing parameters.");
    }
    $user_id = intval($_POST["user_id"]);
    $product_id = intval($_POST["product_id"]);
    $order_item_id = intval($_POST["order_item_id"]);
    $old_domain = sanitize_text_field($_POST["old_domain"]);
    $new_domain = sanitize_text_field($_POST["new_domain"]);
    $meta_key_old = "license_status_" . $product_id . "_" . $old_domain;
    $meta_key_new = "license_status_" . $product_id . "_" . $new_domain;
    $meta_key_expiry_old = "license_expiry_" . $product_id . "_" . $order_item_id . "_" . $old_domain;
    $meta_key_expiry_new = "license_expiry_" . $product_id . "_" . $order_item_id . "_" . $new_domain;
    $current_status = get_user_meta($user_id, $meta_key_old, true);
    $current_expiry = get_user_meta($user_id, $meta_key_expiry_old, true);
    $history_meta_key = "domain_history_" . $product_id . "_" . $order_item_id;
    $domain_history = get_user_meta($user_id, $history_meta_key, true);
    if (!is_array($domain_history)) {
        $domain_history = [];
    }
    $domain_history[] = ["domain" => $old_domain, "updated_at" => current_time("Y-m-d H:i:s"), "replaced_by" => $new_domain];
    update_user_meta($user_id, $history_meta_key, $domain_history);
    if (empty($current_status)) {
        $current_status = "active";
    }
    update_user_meta($user_id, $meta_key_new, $current_status);
    delete_user_meta($user_id, $meta_key_old);
    if ($current_expiry) {
        update_user_meta($user_id, $meta_key_expiry_new, $current_expiry);
        delete_user_meta($user_id, $meta_key_expiry_old);
    }
    $meta_key = "_user_domain_" . $user_id . "_" . $product_id . "_" . $order_item_id;
    $domains = get_post_meta($product_id, $meta_key, true);
    if (!is_array($domains)) {
        $domains = [];
    }
    if (($key = array_search($old_domain, $domains)) !== false) {
        unset($domains[$key]);
    }
    $domains[] = $new_domain;
    update_post_meta($product_id, $meta_key, $domains);
    $domains = get_post_meta($product_id, $meta_key, true);
    wp_send_json_success(["domains" => $domains, "current_status" => $current_status]);
}
function toggle_license_status()
{
    if (!isset($_POST["security"]) || !wp_verify_nonce($_POST["security"], "toggle-license-nonce")) {
        wp_send_json_error("Invalid nonce.");
    }
    if (!isset($_POST["user_id"]) || !isset($_POST["product_id"]) || !isset($_POST["domain"])) {
        wp_send_json_error("Invalid parameters.");
    }
    if (!is_user_logged_in()) {
        wp_send_json_error("User not logged in.");
    }
    $user_id = intval($_POST["user_id"]);
    $product_id = intval($_POST["product_id"]);
    $domain = sanitize_text_field($_POST["domain"]);
    $meta_key = "license_status_" . $product_id . "_" . $domain;
    $current_status = get_user_meta($user_id, $meta_key, true);
    if (empty($current_status)) {
        $current_status = "active";
    }
    $new_status = $current_status === "active" ? "disabled" : "active";
    update_user_meta($user_id, $meta_key, $new_status);
    wp_send_json_success(["status" => $new_status]);
}
function update_license_days()
{
    if (!isset($_POST["security"]) || !wp_verify_nonce($_POST["security"], "toggle-license-nonce")) {
        wp_send_json_error("Invalid nonce.");
    }
    if (!isset($_POST["user_id"]) || !isset($_POST["product_id"]) || !isset($_POST["order_item_id"]) || !isset($_POST["domain"]) || !isset($_POST["days"])) {
        wp_send_json_error("Missing parameters.");
    }
    $user_id = intval($_POST["user_id"]);
    $product_id = intval($_POST["product_id"]);
    $order_item_id = intval($_POST["order_item_id"]);
    $domain = sanitize_text_field($_POST["domain"]);
    $days = intval($_POST["days"]);
    $meta_key_expiry = "license_expiry_" . $product_id . "_" . $order_item_id . "_" . $domain;
    $new_expiry_date = date("Y-m-d H:i:s", strtotime("+" . $days . " days"));
    update_user_meta($user_id, $meta_key_expiry, $new_expiry_date);
    wp_send_json_success(["message" => "روزهای باقی‌مانده با موفقیت به‌روزرسانی شد."]);
}
function get_products_list()
{
    $paged = isset($_POST["paged"]) ? intval($_POST["paged"]) : 1;
    $search = isset($_POST["search"]) ? sanitize_text_field($_POST["search"]) : "";
    $offset = ($paged - 1) * 10;
    $args = ["post_type" => "product", "posts_per_page" => 10, "paged" => $paged, "s" => $search, "post_status" => "publish"];
    $query = new WP_Query($args);
    $total_products = $query->found_posts;
    $total_pages = $query->max_num_pages;
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            $license_code = get_post_meta(get_the_ID(), "_product_license_code", true);
            if (empty($license_code)) {
                $license_code = generate_unique_code();
                update_post_meta(get_the_ID(), "_product_license_code", $license_code);
            }
            echo "<tr>";
            echo "<td>" . get_the_title() . "</td>";
            echo "<td>" . $license_code . "</td>";
            echo "<td><button class=\"uk-button uk-button-primary generate-license\" data-product-id=\"" . get_the_ID() . "\">تغییر مرچنت کد</button></td>";
            echo "</tr>";
        }
        wp_reset_postdata();
    } else {
        echo "<tr><td colspan=\"3\">اطلاعاتی یافت نشد !!</td></tr>";
    }
    $output = ob_get_clean();
    wp_send_json_success(["html" => $output, "total_pages" => $total_pages]);
}
function generate_unique_code($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}
function get_product_details()
{
    if (!isset($_POST["product_id"]) || !is_numeric($_POST["product_id"])) {
        wp_send_json_error("Invalid product ID.");
    }
    $product_id = intval($_POST["product_id"]);
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error("Product not found.");
    }
    $unique_code = generate_unique_code();
    update_post_meta($product_id, "_product_license_code", $unique_code);
    wp_send_json_success($unique_code);
}
function display_license_admin_page()
{
    require_once plugin_dir_path(__FILE__) . "template/navbar.php";
    require_once plugin_dir_path(__FILE__) . "template/general.php";
    require_once plugin_dir_path(__FILE__) . "template/product.php";
    require_once plugin_dir_path(__FILE__) . "template/setting.php";
    require_once plugin_dir_path(__FILE__) . "template/label.php";
    require_once plugin_dir_path(__FILE__) . "template/help.php";
    require_once plugin_dir_path(__FILE__) . "template/encode.php";
    require_once plugin_dir_path(__FILE__) . "template/info.php";
    require_once plugin_dir_path(__FILE__) . "template/button.php";
}
function enqueue_admin_license_scripts()
{
    $current_screen = get_current_screen();
    if ($current_screen->id == "toplevel_page_user_licenses") {
        wp_enqueue_style("uikit-rtl", plugin_dir_url(__FILE__) . "assets/css/uikit-rtl.min.css");
        wp_enqueue_style("admin-styles", plugin_dir_url(__FILE__) . "assets/css/admin-styles.css");
        wp_enqueue_style("styles-rtl", plugin_dir_url(__FILE__) . "assets/css/admin-styles-rtl.css");
        wp_enqueue_style("colors-min", plugin_dir_url(__FILE__) . "assets/css/colors.min.css");
        wp_enqueue_script("jquery-ui-tabs");
        wp_enqueue_script("my-custom-scripts", plugin_dir_url(__FILE__) . "assets/js/custom-admin.js", ["jquery"], NULL, true);
        wp_enqueue_script("admin-scripts", plugin_dir_url(__FILE__) . "assets/js/admin-scripts.js", ["jquery"], NULL, true);
        wp_enqueue_script("uikit-icons", plugin_dir_url(__FILE__) . "assets/js/uikit-icons.min.js", ["jquery"], NULL, true);
        wp_enqueue_script("uikit-min", plugin_dir_url(__FILE__) . "assets/js/uikit.min.js", ["jquery"], NULL, true);
        wp_enqueue_script("setting", plugin_dir_url(__FILE__) . "assets/js/setting.js", ["jquery"], NULL, true);
        wp_enqueue_script("colors-min", plugin_dir_url(__FILE__) . "assets/js/colors.min.js", ["jquery"], NULL, true);
        wp_enqueue_script("license-script", plugin_dir_url(__FILE__) . "assets/js/admin-license-script.js", ["jquery"], NULL, true);
        wp_enqueue_script("u-admin", plugin_dir_url(__FILE__) . "assets/js/u-admin.js", ["jquery"], NULL, true);
        wp_enqueue_script("u-admin", plugin_dir_url(__FILE__) . "assets/js/code.js", ["jquery"], NULL, true);
        wp_localize_script("license-script", "admin_license_ajax", ["ajax_url" => admin_url("admin-ajax.php"), "nonce" => wp_create_nonce("toggle-license-nonce")]);
        wp_enqueue_script("php-code-script", plugins_url("/assets/js/php-code.js", __FILE__), ["jquery"], "1.0", true);
        wp_localize_script("php-code-script", "phpCodeEncoder", ["ajax_url" => plugins_url("/encoder.php", __FILE__)]);
    }
}

?>