<?php

/*
Plugin Name: ACF Secure File Field
Plugin URI: https://wordpress.org/plugins/acf-secure-file-field/
Description: Custom ACF field for secure file uploads outside Media Library
Version: 1.0
Author: Vitalii Kaplia
Author URI: https://vitaliikaplia.com/
License: GPLv2 or later
Text Domain: acf-secure-file-field
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Activation hook to set default options
register_activation_hook( __FILE__, 'asff_activate' );
function asff_activate() {
    if ( false === get_option( 'asff_options' ) ) {
        add_option( 'asff_options', array(
            'access_level' => 'all',
            'allowed_roles' => array(),
            'upload_dir' => 'secure-uploads',
        ) );
    }
}


define( 'ACF_SECURE_FILE_FIELD_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACF_SECURE_FILE_FIELD_URL', plugin_dir_url( __FILE__ ) );

// Check dependency
add_action( 'admin_notices', 'asff_check_acf_dependency' );
function asff_check_acf_dependency() {
    if ( ! class_exists( 'ACF' ) && ! function_exists( 'acf' ) ) {
        if ( current_user_can( 'activate_plugins' ) ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <?php
                    printf(
                    /* translators: %s: Plugin name */
                            esc_html__( '%s requires Advanced Custom Fields (ACF) to be installed and active.', 'acf-secure-file-field' ),
                            '<strong>ACF Secure File Field</strong>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }
    }
}

