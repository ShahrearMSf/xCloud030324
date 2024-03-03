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
 * Model that houses the logic for the Geolocation module.
 *
 * @since 1.0.0
 */
class Geolocation implements Model_Interface , Activatable_Interface , Initiable_Interface {

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
     * Add/Edit single geolink.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int    $post_id         Affiliate Link post ID.
     * @param array  $countries       List of country codes.
     * @param string $destination_url Destination url for the set countries.
     * @param string $old_key         Old key of the geolink to be updated.
     * @return WP_Error | array WP_Error instance on failure, array of geolocation_links meta otherwise.
     */
    private function add_edit_single_geolink( $post_id , $countries , $destination_url , $old_key = '' ) {

        do_action( 'tap_before_add_edit_single_geolink' , $post_id , $countries , $destination_url , $old_key );

        $geolinks = get_post_meta( $post_id , Plugin_Constants::META_DATA_PREFIX . 'geolocation_links' , true );

        if ( ! is_array( $geolinks ) )
            $geolinks = array();

        // check if we are editing an existing geolink or not.
        if ( $old_key ) {

            if ( ! array_key_exists( $old_key , $geolinks ) )
                return new \WP_Error( 'tap_failed_edit_geolink' , __( 'The selected geolocation link being edited doesn\'t exist anymore.' , 'thirstyaffiliates-pro' ) , array( 'post_id' => $post_id , 'geolinks' => $geolinks ) );

            $geolinks[ $old_key ] = $destination_url;
            $new_key              = trim( implode( ',' , $countries ) );
            $keys                 = array_keys( $geolinks );
            $key_search           = array_search( $old_key , $keys );
            $keys[ $key_search ]  = $new_key;
            $geolinks             = array_combine( $keys , $geolinks );

        } else {

            $key              = trim( implode( ',' , $countries ) );
            $geolinks[ $key ] = $destination_url;
        }

        $check = update_post_meta( $post_id , Plugin_Constants::META_DATA_PREFIX . 'geolocation_links' , $geolinks );

        do_action( 'tap_after_add_edit_single_geolink' , $post_id , $countries , $destination_url , $old_key );

        // when the value set on update_post_meta is the same with the value in the database, it returns false
        // that's why we need to add $old_key in the condition for the return here, so it won't result an error
        // when the passed value is the same or no changes has been made.
        return $check || $old_key ? $geolinks : new \WP_Error( 'tap_failed_update_geolinks' , __( 'There was an error on updating the geolinks.' , 'thirstyaffiliates-pro' ) , array( 'post_id' => $post_id , 'geolinks' => $geolinks ) );
    }

    /**
     * Add single geolink.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int    $post_id         Affiliate Link post ID.
     * @param string $countries_key   List of countries imploded as a string.
     * @return WP_Error | array WP_Error instance on failure, array of geolocation_links meta otherwise.
     */
    private function remove_single_geolink( $post_id , $countries_key ) {

        $geolinks = get_post_meta( $post_id , Plugin_Constants::META_DATA_PREFIX . 'geolocation_links' , true );

        if ( ! is_array( $geolinks ) || empty( $geolinks ) || ! isset( $geolinks[ $countries_key ] ) )
            return new \WP_Error( 'tap_failed_delete_geolink' , __( 'There are no registered geolocation links to remove.' , 'thirstyaffiliates-pro' ) , array( 'post_id' => $post_id , 'geolinks' => $geolinks ) );

        unset( $geolinks[ $countries_key ] );
        $check = update_post_meta( $post_id , Plugin_Constants::META_DATA_PREFIX . 'geolocation_links' , $geolinks );

        return $check ? $geolinks : new \WP_Error( 'tap_failed_delete_geolink' , __( 'The selected geolocation link doesn\'t exist or has already been removed' , 'thirstyaffiliates-pro' ) , array( 'post_id' => $post_id , 'geolinks' => $geolinks ) );
    }

