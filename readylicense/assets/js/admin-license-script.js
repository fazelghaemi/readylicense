jQuery(document).ready(function ($) {
    $(document).on('click', '.toggle-license', function (e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        var productId = $(this).data('product-id');
        var orderItemId = $(this).data('order-item-id');
        var domain = $(this).data('domain');
        var button = $(this);

        $.ajax({
            url: admin_license_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'toggle_license_status',
                user_id: userId,
                product_id: productId,
                order_item_id: orderItemId,
                domain: domain,
                security: admin_license_ajax.nonce
            },
            beforeSend: function () {
                button.prop('disabled', true);
            },
            success: function (response) {
                if (response.success) {
                    var newStatus = response.data.status;
                    var label = (newStatus === 'active') ? 'فعال' : 'غیرفعال';
                    var buttonClass = (newStatus === 'active') ? 'uk-button-primary' : 'uk-button-danger';
                    button.removeClass('uk-button-primary uk-button-danger فعال غیرفعال').addClass(buttonClass).html(label);
                    alertUIkit('success', 'وضعیت با موفقیت ویرایش گردید.');
                } else {
                    alertUIkit('danger', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
                }
            },
            error: function () {
                alertUIkit('danger', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
            },
            complete: function () {
                button.prop('disabled', false);
            }
        });
    });

    $(document).on('change', '.edit-days', function () {
        var userId = $(this).data('user-id');
        var productId = $(this).data('product-id');
        var orderItemId = $(this).data('order-item-id');
        var domain = $(this).data('domain');
        var days = $(this).val();

        $.ajax({
            url: admin_license_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'update_license_days',
                user_id: userId,
                product_id: productId,
                order_item_id: orderItemId,
                domain: domain,
                days: days,
                security: admin_license_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    alertUIkit('success', response.data.message);
                } else {
                    alertUIkit('danger', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
                }
            },
            error: function () {
                alertUIkit('danger', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
            }
        });
    });

    function alertUIkit(type, message) {
        UIkit.notification({
            message: message,
            status: type,
            pos: 'top-center',
            timeout: 3000
        });
    }
});