// Add Settings link on plugin page
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'asff_add_settings_link' );
function asff_add_settings_link( $links ) {
    $settings_link = '<a href="edit.php?post_type=secure-file&page=asff-settings">' . __( 'Settings', 'acf-secure-file-field' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// 1. Register Custom Post Type for File Management
add_action( 'init', 'asff_register_cpt' );
function asff_register_cpt() {
    register_post_type( 'secure-file', array(
        'labels' => array(
            'name' => __( 'Secured Files', 'acf-secure-file-field' ),
            'singular_name' => __( 'Secured File', 'acf-secure-file-field' ),
            'menu_name' => __( 'Secured Files', 'acf-secure-file-field' )
        ),
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-lock',
        'menu_position' => 99,
        'supports' => array( 'title', 'custom-fields' ), // Title will hold original filename
        'capabilities' => array(
            'create_posts' => 'do_not_allow', // Users shouldn't create manually via UI
        ),
        'map_meta_cap' => true,
    ));
}

// 2. Register ACF Field
add_action( 'acf/include_field_types', 'asff_include_field' );
function asff_include_field() {
    include_once ACF_SECURE_FILE_FIELD_PATH . 'includes/class-acf-field-secure-file.php';
}

// 3. AJAX Upload Handler
add_action( 'wp_ajax_asff_upload_file', 'asff_handle_ajax_upload' );
function asff_handle_ajax_upload() {
    check_ajax_referer( 'acf_nonce', 'nonce' );

    if ( ! current_user_can( 'upload_files' ) ) {
        wp_send_json_error( __( 'Permission denied', 'acf-secure-file-field' ) );
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is handled by wp_handle_upload validation mechanisms.
    if ( empty( $_FILES['file'] ) ) {
        wp_send_json_error( __( 'No file uploaded', 'acf-secure-file-field' ) );
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Assigned to variable for processing via wp_handle_upload.
    $file = $_FILES['file'];

    // Define a closure to modify the upload directory temporarily
    $upload_dir_filter = function( $param ) {
        $options = get_option( 'asff_options' );
        $upload_dir_name = isset( $options['upload_dir'] ) ? $options['upload_dir'] : 'secure-uploads';

        $custom_dir = WP_CONTENT_DIR . '/' . $upload_dir_name;
        $param['basedir'] = $custom_dir;
        $param['baseurl'] = content_url() . '/' . $upload_dir_name; // Note: access denied via .htaccess, but URL struct remains

        if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
            $time = current_time( 'mysql' );
            $y = substr( $time, 0, 4 );
            $m = substr( $time, 5, 2 );
            $param['path'] = $custom_dir . "/$y/$m";
            $param['url']  = $param['baseurl'] . "/$y/$m";
        } else {
            $param['path'] = $custom_dir;
            $param['url']  = $param['baseurl'];
        }

        return $param;
    };

    // Apply filter
    add_filter( 'upload_dir', $upload_dir_filter );

    // Use wp_handle_upload instead of move_uploaded_file
    $upload_overrides = array( 'test_form' => false );

    // Generate secure name
    $original_name = sanitize_file_name( $file['name'] );
    $renamed_filename = wp_generate_password( 32, false ) . '.file';

    // Hook into wp_unique_filename to force our secure name
    $filename_filter = function( $dir, $name, $ext ) use ( $renamed_filename ) {
        return $renamed_filename;
    };
    add_filter( 'wp_unique_filename', $filename_filter, 10, 3 );

    $movefile = wp_handle_upload( $file, $upload_overrides );

    // Remove filters immediately
    remove_filter( 'upload_dir', $upload_dir_filter );
    remove_filter( 'wp_unique_filename', $filename_filter );

    if ( $movefile && ! isset( $movefile['error'] ) ) {

        // Secure directory protection (check if .htaccess exists)
        $target_dir = dirname( $movefile['file'] );
        $base_secure_dir = WP_CONTENT_DIR . '/' . ( isset( $options['upload_dir'] ) ? $options['upload_dir'] : 'secure-uploads' );

        if ( ! file_exists( $base_secure_dir . '/.htaccess' ) ) {
            // Create .htaccess and index.php silently
            @file_put_contents( $base_secure_dir . '/.htaccess', "Order Deny,Allow\nDeny from all" );
            @file_put_contents( $base_secure_dir . '/index.php', "<?php // Silence is golden." );
        }

        // Create CPT Record
        $hash = wp_generate_password( 64, false );
        $post_id = wp_insert_post( array(
                'post_title'  => $original_name,
                'post_type'   => 'secure-file',
                'post_status' => 'private',
        ));

        if ( $post_id ) {
            update_post_meta( $post_id, 'asff_original_name', $original_name );
            update_post_meta( $post_id, 'asff_file_path', $movefile['file'] );
            update_post_meta( $post_id, 'asff_renamed_name', basename( $movefile['file'] ) );
            update_post_meta( $post_id, 'asff_mime_type', $movefile['type'] );
            update_post_meta( $post_id, 'asff_download_hash', $hash );

            wp_send_json_success( array(
                    'id' => $post_id,
                    'name' => $original_name,
                    'hash' => $hash,
                    'url' => home_url( '/?secure-file-download=' . $hash )
            ));
        } else {
            wp_delete_file( $movefile['file'] ); // Use wp_delete_file
            wp_send_json_error( __( 'Failed to create database record', 'acf-secure-file-field' ) );
        }
    } else {
        wp_send_json_error( $movefile['error'] );
    }
}

// 4. Download Handler
add_action( 'template_redirect', 'asff_handle_download_link' );
function asff_handle_download_link() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verification is done via the unique hash token, not a nonce, to allow public/direct downloads.
    if ( empty( $_GET['secure-file-download'] ) ) {
        return;
    }

    // Access Control Check
    $options = get_option( 'asff_options', array( 'access_level' => 'all', 'allowed_roles' => array() ) );
    $access_level = $options['access_level'];
    $allowed_roles = (array) $options['allowed_roles'];
    $user_can_download = false;

    if ( $access_level === 'all' ) {
        $user_can_download = true;
    } elseif ( $access_level === 'user' ) {
        if ( is_user_logged_in() ) {
            $user_can_download = true;
        }
    } elseif ( $access_level === 'role' ) {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            $user_roles = (array) $user->roles;
            if ( ! empty( array_intersect( $user_roles, $allowed_roles ) ) ) {
                $user_can_download = true;
            }
        }
    }

    if ( ! $user_can_download ) {
        wp_die( esc_html__( 'You do not have permission to download this file.', 'acf-secure-file-field' ), esc_html__( 'Access Denied', 'acf-secure-file-field' ), array( 'response' => 403 ) );
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Already ignored above.
    $hash = sanitize_text_field( wp_unslash( $_GET['secure-file-download'] ) );

    $args = array(
            'post_type'  => 'secure-file',
            'meta_key'   => 'asff_download_hash',
            'meta_value' => $hash,
            'posts_per_page' => 1,
            'post_status' => array('private', 'publish'),
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Meta query is essential for looking up files by hash.
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        $query->the_post();
        $post_id = get_the_ID();

        $file_path = get_post_meta( $post_id, 'asff_file_path', true );
        $original_name = get_post_meta( $post_id, 'asff_original_name', true );
        $mime_type = get_post_meta( $post_id, 'asff_mime_type', true );

        if ( file_exists( $file_path ) ) {
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="' . basename($original_name) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));

            if (ob_get_level()) {
                ob_end_clean();
            }

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Using readfile for memory-efficient output.
            readfile($file_path);
            exit;
        } else {
            wp_die( esc_html__( 'File not found on server.', 'acf-secure-file-field' ), esc_html__( 'Error', 'acf-secure-file-field' ), array('response' => 404) );
        }
    } else {
        wp_die( esc_html__( 'Invalid download link.', 'acf-secure-file-field' ), esc_html__( 'Error', 'acf-secure-file-field' ), array('response' => 403) );
    }
}

// 5. Settings Page
add_action( 'admin_notices', 'asff_admin_notices' );
function asff_admin_notices() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking a UI flag, no data processing.
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'asff-settings' ) {
        return;
    }

    // Fix: Properly verify, unslash and sanitize the GET parameter
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Standard WP redirect flag check.
    if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) {
        ?>
        <div id="message" class="updated notice is-dismissible">
            <p><strong><?php esc_html_e( 'Settings saved.', 'acf-secure-file-field' ); ?></strong></p>
        </div>
        <?php
    }
}