    /**
     * Redirect user by geolocation.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string         $redirect_url URL for the user to be redirected.
     * @param Affiliate_Link $thirstylink  Affiliate Link object.
     * @return string Processed URL with geolocation for the user to be redirected.
     */
    public function geolocation_redirect( $redirect_url , $thirstylink ) {

        $geolocations = $thirstylink->get_prop( 'geolocation_links' );

        // skip if there are no geolocation links saved
        if ( empty( $geolocations ) )
            return $redirect_url;

        $country = $this->get_geolocation_country();

        if ( ! $country ) {
            return $redirect_url;
        }

        // find the destination url that matches the country
        foreach( $geolocations as $key => $geolocation ) {

            if ( strpos( $key , $country ) === false )
                continue;

            $redirect_url = $geolocation;
            break;
        }

        return $redirect_url;
    }

    /**
     * Get geolocation country (two character country code).
     *
     * @since 1.0.0
     * @access private
     *
     * @return string|null Two character country code, or null if not found.
     */
    private function get_geolocation_country() {

        // Get client IP and country code
        $client_ip = isset( $_SERVER[ 'REMOTE_ADDR' ] ) && ! empty( $_SERVER[ 'REMOTE_ADDR' ] ) ? $_SERVER[ 'REMOTE_ADDR' ] : getenv( 'REMOTE_ADDR' );

        // Test for forwarding proxy
        if ( get_option( 'tap_geolocations_disable_proxy_test' ) !== 'yes' && isset( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) && ! empty( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
            $client_ip = explode( ',', $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] );
            $client_ip = trim( $client_ip[0] );
        }

        $client_ip = apply_filters( 'tap_geolocation_client_ip', $client_ip );

        // DEBUG: manually overriding the IP address for testing
        if ( WP_DEBUG && current_user_can( 'administrator' ) && get_option( 'tap_geolocation_ip_address_debug' ) ) {
            $client_ip = get_option( 'tap_geolocation_ip_address_debug' );
        }

        $country_code = $this->_helper_functions->get_geolocation_country_by_ip( $client_ip );

        return apply_filters( 'tap_get_geolocation_country', $country_code, $client_ip );
    }

