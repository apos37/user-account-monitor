<?php 
/**
 * Plugin settings
 */


/**
 * Define Namespaces
 */
namespace Apos37\UserAccountMonitor;
use Apos37\UserAccountMonitor\Flags;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new Settings())->init();
} );


/**
 * The class
 */
class Settings {
   
    /**
     * Load on init
     */
    public function init() {
        
		// Submenu
        add_action( 'admin_menu', [ $this, 'submenu' ] );
        if ( is_multisite() ) {
            add_action( 'network_admin_menu', [ $this, 'submenu_network' ] );
        }

		// Settings fields
        add_action( 'admin_init', [  $this, 'settings_fields' ] );

    } // End init()


	/**
     * Submenu
     *
     * @return void
     */
    public function submenu() {
        add_submenu_page(
            'users.php',
            UAMONITOR_NAME . ' — ' . __( 'Settings', 'user-account-monitor' ),
            __( 'Account Monitor', 'user-account-monitor' ),
            'manage_options',
            UAMONITOR__TEXTDOMAIN,
            [ $this, 'page' ]
        );
    } // End submenu()


    /**
     * Submenu for network admin.
     *
     * @return void
     */
    public function submenu_network() {
        $page = is_network_admin() ? [ $this, 'redirect_to_main_site_settings' ] : [ $this, 'page' ];
        add_submenu_page(
            'users.php', // still 'users.php' in network admin
            UAMONITOR_NAME . ' — ' . __( 'Settings', 'user-account-monitor' ),
            __( 'Account Monitor', 'user-account-monitor' ),
            'manage_options',
            UAMONITOR__TEXTDOMAIN,
            $page
        );
    } // End submenu_network()


    /**
     * Redirect to main site settings if in network admin.
     *
     * This is to ensure that the settings page is only accessible from the main site in a multisite setup.
     */
    public function redirect_to_main_site_settings() {
        if ( is_multisite() && is_network_admin() ) {
            $url = add_query_arg( [
                'page' => UAMONITOR__TEXTDOMAIN
            ], get_admin_url( get_main_site_id(), 'users.php' ) );
            wp_redirect( $url );
            exit;
        }
    } // End redirect_to_main_site_settings()

    
    /**
     * The page
     *
     * @return void
     */
    public function page() {
        global $current_screen;
        if ( $current_screen->id != UAMONITOR_SETTINGS_SCREEN_ID ) {
            return;
        }
        ?>
		<div class="wrap">
			<h1><?php echo esc_attr( get_admin_page_title() ) ?></h1>

            <!-- Settings Form -->
            <br><br>
			<form method="post" action="options.php">
				<?php
					settings_fields( UAMONITOR_TEXTDOMAIN );
					do_settings_sections( UAMONITOR_TEXTDOMAIN );
                    submit_button();
				?>
			</form>
		</div>
        <?php
    } // End page()


