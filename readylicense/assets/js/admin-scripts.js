jQuery(document).ready(function($) {
    
    // متغیرهای گلوبال از PHP
    const config = window.rl_obj || {};

    // --- Tab Navigation ---
    $('.rl-sidebar li').on('click', function() {
        const tabId = $(this).data('tab');
        
        // UI Updates
        $('.rl-sidebar li').removeClass('active');
        $(this).addClass('active');
        
        $('.rl-tab-pane').removeClass('active');
        $('#tab-' + tabId).addClass('active');

        // Lazy Load Data
        if (tabId === 'users' && $('#rl-users-list-body').is(':empty')) {
            loadData('get_users', '#rl-users-list-body', 1);
        }
        if (tabId === 'products' && $('#rl-products-list-body').is(':empty')) {
            loadData('get_products', '#rl-products-list-body', 1);
        }
    });

    // --- Data Loading (Generic) ---
    function loadData(actionType, target, page, search = '') {
        const $target = $(target);
        $target.html('<tr><td colspan="3" class="text-center"><span class="spinner is-active"></span> در حال بارگذاری...</td></tr>');

        $.post(config.ajax_url, {
            action: 'rl_admin_action',
            security: config.nonce,
            request_type: actionType,
            paged: page,
            search: search
        }, function(res) {
            if (res.success) {
                $target.html(res.data.html);
                // اینجا می‌توان پیجینیشن را رندر کرد (ساده‌سازی شده)
            } else {
                $target.html('<tr><td colspan="3" class="text-center">خطا در دریافت اطلاعات</td></tr>');
            }
        });
    }

    // --- Live Search (Debounced) ---
    let timer;
    $('#rl-user-search, #rl-product-search').on('input', function() {
        clearTimeout(timer);
        const val = $(this).val();
        const type = $(this).attr('id') === 'rl-user-search' ? 'get_users' : 'get_products';
        const target = $(this).attr('id') === 'rl-user-search' ? '#rl-users-list-body' : '#rl-products-list-body';

        timer = setTimeout(() => {
            loadData(type, target, 1, val);
        }, 500);
    });

    // --- Actions: Generate Merchant Code ---
    $(document).on('click', '.generate-merchant', function(e) {
        e.preventDefault();
        const btn = $(this);
        const id = btn.data('id');
        
        btn.addClass('disabled').text('...');

        $.post(config.ajax_url, {
            action: 'rl_admin_action',
            security: config.nonce,
            request_type: 'generate_merchant',
            product_id: id
        }, function(res) {
            if (res.success) {
                btn.closest('tr').find('.code-box').text(res.data.code);
                btn.text('تغییر کد');
            }
            btn.removeClass('disabled');
        });
    });

    // --- Save Settings ---
    $('#rl-settings-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        const spinner = $(this).find('.spinner');
        
        btn.prop('disabled', true);
        spinner.addClass('is-active');

        $.post(config.ajax_url, {
            action: 'rl_admin_action',
            security: config.nonce,
            request_type: 'save_settings',
            settings: getFormData($(this))
        }, function(res) {
            if (res.success) {
                // Show a nice toast notification here if you want
                alert('تنظیمات ذخیره شد.');
            }
            btn.prop('disabled', false);
            spinner.removeClass('is-active');
        });
    });

    // --- Encoder ---
    $('#rl-btn-encode').on('click', function() {
        const code = $('#rl-raw-code').val();
        if (!code) return alert('لطفا کدی وارد کنید');

        const btn = $(this);
        btn.prop('disabled', true).text('در حال پردازش...');

        $.post(config.ajax_url, {
            action: 'rl_admin_action',
            security: config.nonce,
            request_type: 'encode_code',
            code: code
        }, function(res) {
            if (res.success) {
                $('#rl-encoded-code').val(res.data.encoded);
                $('#rl-encoded-result').slideDown();
            }
            btn.prop('disabled', false).text('رمزنگاری کن');
        });
    });

    // Helper: Form Data to Object
    function getFormData($form){
        var unindexed_array = $form.serializeArray();
        var indexed_array = {};
        $.map(unindexed_array, function(n, i){
            // Handle array names like settings[key]
            let name = n['name'];
            if (name.includes('[')) {
                name = name.split('[')[1].split(']')[0];
            }
            indexed_array[name] = n['value'];
        });
        return indexed_array;
    }
});