add_action( 'admin_menu', 'asff_register_settings_page' );
function asff_register_settings_page() {
    add_submenu_page(
        'edit.php?post_type=secure-file',
        __( 'Settings', 'acf-secure-file-field' ),
        __( 'Settings', 'acf-secure-file-field' ),
        'manage_options',
        'asff-settings',
        'asff_render_settings_page'
    );
}

function asff_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'asff_settings' );
            do_settings_sections( 'asff_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action( 'admin_init', 'asff_register_settings' );
function asff_register_settings() {
    register_setting( 'asff_settings', 'asff_options', 'asff_options_sanitize' );

    add_settings_section(
        'asff_section_access',
        __( 'Download Access Control', 'acf-secure-file-field' ),
        'asff_section_access_callback',
        'asff_settings'
    );

    add_settings_field(
        'asff_field_access_level',
        __( 'Who can download files?', 'acf-secure-file-field' ),
        'asff_field_access_level_callback',
        'asff_settings',
        'asff_section_access'
    );

    add_settings_field(
        'asff_field_allowed_roles',
        __( 'Allowed Roles', 'acf-secure-file-field' ),
        'asff_field_allowed_roles_callback',
        'asff_settings',
        'asff_section_access'
    );

    add_settings_section(
        'asff_section_general',
        __( 'General Settings', 'acf-secure-file-field' ),
        'asff_section_general_callback',
        'asff_settings'
    );

    add_settings_field(
        'asff_field_upload_dir',
        __( 'Upload Directory Name', 'acf-secure-file-field' ),
        'asff_field_upload_dir_callback',
        'asff_settings',
        'asff_section_general'
    );
}

function asff_section_general_callback() {
    echo '<p>' . esc_html__( 'General plugin settings.', 'acf-secure-file-field' ) . '</p>';
}

function asff_field_upload_dir_callback() {
    $options = get_option( 'asff_options', array( 'upload_dir' => 'secure-uploads' ) );
    $upload_dir = $options['upload_dir'];
    $has_files = asff_has_secure_files();
    ?>
    <input type="text" name="asff_options[upload_dir]" value="<?php echo esc_attr( $upload_dir ); ?>" <?php disabled( $has_files, true ); ?>>
    <?php if ( $has_files ) : ?>
        <p class="description">
            <?php esc_html_e( 'The directory name cannot be changed because secure files have already been uploaded.', 'acf-secure-file-field' ); ?>
        </p>
    <?php else: ?>
        <p class="description">
            <?php esc_html_e( 'The name of the folder inside wp-content where files will be stored. Use lowercase letters, numbers, and hyphens only.', 'acf-secure-file-field' ); ?>
        </p>
    <?php endif;
}


function asff_section_access_callback() {
    echo '<p>' . esc_html__( 'Control who is allowed to download the secure files.', 'acf-secure-file-field' ) . '</p>';
}

function asff_field_access_level_callback() {
    $options = get_option( 'asff_options', array( 'access_level' => 'all' ) );
    $access_level = $options['access_level'];
    ?>
    <select name="asff_options[access_level]" id="asff_field_access_level">
        <option value="all" <?php selected( $access_level, 'all' ); ?>><?php esc_html_e( 'Anyone with the link', 'acf-secure-file-field' ); ?></option>
        <option value="user" <?php selected( $access_level, 'user' ); ?>><?php esc_html_e( 'Any logged-in user', 'acf-secure-file-field' ); ?></option>
        <option value="role" <?php selected( $access_level, 'role' ); ?>><?php esc_html_e( 'Users with specific roles', 'acf-secure-file-field' ); ?></option>
    </select>
    <?php
}

function asff_field_allowed_roles_callback() {
    $options = get_option( 'asff_options', array( 'allowed_roles' => array() ) );
    $allowed_roles = (array) $options['allowed_roles'];
    $editable_roles = get_editable_roles();

    echo '<fieldset>';
    foreach ( $editable_roles as $role => $details ) {
        // Fix: Use checked() function which handles escaping output
        echo '<label>';
        echo '<input type="checkbox" name="asff_options[allowed_roles][]" value="' . esc_attr( $role ) . '" ' . checked( in_array( $role, $allowed_roles ), true, false ) . '> ';
        echo esc_html( translate_user_role( $details['name'] ) );
        echo '</label><br>';
    }
    echo '</fieldset>';
    echo '<p class="description">' . esc_html__( 'This is only active when "Users with specific roles" is selected above.', 'acf-secure-file-field' ) . '</p>';
}

function asff_options_sanitize( $input ) {
    $current_options = get_option( 'asff_options' );
    $new_input = array();

    // Sanitize access level
    if ( isset( $input['access_level'] ) && in_array( $input['access_level'], array( 'all', 'user', 'role' ) ) ) {
        $new_input['access_level'] = $input['access_level'];
    } else {
        $new_input['access_level'] = $current_options['access_level'];
    }

    // Sanitize allowed roles
    if ( isset( $input['allowed_roles'] ) && is_array( $input['allowed_roles'] ) ) {
        $new_input['allowed_roles'] = array_map( 'sanitize_text_field', $input['allowed_roles'] );
    } else {
        $new_input['allowed_roles'] = array();
    }

    // Sanitize and handle upload directory
    $has_files = asff_has_secure_files();
    if ( $has_files ) {
        $new_input['upload_dir'] = $current_options['upload_dir'];
    } else {
        $old_dir_name = $current_options['upload_dir'];
        $new_dir_name = isset( $input['upload_dir'] ) ? sanitize_file_name( $input['upload_dir'] ) : $old_dir_name;

        if ( empty($new_dir_name) ) {
            $new_dir_name = 'secure-uploads';
        }

        if ( $old_dir_name !== $new_dir_name ) {
            $old_dir_path = WP_CONTENT_DIR . '/' . $old_dir_name;
            $new_dir_path = WP_CONTENT_DIR . '/' . $new_dir_name;

            // Check if old directory exists before renaming
            if ( is_dir( $old_dir_path ) && ! is_dir( $new_dir_path ) ) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Using PHP rename for local directory change during settings save to avoid WP_Filesystem credential request complexity.
                rename( $old_dir_path, $new_dir_path );
            }
        }
        $new_input['upload_dir'] = $new_dir_name;
    }

    return $new_input;
}

