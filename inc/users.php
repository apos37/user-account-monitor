<?php 
/**
 * Users page
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserAccountMonitor;
use PluginRx\UserAccountMonitor\IndividualUser;
use PluginRx\UserAccountMonitor\Indicator;


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
    (new Users())->init();
} );


/**
 * The class
 */
class Users {

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
    private $nonce_filter = 'uamonitor_nonce_filter';


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

        // Filter
        add_action( 'manage_users_extra_tablenav', [ $this, 'add_user_filter_dropdown' ], 9999999 );
        add_action( 'manage_users_network_extra_tablenav', [ $this, 'add_user_filter_dropdown' ], 9999999 );
        add_action( 'pre_get_users', [ $this, 'filter_users_list_query' ] );

        // Add a row action
        add_filter( 'user_row_actions', [ $this, 'add_clear_action_link' ], 10, 2 );
        add_filter( 'ms_user_row_actions', [ $this, 'add_clear_action_link' ], 10, 2 );

        // User column
        add_filter( 'manage_users_columns', [ $this, 'user_column' ] );
        add_filter( 'manage_users-network_columns', [ $this, 'user_column' ] );
        add_action( 'admin_head-users.php', [ $this, 'user_column_style' ] );
        add_action( 'admin_head-users-network.php', [ $this, 'user_column_style' ] );
        add_action( 'manage_users_custom_column', [ $this, 'user_column_content' ], 10, 3 );

        // Scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End init()


    /**
     * Add a dropdown filter to the Users admin screen for suspicious status.
     *
     * Options include: All Users, Not Checked, Cleared, Flagged.
     *
     * @return void
     */
    public function add_user_filter_dropdown( $which ) {
        if ( $which !== 'top' ) {
            return;
        }

        $value = isset( $_GET[ $this->meta_key_suspicious ] ) ? sanitize_text_field( wp_unslash( $_GET[ $this->meta_key_suspicious ] ) ) : '';
        $nonce = wp_create_nonce( $this->nonce_filter );

        printf(
            '<div class="alignleft actions">
                <label class="screen-reader-text" for="uamonitor_suspicious">%s</label>
                <select name="' . $this->meta_key_suspicious . '" id="uamonitor_suspicious">
                    <option value="">%s</option>
                    <option value="not_checked"%s>%s</option>
                    <option value="cleared"%s>%s</option>
                    <option value="flagged"%s>%s</option>
                </select>
                <input type="hidden" name="uamonitor_filter_nonce" value="%s" />
                <input type="submit" class="button" value="%s" />
            </div>',
            esc_html__( 'Filter by Status', 'user-account-monitor' ),
            esc_html__( 'All Users', 'user-account-monitor' ),
            selected( $value, 'not_checked', false ),
            esc_html__( 'Not Checked', 'user-account-monitor' ),
            selected( $value, 'cleared', false ),
            esc_html__( 'Cleared', 'user-account-monitor' ),
            selected( $value, 'flagged', false ),
            esc_html__( 'Suspicious', 'user-account-monitor' ),
            esc_attr( $nonce ),
            esc_attr__( 'Filter', 'user-account-monitor' )
        );
    } // End add_user_filter_dropdown()


    /**
     * Modify the user query based on the suspicious filter dropdown.
     *
     * Uses the 'suspicious' usermeta key:
     * - 'cleared'     ⇢ user has been manually cleared.
     * - anything else ⇢ user is considered flagged.
     * - not set       ⇢ user has not been checked.
     *
     * @param WP_User_Query $query The current user query object.
     * @return void
     */
    public function filter_users_list_query( $query ) {
        if ( !is_admin() ) {
            return;
        }

        // Only modify the main query on the users.php admin page.
        if ( !$query instanceof \WP_User_Query ) {
            return;
        }

        $screen = get_current_screen();
        if ( !$screen || $screen->id !== 'users' ) {
            return;
        }

        $filter = isset( $_GET[ $this->meta_key_suspicious ] ) ? sanitize_text_field( wp_unslash( $_GET[ $this->meta_key_suspicious ] ) ) : '';
        $nonce  = isset( $_GET[ 'uamonitor_filter_nonce' ] ) ? sanitize_text_field( wp_unslash( $_GET[ 'uamonitor_filter_nonce' ] ) ) : '';

        if ( $filter === '' || !wp_verify_nonce( $nonce, $this->nonce_filter ) ) {
            return;
        }

        $meta_query = [];

        if ( $filter === 'cleared' ) {
            $meta_query[] = [
                'key'     => 'suspicious',
                'value'   => 'cleared',
                'compare' => '=',
            ];
        } elseif ( $filter === 'flagged' ) {
            $meta_query[] = [
                'key'     => 'suspicious',
                'value'   => 'cleared',
                'compare' => '!=',
            ];
        } elseif ( $filter === 'not_checked' ) {
            $meta_query[] = [
                'key'     => 'suspicious',
                'compare' => 'NOT EXISTS',
            ];
        }

        // Merge with any existing meta_query
        if ( !empty( $query->query_vars[ 'meta_query' ] ) ) {
            $meta_query = array_merge( $query->query_vars[ 'meta_query' ], $meta_query );
        }
        
        $query->set( 'meta_query', $meta_query );
    } // End filter_users_list_query


