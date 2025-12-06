<?php

if (!defined("ABSPATH")) {
    exit;
}
add_filter("woocommerce_account_menu_items", "add_license_tab_in_middle", 10, 1);
add_action("init", "add_license_endpoint");
add_filter("woocommerce_product_data_tabs", "add_license_product_tab", 99);
add_action("woocommerce_product_data_panels", "add_license_product_tab_content");
add_action("woocommerce_process_product_meta", "save_exclude_from_license_field");
add_action("woocommerce_account_licenses_endpoint", "licenses_content");
add_action("wp", "check_and_disable_expired_licenses");
add_shortcode("show_licenses", "licenses_content_shortcode");
add_action("wp_ajax_save_license", "save_license");
add_action("wp_ajax_nopriv_save_license", "save_license");
add_action("wp_ajax_check_domain_count", "check_domain_count");
add_action("wp_ajax_renew_license", "renew_license");
add_action("woocommerce_order_status_completed", "process_license_renewal");
add_filter("woocommerce_admin_order_items", "add_custom_renewal_item_in_admin", 10, 2);
add_action("wp_enqueue_scripts", "enqueue_license_scripts_shortcode");
add_action("wp_enqueue_scripts", "enqueue_license_scripts_account_page");


include_once plugin_dir_path(__FILE__) . "panel.php";

