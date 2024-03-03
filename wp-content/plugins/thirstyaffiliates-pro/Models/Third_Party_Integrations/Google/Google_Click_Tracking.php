<?php
namespace ThirstyAffiliates_Pro\Models\Third_Party_Integrations\Google;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

// Data Models
use ThirstyAffiliates\Models\Affiliate_Link;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the CSV Import/Export module.
 *
 * @since 1.2.0
 */
class Google_Click_Tracking implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Settings_Extension.
     *
     * @since 1.2.0
     * @access private
     * @var Settings_Extension
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.2.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.2.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Class constructor.
     *
     * @since 1.2.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

        $main_plugin->add_to_all_plugin_models( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @since 1.2.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Settings_Extension
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }

    /**
     * Get the tracking script JS function name.
     *
     * @since 1.2.0
     * @access private
     *
     * @return string JS function name.
     */
    private function get_tracking_script_function_name() {

        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        $script_type  = get_option( 'tap_google_click_tracking_script' , 'universal_ga' );

        switch( $script_type ) {

            case 'gtm' :
                $ga_func_name = 'dataLayer.push';
                break;

            case 'legacy_ga' :
                $ga_func_name = '_gaq.push';
                break;

            case 'universal_ga' :
                $is_yoast     = is_plugin_active( 'google-analytics-for-wordpress/googleanalytics.php' ) ? true : false;
                $custom_func  = get_option( 'tap_universal_ga_custom_func' );
                $ga_func_name = $is_yoast ? '__gaTracker' : 'ga';
                $ga_func_name = $custom_func ? esc_attr( $custom_func ) : $ga_func_name;
                break;

            case 'gtag_ga' :
            default:
                $ga_func_name = 'gtag';
                break;
        }

        return $ga_func_name;
    }

    public function load_script() {

        if ( get_option( 'tap_enable_google_click_tracking' , 'yes' ) != 'yes' ) {
            return;
        }

        wp_enqueue_script( 'tap-gct', $this->_constants->JS_ROOT_URL() . 'app/tap-gct.js', array( 'jquery', 'ta_main_js' ), Plugin_Constants::VERSION, true );

        $action_name = get_option( 'tap_google_click_tracking_action_name' );
        $page_uri = isset( $_SERVER[ 'REQUEST_URI' ] ) ? $_SERVER[ 'REQUEST_URI' ] : '';
        $page_slug = apply_filters( 'tap_gct_clean_page_slug' , true ) ? preg_replace( '/\?.*/' , '' , $page_uri ) : $page_uri; // Clean the URI by removing all query strings
        $ga_func_name = $this->get_tracking_script_function_name();

        wp_localize_script( 'tap-gct', 'tap_gct_vars', array(
            'action_name' => $action_name ? $action_name : __( 'Affiliate Link' , 'thirstyaffiliates-pro' ),
            'page_slug' => $page_slug,
            'home_url' => home_url(),
            'script_type' => get_option( 'tap_google_click_tracking_script' , 'universal_ga' ),
            'ga_func_name' => $ga_func_name,
            'event_action' => get_option( 'tap_google_click_tracking_event_action_value' , 'link_uri' )
        ) );

    }

    /**
     * Make sure ta.js script is enqueued when GCT module is enabled.
     *
     * @since 1.2.0
     * @access public
     *
     * @param bool $enable Toggle to check if ta.js is enqueued or not.
     * @return bool Filtered toggle to check if ta.js is enqueued or not.
     */
    public function enqueue_ta_js( $enable ) {

        return ( get_option( 'tap_enable_google_click_tracking' , 'yes' ) === 'yes' ) ? true : $enable;
    }




    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Execute model.
     *
     * @implements ThirstyAffiliates_Pro\Interfaces\Model_Interface
     *
     * @since 1.2.0
     * @access public
     */
    public function run() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_google_click_tracking' , 'yes' ) !== 'yes' )
            return;

        add_action( 'wp_enqueue_scripts' , array( $this , 'load_script' ) );
        add_filter( 'ta_enqueue_tajs_script' , array( $this , 'enqueue_ta_js' ) );
    }

}
