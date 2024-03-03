<?php
namespace ThirstyAffiliates_Pro\Models\Third_Party_Integrations\Amazon;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Amazon implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Amazon.
     *
     * @since 1.0.0
     * @access private
     * @var Amazon
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
     * @return Amazon
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }

    /**
     * Register admin interfaces.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of admin interfaces.
     * @return array Filtered list of admin interfaces.
     */
    public function register_admin_interfaces( $interfaces ) {

        $interfaces[ 'thirstylink_page_amazon' ] = 'manage_options';

        return $interfaces;
    }

    /**
     * Register admin interfaces.
     *
     * @since 3.3.3
     * @access public
     *
     * @param array $interfaces List of menu items.
     * @return array Filtered list of menu items.
     */
    public function register_admin_menu_items( $menu_items ) {

        $menu_items[ 'amazon' ] = 'manage_categories';

        return $menu_items;
    }

    /**
     * Add amazon page.
     *
     * @since 1.0.0
     * @access public
     */
    public function add_amazon_page() {

        add_submenu_page(
            'edit.php?post_type=thirstylink',         // Part of query string or Identifier ( ex. woocommerce ) of the main plugin menu you want to attache with
            __( 'Amazon Import' , 'thirstyaffiliates-pro' ), // Page Title
            __( 'Amazon Import' , 'thirstyaffiliates-pro' ), // Menu Title
            'edit_posts',                                   // Capabilities
            'amazon',                                 // Slug
            array( $this, 'view_amazon_page' )        // Callback
        );

    }

    /**
     * Amazon page view.
     *
     * @since 1.0.0
     * @access public
     */
    public function view_amazon_page() {

        $amazon_access_key_id    = trim( get_option( 'tap_amazon_access_key_id' ) );
        $amazon_secret_key       = trim( get_option( 'tap_amazon_secret_key' ) );
        $amazon_associate_tags   = get_option( 'tap_amazon_associate_tags' );
        $amazon_search_indexes   = $this->_constants->AMAZON_SEARCH_INDEX();     // a.k.a Categories
        $amazon_search_endpoints = $this->_constants->AMAZON_SEARCH_COUNTRIES(); // The country to search
        $active_search_countries = array();
        $default_search_index    = array();
        $active_search_indexes   = array();
        $error_message           = '';

        $valid_keys = $this->check_if_amazon_keys_are_entered( $amazon_access_key_id , $amazon_secret_key , $amazon_associate_tags );

        if ( $valid_keys !== true )
            $error_message = $valid_keys;
        else {

            $valid_associate_tags = $this->check_if_entered_associate_tags_are_valid( $amazon_associate_tags , $amazon_search_indexes , $amazon_search_endpoints );

            if ( !is_array( $valid_associate_tags ) )
                $error_message = $valid_associate_tags;
            else {

                $last_used_search_endpoint = get_option( Plugin_Constants::LAST_USED_SEARCH_ENDPOINT );
                $active_search_countries   = $valid_associate_tags;
                $default_search_index      = ( $last_used_search_endpoint && array_key_exists( $last_used_search_endpoint , $active_search_countries ) ) ? $amazon_search_indexes[ $last_used_search_endpoint ] : $amazon_search_indexes[ current( array_keys( $active_search_countries ) ) ];

                foreach ( $active_search_countries as $country_code => $value )
                    $active_search_indexes[ $country_code ] = $amazon_search_indexes[ $country_code ];

            }

        }

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'amazon/view-amazon-page.php' );

    }

    /**
     * Amazon page screen options.
     * References:
     * // https://www.joedolson.com/2013/01/custom-wordpress-screen-options/
     * // https://chrismarslender.com/2012/01/26/wordpress-screen-options-tutorial/
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $status Screen option wrapper markup.
     * @param object $args   Object variable that specify data about the current page.
     * @return string Filtered screen option markup.
     */
    public function amazon_page_screen_option( $status , $args ) {

        $amazon_table_columns   = get_option( Plugin_Constants::AMAZON_TABLE_COLUMNS );
        $price_checked_str      = ( isset( $amazon_table_columns[ "price" ] ) && $amazon_table_columns[ "price" ] == "yes" ) ? "checked" : "";
        $item_stock_checked_str = ( isset( $amazon_table_columns[ "item-stock" ] ) && $amazon_table_columns[ "item-stock" ] == "yes" ) ? "checked" : "";
        $sales_rank_checked_str = ( isset( $amazon_table_columns[ "sales-rank" ] ) && $amazon_table_columns[ "sales-rank" ] == "yes" ) ? "checked" : "";
        $return                 = $status;

        if ( $args->base === 'thirstylink_page_amazon' ) {

            $return .= "<fieldset>
                            <legend>" . __( 'Show Columns' , 'thirstyaffiliates-pro' ) . "</legend>
                            <div class='metabox-prefs'>
                                <div id='tap-amazon-table-fields'>
                                    <label for='tap-column-price'><input type='checkbox' value='yes' id='tap-column-price' class='column-check-field' autocomplete='off' $price_checked_str/> " . __( 'Price' , 'thirstyaffiliates-pro' ) . "</label>
                                    <label for='tap-column-item-stock'><input type='checkbox' value='yes' id='tap-column-item-stock' class='column-check-field' autocomplete='off' $item_stock_checked_str/> " . __( 'Item Stock' , 'thirstyaffiliates-pro' ) . "</label>
                                    <label for='tap-column-sales-rank'><input type='checkbox' value='yes' id='tap-column-sales-rank' class='column-check-field' autocomplete='off' $sales_rank_checked_str/> " . __( 'Sales Rank' , 'thirstyaffiliates-pro' ) . "</label>
                                </div>
                            </div>
                        </fieldset>";
        }

        return $return;

    }

    /**
     * Generate unique transient key.
     *
     * @since 1.1.0
     * @access public
     *
     * @param string $search_keyword  Search keyword.
     * @param string $search_index    Search index.
     * @param string $search_endpoint Search endpoint.
     * @param int    $item_page       Item page number.
     * @return string Generated transient key.
     */
    public function generate_transient_key( $search_keyword , $search_index , $search_endpoint, $item_page ) {

        return 'tap_azon_' . sanitize_title( $search_keyword ) . '_' . $search_index . '_' . $search_endpoint . '_' . $item_page;

    }

    /**
     * Add transient key to the list of transients amazon module created.
     * Will be used later for cleaning up.
     *
     * @since 1.1.0
     * @access public
     *
     * @param string $transient_key Transient key.
     */
    public function add_to_amazon_transients( $transient_key ) {

        if ( empty( $transient_key ) )
            return;

        $generated_transients = get_option( Plugin_Constants::AMAZON_GENERATED_TRANSIENTS );
        if ( !is_array( $generated_transients ) )
            $generated_transients = array();

        if ( !in_array( $transient_key , $generated_transients ) ) {

            $generated_transients[] = $transient_key;

            update_option( Plugin_Constants::AMAZON_GENERATED_TRANSIENTS , $generated_transients );

        }

    }




    /*
    |--------------------------------------------------------------------------
    | AJAX Callbacks
    |--------------------------------------------------------------------------
    */

    /**
     * Ajax query amazon product advertisement api.
     *
     * @since 1.0.0
     * @since 1.1.0 Add search result caching via transient
     * @access public
     */
    public function ajax_amazon_product_advertisement_api_search() {

        set_time_limit( 0 );              // No timeout limit
        ini_set( 'memory_limit' , '-1' ); // No memory limit

        $search_keywords = isset( $_POST[ 'search-keywords' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'search-keywords' ] ) ) : '';

        if ( !defined( "DOING_AJAX" ) || !DOING_AJAX )
            $response = array( 'status' => 'fail' , 'message' => __( 'Invalid AJAX Operation' , 'thirstyaffiliates-pro' ) );
        elseif ( !$search_keywords || !isset( $_POST[ 'search-index' ], $_POST[ 'search-endpoint' ], $_POST[ 'item-page' ], $_POST[ 'ajax-nonce' ] ) )
            $response = array( 'status' => 'fail' , 'message' => __( 'Required parameters not supplied' , 'thirstyaffiliates-pro' ) );
        elseif ( !check_ajax_referer( 'tap_amazon_product_advertisement_api_search' , 'ajax-nonce' , false ) )
            $response = array( 'status' => 'fail' , 'message' => __( 'Security check failed' , 'thirstyaffiliates-pro' ) );
        else {

            $country_code = sanitize_text_field( wp_unslash( $_POST[ 'search-endpoint' ] ) );
            $search_index = sanitize_text_field( wp_unslash( $_POST[ 'search-index' ] ) );
            $item_page = $this->_sanitize_item_page( $_POST[ 'item-page' ] );
            $transient_key = $this->generate_transient_key( $search_keywords, $search_index, $country_code, $item_page );
            $transient_enabled = get_option( 'tap_enable_azon_transient' );

            if ( $transient_enabled === 'yes' && $transient_data = get_transient( $transient_key ) ) {

                $response = array(
                    'status' => 'success',
                    'error_code' => null,
                    'message' => __( 'Product search success' , 'thirstyaffiliates-pro' ),
                    'results' => $transient_data
                );

            } else {

                $results = array();
                $critical_error_code    = null;
                $critical_error_msg     = null;

                // Amazon keys
                $amazon_keys = array(
                    'amazon_access_key_id'  => get_option( 'tap_amazon_access_key_id' ),
                    'amazon_secret_key'     => get_option( 'tap_amazon_secret_key' ),
                    'amazon_associate_tags' => get_option( 'tap_amazon_associate_tags' )
                );

                if ( !array_key_exists( $country_code , $amazon_keys[ 'amazon_associate_tags' ] ) ) {

                    $critical_error_code = "TAP.MissingAmazonAssociateTag";
                    $critical_error_msg  = __( 'You do not have an Amazon Associate Tag for the current country you are currently conducting a product search' , 'thirstyaffiliates-pro' );

                } else {

                    try {

                        $params = array(
                            'Keywords' => $search_keywords,
                            'ItemCount' => 10,
                            'ItemPage' => $item_page,
                            'Resources' => array(
                                'ItemInfo.Title',
                                'Offers.Listings.Price',
                                'Offers.Summaries.OfferCount',
                                'Images.Primary.Small',
                                'Images.Primary.Medium',
                                'Images.Primary.Large',
                                'BrowseNodeInfo.WebsiteSalesRank'
                            ),
                            'SearchIndex' => $search_index,
                            'PartnerTag' => $amazon_keys['amazon_associate_tags'][$country_code],
                            'PartnerType' => 'Associates'
                        );

                        $amazon_response = $this->_query_amazon_api(
                            '/paapi5/searchitems',
                            'SearchItems',
                            $params,
                            $country_code,
                            $amazon_keys
                        );

                        if ( isset( $amazon_response['SearchResult']['Items'] ) && is_array( $amazon_response['SearchResult']['Items'] ) ) {
                            $results = $amazon_response['SearchResult']['Items'];
                        }

                        if ( isset($amazon_response['Errors']) && is_array( $amazon_response['Errors'] ) ) {

                            foreach ( $amazon_response['Errors'] as $error ) {

                                if ( in_array( $error['Code'], $this->_constants->AMAZON_API_CRITICAL_ERRORS() ) ) {

                                    $critical_error_code = $error['Code'];
                                    $critical_error_msg = $error['Message'];
                                    break;

                                }

                            }

                        }

                    } catch ( \Exception $e ) {

                        $critical_error_code = "TAP.AmazonQueryException";
                        $critical_error_msg  = $e->getMessage();

                    }

                }

                $response = array(
                    'status' => !is_null( $critical_error_code ) ? 'fail' : 'success',
                    'error_code' => $critical_error_code,
                    'message' => !is_null( $critical_error_msg ) ? $critical_error_msg : __( 'Product search success' , 'thirstyaffiliates-pro' ),
                    'results' => $results
                );

                // Only create transient for successful api queries
                if ( $transient_enabled === 'yes' && is_null( $critical_error_code ) && is_null( $critical_error_msg ) ) {

                    set_transient( $transient_key, $results, get_option( 'tap_azon_transient_lifespan' , 7 ) * DAY_IN_SECONDS );
                    $this->add_to_amazon_transients( $transient_key );

                }

            }

        }

        wp_send_json( $response );

    }

    /**
     * Send a request to the Amazon API
     *
     * @param string $resource_path The resource path of the request e.g. '/paapi5/searchitems'
     * @param string $operation     The request operation e.g. 'SearchItems'
     * @param array  $params        The request parameters
     * @param string $country_code  The two letter country code
     * @param array  $amazon_keys   The Amazon keys
     * @return array                The response data
     * @throws \Exception           If there was an error
     */
    private function _query_amazon_api( $resource_path, $operation, array $params, $country_code, array $amazon_keys) {

        $api_end_points = $this->_constants->AMAZON_SEARCH_ENDPOINTS();
        $host = $api_end_points[$country_code];
        $now = date_create( 'now' );
        $x_amz_date = $now->format( 'Ymd\THis\Z' );
        $current_date = $now->format( 'Ymd' );
        $api_regions = $this->_constants->AMAZON_SEARCH_REGIONS();
        $region = $api_regions[$country_code];
        $payload = wp_json_encode( $params );

        $headers = array(
            'Content-Encoding' => 'amz-1.0',
            'Content-Type' => 'application/json; charset=utf-8',
            'Host' => $host,
            'X-Amz-Date' => $x_amz_date,
            'X-Amz-Target' => 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.' . $operation
        );

        $headers['Authorization'] = $this->_get_authorization_header( $resource_path, $headers, $payload, $x_amz_date, $current_date, $region, $amazon_keys );

        $response = wp_remote_post(
            "https://{$host}{$resource_path}",
            array(
                'headers' => $headers,
                'body' => $payload
            )
        );

        if( is_wp_error( $response ) ) {
            throw new \Exception( $response->get_error_message() );
        }

        $results = json_decode( wp_remote_retrieve_body( $response ), true );

        if( !is_array( $results ) ) {
            throw new \Exception( __( 'Response was invalid or empty', 'thirstyaffiliates-pro' ) );
        }

        return $results;
    }

    /**
     * Get the Authorization header value
     *
     * @param  string $resource_path The resource path of the request e.g. '/paapi5/searchitems'
     * @param  array  $headers       The request headers
     * @param  string $payload       The request payload (JSON body)
     * @param  string $x_amz_date    The request date and time
     * @param  string $current_date  The current date
     * @param  string $region        The AWS region for this request
     * @param  array  $amazon_keys   The Amazon keys
     * @return string
     */
    private function _get_authorization_header($resource_path, array $headers, $payload, $x_amz_date, $current_date, $region, array $amazon_keys) {
        $canonical_url = $this->_prepare_canonical_request( $resource_path, $headers, $payload );
        $string_to_sign = $this->_prepare_string_to_sign( $canonical_url, $x_amz_date, $current_date, $region );
        $signature_key = $this->_get_signature_key( $amazon_keys['amazon_secret_key'], $current_date, $region );
        $signature = $this->_calculate_signature( $string_to_sign, $signature_key );

        return $this->_build_authorization_string( $amazon_keys['amazon_access_key_id'], $current_date, $region, $headers, $signature );
    }

    /**
     * Get the canonical request
     *
     * @param  string $resource_path The resource path of the request e.g. '/paapi5/searchitems'
     * @param  array  $headers       The request headers
     * @param  string $payload       The request payload (JSON body)
     * @return string
     */
    private function _prepare_canonical_request( $resource_path, array $headers, $payload ) {
        $canonical_url = "POST\n";
        $canonical_url .= "$resource_path\n\n";

        foreach ($headers as $key => $value) {
            $canonical_url .= strtolower( $key ) . ":$value\n";
        }

        $canonical_url .= "\n";
        $canonical_url .= join( ';', array_map( 'strtolower', array_keys( $headers ) ) ) . "\n";
        $canonical_url .= hash( 'sha256', $payload );

        return $canonical_url;
    }

    /**
     * Get the string to sign
     *
     * @param  string $canonical_url The canonical request
     * @param  string $x_amz_date    The request date and time
     * @param  string $current_date  The current date
     * @param  string $region        The AWS region for this request
     * @return string
     */
    private function _prepare_string_to_sign( $canonical_url, $x_amz_date, $current_date, $region ) {
        $string_to_sign = "AWS4-HMAC-SHA256\n";
        $string_to_sign .= "$x_amz_date\n";
        $string_to_sign .= "$current_date/$region/ProductAdvertisingAPI/aws4_request\n";
        $string_to_sign .= hash( 'sha256', $canonical_url );

        return $string_to_sign;
    }

    /**
     * Get the signature key
     *
     * @param  string $secret_key   The Amazon Secret Key
     * @param  string $current_date The current date
     * @param  string $region       The AWS region for this request
     * @return string
     */
    private function _get_signature_key( $secret_key, $current_date, $region ) {
        $current_date_key = hash_hmac( 'sha256', $current_date , 'AWS4' . $secret_key, true );
        $region_key = hash_hmac( 'sha256', $region, $current_date_key, true );
        $service_key = hash_hmac( 'sha256', 'ProductAdvertisingAPI', $region_key, true );
        $signing_key = hash_hmac( 'sha256', 'aws4_request', $service_key, true );

        return $signing_key;
    }

    /**
     * Get the signature
     *
     * @param  string $string_to_sign The string to sign
     * @param  string $signature_key  The signature key
     * @return string
     */
    private function _calculate_signature( $string_to_sign, $signature_key ) {
        $signature = hash_hmac( 'sha256', $string_to_sign, $signature_key, true );

        return strtolower( bin2hex( $signature ) );
    }

    /**
     * Get the authorization string
     *
     * @param  string $access_key   The Amazon Access Key
     * @param  string $current_date The current date
     * @param  string $region       The AWS region for this request
     * @param  array  $headers      The request headers
     * @param  string $signature    The signature
     * @return string
     */
    private function _build_authorization_string( $access_key, $current_date, $region, $headers, $signature ) {
        $signed_headers = join( ';', array_map( 'strtolower', array_keys( $headers ) ) );

        return "AWS4-HMAC-SHA256 Credential=$access_key/$current_date/$region/ProductAdvertisingAPI/aws4_request,SignedHeaders=$signed_headers,Signature=$signature";
    }

    /**
     * AJAX set amazon table visible columns.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_set_amazon_table_visible_columns() {

        if ( !defined( "DOING_AJAX" ) || !DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX Operation' , 'thirstyaffiliates-pro' ) );
        elseif ( !isset( $_POST[ 'amazon-table-columns' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameters not supplied' , 'thirstyaffiliates-pro' ) );
        elseif ( !check_ajax_referer( 'tap_set_amazon_table_visible_columns' , 'ajax-nonce' , false ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Security check failed' , 'thirstyaffiliates-pro' ) );
        else {

            if ( is_array( $_POST[ 'amazon-table-columns' ] ) )
                update_option( Plugin_Constants::AMAZON_TABLE_COLUMNS , $_POST[ 'amazon-table-columns' ] );

            $response = array( 'status' => 'success' , 'success_msg' => __( 'Successfully saved amazon table columns' , 'thirstyaffiliates-pro' ) );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * AJAX set last used search endpoint.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_set_last_used_search_endpoint() {

        if ( !defined( "DOING_AJAX" ) || !DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX Operation' , 'thirstyaffiliates-pro' ) );
        elseif ( !isset( $_POST[ 'amazon-search-endpoint' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameters not supplied' , 'thirstyaffiliates-pro' ) );
        elseif ( !check_ajax_referer( 'tap_set_last_used_search_endpoint' , 'ajax-nonce' , false ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Security check failed' , 'thirstyaffiliates-pro' ) );
        else {

            update_option( Plugin_Constants::LAST_USED_SEARCH_ENDPOINT , $_POST[ 'amazon-search-endpoint' ] );

            $response = array( 'status' => 'success' , 'success_msg' => __( 'Successfully saved last used search endpoint' , 'thirstyaffiliates-pro' ) );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * Ajax import amazon product as an affiliate link.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_import_link() {

        if ( !defined( "DOING_AJAX" ) || !DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX Operation' , 'thirstyaffiliates-pro' ) );
        elseif ( !isset( $_POST[ 'link-data' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameters not supplied' , 'thirstyaffiliates-pro' ) );
        elseif ( !check_ajax_referer( 'tap_import_link' , 'ajax-nonce' , false ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Security check failed' , 'thirstyaffiliates-pro' ) );
        else {

            $current_user = wp_get_current_user();
            $link_name    = sanitize_text_field( $_POST[ 'link-data' ][ 'link-name' ] );
            $new_post     = array(
                'post_author'   => $current_user->ID,
                'post_date'     => current_time( 'mysql' ),
                'post_date_gmt' => current_time( 'mysql' , true ),
                'post_title'    => $link_name,
                'post_status'   => 'publish',
                'post_type'     => 'thirstyLink'
            );

            $link_id = wp_insert_post( $new_post );

            if ( !$link_id )
                $response = array( 'status' => 'fail' , 'link_id' => $link_id , 'error_msg' => __( 'Failed to import product as an affiliate link' , 'thirstyaffiliates-pro' ) );
            else {

                $this->_save_link_meta( $link_id );

                if ( isset( $_POST[ 'link-data' ][ 'link-images' ] ) )
                    $this->_import_images( $link_id , $link_name , $_POST[ 'link-data' ][ 'link-images' ] );

                $this->_add_imported_link_to_categories( $link_id );

                if ( get_option( 'tap_azon_geolocation_integration' ) === 'yes' )
                    $this->import_geolocation_urls( $link_id );

                $response = array(
                    'status'      => 'success',
                    'link_id'     => $link_id,
                    'admin_url'   => get_admin_url() . "post.php?post=" . $link_id . "&action=edit",
                    'cloaked_url' => get_the_permalink( $link_id ),
                    'success_msg' => __( 'Successfully imported product as an affiliate link' , 'thirstyaffiliates-pro' ) );

            }

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * Ajax delete an affiliate link that is imported from amazon.
     *
     * @since 1.0.0
     * @access public
     */
    public function ajax_delete_amazon_imported_link() {

        if ( !defined( "DOING_AJAX" ) || !DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX Operation' , 'thirstyaffiliates-pro' ) );
        elseif ( !isset( $_POST[ 'link-id' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Required parameters not supplied' , 'thirstyaffiliates-pro' ) );
        elseif ( !check_ajax_referer( 'tap_delete_amazon_imported_link' , 'ajax-nonce' , false ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Security check failed' , 'thirstyaffiliates-pro' ) );
        else {

            wp_delete_post( (int) $_POST[ 'link-id' ] , true );

            $response = array( 'status' => 'success' , 'success_msg' => __( 'Successfully deleted imported link' , 'thirstyaffiliates-pro' ) );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }




    /*
    |--------------------------------------------------------------------------
    | Helper Functions
    |--------------------------------------------------------------------------
    */

    /**
     * Check if required amazon api keys are entered.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $amazon_access_key_id  Amazon access key id.
     * @param string $amazon_secret_key     Amazon secret key.
     * @param array  $amazon_associate_tags Amazon associate tags.
     * @return string|boolean Error message on failure, true otherwise.
     */
    public function check_if_amazon_keys_are_entered( $amazon_access_key_id , $amazon_secret_key , $amazon_associate_tags ) {

        $missing_keys = array();

        if ( empty( $amazon_access_key_id ) )
            $missing_keys[] = __( '<b>Amazon Access Key ID</b>' , 'thirstyaffiliates-pro' );

        if ( empty( $amazon_secret_key ) )
            $missing_keys[] = __( '<b>Amazon Secret Key</b>' , 'thirstyaffiliates-pro' );

        if ( empty( $amazon_associate_tags ) )
            $missing_keys[] = __( '<b>Amazon Associate Tags</b>' , 'thirstyaffiliates-pro' );

        if ( !empty( $missing_keys ) ) // Some or all required amazon keys are missing
            return sprintf( __( 'Please <a href="%1$s">input these required amazon credentials</a> in order to successfully query the Amazon Product Advertisement API:<br>%2$s' , 'thirstyaffiliates-pro' ) , admin_url() . 'edit.php?post_type=thirstylink&page=thirsty-settings&tab=tap_amazon_settings_section' , implode( ' , ' , $missing_keys ) );
        else
            return true;

    }

    /**
     * Check if entered associate tags are valid.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $amazon_associate_tags   Amazon associate tags.
     * @param array $amazon_search_indexes   Amazon search indexes.
     * @param array $amazon_search_endpoints Amazon search endpoints.
     * @return string|array Error message on failure, array of active endpoints otherwise.
     */
    public function check_if_entered_associate_tags_are_valid( $amazon_associate_tags , $amazon_search_indexes , $amazon_search_endpoints ) {

        $filtered_amazon_search_endpoints = array();

        if ( is_array( $amazon_associate_tags ) )
            foreach ( $amazon_associate_tags as $key => $val )
                if ( array_key_exists( $key , $amazon_search_endpoints ) )
                    $filtered_amazon_search_endpoints[ $key ] = $amazon_search_endpoints [ $key ];

        if ( empty( $filtered_amazon_search_endpoints ) )
            return sprintf( __( 'Please <a href="%1$s">enter valid <b>Amazon Associate Tags</b></a>. Product search is disabled until this is resolved.' , 'thirstyaffiliates-pro' ) , admin_url() . 'edit.php?post_type=thirstylink&page=thirsty-settings&tab=tap_amazon_settings_section' );
        else
            return $filtered_amazon_search_endpoints;

    }

    /**
     * Save affiliate link meta.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int $link_id Affiliate link id.
     */
    private function _save_link_meta( $link_id ) {

        global $wpdb;

        $link_meta = array(
            // POST submitted data
            '_ta_destination_url'  => esc_url_raw( wp_unslash( $_POST[ 'link-data' ][ 'link-url' ] ) ),
            '_ta_no_follow'        => sanitize_text_field( wp_unslash( $_POST[ 'link-data' ][ 'no-follow' ] ) ),
            '_ta_new_window'       => sanitize_text_field( wp_unslash( $_POST[ 'link-data' ][ 'new-window' ] ) ),
            '_ta_redirect_type'    => sanitize_text_field( wp_unslash( $_POST[ 'link-data' ][ 'redirect-type' ] ) ),
            '_tap_asin'            => sanitize_text_field( wp_unslash( $_POST[ 'link-data' ][ 'asin' ] ) ),
            '_tap_search_keywords' => sanitize_text_field( wp_unslash( $_POST[ 'link-data' ][ 'search-keywords' ] ) ),
            '_tap_search_index'    => sanitize_text_field( wp_unslash( $_POST[ 'link-data' ][ 'search-index' ] ) ),
            '_tap_search_endpoint' => sanitize_text_field( wp_unslash( $_POST[ 'link-data' ][ 'search-endpoint' ] ) ),
            // Settings default
            '_ta_rel_tags'         => get_option( 'ta_additional_rel_tags' )
        );

        $query = "INSERT INTO $wpdb->postmeta ( post_id, meta_key, meta_value ) VALUES ";
        $values = array();
        $placeholders = array();

        foreach ( $link_meta as $meta_key => $meta_value ) {

            $placeholders[] = "( %d, %s, %s )";
            array_push($values, $link_id, $meta_key, $meta_value);

        }

        $query .= $wpdb->prepare(join(', ', $placeholders), $values);

        $wpdb->query( $query );

    }

    /**
     * Check if image url is valid image.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $file Image url string.
     * @return boolean True if image url string is a valid image.
     */
    public function _is_valid_image( $file ) {

        $allowed = array( '.jpg' , 'jpeg', '.png', '.bmp' , '.gif' );

        $ext = substr( $file , -4 );

        if ( in_array( $ext , $allowed ) )
            return true;

        return false;

    }

    /**
     * Import amazon product images as an attachment for the affiliate link.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int    $link_id     Affiliate link id.
     * @param string $link_name   Affiliate link name.
     * @param array  $link_images Array of link images. Array key is size, value is url.
     */
    public function _import_images( $link_id , $link_name , $link_images ){

        error_reporting( E_ERROR | E_PARSE );

        $attachment_ids_with_key = array();
        $attachment_ids          = array();

        if ( !empty( $link_images ) ) {

            foreach( $link_images as $key => $img ){

                if ( !$this->_is_valid_image( $img) ) {

                    error_log( "Attempt to import invalid/unsupported image type. Affected image skipped from link image import" );
                    error_log( print_r( $img , true ) );

                    continue;

                }

                // Download file to temp location
                $tmp = download_url( $img );

                // Set variables for storage
                // fix file filename for query strings
                preg_match( '/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/' , $img , $matches );
                $file_array[ 'name' ]     = basename( $matches[ 0 ] );
                $file_array[ 'tmp_name' ] = $tmp;

                // If error storing temporarily, unlink
                if ( is_wp_error( $tmp ) ) {

                    @unlink( $file_array[ 'tmp_name' ] );
                    $file_array[ 'tmp_name' ] = '';

                    error_log( "Error Storing Image Temporarily, Mostly Caused by Write Permissions on WP Temp Folders Or the Specified Image Temporarily not Being Available, This may Happen due to Internet Connection Issues" );
                    error_log( print_r( $img , true ) );
                    error_log( print_r( $file_array , true ) );

                    continue;

                }

                // do the validation and storage stuff
                // Basically what we do here is attach the image to the post (link)
                $attachment_id = media_handle_sideload( $file_array , $link_id , $link_name . " image attachment (" . $key . ")" );

                // If error storing permanently, unlink
                if ( is_wp_error( $attachment_id ) ) {

                    @unlink( $file_array[ 'tmp_name' ] );
                    $file_array[ 'tmp_name' ] = '';

                    error_log( "Failed to Upload External Image to Wordpress" );
                    error_log( print_r( $img , true ) );
                    error_log( print_r( $file_array , true ) );

                    continue;

                }

                $attachment_ids_with_key[ $key ] = $attachment_id;
                $attachment_ids[]                = $attachment_id;

            }

            if ( !empty( $attachment_ids ) ) {

                ThirstyAffiliates()->models[ 'Affiliate_Link_Attachment' ]->add_attachments_to_affiliate_link( $attachment_ids , $link_id );
                update_post_meta( $link_id , '_tap_azon_image_attachment' , $attachment_ids_with_key ); // Only used for tracking which image from amazon is imported to this link

            }

        }

    }

    /**
     * Add categories to imported links.
     *
     * @access public
     * @since 1.0.0
     *
     * @param int $link_id Affiliate link id.
     */
    public function _add_imported_link_to_categories( $link_id ) {

        $link_categories = get_option( 'tap_azon_imported_link_categories' );
        if ( !is_array( $link_categories ) )
            $link_categories = array();

        if ( !empty( $link_categories ) )
            wp_set_post_terms( $link_id , $link_categories , 'thirstylink-category' , false );

    }

    /**
     * Import geolocation urls for the currently imported product.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int         $link_id         Affiliate link id.
     * @param string|null $search_endpoint Country code of the search endpoint
     */
    public function import_geolocation_urls( $link_id , $search_endpoint = null ) {

        $asin            = sanitize_text_field( $_POST[ 'link-data' ][ 'asin' ] );
        $search_endpoint = $search_endpoint ? $search_endpoint : $_POST[ 'link-data' ][ 'search-endpoint' ];
        $amazon_keys     = array(
            'amazon_access_key_id'  => get_option( 'tap_amazon_access_key_id' ),
            'amazon_secret_key'     => get_option( 'tap_amazon_secret_key' ),
            'amazon_associate_tags' => get_option( 'tap_amazon_associate_tags' )
        );

        $geo_links = $this->_get_product_geolocation_links( $asin , $search_endpoint , $amazon_keys );

        update_post_meta( $link_id , '_ta_geolocation_links' , $geo_links );

    }

    /**
     * Get geolocation links for the currently imported product.
     *
     * @since 1.0.0
     * @since 1.4.0 Change UK country code to GB.
     * @access public
     *
     * @param string $asin            Product ASIN.
     * @param string $search_endpoint Country code of the search endpoint.
     * @param array  $amazon_keys     Array that contains amazon api credentials.
     * @return array Other links for the main link.
     */
    private function _get_product_geolocation_links( $asin, $search_endpoint, $amazon_keys ) {

        $other_links = array();

        foreach ( $this->_constants->AMAZON_SEARCH_ENDPOINTS() as $c_code => $c_endpoint ) {

            if ( $search_endpoint == $c_code ) {
                continue; // Don't search the main imported product link api endpoint
            }

            if ( !isset( $amazon_keys[ 'amazon_associate_tags' ][ $c_code ] ) ) {
                continue; // Don't include non supported api endpoints
            }

            $params = array(
                'Condition' => 'Any',
                'ItemIds' => array($asin),
                'ItemIdType' => 'ASIN',
                'PartnerTag' => $amazon_keys[ 'amazon_associate_tags' ][ $c_code ],
                'PartnerType' => 'Associates'
            );

            try {
                $amazon_response = $this->_query_amazon_api(
                    '/paapi5/getitems',
                    'GetItems',
                    $params,
                    $c_code,
                    $amazon_keys
                );
            } catch (\Exception $e) {
                continue;
            }

            if ( isset( $amazon_response['Errors'] ) ) {
                continue;
            }

            if ( isset( $amazon_response['ItemsResult']['Items'][0]['DetailPageURL'] ) ) {
                $other_links[$c_code] = $amazon_response['ItemsResult']['Items'][0]['DetailPageURL'];
            }

        }

        return $other_links;

    }

    /**
     * Get the data of affiliate links imported via azon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return array Data of affiliate links imported via azon.
     */
    public function get_azon_link_data() {

        global $wpdb;

        return $wpdb->get_results( "SELECT p.ID AS link_id , pm.meta_value AS asin , p.guid AS cloaked_url , CONCAT( '" . get_admin_url() . "post.php?post=' , p.ID , '&action=edit' ) AS admin_url
                                    FROM $wpdb->posts p INNER JOIN $wpdb->postmeta pm
                                    ON p.ID = pm.post_id
                                    WHERE pm.meta_key = '_tap_asin'" , ARRAY_A );


    }

    /**
     * Sanitize the given item page number to ensure that it is between 1 and 10
     *
     * @param  string $item_page
     * @return int
     */
    private function _sanitize_item_page( $item_page ) {
        $item_page = (int) $item_page;

        if ( $item_page < 1 ) {
            $item_page = 1;
        } elseif ( $item_page > 10 ) {
            $item_page = 10;
        }

        return $item_page;
    }


    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Execute plugin script loader.
     *
     * @since 1.0.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        add_action( 'wp_ajax_tap_amazon_product_advertisement_api_search' , array( $this , 'ajax_amazon_product_advertisement_api_search' ) );
        add_action( 'wp_ajax_tap_set_amazon_table_visible_columns'        , array( $this , 'ajax_set_amazon_table_visible_columns' ) );
        add_action( 'wp_ajax_tap_set_last_used_search_endpoint'           , array( $this , 'ajax_set_last_used_search_endpoint' ) );

        add_action( 'wp_ajax_tap_import_link' , array( $this , 'ajax_import_link' ) );
        add_action( 'wp_ajax_tap_delete_amazon_imported_link' , array( $this , 'ajax_delete_amazon_imported_link' ) );

    }

    /**
     * Execute plugin script loader.
     *
     * @since 1.0.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Model_Interface
     */
    public function run () {

        if ( get_option( 'tap_enable_azon' , 'yes' ) === 'yes' )
            add_action( 'admin_menu' , array( $this , 'add_amazon_page' ) );

        add_filter( 'screen_settings' , array( $this , 'amazon_page_screen_option' ) , 10 , 2 );

        // Register admin interface and menus.
        add_filter( 'ta_admin_interfaces' , array( $this , 'register_admin_interfaces' ) , 20 );
        add_filter( 'ta_menu_items' , array( $this , 'register_admin_menu_items' ) , 20 );
    }

}
