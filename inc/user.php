<?php 
/**
 * Actions performed on an individual user
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
    (new IndividualUser())->init();
} );


/**
 * The class
 */
class IndividualUser {

    /**
     * Meta keys
     *
     * @var string
     */
    public $meta_key_suspicious = 'suspicious';


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce_scan = 'uamonitor_nonce_scan';
    private $nonce_clear = 'uamonitor_nonce_clear';

    
    /**
     * Ajax keys
     *
     * @var string
     */
    private $ajax_key_scan = 'uamonitor_scan';
    private $ajax_key_clear = 'uamonitor_clear';


    /**
     * Available flags
     *
     * @var array
     */
    private $available_flags = [];


    /**
     * Constructor
     */
    public function __construct() {
        
        // Get the available flags
        $this->available_flags = array_merge( $this->available_flags, (new Flags())->options() );

    } // End __construct()


    /**
     * Load on init
     */
    public function init() {

        // Ajax
        add_action( 'wp_ajax_'.$this->ajax_key_scan, [ $this, 'ajax_scan' ] );
        add_action( 'wp_ajax_'.$this->ajax_key_clear, [ $this, 'ajax_clear' ] );

        // Custom profile fields
        add_action( 'show_user_profile', [ $this, 'add_user_profile_fields' ] );
        add_action( 'edit_user_profile', [ $this, 'add_user_profile_fields' ] );
        add_action( 'personal_options_update', [ $this, 'save_user_profile_fields' ] );
        add_action( 'edit_user_profile_update', [ $this, 'save_user_profile_fields' ] );

    } // End init()


    /**
     * Check if a user should be flagged
     *
     * @param int $user_id
     * @return array|false
     */
    public function check( $user_id, $only_check_existing = false, $force_recheck = false, $update_user = true ) {
        // If they are already cleared
        $suspicious = get_user_meta( $user_id, $this->meta_key_suspicious, true );
        $recheck = $force_recheck || get_option( 'uamonitor_recheck_cleared' );
        if ( !$recheck && $suspicious === 'cleared' ) {
            return 'cleared';
        }

        // Get the options
        $FLAGS        = new Flags();
        $option_keys  = $FLAGS->options( true );
        $enabled_keys = [];

        foreach ( $option_keys as $key ) {
            if ( $key == 'admin_flag' || get_option( 'uamonitor_' . $key, true ) ) {
                $enabled_keys[] = $key;
            }
        }

        // If they have already been checked and found suspicious
        if ( $suspicious !== 'cleared' && !empty( $suspicious ) && !$force_recheck ) {
            $active_flags = array_values( array_intersect( $suspicious, $enabled_keys ) );
            if ( !empty( $active_flags ) ) {
                return $active_flags;
            }
            return 'cleared';
        }

        // Stop here if we are just checking for those that have already been checked
        if ( $only_check_existing ) {
            return false;
        }

        // Get the user object
        $user = get_userdata( $user_id );
        if ( !$user ) {
            return false;
        }

        // Check it
        $user_flags = $this->run_flag_checks( $user, $enabled_keys );

        // If we have flags
        if ( !empty( $user_flags ) ) {
            if ( $update_user ) {
                update_user_meta( $user_id, $this->meta_key_suspicious, $user_flags );
            }
            $this->log_flags( $user, $user_flags );
            $this->auto_delete( $user, $user_flags );
            do_action( 'uamonitor_check_after_flagged', $user, $user_flags );
            return $user_flags;
        } else {
            if ( $update_user ) {
                update_user_meta( $user_id, $this->meta_key_suspicious, 'cleared' );
            }
        }

        do_action( 'uamonitor_check_after_cleared', $user, $user_flags );
        return 'cleared';
    } // End check()


    /**
     * Run flag checks on a user object
     *
     * @param object $user
     * @param array  $enabled_keys
     * @return array Flag keys that triggered
     */
    public function run_flag_checks( $user, $enabled_keys ) {
        $FLAGS       = new Flags();
        $user_flags  = [];

        foreach ( $enabled_keys as $key ) {
            $method = 'check_' . $key;

            if ( method_exists( $FLAGS, $method ) && $FLAGS->$method( $user ) ) {
                $user_flags[] = $key;
                continue;
            }

            $custom_check_callback = apply_filters( 'uamonitor_custom_flag_callback', null, $key );
            if ( is_callable( $custom_check_callback ) && call_user_func( $custom_check_callback, $user, $key ) ) {
                $user_flags[] = $key;
            }
        }

        return $user_flags;
    } // End run_flag_checks()
   

    /**
     * Log flags
     *
     * @param object $user
     * @param array $flags
     * @return void
     */
    public function log_flags( $user, $flags, $registration = false ) {
        $log_flags = filter_var( get_option( 'uamonitor_log_flags', false ), FILTER_VALIDATE_BOOLEAN );
        if ( !$log_flags ) {
            return;
        }

        if ( !empty( $user ) ) {
            if ( $registration ) {
                $user_display = sprintf(
                    'Registration attempt (username: %s, email: %s)',
                    $user->user_login ?? '[unknown]',
                    $user->user_email ?? '[unknown]'
                );
            } else {
                $user_display = $user->display_name . ' (ID - ' . $user->ID . ')';
            }
            
            $error = UAMONITOR_NAME . ': ' . __( 'User flagged - ', 'user-account-monitor' ) . ' ' . implode( ', ', $flags ) . ' - ' .  $user_display;
            $error = apply_filters( 'uamonitor_log_flag_error', $error, $user, $flags );
            error_log( $error ); // phpcs:ignore 
        }
    } // End log_flags()


