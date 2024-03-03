<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates\Models\Affiliate_Link;
use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;
use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Link_Scheduler module.
 *
 * @since 1.2.0
 */
class Link_Scheduler implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Link_Scheduler.
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
     * Implement link schedule on redirect.
     *
     * @since 1.2.0
     * @since 1.3.0 Moved previous code to a helper function. see $this->_helper_functions->get_thirstylink_schedule.
     * @access public
     *
     * @param string         $redirect_url URL for the user to be redirected.
     * @param Affiliate_Link $thirstylink  Affiliate Link object.
     * @return string Processed URL with link schedule for the user to be redirected.
     */
    public function implement_link_schedule( $redirect_url , $thirstylink ) {

        $thirstylink_schedule = $this->_helper_functions->get_thirstylink_schedule( $thirstylink );

        if ( is_string( $thirstylink_schedule ) ) {
            if ( $thirstylink_schedule == 'early' ) {
                add_filter( 'ta_filter_redirect_type' , array( $this , 'before_start_redirect_type' ) );
                $redirect_url = html_entity_decode( $this->get_before_start_redirect( $thirstylink ) );
            } elseif ( $thirstylink_schedule == 'expire' ) {
                add_filter( 'ta_filter_redirect_type' , array( $this , 'after_expire_redirect_type' ) );
                $redirect_url = html_entity_decode( $this->get_after_expire_redirect( $thirstylink ) );
            }
        }

        return $redirect_url;
    }

    /**
     * Filter before start redirect type.
     *
     * @since 1.2.0
     * @access public
     *
     * @param string $redirect_type Redirect type.
     * @return string Filtered redirect type.
     */
    public function before_start_redirect_type( $redirect_type ) {

        return ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_global_before_start_redirect_type' , '302' );
    }

    /**
     * Filter before start redirect type.
     *
     * @since 1.2.0
     * @access public
     *
     * @param string $redirect_type Redirect type.
     * @return string Filtered redirect type.
     */
    public function after_expire_redirect_type( $redirect_type ) {

        return ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_global_after_expire_redirect_type' , '302' );
    }

    /**
     * Get before start redirect URL.
     *
     * @since 1.2.0
     * @access private
     *
     * @param Affiliate_Link $thirstylink  Affiliate Link object.
     * @return string Before start redirect URL.
     */
    private function get_before_start_redirect( $thirstylink ) {

        $before_start_redirect = $thirstylink->get_prop( 'before_start_redirect' );
        return $before_start_redirect ? $before_start_redirect : ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_global_before_start_redirect_url' , home_url('/') );
    }

    /**
     * Get after expire redirect URL.
     *
     * @since 1.2.0
     * @access private
     *
     * @param Affiliate_Link $thirstylink  Affiliate Link object.
     * @return string After expire redirect URL.
     */
    private function get_after_expire_redirect( $thirstylink ) {

        $after_expire_redirect = $thirstylink->get_prop( 'after_expire_redirect' );
        return $after_expire_redirect ? $after_expire_redirect : ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_global_after_expire_redirect_url' , home_url('/') );
    }

    /**
     * Prevent save click data when link has expired.
     *
     * @since 1.2.0
     * @access public
     *
     * @param bool           $toggle      Filter toggle value.
     * @param Affiliate_Link $thirstylink Affiliate Link object.
     * @return bool Filtered toggle value.
     */
    public function disable_save_click_data_on_link_expire( $toggle , $thirstylink ) {

        $timezone    = new \DateTimeZone( $this->_helper_functions->get_site_current_timezone() );
        $today       = new \DateTime( "now" , $timezone );
        $expire_date = \DateTime::createFromFormat( 'Y-m-d' , $thirstylink->get_prop( 'link_expire_date' ) , $timezone );

        return ( $expire_date && $today >= $expire_date ) ? true : $toggle;
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

        if ( get_option( 'tap_enable_link_scheduler' , 'yes' ) !== 'yes' )
            return;

        add_filter( 'ta_filter_redirect_url' , array( $this , 'implement_link_schedule' ) , 10 , 2 );
        add_filter( 'ta_filter_before_save_click' , array( $this , 'disable_save_click_data_on_link_expire' ) , 20 , 2 );
    }

}
