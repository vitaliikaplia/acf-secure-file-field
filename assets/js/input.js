(($) => {

    if (typeof acf === 'undefined') {
        return;
    }

    const SecureField = acf.Field.extend({
        type: 'secure_file',
        events: {
            'click .asff-select-file': 'onClickSelect',
            'click .asff-remove-file': 'onClickRemove',
            'click .asff-modal-close': 'onClickCloseModal',
            'click .asff-modal-backdrop': 'onClickCloseModal',
            'click .asff-modal-content': 'onModalContentClick',
            'click .asff-tab': 'onTabClick',
            'click .asff-file-list-item .button': 'onSelectExistingFile',
            'change .asff-file-input': 'onFileSelectedForUpload',
        },

        initialize() {
            this.$modal = this.$('.asff-modal-backdrop');
            this.$valueInput = this.$('.acf-secure-file-field-value');
            this.$preview = this.$('.asff-file-preview');
        },

        onClickSelect(e) {
            e.preventDefault();
            this.$modal.show();
            if (this.$('.asff-tab[href="#asff-tab-select"]').hasClass('active')) {
                this.loadExistingFiles();
            }
        },

        onClickCloseModal(e) {
            e.preventDefault();
            this.$modal.hide();
        },

        onModalContentClick(e) {
            e.stopPropagation();
        },

        onTabClick(e) {
            e.preventDefault();
            const $el = $(e.currentTarget);
            const tabId = $el.attr('href');

            this.$('.asff-tab').removeClass('active');
            $el.addClass('active');

            this.$('.asff-tab-content').removeClass('active');
            this.$(tabId).addClass('active');

            if (tabId === '#asff-tab-select') {
                this.loadExistingFiles();
            }
        },

        onSelectExistingFile(e) {
            e.preventDefault();
            const fileData = $(e.currentTarget).closest('.asff-file-list-item').data('file');
            this.selectFile(fileData);
        },

        onFileSelectedForUpload(e) {
            const file = e.currentTarget.files[0];
            if (!file) return;
            this.uploadFile(file);
        },

        loadExistingFiles() {
            const $container = this.$('#asff-tab-select .asff-file-list-container');

            if ($container.data('loaded')) return;

            $container.html('<div class="asff-loader"></div>');

            $.ajax({
                url: acf.get('ajaxurl'),
                method: 'POST',
                data: {
                    action: 'asff_get_secure_files',
                    nonce: acf.get('nonce'),
                },
                success: (response) => {
                    if (response.success) {
                        $container.data('loaded', true);
                        if (response.data.length) {
                            let html = '<ul>';
                            response.data.forEach((file) => {
                                html += `
                                    <li class="asff-file-list-item" data-file='${JSON.stringify(file)}'>
                                        <span class="dashicons dashicons-media-document"></span>
                                        <span class="filename">${file.name}</span>
                                        <button type="button" class="button button-primary">${asff_i18n.select}</button>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                            $container.html(html);
                        } else {
                            $container.html(`<p>${asff_i18n.no_secure_files_found}</p>`);
                        }
                    } else {
                        $container.html(`<p>${response.data.message}</p>`);
                    }
                },
                error: () => {
                    $container.html(`<p>${asff_i18n.error_fetching_files}</p>`);
                }
            });
        },

        uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'asff_upload_file');
            formData.append('nonce', acf.get('nonce'));

            const $progressBar = this.$('.asff-progress-bar .bar');

            $.ajax({
                url: acf.get('ajaxurl'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: () => {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', (evt) => {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $progressBar.width(`${percentComplete}%`);
                        }
                    }, false);
                    return xhr;
                },
                beforeSend: () => {
                    $progressBar.width('0%').parent().show();
                },
                success: (response) => {
                    $progressBar.width('100%');
                    if (response.success) {
                        this.selectFile(response.data);
                    } else {
                        alert(response.data);
                    }
                },
                error: () => {
                    alert(asff_i18n.upload_failed);
                },
                complete: () => {
                    setTimeout(() => {
                        $progressBar.parent().hide();
                        $progressBar.width('0%');
                    }, 1000);
                    this.$('.asff-file-input').val('');
                }
            });
        },

        selectFile(fileData) {
            this.$valueInput.val(fileData.id).trigger('change');

            const $previewFile = this.$preview.find('.asff-selected-file');
            $previewFile.find('.filename').text(fileData.name);

            let $downloadBtn = $previewFile.find('.asff-download');
            if ($downloadBtn.length) {
                $downloadBtn.attr('href', fileData.url);
            } else {
                $previewFile.append(` <a href="${fileData.url}" target="_blank" class="button button-small asff-download">${asff_i18n.download}</a>`);
            }

            this.$preview.removeClass('no-file').addClass('has-file');
            this.$modal.hide();
        },

        onClickRemove(e) {
            e.preventDefault();
            this.$valueInput.val('').trigger('change');

            const $previewFile = this.$preview.find('.asff-selected-file');
            $previewFile.find('.filename').text(asff_i18n.no_file_selected);
            $previewFile.find('.asff-download').remove();

            this.$preview.removeClass('has-file').addClass('no-file');
        }
    });

    acf.registerFieldType(SecureField);

})(jQuery);