    /**
     * Delete users
     *
     * @param object $user
     * @param array $flags
     * @return array
     */
    public function auto_delete( $user, $flags ) {
        $auto_delete = filter_var( get_option( 'uamonitor_auto_delete', false ), FILTER_VALIDATE_BOOLEAN );
        if ( !$auto_delete ) {
            return;
        }

        do_action( 'uamonitor_auto_delete_before', $user, $flags );

        $should_delete = apply_filters( 'uamonitor_auto_delete', true, $user, $flags );
        if ( $should_delete ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user( $user->ID );
        }

        do_action( 'uamonitor_auto_delete_after', $user, $flags );
    } // End auto_delete()


    /**
     * Ajax call to start scanning
     *
     * @return void
     */
    public function ajax_scan() {
        // Verify nonce for AJAX
        check_ajax_referer( $this->nonce_scan, 'nonce' );

        // Get the user ID
        $user_id = isset( $_REQUEST[ 'userID' ] ) ? absint( wp_unslash( $_REQUEST[ 'userID' ] ) ) : 0;
        if ( $user_id ) {

            // Are we scanning a single user?
            $single = isset( $_REQUEST[ 'single' ] ) ? filter_var( wp_unslash( $_REQUEST[ 'single' ] ), FILTER_VALIDATE_BOOLEAN ) : false;

            // Check the user
            $suspicious = $this->check( $user_id, false, $single );

            if ( is_array( $suspicious ) && !empty( $suspicious ) ) {
                $flag_names = [];
                foreach ( $suspicious as $flag ) {
                    foreach ( $this->available_flags as $available_flag ) {
                        if ( $available_flag[ 'key' ] == $flag ) {
                            $flag_names[] = $available_flag[ 'title' ];
                        }
                    }
                }
                $suspicious = $flag_names;
            }

            // Return success
            wp_send_json_success( [
                'user_id'    => $user_id,
                'suspicious' => $suspicious,
            ] );
        }

        // Return error
        wp_send_json_error( [ 'msg' => 'No user ID found.' ] );
    } // End ajax_scan()


    /**
     * Ajax call for clearing a user
     *
     * @return void
     */
    public function ajax_clear() {
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'msg' => 'Permission denied.' ] );
        }
        
        // Verify nonce for AJAX
        check_ajax_referer( $this->nonce_clear, 'nonce' );

        // Action
        $method = isset( $_REQUEST[ 'method' ] ) ? sanitize_key( wp_unslash( $_REQUEST[ 'method' ] ) ) : 0;

        // Get the user ID
        $user_id = isset( $_REQUEST[ 'userID' ] ) ? absint( wp_unslash( $_REQUEST[ 'userID' ] ) ) : 0;
        if ( $user_id ) {

            // Clear the user
            $value = ( $method == 'clear' ) ? 'cleared' : [ 'admin_flag' ];
            update_user_meta( $user_id, $this->meta_key_suspicious, $value );

            // Return success
            wp_send_json_success( [
                'msg' => __( 'User updated.', 'user-account-monitor' ),
            ] );
        }

        // Return error
        wp_send_json_error( [ 'msg' => 'No user ID found.' ] );
    } // End ajax_clear()


    /**
     * Add custom user meta fields based on uamonitor_profile_fields option
     *
     * @param WP_User $user
     * @return void
     */
    public function add_user_profile_fields( $user ) {
        $fields_option = get_option( 'uamonitor_profile_fields', '' );
        if ( empty( $fields_option ) ) {
            return;
        }

        $fields = array_map( 'trim', explode( ',', $fields_option ) );

        printf(
            '<h2>%1$s</h2>',
            esc_html( __( 'Meta Keys', 'user-account-monitor' ) )
        );

        foreach ( $fields as $field ) {
            if ( preg_match( '/^([^\(]+)\((.+)\)$/', $field, $matches ) ) {
                $meta_key = sanitize_key( trim( $matches[1] ) );
                $label = sanitize_text_field( trim( $matches[2] ) );
            } else {
                $meta_key = sanitize_key( $field );
                $label = sanitize_text_field( $field );
            }

            $value = get_user_meta( $user->ID, $meta_key, true );
            $value = is_array( $value ) ? array_map( 'esc_attr', $value ) : esc_attr( $value );

            printf(
                '<table class="form-table">
                    <tr>
                        <th><label for="%1$s">%2$s</label></th>
                        <td><input type="text" name="%1$s" id="%1$s" value="%3$s" class="regular-text" /></td>
                    </tr>
                </table>',
                esc_attr( $meta_key ),
                esc_html( $label ),
                is_array( $value ) ? implode( ', ', $value ) : $value
            );
        }
    } // End add_user_profile_fields()


    /**
     * Save the user meta fields
     *
     * @param int $user_id
     * @return void
     */
    public function save_user_profile_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        $fields_option = get_option( 'uamonitor_profile_fields', '' );

        if ( empty( $fields_option ) ) {
            return;
        }

        $fields = array_map( 'trim', explode( ',', $fields_option ) );

        foreach ( $fields as $field ) {
            if ( preg_match( '/^([^\(]+)\((.+)\)$/', $field, $matches ) ) {
                $meta_key = sanitize_key( trim( $matches[1] ) );
            } else {
                $meta_key = sanitize_key( $field );
            }

            if ( isset( $_POST[ $meta_key ] ) ) {
                if ( is_array( $_POST[ $meta_key ] ) ) {
                    $value = array_map( 'sanitize_text_field', $_POST[ $meta_key ] );
                } else {
                    $value = sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) );
                }
                update_user_meta( $user_id, $meta_key, $value );
            }
        }
    } // End save_user_profile_fields()


}