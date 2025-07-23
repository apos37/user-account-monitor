<?php
/**
 * Gravity Forms Integration
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
new GravityForms();


/**
 * The class
 */
class GravityForms {

    /**
     * The setting key
     *
     * @var string
     */
    public $setting_key = 'gravity_forms_registration_form';


    /**
     * Instantiate flags
     *
     * @var Flags
     */
    public $FLAGS;


    /**
     * Constructor
     */
    public function __construct() {

        // Stop if we're in the network admin
        if ( is_network_admin() ) {
            return;
        }

        // Stop if Gravity Forms is not active
        if ( !function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( !is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            return;
        }

        // Instantiate flags
        $this->FLAGS = new Flags();

        // The setting
        add_filter( 'uamonitor_integrations_fields', [ $this, 'setting_field' ] );

        // Check for flags at validation
        $registration_form_id = absint( get_option( 'uamonitor_' . $this->setting_key ) );
        if ( $registration_form_id && $registration_form_id > 0 ) {
            add_filter( 'gform_field_validation_' . $registration_form_id, [ $this, 'validate_registration' ], 10, 4 );
        }

    } // End __construct()


    /**
     * Add Gravity Forms registration form selector to settings if GF is active.
     *
     * @param array $fields Existing settings fields.
     * @return array Modified settings fields.
     */
    public function setting_field( $fields ) {
        $forms = \GFAPI::get_forms( null );
        $choices = [
            '' => __( 'Select a Form', 'user-account-monitor' )
        ];

        foreach ( $forms as $form ) {
            $title = $form[ 'title' ];
            if ( !$form[ 'is_active' ] ) {
                $title .= ' (' . __( 'Inactive', 'user-account-monitor' ) . ')';
            }
            $choices[ $form[ 'id' ] ] = $title;
        }

        $fields[] = [
            'key'        => $this->setting_key,
            'title'      => __( 'Gravity Forms Registration Form Validation', 'user-account-monitor' ),
            'comments'   => __( 'Select the Gravity Form used for user registration to enable fake account checks.', 'user-account-monitor' ),
            'field_type' => 'select',
            'options'    => $choices,
            'sanitize'   => 'absint',
            'section'    => 'integrations',
            'default'    => '',
        ];

        return $fields;
    } // End setting_field()


    /**
     * Validate Gravity Forms registration form for suspicious users.
     *
     * @param array $result
     * @param mixed $value
     * @param array $form
     * @param \GF_Field $field
     * @return array
     */
    public function validate_registration( $result, $value, $form, $field ) {
        $registration_form_id = absint( get_option( 'uamonitor_' . $this->setting_key ) );
        if ( empty( $registration_form_id ) || $registration_form_id !== absint( $form['id'] ) ) {
            return $result;
        }

        // Validate only specific field types
        $valid_types = [ 'name', 'email', 'text', 'textarea' ];
        if ( !in_array( $field->type, $valid_types, true ) ) {
            return $result;
        }

        // Initialize errors array
        $errors = [];

        // Name field validation
        if ( $field->type === 'name' || $field->inputName === 'name' ) {
            $names = [];

            if ( $field->type === 'name' && is_array( $value ) ) {
                $names[] = sanitize_text_field( rgar( $value, $field->id . '.3' ) ); // First name
                $names[] = sanitize_text_field( rgar( $value, $field->id . '.6' ) ); // Last name
            } else {
                $names[] = sanitize_text_field( $value );
            }

            foreach ( $names as $name ) {
                if ( $this->FLAGS->check_excessive_uppercase( $name ) ) {
                    $errors[] = 'Excessive uppercase letters';
                }
                if ( $this->FLAGS->check_no_vowels( $name ) ) {
                    $errors[] = 'No vowels';
                }
                if ( $this->FLAGS->check_consonant_cluster( $name ) ) {
                    $errors[] = 'Suspicious consonant clusters';
                }
                if ( $this->FLAGS->check_numbers( $name ) ) {
                    $errors[] = 'Contains numbers';
                }
                if ( $this->FLAGS->check_special_characters( $name ) ) {
                    $errors[] = 'Contains special characters';
                }
                if ( $this->FLAGS->check_spam_words( $name ) ) {
                    $errors[] = 'Contains spam words';
                }
            }

            if ( $field->type === 'name' && $this->FLAGS->check_similar_first_last_name( [
                'first_name' => sanitize_text_field( rgar( $value, $field->id . '.3' ) ),
                'last_name'  => sanitize_text_field( rgar( $value, $field->id . '.6' ) )
            ] ) ) {
                $errors[] = 'First and last names are too similar';
            }

            $errors = array_unique( $errors );

            if ( !empty( $errors ) ) {
                $result[ 'is_valid' ] = false;
                $result[ 'message' ] = implode( '. ', $errors ) . '.';
            }

            return $result;
        }

        // Username/email logic handling
        $is_username = ( $field->inputName === 'username' || $field->inputName === 'user_name' );
        $is_email_field = ( $field->type === 'email' || $field->inputName === 'email' );
        $is_text_field_email = ( $field->type === 'text' && $is_username );

        if ( $is_email_field || $is_text_field_email ) {
            $email_value = sanitize_email( is_array( $value ) ? rgar( $value, 0 ) : $value );
            $errors = [];

            // Email-specific checks
            if ( $this->FLAGS->check_invalid_email_domain( $email_value ) ) {
                $errors[] = 'Invalid domain';
            }

            if ( $this->FLAGS->check_excessive_periods_email( $email_value ) ) {
                $errors[] = 'Excessive periods';
            }

            // Username check if this is also the username field
            if ( $is_username && $this->FLAGS->check_url_in_username( $email_value ) ) {
                $errors[] = 'Contains URL';
            }

            $errors = array_unique( $errors );

            if ( !empty( $errors ) ) {
                $result[ 'is_valid' ] = false;
                $result[ 'message' ] = 'Email issue(s): ' . implode( '. ', $errors ) . '.';
            }
            return $result;
        }

        // Username field validation (text inputName=username that is not an email)
        if ( $field->type === 'text' && $is_username && !is_email( $value ) ) {
            $username = sanitize_text_field( $value );
            if ( $this->FLAGS->check_url_in_username( $username ) ) {
                $result[ 'is_valid' ] = false;
                $result[ 'message' ] = 'Username contains a URL.';
            }
            return $result;
        }

        // Description / textarea spam words check
        if ( $field->type === 'textarea' || $field->inputName === 'description' ) {
            if ( $this->FLAGS->check_spam_words( sanitize_text_field( $value ) ) ) {
                $result[ 'is_valid' ] = false;
                $result[ 'message' ] = 'Description contains spam words.';
            }
        }

        return $result;
    } // End validate_registration()

}