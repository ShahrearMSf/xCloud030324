<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;


if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic of all Advanced_Reporting registered in the plugin.
 *
 * @since 1.0.0
 */
class Advanced_Reporting implements Model_Interface , Initiable_Interface {

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
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.2.2
     * @access private
     * @var ThirstyAffiliates\Models\Stats_Reporting
     */
    private $_stats_model;




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
     * Register reports admin interfaces.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of admin interfaces.
     * @return array Filtered list of admin interfaces.
     */
    public function register_reports_admin_interfaces( $interfaces ) {

        $interfaces[ 'geolocation' ]        = 'manage_options';
        $interfaces[ 'stats_table' ]        = 'manage_options';
        $interfaces[ 'keyword_report' ]     = 'manage_options';
        $interfaces[ 'link_health_report' ] = 'manage_options';

        return $interfaces;
    }




    /*
    |--------------------------------------------------------------------------
    | Link / Category Reports (Extended)
    |--------------------------------------------------------------------------
    */

    /**
     * Fetch category performance data by date range.
     *
     * @since 1.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param string $start_date Report start date. Format: YYYY-MM-DD hh:mm:ss
     * @param string $end_date   Report end date. Format: YYYY-MM-DD hh:mm:ss
     * @param array  $link_ids    Affiliate Link post ID
     * @return string/array Link click meta data value.
     */
    private function get_category_performance_data( $start_date , $end_date , $link_ids ) {

        global $wpdb;

        $link_clicks_db = $wpdb->prefix . Plugin_Constants::LINK_CLICK_DB;
        $link_ids_str   = implode( ', ' , $link_ids );
        $query          = "SELECT * FROM $link_clicks_db WHERE date_clicked between '$start_date' and '$end_date'";
        $query         .= ( $link_id ) ? " and link_id IN ( $link_ids )" : "";

        return $wpdb->get_results( $query );
    }

