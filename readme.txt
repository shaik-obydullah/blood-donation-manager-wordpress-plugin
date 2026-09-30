=== Obydullah Blood Bank Manager ===
Contributors: obydullah
Tags: blood donation, blood bank, donor, blood request, health
Text Domain: obydullah-blood-bank-manager
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete blood bank management system with donor registration, donation requests, blood bank listings, and compatibility matching.

== Description ==

Obydullah Blood Bank Manager turns your WordPress site into a complete blood donation platform. Register donors, publish urgent blood requests, maintain a searchable blood bank directory, and keep everyone informed with automated email notifications.

= Features =

* Donor registration with blood type, availability, and city profiles.
* Blood request submission with urgency levels and automatic donor matching.
* Blood bank directory with contact details, hours, and location.
* Blood type compatibility and coverage dashboard.
* Email notifications for matching donors.
* Campaign management for themed blood drives.
* Frontend shortcodes and admin panel included.

= Shortcodes =

| Shortcode | Description |
|-----------|-------------|
| `[obdm_donor_registration]` | Donor signup form |
| `[obdm_donation_request]` | Blood request form |
| `[obdm_blood_banks]` | Blood bank directory |
| `[obdm_donors_list]` | Available donors list |
| `[obdm_blood_requests]` | Active blood requests |
| `[obdm_dashboard]` | Statistics dashboard |

= Compatibility =

Compatible with the latest WordPress versions, standard themes, and page builders through shortcodes.

== Installation ==

1. Upload the `obydullah-blood-bank-manager` folder to the `/wp-content/plugins/` directory, or install the plugin zip through the WordPress admin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to the new 'Blood Bank Management' menu in the admin sidebar.
4. Place the shortcodes on your pages to start collecting donor registrations and blood requests.

== Frequently Asked Questions ==

= Will existing data be preserved when updating? =

Yes. The plugin renames its storage to the `obdm` prefix and migrates existing donors, requests, blood banks, campaigns, and settings on activation.

= Can I customize the emails sent to donors? =

Yes. Notification message templates can be edited under Settings > Email notifications.

== Changelog ==

= 1.0.0 =
* Initial release.
* Donor registration, blood requests, blood bank directory, campaigns, and settings.
* BDM to OBDM codebase rename with automatic database migration.