function add_license_tab_in_middle($items)
{
    $license_label = get_option("label_title_menu") ?: "لایسنس‌های من";
    $new_items = [];
    $middle_index = ceil(count($items) / 2);
    $i = 0;
    foreach ($items as $key => $item) {
        $new_items[$key] = $item;
        if (++$i == $middle_index) {
            $new_items["licenses"] = $license_label;
        }
    }
    return $new_items;
}
function add_license_endpoint()
{
    add_rewrite_endpoint("licenses", EP_ROOT | EP_PAGES);
}
function add_license_product_tab($tabs)
{
    $tabs["license"] = ["label" => "لایسنس", "target" => "license_product_data", "class" => ["show_if_simple", "show_if_variable"], "priority" => 70];
    return $tabs;
}
function add_license_product_tab_content()
{
    echo "    <div id=\"license_product_data\" class=\"panel woocommerce_options_panel\">\r\n        <div class=\"options_group\">\r\n            ";
    woocommerce_wp_checkbox(["id" => "_exclude_from_license", "label" => "عدم نمایش در پنل کاربری", "description" => "با فعال کردن این گزینه، این محصول در جدول لایسنس‌های کاربر نمایش داده نمی‌شود.", "desc_tip" => true]);
    woocommerce_wp_text_input(["id" => "_license_duration", "label" => "مدت زمان لایسنس (روز)", "description" => "تعداد روزهای اعتبار لایسنس را وارد کنید (خالی بگذارید برای نامحدود).", "desc_tip" => true, "type" => "number", "custom_attributes" => ["step" => "1"], "value" => get_post_meta(get_the_ID(), "_license_duration", true)]);
    woocommerce_wp_text_input(["id" => "_license_renewal_price", "label" => "قیمت تمدید لایسنس (تومان)", "description" => "قیمت تمدید لایسنس را وارد کنید.", "desc_tip" => true, "type" => "number", "custom_attributes" => ["min" => 0]]);
    echo "        </div>\r\n    </div>\r\n    ";
}
function save_exclude_from_license_field($product_id)
{
    $exclude_from_license = isset($_POST["_exclude_from_license"]) ? "yes" : "no";
    update_post_meta($product_id, "_exclude_from_license", $exclude_from_license);
    $license_duration = isset($_POST["_license_duration"]) && $_POST["_license_duration"] !== "" ? intval($_POST["_license_duration"]) : "";
    update_post_meta($product_id, "_license_duration", $license_duration);
    $renewal_price = isset($_POST["_license_renewal_price"]) ? floatval($_POST["_license_renewal_price"]) : "";
    update_post_meta($product_id, "_license_renewal_price", $renewal_price);
}
function get_user_domains($user_id, $product_id, $order_item_id)
{
    $meta_key = "_user_domain_" . $user_id . "_" . $product_id . "_" . $order_item_id;
    $domains = get_post_meta($product_id, $meta_key, true);
    return is_array($domains) ? $domains : [];
}
function get_user_domain_count($user_id, $product_id, $order_item_id)
{
    $meta_key_count = "_user_domain_count_" . $user_id . "_" . $product_id . "_" . $order_item_id;
    $count = get_post_meta($product_id, $meta_key_count, true);
    return intval($count);
}
function licenses_content()
{
    $user_id = get_current_user_id();
    $orders = wc_get_orders(["customer_id" => $user_id, "status" => "completed"]);
    $label_title_page = get_option("label_title_page") ?: "لیست لایسنس‌ها";
    $label_title_button = get_option("label_title_button") ?: "ویرایش دامنه";
    $label_title_product = get_option("label_title_product") ?: "نام محصول";
    $label_count_domain = get_option("label_count_domain") ?: "تعداد تغییر دامنه";
    $label_name_domain = get_option("label_name_domain") ?: "نام دامنه";
    $label_status_domain = get_option("label_status_domain") ?: "وضعیت";
    $label_edit_domain = get_option("label_edit_domain") ?: "عملیات";
    $label_days_remaining = get_option("alert_count_day") ?: "روز های باقی مانده";
    $label_renew = get_option("alert_renewal") ?: "تمدید";
    $alert_renewal = get_option("btn_renewal") ?: "پرداخت";
    $title_license_help = get_option("title_license_help") ?: "ثبت دامنه جدید";
    $title_license_no_view = get_option("title_license_no_view") ?: "در حال حاضر اطلاعاتی برای نمایش به شما وجود ندارد.";
    $No_information = get_option("No_information") ?: "هنوز اطلاعاتی ثبت نشده است.";
    $active = get_option("active") ?: "فعال";
    $de_active = get_option("de_active") ?: "غیرفعال";
    $save = get_option("save") ?: "ذخیره";
    $order_created_successfully = get_option("order_created_successfully") ?: "سفارش تمدید با موفقیت ایجاد شد. لطفاً پرداخت را تکمیل کنید.";
    $opt_comment_license = get_option("opt_comment_license");
    $opt_comment = str_replace(":", "\n", $opt_comment_license);
    echo "<div id=\"license-alert\" class=\"alert\" style=\"display: none;\"></div>";
    echo "<h3>" . $label_title_page . "</h3>";
    if (!empty($orders)) {
        echo "<div class=\"table-responsive list-license\"><table class=\"table table-striped\" id=\"licenses-table\">";
        echo "<thead><tr><th>" . $label_title_product . "</th><th>" . $label_count_domain . "</th><th>" . $label_name_domain . "</th><th>" . $label_status_domain . "</th><th>" . $label_days_remaining . "</th><th>" . $label_edit_domain . "</th><th>" . $label_renew . "</th></tr></thead>";
        echo "<tbody>";
        $has_items = false;
        foreach ($orders as $order) {
            $is_renewal = $order->get_meta("_is_license_renewal", true);
            if ($is_renewal === "yes") {
            } else {
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = $item->get_product_id();
                    $exclude_from_license = get_post_meta($product_id, "_exclude_from_license", true);
                    if ($exclude_from_license === "yes") {
                    } else {
                        $has_items = true;
                        $product_name = $item->get_name();
                        $domains_count = get_user_domain_count($user_id, $product_id, $item_id);
                        $domains = get_user_domains($user_id, $product_id, $item_id);
                        echo "<tr>";
                        echo "<td><a href=\"" . get_permalink($product_id) . "\">" . $product_name . "</a></td>";
                        echo "<td>" . $domains_count . "</td>";
                        echo "<td>";
                        if (is_array($domains)) {
                            foreach ($domains as $domain) {
                                echo "<span>" . $domain . "</span><br>";
                            }
                        } else {
                            echo $No_information;
                        }
                        echo "</td><td>";
                        if (is_array($domains)) {
                            foreach ($domains as $domain) {
                                $meta_key_status = "license_status_" . $product_id . "_" . $domain;
                                $current_status = get_user_meta($user_id, $meta_key_status, true);
                                $translated_status = $current_status === "disabled" ? $de_active : $active;
                                echo "<span class=\"status-doamin\">" . $translated_status . "</span><br>";
                            }
                        } else {
                            echo "-";
                        }
                        echo "</td><td>";
                        if (is_array($domains)) {
                            foreach ($domains as $domain) {
                                $meta_key_expiry = "license_expiry_" . $product_id . "_" . $item_id . "_" . $domain;
                                $expiry_date = get_user_meta($user_id, $meta_key_expiry, true);
                                if ($expiry_date) {
                                    $days_remaining = calculate_days_remaining($expiry_date);
                                    echo "<span>" . $days_remaining . " روز</span><br>";
                                } else {
                                    echo "<span>نامحدود</span><br>";
                                }
                            }
                        } else {
                            echo "-";
                        }
                        echo "</td>";
                        echo "<td><button class=\"btn btn-primary license-button\" data-product_id=\"" . $product_id . "\" data-order_item_id=\"" . $item_id . "\">" . $label_title_button . "</button></td>";
                        echo "<td>";
                        if (is_array($domains)) {
                            foreach ($domains as $domain) {
                                $renewal_price = get_post_meta($product_id, "_license_renewal_price", true);
                                $license_duration = get_post_meta($product_id, "_license_duration", true);
                                if ($renewal_price && 0 < $renewal_price && $license_duration !== "") {
                                    echo "<button class=\"btn btn-success renew-license\" data-product_id=\"" . $product_id . "\" data-order_item_id=\"" . $item_id . "\" data-domain=\"" . $domain . "\" data-price=\"" . $renewal_price . "\">" . $alert_renewal . "</button><br>";
                                } else {
                                    echo "-";
                                }
                            }
                        } else {
                            echo "-";
                        }
                        echo "</td></tr>";
                    }
                }
            }
        }
        echo "</tbody></table><div id=\"licenses-pagination\" class=\"pagination\"></div></div>";
        if (!$has_items) {
            echo "<p class=\"alert alert-info\">" . $title_license_no_view . "</p>";
        }
    } else {
        echo "<p class=\"alert alert-info\">" . $title_license_no_view . "</p>";
    }
    echo "\r\n    <div class=\"modal fade\" id=\"license-modal\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"licenseModalLabel\" aria-hidden=\"true\">\r\n        <div class=\"modal-dialog modal-dialog-centered\" role=\"document\">\r\n            <div class=\"modal-content\">\r\n                <div class=\"modal-header\">\r\n                    <h5 class=\"modal-title title-domain\" id=\"licenseModalLabel\">ثبت دامنه جدید</h5>\r\n                    <button type=\"button\" class=\"close control-close\" data-dismiss=\"modal\" aria-label=\"Close\">\r\n                        <span aria-hidden=\"true\">×</span>\r\n                    </button>\r\n                </div>\r\n                <div class=\"modal-body p-3\">\r\n                    <form id=\"license-form\">\r\n                        <div class=\"form-group\">\r\n                            <label class=\"title-domain\" for=\"domain-name\">" . $title_license_help . "</label>\r\n                            <div>" . nl2br($opt_comment) . "</div>\r\n                            <input type=\"text\" class=\"form-control text-center mt-3\" id=\"domain-name\" name=\"domain\" placeholder=\"domain.com\" required />\r\n                        </div>\r\n                        <input type=\"hidden\" id=\"product-id\" name=\"product_id\" />\r\n                        <input type=\"hidden\" id=\"order-item-id\" name=\"order_item_id\" />\r\n                        <button type=\"submit\" class=\"btn btn-primary\">" . $save . "</button>\r\n                    </form>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </div>";
}
function calculate_days_remaining($expiry_date)
{
    $current_date = current_time("timestamp");
    $expiry_timestamp = strtotime($expiry_date);
    $days_remaining = floor(($expiry_timestamp - $current_date) / 86400);
    return 0 < $days_remaining ? $days_remaining : 0;
}
function check_and_disable_expired_licenses()
{
    $users = get_users();
    foreach ($users as $user) {
        $user_id = $user->ID;
        $orders = wc_get_orders(["customer_id" => $user_id, "status" => "completed"]);
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                $domains = get_user_domains($user_id, $product_id, $item_id);
                if (is_array($domains)) {
                    foreach ($domains as $domain) {
                        $meta_key_expiry = "license_expiry_" . $product_id . "_" . $item_id . "_" . $domain;
                        $meta_key_status = "license_status_" . $product_id . "_" . $domain;
                        $expiry_date = get_user_meta($user_id, $meta_key_expiry, true);
                        if ($expiry_date && calculate_days_remaining($expiry_date) <= 0) {
                            update_user_meta($user_id, $meta_key_status, "disabled");
                        }
                    }
                }
            }
        }
    }
}
function licenses_content_shortcode()
{
    ob_start();
    licenses_content();
    return ob_get_clean();
}
function save_domain_with_prefix_suffix($domain, $product_id, $user_id, $order_item_id)
{
    $domain = sanitize_text_field($domain);
    if (!is_domain_valid($domain)) {
        return new WP_Error("invalid_domain", "دامنه نامعتبر وارد شده است.");
    }
    $current_count = get_user_domain_count($user_id, $product_id, $order_item_id);
    $number_domain = get_option("opt_number_license") ?: 3;
    if ($number_domain <= $current_count) {
        return new WP_Error("domain_limit", "شما مجاز به ثبت دامنه بیش از حد مجاز نیستید...");
    }
    function get_clean_domain($domain)
    {
        if (strpos($domain, "http://") === false && strpos($domain, "https://") === false) {
            $domain = "http://" . $domain;
        }
        $parsed_url = wp_parse_url($domain);
        $host = $parsed_url["host"] ?? "";
        if (strpos($host, "www.") === 0) {
            $host = substr($host, 4);
        }
        return $host;
    }
    $domain_name = get_clean_domain($domain);
    $current_domains = get_user_domains($user_id, $product_id, $order_item_id);
    if (is_array($current_domains)) {
        foreach ($current_domains as $current_domain) {
            $meta_key_status = "license_status_" . $product_id . "_" . $current_domain;
            $current_status = get_user_meta($user_id, $meta_key_status, true);
            if ($current_status === "disabled") {
                return new WP_Error("disabled_domain", "دامنه‌های شما غیرفعال است و قابل ویرایش نیست.");
            }
        }
    }
    $meta_key = "_user_domain_" . $user_id . "_" . $product_id . "_" . $order_item_id;
    $old_domains = get_post_meta($product_id, $meta_key, true);
    $history_meta_key = "domain_history_" . $product_id . "_" . $order_item_id;
    $domain_history = get_user_meta($user_id, $history_meta_key, true);
    if (!is_array($domain_history)) {
        $domain_history = [];
    }
    if (is_array($old_domains) && !empty($old_domains)) {
        foreach ($old_domains as $old_domain) {
            $domain_history[] = ["domain" => $old_domain, "updated_at" => current_time("Y-m-d H:i:s"), "replaced_by" => $domain_name, "updated_by" => "user"];
        }
    }
    update_user_meta($user_id, $history_meta_key, $domain_history);
    $old_expiry_date = NULL;
    $old_status = NULL;
    if (is_array($old_domains) && !empty($old_domains)) {
        foreach ($old_domains as $old_domain) {
            $meta_key_expiry_old = "license_expiry_" . $product_id . "_" . $order_item_id . "_" . $old_domain;
            $meta_key_status_old = "license_status_" . $product_id . "_" . $old_domain;
            $old_expiry_date = get_user_meta($user_id, $meta_key_expiry_old, true);
            $old_status = get_user_meta($user_id, $meta_key_status_old, true);
            delete_user_meta($user_id, $meta_key_expiry_old);
            delete_user_meta($user_id, $meta_key_status_old);
        }
    }
    $new_domains = [$domain_name];
    update_post_meta($product_id, $meta_key, $new_domains);
    $meta_key_count = "_user_domain_count_" . $user_id . "_" . $product_id . "_" . $order_item_id;
    $new_count = $current_count + 1;
    update_post_meta($product_id, $meta_key_count, $new_count);
    $meta_key_status = "license_status_" . $product_id . "_" . $domain_name;
    $current_status = $old_status ?: "active";
    update_user_meta($user_id, $meta_key_status, $current_status);
    $meta_key_expiry = "license_expiry_" . $product_id . "_" . $order_item_id . "_" . $domain_name;
    if ($old_expiry_date) {
        update_user_meta($user_id, $meta_key_expiry, $old_expiry_date);
    } else {
        $license_duration = get_post_meta($product_id, "_license_duration", true);
        if ($license_duration !== "") {
            $expiry_date = date("Y-m-d H:i:s", strtotime("+" . $license_duration . " days"));
            update_user_meta($user_id, $meta_key_expiry, $expiry_date);
        }
    }
    return true;
}
function is_domain_valid($domain)
{
    if (strlen($domain) < 5 || 255 < strlen($domain)) {
        return false;
    }
    if (!preg_match("/\\.[a-zA-Z]{2,}\$/", $domain)) {
        return false;
    }
    return true;
}
function save_license()
{
    if (isset($_POST["domain"]) && isset($_POST["product_id"]) && isset($_POST["order_item_id"])) {
        $domain = sanitize_text_field($_POST["domain"]);
        $product_id = intval($_POST["product_id"]);
        $order_item_id = intval($_POST["order_item_id"]);
        $user_id = get_current_user_id();
        $result = save_domain_with_prefix_suffix($domain, $product_id, $user_id, $order_item_id);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        $domains = get_user_domains($user_id, $product_id, $order_item_id);
        $domain_count = get_user_domain_count($user_id, $product_id, $order_item_id);
        wp_send_json_success(["message" => "دامنه با موفقیت ثبت گردید.", "domains" => $domains, "domain_count" => $domain_count]);
    } else {
        wp_send_json_error("داده‌های نامعتبر.");
    }
    wp_die();
}
function check_domain_count()
{
    if (isset($_POST["product_id"]) && isset($_POST["order_item_id"])) {
        $product_id = intval($_POST["product_id"]);
        $order_item_id = intval($_POST["order_item_id"]);
        $user_id = get_current_user_id();
        $domain_count = get_user_domain_count($user_id, $product_id, $order_item_id);
        $domains = get_user_domains($user_id, $product_id, $order_item_id);
        $is_disabled = false;
        if (is_array($domains)) {
            foreach ($domains as $domain) {
                $meta_key_status = "license_status_" . $product_id . "_" . $domain;
                $current_status = get_user_meta($user_id, $meta_key_status, true);
                if ($current_status === "disabled") {
                    $is_disabled = true;
                }
            }
        }
        wp_send_json_success(["domain_count" => $domain_count, "is_disabled" => $is_disabled, "current_domains" => $domains]);
    } else {
        wp_send_json_error("داده‌های نامعتبر.");
    }
    wp_die();
}
function renew_license()
{
    if (!isset($_POST["product_id"]) || !isset($_POST["order_item_id"]) || !isset($_POST["domain"]) || !isset($_POST["price"])) {
        wp_send_json_error("پارامترهای ضروری ارسال نشده‌اند.");
    }
    $product_id = intval($_POST["product_id"]);
    $order_item_id = intval($_POST["order_item_id"]);
    $domain = sanitize_text_field($_POST["domain"]);
    $price = floatval($_POST["price"]);
    $user_id = get_current_user_id();
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error("محصول مورد نظر یافت نشد.");
    }
    $renewal_price = get_post_meta($product_id, "_license_renewal_price", true);
    if (!$renewal_price || $renewal_price <= 0 || $price != $renewal_price) {
        wp_send_json_error("قیمت تمدید نامعتبر است.");
    }
    $license_duration = get_post_meta($product_id, "_license_duration", true);
    if ($license_duration === "") {
        wp_send_json_error("مدت زمان لایسنس برای این محصول تنظیم نشده است.");
    }
    $order = wc_create_order(["customer_id" => $user_id, "status" => "pending", "created_via" => "license_renewal"]);
    if (is_wp_error($order)) {
        wp_send_json_error("خطا در ایجاد سفارش تمدید.");
    }
    $product_name = $product->get_name();
    $renewal_display_name = sprintf("تمدید لایسنس %s (دامنه: %s) - مدت: %s روز", $product_name, $domain, $license_duration);
    $order->update_meta_data("_is_license_renewal", "yes");
    $order->update_meta_data("_renewal_product_id", $product_id);
    $order->update_meta_data("_renewal_order_item_id", $order_item_id);
    $order->update_meta_data("_renewal_domain", $domain);
    $order->update_meta_data("_renewal_duration", $license_duration);
    $order->update_meta_data("_renewal_display_name", $renewal_display_name);
    $order->update_meta_data("_renewal_price", $price);
    $order->set_customer_note("سفارش تمدید لایسنس-" . $renewal_display_name);
    $order->set_total($price);
    $order->save();
    $item_id = wc_add_order_item($order->get_id(), ["order_item_name" => $renewal_display_name, "order_item_type" => "line_item"]);
    wc_add_order_item_meta($item_id, "_qty", 1);
    wc_add_order_item_meta($item_id, "_line_total", $price);
    wc_add_order_item_meta($item_id, "_line_subtotal", $price);
    $checkout_url = $order->get_checkout_payment_url();
    $order_created_successfully = get_option("order_created_successfully") ?: "سفارش تمدید با موفقیت ایجاد شد. لطفاً پرداخت را تکمیل کنید.";
    wp_send_json_success(["message" => $order_created_successfully, "checkout_url" => $checkout_url, "order_id" => $order->get_id()]);
    wp_die();
}
function process_license_renewal($order_id)
{
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    $is_renewal = $order->get_meta("_is_license_renewal", true);
    if ($is_renewal) {
        $product_id = $order->get_meta("_renewal_product_id", true);
        $order_item_id = $order->get_meta("_renewal_order_item_id", true);
        $domain = $order->get_meta("_renewal_domain", true);
        $license_duration = get_post_meta($product_id, "_license_duration", true);
        if ($license_duration !== "") {
            $meta_key_expiry = "license_expiry_" . $product_id . "_" . $order_item_id . "_" . $domain;
            $current_expiry = get_user_meta($user_id, $meta_key_expiry, true);
            $base_date = $current_expiry ? $current_expiry : current_time("Y-m-d H:i:s");
            $new_expiry_date = date("Y-m-d H:i:s", strtotime($base_date . " +" . $license_duration . " days"));
            update_user_meta($user_id, $meta_key_expiry, $new_expiry_date);
        }
        $meta_key_status = "license_status_" . $product_id . "_" . $domain;
        update_user_meta($user_id, $meta_key_status, "active");
    }
}
function add_custom_renewal_item_in_admin($items, $order)
{
    if ($order->get_meta("_is_license_renewal", true) && empty($items)) {
        $renewal_item_name = $order->get_meta("_renewal_display_name", true) ?: "تمدید لایسنس محصول نامشخص";
        $product_id = $order->get_meta("_renewal_product_id", true);
        $domain = $order->get_meta("_renewal_domain", true);
        $item_id = wc_add_order_item($order->get_id(), ["order_item_name" => $renewal_item_name, "order_item_type" => "line_item"]);
        if ($item_id) {
            wc_add_order_item_meta($item_id, "_qty", 1);
            wc_add_order_item_meta($item_id, "_line_subtotal", $order->get_total());
            wc_add_order_item_meta($item_id, "_line_total", $order->get_total());
            wc_add_order_item_meta($item_id, "_product_id", $product_id);
            wc_add_order_item_meta($item_id, "_domain", $domain);
            wc_add_order_item_meta($item_id, "_is_renewal", "yes");
        }
        return $order->get_items();
    }
    return $items;
}
function enqueue_license_scripts()
{
    wp_enqueue_style("bootstrap-css", plugin_dir_url(__FILE__) . "assets/css/bootstrap.min.css");
    wp_enqueue_style("style", plugin_dir_url(__FILE__) . "assets/css/style.css");
    wp_enqueue_script("bootstrap-js", plugin_dir_url(__FILE__) . "assets/js/bootstrap.min.js", ["jquery"], "1.1", true);
    wp_enqueue_script("license-script", plugin_dir_url(__FILE__) . "assets/js/user-script.js", ["jquery", "bootstrap-js"], "1.1", true);
    $opt_number_license = get_option("opt_number_license") ?: 3;
    wp_localize_script("license-script", "license_ajax", ["ajax_url" => admin_url("admin-ajax.php"), "opt_number_license" => $opt_number_license]);
}
function enqueue_license_scripts_shortcode()
{
    if (has_shortcode(get_post()->post_content, "show_licenses")) {
        enqueue_license_scripts();
    }
}
function enqueue_license_scripts_account_page()
{
    if (is_account_page()) {
        enqueue_license_scripts();
    }
}

?>