<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class ACF_Field_Secure_File extends acf_field {

    function __construct() {
        $this->name = 'secure_file';
        $this->label = __( 'Secure File', 'acf-secure-file-field' );
        $this->category = 'content';
        $this->defaults = array(
            'return_format' => 'array', // array (info) or id
        );
        parent::__construct();
    }

    function input_admin_enqueue_scripts() {
        $url = ACF_SECURE_FILE_FIELD_URL;
        wp_enqueue_script('acf-secure-file-field-js', $url . 'assets/js/input.js', array('jquery'), '1.1', true);
        wp_enqueue_style('acf-secure-file-field-css', $url . 'assets/css/input.css', array(), '1.1');

        // Localize strings for JS
        wp_localize_script('acf-secure-file-field-js', 'asff_i18n', array(
            'select' => __('Select', 'acf-secure-file-field'),
            'no_secure_files_found' => __('No secure files found.', 'acf-secure-file-field'),
            'error_fetching_files' => __('An error occurred while fetching files.', 'acf-secure-file-field'),
            'upload_failed' => __('Upload failed.', 'acf-secure-file-field'),
            'download' => __('Download', 'acf-secure-file-field'),
            'no_file_selected' => __('No file selected', 'acf-secure-file-field'),
        ));
    }

    function render_field( $field ) {
    // Current Value Logic
    $file_data = null;
    if( $field['value'] ) {
        $post_id = $field['value'];
        $hash = get_post_meta($post_id, 'asff_download_hash', true);
        $name = get_post_meta($post_id, 'asff_original_name', true);
        if( $hash ) {
            $file_data = array(
                'id' => $post_id,
                'name' => $name,
                'url' => home_url( '/?secure-file-download=' . $hash )
            );
        }
    }
    ?>
    <div class="acf-secure-file-field-wrapper">
        <input type="hidden" name="<?php echo esc_attr($field['name']); ?>" value="<?php echo esc_attr($field['value']); ?>" class="acf-secure-file-field-value">

        <div class="asff-file-preview <?php echo $file_data ? 'has-file' : 'no-file'; ?>">
            <div class="asff-selected-file">
                <span class="dashicons dashicons-media-document"></span>
                <span class="filename"><?php echo $file_data ? esc_html($file_data['name']) : esc_html__('No file selected', 'acf-secure-file-field'); ?></span>
                <?php if($file_data): ?>
                    <a href="<?php echo esc_url($file_data['url']); ?>" target="_blank" class="button button-small asff-download"><?php esc_html_e('Download', 'acf-secure-file-field'); ?></a>
                <?php endif; ?>
            </div>
            <div class="asff-actions">
                <button type="button" class="button asff-select-file"><?php esc_html_e('Select File', 'acf-secure-file-field'); ?></button>
                <button type="button" class="button button-link asff-remove-file"><?php esc_html_e('Remove', 'acf-secure-file-field'); ?></button>
            </div>
        </div>

        <!-- Modal Structure -->
        <div class="asff-modal-backdrop" style="display: none;">
            <div class="asff-modal-content">
                <div class="asff-modal-header">
                    <h2><?php esc_html_e('Secure File', 'acf-secure-file-field'); ?></h2>
                    <button type="button" class="asff-modal-close">&times;</button>
                </div>
                <div class="asff-modal-body">
                    <div class="asff-modal-tabs">
                        <a href="#asff-tab-upload" class="asff-tab active"><?php esc_html_e('Upload New', 'acf-secure-file-field'); ?></a>
                        <a href="#asff-tab-select" class="asff-tab"><?php esc_html_e('Select Existing', 'acf-secure-file-field'); ?></a>
                    </div>
                    <div id="asff-tab-upload" class="asff-tab-content active">
                        <div class="asff-uploader-ui">
                            <input type="file" class="asff-file-input">
                            <div class="asff-progress-bar"><div class="bar"></div></div>
                            <p class="description"><?php esc_html_e('Select or drag a file for secure upload.', 'acf-secure-file-field'); ?></p>
                        </div>
                    </div>
                    <div id="asff-tab-select" class="asff-tab-content">
                        <div class="asff-file-list-container">
                            <!-- Files will be loaded here via AJAX -->
                            <div class="asff-loader"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
}

new ACF_Field_Secure_File();
