<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the URL_Shortener module.
 *
 * @since 1.1.0
 */
class URL_Shortener implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of URL_Shortener.
     *
     * @since 1.1.0
     * @access private
     * @var Settings_Extension
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.1.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.1.0
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
     * @since 1.1.0
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
     * @since 1.1.0
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
     * Shorten URL via Goo.gl service.
     *
     * @since 1.1.0
     * @access private
     *
     * @param string $long_url URL to shorten
     * @return WP_Error | string WP_Error object on failure, Googl Short URL equivalent otherwise.
     */
    private function googl_shorten_url( $long_url ) {

        $api_key = get_option( 'tap_googl_api_key' );

        if ( ! $api_key )
            return new \WP_Error( 'tap_failed_googl_shorten' , __( 'The required goo.gl api key is missing.' , 'thirstyaffiliates-pro' ) , array( 'access_token' => $api_key , 'long_url' => $long_url ) );

        if ( ! filter_var( $long_url , FILTER_VALIDATE_URL ) )
            return new \WP_Error( 'tap_failed_googl_shorten' , __( 'The provided URL to shorten is not valid.' , 'thirstyaffiliates-pro' ) , array( 'access_token' => $api_key , 'long_url' => $long_url ) );

        $api_url  = Plugin_Constants::GOOGL_SHORTENER_URL . '?key=' . $api_key;
        $args     = array(
            'method'  => 'POST',
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => '{"longUrl": "' . $long_url .'"}'
        );

        $api_call = wp_remote_get( $api_url , $args );

        // don't proceed if error
        if ( is_object( $api_call ) && is_wp_error( $api_call ) )
            return $api_call;

        $response = json_decode( $api_call[ 'body' ] , true );

        if ( ! isset( $response[ 'id' ] ) || isset( $response[ 'error' ] ) )
            return new \WP_Error( 'tap_failed_googl_shorten' , __( 'There was an error on trying to shorten the provided URL. Make sure that the inputted API key and/or URL is valid.' , 'thirstyaffiliates-pro' ) , array( 'api_key' => $api_key , 'long_url' => $long_url , 'googl_response' => $response ) );

        return $response[ 'id' ];
    }

    /**
     * Shorten URL via Bitly service.
     *
     * @since 1.1.0
     * @access private
     *
     * @param string $long_url URL to shorten
     * @return WP_Error | string WP_Error object on failure, Bitly Short URL equivalent otherwise.
     */
    private function bitly_shorten_url( $long_url ) {

        $token = get_option( 'tap_bitly_access_token' );

        if ( ! $token )
            return new \WP_Error( 'tap_failed_bitly_shorten' , __( 'The required bitly access token is missing.' , 'thirstyaffiliates-pro' ) , array( 'access_token' => $token , 'long_url' => $long_url ) );

        if ( ! filter_var( $long_url , FILTER_VALIDATE_URL ) )
            return new \WP_Error( 'tap_failed_bitly_shorten' , __( 'The provided URL to shorten is not valid.' , 'thirstyaffiliates-pro' ) , array( 'access_token' => $token , 'long_url' => $long_url ) );

        $response = json_decode( file_get_contents( Plugin_Constants::BITLY_API_URL . 'shorten?access_token=' . $token . '&longUrl=' . $long_url ) , true );

        if ( ! isset( $response[ 'status_code' ] ) || $response[ 'status_code' ] != 200 || ! isset( $response[ 'data' ][ 'url' ] ) )
            return new \WP_Error( 'tap_failed_bitly_shorten' , __( 'There was an error on trying to shorten the provided URL. Make sure that the inputted access token and/or URL is valid.' , 'thirstyaffiliates-pro' ) , array( 'access_token' => $token , 'long_url' => $long_url , 'bitly_response' => $response ) );

        return $response[ 'data' ][ 'url' ];
    }

    /**
     * Shorten URL via Firebase dynamic link service.
     *
     * @since 1.3.0
     * @access private
     *
     * @param string $long_url URL to shorten
     * @return WP_Error | string WP_Error object on failure, Bitly Short URL equivalent otherwise.
     */
    private function firebase_dynamic_links_shorten_url( $long_url ) {

        $domain  = get_option( 'tap_firebase_dynamic_link_domain' );
        $api_key = get_option( 'tap_firebase_dynamic_links_api_key' );

        if ( ! $api_key )
            return new \WP_Error( 'tap_failed_firebase_dynamic_links_shorten' , __( 'The required Firebase API key is missing.' , 'thirstyaffiliates-pro' ) , array( 'api_key' => $api_key , 'long_url' => $long_url ) );

        if ( ! filter_var( $long_url , FILTER_VALIDATE_URL ) )
            return new \WP_Error( 'tap_failed_firebase_dynamic_links_shorten' , __( 'The provided URL to shorten is not valid.' , 'thirstyaffiliates-pro' ) , array( 'api_key' => $api_key , 'long_url' => $long_url ) );

        $api_url  = Plugin_Constants::FIREBASE_DL_SHORTENER_URL . '?key=' . $api_key;
        $args     = array(
            'method'  => 'POST',
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => json_encode( array(
                'longDynamicLink' => $domain . '?link=' . $long_url,
                'suffix'          => array( 'option' => 'SHORT' )
            ) , JSON_UNESCAPED_SLASHES )
        );

        $api_call = wp_remote_get( $api_url , $args );

        // don't proceed if error
        if ( is_object( $api_call ) && is_wp_error( $api_call ) )
            return $api_call;

        $response = json_decode( $api_call[ 'body' ] , true );

        if ( isset( $response[ 'error' ] ) || ! isset( $response[ 'shortLink' ] ) ) {
            $message = isset( $response[ 'error' ][ 'message' ] ) ? $response[ 'error' ][ 'message' ] : __( 'There was an error trying to generate the short URL. Please try again.' , 'thirstyaffiliates-pro' );
            return new \WP_Error( 'tap_failed_firebase_dynamic_links_shorten' , $message , array( 'api_key' => $api_key , 'long_url' => $long_url ) );
        }

        return $response[ 'shortLink' ];
    }

    /**
     * Generic short url function.
     *
     * @since 1.1.0
     * @since 1.3.0 Add Firebase Dynamic Links option.
     * @access private
     *
     * @param string $long_url URL to shorten
     * @return WP_Error | string WP_Error object on failure, Short URL equivalent otherwise.
     */
    private function shorten_url( $long_url ) {

        $service = get_option( 'tap_url_shortener_service' , 'bitly' );

        switch ( $service ) {

            case 'firebasedl' :
                $short_url = $this->firebase_dynamic_links_shorten_url( $long_url );
                break;

            case 'googl' :
                $short_url = $this->googl_shorten_url( $long_url );
                break;

            case 'bitly' :
            default :
                $short_url = $this->bitly_shorten_url( $long_url );
                break;
        }

        return $short_url;
    }

    /**
     * AJAX shorten affiliate link destination URL.
     *
     * @since 1.1.0
     * @access public
     */
    public function ajax_shorten_affiliate_link_destination_url() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'tap_shorten_affiliate_link_destination_url' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        else {

            $link_id = isset( $_POST[ 'link_id' ] ) ? (int) sanitize_text_field( $_POST[ 'link_id' ] ) : 0;

            if ( get_post_status( $link_id ) != 'publish' )
                $response = array( 'status' => 'fail' , 'error_msg' => __( 'The affiliate link must be on "publish" status before shortening URL.' , 'thirstyaffiliates-pro' ) );
            else {

                $long_url  = esc_url_raw( get_permalink( $link_id ) );
                $short_url = $this->shorten_url( $long_url );

                if ( is_wp_error( $short_url ) )
                    $response = array( 'status' => 'fail' , 'error_msg' => $short_url->get_error_message() );
                else {
                    update_post_meta( $link_id , Plugin_Constants::META_DATA_PREFIX . 'shortened_url' , $short_url );
                    $response = array( 'status' => 'success' , 'short_url' => $short_url );
                }
            }
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Generate short URL when the affilate link is saved and the destination_url has changed.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array          $changes     List of changes on the Affiliate link object.
     * @param Affiliate_Link $thirstylink Affiliate_Link object.
     */
    public function generate_short_url_on_affiliate_link_save( $changes , $thirstylink ) {

        if ( $thirstylink->get_prop( 'status' ) != 'publish' || get_option( 'tap_generate_short_url_on_affiliate_link_save' ) != 'yes' )
            return;

        $long_url  = $thirstylink->get_prop( 'permalink' );
        $short_url = $this->shorten_url( $long_url );

        if ( is_wp_error( $short_url ) )
            $this->_helper_functions->write_debug_log( $short_url->get_error_message() . ' ' . print_r( $short_url->get_error_data() , true ) );
        else
            update_post_meta( $thirstylink->get_id() , Plugin_Constants::META_DATA_PREFIX . 'shortened_url' , $short_url );
    }

    /**
     * Check if URL Shortener is configured or not.
     *
     * @since 1.1.2
     * @since 1.2.0 Add service used and current active service in the condition parameters.
     * @since 1.3.0 Add support for firebase dynamic links.
     * @access private
     *
     * @param string $service_used  Service used on the existing shortened URL.
     * @param string $shortened_url Shortened URL.
     * @return bool True if configured, otherwise false.
     */
    private function is_configured( $service_used = '' , $shortened_url = null ) {

        $active_service       = get_option( 'tap_url_shortener_service' , 'bitly' );
        $shortener_api_option = $this->_helper_functions->get_url_shortener_api_option( $active_service );

        // If active service is firebase, then we should also check for the dynamic link domain option.
        if ( ! is_null( $shortened_url ) && $active_service === 'firebasedl' && ! get_option( 'tap_firebase_dynamic_link_domain' ) )
            return;

        return $service_used !== $active_service && get_option( $shortener_api_option );
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
    public function shortened_url_cpt_column( $columns ) {

        $updated_columns = array();

        foreach ( $columns as $key => $column ) {

            $updated_columns[ $key ] = $column;

            if ( $key == 'taxonomy-thirstylink-category' && $this->is_configured() )
                $updated_columns[ 'shortened_url' ] = __( 'Shortened URL' , 'thirstyaffiliates-pro' );
        }

        return $updated_columns;
    }

    /**
     * Link health status CPT column value.
     *
     * @since 1.1.0
     * @since 1.2.0 Added code to display button as "regenerate" when the used service is different with the currently active one in the setting.
     * @access public
     *
     * @param string $column Affiliate link single column.
     * @param Affiliate_Link $thirstylink Affiliate_Link object.
     */
    public function shortened_url_cpt_column_value( $column , $thirstylink ) {

        if ( $column != 'shortened_url' || $thirstylink->get_prop( 'status' ) != 'publish' )
            return;

        $shortened_url = $thirstylink->get_prop( 'shortened_url' );
        $input_style   = ( ! $shortened_url ) ? 'display:none;' : '';
        $service_used  = '';

        if ( $shortened_url )
            $service_used = $this->_helper_functions->detect_url_shortener_service_used( $shortened_url );

        $button_text   = $service_used && $shortened_url ? __( 'Regenerate' , 'thirstyaffiliates-pro' ) : __( 'Generate' , 'thirstyaffiliates-pro' );

        echo '<input type="text" value="' . $shortened_url . '" readonly style="' . $input_style . 'width:100%;">';

        if ( $this->is_configured( $service_used , $shortened_url ) ) {

            echo '<button type="button" class="button" data-link_id="' . $thirstylink->get_id() . '" data-nonce="' . wp_create_nonce( 'tap_shorten_affiliate_link_destination_url' ) . '">' . $button_text . '</button>';
            echo '<span class="tap-spinner"></span>';
        }
    }




    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.1.0
     * @access public
     * @inherit ThirstyAffiliates_Pro\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_url_shortener' , 'yes' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_tap_shorten_url' , array( $this , 'ajax_shorten_affiliate_link_destination_url' ) , 10 );
    }

    /**
     * Execute model.
     *
     * @implements ThirstyAffiliates_Pro\Interfaces\Model_Interface
     *
     * @since 1.1.0
     * @access public
     */
    public function run() {

        if ( get_option( 'tap_enable_url_shortener' , 'yes' ) !== 'yes' )
            return;

        add_filter( 'ta_post_listing_custom_columns' , array( $this , 'shortened_url_cpt_column' ) );
        add_action( 'ta_post_listing_custom_columns_value' , array( $this , 'shortened_url_cpt_column_value' ) , 10 , 2 );
        add_action( 'ta_save_affiliate_link' , array( $this , 'generate_short_url_on_affiliate_link_save' ) , 10 , 2 );
    }

}
