jQuery(document).ready(function ($) {
    function fetchUsers(paged = 1, search = '') {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_users_list',
                paged: paged,
                search: search
            },
            beforeSend: function () {
                $('#user-list').html('<tr><td colspan="2">در حال فراخوانی اطلاعات لطفا صبرکنید...</td></tr>');
            },
            success: function (response) {
                if (response.success) {
                    $('#user-list').html(response.data.html);
                    renderPagination(response.data.total_pages, paged);
                } else {
                    $('#user-list').html('<tr><td colspan="2">اطلاعاتی یافت نشد.</td></tr>');
                }
            },
            error: function () {
                $('#user-list').html('<tr><td colspan="2">متاسفانه خطایی رخ داده است لطفا مجددا وارد شوید.</td></tr>');
            }
        });
    }

    function renderPagination(totalPages, currentPage) {
        $('#pagination').html('');
        for (let i = 1; i <= totalPages; i++) {
            let activeClass = (i === currentPage) ? 'uk-active' : '';
            $('#pagination').append('<li class="' + activeClass + '"><a href="#" data-page="' + i + '">' + i + '</a></li>');
        }
    }

    $('#pagination').on('click', 'a', function (e) {
        e.preventDefault();
        let paged = $(this).data('page');
        let search = $('#user-search').val();
        fetchUsers(paged, search);
    });

    $('#user-search').on('input', function () {
        let search = $(this).val();
        fetchUsers(1, search);
    });

    $('#user-list-container').on('click', '.uk-button-primary', function (e) {
        e.preventDefault();
        var userId = $(this).data('user-id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_user_licenses',
                user_id: userId
            },
            beforeSend: function () {
                $('#user-list-container').hide();
                $('#licenses-container').html('<p>در حال فراخوانی اطلاعات لطفا صبرکنید...</p>');
            },
            success: function (response) {
                if (response.success) {
                    $('#licenses-container').html(response.data);
                } else {
                    $('#licenses-container').html('<p>' + response.data + '</p>');
                }
            },
            error: function () {
                $('#licenses-container').html('<p>متاسفانه خطایی رخ داده است لطفا مجددا وارد شوید.</p>');
            }
        });
    });

    // Add this function to handle the back link
    $('#lpt-general').on('click', function (e) {
        e.preventDefault();
        $('#licenses-container').html(''); // Clear the licenses container
        $('#user-list-container').show(); // Show the user list container
        fetchUsers(); // Fetch the user list again
    });

    fetchUsers();
});





jQuery(document).ready(function ($) {
    function fetchUsers(paged = 1, search = '') {
        $.ajax({
            url: admin_license_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_users_list',
                paged: paged,
                search: search
            },
            beforeSend: function () {
                $('#user-list').html('<tr><td colspan="2">در حال فراخوانی اطلاعات لطفا صبرکنید...</td></tr>');
            },
            success: function (response) {
                if (response.success) {
                    $('#user-list').html(response.data.html);
                    renderUserPagination(response.data.total_pages, paged);
                } else {
                    $('#user-list').html('<tr><td colspan="2">کاربری یافت نشد !!</td></tr>');
                }
            },
            error: function () {
                $('#user-list').html('<tr><td colspan="2">متاسفانه خطایی رخ داده است لطفا مجددا وارد شوید.</td></tr>');
            }
        });
    }







    jQuery(document).ready(function ($) {
        function fetchProducts(paged = 1, search = '') {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_products_list',
                    paged: paged,
                    search: search
                },
                beforeSend: function () {
                    $('#product-list tbody').html('<tr><td colspan="3">در حال فراخوانی اطلاعات لطفا صبر کنید...</td></tr>');
                },
                success: function (response) {
                    if (response.success) {
                        $('#product-list tbody').html(response.data.html);
                        renderProductPagination(response.data.total_pages, paged);
                    } else {
                        $('#product-list tbody').html('<tr><td colspan="3">اطلاعاتی یافت نشد !!</td></tr>');
                    }
                },
                error: function () {
                    $('#product-list tbody').html('<tr><td colspan="3">متاسفانه خطایی رخ داده است لطفا مجددا وارد شوید.</td></tr>');
                }
            });
        }

        function renderProductPagination(totalPages, currentPage) {
            $('#product-pagination').html('');
            for (let i = 1; i <= totalPages; i++) {
                let activeClass = (i === currentPage) ? 'uk-active' : '';
                $('#product-pagination').append('<li class="' + activeClass + '"><a href="#" data-page="' + i + '">' + i + '</a></li>');
            }
        }

        $('#product-pagination').on('click', 'a', function (e) {
            e.preventDefault();
            let paged = $(this).data('page');
            let search = $('#product-search').val();
            fetchProducts(paged, search);
        });

        $('#product-search').on('input', function () {
            let search = $(this).val();
            fetchProducts(1, search);
        });

        $('#product-list').on('click', '.generate-license', function (e) {
            e.preventDefault();
            var productId = $(this).data('product-id');
            var button = $(this);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_product_details',
                    product_id: productId
                },
                beforeSend: function () {
                    button.text('در حال تولید کد...');
                },
                success: function (response) {
                    if (response.success) {
                        var newCode = response.data;
                        button.closest('tr').find('td:nth-child(2)').text(newCode);
                        alertUIkit('success', 'کد مرچنت جدید با موفقیت ایجاد شد.');
                    } else {
                        alertUIkit('danger', 'خطایی رخ داده است: ' + response.data);
                    }
                    button.text('تغییر مرچنت کد');
                },
                error: function () {
                    alertUIkit('danger', 'متاسفانه خطایی رخ داده است لطفا مجددا وارد شوید.');
                    button.text('تغییر مرچنت کد');
                }
            });
        });

        function alertUIkit(status, message) {
            UIkit.notification({
                message: message,
                status: status,
                pos: 'top-center',
                timeout: 3000
            });
        }

        fetchProducts();
    });
});


jQuery(document).ready(function ($) {
    // نمایش تاریخچه دامنه‌ها
    $(document).on('click', '.view-history', function (e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        var productId = $(this).data('product-id');
        var orderItemId = $(this).data('order-item-id');

        $.ajax({
            url: admin_license_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_domain_history',
                user_id: userId,
                product_id: productId,
                order_item_id: orderItemId,
                security: admin_license_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $('#history-content').html(response.data);
                    UIkit.modal('#history-modal').show();
                } else {
                    $('#history-content').html('<p>' + response.data + '</p>');
                    UIkit.modal('#history-modal').show();
                }
            },
            error: function () {
                $('#history-content').html('<p>خطا در بارگذاری تاریخچه.</p>');
                UIkit.modal('#history-modal').show();
            }
        });
    });
});

/*--------------------------*/

