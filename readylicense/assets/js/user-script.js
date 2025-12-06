jQuery(document).ready(function ($) {
    // تابع برای نمایش اعلان بوت‌استرپ
    function showAlert(type, message) {
        var alertDiv = $('#license-alert');
        alertDiv.removeClass('alert-success alert-danger').addClass('alert-' + type);
        alertDiv.text(message);
        alertDiv.show();
        setTimeout(function () {
            alertDiv.fadeOut();
        }, 5000); // مخفی شدن بعد از 5 ثانیه
    }

    // اعتبارسنجی دامنه در سمت کلاینت
    function isDomainValid(domain) {
        return /\.[a-zA-Z]{2,}$/.test(domain) && domain.length >= 5 && domain.length <= 255;
    }

    // متغیر برای ذخیره دامنه‌های فعلی
    let currentDomains = [];

    // مدیریت دکمه ویرایش دامنه
    $('.license-button').on('click', function () {
        var productId = $(this).data('product_id');
        var orderItemId = $(this).data('order_item_id');

        // پر کردن مقادیر در مودال
        $('#product-id').val(productId);
        $('#order-item-id').val(orderItemId);

        // بررسی تعداد دامنه و وضعیت غیرفعال
        $.ajax({
            url: license_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'check_domain_count',
                product_id: productId,
                order_item_id: orderItemId
            },
            success: function (response) {
                if (response.success) {
                    var domainCount = response.data.domain_count;
                    var isDisabled = response.data.is_disabled;
                    var maxDomains = license_ajax.opt_number_license;
                    currentDomains = response.data.current_domains || []; // ذخیره دامنه‌های فعلی

                    if (isDisabled) {
                        showAlert('danger', 'دامنه‌های شما غیرفعال است و قابل ویرایش نیست.');
                        $('#disabled-warning').show();
                        $('#license-form button[type="submit"]').prop('disabled', true);
                    } else if (domainCount >= maxDomains) {
                        showAlert('danger', 'شما به حداکثر تعداد مجاز تغییر دامنه رسیده‌اید.');
                        $('#domain-limit-warning').show();
                        $('#license-form button[type="submit"]').prop('disabled', true);
                    } else {
                        $('#disabled-warning').hide();
                        $('#domain-limit-warning').hide();
                        $('#license-form button[type="submit"]').prop('disabled', false);
                        $('#license-modal').modal('show');
                    }
                } else {
                    showAlert('danger', response.data || 'خطا در بررسی وضعیت دامنه.');
                }
            },
            error: function () {
                showAlert('danger', 'خطایی در بررسی تعداد دامنه رخ داد.');
            }
        });
    });

    // ارسال فرم ثبت دامنه
    $('#license-form').on('submit', function (e) {
        e.preventDefault();
        var domain = $('#domain-name').val().trim();
        var productId = $('#product-id').val();
        var orderItemId = $('#order-item-id').val();

        // اعتبارسنجی دامنه در سمت کلاینت
        if (!isDomainValid(domain)) {
            showAlert('danger', 'دامنه نامعتبر است. لطفاً دامنه‌ای با پسوند معتبر (مثل .com) وارد کنید.');
            return;
        }

        // بررسی یکسان بودن دامنه با دامنه فعلی
        if (currentDomains.length > 0 && currentDomains.includes(domain)) {
            showAlert('warning', 'دامنه واردشده با دامنه فعلی یکسان است. لطفاً دامنه جدیدی وارد کنید.');
            return;
        }

        $.ajax({
            url: license_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_license',
                domain: domain,
                product_id: productId,
                order_item_id: orderItemId
            },
            beforeSend: function () {
                $('#license-form button[type="submit"]').prop('disabled', true).text('در حال ذخیره...');
            },
            success: function (response) {
                if (response.success) {
                    showAlert('success', response.data.message);
                    $('#license-modal').modal('hide');
                    setTimeout(function () {
                        location.reload(); // رفرش صفحه برای به‌روزرسانی لیست
                    }, 1000); // تأخیر 1 ثانیه برای نمایش پیام موفقیت
                } else {
                    showAlert('danger', response.data || 'خطا در ثبت دامنه.');
                }
            },
            error: function () {
                showAlert('danger', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
            },
            complete: function () {
                $('#license-form button[type="submit"]').prop('disabled', false).text('ذخیره');
            }
        });
    });

    // مدیریت دکمه تمدید
    $('.renew-license').on('click', function () {
        var productId = $(this).data('product_id');
        var orderItemId = $(this).data('order_item_id');
        var domain = $(this).data('domain');
        var price = $(this).data('price');
        var $button = $(this);

        if (!productId || !orderItemId || !domain || !price) {
            showAlert('danger', 'یکی از مقادیر مورد نیاز وجود ندارد!');
            return;
        }

        $.ajax({
            url: license_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'renew_license',
                product_id: productId,
                order_item_id: orderItemId,
                domain: domain,
                price: price
            },
            beforeSend: function () {
                $button.prop('disabled', true).text('در حال پردازش...');
            },
            success: function (response) {
                if (response.success) {
                    showAlert('success', response.data.message);
                    setTimeout(function () {
                        window.location.href = response.data.checkout_url;
                    }, 1000); // تأخیر 1 ثانیه برای نمایش پیام موفقیت
                } else {
                    showAlert('danger', response.data || 'خطا در تمدید لایسنس.');
                }
            },
            error: function () {
                showAlert('danger', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
            },
            complete: function () {
                $button.prop('disabled', false).text('تمدید');
            }
        });
    });

    // بستن مودال و ریست فرم
    $('#license-modal').on('hidden.bs.modal', function () {
        $('#license-form')[0].reset();
        $('#disabled-warning').hide();
        $('#domain-limit-warning').hide();
        $('#license-form button[type="submit"]').prop('disabled', false).text('ذخیره');
    });
});