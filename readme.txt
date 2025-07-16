=== User Account Monitor ===
Contributors: apos37
Tags: spam, user registration, fake users, bot detection, account flagging
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.2
License: Proprietary
License URI: https://pluginrx.com/proprietary-license-agreement/

Detects suspicious or fake user registrations based on name patterns, gibberish detection, and other simple checks. Flags questionable accounts for admin review or deletion.

== Description ==

**User Account Monitor** helps WordPress site owners identify and flag suspicious user registrations before they become a problem. This plugin scans new user accounts at registration and applies a set of tests to detect common spam and bot patterns.

**Features:**

- **Gibberish Detection:** Flags accounts with non-human patterns like too many uppercase letters, no vowels, or clusters of consonants.
- **Symbol and Number Filters:** Detects unnatural use of digits or special characters in names.
- **Customizable Detection Rules:** Enable or disable individual checks to suit your site's user base.
- **Flag for Review:** Suspicious accounts are flagged and marked for potential deletion.
- **Admin Dashboard Notice:** Quickly see how many flagged users exist from your dashboard.
- **Developer Hooks:** Add or customize detection logic with your own functions.

**Detection Checks Include:**

- Excessive uppercase letters
- No vowels in names longer than 3 characters
- Five or more consonants in a row
- Use of numbers or special characters in names

This plugin is ideal for membership sites, forums, or any WordPress site that allows user registration and needs protection against fake or low-quality signups.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/user-account-monitor/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure detection settings in the plugin's admin settings page.
4. Monitor flagged accounts from the WordPress Users screen.

== Frequently Asked Questions ==

= How does the plugin determine if an account is fake? =
The plugin uses a set of simple checks designed to identify accounts that don’t look like real human registrations. This includes checking for too many uppercase letters, missing vowels, long consonant clusters, symbols, and more.

= Will flagged users be deleted automatically? =
By default, flagged users are only marked for review. However, there is an option in the settings to enable automatic deletion of flagged users.

= Can I disable specific checks? =
Yes. The plugin settings let you turn on or off individual detection checks such as vowel absence or number detection.

= Where can I request features and get support? =
We recommend using our [website support forum](https://pluginrx.com/support/plugin/user-account-monitor/) as the primary method for requesting features and getting help. You can also reach out via our [Discord support server](https://discord.gg/3HnzNEJVnR) or the [WordPress.org support forum](https://wordpress.org/support/plugin/user-account-monitor/), but please note that WordPress.org doesn’t always notify us of new posts, so it’s not ideal for time-sensitive issues.

== Screenshots ==
1. Plugin settings page with heuristic toggles.
2. Flagged users list in the WordPress Users screen.
3. Admin dashboard widget showing flagged user count.

== Changelog ==
= 1.0.2 =
* Added support for multisite networks

= 1.0.1 =
* Initial release
