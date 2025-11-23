# ACF Secure File Field

**Contributors:** [@vitaliikaplia](https://profiles.wordpress.org/vitaliikaplia/)  
**Tags:** acf, advanced custom fields, file, upload, secure, security, media modal  
**Requires at least:** 5.0  
**Tested up to:** 6.8
**Stable tag:** 1.0.0
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A custom ACF field that provides a secure way to upload files, storing them outside the default WordPress Media Library with an enhanced UI and robust access control.

## Description

The **ACF Secure File Field** plugin adds a new field type to Advanced Custom Fields, "Secure File". This field allows for file uploads that are stored in a protected directory on your server, separate from the standard `wp-content/uploads` folder. It features a modal-based interface for a smooth workflow, allowing you to either upload new files or select from previously uploaded secure files.

This is ideal for situations where you need to manage sensitive documents, client files, or any other asset that should not be publicly accessible through predictable URLs. The plugin generates a secure, unique download link for each file, with granular control over who can access it.

### Key Features

*   **Secure Storage:** Files are uploaded to a protected directory (default: `wp-content/secure-uploads/`) with an `.htaccess` file to prevent direct access.
*   **Intuitive Modal UI:** A clean, modal-based interface to either upload a new file (with drag-and-drop support) or select from a library of existing secure files.
*   **Unique Download Links:** Access files via a unique, non-guessable hash-based URL.
*   **Granular Access Control:** Restrict file downloads to:
    *   Anyone with the link.
    *   Any logged-in WordPress user.
    *   Users with specific roles.
*   **Independent of Media Library:** Keeps your secure files completely separate from your public media assets.
*   **Custom Post Type Management:** Uploaded files are managed as a `secure-file` custom post type, with a customized admin view for better management (direct downloads, synchronized file deletion).
*   **Customizable Upload Directory:** Change the name of the secure storage folder from the settings page.

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
2.  **Using the Field:**
    *   Go to any post or page where your field group is active.
    *   Click the "Select File" button. A modal window will appear.
    *   **To upload a new file:** Stay on the "Upload New" tab. Drag and drop a file or click to use the file browser. The file will be uploaded and automatically selected.
    *   **To use an existing file:** Click the "Select Existing" tab. A list of previously uploaded secure files will appear. Click the "Select" button next to the desired file.
3.  **Manage Files:**
    *   Once a file is selected, the field will display its name with "Download" and "Remove" buttons.
    *   All uploaded files can be viewed in the WordPress admin under the "Secured Files" menu.

## Settings

To configure the plugin, navigate to **Secured Files > Settings** in your WordPress admin dashboard. A "Settings" link is also available on the main Plugins page for quick access.

*   **Download Access Control:**
    *   **Who can download files?:** Choose between "Anyone with the link," "Any logged-in user," or "Users with specific roles."
*   **General Settings:**
    *   **Upload Directory Name:** Set a custom name for the secure storage folder. **Important:** This setting can only be changed before any files have been uploaded. It becomes read-only to protect existing file paths.

## Template Usage Example

Here is a basic example of how to display the secure download link in your theme's template files.

```php
<?php
// The field returns a File Array by default
$secure_file = get_field('your_field_name');

if ( $secure_file && is_array($secure_file) ) : ?>
    <h3>Download Our Secure Document</h3>
    <p>
        <a href="<?php echo esc_url( $secure_file['url'] ); ?>" class="button" download>
            Download <?php echo esc_html( $secure_file['name'] ); ?>
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