    /**
     * Add clear action link
     *
     * @param array $actions
     * @param WP_User $user
     * @return array
     */
    public function add_clear_action_link( $actions, $user ) {
        // Allow manual scan per user
        $actions[ 'uamonitor_scan' ] = sprintf(
            '<a href="#" class="uamonitor-scan" data-userid="%d" data-method="scan">%s</a>',
            $user->ID,
            esc_html__( 'Check for Flags', 'user-account-monitor' )
        );

        // Allow manual marking
        $suspicious = (new IndividualUser())->check( $user->ID, true );
        if ( $suspicious !== 'cleared' ) {
            $actions[ 'uamonitor_clear' ] = sprintf(
                '<a href="#" class="uamonitor-clear" data-userid="%d" data-method="clear">%s</a>',
                $user->ID,
                esc_html__( 'Mark Not Suspicious', 'user-account-monitor' )
            );
        } else {
            $actions[ 'uamonitor_flag' ] = sprintf(
                '<a href="#" class="uamonitor-flag" data-userid="%d" data-method="flag">%s</a>',
                $user->ID,
                esc_html__( 'Mark Suspicious', 'user-account-monitor' )
            );
        }

        return $actions;
    } // End add_clear_action_link()


    /**
     * Add a run scan button to the top of all post types
     *
     * @return void
     */
    public function run_scan_button() {
        $nonce = wp_create_nonce( $this->nonce_scan );
        ?>
        <script>
            jQuery( $ => { 
                const currentURL = window.location.href;
                var btnURL;
                var btnText;
                if ( currentURL.includes( 'blinks=true' ) && currentURL.includes( '_wpnonce=<?php echo esc_html( $nonce ); ?>' ) ) {
                    btnURL = '<?php echo esc_url( remove_query_arg( [ 'blinks', '_wpnonce' ] ) ); ?>';
                    btnText = 'Stop Scanning';
                } else {
                    btnURL = '<?php echo esc_url( add_query_arg( [ 'blinks' => 'true', '_wpnonce' => $nonce ] ) ); ?>';
                    btnText = 'Scan for Broken Links';
                }
                $( '.wrap > a.page-title-action' ).after( `<a id="bln-run-scan" href="${btnURL}" class="page-title-action" style="margin-left: 10px;"><span class="text">${btnText}</span><span class="done"></span></a>` );
            } )
        </script>
        <?php
    } // End run_scan_button()


    /**
     * Add the user column
     *
     * @param array $columns
     * @return array
     */
    public function user_column( $columns ) {
        $columns[ 'suspicious' ] = __( 'Suspicious', 'user-account-monitor' );
        return $columns;
    } // End user_column()


    /**
     * Column width
     *
     * @return void
     */
    public function user_column_style() {
        echo '<style>.column-suspicious{width: 10%}</style>';
    } // End users_column_style()


    /**
     * Column content
     *
     * @param string $value
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public function user_column_content( $value, $column_name, $user_id ) {
        if ( $column_name == 'suspicious' ) {
            $suspicious = (new IndividualUser())->check( $user_id, true );

            // They have been cleared - not suspicious
            if ( $suspicious === 'cleared' ) {
                return '<em data-suspicious-status="cleared" style="color: green">' . esc_html__( 'Cleared', 'user-account-monitor' ) . '</em>';
                
            // They have been flagged - suspicious
            } elseif ( is_array( $suspicious ) && !empty( $suspicious ) ) {
                $flag_names = [];
                foreach ( $suspicious as $flag ) {
                    foreach ( $this->available_flags as $available_flag ) {
                        if ( $available_flag[ 'key' ] == $flag ) {
                            $flag_names[] = $available_flag[ 'title' ];
                        }
                    }
                }
                return '<strong data-suspicious-status="flagged" style="color: red;">' . esc_html( implode( ', ', $flag_names ) ) . '</strong>';
            } else {
                return esc_html__( 'Not Checked', 'user-account-monitor' );
            }
        }
    } // End column_content()


    /**
	 * Enqueue JQuery
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
        // Only on the users page
        if ( $hook !== 'users.php' ) {
            return;
        }

        // Enqueue jquery
        $handle = UAMONITOR_TEXTDOMAIN . 'users-js';
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( $handle, UAMONITOR_JS_PATH . 'users.js', [ 'jquery' ], UAMONITOR_SCRIPT_VERSION, true );
        wp_localize_script( $handle, 'uamonitor_users', [
            'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            'nonce_scan'      => wp_create_nonce( $this->nonce_scan ),
            'nonce_clear'     => wp_create_nonce( $this->nonce_clear ),
            'hide_cleared'    => filter_var( get_option( 'uamonitor_hide_cleared', false ), FILTER_VALIDATE_BOOLEAN ),
            'recheck_cleared' => filter_var( get_option( 'uamonitor_recheck_cleared', false ), FILTER_VALIDATE_BOOLEAN ),
            'already_flagged' => (new Indicator())->count_flagged_users(),
            'text'            => [
                'start'      => __( 'Scan for Suspicious Accounts', 'user-account-monitor' ),
                'stop'       => __( 'Stop Scanning', 'user-account-monitor' ),
                'scanning'   => __( 'Scanning', 'user-account-monitor' ),
                'cleared'    => __( 'Cleared', 'user-account-monitor' ),
                'complete'   => __( 'Scan Complete', 'user-account-monitor' ),
                'error'      => __( 'Error - Could Not Complete Scan', 'user-account-monitor' ),
                'mark_clear' => __( 'Mark Not Suspicious', 'user-account-monitor' ),
                'mark_flag'  => __( 'Mark Suspicious', 'user-account-monitor' ),
                'manual'     => __( 'Manually Flagged', 'user-account-monitor' )
            ]
        ] );
        
        // Enqueue css
        wp_enqueue_style( UAMONITOR_TEXTDOMAIN . '-css', UAMONITOR_CSS_PATH . 'users.css', [], UAMONITOR_SCRIPT_VERSION );
    } // End enqueue_scripts()

}