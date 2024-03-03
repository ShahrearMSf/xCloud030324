<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Plugin_Visibility module.
 *
 * @since 1.4.0
 */
class Plugin_Visibility implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Plugin_Visibility.
     *
     * @since 1.4.0
     * @access private
     * @var Settings_Extension
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @since 1.4.0
     * @access private
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @since 1.4.0
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
     * @since 1.4.0
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
     * @since 1.4.0
     * @access public
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     * @return Plugin_Visibility
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }




    /*
    |--------------------------------------------------------------------------
    | Code Implementation
    |--------------------------------------------------------------------------
    */

    /**
     * Update interface capability.
     *
     * @since 1.4.0
     * @access private
     *
     * @param array $interfaces List of registered admin interfaces.
     * @param array $option     Saved interfaces option data.
     * @param array $const      Input name to interface id reference.
     * @return array Filtered list of registered admin interfaces.
     */
    private function _update_interface_capability( $interfaces , $option , $const ) {

        if ( ! is_array( $option ) || empty( $option ) )
            return $interfaces;

        foreach ( $option as $name => $capability ) {

            if ( ! isset( $const[ $name ] ) ) continue;

            $key = $const[ $name ];

            if ( ! $key || ! isset( $interfaces[ $key ] ) ) continue;

            $interfaces[ $key ] = $capability;
        }

        return $interfaces;
    }

    /**
     * Implement admin interfaces plugin visibilty.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of registered admin interfaces.
     * @return array Filtered list of registered admin interfaces.
     */
    public function implement_admin_interface_plugin_visibility( $interfaces ) {

        $option = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_plugin_visibility_admin_interfaces' , array() );
        $const  = apply_filters( 'tap_admin_interface_plugin_visibility_key_reference' , array(
            'thirstylink_list'    => 'edit-thirstylink',
            'thirstylink_edit'    => 'thirstylink',
            'event_notifications' => 'edit-tap-event-notification',
            'link_categories'     => 'edit-thirstylink-category',
            'amazon'              => 'thirstylink_page_amazon'
        ) );

        return $this->_update_interface_capability( $interfaces , $option , $const );
    }

    /**
     * Implement report interfaces plugin visibilty.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of registered admin interfaces.
     * @return array Filtered list of registered admin interfaces.
     */
    public function implement_report_interface_plugin_visibility( $interfaces ) {

        $report_interfaces = isset( $interfaces[ 'thirstylink_page_thirsty-reports' ] ) ? $interfaces[ 'thirstylink_page_thirsty-reports' ] : array();
        $option            = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_plugin_visibility_report_interfaces' , array() );
        $const             = apply_filters( 'tap_report_interface_plugin_visibility_key_reference' , array(
            'link_performance' => 'link_performance',
            'geolocation'      => 'geolocation',
            'stats_table'      => 'stats_table',
            'keyword'          => 'keyword_report',
            'link_health'      => 'link_health_report',
        ) );

        $interfaces[ 'thirstylink_page_thirsty-reports' ] = $this->_update_interface_capability( $report_interfaces , $option , $const );
        return $interfaces;
    }

    /**
     * Implement report interfaces plugin visibilty.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of registered admin interfaces.
     * @return array Filtered list of registered admin interfaces.
     */
    public function implement_setting_interface_plugin_visibility( $interfaces ) {

        $setting_interfaces = isset( $interfaces[ 'thirstylink_page_thirsty-settings' ] ) ? $interfaces[ 'thirstylink_page_thirsty-settings' ] : array();
        $option             = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_plugin_visibility_setting_interfaces' , array() );
        $const              = apply_filters( 'tap_setting_interface_plugin_visibility_key_reference' , array(
            'general'             => 'ta_general_settings',
            'links'               => 'ta_links_settings',
            'modules'             => 'ta_modules_settings',
            'amazon'              => 'tap_amazon_settings_section',
            'autolinker'          => 'tap_autolinker_settings',
            'geolocations'        => 'tap_geolocations_settings',
            'click_tracking'      => 'tap_google_click_tracking_settings',
            'link_health_checker' => 'tap_link_health_checker_settings',
            'url_shortener'       => 'tap_url_shortener_settings',
            'link_scheduler'      => 'tap_link_scheduler_settings',
            'help'                => 'ta_help_settings'
        ) );

        $interfaces[ 'thirstylink_page_thirsty-settings' ] = $this->_update_interface_capability( $setting_interfaces , $option , $const );
        return $interfaces;
    }

    /**
     * Implement admin menu items plugin visibilty.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $interfaces List of registered admin menu items.
     * @return array Filtered list of registered admin menu items.
     */
    public function implement_admin_menu_items_plugin_visibility( $menu_items ) {

        $option = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_plugin_visibility_admin_interfaces' , array() );
        $const  = apply_filters( 'tap_admin_menu_items_plugin_visibility_key_reference' , array(
            'thirstylink_list'    => 'edit.php?post_type=thirstylink',
            'thirstylink_edit'    => 'post-new.php?post_type=thirstylink',
            'event_notifications' => 'edit-tags.php?taxonomy=tap-event-notification&post_type=thirstylink',
            'link_categories'     => 'edit-tags.php?taxonomy=thirstylink-category&post_type=thirstylink',
            'amazon'              => 'amazon',
            'settings'            => 'thirsty-settings',
            'reports'             => 'thirsty-reports',
            'import_csv'          => 'thirsty_csv_import',
            'export_csv'          => 'thirsty_csv_export'
        ) );

        return $this->_update_interface_capability( $menu_items , $option , $const );
    }

    /**
     * Reports menu visibility implementation.
     *
     * @since 1.4.0
     *
     * @param array $reports Registered reports in TA.
     * @return array Filtered registered reports in TA.
     */
    public function reports_menu_visibility( $reports ) {

        $filtered_reports   = array();
        $interface_id       = 'thirstylink_page_thirsty-reports';
        $interfaces         = apply_filters( 'ta_admin_interfaces' , array() );
        $reports_interfaces = isset( $interfaces[ $interface_id ] ) ? $interfaces[ $interface_id ] : array();

        if ( is_array( $reports_interfaces ) && ! empty( $reports_interfaces ) ) {

            foreach ( $reports as $key => $setting ) {

                $capability = isset( $reports_interfaces[ $key ] ) ? $reports_interfaces[ $key ] : 'manage_options';
                if ( current_user_can( $capability ) )
                    $filtered_reports[ $key ] = $setting;
            }
        }

        return $filtered_reports;
    }

    /**
     * Settings menu visibility implementation.
     *
     * @since 1.4.0
     *
     * @param array $reports Registered settings in TA.
     * @return array Filtered registered settings in TA.
     */
    public function settings_menu_visibility( $settings ) {

        $filtered_settings   = array();
        $interface_id        = 'thirstylink_page_thirsty-settings';
        $interfaces          = apply_filters( 'ta_admin_interfaces' , array() );
        $settings_interfaces = isset( $interfaces[ $interface_id ] ) ? $interfaces[ $interface_id ] : array();

        if ( is_array( $settings_interfaces ) && ! empty( $settings_interfaces ) ) {

            foreach ( $settings as $key => $setting ) {

                $capability = isset( $settings_interfaces[ $key ] ) ? $settings_interfaces[ $key ] : 'manage_options';
                if ( current_user_can( $capability ) )
                    $filtered_settings[ $key ] = $setting;
            }

            // For users without the capability 'manage_options' we need to add a filter to allow them to save the settings
            if ( ! current_user_can( 'manage_options' ) ) {

                foreach ( $settings_interfaces as $settings_page => $settings_page_capability ) {

                    if ( current_user_can( $settings_page_capability ) ) {
                        add_filter( "option_page_capability_{$settings_page}_options_group" , function () use ( $settings_page_capability ) {
                            return $settings_page_capability;
                        });
                    }

                }

            }

        }

        return $filtered_settings;
    }

    /**
     * Hide add new affiliates link button.
     *
     * @since 1.4
     * @access public
     */
    public function hide_new_affiliates_link_button() {

        $new_post_slug = 'post-new.php?post_type=' . Plugin_Constants::AFFILIATE_LINKS_CPT;
        $menu_items    = apply_filters( 'ta_menu_items' , array() );
        $capability    = isset( $menu_items[ $new_post_slug ] ) && $new_post_slug ? $menu_items[ $new_post_slug ] : 'edit_posts';

        $post_type = get_post_type();
        if ( !$post_type && isset( $_GET[ 'post_type' ] ) )
            $post_type = $_GET[ 'post_type' ];

        if ( $capability && ! current_user_can( $capability ) && $post_type === Plugin_Constants::AFFILIATE_LINKS_CPT ) {
            echo '
            <script type="text/javascript">
                (function($){
                    jQuery(".wrap > .page-title-action,li#wp-admin-bar-new-' . Plugin_Constants::AFFILIATE_LINKS_CPT .'").remove();
                })(jQuery);
            </script>
            ';
        }
    }




    /*
    |--------------------------------------------------------------------------
    | Settings field display related functions.
    |--------------------------------------------------------------------------
    */

    /**
     * Get roles and capabilit options.
     *
     * @since 1.4.0
     * @access private
     *
     * @return array List of capability options.
     */
    private function _get_capability_options( $is_tax = false ) {

        if ( $is_tax ) {
            $capabilities = array(
                'manage_options'       => sprintf( __( '(%s) At least an Administrator' , 'thirstyaffiliates-pro' ) , 'manage_options' ),
                'manage_categories'    => sprintf( __( '(%s) At least an Editor (terms)' , 'thirstyaffiliates-pro' ) , 'manage_categories' ),

            );
        } else {
            $capabilities = array(
                'manage_options'       => sprintf( __( '(%s) At least an Administrator' , 'thirstyaffiliates-pro' ) , 'manage_options' ),
                'edit_others_posts'    => sprintf( __( '(%s) At least an Editor (posts)' , 'thirstyaffiliates-pro' ) , 'edit_others_posts' ),
                'edit_published_posts' => sprintf( __( '(%s) At least an Author' , 'thirstyaffiliates-pro' ) , 'edit_published_posts' ),
                'edit_posts'           => sprintf( __( '(%s) At least a Contributor' , 'thirstyaffiliates-pro' ) , 'edit_posts' )
            );
        }

        return apply_filters( 'tap_plugin_visibility_capability_options' , $capabilities , $is_tax );
    }

    /**
     * Get menu item capability select options.
     *
     * @since 1.4.0
     * @access private
     *
     * @param string $key  Menu item name.
     * @param string $name Menu field name.
     */
    private function _render_capability_select_options_field( $key , $name , $value = null ) {

        $is_tax       = in_array( $key , array( 'edit-tap-event-notification' , 'edit-thirstylink-category' ) );
        $options      = $this->_get_capability_options( $is_tax );
        $capabilities = array_keys( $options );
        $custom       = ! in_array( $value , $capabilities ) ? $value : '';

        ?>
        <select id="capability-<?php echo $name; ?>" name="data[<?php echo $name; ?>]" placeholder="<?php _e( 'Select role or capability' , 'thirstyaffiliates-pro' ); ?>">
            <?php foreach ( $options as $item_key => $label ) : ?>
                <option value="<?php echo $item_key ?>" <?php selected( $value , $item_key ); ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
            <option value="custom" <?php echo $custom ? 'selected' : ''; ?>><?php _e( 'Custom capability' , 'thirstyaffilites-pro' ); ?></option>
        </select>
        <input id="capability-<?php echo $name; ?>-custom" class="custom-capability" type="text" name="data[<?php echo $name; ?>]" value="<?php echo $custom; ?>" disabled>
        <?php
    }

    /**
     * Register menu items visibility setings field type.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $option Array of options data. May vary depending on option type.
     */
    public function register_plugin_visibility_setting_option_field( $supported_field_types ) {

        if ( array_key_exists( 'plugin_visibility' , $supported_field_types ) )
            return $supported_field_types;

        $supported_field_types[ 'plugin_visibility' ] = function( $field_data ) {

            // This function will be the render callback of this new custom field type
            // It will expect 1 parameter to be passed by our Settings API, and that is the $option data

            $options = isset( $field_data[ 'options' ] ) ? $field_data[ 'options' ] : array();
            $data    = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( $field_data[ 'id' ] , array() );
            $desc    = isset( $field_data[ 'desc' ] ) ? sanitize_text_field( $field_data[ 'desc' ] ) : '';
            $items   = isset( $field_data[ 'item_data' ] ) ? $field_data[ 'item_data' ] : array(); ?>

            <tr valign="top" class="<?php echo esc_attr( $field_data[ 'id' ] ) . '-row'; ?>">
                <th scope="row">
                    <?php echo sanitize_text_field( $field_data[ 'title' ] ); ?>
                    <p class="description"><?php echo $desc; ?></p>
                </th>
                <td>
                    <ul id="<?php echo esc_attr( $field_data[ 'id' ] ); ?>" class="menu-item-visibility-setting">
                        <?php foreach ( $items as $key => $item ) : ?>
                            <li class="menu-item">
                                <div class="label"><?php echo $item[ 'label' ]; ?></div>
                                <div class="option">
                                    <?php
                                        $name    = isset( $item[ 'name' ] ) ? $item[ 'name' ] : '';
                                        $default = isset( $options[ $key ] ) ? $options[ $key ] : 'manage_options';
                                        $value   = isset( $data[ $name ] ) && $data[ $name ] ? $data[ $name ] : $default;
                                        $this->_render_capability_select_options_field( $key , $item[ 'name' ] , $value );
                                    ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <li class="save-row">
                            <button type="button" class="button-primary"><?php _e( 'Save Changes' , 'thirstyaffiliates-pro' ); ?></button>
                            <input type="hidden" name="action" value="tap_save_plugin_visibility">
                            <input type="hidden" name="id" value="<?php echo esc_attr( $field_data[ 'id' ] ); ?>">
                            <?php wp_nonce_field( 'tap_save_plugin_visibility' ); ?>

                            <span class="processing">
                                <img src="<?php echo $this->_constants->IMAGES_ROOT_URL() . 'spinner.gif'; ?>">
                                <?php _e( 'Please wait...' , 'thirstyaffiliates-pro' ); ?>
                            </span>
                        </li>
                    </ul>
                </td>

                <script>
                    jQuery( document ).ready( function( $ ) {

                        $( '#<?php echo esc_attr( $field_data[ 'id' ] ); ?> select' ).selectize({
                            plugins : [ 'remove_button' ],
                            searchField : 'text'
                        });

                        $( '#<?php echo esc_attr( $field_data[ 'id' ] ); ?>' ).on( 'change' , 'select' , function() {

                            var $select = $(this),
                                $parent = $select.closest( '.option' ),
                                $input  = $parent.find( '.custom-capability' ),
                                value   = $select.val();

                            if ( value === 'custom' ) {
                                $input.prop( 'disabled' , false ).show();
                            } else {
                                $input.prop( 'disabled' , true ).hide();
                            }

                        } );
                        $( '#<?php echo esc_attr( $field_data[ 'id' ] ); ?> select' ).trigger( 'change' );

                        $( '#<?php echo esc_attr( $field_data[ 'id' ] ); ?>' ).on( 'click' , '.save-row button' , function() {

                            var $button  = $(this),
                                $form    = $button.closest( 'ul.menu-item-visibility-setting' ),
                                $spinner = $form.find( 'li.save-row .processing' ),
                                formData = $form.find( 'input,select' ).serializeArray(),
                                invalid  = false;

                            $form.find( '.option' ).removeClass( '.form-error' );

                            // validate form.
                            for ( x in formData ) {

                                if ( ! formData[x].value ) {
                                    $( '*[name="' + formData[x].name + '"]' ).closest( '.option' ).addClass( 'form-error' );
                                    invalid = true;
                                }
                            }
                            if ( invalid ) {
                                alert( tap_settings_params.i18n_invalid_form_data );
                                return;
                            }

                            $spinner.css( 'display' , 'inline-block' );
                            $button.prop( 'disabled' , true );

                            $.post( ajaxurl , formData , function( response ) {

                                if ( response.status == 'success' ) {

                                    markup = '<p class="success-msg">' + response.message + '</p>';
                                    $form.find( 'li.save-row' ).append( markup );

                                    setTimeout( function() {
                                        $form.find( 'li.save-row .success-msg' ).fadeOut( 'fast' );
                                        $form.find( 'li.save-row .success-msg' ).remove();
                                    } , 8000 );

                                } else {
                                    // TODO: change to vex
                                    alert( response.error_msg );
                                }

                                $spinner.hide();
                                $button.prop( 'disabled' , false );

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
     * AJAX save plugin visibility menu items.
     *
     * @since 1.4.0
     * @access public
     */
    public function ajax_save_plugin_visibility() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'tap_save_plugin_visibility' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'id' ] ) || ! isset( $_POST[ 'data' ] ) || ! is_array( $_POST[ 'data' ] ) || empty( $_POST[ 'data' ] ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required post data' , 'thirstyaffiliates-pro' ) );
        else {

            $id    = sanitize_text_field( $_POST[ 'id' ] );
            $data  = array_map( 'sanitize_text_field' , $_POST[ 'data' ] );
            $valid = array(
                'tap_plugin_visibility_admin_interfaces',
                'tap_plugin_visibility_report_interfaces',
                'tap_plugin_visibility_setting_interfaces',
                'tap_plugin_visibility_menu_items'
            );

            // make sure id sent is valid.
            if ( in_array( $id , $valid ) )
                $check = update_option( $id , $data );

            if ( $check ) {
                $response = array(
                    'status'  => 'success',
                    'message' => __( 'Admin menu items visibilty saved successfully.' , 'thirstyaffiliates-pro' )
                );
            } else
                $response = array( 'status' => 'fail' , 'error_msg' => __( 'No changes to be saved.' , 'thirstyaffiliates-pro' ) );

        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Remove save changes button on bottom of plugin visibility settings page.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array $tabs List of settings tabs.
     * @return array Filtered list of settings tabs.
     */
    public function remove_setting_tab_save_changes_button( $tabs ) {

        $tabs[] = 'tap_plugin_visibility_settings';
        return $tabs;
    }

    /**
     * Add new affiliate link interface visibility.
     *
     * @since 1.4.0
     * @access public
     */
    public function add_new_affiliate_link_interface_visibility() {

        $screen = get_current_screen();

        if ( $screen->id !== 'thirstylink' || $screen->action !== 'add' || $screen->base !== 'post' ) return;

        $screen_id  = 'thirstylink';
        $interfaces = apply_filters( 'ta_admin_interfaces' , array() );
        $capability = $screen_id &&  isset( $interfaces[ $screen_id ] ) ? $interfaces[ $screen_id ] : null;

        if ( $capability && ! current_user_can( $capability ) )
            wp_die( __( "Sorry, you are not allowed to access this page." , 'thirstyaffiliates-pro' ) );
    }




    /*
    |--------------------------------------------------------------------------
    | Post type capabilities restriction implementation.
    |--------------------------------------------------------------------------
    */

    /**
     * Update post type capabilities.
     *
     * @since 1.5
     * @access public
     *
     * @param array $args Post type arguments.
     * @return array Filtered post type arguments.
     */
    public function update_post_type_capabilities( $args ) {

        $args[ 'capabilities' ] = array(
            'edit_post'          => 'edit_thirstylink',
            'edit_posts'         => 'edit_thirstylinks',
            'edit_others_posts'  => 'edit_other_thirstylinks',
            'publish_posts'      => 'publish_thirstylinks',
            'read_post'          => 'read_thirstylink',
            'read_private_posts' => 'read_private_thirstylinks',
            'delete_post'        => 'delete_thirstylink'
        );

        $args[ 'map_meta_cap' ] = true;

        return $args;
    }

    /**
     * Add post type capabilities to roles.
     *
     * @since 1.5
     * @access public
     *
     * @param array $option_value Admin interfaces visibility option value.
     * @return array Filtered Admin interfaces visibility option value.
     */
    public function add_post_type_capabilities_to_roles( $option_value ) {

        $this->_reset_roles_post_type_capabilities();

        $view_capability = 'edit_posts'; // we set roles from at least contributor to view affiliate links list. restrictions will be handled by plugin visibilty instead.
        $edit_capability = isset( $option_value[ 'thirstylink_edit' ] ) ? $option_value[ 'thirstylink_edit' ] : 'edit_others_posts';

        $this->_add_view_post_type_capabilities_to_roles( $view_capability );
        $this->_add_edit_post_type_capabilities_to_roles( $edit_capability );

        return $option_value;
    }

    /**
     * Add view post type capabilities to roles.
     *
     * @since 1.5
     * @access private
     *
     * @param string $capability User capability.
     */
    private function _add_view_post_type_capabilities_to_roles( $capability = 'edit_posts' ) {

        $view_capable_roles = $this->_get_roles_that_can( $capability );

        foreach ( $view_capable_roles as $role_object ) {

            $role_object->add_cap( 'edit_thirstylink' );
            $role_object->add_cap( 'edit_thirstylinks' );
            $role_object->add_cap( 'read_thirstylink' );
            $role_object->add_cap( 'read_private_thirstylinks' );
            $role_object->add_cap( 'publish_thirstylinks' );
        }
    }

    /**
     * Add edit post type capabilities to roles.
     *
     * @since 1.5
     * @access private
     *
     * @param string $capability User capability.
     */
    private function _add_edit_post_type_capabilities_to_roles( $capability = 'edit_others_posts' ) {

        $edit_capable_roles = $this->_get_roles_that_can( $capability );

        foreach( $edit_capable_roles as $role_object ) {

            $role_object->add_cap( 'edit_other_thirstylinks' );
            $role_object->add_cap( 'delete_thirstylink' );
        }
    }

    /**
     * Reset roles post type capabilities.
     *
     * @since 1.5
     * @access private
     */
    private function _reset_roles_post_type_capabilities() {

        global $wp_roles;

        if ( !isset( $wp_roles ) ) $wp_roles = new WP_Roles();

        foreach ( $wp_roles->role_objects as $role_object ) {

            $role_object->remove_cap( 'edit_thirstylink' );
            $role_object->remove_cap( 'edit_thirstylinks' );
            $role_object->remove_cap( 'edit_other_thirstylinks' );
            $role_object->remove_cap( 'publish_thirstylinks' );
            $role_object->remove_cap( 'read_thirstylink' );
            $role_object->remove_cap( 'read_private_thirstylinks' );
            $role_object->remove_cap( 'delete_thirstylink' );
        }
    }

    /**
     * Get user roles that has a certain capability.
     *
     * @since 1.5
     * @access private
     *
     * @param string $capability User capability.
     * @param array List of capable roles.
     */
    private function _get_roles_that_can( $capability ) {

        global $wp_roles;

        if ( !isset( $wp_roles ) ) $wp_roles = new WP_Roles();

        $available_roles = $wp_roles->get_names();
        $capable_roles   = array();

        foreach ( $wp_roles->role_objects as $role_key => $role_object ) {

            $role_caps = $role_object->capabilities;

            if ( isset( $role_caps[ $capability ] ) && $role_caps[ $capability ] == 1 )
                $capable_roles[ $role_key ] = $role_object;
        }

        return $capable_roles;
    }

    /**
     * Initialize thirstylink post type capabilities.
     *
     * @since 1.5
     * @access private
     */
    private function _initialize_post_type_capabilities() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_init_thirstylink_post_type-capabilities' , 'no' ) === 'yes' )
            return;

        $option = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_option( 'tap_plugin_visibility_admin_interfaces' , array() );

        if ( ! is_array( $option ) || empty( $option ) ) {

            $this->_add_view_post_type_capabilities_to_roles();
            $this->_add_edit_post_type_capabilities_to_roles();

        } else
            update_option( 'tap_plugin_visibility_admin_interfaces' , $option );

        update_option( 'tap_init_thirstylink_post_type-capabilities' , 'yes' );
    }

    /**
     * Post type list row actions visibility implementation.
     *
     * @since 1.5
     *
     * @param array   $actions List of row actions.
     * @param WP_Post $post    Row item post object.
     * @return array Filtered list of row actions.
     */
    public function thirstylink_list_row_actions_visibility( $actions , $post ) {

        if ( $post->post_type === Plugin_Constants::AFFILIATE_LINKS_CPT && (
             ! ( get_current_user_id() == get_post_field( 'post_author' , $post ) || current_user_can( 'edit_others_posts' ) )
             || ! current_user_can( 'edit_other_thirstylinks' )
           ) ) {

            unset( $actions[ 'delete_post' ] );
            unset( $actions[ 'untrash' ] );
            unset( $actions[ 'trash' ] );
            unset( $actions[ 'edit' ] );
            unset( $actions[ 'inline hide-if-no-js' ] );

        }

        return $actions;
    }

    /**
     * Post tyle list title link visibility implementation.
     *
     * @since 1.5
     *
     * @param bool[]   $allcaps Array of key/value pairs where keys represent a capability name and boolean values. represent whether the user has that capability.
     * @param string[] $caps    Required primitive capabilities for the requested capability.
     * @param array    $args    Arguments that accompany the requested capability check.
     */
    public function thirstylink_title_link_visibility( $allcaps , $caps , $args ) {

        // $args[2] contains the post/object ID while $caps[0] contains the require capability. when condition is true, we remove the required capability from $allcaps to restrict it.
        if ( isset( $args[2] )
             && isset( $caps[0] )
             && get_post_type( $args[2] ) === Plugin_Constants::AFFILIATE_LINKS_CPT
             && ( ! ( get_current_user_id() == get_post_field( 'post_author' , $args[2] ) || current_user_can( 'edit_others_posts' ) )
                  || ! current_user_can( 'edit_other_thirstylinks' )
                ) )
            unset( $allcaps[ $caps[0] ] );

        return $allcaps;
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
        if ( get_option( 'tap_enable_plugin_visibility' , 'yes' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_tap_save_plugin_visibility' , array( $this , 'ajax_save_plugin_visibility' ) );

        $this->_initialize_post_type_capabilities();
    }

    /**
     * Execute model.
     *
     * @implements ThirstyAffiliates_Pro\Interfaces\Model_Interface
     *
     * @since 1.4.0
     * @access public
     */
    public function run() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_plugin_visibility' , 'yes' ) !== 'yes' )
            return;

        add_filter( 'ta_supported_field_types' , array( $this , 'register_plugin_visibility_setting_option_field' ) );
        add_filter( 'ta_render_settings_no_save_section' , array( $this , 'remove_setting_tab_save_changes_button' ) );

        add_filter( 'ta_admin_interfaces' , array( $this , 'implement_admin_interface_plugin_visibility' ) , 30 );
        add_filter( 'ta_admin_interfaces' , array( $this , 'implement_report_interface_plugin_visibility' ) , 30 );
        add_filter( 'ta_admin_interfaces' , array( $this , 'implement_setting_interface_plugin_visibility' ) , 30 );
        add_filter( 'ta_menu_items' , array( $this , 'implement_admin_menu_items_plugin_visibility' ) , 30 );

        add_filter( 'ta_register_reports' , array( $this , 'reports_menu_visibility' ) , 99 );
        add_filter( 'ta_settings_option_sections' , array( $this , 'settings_menu_visibility' ) , 99 );

        add_action( 'admin_footer' , array( $this , 'hide_new_affiliates_link_button' ) );
        add_action( 'current_screen' , array( $this , 'add_new_affiliate_link_interface_visibility' ) , 20 );

        add_action( 'ta_affiliate_links_cpt_args' , array( $this , 'update_post_type_capabilities' ) );
        add_action( 'pre_update_option_tap_plugin_visibility_admin_interfaces' , array( $this , 'add_post_type_capabilities_to_roles' ) );

        add_action( 'page_row_actions' , array( $this , 'thirstylink_list_row_actions_visibility' ) , 10 , 2 );
        add_action( 'post_row_actions' , array( $this , 'thirstylink_list_row_actions_visibility' ) , 10 , 2 );
        add_action( 'user_has_cap' , array( $this , 'thirstylink_title_link_visibility' ) , 10 , 3 );

    }

}