    /**
     * Download the Geolite2-Country.mmdb file
     *
     * @since 1.0.0
     * @access private
     */
    private function download_geolite2_mmdb_file() {

        if ( $this->geolite2_mmdb_update_check() ) {

            $date        = date( 'Y-m-d' );
            $dir         = $this->_constants->TA_UPLOADS_DIR();
            $file        = trailingslashit( $dir ) . strtotime( $date ) . '.mmdb.gz';
            $remove_file = $file;
            $host        = apply_filters( 'tap_geolite2_country_mmdb' , 'http://thirstyaffiliates.s3.amazonaws.com/maxmind-country-free/GeoLite-Country.mmdb.gz' );

            if( is_dir( $dir ) === false )
                  mkdir( $dir );

            if ( function_exists( 'curl_init' ) ) {

                $ch = curl_init();
                curl_setopt( $ch , CURLOPT_URL , $host );
                curl_setopt( $ch , CURLOPT_VERBOSE , 1 );
                curl_setopt( $ch , CURLOPT_RETURNTRANSFER , 1 );
                curl_setopt( $ch , CURLOPT_AUTOREFERER , false );
                curl_setopt( $ch , CURLOPT_REFERER , admin_url() );
                curl_setopt( $ch , CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
                curl_setopt( $ch , CURLOPT_HEADER , 0 );
                $result = curl_exec( $ch );
                curl_close( $ch );

                // the following lines write the contents to a file in the same directory (provided permissions etc)
                $fp = fopen( $file , "w" );
                fwrite( $fp , $result );
                fclose( $fp );
            }

            // Extract the file GeoLite2-Country.mmdb.gz
            if( file_exists( $file ) ){

                // Remove all .mmdb files
                array_map( 'unlink' , glob( $dir . '*.mmdb' ) );

                // Raising this value may increase performance
                $buffer_size = 4096; // read 4kb at a time

                // Open our files (in binary mode)
                $out_file_name = str_replace( '.gz' , '' , $file );
                $file          = gzopen( $file , 'rb' );
                $out_file      = fopen( $out_file_name , 'wb' );

                // Keep repeating until the end of the input file
                while ( ! gzeof( $file ) ) {
                    // Read buffer-size bytes
                    // Both fwrite and gzread and binary-safe
                    fwrite( $out_file , gzread( $file , $buffer_size ) );
                }

                // Files are done, close files
                fclose( $out_file );
                gzclose( $file );

                // Remove downloaded .mmdb.gz
                unlink( $remove_file );
                array_map( 'unlink' , glob( $dir . '*.gz' ) );
            }
        }
    }

    /**
     * Check if we need to download GeoLite2-Country.mmdb file again
     *
     * @since 1.0.0
     * @access public
     *
     * @return boolean True if file needs to be redownloaded, otherwise false.
     */
    private function geolite2_mmdb_update_check() {

        $today = current_time( "timestamp" );
        $dir   = $this->_constants->TA_UPLOADS_DIR();

        // If there's no thirstyaffilates folder or thirstyaffilates folder is empty
        if( is_dir( $dir ) === false )
            return true;

        $last_mod_file = $this->_helper_functions->get_geolite2_country_mmdb_file();
        $mod_file      = is_file( $dir . $last_mod_file ) ? explode( '.' , $last_mod_file ) : '';

        if ( is_array( $mod_file ) ) {

            $mod_file_plus_month = strtotime( "+1 Month" , $mod_file[0] );

            // If file is 1 month old we need to download another one to keep the file up to date
            if( $today >= $mod_file_plus_month )
                return true;
        }

        return $mod_file == '' ? true : false;
    }




    /*
    |--------------------------------------------------------------------------
    | AJAX methods
    |--------------------------------------------------------------------------
    */

    /**
     * AJAX Add / Edit single geolink.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_add_edit_single_geolink() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        else {

            $post_id   = (int) sanitize_text_field( $_POST[ 'post_id' ] );
            $countries = array_map( 'sanitize_text_field' , $_POST[ 'countries' ] );
            $link      = esc_url_raw( $_POST[ 'link' ] );
            $old_key   = sanitize_text_field( $_POST[ 'old_key' ] );
            $geolinks  = $this->add_edit_single_geolink( $post_id , $countries , $link , $old_key );

            if ( is_wp_error( $geolinks ) )
                $response = array( 'status' => 'fail' , 'error_msg' => $geolinks->get_error_message() , 'error_data' => $geolinks->get_error_data() );
            else {

                $row_markup = $this->get_single_geolink_group_markup( $countries , $link );
                $countries  = $this->get_updated_countries_selectize( $geolinks );
                $response   = array( 'status'  => 'success' , 'row_markup' => $row_markup , 'countries_selectize' => $countries );
            }
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX Add single geolink.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_remove_single_geolink() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        else {

            $post_id       = (int) sanitize_text_field( $_POST[ 'post_id' ] );
            $countries_key = sanitize_text_field( $_POST[ 'countries_key' ] );
            $geolinks      = $this->remove_single_geolink( $post_id , $countries_key );

            if ( is_wp_error( $geolinks ) )
                $response = array( 'status' => 'fail' , 'error_msg' => $geolinks->get_error_message() , 'error_data' => $geolinks->get_error_data() );
            else {

                $countries = $this->get_updated_countries_selectize( $geolinks );
                $response  = array( 'status'  => 'success' , 'countries_selectize' => $countries );
            }
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }




    /*
    |--------------------------------------------------------------------------
    | Helper methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get single geolink group markup.
     *
     * @since 1.0.0
     * @access private
     *
     * @param array  $countries       List of country codes.
     * @param string $destination_url Destination url for the set countries.
     * @return string Single geolink group markup.
     */
    private function get_single_geolink_group_markup( $countries , $destination_url ) {

        ob_start(); ?>

        <tr class="geolink new">
            <td><?php echo implode( ',' , $countries ); ?></td>
            <td>
                <span title="<?php echo esc_attr( $destination_url ); ?>">
                    <?php echo mb_strimwidth( $destination_url , 0 , 71 , '[...]' ); ?>
                </span>
            </td>
            <td class="actions">
                <a class="link" href="<?php echo esc_attr( $destination_url ); ?>" target="_blank"><span class="dashicons dashicons-admin-links"></span></a>
                <a class="edit" href="#"><span class="dashicons dashicons-edit"></span></a>
                <a class="remove" href="#"><span class="dashicons dashicons-no"></span></a>
            </td>
        </tr>

        <?php
        return ob_get_clean();
    }

    /**
     * Get updated countries select option markup.
     *
     * @since 1.0.0
     * @access private
     *
     * @param array  $countries       List of country codes.
     * @return string Updated countries select option markup.
     */
    private function get_updated_countries_selectize( $geolinks ) {

        $countries = $this->_helper_functions->get_available_countries( $geolinks );
        $formatted = array();

        if ( ! is_array( $countries ) || empty( $countries ) )
            return $formatted;

        foreach( $countries as $code => $name )
            $formatted[] = array( 'value' => $code , 'text' => $name );

        return $formatted;
    }

    /**
     * Fix all existing destination urls in db.
     *
     * @since 1.1.3
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param array $link_ids All published affiliate link IDs.
     */
    public function fix_all_geolocation_urls_in_db( $link_ids ) {

        global $wpdb;

        $link_ids_str = implode( ',' , array_map( 'intval' , $link_ids ) );
        $results      = $wpdb->get_results( "SELECT meta_id , post_id , meta_value FROM $wpdb->postmeta WHERE meta_key = '_ta_geolocation_links' AND post_id IN ( $link_ids_str )" );

        if ( ! is_array( $results ) || empty( $results ) )
            return;

        foreach ( $results as $row ) {

            $geolinks = maybe_unserialize( $row->meta_value );

            if ( is_array( $geolinks )) {

                foreach ( $geolinks as $key => $geolink ) {
                    $geolinks[ $key ] = str_replace( array( '&amp;amp;', '&amp;', '&#038;' ), '&', $geolink );
                }

                update_post_meta( $row->post_id , '_ta_geolocation_links' , $geolinks );

            }
        }
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

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_geolocation' , 'yes' ) !== 'yes' )
            return;

        $previous_version = is_multisite() ? get_site_option( Plugin_Constants::INSTALLED_VERSION ) : get_option( Plugin_Constants::INSTALLED_VERSION );

        if ( $previous_version && version_compare( $previous_version, '1.7.1', '<=' ) ) {
            $file = $this->_helper_functions->get_geolite2_country_mmdb_file();

            if ( $file ) {
                $path = $this->_constants->TA_UPLOADS_DIR() . $file;

                if ( is_file( $path ) ) {
                    unlink( $path );
                }
            }
        }

        // download geolite2 country mmdb
        $this->download_geolite2_mmdb_file();
    }

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.0.0
     * @access public
     * @inherit ThirstyAffiliates_Pro\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_geolocation' , 'yes' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_tap_add_single_geolink' , array( $this , 'ajax_add_edit_single_geolink' ) , 10 );
        add_action( 'wp_ajax_tap_remove_single_geolink' , array( $this , 'ajax_remove_single_geolink' ) , 10 );
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

        // needs to run even when module is disabled.
        add_action( 'tap_fix_all_links' , array( $this , 'fix_all_geolocation_urls_in_db' ) , 10 , 1 );

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_geolocation' , 'yes' ) !== 'yes' )
            return;

        add_filter( 'ta_filter_redirect_url' , array( $this , 'geolocation_redirect' ) , 10 , 2 );
        add_filter( 'ta_uncloak_link_url' , array( $this , 'geolocation_redirect' ) , 10 , 2 );
    }

}
