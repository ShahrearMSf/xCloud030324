<?php
namespace ThirstyAffiliates_Pro\Models\Third_Party_Integrations\Amazon;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Amazon_Settings implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Amazon_Settings.
     *
     * @since 1.0.0
     * @access private
     * @var Amazon_Settings
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
     * @return Amazon_Settings
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }

    /**
     * AJAX clear azon transients.
     *
     * @since 1.1.0
     * @access public
     */
    public function ajax_clear_azon_transients() {

        if ( !defined( "DOING_AJAX" ) || !DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX Operation' , 'thirstyaffiliates-pro' ) );
        elseif ( !check_ajax_referer( 'tap_clear_azon_transients' , 'ajax-nonce' , false ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Security check failed' , 'thirstyaffiliates-pro' ) );
        else {

            $generated_transients = get_option( Plugin_Constants::AMAZON_GENERATED_TRANSIENTS );
            if ( !is_array( $generated_transients ) )
                $generated_transients = array();

            foreach( $generated_transients as $transient_key )
                delete_transient( $transient_key );

            delete_option( Plugin_Constants::AMAZON_GENERATED_TRANSIENTS );

            $response = array( 'status' => 'success' , 'success_msg' => __( 'Successfully cleared search result cache' , 'thirstyaffiliates-pro' ) );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * Register 'clear_azon_transients' field type.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array $field_types Settings supported field types.
     * @return array Filtered settings supported field types.
     */
    public function register_clear_azon_transients_field_type( $field_types ) {

        if ( !array_key_exists( 'clear_azon_transients' , $field_types ) ) {

            $field_types[ 'clear_azon_transients' ] = function( $option ) {

                ?>

                <tr>
                    <th scope="row"><?php echo $option[ 'title' ]; ?></th>
                    <td>
                        <button class="button button-primary <?php echo $option[ 'id' ]; ?>" type="button" id="<?php echo $option[ 'id' ]; ?>"><?php _e( 'Clear cache' , 'thirstyaffiliates-pro' ); ?></button>
                        <span class="spinner" style="display: inline-block; float: none; margin-left: 5px;"></span>
                        <p class="desc"><?php echo $option[ 'desc' ]; ?></p>
                    </td>
                </tr>

                <script>
                    jQuery( document ).ready( function( $ ) {

                        var nonce = '<?php echo wp_create_nonce( 'tap_clear_azon_transients' ); ?>';

                        $( "#<?php echo $option[ 'id' ]; ?>" ).click( function() {

                            var $this    = $( this );

                            $this
                                .attr( 'disabled' , 'disabled' )
                                .siblings( '.spinner' )
                                    .css( 'visibility' , 'visible' );

                            $.ajax( {
                                'url'      : ajaxurl,
                                'type'     : 'POST',
                                'data'     : { 'action' : 'tap_clear_azon_transients' , 'ajax-nonce' : nonce },
                                'dataType' : 'json'
                            } )
                            .done( function( data ) {

                                if ( data.status === 'success' )
                                    alert( data.success_msg );
                                else {

                                    console.log( data );
                                    alert( data.error_msg );

                                }

                            } )
                            .fail( function( jqxhr ) {

                                console.log( jqxhr );
                                alert( '<?php _e( 'Failed to clear search result cache.' , 'thirstyaffiliates-pro' ); ?>' );

                            } )
                            .always( function() {

                                $this
                                .removeAttr( 'disabled' )
                                .siblings( '.spinner' )
                                    .css( 'visibility' , 'hidden' );

                            } );

                        } );

                    } );
                </script>

                <?php

            };

        }

        return $field_types;

    }

    /**
     * Register 'clear_azon_transients' to the field types that dont need to be registered on the settings api.
     *
     * @since 1.1.0
     * @access public
     *
     * @param array $field_types Array of field types.
     * @return array Filtered array of field types.
     */
    public function register_clear_azon_transients_to_skippable_fields( $field_types ) {

        if ( !in_array( 'clear_azon_transients' , $field_types ) )
            $field_types[] = 'clear_azon_transients';

        return $field_types;

    }

    /**
     * Register amazon settings section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_sections Array of settings sections.
     * @return array Filtered array of settings sections.
     */
    public function register_amazon_settings_section( $settings_sections ) {

        if ( array_key_exists( 'tap_amazon_settings_section' , $settings_sections ) )
            return $settings_sections;

        $offset = ( int ) array_search( 'ta_modules_settings' , array_keys( $settings_sections ) ) + 1;

        $amazon_settings = array();

        $amazon_settings[ 'tap_amazon_settings_section' ] = array(
            'title' => __( 'Amazon Import' , 'thirstyaffiliates-pro' ),
            'desc'  => __( 'Settings for Amazon Product Advertisement API' , 'thirstyaffiliates-pro' )
        );

        return ( ( count ( $amazon_settings ) > 0 ) ? array_merge ( array_slice( $settings_sections , 0 , $offset , true) , $amazon_settings , array_slice( $settings_sections , $offset , null , true) ) : $settings_sections );

    }

    /**
     * Register amazon settings section options.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_section_options Array of options per settings sections.
     * @return array Filtered array of options per settings sections.
     */
    public function register_amazon_settings_section_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_amazon_settings_section' , $settings_section_options ) )
            return $settings_section_options;

        $settings_section_options[ 'tap_amazon_settings_section' ] = apply_filters(
            'tap_amazon_settings_section_options' ,
            array(
                array(
                    'id'    => 'tap_amazon_access_key_id',
                    'title' => __( 'Amazon Access Key ID' , 'thirstyaffiliates-pro' ),
                    'desc'  => sprintf( __( '<a href="%1$s" target="_blank">How To Get My Access Key ID?</a>' , 'thirstyaffiliates-pro' ) , 'https://thirstyaffiliates.com/knowledgebase/retrieve-amazon-product-advertising-api-access-key-secret-key' ),
                    'type'  => 'text',
                ),
                array(
                    'id'    => 'tap_amazon_secret_key',
                    'title' => __( 'Amazon Secret Key' , 'thirstyaffiliates-pro' ),
                    'desc'  => sprintf( __( '<a href="%1$s" target="_blank">How To Get My Secret Key?</a>' , 'thirstyaffiliates-pro' ) , 'https://thirstyaffiliates.com/knowledgebase/retrieve-amazon-product-advertising-api-access-key-secret-key' ),
                    'type'  => 'text',
                ),
                array(
                    'id'     => 'tap_amazon_associate_tag_divider',
                    'type'   => 'option_divider',
                    'title'  => __( 'Amazon Associate Tag' , 'thirstyaffiliates-pro' ),
                    'markup' => sprintf( __( '<p style="font-weight: normal;"><a href="%1$s" target="_blank">How To Get My Amazon_Settings Associate Tag?</a><br><br>
                                                Enter your Amazon associate tag per country you are registered. Note that you will only be able to search on the countries you are registered.<br>
                                                Enter the two letter <b>Country Code</b> as the <b>Key</b> and your <b>Amazon Associate Tag</b> for that country as the <b>Value</b>. Currently supported countries are:<br><br>
                                                <em>United States <b>(US)</b>, Canada <b>(CA)</b>, United Kingdom <b>(UK)</b>, Australia <b>(AU)</b>, Brazil <b>(BR)</b>, France <b>(FR)</b>, Germany <b>(DE)</b>, India <b>(IN)</b>, Italy <b>(IT)</b>, Japan <b>(JP)</b>, Mexico <b>(MX)</b>, Netherlands <b>(NL)</b>, Poland <b>(PL)</b>, Saudi Arabia <b>(SA)</b>, Singapore <b>(SG)</b>, Spain <b>(ES)</b>, Sweden <b>(SE)</b>, Turkey <b>(TR)</b> and United Arab Emirates <b>(AE)</b></p>' , 'thirstyaffiliates-pro' ) , 'https://thirstyaffiliates.com/knowledgebase/retrieve-amazon-associates-tracking-id' )
                ),
                array(
                    'id'      => 'tap_amazon_associate_tags',
                    'type'    => 'key_value',
                    'title'   => __( 'Amazon Associate Tag' , 'thirstyaffiliates-pro' ),
                    'default' => array()
                ),
                array(
                    'id'    => 'tap_hide_products_with_empty_price',
                    'type'  => 'toggle',
                    'title' => __( 'Hide Empty Priced Products' , 'thirstyaffiliates-pro' ),
                    'desc'  => __( 'Empty priced products are products with price of zero or products which have no price set.' , 'thirstyaffiliates-pro'  )
                ),
                array(
                    'id'      => 'tap_azon_import_images',
                    'type'    => 'checkbox',
                    'title'   => __( 'Import Images' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Quick importing will automatically import the checked images. Normal import will have these images checked by default.' , 'thirstyaffiliates-pro' ),
                    'options' => array(
                        'small'  => __( 'Small' , 'thirstyaffiliates-pro' ),
                        'medium' => __( 'Medium' , 'thirstyaffiliates-pro' ),
                        'large'  => __( 'Large' , 'thirstyaffiliates-pro' )
                    )
                ),
                array(
                    'id'      => 'tap_azon_imported_link_categories',
                    'type'    => 'multiselect',
                    'title'   => __( 'Imported Link Categories' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Add imported links to these categories.' , 'thirstyaffiliates-pro' ),
                    'options' => ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_all_category_as_options()
                ),
                array(
                    'id'    => 'tap_azon_geolocation_integration',
                    'type'  => 'toggle',
                    'title' => __( 'Enable Geolocation Integration' , 'thirstyaffiliates-pro' ),
                    'desc'  => __( 'If associates tags are provided for alternate Amazon store locales, this option will import links the product on those other stores as Geolocation URLs per supported country.' , 'thirstyaffiliates-pro' )
                ),
                array(
                    'id'     => 'tap_search_result_caching_divider',
                    'type'   => 'option_divider',
                    'title'  => __( 'Search Result Caching' , 'thirstyaffiliates-pro' ),
                    'markup' => __( '<p style="font-weight: normal;">Cache searches performed by the Amazon importer to improve performance for repeated searches.</p>' , 'thirstyaffiliates-pro' )
                ),
                array(
                    'id'    => 'tap_enable_azon_transient',
                    'type'  => 'toggle',
                    'title' => __( 'Enable Search Result Caching' , 'thirstyaffiliates-pro' ),
                    'desc'  => __( 'If enabled, amazon module will save the search results in a cache, so if you search the same thing later, the cached data will be used instead for faster query.' , 'thirstyaffiliates-pro' )
                ),
                array(
                    'id'            => 'tap_azon_transient_lifespan',
                    'type'          => 'number',
                    'title'         => __( 'Cache Lifespan' , 'thirstyaffiliates-pro' ),
                    'desc'          => __( 'In days, defaults to 7 days.' , 'thirstyaffiliates-pro' ),
                    'sanitation_cb' => function( $new_option_value ) {

                        if ( empty( $new_option_value ) || !is_numeric( $new_option_value ) || (int) $new_option_value <= 0 ) {

                            add_settings_error(
                                'tap_azon_transient_lifespan' ,                                                                    // The option id this error applies to
                                'tap_azon_transient_lifespan_invalid_value' ,                                                      // Error code that uniquely identifies the error
                                __( 'Cache lifespan option must be a number greater than or equal 1' , 'thirstyaffiliates-pro' ) , // Error message
                                'error'                                                                                            // Message type ( error or updated )
                            );

                            return get_option( 'tap_azon_transient_lifespan' );

                        }

                        return ( int ) $new_option_value;

                    },
                    'default' => 7
                ),
                array(
                    'id'    => 'tap_clear_azon_transients',
                    'type'  => 'clear_azon_transients',
                    'title' => __( 'Clear Cache' , 'thirstyaffiliates-pro' ),
                    'desc'  => __( 'Clear amazon api search result cache' , 'thirstyaffiliates-pro' )
                )
            )
        );

        return $settings_section_options;

    }

    /**
     * Register amazon module on ta modules.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $ta_modules_options Array of ta modules options.
     * @return array Filtered array of ta modules options.
     */
    public function register_amazon_module( $ta_modules_options ) {

        $ta_modules_options[] = array(
                                    'id'    => 'tap_enable_azon',
                                    'title' => __( 'Amazon Import' , 'thirstyaffiliates-pro' ),
                                    'desc'  => __( "The Amazon Importer module lets you create affiliate links to Amazon products by searching and importing them into ThirstyAffiliates." , 'thirstyaffiliates-pro' ),
                                    'type'  => 'toggle',
                                    'default' => 'yes'
                                );

        return $ta_modules_options;

    }




    /*
    |--------------------------------------------------------------------------
    | Implemented Interface Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Execute codebase that needs to run on init.
     *
     * @since 1.1.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        add_action( 'wp_ajax_tap_clear_azon_transients' , array( $this , 'ajax_clear_azon_transients' ) );

    }

    /**
     * Execute model.
     *
     * @since 1.0.0
     * @access public
     * @implements ThirstyAffiliates\Interfaces\Model_Interface
     */
    public function run() {

        if ( get_option( 'tap_enable_azon' , 'yes' ) === 'yes' ) {

            add_filter( 'ta_supported_field_types'         , array( $this , 'register_clear_azon_transients_field_type' ) );
            add_filter( 'ta_skip_wp_settings_registration' , array( $this , 'register_clear_azon_transients_to_skippable_fields' ) );
            add_filter( 'ta_settings_option_sections'      , array( $this , 'register_amazon_settings_section' ) );
            add_filter( 'ta_settings_section_options'      , array( $this , 'register_amazon_settings_section_options' ) );

        }

        add_filter( 'ta_modules_settings_options' , array( $this , 'register_amazon_module' ) );

    }

}