// Helper function to check if any secure files exist
function asff_has_secure_files() {
    $count = wp_count_posts( 'secure-file' );
    $total = 0;
    foreach ( (array) $count as $status => $amount ) {
        // We only care about statuses that mean a file is in use.
        if ( in_array( $status, array( 'publish', 'private', 'draft', 'pending', 'future' ) ) ) {
            $total += $amount;
        }
    }
    return $total > 0;
}

// 6. Admin Table Customizations for 'secure-file' CPT

// Delete file from server when post is deleted
add_action( 'before_delete_post', 'asff_delete_file_on_post_delete' );
function asff_delete_file_on_post_delete( $post_id ) {
    if ( get_post_type( $post_id ) === 'secure-file' ) {
        $file_path = get_post_meta( $post_id, 'asff_file_path', true );
        if ( $file_path && file_exists( $file_path ) ) {
            wp_delete_file( $file_path );
        }
    }
}

// Add custom columns to the post list
add_filter( 'manage_secure-file_posts_columns', 'asff_set_custom_edit_columns' );
function asff_set_custom_edit_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['file_type'] = __( 'File Type', 'acf-secure-file-field' );
    $new_columns['file_size'] = __( 'File Size', 'acf-secure-file-field' );
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}

// Populate the custom columns
add_action( 'manage_secure-file_posts_custom_column', 'asff_custom_column_content', 10, 2 );
function asff_custom_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'file_type':
            echo esc_html( get_post_meta( $post_id, 'asff_mime_type', true ) );
            break;
        case 'file_size':
            $file_path = get_post_meta( $post_id, 'asff_file_path', true );
            if ( $file_path && file_exists( $file_path ) ) {
                echo esc_html( size_format( filesize( $file_path ), 2 ) );
            } else {
                esc_html_e( 'File not found', 'acf-secure-file-field' );
            }
            break;
    }
}

