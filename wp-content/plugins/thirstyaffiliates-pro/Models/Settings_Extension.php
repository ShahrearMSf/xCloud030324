<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The purpose of this model is to show an example on how to Extend the WPB Settings API.
 * Of course the realistic use case external plugins extending this plugin's Settings API.
 * We just add this file here for illustration purposes.
 * Please delete this file when creating a plugin from this boilerplate.
 *
 * Private Model.
 *
 * @since 1.0.0
 */
class Settings_Extension implements Model_Interface , Initiable_Interface {

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
     * Register settings admin interfaces.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of admin interfaces.
     * @return array Filtered list of admin interfaces.
     */
    public function register_settings_admin_interfaces( $interfaces ) {

        $interfaces[ 'tap_amazon_settings_section' ]        = 'manage_options';
        $interfaces[ 'tap_autolinker_settings' ]            = 'manage_options';
        $interfaces[ 'tap_geolocations_settings' ]          = 'manage_options';
        $interfaces[ 'tap_google_click_tracking_settings' ] = 'manage_options';
        $interfaces[ 'tap_link_health_checker_settings' ]   = 'manage_options';
        $interfaces[ 'tap_url_shortener_settings' ]         = 'manage_options';
        $interfaces[ 'tap_link_scheduler_settings' ]        = 'manage_options';
        $interfaces[ 'tap_plugin_visibility_settings' ]     = 'manage_options';
        $interfaces[ 'ta_import_export_settings' ]          = 'manage_options';
        $interfaces[ 'tap_mothership_settings_section' ]    = 'manage_options';

        return $interfaces;
    }

    /**
     * Register the rebuild cache button for the Autolinker cache.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $supported_field_types Array of all supported field types for the WPB Settings API.
     * @return array Filtered array of supported field types.
     */
    public function register_autolinker_rebuild_cache_field( $supported_field_types ) {

        if ( array_key_exists( 'autolink_rebuild_cache' , $supported_field_types ) )
            return $supported_field_types;

        $supported_field_types[ 'autolink_rebuild_cache' ] = function( $option ) {

            // This function will be the render callback of this new custom field type
            // It will expect 1 parameter to be passed by our Settings API, and that is the $option data

            ?>

            <tr>
                <th scope="row"><?php echo $option[ 'title' ]; ?></th>
                <td>
                    <button class="button button-primary <?php echo $option[ 'id' ]; ?>" type="button" id="<?php echo $option[ 'id' ]; ?>"><?php echo $option[ 'default' ]; ?></button>
                    <span class="cache-rebuild-spinner" style="display:none;margin-top:4px;">
                        <img src="<?php echo $this->_constants->IMAGES_ROOT_URL() . 'spinner.gif'; ?>">
                    </span>
                    <span class="clear-cache-done" style="display:none;margin-top:5px;margin-left:5px;color:#27ae60;">
                        <?php _e( 'Done! Cache Rebuilt' , 'thirstyaffiliates-pro' ); ?>
                    </span>
                    <br>
                    <p class="desc"><?php echo $option[ 'desc' ]; ?></p>
                </td>

                <script>
                    jQuery( document ).ready( function( $ ) {

                        $( "button.<?php echo esc_attr( $option[ 'id' ] ); ?>" ).on( "click" , function() {

                            var $this   = $(this),
                                $parent = $this.closest( 'td' );

                            // show spinner
                            $parent.find( '.cache-rebuild-spinner' ).css( 'display' , 'inline-block' );

                            $.post( window.ajaxurl, {
                                action  : 'tap_autolink_rebuild_cache'
                            }, function( response ) {

                                if ( response.status == 'success' ) {

                                    $parent.find( '.cache-rebuild-spinner' ).hide();
                                    $parent.find( '.clear-cache-done' ).css( 'display' , 'inline-block' );

                                } else {

                                    // TODO: replace with Vex dialog
                                    alert( response.error_msg );
                                    console.log( response.error_msg );
                                }

                            } , 'json' );

                        } );

                    } );
                </script>
            </tr>

            <?php

        };

        return $supported_field_types;

    }

    /**
     * Register MaxMind Upload DB Field
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $supported_field_types Array of all supported field types for the WPB Settings API.
     * @return array Filtered array of supported field types.
     */
    public function register_maxmind_db_upload_field( $supported_field_types ) {

        if ( array_key_exists( 'maxmind_db_upload' , $supported_field_types ) )
            return $supported_field_types;

        $supported_field_types[ 'maxmind_db_upload' ] = function( $option ) {

            // This function will be the render callback of this new custom field type
            // It will expect 1 parameter to be passed by our Settings API, and that is the $option data

            $option_value = get_option( $option[ 'id' ] );

            ?>

            <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

                <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

                <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">

                    <div class="text-field" <?php echo empty( $option_value ) ? 'style="display:none;"' : ''; ?>>
                        <input
                            type  = "text"
                            name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                            id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                            class = "option-field <?php echo isset( $option[ 'class' ] ) ? esc_attr( $option[ 'class' ] ) : ''; ?>"
                            style = "<?php echo isset( $option[ 'style' ] ) ? $option[ 'style' ] : 'width: 360px;'; ?>"
                            value = "<?php echo $option_value; ?>"
                            <?php echo empty( $option_value ) ? 'disabled' : ''; ?> >
                        <a class="change" href="#"><?php _e( 'Change' , 'thirstyaffiliates-pro' ); ?></a>
                    </div>

                    <div class="upload-field" <?php echo ! empty( $option_value ) ? 'style="display:none;"' : ''; ?>>
                        <input
                            type  = "file"
                            name  = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                            id    = "<?php echo esc_attr( $option[ 'id' ] ); ?>"
                            class = "maxmind_db_upload"
                            <?php echo ! empty( $option_value ) ? 'disabled' : ''; ?> >
                        <?php if ( ! empty( $option_value ) ) : ?>
                            <a class="cancel-change" href="#"><?php _e( 'Cancel change' , 'thirstyaffiliates-pro' ); ?></a>
                        <?php endif; ?>
                    </div>

                    <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
                </td>

                <script>
                jQuery( document ).ready( function( $ ) {

                    // change mmdb upload file event.
                    $( '.<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>' ).on( 'click' , '.text-field .change' , function( e ) {

                        e.preventDefault();

                        var $row        = $(this).closest( 'tr' );
                            $text_div   = $row.find( '.text-field' );
                            $upload_div = $row.find( '.upload-field' );

                        $text_div.find( 'input' ).prop( 'disabled' , true );
                        $upload_div.find( 'input' ).prop( 'disabled' , false );

                        $text_div.fadeOut( 'fast' );
                        $upload_div.fadeIn( 'fast' );

                    } );

                    // cancel change event
                    $( '.<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>' ).on( 'click' , '.upload-field .cancel-change' , function( e ) {

                        e.preventDefault();

                        var $row        = $(this).closest( 'tr' );
                            $text_div   = $row.find( '.text-field' );
                            $upload_div = $row.find( '.upload-field' );

                        $upload_div.find( 'input' ).prop( 'disabled' , true );
                        $text_div.find( 'input' ).prop( 'disabled' , false );

                        $upload_div.fadeOut( 'fast' );
                        $text_div.fadeIn( 'fast' );

                    } );

                    // validate selected file. make sure its .mmdb
                    $( '.<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>' ).on( 'change' , 'input.maxmind_db_upload' , function() {

                        var file_extension = $(this).val().split( '.' ).pop();

                        if ( file_extension === 'mmdb' )
                            return;

                        alert( '<?php _e( 'Please select a valid .mmdb file' , 'thirstyaffiliates-pro' ); ?>' );
                        $(this).val( '' );

                    } );

                    // validate file
                });
                </script>

            </tr>

            <?php

        };

        return $supported_field_types;
    }

