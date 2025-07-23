=== User Account Monitor ===
Contributors: apos37
Tags: spam, user registration, fake users, bot detection, account flagging
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Detect and flag suspicious user registrations using simple checks to protect your site from fake accounts and bots.

== Description ==

**User Account Monitor** helps WordPress site owners identify and flag suspicious user registrations before they become a problem. This plugin scans new user accounts at registration and applies a set of tests to detect common spam and bot patterns.

**Features:**

- **Gibberish Detection:** Flags accounts with non-human patterns like too many uppercase letters, no vowels, or clusters of consonants.
- **Symbol and Number Filters:** Detects unnatural use of digits or special characters in names.
- **Customizable Detection Rules:** Enable or disable individual checks to suit your site's user base.
- **Flag for Review:** Suspicious accounts are flagged and marked for potential deletion.
- **Admin Notice:** Quickly see how many flagged users exist from your admin area.
- **Scan Existing Users:** Scan the users admin list table for suspicious accounts so you can easily delete them.
- **Gravity Forms Integration:** If using Gravity Forms User Registration, the plugin optionally runs validation checks on registrations submitted via forms.
- **Developer Hooks:** Add or customize detection logic with your own functions.

**Detection Checks Include:**

- Manually flagged by admin
- Excessive uppercase letters (more than 5 in a name unless all caps)
- No vowels in names longer than 5 characters
- Six or more consecutive consonants in a name
- Presence of numbers in names
- Presence of special characters other than letters, numbers, and dashes
- Similarity between first and last name (exact match or one includes the other)
- Very short names (2 characters)
- Invalid or disposable email domains
- Excessive periods in email address (more than 3)
- Username containing URL patterns (`http`, `https`, or `www`)
- Known spam words in user bio or name

This plugin is ideal for membership sites, forums, or any WordPress site that allows user registration and needs protection against fake or low-quality signups.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/user-account-monitor/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure detection settings in the plugin's admin settings page under **Users > Acount Monitor**.
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

== Changelog ==
= 1.0.3 =
* Update: Added more support for hooks

= 1.0.2 =
* Update: Added support for multisite networks

= 1.0.1 =
* Initial release
