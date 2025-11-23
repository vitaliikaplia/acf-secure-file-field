=== ACF Secure File Field ===
Contributors: drop
Tags: acf, secure upload, file field, private files, document management
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a secure file upload field to ACF. Files are stored outside the media library in a protected directory and served via secure, expiring links.

== Description ==

ACF Secure File Field creates a new field type in Advanced Custom Fields (ACF) designed for handling sensitive documents. unlike the standard File field, this plugin **does not** use the WordPress Media Library.

Instead, files are uploaded securely via AJAX to a protected directory (`wp-content/secure-uploads/`) blocked from direct public access via `.htaccess`.

**Key Features:**

* **Secure Storage:** Files are stored outside the public uploads structure.
* **Direct Access Blocked:** The upload folder is protected against direct HTTP access.
* **Obfuscated Filenames:** Files are renamed on the server to prevent guessing.
* **Secure Download Links:** Downloads are handled via a PHP proxy using hashed, temporary links (e.g., `?secure-file-download=hash`).
* **Access Control:** Define who can download files (Everyone, Logged-in Users, or Specific Roles) via settings.
* **Seamless ACF Integration:** Works just like a native ACF field.

**How it works:**
1.  Add the "Secure File" field to your Field Group.
2.  Users upload files on the frontend or backend.
3.  Files are saved in `wp-content/secure-uploads/YYYY/MM/`.
4.  The plugin generates a secure hash for downloading.

== Installation ==

1.  Upload the plugin files to the `/wp-content/plugins/acf-secure-file-field` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Ensure you have **Advanced Custom Fields** installed and active.
4.  Create a new Field Group and select the **Secure File** field type.

== Frequently Asked Questions ==

= Where are the files stored? =
By default, they are stored in `wp-content/secure-uploads/`. You can see this directory in your server's file manager, but you cannot access files inside it directly via a browser URL.

= Can I change the upload directory? =
Yes, you can change the directory name in the plugin settings *before* you upload your first file. Once files are uploaded, the directory setting locks to prevent broken links.

= Does this require ACF Pro? =
No, it works with both the free version of ACF and ACF Pro.

== Screenshots ==

1. Field view in the post editor (Upload UI).
2. Field view with a file selected (Preview UI).
3. Plugin Settings page for Access Control.

== Changelog ==

= 1.0 =
* Initial release.