    /**
     * Register Htaccess recor all links field
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $supported_field_types Array of all supported field types for the WPB Settings API.
     * @return array Filtered array of supported field types.
     */
    public function register_htaccess_record_all_links_field( $supported_field_types ) {

        if ( array_key_exists( 'htaccess_record_all_links' , $supported_field_types ) )
            return $supported_field_types;

        $supported_field_types[ 'htaccess_record_all_links' ] = function( $option ) {

            // This function will be the render callback of this new custom field type
            // It will expect 1 parameter to be passed by our Settings API, and that is the $option data

            $option_value = get_option( $option[ 'id' ] );

            ?>

            <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

                <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

                <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">

                    <div class="record-all-links-field">

                        <button id= "<?php echo esc_attr( $option[ 'id' ] ); ?>" class="button button-primary">
                            <?php _e( 'Record/recreate all links' , 'thirstyaffiliates-pro' ); ?>
                        </button>

                        <p class="loading-message" style="display:none;">
                            <span class="htaccess-spinner" style="margin-top:4px;">
                                <img src="<?php echo $this->_constants->IMAGES_ROOT_URL() . 'spinner.gif'; ?>">
                            </span>
                            <?php _e( 'Adding all existing link entries to htaccess. Please be patient...<br/>This could take a while depending on the number of existing links' , 'thirstyaffiliates-pro' ); ?>
                        </p>

                        <p class="success-message" style="display:none;"></p>

                    </div>

                    <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
                </td>

                <script>
                jQuery( document ).ready( function( $ ) {

                    $( '.<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>' ).on( 'click' , '.record-all-links-field button' , function() {

                        var $this            = $(this),
                            $field           = $this.closest( '.record-all-links-field' ),
                            $loading_message = $field.find( '.loading-message' ),
                            $success_message = $field.find( '.success-message' );

                        $this.prop( 'disabled' , true );
                        $loading_message.show();

                        $.post( ajaxurl , {
                            action  : 'tap_htaccess_record_all_links'
                        }, function( response ) {

                            if ( response.status == 'success' ) {

                                $loading_message.hide();
                                $success_message.append( response.message ).show();
                                $this.prop( 'disabled' , false );

                                setTimeout( function(){ $success_message.fadeOut().html( '' ) }, 15000 );
                                $( "#recreate-htaccess-warning-notice" ).fadeOut();

                            } else {

                                // TODO: change to vex dialog
                                alert( response.error_msg );
                            }

                        }, 'json' );
                    } );

                } );
                </script>

            </tr>
            <?php

        };

        return $supported_field_types;
    }

    /**
     * Register view debug log field.
     *
     * @since 1.2.0
     * @access public
     *
     * @param array $supported_field_types Array of all supported field types for the WPB Settings API.
     * @return array Filtered array of supported field types.
     */
    public function register_view_debug_log_field( $supported_field_types ) {

        if ( array_key_exists( 'view_debug_log' , $supported_field_types ) )
            return $supported_field_types;

        $supported_field_types[ 'view_debug_log' ] = function( $option ) {

            // This function will be the render callback of this new custom field type
            // It will expect 1 parameter to be passed by our Settings API, and that is the $option data

            $option_value = get_option( $option[ 'id' ] );

            ?>

            <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

                <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

                <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">

                    <button class="button vex-open" type="button" data-log="htaccess"><?php _e( 'View .htaccess debug log' , 'thirstyaffiliates-pro' ); ?></button>
                    <button class="button vex-open" type="button" data-log="plugin"><?php _e( 'View plugin debug log' , 'thirstyaffiliates-pro' ); ?></button>

                    <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>

                    <div class="vex-dialog-content" style="display:none;"><strong>text</strong></div>
                </td>

                <script>
                jQuery( document ).ready( function( $ ) {

                    var $debug_log_row = $( '.<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>' ),
                        $dialog, data;

                    $debug_log_row.on( 'click' , 'button.vex-open' , function() {

                        data    = { action : 'tap_get_debug_log' , log : $(this).data( 'log' ) };
                        $dialog = vex.open({
                            content   : ' ',
                            className : 'vex-theme-plain vex-debug-log'
                        });

                        $.post( ajaxurl , data , function( response ) {

                            if ( response.status == 'success' ) {

                                var $vex_debug_log = $( 'body' ).find( '.vex-theme-plain.vex-debug-log' ),
                                    $vex_content   = $vex_debug_log.find( '.vex-content' );

                                $vex_content.append( response.markup );
                                $vex_content.addClass( "show" );

                            } else {

                                // TODO: change to vex dialog
                                alert( response.error_msg );
                            }

                        }, 'json' );


                    });
                });
                </script>

            </tr>
            <?php

        };
        return $supported_field_types;
    }

    /**
     * Register htaccess filx all links field.
     *
     * @since 1.1.3
     * @access public
     *
     * @param array $supported_field_types Array of all supported field types for the WPB Settings API.
     * @return array Filtered array of supported field types.
     */
    public function register_fix_all_links_field( $supported_field_types ) {

        if ( array_key_exists( 'fix_all_links' , $supported_field_types ) )
            return $supported_field_types;

        $supported_field_types[ 'fix_all_links' ] = function( $option ) {

            // This function will be the render callback of this new custom field type
            // It will expect 1 parameter to be passed by our Settings API, and that is the $option data

            $option_value = get_option( $option[ 'id' ] );

            ?>

            <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

                <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

                <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">

                    <div class="fix-all-links-field">

                        <button type="button" id= "<?php echo esc_attr( $option[ 'id' ] ); ?>" class="button button-primary">
                            <?php _e( 'Fix all links' , 'thirstyaffiliates-pro' ); ?>
                        </button>

                        <p class="loading-message" style="display:none;">
                            <span class="htaccess-spinner" style="margin-top:4px;">
                                <img src="<?php echo $this->_constants->IMAGES_ROOT_URL() . 'spinner.gif'; ?>">
                            </span>
                            <?php _e( 'Fixing all existing link entries on the htaccess. Please be patient...<br/>This could take a while depending on the number of existing links' , 'thirstyaffiliates-pro' ); ?>
                        </p>

                        <p class="success-message" style="display:none;"></p>

                    </div>

                    <p class="desc"><?php echo isset( $option[ 'desc' ] ) ? $option[ 'desc' ] : ''; ?></p>
                </td>

                <script>
                jQuery( document ).ready( function( $ ) {

                    $( '.<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>' ).on( 'click' , '.fix-all-links-field button' , function() {

                        var $this            = $(this),
                            $field           = $this.closest( '.fix-all-links-field' ),
                            $loading_message = $field.find( '.loading-message' ),
                            $success_message = $field.find( '.success-message' );

                        $this.prop( 'disabled' , true );
                        $loading_message.show();

                        $.post( ajaxurl , {
                            action  : 'tap_fix_all_links'
                        }, function( response ) {

                            if ( response.status == 'success' ) {

                                $loading_message.hide();
                                $success_message.html( response.message ).show();
                                $this.prop( 'disabled' , false );

                                setTimeout( function(){ $success_message.fadeOut().html( '' ) }, 8000 );

                            } else {

                                // TODO: change to vex dialog
                                alert( response.error_msg );
                            }

                        }, 'json' );
                    } );
                } );
                </script>

            </tr>
            <?php

        };

        return $supported_field_types;
    }

    /**
     * Register trigger link health checker field.
     *
     * @since 1.3.0
     * @since 1.3.2 Set it so that if link health checker is running, then button is hidden and instead show a message.
     * @access public
     *
     * @param array $supported_field_types Array of all supported field types for the WPB Settings API.
     * @return array Filtered array of supported field types.
     */
    public function register_trigger_link_health_checker_field( $supported_field_types ) {

        if ( array_key_exists( 'trigger_link_health_checker' , $supported_field_types ) )
            return $supported_field_types;

        $supported_field_types[ 'trigger_link_health_checker' ] = function( $option ) {

            ?>
             <tr valign="top" class="<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>">

                <th scope="row"><?php echo sanitize_text_field( $option[ 'title' ] ); ?></th>

                <td class="forminp forminp-<?php echo sanitize_title( $option[ 'type' ] ) ?>">
                    <?php if ( get_transient( 'tap_link_health_checker_transient' ) ) : ?>
                        <span class="still-running"><?php _e( 'The previous automatic 404 checker cron job is still running.' , 'thirstyaffiliates-pro' ); ?></span>
                    <?php else : ?>
                        <button type="button" class="button-primary" data-nonce="<?php echo wp_create_nonce( 'tap_trigger_link_health_checker' ); ?>">
                            <?php _e( 'Run automatic 404 checker cron now' , 'thirstyaffiliates-pro' ) ?>
                        </button>
                        <span class="success-msg" style="display: none;"></span>
                    <?php endif; ?>
                    <p class="desc">
                        <?php echo esc_html( $option[ 'desc' ] ); ?>
                    </p>
                </td>

                <script type="text/javascript">
                jQuery( document ).ready( function($) {

                    $( '.<?php echo esc_attr( $option[ 'id' ] ) . '-row'; ?>' ).on( 'click' , '.button-primary' , function() {

                        var $row    = $(this).closest( 'tr' ),
                            $button = $(this);
                        $button.prop( 'disabled' , true );

                        $.post( ajaxurl , {
                            action : 'tap_trigger_link_health_checker',
                            nonce  : $button.data( 'nonce' )
                        } , function( response ) {

                            if ( response.status == 'success' ) {

                                $row.find( 'td .success-msg' ).text( response.message )
                                    .show().delay( 5000 ).fadeOut( 'fast' );

                            } else {
                                // TODO: changed to VEX modal
                                alert( response.error_msg );
                                $button.prop( 'disabled' , false );
                            }

                        }, 'json' );

                    });

                });
                </script>
            </tr>
            <?php

        };

        return $supported_field_types;
    }

