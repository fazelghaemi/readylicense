jQuery(document).ready(function($) {
    $('#php-code-encoder-form').on('submit', function(e) {
        e.preventDefault();

        var code = $('textarea[name="code"]').val();

        $.ajax({
            url: phpCodeEncoder.ajax_url,
            method: 'POST',
            data: {
                code: code
            },
            success: function(response) {
                $('#encoded-code').val(response);
                $('#encoded-code-container').show();
            },
            error: function() {
                alert('خطا در ارسال درخواست.');
            }
        });
    });
});
