<?php

add_action("wp_ajax_save_custom_settings_license", "save_custom_settings_license");
function save_custom_settings_license()
{
    if (!current_user_can("manage_options")) {
        wp_send_json_error("شما دسترسی لازم برای انجام این عملیات را ندارید.");
    } else if (!isset($_POST["license_settings_nonce"]) || !wp_verify_nonce($_POST["license_settings_nonce"], "license_settings_nonce")) {
        wp_send_json_error("Nonce verification failed.");
    } else {
        $fields = ["opt_number_license", "opt_comment_license", "label_title_menu", "label_title_page", "alert_count_domain", "alert_ban_domain", "label_title_button", "label_title_product", "label_count_domain", "label_name_domain", "label_status_domain", "label_edit_domain", "title_license_help", "alert_count_day", "alert_renewal", "btn_renewal", "title_license_no_view", "No_information", "save", "active", "de_active", "order_created_successfully"];
        try {
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $new_value = sanitize_text_field($_POST[$field]);
                    $current_value = get_option($field, "");
                    if ($new_value !== $current_value) {
                        update_option($field, $new_value);
                    }
                }
            }
            wp_send_json_success("تنظیمات ذخیره گردید.");
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            wp_send_json_error("خطایی رخ داد.");
        }
    }
}

?>