// Customize the row actions (remove edit, quick edit, etc.)
add_filter( 'post_row_actions', 'asff_remove_row_actions', 10, 2 );
function asff_remove_row_actions( $actions, $post ) {
    if ( $post->post_type === 'secure-file' ) {
        unset( $actions['edit'] );
        unset( $actions['inline hide-if-no-js'] ); // 'Quick Edit'
        unset( $actions['view'] );
    }
    return $actions;
}

// Redirect edit link to download the file
add_action( 'admin_init', 'asff_redirect_edit_to_download' );
function asff_redirect_edit_to_download() {
    global $pagenow;
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This intercepts the standard Edit link. Strict nonce verification is handled by WordPress core before saving any data.
    if ( $pagenow === 'post.php' && isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] === 'edit' ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Ignoring nonce to allow redirect logic.
        $post_id = (int) $_GET['post'];
        if ( get_post_type( $post_id ) === 'secure-file' ) {
            $hash = get_post_meta( $post_id, 'asff_download_hash', true );
            if ( $hash ) {
                wp_safe_redirect( home_url( '/?secure-file-download=' . $hash ) );
                exit;
            } else {
                wp_die( esc_html__( 'This file does not have a valid download link.', 'acf-secure-file-field' ) );
            }
        }
    }
}

// 7. AJAX Endpoint for Media Modal
add_action('wp_ajax_asff_get_secure_files', 'asff_get_secure_files_ajax');
function asff_get_secure_files_ajax() {
    check_ajax_referer('acf_nonce', 'nonce');

    if ( ! current_user_can('upload_files') ) {
        wp_send_json_error(array('message' => __('Permission denied.', 'acf-secure-file-field')));
    }

    $files_query = new WP_Query(array(
        'post_type' => 'secure-file',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => array('private', 'publish'),
    ));

    $files_data = array();
    if ( $files_query->have_posts() ) {
        while ( $files_query->have_posts() ) {
            $files_query->the_post();
            $post_id = get_the_ID();
            $files_data[] = array(
                'id' => $post_id,
                'name' => get_the_title(),
                'hash' => get_post_meta($post_id, 'asff_download_hash', true),
                'url' => home_url('/?secure-file-download=' . get_post_meta($post_id, 'asff_download_hash', true)),
            );
        }
    }
    wp_reset_postdata();

    wp_send_json_success($files_data);
}