=== SiteOrigin Widgets Bundle ===
Tags: bundle, widget, button, slider, image, carousel, price table, google maps, tinymce, social links
Requires at least: 3.9
Tested up to: 4.5.3
Stable tag: trunk
Build time: unbuilt
License: GPLv3 or later
Contributors: gpriday, braam-genis

== Description ==

[vimeo https://vimeo.com/102103379]

Widgets are great. No matter where you’re using them. In a [Page Builder](http://siteorigin.com/page-builder/) page or on your widgetized areas.

The SiteOrigin widget bundle gives you a collection of widgets that you can use and customize. All the widgets are built on our powerful framework, giving you advanced forms, unlimited colours and 1500+ icons.

The collection is growing, but here’s what we have so far.

* Google Maps Widget that's going places.
* Button Widget that you’ll love to click.
* Image Widget that’ll let you add images everywhere.
* Call To Action Widget that’ll get your users performing the actions you want.
* Slider Widget that slides images and HTML5 videos.
* Price Table Widget that’ll help you sell more.
* Post Carousel Widget that displays your posts as a carousel.
* Features Widget that lets you display a set of site or service features.
* Video Widget to get your videos out there.
* Headline Widget to get you noticed.
* Social Links Widget to show you're active.

Once you enable a widget, you'll be able to use it anywhere standard widgets are used. You can manage your widgets by going to Plugins > SiteOrigin Widgets in your WordPress admin.

== Documentation ==

[Documentation](https://siteorigin.com/css/getting-started/) is available on SiteOrigin.

== Support ==

We offer free support on the [SiteOrigin support forums](https://siteorigin.com/thread/).

= Create Custom Widgets =

The SiteOrigin Widgets Bundle is the perfect platform to build widgets for your theme or plugin. Read more on our [developer docs](https://siteorigin.com/docs/widgets-bundle/).

== Screenshots ==

1. Manage which widgets you want enabled or disabled.
2. The button widget shows the clean, standard interface all the widgets use.
3. An example of the button widget.

== Changelog ==

= 1.6.4 - 21 July 2016 =
* More settings and customizability for Headline widget.
* Added FitText to Headline and Hero Image widgets.
* Fixed Pixabay image importing.

= 1.6.3 - 19 July 2016 =
* Added image search functionality to media field.
* Moved actions into their own file.
* Allow widgets to provide their own LESS/HTML.
* Added very simple code field.
* Multiple widgets can have the same class. Allowing widget functionality to come from configuration.
* Various tweaks for upcoming Widgets Builder plugin.

= 1.6.2 - 11 July 2016 =
* Fixed Firefox issue in post selector builder.
* Properly escape all uses off add_query_arg.
* Added filter after video

= 1.6.1 - 24 June 2016 =
* Social Links: Fixed auto-filling of network colors.
* Social Links: Added 500px network.
* Social Links: Added title tag to link tags.
* Maps: Prevent JS error in when maps widget script is enqueued but widget isn't displayed.
* Maps: Made API field more prominent as it's now required by the Google Maps API.
* Added more general error checking.

= 1.6 - 21 June 2016 =
* Added builder field.
* Added new multi checkboxes field.
* Added Layout Slider widget.
* Added taxonomy widget.
* Added slider wrapper attributes and filter.
* Fix for measurement field inside a repeater.
* Modified base folder to work independently of Widgets Bundle.
* Added custom icon families callback argument to icon field.
* Properly handle attachments in post selector
* Contact Form: Refactored form fields.
* Hero Image: Added image type to Hero Image widget.
* Button: Handle empty width.
* Image: Added filter for SiteOrigin image attributes.
* Image: Add dimensions to sizes dropdown.
* Maps: Ensure maps widget works with API key.
* Hero Image: Added setting to disable swipe on mobile Hero Image Widget.
* Fixed title syntax in Image widget.
* Video: Correctly get video file mime-types.
* Video Widget: Allow specifying multiple self-hosted video sources to support various formats.

= 1.5.11 - April 11 2016 =
* Fixed features widget container shape setting.

= 1.5.10 - April 5 2016 =
* Added Icon widget.
* Moved widget form arrays into separate functions to improve performance.
* Cache widget style CSS if it can't be saved to filesystem.
* Improved preview checking so preview style CSS isn't stored.
* Contact Form: Improved instance hashing for compatibility with Yoast SEO.
* Contact Form: Added description field and customisation.
* Slider: Ensure correct styles are applied to slider images when a link is defined.
* Features: Allow user to select size for uploaded icon image.
* Price Table: Ensure feature icons always vertically centered, alongside feature text.

= 1.5.9 - February 26 2016 =
* Contact Form: Fixed hash checking for duplicate emails.
* Contact Form: Replace default emails with admin_email.

= 1.5.8 - February 26 2016 =
* Skip empty sidebars when loading widget scripts.
* Changes to cache clearing.
* Typo corrections.
* Fixed conflict with Child Theme Configurator.
* Image Grid widget: Using correct field and image size names to determine image sizes.
* Editor widget: Added shortcode unautop to Editor widget.
* Contact Form widget: Added check to prevent email resends in contact form widget.
* Masonry widget: properly handles full width rows in Page Builder.
* Hero Image widget: Fix backgrounds URL.
* Price Table widget: Skip empty buttons.
* Maps Widget: Allow clicking markers to reopen info windows if closed.

= 1.5.7 - February 4 2016 =
* Restored old class name for Image Grid Widget.

= 1.5.6 - January 23 2016 =
* Fixed widget name migration
* Fixed hero image height issue.
* Fixed admin page layout.

= 1.5.5 - January 21 2016 =
* Changed widget folder names to make them less verbose.
* Properly handle LESS compile errors.
* Fixed regex causing only the first 10 TinyMCE fields to be initialized.
* Fixed sanitization in the contact form.
* Fixed Google webfont function.
* Fixed image output for slider base.
* Image Widget: Added alignment options.
* Contact Form: Use anchor to return to form after submit
* Change default caps to manage options.
* Contact form widget: fixed - form in customizer doesn't resize.
* Price Table: Added image alt tags.
* Editor Widget: Fixed issue where only admins can view unfiltered content.
* Editor Widget: Fixed issue where Editor Widget was removing new lines in code.
* Post Selector Field: Support for date fields.
* Maps Widget: Fixed Lat/Long coordinate handling.
* Masonry widget: fixed layout and sizing.
* Image Widget: Allow display of image title above or below image.
* Added more relative measurement units to base.
* Hero Image Widget: Added height setting.
* Testimonial widget: Prevent outputting related image HTML if no image is set.
* Testimonial Widget: Use testimonial URLs to link location and optionally link names and images.
* Contact Form: Prevent multiple submit button clicks.
* Image Widget: Add support for srcset to Image widget
* Contact Form: Allow user to set field label position.
* Contact Form: Allow user to set field label font styles.
* Contact Form: Allow user setting focussed field outline styles.
* Contact Form: Don't do recaptcha validation in admin preview.

= 1.5.4 - November 18 2015 =
* Fixed compatibility with PHP 5.2

= 1.5.3 - November 17 2015 =
* Fixed defaults for Features widget and Hero Image widget.
* Fixed previewing for Editor widget.
* Change measurement field to work as single string.
* Use new measurement field for existing widgets.
* Carousel widget supports RTL.

= 1.5.2 - November 10 2015 =
* Removed word break style from headline widget.
* Fixed image grid URL field.
* Added more text styling options to features widget.
* Added measurement field to use in various widgets.
* Prefix function name in Google Map widget to prevent conflicts.
* Fixed styling for contact form widget.
* Fix to allow multiple duplicated contact forms on a single page.
* Fixed Hero Image widget button shortcode in text mode.
* TinyMCE fields maintain editor state.
* Added support for WP Canvas Shortcodes in TinyMCE field.
* Don't initialize TinyMCE outside the admin.
* Added more styling to contact form submit button.

= 1.5.1 - October 7 2015 =
* Fixed: Issue with Call To Action widget being missing.

= 1.5 - October 5 2015 =
* Fixed: Conflict between WPML and repeaters.
* Added Simple Masonry Layout widget.
* Added Contact Form widget.
* Added Image Grid widget.
* Added Testimonial widget.
* Changed layout of widgets activation page.
* Added Trianglify to generate placeholder widget icons.
* Added mechanism to use state emitters in repeaters.
* Section expanded/collapsed states now stored across form loads.
* Display once off admin notice when new widgets are available.
* Fixed translation domain.
* Editor Widget: Allow more HTML in Editor widget for trusted users.
* Hero Image Widget: Added top padding setting.
* Hero Image Widget: Can now set background click URL.
* Hero Image Widget: Improved handling of buttons shortcode.
* Slider Widget : Fixed open in new window setting.
* Headline Widget: Added word-break CSS.
* Headline Widget: Added option to set type of heading tags used.

= 1.4.4 - September 6 2015 =
* Fixed issue with slider image widths.

= 1.4.3 - September 5 2015 =
* Added support for WooCommerce Shortcodes plugin to TinyMCE field.
* New streamlined icon selector field.
* Added info window functionality to maps widget.
* Added a button to duplicate repeater items.
* Added more design settings to hero image widget.
* Removed full screen mode from TinyMCE field.
* Option to keep map centered when container is resized.
* Fixed: CSS bug for Google font imports on generated CSS.
* Fixed: Post selector for URL fields properly handles empty titles.
* Added option to skip auto paragraphs in Editor widget.

= 1.4.2 - August 18 2015 =
* Urgent fix in preparation for WordPress 4.3 release

= 1.4.1 - August 17 2015 =
* Updated to latest Font Awesome.
* Added TripAdvisor to social links widget.
* Allow unfiltered HTML in SiteOrigin Editor Widget if user has rights.
* Properly set URL scheme.
* Fixed state emitter issue for Google Maps Widget.

= 1.4 - July 20 2015 =
* Created a base slider widget class.
* Converted current slider widget to use base slider.
* Fixed image sizing in slider widget.
* Added plain background color option to slider widget.
* Added new Hero Image widget.
* Fixed repeaters in sub items.

= 1.3.1 =
* Fix to TinyMCE field when moved in Customizer and Widgets interface.
* Small developer level improvements.
* Fixed autoplay in video widget.
* Fixed behaviour of slides in slider widget.

= 1.3 =
* Added TinyMCE field type.
* All fields now use classes to make them easier to extend.
* Added SiteOrigin Editor widget.
* Made it possible for other plugins to filter default widgets.
* Fixed WordPress CLI compatibility.
* Added unit tests.
* Added networks to social networks widget.
* Changed how repeater HTML is stored.

= 1.2.4 =
* Fixed reference to siteorigin_widgets_is_google_webfont.
* Fixed CSS URL.

= 1.2.3 =
* Fixed Javascript issue with Map widget in customizer.
* Added meta box manager.
* Small style change to flat button style.
* Video widget fixes.

= 1.2.2 =
* Added video widget with support for self/external videos.
* New activate/deactivate widgets interface.
* Headline widget CSS fixes.
* Dev Feature: Error checking for widget field type.
* Dev Feature: Added state emitters.
* Dev Feature: Additional hooks and filters.

= 1.2.1 =
* Removed is_customizer_preview - only available in newer versions of WordPress.

= 1.2 =
* Added headline widget.
* All scripts and styles loaded in header instead of lazy loading.
* Added email to social links widget.
* Made carousel touch friendly.
* Improved input sanitization for HTML input.
* Added nonce request checking in carousel widget.
* Added sticky field to post selector.
* Added function to allow Page Builder to use post selector.
* Added a few developer friendly filters.
* Fixed: Various customizer related issues.
* Fixed: Issue limiting maps widget to 10 markers.
* Fixed: Call to action alignment issues.
* Fixed: Carousel preview.

= 1.1.2 =
* Added social links widget.
* Framework updates.

= 1.1.1 =
* Fixed Google Map preview.

= 1.1 =
* Added powerful Google Maps widget.
* Improved data sanitization.
* Various UI improvements.

= 1.0.6 =
* Fixed issues with adding extra widget folders.
* Added compatibility with WordPress Customizer.
* Added more fields to be used with future widgets.

= 1.0.5 =
* Removed legacy widget deactivation functions.
* Improved how widgets are loaded.

= 1.0.4 =
* Changed how widget list is loaded to fix issue with widgets list not displaying.

= 1.0.3 =
* Features widget icons can now be made clickable.

= 1.0.2 =
* Manage widgets page now does live updates.
* Added widget previews.
* Fixed wire button widget hover issue.
* Old stand alone widget plugins are now deactivated in favor of bundled versions.
* Fixed centering of CTA widget.
* Fixed color settings in CTA widget.
* Fixed button icon color setting.
* Small UI improvements.

= 1.0.1 =
* Clean up of code and bundled widgets.

= 1.0 =
* Initial release.
