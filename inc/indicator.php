<?php 
/**
 * Count indicator
 */


/**
 * Define Namespaces
 */
namespace PluginRx\UserAccountMonitor;



/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Instantiate the class
 */
add_action( 'init', function() {
	(new Indicator())->init();
} );


/**
 * The class
 */
class Indicator {

    /**
     * Meta keys
     *
     * @var string
     */
    public $meta_key_suspicious = 'suspicious';


    /**
     * Transient key for cached count
     *
     * @var string
     */
    public $transient_key = 'uamonitor_flagged_user_count';


    /**
     * Nonce
     *
     * @var string
     */
    private $nonce_filter = 'uamonitor_nonce_filter';


    /**
     * Load on init
     */
    public function init() {

        // Invalidate cache on user meta changes
        add_action( 'update_user_meta', [ $this, 'maybe_invalidate_cache' ], 10, 3 );
        add_action( 'added_user_meta',  [ $this, 'maybe_invalidate_cache' ], 10, 3 );
        add_action( 'deleted_user_meta', [ $this, 'maybe_invalidate_cache' ], 10, 3 );
        
        // Hook into admin_menu to modify the Users menu label.
        add_action( 'admin_menu', [ $this, 'add_suspicious_count_to_users_menu' ], 999 );

        // Notice at top of users page
        add_action( 'admin_notices', [ $this, 'show_flagged_user_count_notice' ] );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

    } // End init()


    /**
     * Count how many users are flagged as suspicious.
     *
     * Flagged means user meta key 'suspicious' exists and is not 'cleared'.
     *
     * @return int Number of flagged users.
     */
    public function count_flagged_users() {
        $transient_key = $this->transient_key;
        $cached = get_transient( $transient_key );

        if ( $cached !== false ) {
            return (int) $cached;
        }

        $args = [
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => 'suspicious',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => 'suspicious',
                    'value'   => 'cleared',
                    'compare' => '!=',
                ],
            ],
            'fields'      => 'ID',
            'number'      => -1,
            'count_total' => true,
        ];

        $user_query = new \WP_User_Query( $args );
        $count = (int) $user_query->get_total();

        set_transient( $transient_key, $count, HOUR_IN_SECONDS );

        return $count;
    } // End count_flagged_users()


    /**
     * Invalidate the flagged user count cache if the 'suspicious' meta key is modified.
     *
     * @param int $meta_id 
     * @param int $user_id
     * @param string $meta_key
     *
     * @return void
     */
    public function maybe_invalidate_cache( $meta_id, $user_id, $meta_key ) {
        if ( $meta_key === $this->meta_key_suspicious ) {
            delete_transient( $this->transient_key );
        }
    } // End maybe_invalidate_cache()


    /**
     * Add suspicious users count indicator bubble to the Users admin menu label.
     *
     * @param array $menu The admin menu array.
     * @return void
     */
    public function add_suspicious_count_to_users_menu( $menu ) {
        global $menu;

        $count = $this->count_flagged_users();
        if ( $count > 0 ) {
            $menu_slug = 'users.php';

            foreach ( $menu as &$menu_item ) {
                if ( isset( $menu_item[2] ) && $menu_item[2] === $menu_slug ) {
                    $menu_item[0] .= '<span class="uamonitor-flagged-count count-' . intval( $count ) . '"><span class="flagged-count">' . intval( $count ) . '</span></span>';
                    break;
                }
            }
        }
    } // End add_suspicious_count_to_users_menu()


    /**
     * Show a flagged user count notice at the top of the Users admin screen.
     *
     * @return void
     */
    public function show_flagged_user_count_notice() {
        global $pagenow;

        if ( $pagenow !== 'users.php' ) {
            return;
        }

        $filter = isset( $_GET[ $this->meta_key_suspicious ] ) ? sanitize_text_field( wp_unslash( $_GET[ $this->meta_key_suspicious ] ) ) : '';
        if ( $filter === 'flagged' ) {
            return;
        }

        $count = $this->count_flagged_users();
        if ( $count === 0 ) {
            return;
        }

        $url = add_query_arg( [
            $this->meta_key_suspicious => 'flagged',
            'uamonitor_filter_nonce' => wp_create_nonce( $this->nonce_filter ),
        ], admin_url( 'users.php' ) );

        printf(
            '<div class="notice notice-error is-dismissible uamonitor-flagged-notice">
                <p>%s <a href="%s">%s</a></p>
            </div>',
            sprintf(
                wp_kses_post( __( 'There are currently <strong><span id="uamonitor-flagged-count">%d</span></strong> flagged user account(s).', 'user-account-monitor' ) ),
                $count
            ),
            esc_url( $url ),
            esc_html__( 'View flagged users', 'user-account-monitor' )
        );
    } // End show_flagged_user_count_notice()


    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueue_scripts() {
        // CSS
		wp_enqueue_style( UAMONITOR_TEXTDOMAIN . '-styles', UAMONITOR_CSS_PATH . 'backend.css', [], UAMONITOR_VERSION );
    } // End enqueue_scripts()
}
