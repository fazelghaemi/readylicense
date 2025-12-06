jQuery(document).ready(function ($) {

    var menuItem = $('.mf-menu-item a');
    menuItem.click(function (e) {
        e.preventDefault();
        var $this = $(this);
        var $toggle = $this.data('toggle');
        $('.mf-menu-item a').removeClass('mf-active');
        $this.addClass('mf-active');
        $('.mf-settings-area').removeClass('mf-active');
        $('.mf-settings-' + $toggle).addClass('mf-active');
    });

    $(".mf-confirm").click(function () {
        var $this = $(this);
        var $toggle = $this.data('toggle');
        if (this.checked) {
            $('#mf-' + $toggle).slideDown();
        } else {
            $('#mf-' + $toggle).slideUp();
        }
    });

    $(".mf-confirm").each(function () {
        var $this = $(this);
        if ($this.is(':checked')) {
            var $toggle = $this.data('toggle');
            $('#mf-' + $toggle).show();
        }
    });

    $("select.mf-confirm").change(function () {
        var $this = $(this);
        var $name = $this.attr('name');
        if ($this.val()) {
            $('#mf-' + $name).slideDown();
        } else {
            $('#mf-' + $name).slideUp();
        }
    });

    $("select.mf-confirm").each(function () {
        var $this = $(this);
        if ($this.val()) {
            var $name = $this.attr('name');
            $('#mf-' + $name).show();
        }
    });

    $('.mf-clipboard').click(function () {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(this).text()).select();
        document.execCommand("copy");
        $temp.remove();
    });

    $(document).on('click', '.mf-upload-file', function (e) {
        e.preventDefault();
        var $this = $(this);
        var image = wp.media({
            multiple: false,
        }).open().on('select', function (e) {
            var uploadedImage = image.state().get('selection').first();
            var imageID = uploadedImage.toJSON().id;
            var imageURL = uploadedImage.toJSON().url;
            $this.val(imageURL);
            $this.siblings('.mf-url').attr('href', imageURL);
            $this.prev('input[type="hidden"]').val(imageID);
        });
    });

    $(document).on('click', '.mf-remove', function (e) {
        e.preventDefault();
        var $this = $(this);
        var $toggle = $this.data('toggle');
        $('input[name="' + $toggle + '"], #mf-' + $toggle).val('');
    });

    $(document).on('click', '.mf-select-uploader', function (e) {
        e.preventDefault();
        var $this = $(this);
        var $target = $this.data('target');
        var targetType = $this.data('target-type');
        var image = wp.media({multiple: false,}).open().on('select', function (e) {
            var uploadedImage = image.state().get('selection').first();
            var imageID = uploadedImage.toJSON().id;
            var imageURL = uploadedImage.toJSON().url;
            switch (true) {
                case targetType === 'image':
                    $('#' + $target).attr('src', imageURL);
                    $('#' + $target + '_input').val(imageID);
                    break;
                case targetType === 'widget':
                    $('#' + $target).attr('src', imageURL);
                    $('#' + $target + '_input').val(imageID).trigger('change');
                    break;
                case targetType === 'video':
                    $('#' + $target + '_input').val(imageID);
                    $('#' + $target + '_url').val(imageURL);
                    break;
                case targetType === 'thumbnail':
                    $this.val(imageID);
                    break;
            }
        });
    });

    $(document).on('click', '.mf-remove-uploader', function (e) {
        e.preventDefault();
        var $this = $(this);
        var target = $this.data('target');
        $('#' + target).attr('src', '');
        $('#' + target + '_input').attr('value', '').trigger('change');
        if (target === 'video') {
            $('#' + target + '_url').val('');
            $('#mf-product-video').find('.wp-video').remove();
        }
    });

    var nonce = $('meta[name="mf-nonce"]').attr('content');

    $('#mf-settings-form').submit(function (e) {
        e.preventDefault();
        var $this = $(this);
        //tinyMCE.triggerSave();
        var button = $this.find('.uk-button');
        var loader = button.find('.loader');
        button.prop('disabled', true);
        loader.show();

        $.ajax({
            url: ajaxurl,
            type: 'post',
            dataType: 'json',
            timeout: 20000,
            data: {
                action: 'mf_update_settings',
                nonce: nonce,
                formData: $this.serialize()
            },
            success: function (response) {
                if (response.result === true) {
                    UIkit.notification({
                        message: MF_DATA.settings_saved,
                        status: 'success',
                        pos: 'bottom-center',
                        timeout: 5000
                    });
                }
            },
            error: function () {
                UIkit.notification({
                    message: MF_DATA.error_occurred,
                    status: 'danger',
                    pos: 'bottom-center',
                    timeout: 5000
                });
            },
            complete: function (data) {
                button.prop('disabled', false);
                loader.hide();
            }
        });
    });

    if ($('.color-field').length) {
        $('.color-field').wpColorPicker();
    }

    $(document).on('change', '.widget-content .mf-post-type', function (e) {
        var $this = $(this);
        var postType = $this.val();
        $this.parentsUntil('.widget-inside').find('.taxonomy').addClass('mf-dnone');
        $this.parentsUntil('.widget-inside').find('.taxonomy.' + postType).removeClass('mf-dnone');
    });

    $('.mf-sortable').sortable();

    $(document).on('click', '.mf-actions .remove', function () {
        $(this).parentsUntil('.mf-sortable').remove();
    });

    $(document).on('click', '.mf-new-social-network', function (e) {
        e.preventDefault();
        var $this = $(this);
        var $key = Math.floor((Math.random() * 1000000000));

        var $html = '<div class="mf-social-network-item mf-item uk-margin-small">';
        $html += '<div class="mf-actions"><span class="move" uk-icon="move" uk-tooltip="title: ' + MF_DATA.move + '"></span><span class="remove" uk-icon="close" uk-tooltip="title: ' + MF_DATA.remove + '"></span></div>';
        $html += '<div class="uk-margin-small"><input type="text" class="uk-input" name="social-network[' + $key + '][icon]" value="" placeholder="' + MF_DATA.icon + '"></div>';
        $html += '<div class="uk-margin-small"><input type="text" class="uk-input" name="social-network[' + $key + '][url]" value="" placeholder="' + MF_DATA.url + '"></div>';
        $html += '<div class="uk-margin-small"><input type="text" class="uk-input" name="social-network[' + $key + '][title]" value="" placeholder="' + MF_DATA.title + '"></div>';
        $html += '<div class="uk-margin-small"><input type="text" class="uk-input" name="social-network[' + $key + '][xfn]" value="" placeholder="' + MF_DATA.xfn + '"></div>';
        $html += '</div>';
        $this.before($html);
    });

    $('#new-download-box').click(function (e) {
        e.preventDefault();

        var table = $('#download-box-table');
        var count = parseInt(table.find('tr').length) / 7;
        count++;

        var download_box = '<tr>';
        download_box += '<td><label for="download-box-title' + count + '">' + MF_DATA.title + '</label></td>';
        download_box += '<td><input type="text" class="regular-text" name="download-box-title' + count + '" id="download-box-title' + count + '"></td>';
        download_box += '</tr>';
        download_box += '<tr>';
        download_box += '<td><label for="download-box-label' + count + '">' + MF_DATA.label + '</label></td>';
        download_box += '<td><input type="text" class="regular-text" name="download-box-label' + count + '" id="download-box-label' + count + '"></td>';
        download_box += '</tr>';
        download_box += '<tr>';
        download_box += '<td><label for="download-box-url"' + count + '">' + MF_DATA.url + '</label></td>';
        download_box += '<td><input type="text" class="regular-text" name="download-box-url' + count + '" id="download-box-url' + count + '" dir="ltr"></td>';
        download_box += '</tr>';
        download_box += '<tr>';
        download_box += '<td><label for="download-box-xfn"' + count + '">' + MF_DATA.xfn + '</label></td>';
        download_box += '<td><input type="text" class="regular-text" name="download-box-xfn' + count + '" id="download-box-xfn' + count + '" dir="ltr"></td>';
        download_box += '</tr>';
        download_box += '<tr>';
        download_box += '<td><label for="download-box-target' + count + '">' + MF_DATA.open_new_tab + '</label></td>';
        download_box += '<td><input type="checkbox" name="download-box-target' + count + '" id="download-box-target' + count + '" value="1"><span>' + MF_DATA.active + '</span></td>';
        download_box += '</tr>';
        download_box += '<tr>';
        download_box += '<td><label for="download-box-desc' + count + '">' + MF_DATA.description + '</label></td>';
        download_box += '<td><textarea class="regular-text" name="download-box-desc' + count + '" id="download-box-desc' + count + '"></textarea></td>';
        download_box += '</tr>';
        download_box += '<tr><td colspan="2"><hr></td></tr>';

        table.append(download_box);
        $('#download-box-count').val(count);
    });

    $('#new-product-badge').click(function (e) {
        e.preventDefault();

        var table = $('#product-badges-table');
        var count = parseInt(table.find('tr').length) / 3;
        count++;

        var product_badge = '<tr>';
        product_badge += '<td><label for="product-badge-title' + count + '">' + MF_DATA.title + '</label></td>';
        product_badge += '<td><input type="text" class="regular-text" name="product-badge-title' + count + '" id="product-badge-title' + count + '"></td>';
        product_badge += '</tr>';
        product_badge += '<tr>';
        product_badge += '<td><label for="product-badge-icon' + count + '">' + MF_DATA.icon + '</label></td>';
        product_badge += '<td><input type="text" class="regular-text" name="product-badge-icon' + count + '" id="product-badge-icon' + count + '">';
        product_badge += '<a href="https://materialdesignicons.com" target="_blank">' + MF_DATA.get_icon + '</a></td>';
        product_badge += '</tr>';
        product_badge += '<tr><td colspan="2"><hr></td></tr>';

        table.append(product_badge);
        $('#product-badge-count').val(count);
    });

    $('#new-course-section').click(function (e) {
        e.preventDefault();

        var container = $('#mf-product-course-section .course-container');

        var section = '<div class="section">';
        section += '<p>';
        section += '<input type="text" class="course regular-text" placeholder="' + MF_DATA.course_title + '">';
        section += '<a href="#" class="actions remove-course"><span class="dashicons dashicons-no-alt"></span></a>';
        section += '<a href="#" class="actions order-course"><span class="dashicons dashicons-menu"></span></a>';
        section += '</p>';
        section += '<p><a href="#" class="button" id="new-course-lesson">' + MF_DATA.new_lesson + '</a></p>';
        section += '</div>';

        container.append(section);
    });

    $(document).on('click', '#new-course-lesson', function (e) {
        e.preventDefault();
        var $this = $(this);

        var course = $this.parentsUntil('.course-container').find('.course');
        var course_val = course.val();
        if (!course_val || course_val === '') {
            alert(MF_DATA.enter_course_title);
            return false;
        }

        var lesson = '<div class="lesson">';
        lesson += '<p>';
        lesson += '<input type="text" class="lesson-title regular-text" data-name="title" data-course-title="' + course_val + '" placeholder="' + MF_DATA.lesson_title + '">';
        lesson += '<a href="#" class="actions remove-lesson"><span class="dashicons dashicons-no-alt"></span></a>';
        lesson += '<a href="#" class="actions order-lesson"><span class="dashicons dashicons-menu"></span></a>';
        lesson += '</p>';
        lesson += '<p><input type="text" class="regular-text" data-name="icon" placeholder="' + MF_DATA.icon + '"></p>';
        lesson += '<p><input type="text" class="regular-text" data-name="subtitle" placeholder="' + MF_DATA.subtitle + '"></p>';
        lesson += '<p>';
        lesson += '<select class="regular-text" data-name="label">';
        lesson += '<option class="no-badge" value="no-badge">' + MF_DATA.select_item + '</option>';
        lesson += '<option class="video" value="video">' + MF_DATA.video + '</option>';
        lesson += '<option class="exam" value="exam">' + MF_DATA.exam + '</option>';
        lesson += '<option class="quiz" value="quiz">' + MF_DATA.quiz + '</option>';
        lesson += '<option class="lecture" value="lecture">' + MF_DATA.lecture + '</option>';
        lesson += '<option class="free" value="free">' + MF_DATA.free + '</option>';
        lesson += '<option class="practice" value="practice">' + MF_DATA.practice + '</option>';
        lesson += '<option class="attachments" value="attachments">' + MF_DATA.attachments + '</option>';
        lesson += '</select>';
        lesson += '</p>';
        lesson += '<p><label><input type="checkbox" class="regular-text" data-name="private">' + MF_DATA.Private + '</label></p>';
        lesson += '<p><input type="text" class="regular-text" data-name="preview-video" placeholder="' + MF_DATA.preview_video + '"></p>';
        lesson += '<p><input type="text" class="regular-text" data-name="download-lesson" placeholder="' + MF_DATA.download_url + '"></p>';
        lesson += '<p><textarea class="regular-text" data-name="description" rows="4" placeholder="' + MF_DATA.description + '"></textarea></p>';
        lesson += '</div>';

        $this.parentsUntil('.section').before(lesson);
    });

    $(document).on('input', '.lesson-title', function (e) {
        var $this = $(this);
        var lesson_value = $this.val();
        var course_title = $this.data('course-title');
        var lesson_wrapper = $this.parentsUntil('.section');

        if (!lesson_value || lesson_value === '' || !course_title || course_title === '') {
            return false;
        }

        lesson_wrapper.find('.regular-text:not(.lesson-title)').each(function () {
            var $this = $(this);
            var name = $this.data('name');
            $(this).attr('name', 'course[' + course_title + '][' + lesson_value + '][' + name + ']');
        });
    });

    $('#mf-product-course-section .course-container').sortable();
    $('#mf-product-course-section .section').sortable({items: ".lesson"});

    $('.course-container .close-all').click(function (e) {
        e.preventDefault();

        $('.course-container .lesson').hide();
    });

    $('.course-container .expand-all').click(function (e) {
        e.preventDefault();

        $('.course-container .lesson').show();
    });

    $(document).on('click', '.remove-course', function (e) {
        e.preventDefault();
        var $this = $(this);
        if (!confirm(MF_DATA.sure_remove)) {
            return false;
        }

        $this.parentsUntil('.course-container').remove();
    });

    $(document).on('click', '.remove-lesson', function (e) {
        e.preventDefault();
        var $this = $(this);
        if (!confirm(MF_DATA.sure_remove)) {
            return false;
        }

        $this.parentsUntil('.section').remove();
    });

    $('#new-teacher-skill').click(function (e) {
        e.preventDefault();

        var table = $('#teacher-skill-table');
        var count = parseInt(table.find('tr').length);
        count++;

        var teacher_skill = '<tr>';
        teacher_skill += '<td><label for="teacher-skill' + count + '">' + MF_DATA.skill + ' ' + count + '</label></td>';
        teacher_skill += '<td><input type="text" class="regular-text" name="teacher-skill' + count + '" id="teacher-skill' + count + '" placeholder="' + MF_DATA.title_percent_hex + '"></td>';
        teacher_skill += '</tr>';

        table.append(teacher_skill);
        $('#teacher-skill-count').val(count);
    });
});


