<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Affiliate_Link_Extend module.
 *
 * @since 1.0.0
 */
class Affiliate_Link_Extend implements Model_Interface {

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
     * Stores extended data to be registered to Affiliate_Link factory class.
     *
     * @since 1.0.0
     * @since 1.1.0 Added URL shortener and link health checker properties.
     * @access private
     * @var array
     */
    private $_extend_data = array(

        // autolinker fields
        'autolink_keyword_list'     => '',
        'autolink_keyword_limit'    => 0,
        'autolink_inside_heading'   => 'global',
        'autolink_random_placement' => 'global',

        // geolocation fields
        'geolocation_links' => array(),

        // link health checker
        'link_health_status'       => 'waiting',
        'link_health_last_checked' => '1970-01-01 00:00:00',

        // URL shortener
        'shortened_url' => '',

        // Link Scheduler
        'link_start_date'  => '',
        'link_expire_date' => '',
        'before_start_redirect' => '',
        'after_expire_redirect' => ''

    );




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
     * Register the extended post meta data into the Affiliate_Link factory cass.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $extend_data  Array list of Affiliate_Link extended data.
     * @param array $default_data Array list of Affiliate_Link default data.
     * @return array Array list of Affiliate_Link extended data.
     */
    public function register_extend_data( $extend_data , $default_data ) {

        return array_merge( $extend_data , $this->_extend_data );
    }

    /**
     * Read the extended post meta data into the Affiliate_Link factory cass.
     *
     * @since 1.0.0
     * @since 1.4.0 Add workaround for geolocation value when key is set to UK (see TAP-205).
     * @access public
     *
     * @param mixed  $raw_value Affiliate_Link post meta/data value.
     * @param string $prop      Affiliate_Link post meta/data property identifier.
     * @return array Array      list of Affiliate_Link default data.
     */
    public function read_extend_data( $raw_value , $prop , $default_data ) {

        switch ( $prop ) {

            // properties with global value
            case 'autolink_inside_heading' :
            case 'autolink_random_placement' :
                $value = ! empty( $raw_value ) ? $raw_value : $this->get_prop_global_value( $prop );
                break;

            case 'geolocation_links' :
                $value = is_array( $raw_value ) && ! empty( $raw_value ) ? $raw_value : $this->get_prop_global_value( $prop );

                // TAP-205
                if ( isset( $value[ 'UK' ] ) && ! isset( $value[ 'GB' ] ) ) {
                    $value[ 'GB' ] = $value[ 'UK' ];
                    unset( $value[ 'UK' ] );
                }

                break;

            default:
                $value = ! empty( $raw_value ) ? $raw_value : $default_data[ $prop ];
                break;
        }

        return $value;
    }