    /**
     * AJAX fetch report by category.
     *
     * @since 1.0.0
     * @since 1.2.2 Add support for fetching report by browser timezone.
     * @since 1.3.1 Add total clicks.
     * @since 1.3.2 Changed method of setting stats model browser zone string.
     * @access public
     */
    public function ajax_fetch_report_by_category() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'category' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'thirstyaffiliates-pro' ) );
        else {

            $cat_slug    = sanitize_text_field( $_POST[ 'category' ] );
            $category    = get_term_by( 'slug' , $cat_slug , Plugin_Constants::AFFILIATE_LINKS_TAX );
            $range_txt   = isset( $_POST[ 'range' ] ) ? sanitize_text_field( $_POST[ 'range' ] ) : '7day';
            $start_date  = isset( $_POST[ 'start_date' ] ) ? sanitize_text_field( $_POST[ 'start_date' ] ) : '';
            $end_date    = isset( $_POST[ 'end_date' ] ) ? sanitize_text_field( $_POST[ 'end_date' ] ) : '';
            $timezone    = isset( $_POST[ 'timezone' ] ) ? sanitize_text_field( $_POST[ 'timezone' ] ) : '';

            $this->_stats_model->set_browser_zone_str( $timezone );

            // fetch all affiliate link IDs under the selected category using the helper function from TA
            $link_ids = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->search_affiliate_links_query( '' , -1 , $cat_slug );

            // get range details from TA Stats_Reporting class
            $range = $this->_stats_model->get_report_range_details( $range_txt , $start_date , $end_date );

            // get report data from TA Stats_Reporting class
            $data = $this->_stats_model->prepare_data_for_flot( $range , $link_ids );

            $response = array(
                    'status'       => 'success',
                    'label'        => $category->name,
                    'slug'         => __( 'Category: ' , 'thirstyaffiliates-pro' ) . $category->slug,
                    'report_data'  => $data,
                    'total_clicks' => $this->_stats_model->count_total_clicks_from_flot_data( $data )
                );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Display add category form in the sidebar.
     *
     * @since 1.0.0
     * @access public
     */
    public function add_category_report_form_html() {

        $current_range = isset( $_GET[ 'range' ] ) ? sanitize_text_field( $_GET[ 'range' ] ) : '7day';
        $start_date    = isset( $_GET[ 'start_date' ] ) ? sanitize_text_field( $_GET[ 'start_date' ] ) : '';
        $end_date      = isset( $_GET[ 'end_date' ] ) ? sanitize_text_field( $_GET[ 'end_date' ] ) : '';
        $categories    = get_terms( Plugin_Constants::AFFILIATE_LINKS_TAX , array(
            'hide_empty' => false,
        ) );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/add-category-legend.php' );
    }

    /**
     * Add 24hours to the report range navigation.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $range_nav Array list of all report range navigation.
     * @return array Filtered array list of all report range navigation.
     */
    public function add_24hours_to_range_nav( $range_nav ) {

        $range_nav[ '24hours' ] = __( '24 Hours' , 'thirstyaffiliates-pro' );
        return $range_nav;
    }

    /**
     * Filter for 24 hours report so the time of start date in range will not be set to zero.
     *
     * @since 1.0.0
     * @access public
     *
     * @param bool  $toggle Set time to zero toggle.
     * @param array $range  Report range data.
     * @return bool Filtered set time to zero toggle.
     */
    public function disable_set_start_date_to_zero_24hours_report( $toggle , $range ) {

        if ( $range[ 'type' ] !== '24hours' )
            return $toggle;

        return false;
    }

    /**
     * Register report range data for the 24hours range.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array  $data  Report range data.
     * @param string $range Report range type.
     * @return array Filtered report range data.
     */
    public function register_24hours_report_range_data( $data , $range ) {

        if ( $range !== '24hours' )
            return $data;

        $zone_str   = $this->_stats_model->get_report_timezone_string();
        $timezone   = new \DateTimeZone( $zone_str );
        $now        = new \DateTime( 'now' , $timezone );
        $start_date = new \DateTime( 'now -1 day' , $timezone );

        // set minutes and seconds to zero
        $start_date->setTime( $start_date->format( "H" ) , 0 , 0 );
        $now->setTime( $start_date->format( "H" ) , 59 , 59 );

        $data = array(
            'type'       => '24hours',
            'start_date' => $start_date,
            'end_date'   => $now
        );

        return $data;
    }

    /**
     * Set the timestamp incrementor for 24hours range type report data.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int   $incrementor Timestamp incrementor.
     * @param array $range       Report range data.
     * @return array Filtered report range data.
     */
    public function set_incrementor_for_24hours_range_data( $incrementor , $range ) {

        if ( $range[ 'type' ] !== '24hours' )
            return $incrementor;

        return 60 * 60;
    }

    /**
     * Modify the report details JS variables value for the 24hours range type.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $range Report range data.
     */
    public function modify_js_report_details_24hours_flot_data( $range ) {

        if ( $range[ 'type' ] !== '24hours' )
            return;

        ?>
        <script type="text/javascript">
            report_details.timeformat = '%I:%M%p';
            report_details.minTickSize = [ 1 , 'hour' ];
        </script>
        <?php
    }

    /**
     * Save link performance report
     *
     * @since 1.2.0
     * @access private
     *
     * @param array  $reports     Report data to save.
     * @param string $report_name Name of the report (user input).
     * @return array Save report response
     */
    private function save_link_performance_report( $reports , $report_name ) {

        $current_user = wp_get_current_user();

        if ( is_a( $current_user , 'WP_User' ) ) {

            $data = array(
                'report_name' => $report_name,
                'reports'     => $reports,
            );

            $check = add_user_meta( $current_user->ID , 'tap_saved_link_performance_reports' , $data );

            if ( $check )
                $response = array( 'status' => 'success' , 'message' => __( 'Report saved successfully! You will now be able to load this report again after reloading the page.' , 'thirstyaffiliates-pro' ) );
            else
                $response = array( 'status' => 'fail' , 'error_msg' => __( 'Something went wrong while trying to save the report. Please try again.' , 'thirstyaffiliates-pro' ) );

        } else
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );

        return $response;
    }

    /**
     * Load link performance report
     *
     * @since 1.2.0
     * @since 1.3.1 Refactored code and add total clicks to the returned data.
     * @access private
     *
     * @param array $reports Report data to fetch.
     * @param array $range   Report range details.
     * @return array Save report response
     */
    private function load_link_performance_report( $reports , $range ) {

        $report_data = array();

        foreach ( $reports as $key => $report ) {

            switch( $report[ 'type' ] ) {

                case 'link' :
                    $link_ids = array( intval( $report[ 'value' ] ) );
                    break;

                case 'category' :

                    $link_ids = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->search_affiliate_links_query( '' , -1 , $report[ 'value' ] );
                    break;
            }

            $data                = $this->_stats_model->prepare_data_for_flot( $range , $link_ids );
            $total_clicks        = $this->_stats_model->count_total_clicks_from_flot_data( $data );
            $report_data[ $key ] = array( 'plot' => $data , 'total_clicks' => $total_clicks );
        }

        return $report_data;
    }

    /**
     * Delete link performance report
     *
     * @since 1.3.2
     * @access private
     *
     * @param string $report_name Name of the report (user input).
     * @param array  $reports     Report data to save.
     * @return bool True if deleted, otherwise false.
     */
    private function _delete_link_performance_report( $report_name , $reports ) {

        $current_user = wp_get_current_user();
        if ( ! is_a( $current_user , 'WP_User' ) )
            return;

        $data = array(
            'report_name' => $report_name,
            'reports'     => $reports,
        );

        return delete_user_meta( $current_user->ID , 'tap_saved_link_performance_reports' , $data );
    }

    /**
     * AJAX Save link performance report.
     *
     * @since 1.2.0
     * @access public
     */
    public function ajax_save_link_performance_report() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'tap_save_link_performance_report_nonce' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'link_performance_report_name' ] ) || ! $_POST[ 'link_performance_report_name' ] || ! isset( $_POST[ 'reports' ] ) || empty( $_POST[ 'reports' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameters not supplied.' , 'thirstyaffiliates-pro' ) );
        else {

            $report_name = sanitize_text_field( $_POST[ 'link_performance_report_name' ] );
            $reports_raw = $_POST[ 'reports' ];
            $reports     = array();

            // sanitize report data.
            foreach( $reports_raw as $report )
                $reports[] = array_map( 'sanitize_text_field' , $report );

            // save report
            $response = $this->save_link_performance_report( $reports , $report_name );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX load link performance report.
     *
     * @since 1.2.0
     * @since 1.2.2 Add support for fetching report by browser timezone.
     * @since 1.3.2 Changed method of setting stats model browser zone string.
     * @access public
     */
    public function ajax_load_link_performance() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'tap_load_link_performance_report_nonce' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'reports' ] ) || ! is_array( $_POST[ 'reports' ] ) || empty( $_POST[ 'reports' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameters not supplied.' , 'thirstyaffiliates-pro' ) );
        else {

            $reports_raw = $_POST[ 'reports' ];
            $range_txt   = isset( $_POST[ 'range' ] ) ? sanitize_text_field( $_POST[ 'range' ] ) : '7day';
            $start_date  = isset( $_POST[ 'start_date' ] ) ? sanitize_text_field( $_POST[ 'start_date' ] ) : '';
            $end_date    = isset( $_POST[ 'end_date' ] ) ? sanitize_text_field( $_POST[ 'end_date' ] ) : '';
            $timezone    = isset( $_POST[ 'timezone' ] ) ? sanitize_text_field( $_POST[ 'timezone' ] ) : '';
            $reports     = array();

            $this->_stats_model->set_browser_zone_str( $timezone );

            // get report range details.
            $range = $range = $this->_stats_model->get_report_range_details( $range_txt , $start_date , $end_date );

            // sanitize report data.
            foreach( $reports_raw as $key => $report )
                $reports[ $key ] = array_map( 'sanitize_text_field' , $report );

            $response = array(
                'status'      => 'success',
                'report_data' => $this->load_link_performance_report( $reports , $range )

            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX delete link performance report.
     *
     * @since 1.3.2
     * @access public
     */
    public function ajax_delete_link_performance_report() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'tap_delete_link_performance_report_nonce' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'reports' ] ) || ! is_array( $_POST[ 'reports' ] ) || empty( $_POST[ 'reports' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameters not supplied.' , 'thirstyaffiliates-pro' ) );
        else {

            $reports_raw = $_POST[ 'reports' ];
            $report_name = isset( $_POST[ 'report_name' ] ) ? sanitize_text_field( $_POST[ 'report_name' ] ) : '';
            $reports     = array();

            // sanitize report data.
            foreach( $reports_raw as $key => $report ) {

                $temp = array_map( 'sanitize_text_field' , $report );

                if ( isset( $temp[ 'type' ] ) ) unset( $temp[ 'type' ] );

                $reports[ $key ] = $temp;
            }

            $response = array(
                'status' => 'success',
                'check'  => $this->_delete_link_performance_report( $report_name , $reports )
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Load report actions HTML.
     *
     * @since 1.3.2
     * @access public
     */
    public function load_report_actions_html() {

        $actions = apply_filters( 'tap_report_actions' , array(
            'load-link-performance-report' => __( 'Load saved report' , 'thirstyaffiliates-pro' ),
            'save-link-performance-report' => __( 'Save report' , 'thirstyaffiliates-pro' ),
            'add-legend'                   => __( 'Add link report' , 'thirstyaffiliates-pro' ),
            'add-category-legend'          => __( 'Add category report' , 'thirstyaffiliates-pro' )
        ) );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/report-actions.php' );
    }

    /**
     * Loaded report indicator HTML.
     *
     * @since 1.3.2
     * @access public
     */
    public function loaded_report_indicator_html() {

        echo '<div class="loaded-report-indicator"><strong>' . __( 'Loaded report:' , 'thirstyaffiliates-pro' ) . '</strong> <span></span></div>';
    }

    /**
     * Save link performance report form HTML.
     *
     * @since 1.2.0
     * @access public
     */
    public function save_link_performance_report_form_html() {

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/save-link-performance-report.php' );
    }

    /**
     * Load link performance report form HTML.
     *
     * @since 1.2.0
     * @access public
     */
    public function load_link_performance_report_form_html() {

        $current_user  = wp_get_current_user();
        $current_range = isset( $_GET[ 'range' ] ) ? sanitize_text_field( $_GET[ 'range' ] ) : '7day';
        $start_date    = isset( $_GET[ 'start_date' ] ) ? sanitize_text_field( $_GET[ 'start_date' ] ) : '';
        $end_date      = isset( $_GET[ 'end_date' ] ) ? sanitize_text_field( $_GET[ 'end_date' ] ) : '';
        $reports       = get_user_meta( $current_user->ID , 'tap_saved_link_performance_reports' ,false );
        $categories    = get_terms( Plugin_Constants::AFFILIATE_LINKS_TAX , array(
            'hide_empty' => false,
        ) );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/load-link-performance-report.php' );
    }




    /*
    |--------------------------------------------------------------------------
    | Best Performance Report
    |--------------------------------------------------------------------------
    */

    /**
     * Get best link performance report.
     *
     * @since 1.4.0
     * @access private
     *
     * @param array  $range    Report range details.
     * @param string $category Affiliate link category of reports to load.
     * @return array Best performance report data.
     */
    private function _get_best_link_performance_report( $range , $category ) {

        global $wpdb;

        $utc = new \DateTimeZone( 'UTC' );

        $range[ 'start_date' ]->setTimezone( $utc );
        $range[ 'end_date' ]->setTimezone( $utc );

        $clicks_db    = $wpdb->prefix . 'ta_link_clicks';
        $start_date   = $range[ 'start_date' ]->format( 'Y-m-d H:i:s' );
        $end_date     = $range[ 'end_date' ]->format( 'Y-m-d H:i:s' );
        $cat_link_ids = null;

        if ( $category ) {
            $cat_links_arr = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->search_affiliate_links_query( '' , -1 , $category );
            $cat_link_ids  = implode( ',' , $cat_links_arr );

            if ( ! $cat_link_ids ) return array();
        }

        $query  = "SELECT link_id FROM $clicks_db WHERE ( date_clicked between '$start_date' and '$end_date' )";
        $query .= $cat_link_ids ? " AND link_id IN ( $cat_link_ids )" : '';

        $result = $wpdb->get_col( $query );
        $count  = array();

        foreach ( $result as $click_link_id ) {

            if ( get_post_type( $click_link_id ) !== Plugin_Constants::AFFILIATE_LINKS_CPT )
                continue;

            if ( isset( $count[ $click_link_id ] ) )
                $count[ $click_link_id ]++;
            else
                $count[ $click_link_id ] = 1;
        }

        // sort by highest count to lowest.
        uasort( $count , function( $a , $b ) {
            if ($a == $b) return 0;
            return ($a < $b) ? 1 : -1;
        } );

        // get only the top ten best links.
        $best_links = array_slice( $count , 0 , 10 , true );

        return $this->_prepare_links_report_data( $best_links , $range );
    }

    /**
     * AJAX get best link performance report.
     *
     * @since 1.4.0
     * @access public
     */
    public function ajax_get_best_link_performance_report() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'tap_load_link_performance_report_nonce' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            $category   = isset( $_POST[ 'best_performance_category' ] ) ? sanitize_text_field( $_POST[ 'best_performance_category' ] ) : '';
            $range_txt  = isset( $_POST[ 'range' ] ) ? sanitize_text_field( $_POST[ 'range' ] ) : '7day';
            $start_date = isset( $_POST[ 'start_date' ] ) ? sanitize_text_field( $_POST[ 'start_date' ] ) : '';
            $end_date   = isset( $_POST[ 'end_date' ] ) ? sanitize_text_field( $_POST[ 'end_date' ] ) : '';
            $timezone   = isset( $_POST[ 'timezone' ] ) ? sanitize_text_field( $_POST[ 'timezone' ] ) : '';

            $this->_stats_model->set_browser_zone_str( $timezone );

            // get report range details.
            $range = $this->_stats_model->get_report_range_details( $range_txt , $start_date , $end_date );

            // fetch report data.
            $report_data = $this->_get_best_link_performance_report( $range , $category );

            if ( is_array( $report_data ) && ! empty( $report_data ) )
                $response = array( 'status' => 'success', 'report_data' => $report_data );
            else
                $response = array( 'status' => 'fail' , 'error_msg' => __('No report to load.' , 'thirstyaffiliates-pro' ) );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Geolocation Reports
    |--------------------------------------------------------------------------
    */

    /**
     * Register geolocation report.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $reports Array list of all registered reports.
     * @return array Array list of all registered reports.
     */
    public function register_geolocation_reports( $reports ) {

        $reports[ 'geolocation' ] = array(
            'id'      => 'tap_geolocation_report',
            'tab'     => 'geolocation',
            'name'    => __( 'Geolocation' , 'thirstyaffiliates-pro' ),
            'title'   => __( 'Geolocation Report' , 'thirstyaffiliates-pro' ),
            'desc'    => __( 'Total clicks on affiliate links specific on the visitor\'s location over a given period.' , 'thirstyaffiliates-pro' ),
            'content' => function() { $this->get_geolocation_report_content(); }
        );

        return $reports;
    }

    /**
     * Get geolocation report content.
     *
     * @since 1.0.0
     * @since 1.1.3 Removed geolocation data fetch to improve page load speed.
     * @since 1.3.2 Added 24 hours report range option.
     * @access public
     *
     * @return string Geolocation report content.
     */
    public function get_geolocation_report_content() {

        $cpt_slug      = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $current_range = isset( $_GET[ 'range' ] ) ? sanitize_text_field( $_GET[ 'range' ] ) : '7day';
        $start_date    = isset( $_GET[ 'start_date' ] ) ? sanitize_text_field( $_GET[ 'start_date' ] ) : '';
        $end_date      = isset( $_GET[ 'end_date' ] ) ? sanitize_text_field( $_GET[ 'end_date' ] ) : '';
        $range_nav     = apply_filters( 'tap_geolocation_report_nav' , array(
            'year'       => __( 'Year' , 'thirstyaffiliates-pro' ),
            'last_month' => __( 'Last Month' , 'thirstyaffiliates-pro' ),
            'month'      => __( 'This Month' , 'thirstyaffiliates-pro' ),
            '7day'       => __( 'Last 7 Days' , 'thirstyaffiliates-pro' ),
            '24hours'    => __( '24 Hours' , 'thirstyaffiliates-pro' )
        ) );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/geolocation-reports.php' );
    }

    /**
     * Get geolocation report data.
     *
     * @since 1.0.0
     * @since 1.1.3 Changed to a custom SQL query.
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param array $range Report range details.
     * @return array Geolocation report data.
     */
    private function get_geolocation_data( $range ) {

        global $wpdb;

        // set timezone to UTC
        $utc = new \DateTimeZone( 'UTC' );
        $range[ 'start_date' ]->setTimezone( $utc );
        $range[ 'end_date' ]->setTimezone( $utc );

        $cpt_slug   = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $start_date = $range[ 'start_date' ]->format( 'Y-m-d H:i:s' );
        $end_date   = $range[ 'end_date' ]->format( 'Y-m-d H:i:s' );

        // DB tables
        $clicks_db     = $wpdb->prefix . 'ta_link_clicks';
        $clicksmeta_db = $wpdb->prefix . 'ta_link_clicks_meta';

        // build the query.
        $query = "SELECT click.id , cm.meta_value AS user_ip_address FROM $clicks_db AS click
                  INNER JOIN $wpdb->posts AS posts ON ( posts.ID = click.link_id )
                  INNER JOIN $clicksmeta_db AS cm ON ( cm.click_id = click.id AND cm.meta_key = 'user_ip_address' )
                  WHERE click.date_clicked between '$start_date' AND '$end_date'
                  AND posts.post_type = '$cpt_slug'
                  AND posts.post_status = 'publish'
                  GROUP BY click.id ORDER BY click.date_clicked DESC";

        $raw_data  = $wpdb->get_results( $query );
        $temp_data = array();
        $data      = array();

        // return empty array if raw data is empty.
        if ( empty( $raw_data ) )
            return $data;

        foreach ( $raw_data as $click_entry ) {

            $country_code = $this->_helper_functions->get_geolocation_country_by_ip( $click_entry->user_ip_address );

            if ( ! $country_code ) {
                continue;
            }

            $temp_data[ strtolower( $country_code ) ][] = $click_entry->id;
        }

        foreach( $temp_data as $country_code => $ids )
            $data[ $country_code ] = count( $ids );

        return $data;
    }

    /**
     * Get geolocation of a single click entry.
     *
     * @since 1.0.0
     * @since 1.1.0 Added $ip_address and $lowercase parameters. Make sure to only save user_geolocation meta when value is valid.
     * @access public
     *
     * @global \wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param int    $click_id   Click entry row ID.
     * @param string $ip_address User IP address.
     * @param bool   $lowercase  Toggle if lowercase or not.
     * @return string|null Country code (lowercase), or null if not found.
     */
    public function get_click_entry_geolocation( $click_id , $ip_address = '' , $lowercase = true ) {

        global $wpdb;

        $clicks_meta_db = $wpdb->prefix . 'ta_link_clicks_meta';
        $country_code   = $wpdb->get_var( "SELECT meta_value FROM $clicks_meta_db WHERE click_id = $click_id AND meta_key = 'user_geolocation'" );

        if ( $country_code )
            return $lowercase ? strtolower( $country_code ) : $country_code;

        if ( ! $ip_address )
            $ip_address = $wpdb->get_var( "SELECT meta_value FROM $clicks_meta_db WHERE click_id = $click_id AND meta_key = 'user_ip_address'" );

        if ( ! $ip_address )
            return null;

        $country_code = $this->_helper_functions->get_geolocation_country_by_ip( $ip_address );

        if ( ! $country_code) {
            return null;
        }

        $wpdb->insert(
            $clicks_meta_db,
            array(
                'click_id'   => $click_id,
                'meta_key'   => 'user_geolocation',
                'meta_value' => $country_code
            )
        );

        return $lowercase ? strtolower( $country_code ) : $country_code;
    }

    /**
     * Save user geolocation on link click.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array $click_meta Link click meta data.
     * @return array Filtered link click meta data.
     */
    public function save_user_geolocation_on_link_click( $click_meta ) {

        if ( ! is_array( $click_meta ) || ! isset( $click_meta[ 'user_ip_address' ] ) )
            return $click_meta;

        $country_code = $this->_helper_functions->get_geolocation_country_by_ip( $click_meta[ 'user_ip_address' ] );

        if ( $country_code )
            $click_meta[ 'user_geolocation' ] = $country_code;

        return $click_meta;
    }

    /**
     * Get geolocation legend markup.
     *
     * @since 1.1.3
     * @access private
     *
     * @param array $data Geolocation report data.
     * @return string Legend markup.
     */
    private function get_geolocation_legend_markup( $data ) {

        if ( ! is_array( $data ) || empty( $data ) )
            return '<li>' . __( 'No report data to show' , 'thirstyaffiliates-pro' ) . '</li>';

        $markup        = '';
        $all_countries = $this->_helper_functions->get_all_countries();

        array_multisort( $data , SORT_DESC , SORT_NUMERIC );

        foreach( $data as $code => $entry )
            $markup .= '<li>' . $all_countries[ strtoupper( $code ) ] . ': ' . $entry . '</li>';

        return $markup;
    }

    /**
     * AJAX get geolocation data.
     *
     * @since 1.1.3
     * @since 1.3.2 Add support for local browser timezone.
     * @access public
     */
    public function ajax_get_geolocation_data() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        else {

            // set timezone
            $timezone = isset( $_POST[ 'timezone' ] ) ? sanitize_text_field( $_POST[ 'timezone' ] ) : '';
            $this->_stats_model->set_browser_zone_str( $timezone );

            $cpt_slug      = Plugin_Constants::AFFILIATE_LINKS_CPT;
            $current_range = isset( $_POST[ 'range' ] ) ? sanitize_text_field( $_POST[ 'range' ] ) : '7day';
            $start_date    = isset( $_POST[ 'start_date' ] ) ? sanitize_text_field( $_POST[ 'start_date' ] ) : '';
            $end_date      = isset( $_POST[ 'end_date' ] ) ? sanitize_text_field( $_POST[ 'end_date' ] ) : '';
            $range         = $this->_stats_model->get_report_range_details( $current_range , $start_date , $end_date );
            $start_date    = $range[ 'start_date' ]->format( 'F j, Y' );
            $end_date      = $range[ 'end_date' ]->format( 'F j, Y' );

            // get geolocation data
            $data = $this->get_geolocation_data( $range );

            $response = array(
                'status' => 'success',
                'data'   => $data,
                'legend' => $this->get_geolocation_legend_markup( $data ),
                'title'  => sprintf( __( 'Displaying report data from <strong>%s</strong> to <strong>%s</strong>' , 'thirstyaffiliates-pro' ) , $start_date , $end_date )
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | CSV Export Report
    |--------------------------------------------------------------------------
    */

    /**
     * Display export CSV report button.
     *
     * @since 1.0.0
     * @since 1.3.1 Moved CSV headers generation to AJAX. Remove default data parameter and all unneeded variables.
     * @access public
     *
     */
    public function export_csv_html(){

        $report_type = isset( $_GET[ 'range' ] ) ? sanitize_text_field( $_GET[ 'range' ] ) : '7day';
        $today_date  = current_time( 'Y-m-d' );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/export-csv-button.php' );
    }

    /**
     * AJAX Get link performance report CSV headers.
     *
     * @since 1.3.1
     * @access public
     */
    public function ajax_get_link_performance_report_csv_headers() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'plots' ] ) || ! is_array( $_POST[ 'plots' ] ) || empty( $_POST[ 'plots' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'thirstyaffiliates-pro' ) );
        else {

            $timezone_str = isset( $_POST[ 'timezone' ] ) ? sanitize_text_field( $_POST[ 'timezone' ] ) : $this->_helper_functions->get_site_current_timezone();
            $timezone     = new \DateTimeZone( $timezone_str );
            $csv_headers  = array( __( 'Legend' , 'thirstyaffiliates-pro' ) );
            $report_type  = isset( $_POST[ 'type' ] ) ? sanitize_text_field( $_POST[ 'type' ] ) : '7day';

            foreach ( $_POST[ 'plots' ] as $plot ) {

                $date_obj = new \DateTime();
                $date_obj->setTimezone( $timezone );
                $date_obj->setTimestamp( $plot / 1000 );

                switch( $report_type ) {

                    case 'year' :
                        $csv_headers[] = $date_obj->format( 'M-Y' );
                        break;

                    case '24hours' :
                        $csv_headers[] = $date_obj->format( 'h:ia' );
                        break;

                    default :
                        $csv_headers[] = $date_obj->format( 'Y-m-d' );
                        break;
                }
            }

            $response = array( 'status' => 'success' , 'csv_headers' => $csv_headers );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Stats Table
    |--------------------------------------------------------------------------
    */

    /**
     * Register stats table report.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array $reports Array list of all registered reports.
     * @return array Array list of all registered reports.
     */
    public function register_stats_table_report( $reports ) {

        $reports[ 'stats_table' ] = array(
            'id'      => 'tap_stats_table',
            'tab'     => 'stats_table',
            'name'    => __( 'Stats Table' , 'thirstyaffiliates-pro' ),
            'title'   => __( 'Stats Table Report' , 'thirstyaffiliates-pro' ),
            'desc'    => __( 'View click data in a full table report based on a selected time period.' , 'thirstyaffiliates-pro' ),
            'content' => function() { $this->get_stats_table_report_content(); }
        );

        return $reports;
    }

    /**
     * Get stats table report content.
     *
     * @since 1.1.0
     * @since 1.1.3 Removed stats table report data fetch to improve page load speed.
     * @since 1.2.0 Add new required variables for the export CSV button.
     * @since 1.3.2 Removed unused variables. Added 24 hours report range option.
     * @access public
     *
     * @return string Stats table report content.
     */
    public function get_stats_table_report_content() {

        $cpt_slug      = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $today_date    = current_time( 'Y-m-d' );
        $current_range = isset( $_GET[ 'range' ] ) ? sanitize_text_field( $_GET[ 'range' ] ) : '7day';
        $start_date    = isset( $_GET[ 'start_date' ] ) ? sanitize_text_field( $_GET[ 'start_date' ] ) : '';
        $end_date      = isset( $_GET[ 'end_date' ] ) ? sanitize_text_field( $_GET[ 'end_date' ] ) : '';
        $range_nav     = apply_filters( 'tap_geolocation_report_nav' , array(
            'year'       => __( 'Year' , 'thirstyaffiliates-pro' ),
            'last_month' => __( 'Last Month' , 'thirstyaffiliates-pro' ),
            'month'      => __( 'This Month' , 'thirstyaffiliates-pro' ),
            '7day'       => __( 'Last 7 Days' , 'thirstyaffiliates-pro' ),
            '24hours'    => __( '24 Hours' , 'thirstyaffiliates-pro' )
        ) );
        $categories    = get_terms( Plugin_Constants::AFFILIATE_LINKS_TAX , array(
            'hide_empty' => false,
        ) );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/stats-table-report.php' );
    }

    /**
     * Get the total of a basic stats table report (without "search" and link category term).
     *
     * @since 1.1.3
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param array $range Report range data.
     * @return int Query total.
     */
    private function get_stats_table_total( $range ) {

        global $wpdb;

        // set timezone to UTC
        $utc = new \DateTimeZone( 'UTC' );
        $range[ 'start_date' ]->setTimezone( $utc );
        $range[ 'end_date' ]->setTimezone( $utc );

        $cpt_slug   = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $start_date = $range[ 'start_date' ]->format( 'Y-m-d H:i:s' );
        $end_date   = $range[ 'end_date' ]->format( 'Y-m-d H:i:s' );
        $clicks_db  = $wpdb->prefix . 'ta_link_clicks';

        // build the query.
        $query = "SELECT click.id FROM $clicks_db AS click
                  INNER JOIN $wpdb->posts AS posts ON ( posts.ID = click.link_id )
                  WHERE click.date_clicked between '$start_date' AND '$end_date'
                  AND posts.post_type = '$cpt_slug'
                  AND posts.post_status = 'publish'
                  GROUP BY click.id ORDER BY click.date_clicked DESC";

        return intval( $wpdb->get_var( "SELECT COUNT(1) FROM ( $query ) AS combined_table" ) );
    }

    /**
     * Get stats table report data.
     *
     * @since 1.1.0
     * @since 1.1.3 Optimized custom SQL query.
     * @since 1.2.0 Updated code so the "LIMIT" clause will be optional and only run when $limit is set.
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param array  $range   Report range data.
     * @param array  $paged   Current report page to display.
     * @param int    $term_id Category of affiliate links to fetch.
     * @param string $search  Text to search.
     * @param int    $limit   Number of rows to query.
     * @param int    $total   Total number of rows without the limit.
     * @return array Query total and click data.
     */
    private function get_stats_table_data( $range , $paged = 1 , $term_id = 0 , $search = '' , $limit = 25 , $total = 0 ) {

        global $wpdb;

        // set timezone to UTC
        $utc = new \DateTimeZone( 'UTC' );
        $range[ 'start_date' ]->setTimezone( $utc );
        $range[ 'end_date' ]->setTimezone( $utc );

        // DB tables
        $clicks_db     = $wpdb->prefix . 'ta_link_clicks';
        $clicksmeta_db = $wpdb->prefix . 'ta_link_clicks_meta';

        $cpt_slug    = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $start_date  = $range[ 'start_date' ]->format( 'Y-m-d H:i:s' );
        $end_date    = $range[ 'end_date' ]->format( 'Y-m-d H:i:s' );
        $geolocation = get_option( 'tap_enable_geolocation' , 'yes' );
        $offset      = ( $paged - 1 ) * $limit;
        $query       = "SELECT click.*, posts.post_title, posts.post_name,
                        GROUP_CONCAT( cm.meta_key ORDER BY cm.meta_key DESC SEPARATOR '||' ) AS meta_keys,
                        GROUP_CONCAT( cm.meta_value ORDER BY cm.meta_key DESC SEPARATOR '||' ) AS meta_values
                        FROM $clicks_db AS click
                        INNER JOIN $wpdb->posts AS posts ON ( posts.ID = click.link_id )
                        INNER JOIN $clicksmeta_db AS cm ON ( cm.click_id = click.id )";

        // add term relationships table if searching for term_id
        if ( $term_id ) {
            $query .= "\nINNER JOIN $wpdb->term_relationships AS tr ON ( tr.object_id = click.link_id )";
            $query .= "\nINNER JOIN $wpdb->term_taxonomy AS tt ON ( tt.term_taxonomy_id = tr.term_taxonomy_id )";
        }

        $query .= "\nWHERE click.date_clicked between '$start_date' AND '$end_date'
                         AND posts.post_type = '$cpt_slug'
                         AND posts.post_status = 'publish'";

        // set term_id if getting data for specific category
        if ( $term_id ) $query .= "\nAND tt.term_id = $term_id";

        // set query group by.
        $query .= "\nGROUP BY click.id";

        // add custom text search query
        if ( $search ) $query .= "\nHAVING ( posts.post_title LIKE '%$search%' OR posts.post_name LIKE '%$search%' OR meta_values LIKE '%$search%' )";

        // set query order by.
        $query .= "\nORDER BY click.date_clicked DESC";

        if ( ! $total )
            $total = $wpdb->get_var( "SELECT COUNT(1) FROM ( $query ) AS combined_table" );

        if ( $limit )
            $query .= "\nLIMIT $limit OFFSET $offset";

        $results = $wpdb->get_results( $query );

        return array( 'total' => $total , 'click_data' => $results );
    }

    /**
     * Format and prepare stats table single ror raw data.
     *
     * @since 1.2.0
     * @since 1.4.0 Add support for browser device data.
     * @access public
     *
     * @param \stdClass $row Stats table raw single row data.
     * @return array Stats Formatted stats table raw single row data.
     */
    private function format_stats_table_single_row_data( $row , $raw = false ) {

        if ( ! is_object( $row ) )
            return array();

        $esc_text_func = $raw ? 'sanitize_text_field' : 'esc_html';
        $esc_url_func = $raw ? 'esc_url_raw' : 'esc_html';

        $meta_keys      = explode( '||' , $row->meta_keys );
        $meta_values    = explode( '||' , $row->meta_values );
        $click_metas    = array_combine( $meta_keys , $meta_values );
        $ip_address     = isset( $click_metas[ 'user_ip_address' ] ) ? $esc_text_func( $click_metas[ 'user_ip_address' ] ) : '';
        $country        = isset( $click_metas[ 'user_geolocation' ] ) && $click_metas[ 'user_geolocation' ] ? $click_metas[ 'user_geolocation' ] : $this->get_click_entry_geolocation( $row->id , $ip_address , false );

        $formatted_data = array(
            'ip_address'     => $ip_address,
            'date'           => new \DateTime( $row->date_clicked ),
            'country'        => $country ? $esc_text_func ( $country ) : '',
            'link_name'      => $esc_text_func( $row->post_title . ' (/' . $row->post_name . '/)' ),
            'referrer'       => isset( $click_metas[ 'http_referer' ] ) ? $esc_text_func( $click_metas[ 'http_referer' ] ) : '',
            'cloaked_url'    => isset( $click_metas[ 'cloaked_url' ] ) ? $esc_url_func( $click_metas[ 'cloaked_url' ] ) : '',
            'redirect_url'   => isset( $click_metas[ 'redirect_url' ] ) ? $esc_url_func( $click_metas[ 'redirect_url' ] ) : '',
            'redirect_type'  => isset( $click_metas[ 'redirect_type' ] ) ? $esc_text_func( $click_metas[ 'redirect_type' ] ) : '',
            'browser_device' => isset( $click_metas[ 'browser_device' ] ) ? $esc_text_func( $click_metas[ 'browser_device' ] ) : __( '<em>no data</em>' , 'thirstyaffiliates-pro' ),
        );

        return $formatted_data;
    }

    /**
     * Get stats table report data.
     *
     * @since 1.1.0
     * @since 1.1.3 Adjusted how values are displayed to match changes on the get_stats_table_data custom query results.
     * @since 1.2.0 Removed the formatting code into its own separate function. see 'format_stats_table_single_row_data'
     * @since 1.4.0 Add support for browser device data.
     * @access public
     *
     * @param array $data Report range details
     * @return string Stats table html rows markup.
     */
    private function get_stats_table_html_rows_markup( $data ) {

        $timezone = new \DateTimeZone( $this->_stats_model->get_report_timezone_string() );
        $col_num  = 8;

        ob_start();

        if ( is_array( $data ) && ! empty( $data ) ) :

            foreach ( $data as $row ) :

                    $formatted_row = $this->format_stats_table_single_row_data( $row );
                    extract( $formatted_row );

                    $date->setTimezone( $timezone ); ?>
                <tr>
                    <td class="ip-address"><?php echo $ip_address; ?></td>
                    <td class="date"><?php echo $date->format( 'Y-m-d H:i:s' ); ?></td>
                    <td class="geolocation"><?php echo $country; ?></td>
                    <td class="link"><a href="<?php echo get_edit_post_link( $row->link_id ); ?>" target="_blank"><?php echo $link_name; ?></a></td>
                    <td class="referrer"><input type="text" value="<?php echo $referrer; ?>" readonly></td>
                    <td class="cloaked_url"><input type="text" value="<?php echo $cloaked_url; ?>" readonly></td>
                    <td class="redirect_url"><input type="text" value="<?php echo $redirect_url; ?>" readonly></td>
                    <td class="redirect_type"><?php echo $redirect_type; ?></td>
                    <?php if ( get_option( 'ta_disable_browser_device_collection' ) !== 'yes' && ini_get( "browscap" ) ) : ?>
                        <td class="browser-device"><?php echo $browser_device; ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="<?php echo $col_num; ?>"><?php _e( 'No data to show.' , 'thirstyaffiliates-pro' ); ?></td>
            </tr>
        <?php endif;

        return ob_get_clean();
    }

    /**
     * AJAX Get stats table report data.
     *
     * @since 1.1.0
     * @since 1.3.2 Add support for local browser timezone.
     * @access public
     */
    public function ajax_get_stats_table_data() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'tap_filter_stats_table_nonce_field' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            // set timezone
            $timezone = isset( $_POST[ 'timezone' ] ) ? sanitize_text_field( $_POST[ 'timezone' ] ) : '';
            $this->_stats_model->set_browser_zone_str( $timezone );

            $paged         = isset( $_POST[ 'paged' ] ) ? (int) intval( $_POST[ 'paged' ] ) : 1;
            $limit         = isset( $_POST[ 'limit' ] ) ? (int) intval( $_POST[ 'limit' ] ) : 25;
            $search        = isset( $_POST[ 'search' ] ) ? esc_sql( $_POST[ 'search' ] ) : '';
            $term_id       = isset( $_POST[ 'category' ] ) ? (int) intval( $_POST[ 'category' ] ) : 0;
            $total         = isset( $_POST[ 'total' ] ) ? (int) intval( $_POST[ 'total' ] ) : 0;
            $current_range = isset( $_POST[ 'range' ] ) ? sanitize_text_field( $_POST[ 'range' ] ) : '7day';
            $start_date    = isset( $_POST[ 'start_date' ] ) ? sanitize_text_field( $_POST[ 'start_date' ] ) : '';
            $end_date      = isset( $_POST[ 'end_date' ] ) ? sanitize_text_field( $_POST[ 'end_date' ] ) : '';
            $range         = $this->_stats_model->get_report_range_details( $current_range , $start_date , $end_date );
            $data          = $this->get_stats_table_data( $range , $paged , $term_id , $search , $limit , $total );

            $response = array(
                'status'     => 'success',
                'markup'     => $this->get_stats_table_html_rows_markup( $data[ 'click_data' ] ),
                'pagination' => $this->stats_table_pagination_markup( $data[ 'total' ] , $limit , $paged ),
                'total'      => $data[ 'total' ]
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Get stats table pagination markup
     *
     * @since 1.1.0
     * @since 1.2.2 Improve pagination interface by including previous, next, start and end buttons. Limit main pagination to 11.
     * @access private
     *
     * @param int $total Total number of table rows.
     * @param int $limit Total number of rows to display per page.
     * @param int $paged Current page to show.
     * @return string Pagination markup.
     */
    private function stats_table_pagination_markup( $total , $limit = 25 , $paged = 1 ) {

        $total_pages = ceil( $total / $limit );
        $start       = $paged > 5 ? $paged - 5 : 1;
        $end         = $paged < ( $total_pages - 5 ) ? $paged + 5 : $total_pages;
        $previous    = $paged - 1;
        $next        = $paged + 1;

        if ( $paged <= 5 && $total_pages > 11 ) $end = 11;
        if ( $paged > 9 && $paged >= ($total_pages - 5) ) $start = $total_pages - 10;

        ob_start();

        // display previous button
        ?><button class="button previous" value="<?php echo $previous; ?>" <?php echo $previous < 1 ? 'disabled' : '' ?>>
            <?php _e( '« Previous' , 'thirstyaffiliates-pro' ); ?>
        </button><?php

        // display start button
        if ( $paged > 6 ) echo '<button class="button start" value="1">1</button>';
        if ( $start > 2 ) echo '<span class="separator">...</span>';

        // display main pagination
        for ( $x = $start; $x <= $end; $x++ ) : ?>
            <button value="<?php echo $x; ?>"
                <?php echo ( $x == $paged ) ? 'class="button button-primary current" disabled="disabled"' : 'class="button"' ?>>
                <?php echo $x; ?>
            </button>
        <?php endfor;

        // display end button
        if ( $end < $total_pages - 1 ) echo '<span class="separator">...</span>';
        if ( $paged < $total_pages - 5 ) echo '<button class="button end" value="' . $total_pages . '">' . $total_pages . '</button>';

        // display next button
        ?><button class="button next" value="<?php echo $next; ?>" <?php echo $next > $total_pages ? 'disabled' : '' ?>>
            <?php _e( 'Next »' , 'thirstyaffiliates-pro' ); ?>
        </button><?php

        return ob_get_clean();
    }

    /**
     * AJAX Get stats table report data.
     *
     * @since 1.1.0
     * @access public
     */
    public function ajax_stats_table_csv_export() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( false )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            $timezone      = new \DateTimeZone( $this->_helper_functions->get_site_current_timezone() );
            $search        = isset( $_POST[ 'search' ] ) ? esc_sql( $_POST[ 'search' ] ) : '';
            $term_id       = isset( $_POST[ 'category' ] ) ? (int) intval( $_POST[ 'category' ] ) : 0;
            $current_range = isset( $_POST[ 'range' ] ) ? sanitize_text_field( $_POST[ 'range' ] ) : '7day';
            $start_date    = isset( $_POST[ 'start_date' ] ) ? sanitize_text_field( $_POST[ 'start_date' ] ) : '';
            $end_date      = isset( $_POST[ 'end_date' ] ) ? sanitize_text_field( $_POST[ 'end_date' ] ) : '';
            $range         = $this->_stats_model->get_report_range_details( $current_range , $start_date , $end_date );
            $raw_data      = $this->get_stats_table_data( $range , 1 , $term_id , $search , 0 , 1 );
            $data          = array(
                array( 'ip_address' , 'date' , 'country' , 'link_name' , 'referrer' , 'cloaked_url' , 'redirect_url' , 'redirect_type' )
            );

            foreach ( $raw_data[ 'click_data' ] as $row ) {

                $temp = $this->format_stats_table_single_row_data( $row , true );

                $temp[ 'date' ]->setTimezone( $timezone );
                $temp[ 'date' ] = $temp[ 'date' ]->format( 'Y-m-d H:i:s' );

                $data[] = array_values( $temp );
            }

            $response = array(
                'status' => 'success',
                'data'   => $data
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Keyword Report
    |--------------------------------------------------------------------------
    */

    /**
     * Register keyword report.
     *
     * @since 1.2.0
     * @access public
     *
     * @param array $reports Array list of all registered reports.
     * @return array Array list of all registered reports.
     */
    public function register_keyword_report( $reports ) {

        $reports[ 'keyword_report' ] = array(
            'id'      => 'tap_keyword_report',
            'tab'     => 'keyword_report',
            'name'    => __( 'Keywords' , 'thirstyaffiliates-pro' ),
            'title'   => __( 'Keywords Report' , 'thirstyaffiliates-pro' ),
            'desc'    => __( 'The keyword report gathers what particular words or phrases were linked when a link was clicked.' , 'thirstyaffiliates-pro' ),
            'content' => function() { $this->get_keyword_report_content(); }
        );

        return $reports;
    }

    /**
     * Get keyword report content.
     *
     * @since 1.2.0
     * @since 1.3.2 Removed unused variables. Added 24 hours report range option.
     * @access public
     *
     * @return string Keyword report content.
     */
    public function get_keyword_report_content() {

        $cpt_slug      = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $current_range = isset( $_GET[ 'range' ] ) ? sanitize_text_field( $_GET[ 'range' ] ) : '7day';
        $start_date    = isset( $_GET[ 'start_date' ] ) ? sanitize_text_field( $_GET[ 'start_date' ] ) : '';
        $end_date      = isset( $_GET[ 'end_date' ] ) ? sanitize_text_field( $_GET[ 'end_date' ] ) : '';
        $range_nav     = apply_filters( 'tap_keyword_report_nav' , array(
            'year'       => __( 'Year' , 'thirstyaffiliates-pro' ),
            'last_month' => __( 'Last Month' , 'thirstyaffiliates-pro' ),
            'month'      => __( 'This Month' , 'thirstyaffiliates-pro' ),
            '7day'       => __( 'Last 7 Days' , 'thirstyaffiliates-pro' ),
            '24hours'    => __( '24 Hours' , 'thirstyaffiliates-pro' )
        ) );
        $categories    = get_terms( Plugin_Constants::AFFILIATE_LINKS_TAX , array(
            'hide_empty' => false,
        ) );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/keyword-report.php' );
    }

    /**
     * Get keyword report data.
     *
     * @since 1.2.0
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param array $range    Report range data.
     * @param array $link_ids List of affiliate links to filter.
     * @param array $term_ids List of categories to filter.
     * @return array Click data with keyword data.
     */
    private function get_keyword_report_data( $range , $link_ids = array() , $term_ids = array() ) {

        global $wpdb;

        // set timezone to UTC
        $utc = new \DateTimeZone( 'UTC' );
        $range[ 'start_date' ]->setTimezone( $utc );
        $range[ 'end_date' ]->setTimezone( $utc );

        // DB tables
        $clicks_db     = $wpdb->prefix . 'ta_link_clicks';
        $clicksmeta_db = $wpdb->prefix . 'ta_link_clicks_meta';

        $cpt_slug    = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $start_date  = $range[ 'start_date' ]->format( 'Y-m-d H:i:s' );
        $end_date    = $range[ 'end_date' ]->format( 'Y-m-d H:i:s' );
        $query       = "SELECT click.*, posts.post_title, posts.post_name
                        FROM $clicks_db AS click
                        INNER JOIN $wpdb->posts AS posts ON ( posts.ID = click.link_id )";

        // add term relationships table if searching for term_id
        if ( is_array( $term_ids ) && ! empty( $term_ids ) ) $query .= "\nINNER JOIN $wpdb->term_relationships AS tr ON ( tr.object_id = click.link_id )";

        $query .= "\nWHERE click.date_clicked between '$start_date' AND '$end_date'
                         AND posts.post_type = '$cpt_slug'
                         AND posts.post_status = 'publish'";

        // set link_id if getting report for a single link.
        $link_ids_str = implode( ',' , $link_ids );
        if ( is_array( $link_ids ) && ! empty( $link_ids ) )
            $query .= "\nAND click.link_id IN ( $link_ids_str )";

        // set term_id if getting data for specific category
        $term_ids_str = implode( ',' , $term_ids );
        if ( is_array( $term_ids ) && ! empty( $term_ids ) )
            $query .= "\nAND tr.term_taxonomy_id IN ( $term_ids_str )";

        // set query group by.
        $query .= "\nGROUP BY click.id";

        // set query order by.
        $query .= "\nORDER BY click.date_clicked DESC";

        // get click and keyword data.
        $click_data = $wpdb->get_results( $query , ARRAY_A );

        if ( ! is_array( $click_data ) || empty( $click_data ) )
            return array();

        $keywords      = array();
        $click_ids     = array_column( $click_data , 'id' );
        $click_ids_str = implode( ',' , $click_ids );
        $raw_keywords  = $wpdb->get_results( "SELECT click_id,meta_value FROM $clicksmeta_db WHERE click_id IN ( $click_ids_str ) AND meta_key = 'keyword'" , ARRAY_A );

        if ( is_array( $raw_keywords ) && ! empty( $raw_keywords ) )
            foreach ( $raw_keywords as $raw_keyword )
                $keywords[ $raw_keyword[ 'click_id' ] ] = $raw_keyword[ 'meta_value' ];

        foreach( $click_data as $key => $click )
            $click_data[ $key ][ 'keyword' ] = isset( $keywords[ $click[ 'id' ] ] ) ? $keywords[ $click[ 'id' ] ] : '';

        return $click_data;
    }

    /**
     * Prepare raw keyword data for display on the table report format.
     *
     * @since 1.2.0
     * @access private
     *
     * @param array $click_data Click report data with keyword fetched via $this->get_keyword_report_data()
     * @return array Processed keyword data for display on table report.
     */
    private function prepare_keyword_table_data( $click_data ) {

        $keyword_report = array();

        foreach ( $click_data as $click_entry ) {

            $keyword = $click_entry[ 'keyword' ];
            $link_id = $click_entry[ 'link_id' ];
            $key     = $keyword ? $keyword : 'no_keyword';
            $click   = array(
                'id'           => $click_entry[ 'id' ],
                'date_clicked' => $click_entry[ 'date_clicked' ]
            );

            // increment number of clicks
            if ( isset( $keyword_report[ $key ] ) )
                $keyword_report[ $key ][ 'clicks' ]++;
            else {

                $keyword_report[ $key ] = array(
                    'clicks' => 1,
                    'links'  => array()
                );
            }


            // save affiliate link data if not yet set.
            if ( ! isset( $keyword_report[ $key ][ 'links' ][ $link_id ] ) ) {

                $keyword_report[ $key ][ 'links' ][ $link_id ] = array(
                    'post_title' => $click_entry[ 'post_title' ],
                    'post_name'  => $click_entry[ 'post_name' ],
                    'click_data' => array()
                );
            }

            // save click entry.
            $keyword_report[ $key ][ 'links' ][ $link_id ][ 'click_data' ][ $click_entry[ 'id' ] ] = $click_entry[ 'date_clicked' ];
        }

        return $keyword_report;
    }

    /**
     * Get keyword report table rows html markup..
     *
     * @since 1.2.0
     * @access private
     *
     * @param array $click_data Click report data with keyword fetched via $this->get_keyword_report_data()
     * @return array Processed keyword data for display on table report.
     */
    private function get_keyword_table_rows_html_markup( $click_data ) {

        $keyword_report = $this->prepare_keyword_table_data( $click_data );

        ob_start();

        foreach ( $keyword_report as $keyword => $data ) :

            $keyword      = $keyword == 'no_keyword' ? __( '<em>No keyword</em>' , 'thirstyaffiliates-pro' ) : esc_html( $keyword );
            $links_markup = '';

            foreach( $data[ 'links' ] as $link_id => $link )
                $links_markup .= '<a href="' . esc_url( get_edit_post_link( $link_id ) ) . '" target="_blank"><span class="tooltip" data-tip="' . esc_attr( $link[ 'post_title' ] . ' (/' . $link[ 'post_name' ] . '/)' ) . '">#' . $link_id . '</span></a>';
         ?>
            <tr>
                <td class="keyword"><?php echo $keyword; ?></td>
                <td class="total-clicks"><?php echo esc_html( $data[ 'clicks' ] ); ?></td>
                <td class="affiliate-links"><?php echo $links_markup; ?></td>
            </tr>
        <?php endforeach;

        return ob_get_clean();
    }

    /**
     * AJAX get keyword report data.
     *
     * @since 1.2.0
     * @since 1.3.2 Add support for local browser timezone.
     * @access public
     */
    public function ajax_get_keyword_report_data() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'tap_filter_keyword_report_nonce_field' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            // set timezone
            $timezone = isset( $_POST[ 'timezone' ] ) ? sanitize_text_field( $_POST[ 'timezone' ] ) : '';
            $this->_stats_model->set_browser_zone_str( $timezone );

            $link_ids      = isset( $_POST[ 'affiliate_links' ] ) && is_array( $_POST[ 'affiliate_links' ] ) ? array_map( 'intval' , $_POST[ 'affiliate_links' ] ) : array();
            $term_ids      = isset( $_POST[ 'categories' ] ) && is_array( $_POST[ 'categories' ] ) ? array_map( 'intval' , $_POST[ 'categories' ] ) : array();
            $current_range = isset( $_POST[ 'range' ] ) ? sanitize_text_field( $_POST[ 'range' ] ) : '7day';
            $start_date    = isset( $_POST[ 'start_date' ] ) ? sanitize_text_field( $_POST[ 'start_date' ] ) : '';
            $end_date      = isset( $_POST[ 'end_date' ] ) ? sanitize_text_field( $_POST[ 'end_date' ] ) : '';
            $range         = $this->_stats_model->get_report_range_details( $current_range , $start_date , $end_date );
            $click_data    = $this->get_keyword_report_data( $range , $link_ids , $term_ids );

            $response = array(
                'status' => 'success',
                'markup' => $this->get_keyword_table_rows_html_markup( $click_data )
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Utilities
    |--------------------------------------------------------------------------
    */

    /**
     * Search affiliate links for selectize script.
     *
     * @since 1.2.0
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param string $search Search query.
     * @return array Affiliate links list.
     */
    private function selectize_search_affiliate_links( $search ) {

        global $wpdb;

        if ( ! $search )
            return array();

        $cpt_slug = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $query    = "SELECT ID as value, post_title as label, post_name as slug
                     FROM $wpdb->posts
                     WHERE post_type = '$cpt_slug'
                           AND post_status = 'publish'
                           AND post_title LIKE '%$search%'";

        return $wpdb->get_results( $query , ARRAY_A );
    }

    /**
     * AJAX search affiliate links for selectize script.
     *
     * @since 1.2.0
     * @access public
     */
    public function ajax_selectize_search_affiliate_links() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        else {

            $search  = isset( $_POST[ 'search' ] ) ? esc_sql( $_POST[ 'search' ] ) : '';
            $results = $this->selectize_search_affiliate_links( $search );

            $response = array(
                'status' => 'success',
                'items'  => $this->selectize_search_affiliate_links( $search )
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Prepare report data for listed links.
     *
     * @since 1.4.0
     * @access private
     *
     * @param array $links List of affiliate link IDs.
     * @param array $range Report range details.
     * @return array Report data.
     */
    private function _prepare_links_report_data( $links , $range ) {

        $report_data = array();
        foreach ( $links as $link_id => $count ) {

            $report_data[] = array(
                'link_id'      => $link_id,
                'label'        => get_the_title( $link_id ),
                'slug'         => get_post_field( 'post_name', $link_id ),
                'plot'         => $this->_stats_model->prepare_data_for_flot( $range , array( $link_id ) ),
                'total_clicks' => $count
            );
        }

        return $report_data;
    }




    /*
    |--------------------------------------------------------------------------
    | Quick Stats Summary
    |--------------------------------------------------------------------------
    */

    /**
     * Display quick stats summary column in the affiliate link CPT list.
     *
     * @since 1.2.0
     * @access public
     *
     * @param array $columns Affiliate link CPT columns.
     */
    public function quick_stats_summary_cpt_column( $columns ) {

        $updated_columns = array();

        foreach ( $columns as $key => $column ) {

            if ( $key == 'date' )
                $updated_columns[ 'stats_summary' ] = __( 'Stats Summary' , 'thirstyaffiliates-pro' );

            $updated_columns[ $key ] = $column;
        }

        return $updated_columns;
    }

    /**
     * Quick stats summary CPT column value.
     *
     * @since 1.2.0
     * @access public
     *
     * @param string         $column      Affiliate link single column.
     * @param Affiliate_Link $thirstylink Affiliate_Link object.
     */
    public function quick_stats_summary_cpt_column_value( $column , $thirstylink ) {

        if ( $column != 'stats_summary' )
            return;

        $zone_str   = $this->_helper_functions->get_site_current_timezone();
        $timezone   = new \DateTimeZone( $zone_str );
        $utc        = new \DateTimeZone( 'UTC' );
        $week_date  = new \DateTime( 'now -6 days' , $timezone );
        $month_date = new \DateTime( 'now -30 days' , $timezone );

        // set time to zero
        $week_date->setTime( 0 , 0 , 0 );
        $month_date->setTime( 0 , 0 , 0 );

        // set timezone to UTC before fetching.
        $week_date->setTimezone( $utc );
        $month_date->setTimezone( $utc );

        // count the clicks.
        $total_clicks = $thirstylink->count_clicks();
        $week_clicks  = $thirstylink->count_clicks( $week_date->format( 'Y-m-d H:i:s' ) );
        $month_clicks = $thirstylink->count_clicks( $month_date->format( 'Y-m-d H:i:s' ) );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'reports/quick-stats-summary-column.php' );
    }




    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.0.0
     * @access public
     * @inherit ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'ta_enable_stats_reporting_module' , 'yes' ) !== 'yes' )
            return;

        $this->_stats_model = ThirstyAffiliates()->models[ 'Stats_Reporting' ];

        add_action( 'wp_ajax_ta_fetch_report_by_category' , array( $this , 'ajax_fetch_report_by_category' ) , 10 );
        add_action( 'wp_ajax_tap_geolocation_report' , array( $this , 'ajax_get_geolocation_data' ) );
        add_action( 'wp_ajax_tap_filter_stats_table_report' , array( $this , 'ajax_get_stats_table_data' ) );
        add_action( 'wp_ajax_tap_stats_table_export_csv' , array( $this , 'ajax_stats_table_csv_export' ) );
        add_action( 'wp_ajax_tap_filter_keyword_report' , array( $this , 'ajax_get_keyword_report_data' ) );
        add_action( 'wp_ajax_tap_selectize_affiliate_link' , array( $this , 'ajax_selectize_search_affiliate_links' ) );
        add_action( 'wp_ajax_tap_save_link_performance_report' , array( $this , 'ajax_save_link_performance_report' ) );
        add_action( 'wp_ajax_tap_load_link_performance_report' , array( $this , 'ajax_load_link_performance' ) );
        add_action( 'wp_ajax_tap_delete_link_performance_report' , array( $this , 'ajax_delete_link_performance_report' ) );
        add_action( 'wp_ajax_tap_get_link_performance_report_csv_headers' , array( $this , 'ajax_get_link_performance_report_csv_headers' ) );
        add_action( 'wp_ajax_tap_load_best_link_performance_report' , array( $this , 'ajax_get_best_link_performance_report' ) );
    }

    /**
     * Execute model.
     *
     * @implements ThirstyAffiliates_Pro\Interfaces\Model_Interface
     *
     * @since 1.0.0
     * @access public
     */
    public function run() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'ta_enable_stats_reporting_module' , 'yes' ) !== 'yes' )
            return;

        add_action( 'ta_stats_reporting_chart_sidebar' , array( $this , 'add_category_report_form_html' ) , 5 );
        add_action( 'ta_stats_reporting_chart_sidebar' , array( $this , 'load_link_performance_report_form_html' ) , 10 );
        add_action( 'ta_stats_reporting_chart_sidebar' , array( $this , 'save_link_performance_report_form_html' ) , 5 );
        add_action( 'ta_after_stats_reporting_chart_legend' , array( $this , 'load_report_actions_html' ) , 5 );
        add_action( 'ta_before_stats_reporting_chart_legend' , array( $this , 'loaded_report_indicator_html' ) , 5 );
        add_action( 'ta_stats_reporting_menu_items' , array( $this , 'export_csv_html' ) );
        add_action( 'ta_register_reports' , array( $this  , 'register_geolocation_reports' ) , 20 );
        add_action( 'ta_register_reports' , array( $this  , 'register_stats_table_report' ) , 30 );
        add_action( 'ta_register_reports' , array( $this  , 'register_keyword_report' ) , 40 );
        add_action( 'ta_after_link_performace_report' , array( $this , 'modify_js_report_details_24hours_flot_data' ) , 10 );
        add_filter( 'ta_link_performances_report_nav' , array( $this , 'add_24hours_to_range_nav' ) );
        add_filter( 'ta_report_set_start_date_time_to_zero' , array( $this , 'disable_set_start_date_to_zero_24hours_report' ) , 10 , 2 );
        add_filter( 'ta_report_range_data' , array( $this , 'register_24hours_report_range_data' ) , 10 , 2 );
        add_filter( 'ta_report_flot_data_incrementor', array( $this , 'set_incrementor_for_24hours_range_data' ) , 10 , 2 );
        add_filter( 'ta_save_click_data' , array( $this , 'save_user_geolocation_on_link_click' ) , 10 , 1 );
        add_filter( 'ta_post_listing_custom_columns' , array( $this , 'quick_stats_summary_cpt_column' ) , 20 );
        add_action( 'ta_post_listing_custom_columns_value' , array( $this , 'quick_stats_summary_cpt_column_value' ) , 10 , 2 );

        add_filter( 'ta_reports_admin_interface' , array( $this , 'register_reports_admin_interfaces' ) , 20 );
    }

}
