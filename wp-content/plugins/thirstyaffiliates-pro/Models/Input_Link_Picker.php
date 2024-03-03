<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;


use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Input_Link_Picker module.
 *
 * @since 1.4.0
 */
class Input_Link_Picker implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Input_Link_Picker.
     *
     * @since 1.4.0
     * @access private
     * @var Settings_Extension
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.4.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.4.0
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
     * @since 1.4.0
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
     * @since 1.4.0
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
     * Input link picker search.
     *
     * @since 1.4.0
     * @access private
     *
     * @param string $keyword Keyword to search.
     * @param int    $paged   Page of query to load.
     * @param array  $exclude Post IDs array list to exclude.
     * @return array Affiliate links result data.
     */
    private function _input_link_picker_search( $keyword = '' , $paged = 1 , $exclude = array() ) {

        $data = array();
        $args = array(
            'post_type'      => Plugin_Constants::AFFILIATE_LINKS_CPT,
            'post_status'    => 'publish',
            's'              => $keyword,
            'fields'         => 'ids',
            'paged'          => $paged,
            'post__not_in'   => $exclude,
            'posts_per_page' => get_option( 'posts_per_page' , 10 )
        );

        $query    = new \WP_Query( $args );
        $link_ids = $query->posts;

        foreach ( $link_ids as $link_id ) {

            $thirstylink = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_affiliate_link( $link_id );

            $data[ $link_id ] = array(
                'name'      => mb_strimwidth( $thirstylink->get_prop( 'name' ) , 0 , 35 , "..." ),
                'slug'      => mb_strimwidth( $thirstylink->get_prop( 'slug' ) , 0 , 25 , "..." ),
                'permalink' => $thirstylink->get_prop( 'permalink' )
            );
        }

        return array(
            'data'  => $data,
            'total' => (int) $query->found_posts,
            'count' => (int) $query->post_count
        );
    }

    /**
     * AJAX trigger link health checker cron manually in settings page.
     *
     * @since 1.3.0
     * @access public
     */
    public function ajax_input_link_picker_search() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ] , 'tap_input_link_picker_search' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            $keyword = isset( $_POST[ 'keyword' ] ) ? sanitize_text_field( $_POST[ 'keyword' ] ) : '';
            $paged   = isset( $_POST[ 'paged' ] ) ? sanitize_text_field( $_POST[ 'paged' ] ) : 1;
            $result  = $this->_input_link_picker_search( $keyword , $paged );

            $response = array_merge( array( 'status' => 'success' ) , $result );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.4.0
     * @access public
     * @inherit ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_input_link_picker' , 'yes' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_tap_input_link_picker_search' , array( $this , 'ajax_input_link_picker_search' ) );

    }
    /**
     * Execute model.
     *
     * @implements ThirstyAffiliates_Pro\Interfaces\Model_Interface
     *
     * @since 1.4.0
     * @access public
     */
    public function run() {
    }

}
