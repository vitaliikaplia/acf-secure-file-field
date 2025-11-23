# ACF Secure File Field

**Contributors:** [@vitaliikaplia](https://profiles.wordpress.org/drop/)  
**Tags:** acf, advanced custom fields, file, upload, secure, security  
**Requires at least:** 5.0  
**Tested up to:** 6.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A custom ACF field that provides a secure way to upload files, storing them outside the default WordPress Media Library for enhanced access control.

## Description

The **ACF Secure File Field** plugin adds a new field type to Advanced Custom Fields, "Secure File". This field allows for file uploads that are stored in a protected directory on your server, separate from the standard `wp-content/uploads` folder used by the WordPress Media Library.

This is ideal for situations where you need to manage sensitive documents, client files, or any other asset that should not be publicly accessible through predictable URLs. The plugin generates a secure, unique download link for each file, with granular control over who can access it.

### Key Features

*   **Secure Storage:** Files are uploaded to a protected directory (default: `wp-content/secure-uploads/`) with an `.htaccess` file to prevent direct access.
*   **Unique Download Links:** Access files via a unique, non-guessable hash-based URL.
*   **Granular Access Control:** Restrict file downloads to:
    *   Anyone with the link.
    *   Any logged-in WordPress user.
    *   Users with specific roles.
*   **Independent of Media Library:** Keeps your secure files completely separate from your public media assets.
*   **Custom Post Type Management:** Uploaded files are managed as a `secure-file` custom post type, allowing for easy administration.
*   **Clean & Intuitive UI:** A simple drag-and-drop or file select interface for a smooth user experience.

## Installation

1.  **Install the Plugin:**
    *   Upload the `acf-secure-file-field` folder to the `/wp-content/plugins/` directory.
    *   Alternatively, install directly from the WordPress plugin repository.
2.  **Activate the Plugin:** Activate the plugin through the 'Plugins' menu in WordPress.
3.  **ACF Requirement:** Ensure you have Advanced Custom Fields (Free or Pro) version 5.0 or higher installed and activated.

## How to Use

1.  **Add the Field:**
    *   In your ACF Field Group, click "Add Field".
    *   Set the "Field Label" (e.g., "Secure Document").
    *   Under "Field Type", select "Content" > "Secure File".
2.  **Configure Return Format (Optional):**
    *   You can choose between two return formats in the field's settings:
        *   **File Array (default):** Returns an array containing the file's post ID, original name, and secure download URL.
        *   **File ID:** Returns only the post ID of the secure file CPT entry.
3.  **Upload a File:**
    *   Go to any post, page, or custom post type where your field group is active.
    *   You will see the Secure File field's upload interface.
    *   Click to select a file or drag and drop it onto the field. The file will be uploaded via AJAX.
4.  **Manage Files:**
    *   Once uploaded, the field will display the file's name with a "Download" and "Remove" button.
    *   All uploaded files can be viewed in the WordPress admin under the "Secured Files" menu.

## Access Control Settings

To configure who can download the secure files, navigate to **Secured Files > Settings** in your WordPress admin dashboard.

*   **Who can download files?:**
    *   **Anyone with the link:** Public access for anyone who has the unique URL.
    *   **Any logged-in user:** The user must be logged into WordPress to initiate the download.
    *   **Users with specific roles:** A checklist of user roles will appear, allowing you to grant access only to selected roles.

## Template Usage Example

Here is a basic example of how to display the secure download link in your theme's template files (e.g., `single.php`).

```php
<?php
// Assuming the field is set to return a 'File Array'
$secure_file = get_field('secure_document');

if ( $secure_file ) : ?>
    <h3>Download Our Secure Document</h3>
    <p>
        <a href="<?php echo esc_url( $secure_file['url'] ); ?>" class="button">
            Download <?php echo esc_html( $secure_file['name'] ); ?>
        </a>
    </p>
<?php endif; ?>

```

If your field is set to return "File ID", you would need an extra step to get the download URL:

```php
<?php
// Assuming the field is set to return a 'File ID'
$secure_file_id = get_field('secure_document');

if ( $secure_file_id ) :
    $file_hash = get_post_meta( $secure_file_id, 'asff_download_hash', true );
    $file_name = get_post_meta( $secure_file_id, 'asff_original_name', true );
    $download_url = home_url( '/?secure-file-download=' . $file_hash );
    ?>
    <h3>Download Our Secure Document</h3>
    <p>
        <a href="<?php echo esc_url( $download_url ); ?>" class="button">
            Download <?php echo esc_html( $file_name ); ?>
        </a>
    </p>
<?php endif; ?>

```

## Frequently Asked Questions

**Where are the files stored?**
By default, files are stored in `/wp-content/secure-uploads/`. This path is protected by an `.htaccess` file that prevents direct public access. You can change the directory name from the settings page before you upload any files.

**Is this plugin compatible with ACF Pro?**
Yes, it is fully compatible with both the free and Pro versions of Advanced Custom Fields.

## License

This plugin is licensed under the GPLv2 or later. See the [LICENSE URI](https://www.gnu.org/licenses/gpl-2.0.html) for more information. This makes it fully compatible with the WordPress license.
