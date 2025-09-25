# Copilot Instructions for User Account Monitor

## Project Overview
This is a WordPress plugin that detects and flags suspicious user registrations to help site owners prevent fake accounts and bots. It provides customizable detection rules, admin notifications, and integration with Gravity Forms. The plugin supports both single-site and multisite WordPress installations.

## Key Components
- `user-account-monitor.php`: Main plugin bootstrap file.
- `inc/`: Contains all core logic, including:
  - `common.php`: Shared utilities and helpers.
  - `flags.php`: Detection logic and flag definitions.
  - `indicator.php`: Admin bar indicator for flagged users.
  - `registration.php`: Hooks for user registration and validation.
  - `settings.php`: Admin settings page and options logic.
  - `user.php`, `users.php`: User-related logic and admin list table integration.
  - `integrations/gravity-forms.php`: Gravity Forms integration.
  - `css/`, `js/`: Admin UI assets.

## Detection Logic
- Detection checks are defined in `flags.php` and can be customized via hooks.
- Checks include uppercase letters, missing vowels, consonant clusters, numbers, symbols, short names, disposable emails, and more.
- Developers can add or modify detection logic using WordPress hooks (see `flags.php` and settings).

## Admin UI
- Main settings page: `/wp-admin/users.php?page=user_account_monitor` (see `settings.php`).
- Hidden scan page: `/wp-admin/users.php?page=user_account_monitor_scan` (add scan logic in `settings.php`).
- Flagged users are shown in the Users admin list table, with options to review or delete.

## Multisite Support
- Settings page is only accessible from the main site in a multisite setup.
- Network admin menu redirects to main site settings.

## Developer Workflows
- No build step required; plugin is pure PHP with static assets.
- Activate via WordPress admin, then configure settings.
- Debugging: Use `error_log()` for PHP errors; plugin logs flagged users if enabled.
- To add new detection rules, extend `Flags` class or use hooks (`uamonitor_settings_fields`, etc.).

## Project Conventions
- All plugin code is namespaced under `Apos37\UserAccountMonitor`.
- Settings/options are prefixed with `uamonitor_`.
- Use WordPress translation functions for all UI strings.
- Use WordPress hooks and filters for extensibility.

## Integration Points
- Gravity Forms integration is optional and handled in `inc/integrations/gravity-forms.php`.
- Detection logic can be extended via hooks and filters.

## Example: Adding a New Detection Rule
1. Extend the `Flags` class in `inc/flags.php`.
2. Add your rule to the `options()` method.
3. Use the `uamonitor_settings_fields` filter to expose it in the settings UI.

## References
- See `readme.txt` for feature overview and changelog.
- See `inc/settings.php` for admin UI and settings logic.
- See `inc/flags.php` for detection logic and extensibility.

---
If any section is unclear or missing, please provide feedback so instructions can be improved for future AI agents.

# Instructions for AI Code Assistants
- Do not generate patches without asking first. Always confirm with the user before making changes.
- Please add spaces inside parenthesis and brackets.