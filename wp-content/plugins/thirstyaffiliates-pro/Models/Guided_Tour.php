<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Activatable_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

// Data Models
use ThirstyAffiliates\Models\Affiliate_Link;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Guided_Tour module.
 *
 * @since 1.0.0
 */
class Guided_Tour implements Model_Interface , Activatable_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Settings_Extension.
     *
     * @since 1.0.0
     * @access private
     * @var Settings_Extension
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.0.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.0.0
     * @access private
     * @var Helper_Functions
     */
    private $_helper_functions;

    /**
     * Property that urls of the guided tour screens.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_urls = array();

    /**
     * Property that houses the screens of the guided tour.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_screens = array();




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Class constructor.
     *
     * @since 1.0.0
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
     * @since 1.0.0
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
     * Define guided tour pages.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_guided_tour_pages() {

        $this->_urls = apply_filters( 'tap_guided_tour_pages' , array(
            'plugins'                            => admin_url( 'plugins.php' ),
            'thirstylink'                        => admin_url( 'post-new.php?post_type=thirstylink' ),
            'edit-tap-event-notification'        => admin_url( 'edit-tags.php?taxonomy=tap-event-notification&post_type=thirstylink' ),
            'thirstylink_page_amazon'            => admin_url( 'edit.php?post_type=thirstylink&page=amazon' ),
            'tap_csv_importer'                   => admin_url( 'admin.php?import=tap_csv_importer' ),
            'thirstylink_page_thirsty-reports'   => admin_url( 'edit.php?post_type=thirstylink&page=thirsty-reports' ),
            'tap_google_click_tracking_settings' => admin_url( 'edit.php?post_type=thirstylink&page=thirsty-settings&tab=tap_google_click_tracking_settings' ),
            'ta_modules_settings'                => admin_url( 'edit.php?post_type=thirstylink&page=thirsty-settings&tab=ta_modules_settings' ),
            'edit-thirstylink'                   => admin_url( 'edit.php?post_type=thirstylink' ),
        ) );

        $this->_screens = apply_filters( 'tap_guided_tours' , array(
            'plugins' => array(
                'elem'  => '#menu-posts-thirstylink .menu-top',
                'html'  => __( '<h3> Thanks for activating ThirstyAffiliates Pro!</h3>
                               <p>The Pro add-on for ThirstyAffiliates contains all the advanced features that you’ve been dreaming of.<p>
                               <p>Would you like to go on a quick guided tour of these advanced features?</p>
                               <p>It takes less than a minute and you’ll know more about what is possible with ThirstyAffiliates Pro.</p>', 'thirstyaffiliates-pro' ),
                'prev'  => null,
                'next'  => $this->get_tour_navigation( 'plugins' , true ),
                'edge'  => 'left',
                'align' => 'left',
            ),
            'thirstylink' => $this->define_edit_thirstylink_panels(),
            'edit-tap-event-notification' => array(
                'elem'  => '#menu-posts-thirstylink .wp-submenu .current',
                'html'  => __( '<h3>As mentioned, Event Notifications can alert you or others when certain events happen with your affiliate links.</h3>
                               <p>This screen lets you define how those notifications work.</p>
                               <p>Give you Event Notification a name (for internal reference), select the type and define the trigger value.</p>
                               <p>Once you create an Event Notification you can assign it to any affiliate links that need it.</p>', 'thirstyaffiliates-pro' ),
                'prev'  => $this->get_tour_navigation( 'edit-tap-event-notification' ),
                'next'  => $this->get_tour_navigation( 'edit-tap-event-notification' , true ),
                'edge'  => 'left',
                'align' => 'left',
            ),
            'thirstylink_page_amazon' => array(
                'elem'  => '#menu-posts-thirstylink .wp-submenu .current',
                'html'  => __( '<h3>Amazon is a very popular program for affiliates due to their massive range of products and services.</h3>
                               <p>Now you can import Amazon products as affiliate links in ThirstyAffiliates and they will be automatically setup with your codes and ready to be used in your content.</p>
                               <p>There is some additional configuration required for the Amazon importer to work with Amazon’s API, so please check the settings area after the tour to set it up.</p>
                               <p>We are also working with additional partners to introduce other new API importers to assist you with finding products to promote!</p>', 'thirstyaffiliates-pro' ),
                'prev'  => $this->get_tour_navigation( 'thirstylink_page_amazon' ),
                'next'  => $this->get_tour_navigation( 'thirstylink_page_amazon' , true ),
                'edge'  => 'left',
                'align' => 'left',
            ),
            'admin' => array(
                'tap_csv_importer' => array(
                    'elem'  => '#wpbody-content h2',
                    'html'  => __( '<h3>You can also import and export affiliate links via CSV file.</h3>
                                   <p>This is handy for managing affiliate links across multiple sites.</p>
                                   <p>It handles all the core ThirstyAffiliates data along with any additional meta data you want to import against your links.</p>', 'thirstyaffiliates-pro' ),
                    'prev'  => $this->get_tour_navigation( 'tap_csv_importer' ),
                    'next'  => $this->get_tour_navigation( 'tap_csv_importer' , true ),
                    'edge'  => 'top',
                    'align' => 'left',
                ),
            ),
            'thirstylink_page_thirsty-reports' => array(
                'elem'  => '#menu-posts-thirstylink .wp-submenu .current',
                'html'  => __( '<h3>Additional reports have been added to the Reports section.</h3>
                               <p>In the free ThirstyAffiliates you can report on all your links or specific links, but in Pro, you can now also report on categories. This is great for seeing how a set of related links are performing.</p>
                               <p>Additionally, there is another report available for Geolocation links with an amazing interactive map interface.</p>
                               <p>You can also export your report data to a CSV file for further manipulation in programs like Excel and Numbers.</p>', 'thirstyaffiliates-pro' ),
                'prev'  => $this->get_tour_navigation( 'thirstylink_page_thirsty-reports' ),
                'next'  => $this->get_tour_navigation( 'thirstylink_page_thirsty-reports' , true ),
                'edge'  => 'left',
                'align' => 'left',
            ),
            'thirstylink_page_thirsty-settings' => array(
                'tap_google_click_tracking_settings' => array(
                    'elem'  => '.nav-tab-wrapper .tap_google_click_tracking_settings',
                    'html'  => __( '<h3>Click tracking sends an event to your Google Analytics when someone clicks on an affiliate link.</h3>
                                   <p>This is handy for seeing affiliate link click data in your Google Analytics reports.</p>', 'thirstyaffiliates-pro' ),
                    'prev'  => $this->get_tour_navigation( 'tap_google_click_tracking_settings' ),
                    'next'  => $this->get_tour_navigation( 'tap_google_click_tracking_settings' , true ),
                    'edge'  => 'top',
                    'align' => 'left',
                ),
                'ta_modules_settings' => array(
                    'elem'  => '.nav-tab-wrapper .ta_modules_settings',
                    'html'  => __( '<h3>This is the modules area where you can switch certain features on/off.</h3>
                                   <p>You might notice this has quite a few new modules in here now that you have ThirstyAffiliates Pro installed.</p>
                                   <p>All modules are enabled by default but if you find you aren’t using some features we encourage you to switch that feature off to save resources and speed up the plugin.</p>
                                   <p>The Htaccess module is the only module that is not enabled by default as this is a special feature for advanced users. If you know how .htaccess works and your server technology is compatible, ThirstyAffiliates can transfer the redirects of your affiliate links there.</p>
                                   <p>However, be aware that any links in your .htaccess will not be recorded in your click statistics reports.</p>', 'thirstyaffiliates-pro' ),
                    'prev'  => $this->get_tour_navigation( 'ta_modules_settings' ),
                    'next'  => $this->get_tour_navigation( 'ta_modules_settings' , true ),
                    'edge'  => 'top',
                    'align' => 'left',
                ),
            ),
            'edit-thirstylink' => array(
                'elem'  => '#menu-posts-thirstylink .wp-submenu .current',
                'html'  => __( '<h3>This concludes the ThirstyAffiliates Pro features tour.</h3>
                               <p>We hope you have enjoyed the tour and get tons of value from our Pro features!</p>
                               <p>If you have any problems or questions about them please get in touch with support.</p>', 'thirstyaffiliates-pro' ),
                'prev'  => $this->get_tour_navigation( 'edit-thirstylink' ),
                'next'  => null,
                'edge'  => 'left',
                'align' => 'left',
            ),
        ) );
    }

    /**
     * Filter tour urls and remove modules when they are not active.
     *
     * @since 3.0.0
     * @access public
     *
     * @param array $urls List of tour urls.
     * @return Filtered list of tour urls.
     */
    public function filter_navigation_urls( $urls ) {

        $modules = array(
            'tap_enable_event_notification'    => 'edit-tap-event-notification',
            'tap_enable_azon'                  => 'thirstylink_page_amazon',
            'tap_enable_csv_importer'          => 'tap_csv_importer',
            'ta_enable_stats_reporting_module' => 'thirstylink_page_thirsty-reports',
            'tap_enable_google_click_tracking' => 'tap_google_click_tracking_settings',
        );

        foreach ( $modules as $module => $screen_id ) {

            if ( get_option( $module , 'yes' ) !== 'yes' )
                unset( $urls[ $screen_id ] );
        }

        return $urls;
    }

    /**
     * Filter tour urls and remove modules when they are not active.
     *
     * @since 3.0.0
     * @access public
     *
     * @param string  $screen_id Screen ID.
     * @param boolean $next      Next toggle.
     * @return Filtered list of tour urls.
     */
    private function get_tour_navigation( $screen_id , $next = false ) {

        $navigation = array_keys( $this->_urls );
        $key        = array_search( $screen_id , $navigation );
        $nav_key    = ( $next ) ? $key + 1 : $key - 1;

        if ( ! isset( $navigation[ $nav_key ] ) )
            return;

        $nav_screen = $navigation[ $nav_key ];

        return $this->_urls[ $nav_screen ];
    }

    /**
     * Define edit affiliate link page guided tour panel.
     *
     * @since 1.0.0
     * @access private
     */
    private function define_edit_thirstylink_panels() {

        $panels     = array();
        $navigation = array( '@intro' );

        if ( get_option( 'tap_enable_autolinker' , 'yes' ) == 'yes' )
            $navigation[] = '@autolinker';

        if ( get_option( 'tap_enable_geolocation' , 'yes' ) == 'yes' )
            $navigation[] = '@geolocation';

        if ( get_option( 'tap_enable_event_notification' , 'yes' ) == 'yes' )
            $navigation[] = '@event_notification';

        $raw_panels = array(
            'intro' => array(
                'elem'  => '#menu-posts-thirstylink .wp-submenu .current',
                'html'  => __( '<h3> ThirstyAffiliates Pro adds a number of new sections to your link edit screen to accommodate the new advanced features.</h3>
                               <p>The new sections on this screen are:</p>
                               <ol><li>Autolink Keywords</li>
                               <li>Geolocations URLs</li>
                               <li>Event Notifications</li></ol>', 'thirstyaffiliates-pro' ),
                'prev'  => $this->_urls[ 'plugins' ],
                'next'  => '@autolinker',
                'edge'  => 'left',
                'align' => 'left',
                'width' => 450
            ),
            'autolinker' => array(
                'elem'  => '#tap-autolink-keywords-metabox h2',
                'html'  => __( '<h3> One of the most frustrating parts of monetizing a site with lots of content is going back through that content to add appropriate affiliate links.</h3>
                               <p>The Autolink Keywords feature lets you define a number of keywords that should be automatically linked to your affiliate link. This makes light work of monetizing your existing content and takes away much of the burden of manually linking new articles.</p>
                               <p>You can place limitations on the automatic linking as well. Such as a limit on the number of times a keyword can be linked, whether links should appear in headings and if they should be randomly placed or not.</p>', 'thirstyaffiliates-pro' ),
                'prev'  => '@intro',
                'next'  => '@geolocation',
                'edge'  => 'left',
                'align' => 'left',
                'width' => 300
            ),
            'geolocation' => array(
                'elem'  => '#tap-geolocation-urls-metabox h2',
                'html'  => __( '<h3>Does your site cater to audiences in multiple countries? A great way to increase your earnings is to localise your affiliate links for the visitor’s country.</h3>
                               <p>When visitors are referred to a store that is local to them, as opposed to an overseas store, they will be more likely to buy which results in more commissions for you.</p>
                               <p>You can use this section to define alternate destination URLs for your affiliate link based on the detected country of the visitor.</p>', 'thirstyaffiliates-pro' ),
                'prev'  => '@autolinker',
                'next'  => '@event_notification',
                'edge'  => 'left',
                'align' => 'left',
                'width' => 300
            ),
            'event_notification' => array(
                'elem'  => '#tap-event-notificationdiv h2',
                'html'  => __( '<h3>Event Notifications can alert you when pre-defined events happen with your affiliate links.</h3>
                               <p>This included things like if a link receives a certain number of clicks.</p>
                               <p>Or if a link exceeds a certain threshold of clicks over a 24 hour period.</p>
                               <p>Next, we’ll see how to create new Event Notifications.</p>', 'thirstyaffiliates-pro' ),
                'prev'  => '@geolocation',
                'next'  => $this->get_tour_navigation( 'thirstylink' , true ),
                'edge'  => 'right',
                'align' => 'left'
            ),
        );

        foreach ( $raw_panels as $panel_id => $panel ) {

            $key = array_search( '@' . $panel_id , $navigation );

            if ( $key === false )
                continue;

            $panel[ 'prev' ]     = $key > 0 ? $navigation[ $key - 1 ] : $this->get_tour_navigation( 'thirstylink' );
            $panel[ 'next' ]     = ( $key == count( $navigation ) - 1 ) ? $this->get_tour_navigation( 'thirstylink' , true ) : $navigation[ $key + 1 ];
            $panels[ $panel_id ] = $panel;
        }

        return $panels;
    }

    /**
     * Get current screen.
     *
     * @since 1.0.0
     * @access public
     *
     * @return array Current guide tour screen.
     */
    public function get_current_screen() {

        $screen    = get_current_screen();
        $tab       = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : '';
        $import    = isset( $_GET[ 'import' ] ) ? sanitize_text_field( $_GET[ 'import' ] ) : '';

        if ( ! isset( $this->_screens[ $screen->id ] ) || empty( $this->_screens[ $screen->id ] ) )
            return array();

        // settings screen
        if ( $screen->id == 'thirstylink_page_thirsty-settings' && $tab && isset( $this->_screens[ $screen->id ][ $tab ] ) )
            return $this->_screens[ $screen->id ][ $tab ];

        // csv import screen
        if ( $screen->id == 'admin' && $import && $this->_screens[ $screen->id ][ $import ] )
            return $this->_screens[ $screen->id ][ $import ];

        return $this->_screens[ $screen->id ];
    }

    /**
     * Get all guide tour screens.
     *
     * @since 1.0.0
     * @access public
     *
     * @return array List of all guide tour screens.
     */
    public function get_screens() {

        return $this->_screens;
    }

    /**
     * AJAX close guided tour.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_close_guided_tour() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! check_ajax_referer( 'tap-close-guided-tour' , 'nonce' , false ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Security Check Failed' , 'thirstyaffiliates-pro' ) );
        else {

            update_option( 'tap_guided_tour_status' , 'close' );
            $response = array( 'status' => 'success' );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Set the guided tour status option as 'open' on activation.
     *
     * @since 1.0.0
     * @access private
     */
    private function set_guided_tour_status_open() {

        if ( get_option( 'tap_guided_tour_status' ) )
            return;

        update_option( 'tap_guided_tour_status' , 'open' );
    }




    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.0.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Activatable_Interface
     */
    public function activate() {

        $this->set_guided_tour_status_open();
    }

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.0.0
     * @access public
     * @inherit ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        if ( get_option( 'tap_guided_tour_status' ) !== 'open' || get_option( 'ta_guided_tour_status' ) === 'open'  )
            return;

        add_action( 'wp_ajax_tap_close_guided_tour' , array( $this , 'ajax_close_guided_tour' ) );
    }

    /**
     * Execute model.
     *
     * @implements ThirstyAffiliates\Interfaces\Model_Interface
     *
     * @since 1.0.0
     * @access public
     */
    public function run() {

        if ( get_option( 'tap_guided_tour_status' ) !== 'open' || get_option( 'ta_guided_tour_status' ) === 'open'  )
            return;

        add_filter( 'tap_guided_tour_pages' , array( $this , 'filter_navigation_urls' ) , 10 , 1 );
        $this->define_guided_tour_pages();
    }

}
