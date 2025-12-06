jQuery(document).ready(function($) {
    $('#general-settings-form').on('submit', function(e) {
        e.preventDefault();

        var $saveButton = $('#save-button');
        $saveButton.prop('disabled', true);

        var data = $(this).serializeArray();
        data.push({ name: 'action', value: 'save_custom_settings_license' });
        data.push({ name: 'license_settings_nonce', value: $('input[name="license_settings_nonce"]').val() });

        $('#general-settings-form input[type=checkbox]').each(function() {
            if (!$(this).is(':checked')) {
                data.push({ name: $(this).attr('name'), value: '0' });
            }
        });

        $.post(admin_license_ajax.ajax_url, $.param(data), function(response) {
            var notificationOptions = {
                status: response.success ? 'success' : 'danger',
                timeout: 4000,
                pos: 'top-center',
                message: response.success ? 'تنظیمات با موفقیت ذخیره شدند.' : 'خطایی رخ داد. لطفاً دوباره تلاش کنید.'
            };
            UIkit.notification(notificationOptions);
            $saveButton.prop('disabled', false);

            console.log(response);

        }).fail(function(jqXHR, textStatus, errorThrown) {
            UIkit.notification({
                message: 'خطایی در ارتباط با سرور رخ داد.',
                status: 'danger',
                timeout: 5000,
                pos: 'top-center'
            });
            $saveButton.prop('disabled', false);


        });
    });
});




jQuery(document).ready(function($) {
    $('#general-label-form').on('submit', function(e) {
        e.preventDefault();

        var $saveButton = $('#save-button-label');
        $saveButton.prop('disabled', true);

        var data = $(this).serializeArray();
        data.push({ name: 'action', value: 'save_custom_settings_license' });
        data.push({ name: 'license_settings_nonce', value: $('input[name="license_settings_nonce"]').val() });

        $('#general-settings-form input[type=checkbox]').each(function() {
            if (!$(this).is(':checked')) {
                data.push({ name: $(this).attr('name'), value: '0' });
            }
        });

        $.post(admin_license_ajax.ajax_url, $.param(data), function(response) {
            var notificationOptions = {
                status: response.success ? 'success' : 'danger',
                timeout: 4000,
                pos: 'top-center',
                message: response.success ? 'تنظیمات با موفقیت ذخیره شدند.' : 'خطایی رخ داد. لطفاً دوباره تلاش کنید.'
            };
            UIkit.notification(notificationOptions);
            $saveButton.prop('disabled', false);

            console.log(response);

        }).fail(function(jqXHR, textStatus, errorThrown) {
            UIkit.notification({
                message: 'خطایی در ارتباط با سرور رخ داد.',
                status: 'danger',
                timeout: 5000,
                pos: 'top-center'
            });
            $saveButton.prop('disabled', false);


        });
    });
});