<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Activatable_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;


use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Link_Health_Checker module.
 *
 * @since 1.0.0
 */
class Link_Health_Checker implements Model_Interface , Activatable_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Link_Health_Checker.
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
     * Get all affiliate link ids.
     *
     * @since 1.1.0
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @return array List of all affiliate link IDs and corresponding destination URL.
     */
    private function get_all_link_ids_and_destination() {

        global $wpdb;

        $post_type        = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $destination_meta = Plugin_Constants::META_DATA_PREFIX . 'destination_url';
        $query            = "SELECT p.ID, meta.meta_value AS destination_url FROM $wpdb->posts AS p
                             INNER JOIN $wpdb->postmeta AS meta ON ( meta.post_id = p.ID AND meta.meta_key = '$destination_meta' )
                             WHERE post_type = '$post_type' AND post_status = 'publish'";

        return $wpdb->get_results( $query , ARRAY_A );
    }

    /**
     * Schedule link health checker cron job.
     *
     * @since 1.1.0
     * @since 1.7 Made sure DateInterval object is valid and DateTime is called with a timezone object.
     * @access public
     */
    private function schedule_cron_job() {

        $days_offset = get_option( 'tap_link_health_checker_days_offset' , 7 );
        $time_offset = get_option( 'tap_link_health_checker_hours_offset' , 0 );
        $interval    = date_interval_create_from_date_string( "$days_offset days + $time_offset hours" );
        $timezone    = new \DateTimeZone( $this->_helper_functions->get_site_current_timezone() );
        $time        = new \DateTime( "now" , $timezone );

        if ( ! is_a( $interval , 'DateInterval' ) )
            $interval = date_interval_create_from_date_string( "$days_offset days" );

        if ( ! is_a( $interval , 'DateInterval' ) )
            $interval = new \DateInterval('P7D');

        $time->setTime( 0 , 0 , 0 );
        $time->add( $interval );

        // clear all scheduled crons so there will always only be one.
        wp_clear_scheduled_hook( Plugin_Constants::LINK_HEALTH_CHECKER );

        // schedule cron job
        wp_schedule_single_event( $time->format( 'U' ) , Plugin_Constants::LINK_HEALTH_CHECKER );
    }

    /**
     * Implement link health checker.
     *
     * @since 1.1.0
     * @since 1.2.0 Trimmed down function and transferred all essential data processing on the check_single_link_health_status function.
     * @since 1.3.0 Make sure that only one instance of the function is run at a time by adding a transient.
     * @access public
     *
     * @return array Link health checker result counter.
     */
    public function implement_link_health_checker() {

        if ( get_option( 'tap_enable_link_health_checker' , 'no' ) !== 'yes' ) return;

        // skip implementation if the transient is still present.
        if ( get_transient( 'tap_link_health_checker_transient' ) ) return;

        // Set transient that cron is currently running (Expires default after 6 hours).
        $transient_expire = apply_filters( 'tap_link_health_checker_transient_expire' , 21600 );
        set_transient( 'tap_link_health_checker_transient' , current_time( 'mysql' , true ) , $transient_expire );

        $links    = $this->get_all_link_ids_and_destination();
        $counter  = array(
            'active'   => 0,
            'inactive' => 0,
            'warning'  => 0,
            'error'    => 0
        );

        foreach ( $links as $link ) {

            $data   = $this->check_single_link_health_status( $link );
            $status = $data[ 'status' ] == 'ignored' ? 'active' : $data[ 'status' ];
            $counter[ $status ]++;
        }

        // send email report to admin
        $this->send_email_report( $counter );

        // reschedule cron job
        $this->schedule_cron_job();

        // after running, make sure the transient is deleted.
        delete_transient( 'tap_link_health_checker_transient' );

        return $counter;
    }

    /**
     * Send email report.
     *
     * @since 1.1.0
     * @since 1.2.0 Removed the $issues variable. Add support for 'ignored' status.
     * @access private
     *
     * @param array $counter Link health checker implementation counter.
     */
    private function send_email_report( $counter ) {

        $timezone = new \DateTimeZone( $this->_helper_functions->get_site_current_timezone() );
        $time     = new \DateTime();

        $time->setTimezone( $timezone );

        $args = apply_filters( 'tap_link_health_checker_email_args', array(
            'to'          => get_bloginfo( 'admin_email' ),
            'subject'     => sprintf( __( 'ThirstyAffiliates automatic 404 checker report %s - %s' , 'thirstyaffiliates-pro' ) , $time->format( 'F j, Y' ) , get_bloginfo( 'name' ) ),
            'message'     => $this->get_report_contents( $counter ),
            'headers'     => array(),
            'attachments' => array()
        ) , $time , $counter );

        extract( $args );
        ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->send_email( $to , $subject , $message , $headers , $attachments );
    }

    /**
     * Get email report message body.
     *
     * @since 1.1.0
     * @since 1.2.0 Add $issues_link variable that links to the new link health report. Removed $issues variable.
     * @access private
     *
     * @param array $counter Link health checker implementation counter.
     */
    private function get_report_contents( $counter ) {

        $issues_link = admin_url( 'edit.php?post_type=thirstylink&page=thirsty-reports&tab=link_health_report' );

        ob_start();

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'link-health-checker/link-health-checker-email-report.php' );

        return ob_get_clean();
    }

    /**
     * Get destination URL status.
     *
     * @since 1.1.0
     * @since 1.2.0 Function will now only return a data array information of the issue and will not return a WP_Error anymore. Add support for 'ignored' status.
     *
     * @access private
     *
     * @param array $link   Single link ID and destination URL.
     * @param bool  $manual Function is run manually and not by cron.
     * @return array Data of the issue with the following details: link id, url, status and mesage.
     */
    private function check_single_link_health_status( $link , $manual = false ) {

        // don't proced if ID or destination url is not set.
        if ( ! isset( $link[ 'ID' ] ) || ! isset( $link[ 'destination_url' ] ) ) {

            $data = array(
                'link_id' => null,
                'url'     => null,
                'status'  => 'error',
                'message' => __( 'The required ID or destination_url parameter is missing.' , 'thirstyaffiliates-pro' )
            );

            return $data;
        }

        // get past data if present.
        $data = get_post_meta( $link[ 'ID' ] , Plugin_Constants::META_DATA_PREFIX . 'link_health_issue' , true );

        // recreate data variable if empty or invalid.
        if ( ! is_array( $data ) || empty( $data ) ) {

            $data = array(
                'link_id' => $link[ 'ID' ],
                'url'     => $link[ 'destination_url' ],
                'status'  => '',
                'message' => '',
                'steps'   => array()
            );
        }

        // if issue is "ignored" then don't proceed (cron only).
        if ( $this->is_issue_ignored( $link[ 'ID' ] , $data ) && ! $manual ) {

            $data[ 'status' ] = 'ignored';
            return $data;
        }

        if ( empty( $link[ 'destination_url' ] ) || ! filter_var( $link[ 'destination_url' ] , FILTER_VALIDATE_URL ) ) {

            $data[ 'status' ]  = 'error';
            $data[ 'message' ] = __( 'The provided URL is invalid.' , 'thirstyaffiliates-pro' );

        } else {

            $response = wp_remote_head( $link[ 'destination_url' ] );
            $response_code = wp_remote_retrieve_response_code( $response );

            if ( ! $response_code ) {

                $data[ 'status' ]  = 'warning';
                $data[ 'message' ] = __( 'Failed on getting a response from provided URL.' , 'thirstyaffiliates-pro' );

            } elseif ( $response_code == 404 ) {

                $data[ 'status' ]  = 'inactive';
                $data[ 'message' ] = __( 'Link doesn\'t exist or is temporarily not available' , 'thirstyaffiliates-pro' );

            } else {

                $data[ 'status' ] = 'active';
            }
        }

        $last_checked = current_time( 'mysql' , true );

        update_post_meta( $link[ 'ID' ] , Plugin_Constants::META_DATA_PREFIX . 'link_health_status' , $data[ 'status' ] );
        update_post_meta( $link[ 'ID' ] , Plugin_Constants::META_DATA_PREFIX . 'link_health_last_checked' , $last_checked );

        if ( in_array( $data[ 'status' ] , array( 'warning' , 'inactive' , 'error' ) ) )
            update_post_meta( $link[ 'ID' ] , Plugin_Constants::META_DATA_PREFIX . 'link_health_issue' , $data );
        else
            delete_post_meta( $link[ 'ID' ] , Plugin_Constants::META_DATA_PREFIX . 'link_health_issue' );

        $data[ 'timestamp' ] = $last_checked;

        return $data;
    }

    /**
     * Check if the issue of a single affiliate link is set to be ignored or not.
     *
     * @since 1.2.0
     * @access private
     *
     * @param int   $link_id ID of the affiliate link.
     * @param array $issue Data of affiliate link's issue.
     * @return bool True if data of the issue is equal to the one ignored, false otherwise.
     */
    private function is_issue_ignored( $link_id , $issue ) {

        $ignored = get_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'link_health_issue_ignored' , true );
        $status  = get_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'link_health_status' , true );

        return $status == 'ignored';
    }

    /**
     * Ignore single link health issue.
     *
     * @since 1.2.0
     * @access private
     *
     * @param int $link_id Affiliate link ID.
     */
    private function ignore_single_link_health_issue( $link_id ) {

        $issue = get_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'link_health_issue' , true );

        if ( ! $issue || empty( $issue ) )
            return;

        update_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'link_health_issue_ignored' , $issue );
        update_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'link_health_status' , 'ignored' );
    }




    /*
    |--------------------------------------------------------------------------
    | UI Related Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Display link health status column in the affiliate link CPT list.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array $columns Affiliate link CPT columns.
     */
    public function link_health_status_cpt_column( $columns ) {

        $updated_columns = array();

        foreach ( $columns as $key => $column ) {

            if ( $key == 'date' )
                $updated_columns[ 'link_health_status' ] = __( '404 Checker' , 'thirstyaffiliates-pro' );

            $updated_columns[ $key ] = $column;
        }

        return $updated_columns;
    }

    /**
     * Link health status CPT column value.
     *
     * @since 1.1.0
     * @since 1.2.0 Changed the status texts to be more simpler. Moved display code to a helper function.
     * @access public
     *
     * @param string $column Affiliate link single column.
     * @param Affiliate_Link $thirstylink Affiliate_Link object.
     */
    public function link_health_status_cpt_column_value( $column , $thirstylink ) {

        if ( $column != 'link_health_status' )
            return;

        $status       = $thirstylink->get_prop( 'link_health_status' );
        $last_checked = $thirstylink->get_prop( 'link_health_last_checked' );

        $this->_helper_functions->display_link_health_status( $status , $last_checked );
    }




    /*
    |--------------------------------------------------------------------------
    | Link Health Report
    |--------------------------------------------------------------------------
    */

    /**
     * Register link health report.
     *
     * @since 1.2.0
     * @access public
     *
     * @param array $reports Array list of all registered reports.
     * @return array Array list of all registered reports.
     */
    public function register_link_health_report( $reports ) {

        $description  = __(
            "<p>Below is the list of issues that need to be addressed. Not all issues listed here will be serious issues and some might be false positives.</p>" .
            "<p>Choose to \"Recheck\" to check a single link again and see if the same issue remains. If so, decide if the automatic 404 checker should ignore the reported issue (if it's a false positive).</p>" .
            "<p>If you decide to change the destination URL of the affiliate link, choose \"Recheck\" after you have made the change to see if that solves the issue.</p>" .
            "<div class='legend'><p><strong class='warning'>Warnings</strong>: You should check that the link is resolving where you think it should be resolving. If so, mark it ignored and the health checker won't check it again.</p>" .
            "<p><strong class='error'>Errors:</strong> Pay special attention. Errors indicate a 404 status code was thrown when trying to visit the link. Check that everything is OK and if it is, mark it ignored. Otherwise, make whatever changes you need to the link and click recheck to see if those changes worked.</p></div>"
         , 'thirstyaffiliates-pro' );

        $reports[ 'link_health_report' ] = array(
            'id'      => 'tap_link_health_report',
            'tab'     => 'link_health_report',
            'name'    => __( '404 Checker' , 'thirstyaffiliates-pro' ),
            'title'   => __( '404 Checker Report' , 'thirstyaffiliates-pro' ),
            'desc'    => $description,
            'content' => function() { $this->get_link_health_report_content(); }
        );

        return $reports;
    }

    /**
     * Get link health report total number of rows.
     *
     * @since 1.2.0
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @return int Total number of rows.
     */
    private function get_link_health_report_total() {

        global $wpdb;

        $status_meta = Plugin_Constants::META_DATA_PREFIX . 'link_health_status';
        $issue_meta  = Plugin_Constants::META_DATA_PREFIX . 'link_health_issue';
        $date_meta   = Plugin_Constants::META_DATA_PREFIX . 'link_health_last_checked';
        $post_type   = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $query       = "SELECT p.ID, p.post_title, p.post_name, meta1.meta_value AS status, meta2.meta_value AS issue, meta3.meta_value AS last_checked
                        FROM $wpdb->posts AS p
                        INNER JOIN $wpdb->postmeta AS meta1 ON ( meta1.post_id = p.ID AND meta1.meta_key = '$status_meta' )
                        INNER JOIN $wpdb->postmeta AS meta2 ON ( meta2.post_id = p.ID AND meta2.meta_key = '$issue_meta' )
                        INNER JOIN $wpdb->postmeta AS meta3 ON ( meta3.post_id = p.ID AND meta3.meta_key = '$date_meta' )
                        WHERE post_type = '$post_type' AND post_status = 'publish'
                        GROUP BY p.ID";

        return $wpdb->get_var( "SELECT COUNT(1) FROM ( $query ) AS combined_table" );
    }

    /**
     * Get link health report data custom SQL query.
     *
     * @since 1.2.0
     * @access private
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param int $paged Current report page to display.
     * @param int $limit Number of rows to query.
     * @return array List of link health issues.
     */
    private function get_link_health_report_data( $paged = 1 , $limit = 25 ) {

        global $wpdb;

        $offset      = ( $paged - 1 ) * $limit;
        $status_meta = Plugin_Constants::META_DATA_PREFIX . 'link_health_status';
        $issue_meta  = Plugin_Constants::META_DATA_PREFIX . 'link_health_issue';
        $date_meta   = Plugin_Constants::META_DATA_PREFIX . 'link_health_last_checked';
        $post_type   = Plugin_Constants::AFFILIATE_LINKS_CPT;
        $query       = "SELECT p.ID, p.post_title, p.post_name, meta1.meta_value AS status, meta2.meta_value AS issue, meta3.meta_value AS last_checked
                        FROM $wpdb->posts AS p
                        INNER JOIN $wpdb->postmeta AS meta1 ON ( meta1.post_id = p.ID AND meta1.meta_key = '$status_meta' )
                        INNER JOIN $wpdb->postmeta AS meta2 ON ( meta2.post_id = p.ID AND meta2.meta_key = '$issue_meta' )
                        INNER JOIN $wpdb->postmeta AS meta3 ON ( meta3.post_id = p.ID AND meta3.meta_key = '$date_meta' )
                        WHERE post_type = '$post_type' AND post_status = 'publish'
                        GROUP BY p.ID";

        if ( $limit ) $query  .= "\nLIMIT $limit OFFSET $offset";

        $issues = $wpdb->get_results( $query , ARRAY_A );

        foreach ( $issues as $key => $row )
            $issues[ $key ][ 'issue' ] = unserialize( $row[ 'issue' ] );

        return $issues;
    }

    /**
     * Get keyword report content.
     *
     * @since 1.2.0
     * @access public
     *
     * @return string Keyword report content.
     */
    private function get_link_health_report_content() {

        $total       = $this->get_link_health_report_total();
        // $report      = $this->get_link_health_report_data();
        // $rows_markup = $this->get_link_health_report_rows_markup( $report );
        $statuses    = array(
            'waiting'  => __( 'waiting' , 'thirstyaffiliates-pro' ),
            'active'   => __( 'okay' , 'thirstyaffiliates-pro' ),
            'inactive' => __( 'error' , 'thirstyaffiliates-pro' ),
            'warning'  => __( 'warning' , 'thirstyaffiliates-pro' ),
            'error'    => __( 'error' , 'thirstyaffiliates-pro' ),
            'ignored'  => __( 'ignored' , 'thirstyaffiliates-pro' )
        );

        include( $this->_constants->VIEWS_ROOT_PATH() . 'link-health-checker/link-health-report.php' );
    }

    /**
     * Get link health report rows HTML markup.
     *
     * @since 1.2.0
     * @since 1.3.1 Moved edit link to actions row and change icon and text to "fix".
     * @since 1.4.0 Combine expected and actual destinations column and highlight the difference of its values.
     * @access private
     *
     * @param array $report List of link health issues.
     * @param string $report Link health issues table html rows markup.
     */
    private function get_link_health_report_rows_markup( $report ) {

        $utc         = new \DateTimeZone( 'UTC' );
        $timezone    = new \DateTimeZone( $this->_helper_functions->get_site_current_timezone() );
        $statuses    = array(
            'waiting'  => __( 'waiting' , 'thirstyaffiliates-pro' ),
            'active'   => __( 'okay' , 'thirstyaffiliates-pro' ),
            'inactive' => __( 'error' , 'thirstyaffiliates-pro' ),
            'warning'  => __( 'warning' , 'thirstyaffiliates-pro' ),
            'error'    => __( 'error' , 'thirstyaffiliates-pro' ),
            'ignored'  => __( 'ignored' , 'thirstyaffiliates-pro' )
        );

        ob_start();

        if ( is_array( $report ) && ! empty( $report ) ) :
            foreach ( $report as $row ) :

                $issue        = $row[ 'issue' ];
                $status       = $row[ 'status' ] == 'ignored' ? $row[ 'status' ] : $issue[ 'status' ];
                $last_checked = date_create( $row[ 'last_checked' ] , $utc );

                if ( $last_checked ) {
                    $last_checked->setTimezone( $timezone );
                    $last_checked = $last_checked->format( 'F j, Y h:i:s' );
                } else {
                    $last_checked = __( 'Unknown' , 'thirstyaffiliates-pro' );
                }

                $has_steps    = isset( $issue[ 'steps' ] ) && ! empty( $issue[ 'steps' ] );

                $expected = isset( $issue[ 'url' ] ) ? $issue[ 'url' ] : '';
                $actual   = is_array( $issue[ 'steps' ] ) && ! empty( $issue[ 'steps' ] ) ? end( $issue[ 'steps' ] ) : '';
                $diff     = array();

                if ( $expected && $actual )
                    $diff = $this->_get_expected_and_actual_destination_diff( $expected , $actual );

                $expected = isset( $diff[ 'expected' ] ) && $diff[ 'expected' ] ? $diff[ 'expected' ] : $expected;
                $actual   = isset( $diff[ 'actual' ] ) && $diff[ 'actual' ] ? $diff[ 'actual' ] : $actual; ?>
                <tr class="link-<?php echo esc_attr( $row[ 'ID' ] ); ?> <?php echo esc_attr( $status ); ?>">
                    <td class="bulk"><input type="checkbox" name="link_issue[]" value="<?php echo esc_attr( $row[ 'ID' ] ); ?>"></td>
                    <td class="link_id">
                        <?php echo esc_html( $row[ 'post_title' ] . ' (' . $row[ 'post_name' ] . ')' );  ?></a>
                        <a href="<?php echo esc_url( get_permalink( $row[ 'ID' ] ) ); ?>" target="_blank"><span class="dashicons dashicons-external tooltip" data-tip="<?php esc_attr_e( 'Visit affiliate link' , 'thirstyaffiliates-pro' ); ?>"></span></a>
                        <a href="" target="_blank"></a>
                    </td>
                    <td class="destination">
                        <div class="expected-destination">
                            <span class="label"><?php _e( 'Expected:' , 'thirstyaffiliates-pro' ); ?></span>
                            <div class="value"><?php echo $expected; ?></div>
                        </div>
                        <div class="actual-destination <?php echo ! $has_steps ? "no-data" : ""; ?>">
                            <span class="label"><?php _e( 'Actual:' , 'thirstyaffiliates-pro' ); ?></span>

                            <?php if ( $has_steps ) : ?>
                                <div class="value"><?php echo $actual; ?></div>
                                <a class="view-steps" href="javascript:void(0)"><span class="dashicons dashicons-info"></span></a>
                                <div class="steps-content" style="display:none;">
                                    <ol><li><?php echo implode( '</li><li>' , $issue[ 'steps' ] ); ?></li></ol>
                                </div>
                            <?php else : ?>
                                <em><?php _e( 'No data' , 'thirstyaffiliates-pro' ); ?></em>
                            <?php endif; ?>

                        </div>

                    </td>
                    <td class="status"><span class="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $statuses[ $status ] ); ?></span></td>
                    <td class="last_checked"><?php echo esc_html( $last_checked ); ?></td>
                    <td class="issue"><?php echo esc_html( $issue[ 'message' ] ); ?></td>
                    <td class="actions">
                        <div class="row-spinner"><span></span></div>
                        <a class="button-primary tooltip" href="<?php echo esc_url( get_edit_post_link( $row[ 'ID' ] ) ); ?>" target="_blank" data-tip="<?php _e( 'Fix' , 'thirstyaffiliates-pro' ); ?>">
                            <span class="dashicons dashicons-admin-tools tooltip"></span>
                        </a>
                        <button type="button" class="button recheck tooltip" data-tip="<?php _e( 'Recheck' , 'thirstyaffiliates-pro' ); ?>">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                        <button type="button" class="button ignore tooltip" data-tip="<?php _e( 'Ignore' , 'thirstyaffiliates-pro' ); ?>" <?php echo $status == 'ignored' ? 'disabled' : ''; ?>>
                            <span class="dashicons dashicons-hidden"></span>
                        </button>
                        <input type="hidden" name="link_id" value="<?php echo esc_attr( $row[ 'ID' ] ); ?>">
                        <input type="hidden" name="_nonce" value="<?php echo wp_create_nonce( 'tap_check_single_link_health_' . $row[ 'ID' ] ); ?>">
                    </td>
                </tr>
            <?php endforeach;
        else : ?>
            <tr>
                <td class="no-issues" colspan="7"><?php _e( 'No issues to display.' , 'thirstyaffiliates-pro' ); ?></td>
            </tr>
        <?php endif;

        return ob_get_clean();
    }

    /**
     * Get DIFF of expected and actual destination.
     * Reference: https://coderwall.com/p/3j2hxq/find-and-format-difference-between-two-strings-in-php
     *
     * @since 1.4.0
     * @access private
     *
     * @param string $expected Expected destination.
     * @param string $actual   Actual destination.
     * @return array DIFF of expected and actual destination.
     */
    private function _get_expected_and_actual_destination_diff( $expected , $actual ) {

        if ( ! $expected || ! $actual ) return array();

        $from_start = strspn( $expected ^ $actual , "\0" );
        $from_end   = strspn( strrev( $expected ) ^ strrev( $actual ) , "\0" );

        $exp_end = strlen( $expected ) - $from_end;
        $act_end = strlen( $actual ) - $from_end;

        $start    = substr( $actual , 0 , $from_start );
        $end      = substr( $actual , $act_end );
        $act_diff = substr( $actual , $from_start , $act_end - $from_start );
        $exp_diff = substr( $expected , $from_start , $exp_end - $from_start );

        return array(
            'expected' => "$start<em>$exp_diff</em>$end",
            'actual'   => "$start<em>$act_diff</em>$end"
        );
    }

    /**
     * AJAX load additional health issues report rows.
     *
     * @since 1.2.0
     * @access public
     */
    public function ajax_load_additional_health_issues_report_rows() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        else {

            $paged  = isset( $_POST[ 'paged' ] ) ? intval( $_POST[ 'paged' ] ) : 1;
            $report = $this->get_link_health_report_data( $paged );

            $response = array(
                'status' => 'success',
                'markup' => $this->get_link_health_report_rows_markup( $report ),
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Ajax check single link health status.
     *
     * @since 1.2.0
     * @since 1.3.0 Add link health status markup on response.
     * @access private
     */
    public function ajax_check_single_link_health_status() {

        $link_id = ( isset( $_POST[ 'link_id' ] ) ) ? intval( $_POST[ 'link_id' ] ) : 0;

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'tap_check_single_link_health_' . $link_id ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            $utc             = new \DateTimeZone( 'UTC' );
            $timezone        = new \DateTimeZone( $this->_helper_functions->get_site_current_timezone() );
            $destination_url = get_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'destination_url' , true );
            $data            = $this->check_single_link_health_status( array( 'ID' => $link_id , 'destination_url' => $destination_url ) , true );

            if ( $data[ 'status' ] == 'active' )
                $data[ 'message' ] = __( '<em>Fixed</em>' , 'thirstyaffiliates-pro' );

            // get last checked date.
            $last_checked = date_create( $data[ 'timestamp' ] , $utc );

            if ( $last_checked ) {
                $last_checked_date = $last_checked->format( 'Y-m-d H:i:s' );
                $last_checked->setTimezone( $timezone );
                $data[ 'last_checked' ] = $last_checked->format( 'F j, Y h:i:s' );
            } else {
                $last_checked_date = 'now';
                $data[ 'last_checked' ] = __( 'Unknown' , 'thirstyaffiliates-pro' );
            }

            $response = array(
                'status' => 'success',
                'data'   => $data,
                'markup' => $this->_helper_functions->display_link_health_status( $data[ 'status' ], $last_checked_date, false )
            );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX ignore single link health issue.
     *
     * @since 1.2.0
     * @access public
     */
    public function ajax_ignore_single_link_health_issue() {

        $link_id = ( isset( $_POST[ 'link_id' ] ) ) ? intval( $_POST[ 'link_id' ] ) : 0;

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'tap_check_single_link_health_' . $link_id ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            $this->ignore_single_link_health_issue( $link_id );
            $status = get_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'link_health_status' , true );

            if ( $status !== 'ignored' )
                $response = array( 'status' => 'fail' , 'error_msg' => __( 'Failed to ignore issue or issue doesn\'t exist anymore.' , 'thirstyaffiliates-pro' ) );
            else
                $response = array( 'status' => 'success' , 'issue_status' => $status );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX link health report bulk action.
     *
     * @since 1.2.0
     * @access public
     */
    public function ajax_link_health_report_bulk_action() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ] , 'tap_link_health_bulk_action_nonce' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            $utc         = new \DateTimeZone( 'UTC' );
            $timezone    = new \DateTimeZone( $this->_helper_functions->get_site_current_timezone() );
            $link_issues = array_map( 'intval' , $_POST[ 'link_issue' ] );
            $bulk_action = sanitize_text_field( $_POST[ 'bulk_action' ] );
            $results     = array();

            foreach ( $link_issues as $link_id ) {

                if ( $bulk_action == 'ignore' ) {

                    $this->ignore_single_link_health_issue( $link_id );

                    $status              = get_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'link_health_status' , true );
                    $results[ $link_id ] = array( 'issue_status' => $status );

                } else {

                    $destination_url = get_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'destination_url' , true );
                    $data            = $this->check_single_link_health_status( array( 'ID' => $link_id , 'destination_url' => $destination_url ) , true );

                    if ( $data[ 'status' ] == 'active' )
                        $data[ 'message' ] = __( '<em>Fixed</em>' , 'thirstyaffiliates-pro' );

                    // get last checked date.
                    $last_checked = date_create( $data[ 'timestamp' ] , $utc );

                    if ( $last_checked ) {
                        $last_checked->setTimezone( $timezone );
                        $data[ 'last_checked' ] = $last_checked->format( 'F j, Y h:i:s' );
                    } else {
                        $data[ 'last_checked' ] = __( 'Unknown' , 'thirstyaffiliates-pro' );
                    }

                    $results[ $link_id ] = $data;
                }
            }

            $response = array(
                'status'  => 'success',
                'results' => $results
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX trigger link health checker cron manually in settings page.
     *
     * @since 1.3.0
     * @access public
     */
    public function ajax_trigger_link_health_checker_cron() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ] , 'tap_trigger_link_health_checker' ) || ! current_user_can( 'manage_options' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        elseif ( get_transient( 'tap_link_health_checker_transient' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'The previous automatic 404 checker cron job is still running.' , 'thirstyaffiliates-pro' ) );
        else {

            // trigger to run cron.
            wp_clear_scheduled_hook( Plugin_Constants::LINK_HEALTH_CHECKER );
            wp_schedule_single_event( current_time( 'timestamp' , true ) , Plugin_Constants::LINK_HEALTH_CHECKER );

            $response = array(
                'status'  => 'success',
                'message' => __( 'Link Checker has been run manually successfully.' , 'thirstyaffiliates-pro' ),
            );
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
     * Execute codes that needs to run plugin activation.
     *
     * @since 1.0.0
     * @access public
     * @implements ThirstyAffiliates_Pro\Interfaces\Activatable_Interface
     */
    public function activate() {

        // only schedule cron job when it hasn't been scheduled yet on activation.
        if ( ! wp_next_scheduled( Plugin_Constants::LINK_HEALTH_CHECKER ) )
            $this->schedule_cron_job();
    }

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.0.0
     * @access public
     * @inherit ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_link_health_checker' , 'no' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_tap_load_page_rows_markup' , array( $this , 'ajax_load_additional_health_issues_report_rows' ) );
        add_action( 'wp_ajax_tap_check_single_link_health' , array( $this , 'ajax_check_single_link_health_status' ) );
        add_action( 'wp_ajax_tap_ignore_link_health_issue' , array( $this , 'ajax_ignore_single_link_health_issue' ) );
        add_action( 'wp_ajax_tap_link_health_bulk_action' , array( $this , 'ajax_link_health_report_bulk_action' ) );
        add_action( 'wp_ajax_tap_trigger_link_health_checker' , array( $this , 'ajax_trigger_link_health_checker_cron' ) );
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

        if ( get_option( 'tap_enable_link_health_checker' , 'no' ) !== 'yes' )
            return;

        add_filter( 'ta_post_listing_custom_columns' , array( $this , 'link_health_status_cpt_column' ) );
        add_action( 'ta_post_listing_custom_columns_value' , array( $this , 'link_health_status_cpt_column_value' ) , 10 , 2 );
        add_action( Plugin_Constants::LINK_HEALTH_CHECKER , array( $this , 'implement_link_health_checker' ) );
        add_action( 'ta_register_reports' , array( $this  , 'register_link_health_report' ) , 50 );

    }

}
