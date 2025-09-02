<?php
/**
 * The flags to check
 */


/**
 * Define Namespaces
 */
namespace Apos37\UserAccountMonitor;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
// new Flags();


/**
 * The class
 */
class Flags {

    /**
     * Names to scan
     *
     * @var array
     */
    public $names_to_scan = [
        'first_name',
        'last_name',
        'display_name',
    ];


    /**
     * Very short name arguments
     *
     * @var array
     */
    public $very_short_name_args = [
        'cap'          => 2,
        'allow_single' => true,
        'allow_two'    => false,
    ];


    /**
     * Constructor
     */
    public function __construct() {
        
        // Allow customizing name fields
        $this->customize_names();

    } // End __construct()


    /**
     * Allow customizing the names via a hook
     *
     * @return void
     */
    public function customize_names() {
        $this->names_to_scan = apply_filters( 'uamonitor_names_to_scan', $this->names_to_scan );
    } // End customize_names()


    /**
     * The options
     *
     * @param boolean $return_keys_only
     * @param null|array $sections
     * @return array
     */
    public function options( $return_keys_only = false, $return_option = false ) {
        // Short names description
        $short_name_args = apply_filters( 'uamonitor_short_name_length', $this->very_short_name_args );
        $cap             = absint( $short_name_args[ 'cap' ] );
        $allow_single    = !empty( $short_name_args[ 'allow_single' ] );
        $allow_two       = !empty( $short_name_args[ 'allow_two' ] );

        if ( ( !$allow_single && $cap == 1 ) || ( !$allow_single && $allow_two && $cap == 2 ) ) {
            // translators: Description for rule that flags names that are only 1 character
            $short_names_description = __( 'Flags if the first or last name is only 1 character.', 'user-account-monitor' );
        } elseif ( !$allow_single && !$allow_two && $cap == 2 ) {
            // translators: Description for rule that flags names that are 1 or 2 characters
            $short_names_description = __( 'Flags if the first or last name is only 1 or 2 characters.', 'user-account-monitor' );
        } elseif ( $allow_single && !$allow_two && $cap == 2 ) {
            // translators: Description for rule that flags names that are exactly 2 characters
            $short_names_description = __( 'Flags if the first or last name is exactly 2 characters.', 'user-account-monitor' );
        } elseif ( $allow_single && !$allow_two && $cap > 2 ) {
            // translators: %d is the maximum character length that still triggers a flag (e.g., "3", meaning 2 or 3 characters trigger the flag)
            $short_names_description = sprintf( __( 'Flags if the first or last name is fewer than or equal to %d characters, but more than 1 character.', 'user-account-monitor' ), $cap );
        } elseif ( $allow_single && $allow_two && $cap > 2 ) {
            // translators: %d is the maximum character length that still triggers a flag (e.g., "4", meaning 3 or 4 characters trigger the flag)
            $short_names_description = sprintf( __( 'Flags if the first or last name is fewer than or equal to %d characters, but more than 2 characters.', 'user-account-monitor' ), $cap );
        } elseif ( !$allow_single && !$allow_two && $cap > 2 ) {
            // translators: %d is the maximum character length that triggers a flag (e.g., "3", meaning 1–3 characters trigger the flag)
            $short_names_description = sprintf( __( 'Flags if the first or last name is fewer than or equal to %d characters.', 'user-account-monitor' ), $cap );
        } else {
            // translators: Generic fallback description when none of the specific rules match
            $short_names_description = __( 'Flags very short first or last names.', 'user-account-monitor' );
        }

        // Options array
        $options = [
            [
                'key'        => 'admin_flag',
                'title'      => __( 'Manually Flagged', 'user-account-monitor' ),
                'comments'   => __( 'A manually added flag that is added by an administrator when marked as suspicious.', 'user-account-monitor' ),
                'field_type' => 'hidden',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'excessive_uppercase',
                'title'      => __( 'Excessive Uppercase Letters', 'user-account-monitor' ),
                'comments'   => __( 'Flags if there are more than 5 uppercase letters in a first or last name, only if the name is not in all caps.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'no_vowels',
                'title'      => __( 'No Vowels', 'user-account-monitor' ),
                'comments'   => __( 'Flags if there are no vowels in names longer than 5 characters.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'consonant_cluster',
                'title'      => __( 'Consonant Clusters', 'user-account-monitor' ),
                'comments'   => __( 'Flags if there are 6 or more consecutive consonants in the name.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'numbers',
                'title'      => __( 'Numbers', 'user-account-monitor' ),
                'comments'   => __( 'Flags if names contain numeric characters.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'special_characters',
                'title'      => __( 'Special Characters', 'user-account-monitor' ),
                'comments'   => __( 'Flags if names contain characters other than letters, numbers and dashes.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'similar_first_last_name',
                'title'      => __( 'Similar First and Last Name', 'user-account-monitor' ),
                'comments'   => __( 'Flags if the first and last name are exactly the same or one includes the other.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'short_names',
                'title'      => __( 'Very Short Names', 'user-account-monitor' ),
                'comments'   => $short_names_description,
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'invalid_email_domain',
                'title'      => __( 'Invalid Email Domain', 'user-account-monitor' ),
                'comments'   => __( 'Flags if the email domain is disposable or not registered.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'excessive_periods_email',
                'title'      => __( 'Excessive Periods in Email', 'user-account-monitor' ),
                'comments'   => __( 'Flags if the email address contains more than 3 periods.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'url_in_username',
                'title'      => __( 'Username Contains URL', 'user-account-monitor' ),
                'comments'   => __( 'Flags if the username contains http, https, or www.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ],
            [
                'key'        => 'spam_words',
                'title'      => __( 'Known Spam Words', 'user-account-monitor' ),
                'comments'   => __( 'Flags if spam trigger words are found in user bio or name.', 'user-account-monitor' ),
                'field_type' => 'checkbox',
                'sanitize'   => 'sanitize_checkbox',
                'section'    => 'checks',
                'default'    => TRUE,
            ]
        ];

        // Allow developers to add custom options
        $options = apply_filters( 'uamonitor_flag_settings', $options );
        
        // Filter out other options if we only want one in particular
        if ( $return_option ) {
            $options = array_filter( $options, function( $option ) use ( $return_option ) {
                return $option[ 'key' ] === $return_option;
            } );
        }

        // Return
        if ( $return_keys_only ) {
            $option_keys = [];
            foreach ( $options as $option ) {
                $option_keys[] = $option[ 'key' ];
            }
            return $option_keys;
        }
        return $options;
    } // End options()


    /**
     * Check if name fields contain excessive uppercase letters.
     *
     * @param WP_User|string $user_or_name
     * @return bool
     */
    public function check_excessive_uppercase( $user_or_name ) {
        $names = [];

        if ( is_string( $user_or_name ) ) {
            $names[] = $user_or_name;
        } elseif ( is_object( $user_or_name ) ) {
            foreach ( $this->names_to_scan as $field ) {
                if ( $field == 'display_name' ) {
                    continue; // Skip display_name for excessive uppercase check
                }
                $names[] = $user_or_name->$field ?? '';
            }
        }

        foreach ( $names as $name ) {
            if ( !$name ) {
                continue;
            }
            if ( strtoupper( $name ) === $name ) {
                continue;
            }
            preg_match_all( '/[A-Z]/', $name, $matches );
            if ( count( $matches[0] ) > 5 ) {
                return true;
            }
        }

        return false;
    } // End check_excessive_uppercase()


    /**
     * Check if name fields contain no vowels and are longer than 5 characters.
     *
     * If a WP_User is passed, checks all fields in $this->names_to_scan.
     * If a string is passed, checks only that name.
     *
     * @param WP_User|string $user_or_name
     * @return bool
     */
    public function check_no_vowels( $user_or_name ) {
        $names = [];

        if ( is_string( $user_or_name ) ) {
            $names[] = $user_or_name;
        } elseif ( is_object( $user_or_name ) ) {
            foreach ( $this->names_to_scan as $field ) {
                $names[] = $user_or_name->$field ?? '';
            }
        }

        foreach ( $names as $name ) {
            if ( strlen( $name ) > 5 && !preg_match( '/[aeiouAEIOU]/', $name ) ) {
                return true;
            }
        }

        return false;
    } // End check_no_vowels()


    /**
     * Check if name fields contain consonant clusters.
     *
     * If a WP_User is passed, checks all fields in $this->names_to_scan.
     * If a string is passed, checks only that name.
     *
     * @param WP_User|string $user_or_name
     * @return bool
     */
    public function check_consonant_cluster( $user_or_name ) {
        $names = [];

        if ( is_string( $user_or_name ) ) {
            $names[] = $user_or_name;
        } elseif ( is_object( $user_or_name ) ) {
            foreach ( $this->names_to_scan as $field ) {
                $names[] = $user_or_name->$field ?? '';
            }
        }

        foreach ( $names as $index => $name ) {
            if ( !$name ) {
                continue;
            }

            $field = is_string( $user_or_name ) ? 'string' : $this->names_to_scan[ $index ];

            if ( $field === 'display_name' && is_email( $name ) ) {
                continue;
            }

            $pattern = apply_filters( 'uamonitor_consonant_cluster_pattern', '/[bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ]{6,}/', $field, $user_or_name );

            if ( preg_match( $pattern, $name ) ) {
                return true;
            }
        }

        return false;
    } // End check_consonant_cluster()


    /**
     * Check if name fields contain numeric characters.
     *
     * If a WP_User is passed, checks all fields in $this->names_to_scan.
     * If a string is passed, checks only that name.
     *
     * @param WP_User|string $user_or_name
     * @return bool
     */
    public function check_numbers( $user_or_name ) {
        $names = [];

        if ( is_string( $user_or_name ) ) {
            $names[] = $user_or_name;
        } elseif ( is_object( $user_or_name ) ) {
            foreach ( $this->names_to_scan as $field ) {
                $names[] = $user_or_name->$field ?? '';
            }
        }

        foreach ( $names as $index => $name ) {
            if ( !$name ) {
                continue;
            }

            $field = is_string( $user_or_name ) ? 'string' : $this->names_to_scan[ $index ];

            if ( $field === 'display_name' && is_email( $name ) ) {
                continue;
            }

            if ( preg_match( '/\d/', $name ) ) {
                return true;
            }
        }

        return false;
    } // End check_numbers()


    /**
     * Check if name fields contain special characters.
     *
     * If a WP_User is passed, checks all fields in $this->names_to_scan.
     * If a string is passed, checks only that name.
     *
     * @param WP_User|string $user_or_name
     * @return bool
     */
    public function check_special_characters( $user_or_name ) {
        $names = [];

        if ( is_string( $user_or_name ) ) {
            $names[] = $user_or_name;
        } elseif ( is_object( $user_or_name ) ) {
            foreach ( $this->names_to_scan as $field ) {
                $names[] = $user_or_name->$field ?? '';
            }
        }

        foreach ( $names as $index => $name ) {
            if ( !$name ) {
                continue;
            }

            $field = is_string( $user_or_name ) ? 'string' : $this->names_to_scan[ $index ];

            $default_name_pattern  = '/[^\p{L}\,\.\'\-\s]/u';
            $default_email_pattern = '/[^a-zA-Z0-9@\.\-\_\+]/';

            $pattern = ( $field === 'display_name' && is_email( $name ) )
                ? apply_filters( 'uamonitor_special_characters_email_pattern', $default_email_pattern, $name, $field, $user_or_name )
                : apply_filters( 'uamonitor_special_characters_name_pattern', $default_name_pattern, $name, $field, $user_or_name );

            if ( preg_match( $pattern, $name ) ) {
                return true;
            }
        }

        return false;
    } // End check_special_characters()


    /**
     * Check if first and last names are similar.
     *
     * If a WP_User is passed, uses first_name and last_name properties.
     * If an array is passed, expects keys 'first_name' and 'last_name'.
     *
     * @param WP_User|array $user_or_names
     * @return bool
     */
    public function check_similar_first_last_name( $user_or_names ) {
        if ( is_array( $user_or_names ) ) {
            $first = strtolower( $user_or_names[ 'first_name' ] ?? '' );
            $last  = strtolower( $user_or_names[ 'last_name' ] ?? '' );
        } elseif ( is_object( $user_or_names ) ) {
            $first = strtolower( $user_or_names->first_name ?? '' );
            $last  = strtolower( $user_or_names->last_name ?? '' );
        } else {
            return false;
        }

        if ( !$first || !$last ) {
            return false;
        }

        if ( strlen( $first ) === 1 || strlen( $last ) === 1 ) {
            return false;
        }

        if ( $first === $last ) {
            return true;
        }

        return strpos( $first, $last ) !== false || strpos( $last, $first ) !== false;
    } // End check_similar_first_last_name()


    /**
     * Check if any name field is shorter than 3 characters.
     *
     * If a WP_User is passed, checks all fields in $this->names_to_scan.
     * If a string is passed, checks only that name.
     *
     * @param WP_User|string $user_or_name
     * @return bool
     */
    public function check_short_names( $user_or_name ) {
        $names = [];

        if ( is_string( $user_or_name ) ) {
            $names[] = $user_or_name;
        } elseif ( is_object( $user_or_name ) ) {
            foreach ( $this->names_to_scan as $field ) {
                $names[] = $user_or_name->$field ?? '';
            }
        }

        $rules        = apply_filters( 'uamonitor_short_name_length', $this->very_short_name_args );
        $cap          = absint( $rules[ 'cap' ] );
        $allow_single = !empty( $rules[ 'allow_single' ] );
        $allow_two    = !empty( $rules[ 'allow_two' ] );

        foreach ( $names as $name ) {
            $length = strlen( $name );

            // Case: Flags 1-character names if single not allowed and cap is 1
            if ( !$allow_single && $cap == 1 && $length === 1 ) {
                return true;
            }

            // Case: Flags 1-character names if single not allowed, two allowed, and cap is 2
            if ( !$allow_single && $allow_two && $cap == 2 && $length === 1 ) {
                return true;
            }

            // Case: Flags 1- or 2-character names if both single and two are not allowed and cap is 2
            if ( !$allow_single && !$allow_two && $cap == 2 && ( $length === 1 || $length === 2 ) ) {
                return true;
            }

            // Case: Flags exactly 2-character names if single allowed, two not allowed, and cap is 2
            if ( $allow_single && !$allow_two && $cap == 2 && $length === 2 ) {
                return true;
            }

            // Case: Flags 2-character+ names up to cap if single allowed, two not allowed, and cap > 2
            if ( $allow_single && !$allow_two && $cap > 2 && $length > 1 && $length <= $cap ) {
                return true;
            }

            // Case: Flags 3-character+ names up to cap if single and two are allowed, and cap > 2
            if ( $allow_single && $allow_two && $cap > 2 && $length > 2 && $length <= $cap ) {
                return true;
            }

            // Case: Flags all names up to cap if neither single nor two allowed, and cap > 2
            if ( !$allow_single && !$allow_two && $cap > 2 && $length <= $cap ) {
                return true;
            }
        }

        return false;
    } // End check_short_names()


    /**
     * Check if the user's email domain is invalid.
     *
     * @param WP_User|string $user_or_email
     * @return bool
     */
    public function check_invalid_email_domain( $user_or_email  ) {
        $email = '';

        if ( is_string( $user_or_email ) ) {
            $email = $user_or_email;
        } elseif ( is_object( $user_or_email ) && isset( $user_or_email->user_email ) ) {
            $email = $user_or_email->user_email;
        }
        
        if ( !is_email( $email ) ) {
            return true;
        }

        $domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );
        if ( !$domain ) {
            return true;
        }

        // Allowlist
        $allow_domains = apply_filters( 'uamonitor_allow_email_domains', [] );
        if ( in_array( $domain, $allow_domains, true ) ) {
            return false;
        }

        // Disposable domains
        $disposable_domains = apply_filters( 'uamonitor_disposable_domains', [
            'mailinator.com',
            '10minutemail.com',
            'guerrillamail.com',
            'trashmail.com',
            'tempmail.com',
            'yopmail.com'
        ] );
        if ( in_array( strtolower( $domain ), $disposable_domains, true ) ) {
            return true;
        }

        // Cached valid domains
        $valid_domains = get_transient( 'uamonitor_valid_email_domains' );
        if ( is_array( $valid_domains ) && in_array( $domain, $valid_domains, true ) ) {
            return false;
        }

        // Check DNS
        $is_valid = false;
        try {
            $is_valid = @checkdnsrr( $domain, 'MX' ) || @checkdnsrr( $domain, 'A' );
        } catch ( \Throwable $e ) {}

        if ( $is_valid ) {
            if ( !is_array( $valid_domains ) ) {
                $valid_domains = [];
            }

            if ( !in_array( $domain, $valid_domains, true ) ) {
                $valid_domains[] = $domain;
                set_transient( 'uamonitor_valid_email_domains', $valid_domains, MONTH_IN_SECONDS );
            }

            return false;
        }

        return true;
    } // End check_invalid_email_domain()


    /**
     * Check if an email contains excessive periods.
     *
     * If a WP_User is passed, uses user_email.
     * If a string is passed, treats it as an email address.
     *
     * @param WP_User|string $user_or_email
     * @return bool
     */
    public function check_excessive_periods_email( $user_or_email ) {
        $email = '';

        if ( is_string( $user_or_email ) ) {
            $email = $user_or_email;
        } elseif ( is_object( $user_or_email ) && isset( $user_or_email->user_email ) ) {
            $email = $user_or_email->user_email;
        }

        if ( !is_email( $email ) ) {
            return false;
        }

        $username = substr( $email, 0, strpos( $email, '@' ) );
        $period_count = substr_count( $username, '.' );
        return $period_count > 3;
    } // End check_excessive_periods_email()


    /**
     * Check if username contains a full URL.
     *
     * If a WP_User is passed, uses user_login.
     * If a string is passed, treats it as username.
     *
     * @param WP_User|string $user_or_username
     * @return bool
     */
    public function check_url_in_username( $user_or_username ) {
        $username = '';

        if ( is_string( $user_or_username ) ) {
            $username = $user_or_username;
        } elseif ( is_object( $user_or_username ) && isset( $user_or_username->user_login ) ) {
            $username = $user_or_username->user_login;
        }

        return preg_match( '/\b(?:http|https|www)\b/i', $username ) === 1;
    } // End check_url_in_username()


    /**
     * Check if user bio or display name contains known spam words.
     *
     * Supports WP_User object or array of user properties.
     *
     * @param WP_User|array $user
     * @return bool
     */
    public function check_spam_words( $user ) {
        $default_spam_words = [
            // Generic marketing
            'buy now', 'click here', 'limited time', 'special offer', 'order now', 'shop now',
            'free trial', 'get started', 'try now', 'subscribe now', 'instant access',
            'act now', 'save big', 'don’t miss out', 'sign up', 'join now',

            // Financial
            'cash', 'money back', '100% free', 'guaranteed', 'no risk', 'risk-free', 'winner',
            'earn', 'income', 'double your', 'investment', 'profit', 'easy money',
            'work from home', 'be your own boss',

            // Urgency
            'urgent', 'immediately', 'limited supply', 'only a few left', 'while supplies last',
            'today only', 'last chance', 'final notice',

            // Prizes and incentives
            'bonus', 'prize', 'free gift', 'reward', 'giveaway', 'claim now', 'congratulations',
            'you’ve been selected', 'exclusive deal', 'you’re a winner',

            // Health/medications
            'weight loss', 'miracle', 'cure', 'anti-aging', 'treatment', 'pain relief',
            'no prescription', 'pharmacy', 'viagra', 'levitra', 'cialis',

            // Scam/phishing indicators
            'act now', 'dear friend', 'confidential', 'no obligation', 'click below',
            'password', 'bank account', 'credit card', 'ssn', 'login', 'verify your account',
            'update your information',

            // Adult/spam content
            'xxx', 'sex', 'nude', 'adult', 'porn', 'escort', 'camgirl', 'hot girls',
            'dating', 'hookup', 'live chat', 'strip',

            // Domains often seen in spam (omit if checking separately)
            'bit.ly', 'tinyurl', 'goo.gl', 't.co',

            // Cryptocurrency and high-risk finance
            'bitcoin', 'crypto', 'blockchain', 'forex', 'binary options', 'nft', 'token sale',

            // SEO and web services
            'seo', 'backlinks', 'traffic', 'page rank', 'optimize your site', 'site audit',
            'web design', 'email list', 'mailing list', 'marketing campaign',

            // Language used by bots
            'great post', 'thanks for sharing', 'check out my site', 'visit my blog',
            'contact me', 'looking for friends', 'nice article', 'helpful info', 'i love this',
            'interesting content', 'amazing write-up', 'follow me',

            // Foreign marketing phrases (optional)
            'acheter maintenant', 'meilleur prix', 'angebot', 'jetzt kaufen', 'compra ahora',
            'precio bajo'
        ];

        $default_spam_words = apply_filters( 'uamonitor_default_spam_words', $default_spam_words );

        $saved_list = get_option( 'uamonitor_spam_words_list', '' );

        if ( strlen( trim( $saved_list ) ) > 0 ) {
            $saved_words = preg_split( '/[\s,]+/', trim( $saved_list ) );
        } else {
            $saved_words = [];
        }

        $spam_words = array_unique( array_merge( $default_spam_words, $saved_words ) );

        $haystack = '';

        if ( is_array( $user ) ) {
            $haystack = strtolower( $user[ 'description' ] ?? '' );
            foreach ( $this->names_to_scan as $name_key ) {
                if ( ! empty( $user[ $name_key ] ) ) {
                    $haystack .= ' ' . strtolower( $user[ $name_key ] );
                }
            }
            $user_id = $user[ 'ID' ] ?? 0;
        } elseif ( is_object( $user ) ) {
            $haystack = strtolower( $user->description ?? '' );
            foreach ( $this->names_to_scan as $name_key ) {
                if ( ! empty( $user->$name_key ) ) {
                    $haystack .= ' ' . strtolower( $user->$name_key );
                }
            }
            $user_id = $user->ID ?? 0;
        } else {
            return false;
        }

        $found_words = [];

        foreach ( $spam_words as $word ) {
            $word = trim( $word );
            if ( $word === '' ) {
                continue;
            }

            $escaped_word = preg_quote( $word, '/' );
            $pattern = '/\b' . str_replace( '\ ', '\s+', $escaped_word ) . '\b/i';

            if ( preg_match( $pattern, $haystack ) ) {
                $found_words[] = $word;
            }
        }

        do_action( 'uamonitor_spam_words_found', $user_id, $found_words );

        return !empty( $found_words );
    } // End check_spam_words()

}