    /**
     * Register a new custom settings section ( ThirstyAffiliates Pro ).
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_sections All registered settings sections.
     * @return array Filtered registered settings sections.
     */
    public function register_tap_settings_section( $settings_sections ) {

        $help_settings = $settings_sections[ 'ta_help_settings' ];

        // remove the help settings section
        unset( $settings_sections[ 'ta_help_settings' ] );

        $offset = ( int ) array_search( 'ta_import_export_settings' , array_keys( $settings_sections ) );

        $tap_settings = array();

        // register autolinker settings
        if ( ! array_key_exists( 'tap_autolinker_settings' , $settings_sections ) && ( get_option( 'tap_enable_autolinker' , 'yes' ) === 'yes' ) ) {
            $tap_settings[ 'tap_autolinker_settings' ] = array(
                'title' => __( 'Autolinker' , 'thirstyaffiliates-pro' ),
                'desc'  => __( 'Autolinker lets you provide a list of keywords to automatically link your affiliate links to in your content. These settings let you tweak exactly how the Autolinker does this job.' , 'thirstyaffiliates-pro' )
            );
        }

        // register geolocation settings
        if ( ! array_key_exists( 'tap_geolocations_settings' , $settings_sections ) && ( get_option( 'tap_enable_geolocation' , 'yes' ) === 'yes' ) ) {
            $tap_settings[ 'tap_geolocations_settings' ] = array(
                'title' => __( 'Geolocations' , 'thirstyaffiliates-pro' ),
                'desc'  => __( 'Settings for the geolcations module.' , 'thirstyaffiliates-pro' )
            );
        }

        // register google click tracking settings
        if ( ! array_key_exists( 'tap_google_click_tracking_settings' , $settings_sections ) && ( get_option( 'tap_enable_google_click_tracking' , 'yes' ) === 'yes' ) ) {

            $gtm_kb_url = 'https://thirstyaffiliates.com/knowledgebase/using-thirstyaffiliates-click-tracking-google-tag-manager-gtm';

            $tap_settings[ 'tap_google_click_tracking_settings' ] = array(
                'title' => __( 'Click Tracking' , 'thirstyaffiliates-pro' ),
                'desc'  => sprintf( __( "<strong>Note:</strong> The Google Click Tracking module will not work until you've installed Google Analytics in your site. You can install it via a third party plugin. For advanced users using Google Tag Manager for analytics, please read this <a href='%s' target='_blank'>knowledgebase article</a>." , 'thirstyaffiliates-pro' ) , $gtm_kb_url ),
            );
        }

        // register link health checker settings
        if ( ! array_key_exists( 'tap_link_health_checker_settings' , $settings_sections ) && ( get_option( 'tap_enable_link_health_checker' , 'no' ) === 'yes' ) ) {
            $tap_settings[ 'tap_link_health_checker_settings' ] = array(
                'title' => __( 'Automatic 404 Checker' , 'thirstyaffiliates-pro' ),
                'desc'  => __( 'Settings for the automatic 404 checker module.' , 'thirstyaffiliates-pro' )
            );
        }

        // register url shortener settings
        if ( ! array_key_exists( 'tap_url_shortener_settings' , $settings_sections ) && ( get_option( 'tap_enable_url_shortener' , 'yes' ) === 'yes' ) ) {
            $tap_settings[ 'tap_url_shortener_settings' ] = array(
                'title' => __( 'URL Shortener' , 'thirstyaffiliates-pro' ),
                'desc'  => __( 'Settings for the URL shortener module.' , 'thirstyaffiliates-pro' )
            );
        }

        // register link scheduler settings
        if ( ! array_key_exists( 'tap_link_scheduler_settings' , $settings_sections ) && ( get_option( 'tap_enable_link_scheduler' , 'yes' ) === 'yes' ) ) {
            $tap_settings[ 'tap_link_scheduler_settings' ] = array(
                'title' => __( 'Link Scheduler' , 'thirstyaffiliates-pro' ),
                'desc'  => __( 'Settings for the link scheduler module.' , 'thirstyaffiliates-pro' )
            );
        }

        // register plugin visibility settings
        if ( ! array_key_exists( 'tap_plugin_visibility_settings' , $settings_sections ) && ( get_option( 'tap_enable_plugin_visibility' , 'yes' ) === 'yes' ) ) {
            if ( ! array_key_exists( 'tap_plugin_visibility_settings' , $settings_sections ) ) {
                $tap_settings[ 'tap_plugin_visibility_settings' ] = array(
                    'title' => __( 'Plugin Visibility' , 'thirstyaffiliates-pro' ),
                    'desc'  => __( 'These settings allow you to manage the visibility of screens and menus throughout the plugin. Use it to allow access to some parts of the plugin for people who work on your site, but restrict access to other undesirable areas. Admins have full access and are unaffected by these settings.' , 'thirstyaffiliates-pro' )
                );
            }
        }

        // register affiliate disclosure settings
        if ( ! array_key_exists( 'tap_affiliate_disclosure_settings' , $settings_sections ) && ( get_option( 'tap_enable_affiliate_disclosure' ) === 'yes' ) ) {
            if ( ! array_key_exists( 'tap_affiliate_disclosure_settings' , $settings_sections ) ) {
                $tap_settings[ 'tap_affiliate_disclosure_settings' ] = array(
                    'title' => __( 'Affiliate Disclosure' , 'thirstyaffiliates-pro' ),
                    'desc'  => __( 'Settings to customize affiliate disclosure display.' , 'thirstyaffiliates-pro' )
                );
            }
        }

        // add back the help settings section
        $settings_sections[ 'ta_help_settings' ] = $help_settings;

        return ( ( count ( $tap_settings ) > 0 ) ? array_merge ( array_slice( $settings_sections , 0 , $offset , true) , $tap_settings , array_slice( $settings_sections , $offset , null , true) ) : $settings_sections );

    }


    /**
     * Reorder the settings section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_sections All settings sections.
     * @return array Reorder settings sections.
     */
    public function reorder_tap_settings_section( $settings_sections ) {

        $help_settings = $settings_sections[ 'ta_help_settings' ];

        // remove the help settings section
        unset( $settings_sections[ 'ta_help_settings' ] );

        // add the help settings section to the end
        $settings_sections[ 'ta_help_settings' ] = $help_settings;

        return $settings_sections;

    }

    /**
     * Register options for the autolinker module settings section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_section_options Registered options for each registered settings section.
     * @return array Filtered registered options for each registered settings section.
     */
    public function register_autolinker_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_autolinker_settings' , $settings_section_options ) )
            return;

        $all_post_types     = $this->_helper_functions->get_all_post_types_as_options();
        $autolinker_version = get_option( 'tap_use_revamped_autolinker' , 'old' );
        $default_post_types = apply_filters( 'tap_autolink_default_post_types' , array( 'post', 'page' ) );

