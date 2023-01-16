=== BuddyForms Geo My WP ===

Contributors: themekraft, svenl77, gfirem
Tags: WooCommerce, BuddyPress, Geo My WP, BuddyForms
Requires at least: 4.0
Tested up to: 6.1.1
Stable tag: 1.2.2
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
= 1.2.2 - 15 Jan 2023 =
* Updated duration of trial version.
* Tested up to WordPress 6.1.1

= 1.2.1 - 05 Oct 2022 =
* Added auto activation feature when using bundle license.
* Code refactoring.

= 1.2.0 - 03 Sep 2022 =
* Fixed issue with field display.
* Fixed issue with plugin actiivation.
* Fixed security issue.
* Tested up to WordPress 6.0.2

= 1.1.18 - 12 May 2020 =
* Fixed the field validation to avoid run when it is disabled.

= 1.1.17 - 8 May 2020 =
* Fixed the location function to save the metas to geo my wp.
* Improved the location field metas to include the default meta for the email if exist a buddyforms form element email or user_email.
* Added a hook to force to buddyforms to output all form element with no html.

= 1.1.16 - 24 April 2020 =
* Fixed the required validation.

= 1.1.15 - 24 April 2020 =
* Updated the JS assets.

= 1.1.14 - 24 April 2020 =
* Removing unnecessary dependencies.
* Fixed the required validation.
* Added the functionality to be compatible with the GeoMyWp location meta, now is possible to integrate the form with the native filters.
* Added support to show the Form Element labels for the search location meta.

= 1.1.13 - 23 Mar 2020 =
* Fixed the wordpress compatibility.
* Fixed missing assets.

= 1.1.12 - 23 Mar 2020 =
* Fixed the save functionality for the Address for the user profile.
* Fixed the required validation.
* Improved the compatibility with BuddyForms labels and required signal.

= 1.1.11 - 16 Mar 2020 =
* Fixed the save functionality for the Address for the user profile.

= 1.1.10 - 16 Mar 2020 =
* Fixed the not saving behavior for a single Address field.

= 1.1.9 = 9 Mar 2020
* Added the location icon to get the user location from the navigator.
* Implemented the user auto-location of the user on form load.
* Added a Clean icon to clean the form element.
* Added the options to enabled/disabled the clean icon, the location icon and the function to auto-fill the user location on load.
* Updated the localization field. * Added compatibility with Loco Translate plugin.

= 1.1.8 = 11 Feb 2020
* Improved the compatibility with BuddyForms 2.5.^.
* Fixed the metabox in the administration when the Post was edit.
* Fixed the remove button when the field was used as multiple.
* Improved the compatibility with gutenberg.
* Improved compatibility with Geo My WP 3.4.
* Fixed the option to overrride the Map icon from the shortcode.

= 1.1.7 = 2 Dic 2019
* Improved compatibility with WP 5.3
* Improved compatibility with BF 2.5.9
* Fixed the save issue.

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