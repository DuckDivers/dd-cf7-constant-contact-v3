=== Connect Contact Form 7 to Constant Contact ===
Contributors: thehowarde
Donate link: https://www.howardehrenberg.com
Tags: Contact Form 7, constant contact, cf7, ctct, email marketing
Requires at least: 4.8
Tested up to: 5.2
Requires PHP: 7.0
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


This will connect Contact form 7 and Constant Contact using the Constant Contact API V3. Requires an API Key and Secret for functionality to work.  Allows use of checkbox, all lists, and updates existing records.

== Description ==

This is an advanced Constant Contact to Contact Form 7 Connector. This plug-in will allow you to make a connection to Constant Contact's API using OAUTH protocol.  Retrieve all of your contact lists, and allow users to sign up for a single list, or multiple lists.  This will update exsting contacts in your Constant Contact list, or add new if they don't exist.  Allows you to push basic contact fields, including 

*   First Name
*   Last Name
*   Birthday
*   Company Name


There will be a pro version available that can connect with any available Constant Contact field, including custom fields that you've defined in your Constant Contact account.

== Installation ==

1. Upload `dd-cf7-constant-contact-v3.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[ctct]` Form tag in your Contact Form 7 - Contact Form.

== Frequently Asked Questions ==

= How do I get a Constant Contact API Access Key and Secret? =

To get started, go to [Constant Contact Developer](https://app.constantcontact.com/pages/dma/portal/)

= The form isn't sending the data to Constant Contact =

If you are connected properly, which it will show on the settings page.  Then you must make sure you map your fields and tell the plugin if you are using the form tags or not.  See Screenshot #2 for the settings tab.

== Screenshots ==

1. Admin View of Constant Contact settings page.
2. CF7 Settings Tab for Contact Form
	1. Choose the list or lists you want to assign contacts to.
	2. This checkbox tells the plugin if you're using the shortcode from the form or whether you're using an automatic opt-in without a checkbox.
	3. ** You must map the fields to Constant Contact **

== Changelog ==

= 1.0 =
* Initial Release

 == Upgrade Notice ==
 = None yet =