        $settings_section_options[ 'tap_autolinker_settings' ] = apply_filters(
            'tap_autolinker_settings_options' , // Add a filter to the array of options for this section so that others can extend it
            array(
                array(
                    'id'      => 'tap_use_revamped_autolinker',
                    'title'   => __( 'Autolinker function' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Select version of autolinker function to use.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'select',
                    'default' => 'old',
                    'options' => array(
                        'new' => __( 'New (version 1.4.0 and up).' , 'thirstyaffiliates-pro' ),
                        'old' => __( 'Old (below version 1.4.0)' , 'thirstyaffiliates-pro' )
                    )
                ),
                array(
                    'id'      => 'tap_autolink_keyword_limit',
                    'title'   => __( 'Keyword Limit' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'How many links per keyword should the autolinker place before stopping? (setting it to 0 will disable keyword limit).' , 'thirstyaffiliates-pro' ),
                    'type'    => 'number',
                    'default' => 3
                ),
                array(
                    'id'      => 'tap_autolink_random_placement',
                    'title'   => __( 'Random Autolink Placement?' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Enable random placement of autolinks globally.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'toggle',
                    'default' => 'no'
                ),
                array(
                    'id'      => 'tap_autolink_inside_heading',
                    'title'   => __( 'Link inside of heading tags?' , 'thirstyaffiliates-pro' ),
                    'desc'    => esc_attr__( 'Enable link placement inside of heading tags. Eg. <h1>, <h2>, <h3>, etc. Note this only links if the heading is part of the actual content, not the post/page title globally.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'toggle',
                    'default' => 'no'
                ),
                array(
                    'id'      => 'tap_autolink_disable_archives',
                    'title'   => __( 'Disable autolinking on archive pages?' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Disable autolinking on archive pages (this includes category and tag pages)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'toggle',
                    'default' => 'no'
                ),
                array(
                    'id'      => 'tap_autolink_disable_homepage',
                    'title'   => __( 'Disable autolinking on home page?' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Disable autolinking on home page (many blogs feature a blog post list on the home page or don\'t want links on the home page)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'toggle',
                    'default' => 'no'
                ),
                array(
                    'id'      => 'tap_autolink_enable_feeds',
                    'title'   => __( 'Enable autolinking in feeds?' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'By default Autolinker does not replace keywords in feeds due to it sometimes causing feed validation issues. This option turns it back on.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'toggle',
                    'default' => 'no'
                ),
                array(
                    'id'           => 'tap_autolink_post_types',
                    'title'        => __( 'Enable Autolinker on the following post types:' , 'thirstyaffiliates-pro' ),
                    'desc'         => __( 'Enable the post types you want the Autolinker to work with. If no post types are selected, posts and pages will be enabled for you.' , 'thirstyaffiliates-pro' ),
                    'type'         => 'multiselect',
                    'options'      => $all_post_types,
                    'default'      => $default_post_types,
                    'placeholder'  => __( 'Select post types...' , 'thirstyaffiliates-pro' )
                ),
                array(
                    'id'      => 'autolink_rebuild_cache',
                    'title'   => __( 'Clear the Autolinker cache?' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'The Autolinker add-on self manages it\'s own cache of links and keywords for performance reasons. It will update the cache upon saving a link with that link\'s new value, however if you are experiencing issues you can try clearing and rebuilding the cache manually.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'autolink_rebuild_cache',
                    'default' => __( 'Clear & Rebuild Autolinker Cache' , 'thirstyaffiliates-pro' )
                )
            )
        );

        return $settings_section_options;
    }

    /**
     * Register options for the geolocations module settings section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_section_options Registered options for each registered settings section.
     * @return array Filtered registered options for each registered settings section.
     */
    public function register_geolocations_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_geolocations_settings' , $settings_section_options ) )
            return;

        $settings_section_options[ 'tap_geolocations_settings' ] = apply_filters(
            'tap_geolocations_settings_options' , // Add a filter to the array of options for this section so that others can extend it
            array(
                array(
                    'id'      => 'tap_geolocations_maxmind_db',
                    'title'   => __( 'MaxMind Database' , 'thirstyaffiliates-pro' ),
                    'desc'    => '',
                    'type'    => 'radio',
                    'options' => array(
                        'free'        => __( '<strong>Use free MaxMind Country DB.</strong> <br>ThirstyAffiliates will source the latest freely provided MaxMind Country database and use this for IP address location checking.' , 'thirstyaffiliates-pro' ),
                        'premium'     => __( '<strong>Use premium MaxMind Country DB.</strong> <br>If you have access to a premium MaxMind Country database file, you can upload it here and use this for IP address location checking.' , 'thirstyaffiliates-pro' ),
                        'web_service' => __( '<strong>Use MaxMind Web Service.</strong> <br>If you have access to a MaxMin web service account, you can enter your API credentials here and use this for IP address location checking.' , 'thirstyaffiliates-pro' )
                    ),
                    'default' => 'free'
                ),
                array(
                    'id'      => 'tap_geolocations_maxmind_mmdb_file',
                    'title'   => __( 'Enable MaxMind DB Integration' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'a .mmdb file containing country details which will be used for the Maxmind DB integration.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'maxmind_db_upload',
                    'class'   => 'maxmind-db-toggle',
                    'sanitation_cb' => array( $this , 'handle_maxmind_db_upload' )
                ),
                array(
                    'id'      => 'tap_geolocations_maxmind_api_userid',
                    'title'   => __( 'MaxMind Web Service User ID' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'User identifier to access Maxmind\'s API service.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text',
                    'class'   => 'maxmind-web-toggle'
                ),
                array(
                    'id'      => 'tap_geolocations_maxmind_api_key',
                    'title'   => __( 'MaxMind Web Service API Key' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Service key required to access Maxmind\'s API service.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text',
                    'class'   => 'maxmind-web-toggle'
                ),
                array(
                    'id'      => 'tap_geolocations_disable_proxy_test',
                    'title'   => __( 'Disable Forwarding Proxy Test' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Some hosts use a forwarding proxy to detect the client\'s IP address which is needed for geo detection, we automatically try to see if this is active, but some hosts have a funny setup where they show both IPs in the forwarding information. This setting disables the check altogether so you can get around this limitation.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'toggle',
                    'default' => 'no'
                ),
            )
        );

        return $settings_section_options;
    }

    /**
     * Register options for the google click tracking module settings section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_section_options Registered options for each registered settings section.
     * @return array Filtered registered options for each registered settings section.
     */
    public function register_google_click_tracking_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_google_click_tracking_settings' , $settings_section_options ) )
            return;

        $settings_section_options[ 'tap_google_click_tracking_settings' ] = apply_filters(
            'tap_google_click_tracking_settings',
            array(
                array(
                    'id'      => 'tap_google_click_tracking_script',
                    'title'   => __( 'Tracking Script' , 'thirstyaffiliates-pro' ),
                    'desc'    => '',
                    'type'    => 'radio',
                    'options' => array(
                        'gtag_ga'      => __( '<strong>Global Site Tag (gtag.js)</strong> <br> The Global Site Tag provides streamlined tagging across Google’s site measurement, conversion tracking, and remarketing products – giving you better control while making implementation easier. This is the newest variant of the Google Analytics tracking script.' , 'thirstyaffiliates-pro' ),
                        'universal_ga' => __( '<strong>Universal Google Analytics</strong> <br>The most common Google Analytics tag, uses the function ga().' , 'thirstyaffiliates-pro' ),
                        'legacy_ga'    => __( '<strong>Legacy Google Analytics</strong> <br>Legacy support for the older style _gaq function.' , 'thirstyaffiliates-pro' ),
                        'gtm'          => sprintf( __( '<strong>Google Tag Manager</strong> <br>For those using GTM please see these <a href="%s">additional setup instructions</a>.' , 'thirstyaffiliates-pro' ) , 'https://thirstyaffiliates.com/knowledgebase/using-thirstyaffiliates-click-tracking-google-tag-manager-gtm?utm_source=Pro&utm_medium=GCTSettings' )
                    ),
                    'default' => 'universal_ga'
                ),
                array(
                    'id'      => 'tap_google_click_tracking_event_action_value',
                    'title'   => __( 'Event action value' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Value to set on the event action parameter of Google Analytics event tracking. Note: for uncloaked URLs, the value of event action will always be the full URL except for "link text" option.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'select',
                    'options' => array(
                        'link_uri'  => __( 'Link URI (full link value without the site address)' , 'thirstyaffiliates-pro' ),
                        'href'      => __( 'Full link URL' , 'thirstyaffiliates-pro' ),
                        'link_slug' => __( 'Link slug (last segment of URL)' , 'thirstyaffiliates-pro'  ),
                        'link_text' => __( 'Link text' , 'thirstyaffiliates-pro' )
                    ),
                    'default' => 'link_uri'
                ),
                array(
                    'id'      => 'tap_google_click_tracking_action_name',
                    'title'   => __( 'Custom event category name:' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Customize the event category name of the click tracking Event. (Default: "Affiliate Link")' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text'
                ),
                array(
                    'id'      => 'tap_universal_ga_custom_func',
                    'title'   => __( 'Custom Function Name (for universal GA):' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( "Enter the custom function name used for the Universal Google Analytics on your site. This only applies if your using other than <code>ga</code> or <code>__gaTracker</code>." , 'thirstyaffiliates-pro' ),
                    'type'    => 'text'
                )
            )
        );

        return $settings_section_options;
    }

    /**
     * Register options for the link health checker module settings section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $settings_section_options Registered options for each registered settings section.
     * @return array Filtered registered options for each registered settings section.
     */
    public function register_link_health_checker_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_link_health_checker_settings' , $settings_section_options ) )
            return;

        $settings_section_options[ 'tap_link_health_checker_settings' ] = apply_filters(
            'tap_link_health_checker_settings',
            array(
                array(
                    'id'      => 'tap_link_health_checker_days_offset',
                    'title'   => __( 'Days offset:' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'days (interval to run the automatic 404 checker cron job.)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'number',
                    'default' => 7,
                    'min'     => 1,
                    'max'     => 30
                ),
                array(
                    'id'      => 'tap_link_health_checker_hours_offset',
                    'title'   => __( 'Hours offset:' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'hours (interval to run the automatic 404 checker cron job.)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'number',
                    'default' => 0,
                    'min'     => 0,
                    'max'     => 23
                ),
                array(
                    'id'      => 'tap_trigger_link_health_checker',
                    'title'   => __( 'Run Automatic 404 Checker Manually' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'This will trigger the automatic 404 checker scheduled cron job to run manually.
                                     Once it runs, this process will be done on the background. Please note that only
                                     one instance can be run at a time and it may take some time to finish.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'trigger_link_health_checker'
                )
            )
        );

        return $settings_section_options;
    }

    /**
     * Register options for the url shortener module settings section.
     *
     * @since 1.0.0
     * @since 1.3.0 Add settings for Firebase Dynamic Links.
     * @access public
     *
     * @param array $settings_section_options Registered options for each registered settings section.
     * @return array Filtered registered options for each registered settings section.
     */
    public function register_url_shortener_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_url_shortener_settings' , $settings_section_options ) )
            return;

        $url_shortener_services = array(
            'googl'      => __( '<strong>Goo.gl</strong> <br>Shorten your affiliate links with Google\'s official ur shortener service.' , 'thirstyaffiliates-pro' ),
            'firebasedl' => __( '<strong>Firebase Dynamic Links</strong> <br>Shorten your affiliate links with Google\'s new Firebase Dynamic Links.' , 'thirstyaffiliates-pro' )
        );

        if ( get_option( 'tap_url_shortener_service' ) === 'bitly' ) {
            $url_shortener_services['bitly'] = __( '<strong>Bitly</strong> <br>Shorten your affiliate links with Bitly service by entering your access token.' , 'thirstyaffiliates-pro' ) .
                sprintf( '<br><span class="notice notice-warning">%s</span>', __( 'The Bitly integration is deprecated and will soon be removed.', 'thirstyaffiliates-pro' ) );
        }

        $settings_section_options[ 'tap_url_shortener_settings' ] = apply_filters(
            'tap_url_shortener_settings',
            array(
                array(
                    'id'      => 'tap_url_shortener_service',
                    'title'   => __( 'URL Shortener Service' , 'thirstyaffiliates-pro' ),
                    'desc'    => '',
                    'type'    => 'radio',
                    'options' => $url_shortener_services,
                    'default' => 'googl'
                ),
                array(
                    'id'      => 'tap_googl_api_key',
                    'title'   => __( 'Goo.gl API key:' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Please enter your goo.gl account API key.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text'
                ),
                array(
                    'id'      => 'tap_firebase_dynamic_links_api_key',
                    'title'   => __( 'Firebase Dynamic Links API key:' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Please enter your Firebase project API key.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text'
                ),
                array(
                    'id'      => 'tap_firebase_dynamic_link_domain',
                    'title'   => __( 'Firebase Dynamic Link Domain:' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'Please enter your Firebase dynamic link domain.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'url'
                ),
                array(
                    'id'      => 'tap_generate_short_url_on_affiliate_link_save',
                    'title'   => __( 'Generate Short URL on Affiliate Link Save?' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'By default short urls are generated separately by clicking the "Generate Short URL" button on the edit affiliate link page. Checking this option will immediately generate a short URL for the destination URL when the affiliate link is saved.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'toggle',
                    'default' => 'no'
                )
            )
        );

        if ( get_option( 'tap_url_shortener_service' ) === 'bitly' ) {

            array_splice(
                $settings_section_options[ 'tap_url_shortener_settings' ],
                1,
                0,
                array(
                    array(
                        'id'      => 'tap_bitly_access_token',
                        'title'   => __( 'Bitly Access Token:' , 'thirstyaffiliates-pro' ),
                        'desc'    => __( 'Please enter your Bitly account access token.' , 'thirstyaffiliates-pro' ),
                        'type'    => 'text'
                    )
                )
            );
        }

        return $settings_section_options;
    }

    /**
     * Register options for the link scheduler module settings section.
     *
     * @since 1.2.0
     * @access public
     *
     * @param array $settings_section_options Registered options for each registered settings section.
     * @return array Filtered registered options for each registered settings section.
     */
    public function register_link_scheduler_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_link_scheduler_settings' , $settings_section_options ) )
            return;

        $redirect_types = array(
            '302' => __( '302 Temporary' , 'thirstyaffiliates-pro' ),
            '307' => __( '307 Temporary (alternative)' , 'thirstyaffiliates-pro' )
        );

        $settings_section_options[ 'tap_link_scheduler_settings' ] = apply_filters(
            'tap_link_scheduler_settings',
            array(
                array(
                    'id'      => 'tap_global_before_start_redirect_url',
                    'title'   => __( 'Before start redirect URL (global)' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'The value here will be used when the before start redirect URL field in the affiliate link is empty. (Defaults to the home URL)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text',
                    'default' => home_url('/')
                ),
                array(
                    'id'      => 'tap_global_before_start_redirect_type',
                    'title'   => __( 'Before start redirect type (global)' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'The redirect type to use when <em>before start redirect URL</em> is implemented.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'select',
                    'options' => $redirect_types,
                    'default' => '302'
                ),
                array(
                    'id'      => 'tap_global_after_expire_redirect_url',
                    'title'   => __( 'Link expiration redirect URL (global)' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'The value here will be used when the after expire redirect URL field in the affiliate link is empty. (Defaults to the home URL)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text',
                    'default' => home_url('/')
                ),
                array(
                    'id'      => 'tap_global_after_expire_redirect_type',
                    'title'   => __( 'Link expiration redirect type (global)' , 'thirstyaffiliates-pro' ),
                    'desc'    => __( 'The redirect type to use when <em>after expire redirect URL</em> is implemented.' , 'thirstyaffiliates-pro' ),
                    'type'    => 'select',
                    'options' => $redirect_types,
                    'default' => '302'
                )
            )
        );

        return $settings_section_options;
    }

    /**
     * Register options for the plugin visibility module settings section.
     *
     * @since 1.2.0
     * @access public
     *
     * @param array $settings_section_options Registered options for each registered settings section.
     * @return array Filtered registered options for each registered settings section.
     */
    public function register_plugin_visibility_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_plugin_visibility_settings' , $settings_section_options ) )
            return;

        $admin_interfaces   = apply_filters( 'ta_admin_interfaces' , array() );
        $setting_interfaces = isset( $admin_interfaces[ 'thirstylink_page_thirsty-settings' ] ) ? $admin_interfaces[ 'thirstylink_page_thirsty-settings' ] : array();
        $report_interfaces  = isset( $admin_interfaces[ 'thirstylink_page_thirsty-reports' ] ) ? $admin_interfaces[ 'thirstylink_page_thirsty-reports' ] : array();

        $settings_section_options[ 'tap_plugin_visibility_settings' ] = apply_filters(
            'tap_plugin_visibility_settings', array(
                array(
                    'id'        => 'tap_plugin_visibility_admin_interfaces',
                    'title'     => __( 'Admin Interfaces' , 'thirstyaffiliates-pro' ),
                    'type'      => 'plugin_visibility',
                    'desc'      => __( 'Control the capability required to view and manage certain ThirstyAffiliates features.' , 'thirstyaffiliates-pro' ),
                    'options'   => $admin_interfaces,
                    'item_data' => apply_filters( 'tap_admin_interfaces_visibility_setting_data' , array(
                        'edit-thirstylink' => array(
                            'label' => __( 'Affiliate Links (list)' , 'thirstyaffiliates-pro' ),
                            'name'  => 'thirstylink_list',
                        ),
                        'thirstylink' => array(
                            'label' => __( 'Add/Edit Affiliate Link' , 'thirstyaffiliates-pro' ),
                            'name'  => 'thirstylink_edit'
                        ),
                        'edit-tap-event-notification' => array(
                            'label' => __( 'Event Notifications' , 'thirstyaffiliates-pro' ),
                            'name'  => 'event_notifications'
                        ),
                        'edit-thirstylink-category' => array(
                            'label' => __( 'Link Categories' , 'thirstyaffiliates-pro' ),
                            'name'  => 'link_categories'
                        ),
                        'thirstylink_page_amazon' => array(
                            'label' => __( 'Amazon Import' , 'thirstyaffiliates-pro' ),
                            'name'  => 'amazon'
                        ),
                        'thirsty-settings' => array(
                            'label' => __( 'Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'settings'
                        ),
                        'thirsty-reports' => array(
                            'label' => __( 'Reports' , 'thirstyaffiliates-pro' ),
                            'name'  => 'reports'
                        ),
                    ) )
                ),
                array(
                    'id'        => 'tap_plugin_visibility_report_interfaces',
                    'title'     => __( 'Report Interfaces' , 'thirstyaffiliates-pro' ),
                    'type'      => 'plugin_visibility',
                    'desc'      => __( 'Control the capability required to view ThirstyAffiliates reports.' , 'thirstyaffiliates-pro' ),
                    'options'   => $report_interfaces,
                    'item_data' => apply_filters( 'tap_report_interfaces_visibility_setting_data' , array(
                        'link_performance' => array(
                            'label' => __( 'Link Performance Report' , 'thirstyaffiliates-pro' ),
                            'name'  => 'link_performance'
                        ),
                        'geolocation' => array(
                            'label' => __( 'Geolocations Report' , 'thirstyaffiliates-pro' ),
                            'name'  => 'geolocation'
                        ),
                        'stats_table' => array(
                            'label' => __( 'Stats Report' , 'thirstyaffiliates-pro' ),
                            'name'  => 'stats_table'
                        ),
                        'keyword_report' => array(
                            'label' => __( 'Keyword Report' , 'thirstyaffiliates-pro' ),
                            'name'  => 'keyword'
                        ),
                        'link_health_report' => array(
                            'label' => __( '404 Checker Report' , 'thirstyaffiliates-pro' ),
                            'name'  => 'link_health'
                        ),
                    ) )
                ),
                array(
                    'id'        => 'tap_plugin_visibility_setting_interfaces',
                    'title'     => __( 'Setting Interfaces' , 'thirstyaffiliates-pro' ),
                    'type'      => 'plugin_visibility',
                    'desc'      => __( 'Control the capability required to view and manage each ThirstyAffiliates setting tab.' , 'thirstyaffiliates-pro' ),
                    'options'   => $setting_interfaces,
                    'item_data' => apply_filters( 'tap_setting_interfaces_visibility_setting_data' , array(
                        'ta_general_settings' => array(
                            'label' => __( 'General Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'general'
                        ),
                        'ta_links_settings' => array(
                            'label' => __( 'Links Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'links'
                        ),
                        'ta_modules_settings' => array(
                            'label' => __( 'Modules Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'modules'
                        ),
                        'tap_amazon_settings_section' => array(
                            'label' => __( 'Amazon Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'amazon'
                        ),
                        'tap_autolinker_settings' => array(
                            'label' => __( 'Autolinker Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'autolinker'
                        ),
                        'tap_geolocations_settings' => array(
                            'label' => __( 'Geolocation Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'geolocations'
                        ),
                        'tap_google_click_tracking_settings' => array(
                            'label' => __( 'Click Tracking Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'click_tracking'
                        ),
                        'tap_link_health_checker_settings' => array(
                            'label' => __( 'Automatic 404 Checker Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'link_health_checker'
                        ),
                        'tap_url_shortener_settings' => array(
                            'label' => __( 'URL Shortener Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'url_shortener'
                        ),
                        'tap_link_scheduler_settings' => array(
                            'label' => __( 'Link Scheduler Settings' , 'thirstyaffiliates-pro' ),
                            'name'  => 'link_scheduler'
                        ),
                        'ta_help_settings' => array(
                            'label' => __( 'Help Settings' , 'thirstyaffiliates-pro' ),
                            'desc'  => __( 'Menu item description' , 'thirstyaffiliates-pro' ),
                            'name'  => 'help'
                        ),
                    ) )
                )
            )
        );

        return $settings_section_options;
    }

    /**
     * Register options for the affiliate disclosure module settings section.
     */
    public function register_affiliate_disclosure_settings_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_affiliate_disclosure_settings' , $settings_section_options ) )
            return;

        $all_post_types     = $this->_helper_functions->get_all_post_types_as_options();
        $default_post_types = apply_filters( 'tap_disclosure_notice_default_post_types' , array( 'post' , 'page' ) );

        $settings_section_options[ 'tap_affiliate_disclosure_settings' ] = apply_filters(
            'tap_affiliate_disclosure_settings', array(
                array(
                    'id'      => 'tap_affiliate_disclosure_page',
                    'title'   => __( 'Disclosure page' , 'thirstyaffiliates-pro' ),
                    'type'    => 'select',
                    'desc'    => __( 'The page which contains full information of your disclosure notice.' , 'thirstyaffiliates-pro' ),
                    'options' => $this->_helper_functions->get_all_pages_as_options(),
                    'default' => ''
                ),
                array(
                    'id'      => 'tap_disclosure_notice_button_message',
                    'title'   => __( 'Disclosure notice link text' , 'thirstyaffiliates-pro' ),
                    'type'    => 'text',
                    'desc'    => __( 'The text to display for the link to the set disclosure page' , 'thirstyaffiliates-pro' ),
                    'default' => __( 'disclosure notice' , 'thirstyaffiliates-pro' )
                ),
                array(
                    'id'    => 'tap_display_disclosure_notice_icon',
                    'title' => __( 'Display disclosure notice tooltip' , 'thirstyaffiliates-pro' ),
                    'type'  => 'toggle',
                    'desc'  => __( "When checked, the plugin will add an icon next to all affiliate links in the site, which when hovered will display the disclosure notice message with a link to the disclosure notice page." , 'thirstyaffiliates-pro'  )
                ),
                array(
                    'id'      => 'tap_disclosure_notice_icon_message',
                    'title'   => __( 'Disclosure notice message (tooltip)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'textarea',
                    'desc'    => __( 'The text to display on the tooltip. Add <code>{{disclosure_link}}</code> to display the link to the disclosure page.' , 'thirstyaffiliates-pro' ),
                    'default' => __( 'This is an affiliate link. See our {{disclosure_link}}.' , 'thirstyaffiliates-pro' )
                ),
                array(
                    'id'    => 'tap_display_disclosure_notice_bottom_post',
                    'title' => __( 'Display disclosure notice at the bottom of article' , 'thirstyaffiliates-pro' ),
                    'type'  => 'toggle',
                    'desc'  => __( "When checked, the plugin will add the disclosure notice text at the bottom of the post or page. This will only be added on the actual post/page and not on archive pages." , 'thirstyaffiliates-pro'  )
                ),
                array(
                    'id'          => 'tap_disclosure_notice_bottom_post_types',
                    'title'       => __( 'Display disclosure notice on the following post types' , 'thirstyaffiliates-pro' ),
                    'type'        => 'multiselect',
                    'options'     => $all_post_types,
                    'default'     => $default_post_types,
                    'placeholder' => __( 'Select post types...' , 'thirstyaffiliates-pro' )
                ),
                array(
                    'id'      => 'tap_disclosure_notice_bottom_post_message',
                    'title'   => __( 'Disclosure notice message (bottom of article)' , 'thirstyaffiliates-pro' ),
                    'type'    => 'editor',
                    'desc'    => __( 'The text to display on the disclosure notice at the bottom of post/page article. Add <code>{{disclosure_link}}</code> to display the link to the disclosure page.' , 'thirstyaffiliates-pro' ),
                    'default' => __( '<h4>AFFILIATE DISCLOSURE:</h4><p>This article may contain affiliate links. See our {{disclosure_link}} for more information.</p>' , 'thirstyaffiliates-pro' )
                )
            )
        );

        return $settings_section_options;
    }

    /**
     * Register geolocation ip address debug on the help section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $help_options Help settings registered options.
     * @return array Filtered help settings registered options.
     */
    public function register_geolocation_ip_address_debug_option( $help_options ) {

        // skip if debugging is disabled and user is not an administrator.
        if ( ! WP_DEBUG || ! current_user_can( 'administrator' ) )
            return $help_options;

        $help_options[] = array(
            'id'          =>  'tap_geolocation_ip_address_debug',
            'title'       =>  __( 'Geolocation IP Address Debug' , 'thirstyaffiliates-pro' ),
            'desc'        =>  __( "Used for debugging the geolocation module by overriding the IP address detected in the server." , 'thirstyaffiliates-pro' ),
            'type'        =>  'text',
        );

        return $help_options;
    }

    /**
     * Register htaccess reload all links option on the help section.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $help_options Help settings registered options.
     * @return array Filtered help settings registered options.
     */
    public function register_htaccess_reload_all_links_option( $help_options ) {

        // skip if user is not an administrator.
        if ( ! current_user_can( 'administrator' ) || get_option( 'tap_enable_htaccess' ) !== 'yes' )
            return $help_options;

        $help_options[] = array(
            'id'          =>  'tap_htaccess_reload_all_links',
            'title'       =>  __( 'Record/recreate all existing links to .htaccess file' , 'thirstyaffiliates-pro' ),
            'desc'        =>  __( "Note: Because you are using the ThirstyAffiliates Htaccess Redirect add-on, links may appear cached in your browser. To test a link after you change the destination always use a Private Browsing/Incognito window to ensure a fresh browser session." , 'thirstyaffiliates-pro' ),
            'type'        =>  'htaccess_record_all_links',
        );

        return $help_options;
    }

    /**
     * Register view debug log option on the help section.
     *
     * @since 1.2.0
     * @access public
     *
     * @param array $help_options Help settings registered options.
     * @return array Filtered help settings registered options.
     */
    public function register_view_debug_log_option( $help_options ) {

        $help_options[] = array(
            'id'          =>  'tap_help_view_debug_log',
            'title'       =>  __( 'View Debug Log' , 'thirstyaffiliates-pro' ),
            'desc'        =>  __( "View debug logs description." , 'thirstyaffiliates-pro' ),
            'type'        =>  'view_debug_log',
        );

        return $help_options;
    }

    /**
     * Register htaccess fix all links option on the help section.
     *
     * @since 1.1.3
     * @access public
     *
     * @param array $help_options Help settings registered options.
     * @return array Filtered help settings registered options.
     */
    public function register_fix_all_links_option( $help_options ) {

        // skip if user is not an administrator.
        if ( ! current_user_can( 'administrator' ) )
            return $help_options;

        $help_options[] = array(
            'id'          =>  'tap_fix_all_links',
            'title'       =>  __( 'Fix all existing links in database and .htaccess file' , 'thirstyaffiliates-pro' ),
            'desc'        =>  __( "This feature will fix all ThirstyAffiliates links registered on the database and on the .htaccess file by replacing all instances of <code>&amp;amp;</code> to <code>&</code>." , 'thirstyaffiliates-pro' ),
            'type'        =>  'fix_all_links',
        );

        return $help_options;
    }

    /**
     * Register ThirstyAffiliates Pro modules.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $modules Registered modules toggable in the Settings.
     * @return array Registered modules toggable in the Settings.
     */
    public function register_tap_modules( $modules ) {

        $tap_modules = array(
            array(
                'id'      => 'tap_enable_autolinker',
                'title'   => __( 'Autolinker' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "The Autolinker module gives you the ability to automatically link keywords to affiliate links by defining a list for each affiliate link." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_geolocation',
                'title'   => __( 'Geolocation' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "The Geolocation module adds functionality for geolocation targeting and various other country specific features." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_csv_importer',
                'title'   => __( 'CSV Import/Export' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "Lets you bulk import affiliate links using a CSV spreadsheet. Handy for handling bulk link creation and for importing links from another link management plugin." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_google_click_tracking',
                'title'   => __( 'Google Click Tracking' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module lets you track clicks by way of Google Analytics Events to affiliate links that are inserted." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_htaccess',
                'title'   => __( 'Htaccess' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module moves your affiliate link redirects from PHP based redirects to .htaccess server based redirects." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'no'
            ),
            array(
                'id'      => 'tap_enable_event_notification',
                'title'   => __( 'Event Notification' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module allows you to send notifications to your email whenever a set event has been completed.<br><br><b>Warning :</b> For this feature to work, the <b>Statistics</b> module needs to be turned on." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_link_health_checker',
                'title'   => __( 'Automatic 404 Checker' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module checks the status of all registered affiliate links regularly via cron, by pinging the destination URL and informs the admin by email if a URL is still working or dead." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'no'
            ),
            array(
                'id'      => 'tap_enable_url_shortener',
                'title'   => __( 'URL Shortener' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module allows you to shorten your affiliate link URLs by the most popular shortener services available." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_link_scheduler',
                'title'   => __( 'Link Scheduler' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module allows you set the dates for your affiliate links to become active and/or expire." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_plugin_visibility',
                'title'   => __( 'Plugin Visibility' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module allows you to set user roles/capability to gain access on the plugin's admin interfaces (Affiliate Links, Reports, Settings, etc.)" , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'yes'
            ),
            array(
                'id'      => 'tap_enable_affiliate_disclosure',
                'title'   => __( 'Affiliate Disclosure' , 'thirstyaffiliates-pro' ),
                'desc'    => __( "This module allows you to add disclosure messages beside your affiliate links and/or at the bottom of every post that has affiliate links on it making light work of keeping your site compliant with FTC guidelines." , 'thirstyaffiliates-pro' ),
                'type'    => 'toggle',
                'default' => 'no'
            )
        );

        return array_merge( $modules , $tap_modules );

    }



    /**
     * Register ThirstyAffiliates Pro modules.
     *
     * @since 1.0.0
     * @access public
     *
     * @param mixed $option Settings API single option value.
     * @return mixed Settings API single option.
     */
    public function handle_maxmind_db_upload( $option ) {

        if( ! isset( $_FILES[ 'tap_geolocations_maxmind_mmdb_file' ] ) || empty( $_FILES[ 'tap_geolocations_maxmind_mmdb_file' ][ 'tmp_name' ] ) )
            return $option;

        $file = wp_handle_upload( $_FILES[ 'tap_geolocations_maxmind_mmdb_file' ] , array( 'test_form' => false ) );

        if ( isset( $file[ 'url' ] ) )
            return $file[ 'url' ];

        return $option;
    }

    /**
     * Register MMDB mime type to WordPress to allow upload of .mmdb files.
     *
     * @since 1.0.0
     * @access public
     *
     * @param mixed $option Settings API single option value.
     * @return mixed Settings API single option.
     */
    public function mmdb_mime_type( $mime_types ) {

        $mime_types[ 'mmdb' ] = 'application/octet-stream';

        return $mime_types;
    }

    /**
     * Display recreate htaccess warning notice.
     *
     * @since 1.3.0
     * @access public
     */
    public function dispay_recreate_htaccess_warning_notice() {

        $option    = get_option( 'tap_show_htaccess_warning_on_setting_save' );
        $post_type = get_post_type();

        if ( !$post_type && isset( $_GET[ 'post_type' ] ) )
            $post_type = $_GET[ 'post_type' ];

        if ( ! $option || ! is_admin() || ! current_user_can( 'manage_options' ) || $post_type !== Plugin_Constants::AFFILIATE_LINKS_CPT || get_option( 'tap_enable_htaccess' ) != 'yes' ) return;

        switch ( $option ) {

            case 'ta_show_cat_in_slug' :
                $option_nice_name = __( 'Link Category in URL?' , 'thirstyaffiliates-pro' );
                break;

            case 'ta_link_prefix_custom' :
                $option_nice_name = __( 'Custom Link Prefix' , 'thirstyaffiliates-pro' );
                break;

            case 'ta_link_redirect_type' :
                $option_nice_name = __( 'Link Redirect Type' , 'thirstyaffiliates-pro' );
                break;

            case 'ta_link_prefix' :
            default :
                $option_nice_name = __( 'Link Prefix' , 'thirstyaffiliates-pro' );
                break;
        }

        ?>
        <div id="recreate-htaccess-warning-notice" class="notice notice-warning is-dismissible">
            <p><?php echo sprintf( __( "You've recently changed the <strong>%s</strong> link appearance setting for ThirstyAffiliates while the htaccess module is turned on. We highly recommend that you regenerate your htaccess entries in the <strong>Help</strong> section." , 'thirstyaffiliates-pro' ) , $option_nice_name ); ?></p>
            <p>
                <a class="button-primary" href="<?php echo admin_url( 'edit.php?post_type=thirstylink&page=thirsty-settings&tab=ta_help_settings&scrollto=htaccess_reload_all_links' ); ?>">
                    <?php _e( 'Navigate to Help Settings and Recreate htaccess entries' , 'thirstyaffiliates-pro' ); ?>
                </a>
            </p>
            <script type="text/javascript">
            jQuery( document ).ready(function($){

                $( '#recreate-htaccess-warning-notice' ).on( 'click' , '.notice-dismiss' , function() {
                    $.ajax( ajaxurl , {
                        type: 'POST',
                        data: { action: 'ta_dismiss_recreate_htaccess_warning_notice' }
                    } );
                } );

                <?php if ( isset( $_GET[ 'scrollto' ] ) && $_GET[ 'scrollto' ] == 'htaccess_reload_all_links' ) : ?>
                    (function() {
                        var element = document.querySelector( "#tap_htaccess_reload_all_links" );

                        if ( element ) {
                            window.scroll({ top: jQuery(element).offset().top - 50 , behavior : 'smooth' })
                            jQuery( element ).closest( 'tr' ).addClass( 'highlight-row' );
                        }
                    })();
                <?php endif; ?>
            });
            </script>
        </div>
        <?php
    }

    public function restrict_current_module( $current_module ) {

        if ( $current_module == 'tap_enable_geolocations' )
            return 'tap_enable_geolocation';

        return $current_module;
    }




    /*
    |--------------------------------------------------------------------------
    | AJAX functions
    |--------------------------------------------------------------------------
    */

    /**
     * AJAX fix all links.
     *
     * @since 1.1.3
     * @access public
     */
    public function ajax_fix_all_links() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! current_user_can( 'administrator' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this action.' , 'thirstyaffiliates-pro' ) );
        else {

            // get all published affiliate link ids
            $query = new \WP_Query( array(
                'post_type'      => Plugin_Constants::AFFILIATE_LINKS_CPT,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => -1
            ) );

            if ( is_array( $query->posts ) && ! empty( $query->posts ) )
                do_action( 'tap_fix_all_links' , $query->posts );

            $message  = __( '<strong>All existing links have now been fixed.</strong>' , 'thirstyaffiliates-pro' );
            $response = array( 'status' => 'success' , 'message' => $message );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX Dismiss recreate htaccess after setting save warning.
     *
     * @since 1.3.0
     * @access public
     */
    public function ajax_dismiss_recreate_htaccess_warning_notice() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates' ) );
        else
            $response = delete_option( 'tap_show_htaccess_warning_on_setting_save' );

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX view debug log.
     *
     * @since 1.2.0
     * @access public
     */
    public function ajax_view_debug_log() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! current_user_can( 'administrator' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this action.' , 'thirstyaffiliates-pro' ) );
        else {

            $log_file     = $_POST[ 'log' ] === 'htaccess' ? 'htaccess-debug.log' : 'debug.log';
            $log_contents = @file_get_contents( $this->_constants->LOGS_ROOT_PATH() . $log_file );

            $markup  = '<h2>' . $log_file . '</h2>';
            $markup .= '<div class="log-wrap"><pre>';
            $markup .= $log_contents ? esc_html( $log_contents ) : __( 'No logs to show' , 'thirstyaffiliate-pro' );
            $markup .= '</pre></div>';

            $response = array(
                'status' => 'success',
                'markup' => $markup
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
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.1.3
     * @access public
     * @inherit ThirstyAffiliates_Pro\Interfaces\Initiable_Interface
     */
    public function initialize() {

        add_action( 'wp_ajax_tap_get_debug_log' , array( $this , 'ajax_view_debug_log' ) );
        add_action( 'wp_ajax_tap_fix_all_links' , array( $this , 'ajax_fix_all_links' ) , 10 );
        add_action( 'wp_ajax_ta_dismiss_recreate_htaccess_warning_notice' , array( $this , 'ajax_dismiss_recreate_htaccess_warning_notice' ) );
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

        // Register your custom field types here
        // It is advisable to register them separately, not registering all your custom field types in one function ( S in SOLID principle )
        add_filter( 'ta_supported_field_types' , array( $this , 'register_autolinker_rebuild_cache_field' ) );
        add_filter( 'ta_supported_field_types' , array( $this , 'register_maxmind_db_upload_field' ) );
        add_filter( 'ta_supported_field_types' , array( $this , 'register_htaccess_record_all_links_field' ) );
        add_filter( 'ta_supported_field_types' , array( $this , 'register_view_debug_log_field' ) );
        add_filter( 'ta_supported_field_types' , array( $this , 'register_fix_all_links_field' ) );
        add_filter( 'ta_supported_field_types' , array( $this , 'register_trigger_link_health_checker_field' ) );

        // Register your custom settings section here
        add_filter( 'ta_settings_option_sections' , array( $this , 'register_tap_settings_section' ) );
        add_filter( 'ta_settings_option_sections' , array( $this , 'reorder_tap_settings_section') , 90 );

        // Register the set of options to the new custom settings section you've added above
        // It is advisable to register them separately, not registering all your custom settings sections in one function ( S in SOLID principle )
        add_filter( 'ta_settings_section_options' , array( $this , 'register_autolinker_settings_options' ) );
        add_filter( 'ta_settings_section_options' , array( $this , 'register_geolocations_settings_options' ) );
        add_filter( 'ta_settings_section_options' , array( $this , 'register_google_click_tracking_settings_options' ) );
        add_filter( 'ta_settings_section_options' , array( $this , 'register_link_health_checker_settings_options' ) );
        add_filter( 'ta_settings_section_options' , array( $this , 'register_url_shortener_settings_options' ) );
        add_filter( 'ta_settings_section_options' , array( $this , 'register_link_scheduler_settings_options' ) );
        add_filter( 'ta_settings_section_options' , array( $this , 'register_plugin_visibility_settings_options' ) );
        add_filter( 'ta_settings_section_options' , array( $this , 'register_affiliate_disclosure_settings_options' ) );

        // You can also extend the list of options from an existing settings section
        // Ex. use the 'tap_general_settings_section_options' filter to extend the list of options for the 'General' settings section

        add_filter( 'ta_modules_settings_options' , array( $this , 'register_tap_modules' ) );

        add_filter( 'ta_help_settings_options' , array( $this , 'register_geolocation_ip_address_debug_option' ) );
        add_filter( 'ta_help_settings_options' , array( $this , 'register_htaccess_reload_all_links_option' ) );
        add_filter( 'ta_help_settings_options' , array( $this , 'register_view_debug_log_option' ) );
        add_filter( 'ta_help_settings_options' , array( $this , 'register_fix_all_links_option' ) );
        add_filter( 'upload_mimes' , array( $this , 'mmdb_mime_type' ) , 1 , 1 );

        add_action( 'admin_notices' , array( $this , 'dispay_recreate_htaccess_warning_notice' ) );

        add_filter( 'ta_settings_admin_interface' , array( $this , 'register_settings_admin_interfaces' ) );
        add_filter( 'ta_restrict_current_module' , array( $this , 'restrict_current_module' ) );
    }

}