    /**
     * Get global value of Property
     *
     * @since 1.0.0
     * @access public
     *
     * @param mixed $prop Affiliate link property id.
     * @return mixed Affiliate link property equivalent global value.
     */
    public function get_prop_global_value( $prop ) {

        $default = isset( $this->_extend_data[ $prop ] ) ? $this->_extend_data[ $prop ] : '';

        switch ( $prop ) {

            case 'autolink_inside_heading' :
            case 'autolink_random_placement' :
            case 'geolocation_links' :
                $global_option = 'ta_' . $prop;
                break;

            default :
                return;
                break;
        }

        return get_option( $global_option , $default );
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
    public function fix_all_destination_urls_in_db( $link_ids ) {

        global $wpdb;

        $link_ids_str = implode( ',' , array_map( 'intval' , $link_ids ) );

        $query = "UPDATE $wpdb->postmeta
                    SET meta_value = REPLACE( REPLACE( REPLACE( meta_value , '&amp;amp;' , '&' ) , '&amp;' , '&' ) , '&#038;', '&' )
                    WHERE meta_key = '_ta_destination_url'
                    AND post_id IN ( $link_ids_str )";

        $wpdb->query( $query );
    }




    /*
    |--------------------------------------------------------------------------
    | REST API Integration
    |--------------------------------------------------------------------------
    */

    /**
     * Register TA pro extended fields to the REST API.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array $ta_fields TA fields to register to REST API.
     * @return array Filtered TA field to register to REST API.
     */
    public function register_rest_api_fields( $ta_fields ) {

        return array_merge( $ta_fields , $this->_extend_data );
    }

    /**
     * Register TA pro toggle fields to the REST API.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array $toggle_fields List of TA toggle fields.
     * @return array Filtered list of TA toggle fields.
     */
    public function register_rest_api_toggle_fields( $toggle_fields ) {

        $pro_toggle_fields = array(
            'autolink_inside_heading',
            'autolink_random_placement'
        );

        return array_merge( $toggle_fields , $pro_toggle_fields );
    }

    /**
     * Sanitize single TA Pro REST API field.
     *
     * @since 1.1.0
     * @access public
     *
     * @param mixed  $sanitized_value Value after sanitized. Defaults to boolean false.
     * @param mixed  $field_value     Raw field value.
     * @param string $meta_key        TA field meta key.
     * @param string $field_type      TA field variable type.
     * @return mixed Filter sanitized field value.
     */
    public function escape_rest_api_field( $escaped_value , $meta_value , $meta_key , $field_type ) {

        switch ( $meta_key ) {

            case 'geolocation_links' :

                if ( is_array( $meta_value ) && ! empty( $meta_value ) ) {

                    $escaped_value = array();
                    foreach ( $meta_value as $countries => $geolink ) {

                        $countries                   = esc_attr( $countries );
                        $escaped_value[ $countries ] = esc_url_raw( $geolink );
                    }
                }

                break;

            default:
                break;
        }

        return $escaped_value;
    }

    /**
     * Sanitize single TA Pro REST API field.
     *
     * @since 1.1.0
     * @access public
     *
     * @param mixed  $sanitized_value Value after sanitized. Defaults to boolean false.
     * @param mixed  $field_value     Raw field value.
     * @param string $meta_key        TA field meta key.
     * @param string $field_type      TA field variable type.
     * @param mixed  $default_value    TA field default value.
     * @return mixed Filter sanitized field value.
     */
    public function sanitize_rest_api_field( $sanitized_value , $field_value , $meta_key , $field_type , $default_value ) {

        switch ( $meta_key ) {

            case 'geolocation_links' :

                if ( is_array( $field_value ) && ! empty( $field_value ) ) {

                    $sanitized_value = array();
                    foreach ( $field_value as $countries => $geodata ) {

                        $geodata = json_decode( $geodata , true );

                        // if data doesn't have either countries or the geolink, then skip.
                        if ( ! isset( $geodata[ 'countries' ] ) || ! isset( $geodata[ 'geolink' ] ) )
                            continue;

                        // if geolink is not a valid url, then skip.
                        if ( ! filter_var( $geodata[ 'geolink' ] , FILTER_VALIDATE_URL ) )
                            continue;

                        $countries                     = sanitize_text_field( $geodata[ 'countries' ] );
                        $sanitized_value[ $countries ] = esc_url_raw( $geodata[ 'geolink' ] );
                    }

                } else
                    $sanitized_value = $default_value;

                break;

            case 'autolink_keyword_list' :
                $sanitized_value = sanitize_text_field( rtrim( $field_value , ',' ) );
                break;

            default:
                break;
        }

        return $sanitized_value;
    }

    /**
     * Set filter to not save some TA Pro fields.
     *
     * @since 1.1.0
     * @access public
     *
     * @param boolean $dont_save Toggle to determine if field needs to be saved or not.
     * @param string  $meta_key  TA field meta key.
     * @return boolean Filtered toggle to determine if field needs to be saved or not.
     */
    public function dont_save_meta_fields( $dont_save , $meta_key ) {

        $dont_save_fields = array(
            'link_health_status',
            'link_health_last_checked',
            'shortened_url'
        );

        if ( in_array( $meta_key , $dont_save_fields ) )
            $dont_save = true;

        return $dont_save;
    }

    /**
     * Register event notification as a REST API field.
     *
     * @since 1.1.1
     * @access public
     */
    public function register_event_notification_rest_api_field() {

        $field_meta = '_ta_event_notifications';

        register_rest_field( Plugin_Constants::AFFILIATE_LINKS_CPT , $field_meta , array(

            // REST field get callback.
            'get_callback' => function( $post_data ) {
                return wp_get_post_terms( $post_data[ 'id' ] , Plugin_Constants::EVENT_NOTIFICATION_TAX , array( 'fields' => 'ids' ) );
            },

            // REST field update callback.
            'update_callback' => function( $new_value , $post_obj ) {

                if ( ! is_array( $new_value ) || empty( $new_value ) )
                    return;

                $notifications = array_unique( array_map( 'intval' , $new_value ) );
                wp_set_post_terms( $post_obj->ID , $notifications , Plugin_Constants::EVENT_NOTIFICATION_TAX );
            },

            // REST field schema.
            'schema' => array(
                'type'        => 'array',
                'context'     => array( 'view' , 'edit' )
            )

        ) );
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
     * @since 1.0.0
     * @access public
     */
    public function run() {

        add_filter( 'ta_affiliate_link_extended_data' , array( $this , 'register_extend_data' ) , 1 , 2 );
        add_filter( 'ta_read_thirstylink_property' , array( $this , 'read_extend_data' ) , 1 , 3 );
        add_filter( 'ta_register_rest_api_fields' , array( $this , 'register_rest_api_fields' ) , 10 , 1 );
        add_filter( 'ta_rest_api_esc_meta_value' , array( $this , 'escape_rest_api_field' ) , 10 , 4 );
        add_filter( 'ta_rest_api_sanitize_field' , array( $this , 'sanitize_rest_api_field' ) , 10 , 5 );
        add_filter( 'ta_restapi_field_update_cb' , array( $this , 'dont_save_meta_fields' ) , 10 , 2 );
        add_filter( 'ta_rest_api_sanitize_toggle_fields' , array( $this , 'register_rest_api_toggle_fields' ) , 10 , 1 );
        add_action( 'rest_api_init' , array( $this , 'register_event_notification_rest_api_field' ) , 10 );
        add_action( 'tap_fix_all_links' , array( $this , 'fix_all_destination_urls_in_db' ) , 10 , 1 );
    }

}
