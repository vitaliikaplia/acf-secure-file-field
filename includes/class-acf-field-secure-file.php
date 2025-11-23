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
        wp_enqueue_script('acf-secure-file-js', $url . 'assets/js/input.js', array('jquery'), '1.0', true);
        wp_enqueue_style('acf-secure-file-css', $url . 'assets/css/input.css', array(), '1.0');

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
        <div class="acf-secure-file-wrapper" data-uploader="true">
            <input type="hidden" name="<?php echo esc_attr($field['name']); ?>" value="<?php echo esc_attr($field['value']); ?>" class="acf-secure-file-value">

            <div class="acf-secure-file-preview <?php echo $file_data ? 'has-file' : ''; ?>">
                <div class="file-info">
                    <span class="dashicons dashicons-media-document"></span>
                    <span class="filename"><?php echo $file_data ? esc_html($file_data['name']) : ''; ?></span>
                    <?php if($file_data): ?>
                        <a href="<?php echo esc_url($file_data['url']); ?>" target="_blank" class="button button-small"><?php esc_html_e( 'Download', 'acf-secure-file' ); ?></a>
                    <?php endif; ?>
                </div>
                <div class="actions">
                    <button type="button" class="button button-link asff-remove"><?php esc_html_e( 'Remove', 'acf-secure-file' ); ?></button>
                </div>
            </div>

            <div class="acf-secure-file-uploader <?php echo $file_data ? 'hidden' : ''; ?>">
                <input type="file" class="asff-file-input">
                <div class="asff-progress-bar"><div class="bar"></div></div>
                <p class="description"><?php esc_html_e( 'Select a file for secure upload', 'acf-secure-file' ); ?></p>
            </div>
        </div>
        <?php
    }
}

new ACF_Field_Secure_File();
