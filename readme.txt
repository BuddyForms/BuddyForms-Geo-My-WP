=== BuddyForms Geo My WP ===

Contributors: themekraft, svenl77, gfirem
Tags: WooCommerce, BuddyPress, Geo My WP, BuddyForms
Requires at least: 4.0
Tested up to: 5.2
Stable tag: 1.1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shop solution for your BuddyPress community. Integrates a WooCommerce installation with a BuddyPress social network.

== Description ==

With BuddyForms Geo my WP you can geolocate any Post Type and your site members.

---

> **Powered with ❤ by [ThemeKraft](https://themekraft.com)**

---

#### Tags
Tags: WooCommerce, BuddyPress, Shop, eCommerce, social networking, social shopping, customer, customer relation, achievements, support, product, vendor, marketplace, groups, support groups, profile, my account, my-account


== Installation ==
Upload the entire folder to the /wp-content/plugins/ directory or install the plugin through the WordPress plugins screen directly.
Activate the plugin through the 'Plugins' menu in WordPress.


== Changelog ==
= 1.1.6 = 7 Oct 2019
* Fixed the validation, because it was avoiding to submit the form.
* Fixed the save issue when the element was single.
* Fixed the store address for a registration form.
* Fixed the way the plugin is loading the user id from the entry.

= 1.1.5 = Jun 11 2019
* Fixed the validation, because it was avoiding to submit the form.

= 1.1.4 = Jun 11 2019
* Fix the function to store the user related meta.

= 1.1.3 = Jun 10 2019
* Removing the limit for the queries to bring the result from the forms.
* Improved the check for BuddyForms.
* Fixed issue to avoid save the location in the post meta.
* Improved the user support for the location.
* Fixed the address field when is showed in the entry detail of the registration or contact form.
* Improved the styles.
* Fixed the option to generate the title and content for a post to not take precedense over the submitted value if the field is not hidden.


= 1.1.2 = May 17 2019
* Fixed the assets issue.

= 1.1.0 =
* Adding the new shortcodes to display entries from registration and content forms
* Fixed the way the plugin grab the form slug from the query args when is showing the form trough the form preview
* Implemented the logic to show multiples form in one shortcode and differentiate from registration and content form using different icons
* Added a validation just in case google js is not detected
* Fixed the field name
* Added the custom post type to be used in the shortcodes
* Updated the script to add the assets to take in count if the form is embebed into bp edit profile and custom tab
* Fixed the script to load the address from meta
* Code improved
* Improved the way the script is getting the location
* Added the shortcode tag to show only the information belong to the user
* Added a new shortcode parameter to filter by user_id
* Added filters to extend the info windows inside the map
* Added a validation to avoid run jquery validation without it
* Fixed the remove link to avoid be included in the first field
* Improved the validation to include the assets if the field is used in the form
* Fixed the user id to bring from the hook instead of the current user directly. With this way the user meta is inserted on user registration
* Added a hook to include the assets before buddyforms
* Added a filter to avoid the field validation
* Added a filter to add fields slug
* Added 2 helper functions to grab the user/post meta location
* Improved the helper functions to grab the user/post meta location
* Added the map argument as parameter for the filters `bf_geo_my_wp_locations_for_registration_query_args`, `bf_geo_my_wp_locations_query_args_fields_slug`, `bf_geo_my_wp_locations_for_content_query_args` and `bf_geo_my_wp_locations_for_registration_query_args`
* Adding a filter to override the location to each post before is assign to the instance. Filter name `bf_geo_my_wp_locations_for_registration`
* Added a validation to not process empty query args as a measure to clean the map in some scenarios
* Fixed the limitation to use only one field into a form
* Now the field is possible to be used in the backend as in the frontend
* Fixing the delete button to appear all the time, to give de possibility to remove the current field
* Enabling the field to be used multiples times in the same form

= 1.0.1 =
* Fix the assets to be loaded only if the field exist in the form.

= 1.0.0 =
* Rock it!