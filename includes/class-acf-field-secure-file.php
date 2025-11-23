<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ACF_Field_Secure_File extends acf_field {

    function __construct() {
        $this->name = 'secure_file';
        $this->label = __( 'Secure File', 'acf-secure-file' );
        $this->category = 'content';
        $this->defaults = array(
            'return_format' => 'array', // array (info) or id
        );
        parent::__construct();
    }

    function render_field( $field ) {
    // Enqueue scripts specifically for this render
    $url = ACF_SECURE_FILE_URL;
    wp_enqueue_script('acf-secure-file-js', $url . 'assets/js/input.js', array('jquery'), '1.1', true);
    wp_enqueue_style('acf-secure-file-css', $url . 'assets/css/input.css', array(), '1.1');

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
    <div class="acf-secure-file-wrapper">
        <input type="hidden" name="<?php echo esc_attr($field['name']); ?>" value="<?php echo esc_attr($field['value']); ?>" class="acf-secure-file-value">

        <div class="asff-file-preview <?php echo $file_data ? 'has-file' : 'no-file'; ?>">
            <div class="asff-selected-file">
                <span class="dashicons dashicons-media-document"></span>
                <span class="filename"><?php echo $file_data ? esc_html($file_data['name']) : esc_html__('No file selected', 'acf-secure-file'); ?></span>
                <?php if($file_data): ?>
                    <a href="<?php echo esc_url($file_data['url']); ?>" target="_blank" class="button button-small asff-download"><?php esc_html_e('Download', 'acf-secure-file'); ?></a>
                <?php endif; ?>
            </div>
            <div class="asff-actions">
                <button type="button" class="button asff-select-file"><?php esc_html_e('Select File', 'acf-secure-file'); ?></button>
                <button type="button" class="button button-link asff-remove-file"><?php esc_html_e('Remove', 'acf-secure-file'); ?></button>
            </div>
        </div>

        <!-- Modal Structure -->
        <div class="asff-modal-backdrop" style="display: none;">
            <div class="asff-modal-content">
                <div class="asff-modal-header">
                    <h2><?php esc_html_e('Secure File', 'acf-secure-file'); ?></h2>
                    <button type="button" class="asff-modal-close">&times;</button>
                </div>
                <div class="asff-modal-body">
                    <div class="asff-modal-tabs">
                        <a href="#asff-tab-upload" class="asff-tab active"><?php esc_html_e('Upload New', 'acf-secure-file'); ?></a>
                        <a href="#asff-tab-select" class="asff-tab"><?php esc_html_e('Select Existing', 'acf-secure-file'); ?></a>
                    </div>
                    <div id="asff-tab-upload" class="asff-tab-content active">
                        <div class="asff-uploader-ui">
                            <input type="file" class="asff-file-input">
                            <div class="asff-progress-bar"><div class="bar"></div></div>
                            <p class="description"><?php esc_html_e('Select or drag a file for secure upload.', 'acf-secure-file'); ?></p>
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
