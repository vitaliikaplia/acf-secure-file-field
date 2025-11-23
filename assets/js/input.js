jQuery(document).ready(function($) {

    // Helper to initialize or handle events
    $(document).on('change', '.asff-file-input', function(e) {
        var $input = $(this);
        var $wrapper = $input.closest('.acf-secure-file-wrapper');
        var $progress = $wrapper.find('.asff-progress-bar');
        var $bar = $wrapper.find('.bar');
        var file = this.files[0];

        if (!file) return;

        var formData = new FormData();
        formData.append('action', 'asff_upload_file');
        formData.append('file', file);
        formData.append('nonce', acf.get('nonce')); // Using ACF's nonce

        $progress.show();
        $input.prop('disabled', true);

        $.ajax({
            url: acf.get('ajaxurl'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        percentComplete = parseInt(percentComplete * 100);
                        $bar.css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $input.prop('disabled', false);
                $input.val(''); // Clear input
                $progress.hide();
                $bar.css('width', '0%');

                if( response.success ) {
                    var data = response.data;

                    // Update Value
                    $wrapper.find('.acf-secure-file-value').val(data.id);

                    // Update Preview
                    var $preview = $wrapper.find('.acf-secure-file-preview');
                    $preview.find('.filename').text(data.name);

                    // Add download link if not exists or update it
                    var $link = $preview.find('a.button');
                    if($link.length) {
                        $link.attr('href', data.url);
                    } else {
                        $preview.find('.file-info').append('<a href="'+data.url+'" target="_blank" class="button button-small">Завантажити</a>');
                    }

                    $wrapper.find('.acf-secure-file-uploader').addClass('hidden');
                    $preview.addClass('has-file');

                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Upload error');
                $input.prop('disabled', false);
                $progress.hide();
            }
        });
    });

    // Remove File Handler
    $(document).on('click', '.asff-remove', function(e) {
        e.preventDefault();
        var $wrapper = $(this).closest('.acf-secure-file-wrapper');

        $wrapper.find('.acf-secure-file-value').val('');
        $wrapper.find('.acf-secure-file-preview').removeClass('has-file');
        $wrapper.find('.acf-secure-file-uploader').removeClass('hidden');
    });

});
