jQuery(document).ready(function($) {
    $(document).on('click', '.edit-domain', function(e) {
        e.preventDefault();
        var $this = $(this);
        var userId = $this.data('user-id');
        var productId = $this.data('product-id');
        var orderItemId = $this.data('order-item-id');
        var oldDomain = $this.data('domain');

        // Open UIkit modal for domain editing
        UIkit.modal.prompt('نام دامنه خود را وارد کنید:', oldDomain).then(function(newDomain) {
            if (newDomain === null || newDomain.trim() === "" || newDomain === oldDomain) {
                return; // If user cancels the prompt or enters an empty value or the same domain
            }

            // Disable the button to prevent multiple requests
            $this.prop('disabled', true);

            // Perform AJAX request to save the new domain
            $.ajax({
                url: admin_license_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edit_user_domain',
                    user_id: userId,
                    product_id: productId,
                    order_item_id: orderItemId,
                    old_domain: oldDomain,
                    new_domain: newDomain,
                    security: admin_license_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alertUIkit('success', 'دامنه با موفقیت ویرایش شد.');
                        // Update the domain in the DOM without reloading the page
                        $this.data('domain', newDomain);
                        // Update the domain in the "دامنه فعال" column
                        var $domainColumn = $this.closest('tr').find('.domains-column');
                        $domainColumn.find('.domain-name').each(function() {
                            if ($(this).text() === oldDomain) {
                                $(this).text(newDomain);
                            }
                        });
                    } else {
                        alertUIkit('danger', response.data || 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
                    }
                },
                error: function() {
                    alertUIkit('danger', 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
                },
                complete: function() {
                    // Re-enable the button after the request is complete
                    $this.prop('disabled', false);
                }
            });
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
