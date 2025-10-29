<?php
/**
 * Plugin Name:         User Account Monitor
 * Plugin URI:          https://pluginrx.com/plugin/user-account-monitor/
 * Description:         Detect and flag fake user accounts based on suspicious input patterns.
 * Version:             1.0.9
 * Requires at least:   5.9
 * Tested up to:        6.8
 * Requires PHP:        8.0
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Discord URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         user-account-monitor
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Created on:          June 3, 2025
 */


/**
 * Define Namespace
 */
namespace Apos37\UserAccountMonitor;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Defines
 */
$plugin_data = get_file_data( __FILE__, [
    'name'         => 'Plugin Name',
    'version'      => 'Version',
    'plugin_uri'   => 'Plugin URI',
    'requires_php' => 'Requires PHP',
    'textdomain'   => 'Text Domain',
    'author'       => 'Author',
    'author_uri'   => 'Author URI',
    'discord_uri'  => 'Discord URI'
] );

// Versions
define( 'UAMONITOR_VERSION', $plugin_data[ 'version' ] );
define( 'UAMONITOR_SCRIPT_VERSION', time() );                                                               // REPLACE WITH time() DURING TESTING (DEFAULT UAMONITOR_VERSION)
define( 'UAMONITOR_MIN_PHP_VERSION', $plugin_data[ 'requires_php' ] );

// Names
define( 'UAMONITOR_NAME', $plugin_data[ 'name' ] );
define( 'UAMONITOR_TEXTDOMAIN', $plugin_data[ 'textdomain' ] );
define( 'UAMONITOR__TEXTDOMAIN', str_replace( '-', '_', UAMONITOR_TEXTDOMAIN ) );
define( 'UAMONITOR_AUTHOR', $plugin_data[ 'author' ] );
define( 'UAMONITOR_AUTHOR_URI', $plugin_data[ 'author_uri' ] );
define( 'UAMONITOR_PLUGIN_URI', $plugin_data[ 'plugin_uri' ] );
define( 'UAMONITOR_GUIDE_URL', UAMONITOR_AUTHOR_URI . 'guide/plugin/' . UAMONITOR_TEXTDOMAIN . '/' );
define( 'UAMONITOR_DOCS_URL', UAMONITOR_AUTHOR_URI . 'docs/plugin/' . UAMONITOR_TEXTDOMAIN . '/' );
define( 'UAMONITOR_SUPPORT_URL', UAMONITOR_AUTHOR_URI . 'support/plugin/' . UAMONITOR_TEXTDOMAIN . '/' );
define( 'UAMONITOR_DISCORD_URL', $plugin_data[ 'discord_uri' ] );

// Paths
define( 'UAMONITOR_BASENAME', plugin_basename( __FILE__ ) );                                                //: text-domain/text-domain.php
define( 'UAMONITOR_ABSPATH', plugin_dir_path( __FILE__ ) );                                                 //: /home/.../public_html/wp-content/plugins/text-domain/
define( 'UAMONITOR_DIR', plugin_dir_url( __FILE__ ) );                                                      //: https://domain.com/wp-content/plugins/text-domain/
define( 'UAMONITOR_INCLUDES_ABSPATH', UAMONITOR_ABSPATH . 'inc/' );                                         //: /home/.../public_html/wp-content/plugins/text-domain/inc/
define( 'UAMONITOR_INCLUDES_DIR', UAMONITOR_DIR . 'inc/' );                                                 //: https://domain.com/wp-content/plugins/text-domain/inc/
define( 'UAMONITOR_JS_PATH', UAMONITOR_INCLUDES_DIR . 'js/' );                                              //: https://domain.com/wp-content/plugins/text-domain/inc/js/
define( 'UAMONITOR_CSS_PATH', UAMONITOR_INCLUDES_DIR . 'css/' );                                            //: https://domain.com/wp-content/plugins/text-domain/inc/css/
define( 'UAMONITOR_SETTINGS_PATH', admin_url( 'edit.php?post_type=uamonitor-files&page=settings' ) );       //: https://domain.com/wp-admin/?page=text-domain

// Screen IDs
define( 'UAMONITOR_SETTINGS_SCREEN_ID', 'users_page_' . UAMONITOR__TEXTDOMAIN );
define( 'UAMONITOR_SCAN_SCREEN_ID', 'users_page_' . UAMONITOR__TEXTDOMAIN . '_scan' );


/**
 * Includes
 */
require_once UAMONITOR_INCLUDES_ABSPATH . 'common.php';
require_once UAMONITOR_INCLUDES_ABSPATH . 'user.php';
require_once UAMONITOR_INCLUDES_ABSPATH . 'users.php';
require_once UAMONITOR_INCLUDES_ABSPATH . 'quick-scan.php';
require_once UAMONITOR_INCLUDES_ABSPATH . 'flags.php';
require_once UAMONITOR_INCLUDES_ABSPATH . 'indicator.php';
require_once UAMONITOR_INCLUDES_ABSPATH . 'registration.php';
require_once UAMONITOR_INCLUDES_ABSPATH . 'settings.php';


/**
 * Autoload integration classes
 */
$integration_files = glob( UAMONITOR_INCLUDES_ABSPATH . 'integrations/*.php' );
if ( $integration_files ) {
    foreach ( $integration_files as $file ) {
        require_once $file;
    }
}