    /**
     * The options
     *
     * @param boolean $return_keys_only
     * @param null|array $sections
     * @return array
     */
    public function options( $return_keys_only = false ) {
        // Settings
        $fields = [
            [
                'key'        => 'hide_cleared',
                'title'      => __( 'Temporarily Hide Cleared Users', 'user-account-monitor' ),
                'comments'   => __( 'Temporarily hide users that are cleared during the scan so viewing the flagged list is easier.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'general',
                'default'    => FALSE,
            ],
            [
                'key'        => 'recheck_cleared',
                'title'      => __( 'Recheck Cleared Users', 'user-account-monitor' ),
                'comments'   => __( 'Recheck all previously cleared users.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'general',
                'default'    => FALSE,
            ],
            [
                'key'        => 'log_flags',
                'title'      => __( 'Log Each Flagged User', 'user-account-monitor' ),
                'comments'   => __( 'Logs each user that is flagged to the Debug Log.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'general',
                'default'    => FALSE,
            ],
        ];

        // Conditionally add auto-delete setting
        if ( apply_filters( 'uamonitor_enable_auto_delete_option', false ) ) {
            $fields[] = [
                'key'        => 'auto_delete',
                'title'      => __( 'Auto-Delete Flagged Users', 'user-account-monitor' ),
                'comments'   => __( 'Automatically delete users flagged by any check.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'general',
                'default'    => FALSE,
            ];
        }

        // Add the flags to check
        $fields = array_merge( $fields, (new Flags())->options() );

        // Integrations
        $fields = apply_filters( 'uamonitor_integrations_fields', $fields );

        // Allow developers to customize options
        $fields = apply_filters( 'uamonitor_settings_fields', $fields );

        if ( $return_keys_only ) {
            $field_keys = [];
            foreach ( $fields as $field ) {
                $field_keys[] = $field[ 'key' ];
            }
            return $field_keys;
        }
        return $fields;
    } // End options()


    /**
     * Settings fields
     *
     * @return void
     */
    public function settings_fields() {
        // Slug
        $slug = UAMONITOR_TEXTDOMAIN;

        // Fields
        $fields = $this->options();

        /**
         * Sections
         */
        $sections = [
            [ 'general', __( 'General', 'user-account-monitor' ), '' ],
            [ 'checks', __( 'What do you want to check for?', 'user-account-monitor' ), '' ],
            [ 'integrations', __( 'Integrations', 'user-account-monitor' ), '' ]
        ];

        // Allow developers to customize sections
        $sections = apply_filters( 'uamonitor_settings_sections', $sections );

        // Only include sections with fields
        $sections_to_add = [];
        foreach ( $sections as $section ) {
            $section_key = $section[0];
            
            // Check if any fields exist for the section
            $section_fields = array_filter( $fields, function( $field ) use ( $section_key ) {
                return isset( $field[ 'section' ] ) && $field[ 'section' ] === $section_key;
            } );

            // If there are fields for this section, add the section to the settings sections
            if ( !empty( $section_fields ) ) {
                $sections_to_add[] = $section;
            }
        }

        // Iter the filtered sections
        foreach ( $sections_to_add as $section ) {
            add_settings_section(
                $section[0],
                $section[1] . ':',
                $section[2],
                $slug,
                [ 'after_section' => '<br><br>' ]
            );
        }
        
        /**
         * Fields
         */
        // Iter the fields
        foreach ( $fields as $field ) {
            $option_name = 'uamonitor_'.$field[ 'key' ];
            $callback = 'settings_field_'.$field[ 'field_type' ];

            if ( !method_exists( $this, $callback ) ) {
                $full_callback = get_class( $this ) . '::' . $callback;
                error_log( UAMONITOR_NAME . ': ' . sprintf( __( 'Method "%s" does not exist', 'user-account-monitor' ), $full_callback ) ); // phpcs:ignore
                continue;
            }

            // Hidden
            $incl_hidden_class = ( $field[ 'field_type' ] == 'hidden' ) ? ' hidden' : '';
            
            // Start the args
            $args = [
                'id'    => $option_name,
                'class' => $option_name . $incl_hidden_class,
                'name'  => $option_name,
            ];

            // Add comments
            if ( isset( $field[ 'comments' ] ) ) {
                $args[ 'comments' ] = $field[ 'comments' ];
            }
            
            // Add select options
            if ( isset( $field[ 'options' ] ) ) {
                $args[ 'options' ] = $field[ 'options' ];
            }

            // Add default
            if ( isset( $field[ 'default' ] ) ) {
                $args[ 'default' ] = $field[ 'default' ];
            }

            // Add revert
            if ( isset( $field[ 'revert' ] ) ) {
                $args[ 'revert' ] = $field[ 'revert' ];
            }

            // Add the field
            register_setting( $slug, $option_name, sanitize_key( $field[ 'sanitize' ] ) );
            add_settings_field( $option_name, $field[ 'title' ], [ $this, $callback ], $slug, $field[ 'section' ], $args );
        }
    } // End settings_fields()
  
    
    /**
     * Custom callback function to print text field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_text( $args ) {
        $width = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '43rem';
        $default = isset( $args[ 'default' ] )  ? $args[ 'default' ] : '';
        $value = get_option( $args[ 'name' ], $default );
        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] == true && trim( $value ) == '' ) {
            $value = $default;
        }
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            '<input type="text" id="%s" name="%s" value="%s" style="width: %s;" />%s',
            esc_attr( $args[ 'id' ] ),
            esc_attr( $args[ 'name' ] ),
            esc_html( $value ),
            esc_attr( $width ),
            wp_kses_post( $comments )
        );
    } // settings_field_text()


    /**
     * Custom callback function to print hidden field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_hidden( $args ) {
        $value = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';

        printf(
            '<input type="hidden" id="%s" name="%s" value="%s" />',
            esc_attr( $args[ 'id' ] ),
            esc_attr( $args[ 'name' ] ),
            esc_attr( $value )
        );
    } // settings_field_hidden()


    /**
     * Custom callback function to print checkbox field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_checkbox( $args ) {
        $value = get_option( $args[ 'name' ] );
        if ( false === $value && isset( $args[ 'default' ] ) ) {
            $value = $args[ 'default' ];
        }
        $value = $this->sanitize_checkbox( $value );
        
        $comments = isset( $args[ 'comments' ] ) ? ' <p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            '<input type="checkbox" id="%s" name="%s" value="1" %s/>%s',
            esc_attr( $args[ 'name' ] ),
            esc_attr( $args[ 'name' ] ),
            checked( 1, $value, false ),
            wp_kses_post( $comments )
        );
    } // End settings_field_checkbox()    


    /**
     * Sanitize checkbox
     *
     * @param int $value
     * @return boolean
     */
    public function sanitize_checkbox( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    } // End sanitize_checkbox()


    /**
     * Custom callback function to print select field
     *
     * @param array $args
     * @return void
     */
    public function settings_field_select( $args ) {
        $width   = isset( $args[ 'width' ] ) ? $args[ 'width' ] : '20rem';
        $options = isset( $args[ 'options' ] ) ? $args[ 'options' ] : [];
        $default = isset( $args[ 'default' ] ) ? $args[ 'default' ] : '';
        $value   = sanitize_key( get_option( $args[ 'name' ], $default ) );
        if ( isset( $args[ 'revert' ] ) && $args[ 'revert' ] === true && trim( $value ) === '' ) {
            $value = $default;
        }
        $comments = isset( $args[ 'comments' ] ) ? '<br><p class="description">' . $args[ 'comments' ] . '</p>' : '';

        printf(
            // Translators: %1$s is the select field id, %2$s is the select field name, %3$s is the CSS width style, %4$s is the rendered <option> tags, %5$s is comments HTML.
            '<select id="%1$s" name="%2$s" style="width: %3$s;">%4$s</select>%5$s',
            esc_attr( $args[ 'name' ] ),
            esc_attr( $args[ 'name' ] ),
            esc_attr( $width ),
            wp_kses(
                $this->render_select_options( $options, $value ),
                [
                    'option' => [
                        'value'    => true,
                        'selected' => true,
                    ],
                ]
            ),
            wp_kses_post( $comments )
        );
    } // End settings_field_select()


    /**
     * Renders <option> tags for a select field
     *
     * @param array  $options
     * @param string $selected
     * @return string
     */
    private function render_select_options( $options, $selected ) {
        $html = '';
        foreach ( $options as $val => $label ) {
            $html .= sprintf(
                // Translators: %1$s is the option value, %2$s is 'selected' if this is the current value, %3$s is the label text.
                '<option value="%1$s"%2$s>%3$s</option>',
                esc_attr( $val ),
                selected( $selected, $val, false ),
                esc_html( $label )
            );
        }
        return $html;
    } // End render_select_options()


    /**
     * Enqueue javascript
     *
     * @return void
     */
    public function enqueue_scripts( $hook ) {
		// JavaScript
        wp_register_script( UAMONITOR_TEXTDOMAIN, UAMONITOR_JS_PATH . 'back-notice.js', [ 'jquery' ], UAMONITOR_VERSION, true );
		wp_enqueue_script( UAMONITOR_TEXTDOMAIN );

        // Check if we are on the correct admin page
        if ( $hook !== 'appearance_page_'.UAMONITOR_TEXTDOMAIN ) {
            return;
        }

		// CSS
		wp_enqueue_style( UAMONITOR_TEXTDOMAIN . '-styles', UAMONITOR_CSS_PATH . 'settings.css', [], UAMONITOR_VERSION );
    } // End enqueue_scripts()

}
