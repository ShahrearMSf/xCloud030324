<?php
/**
Plugin Name: ThirstyAffiliates Pro - Basic
Plugin URI: https://thirstyaffiliates.com
Description: The high powered add-on to the free ThirstyAffiliates plugin that will turbo charge your affiliate marketing.
Version: 1.7.14
Author: Caseproof
Author URI: https://caseproof.com/
Requires at least: 5.0
Tested up to: 5.9
Text Domain: thirstyaffiliates-pro
Domain Path: /i18n/

@package ThirstyAffiliates_Pro
@category Core
@author Rymera Web Co
*/

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

use ThirstyAffiliates_Pro\Models\Bootstrap;
use ThirstyAffiliates_Pro\Models\Script_Loader;
use ThirstyAffiliates_Pro\Models\Plugin_Visibility;
use ThirstyAffiliates_Pro\Models\Affiliate_Link_Extend;
use ThirstyAffiliates_Pro\Models\Autolinker;
use ThirstyAffiliates_Pro\Models\Geolocation;
use ThirstyAffiliates_Pro\Models\CSV_Import_Export;
use ThirstyAffiliates_Pro\Models\Advanced_Reporting;
use ThirstyAffiliates_Pro\Models\Third_Party_Integrations\Amazon\Amazon;
use ThirstyAffiliates_Pro\Models\Third_Party_Integrations\Amazon\Amazon_Settings;
use ThirstyAffiliates_Pro\Models\Third_Party_Integrations\Google\Google_Click_Tracking;
use ThirstyAffiliates_Pro\Models\Event_Notification;
use ThirstyAffiliates_Pro\Models\Htaccess;
use ThirstyAffiliates_Pro\Models\Metaboxes;
use ThirstyAffiliates_Pro\Models\Settings_Extension;
use ThirstyAffiliates_Pro\Models\Guided_Tour;
use ThirstyAffiliates_Pro\Models\Link_Health_Checker;
use ThirstyAffiliates_Pro\Models\URL_Shortener;
use ThirstyAffiliates_Pro\Models\Link_Scheduler;
use ThirstyAffiliates_Pro\Models\Input_Link_Picker;
use ThirstyAffiliates_Pro\Models\Preset_Click_Data;

use ThirstyAffiliates_Pro\Models\Mothership\Settings as Mothership_Settings;
use ThirstyAffiliates_Pro\Models\Mothership\Update;

define('TAP_EDITION', 'thirstyaffiliates-basic');
define('TAP_VERSION', tap_get_plugin_info('Version'));

/**
 * Returns a value from the current plugin header comment.
 *
 * @param  string $field Which value to retrieve
 * @return string
 */
function tap_get_plugin_info( $field ) {
    static $plugin_data;

    if ( ! isset( $plugin_data ) ) {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data( __FILE__, false, false);
    }

    if ( isset( $plugin_data[ $field ] ) ) {
        return $plugin_data[ $field ];
    }

    return '';
}

/**
 * Register plugin autoloader.
 *
 * @since 1.0.0
 *
 * @param string $class_name Name of the class to load.
 */
spl_autoload_register( function( $class_name ) {

    if ( strpos( $class_name , 'ThirstyAffiliates_Pro\\' ) === 0 ) { // Only do autoload for our plugin files

        $class_file  = str_replace( array( '\\' , 'ThirstyAffiliates_Pro' . DIRECTORY_SEPARATOR ) , array( DIRECTORY_SEPARATOR , '' ) , $class_name ) . '.php';

        require_once plugin_dir_path( __FILE__ ) . $class_file;

    }

} );

/**
 * The main plugin class.
 */
class ThirstyAffiliates_Pro extends Abstract_Main_Plugin_Class {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Single main instance of Plugin ThirstyAffiliates_Pro plugin.
     *
     * @since 1.0.0
     * @access private
     * @var ThirstyAffiliates_Pro
     */
    private static $_instance;

    /**
     * Array of missing external plugins that this plugin is depends on.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_failed_dependencies;




    /*
    |--------------------------------------------------------------------------
    | Class Methods
    |--------------------------------------------------------------------------
    */

    /**
     * ThirstyAffiliates_Pro constructor.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {

        register_deactivation_hook( __FILE__ , array( $this , 'general_deactivation_code' ) );

        if ( $this->_check_plugin_dependencies() !== true ) {

            // Display notice that plugin dependency is not present.
            add_action( 'admin_notices' , array( $this , 'missing_plugin_dependencies_notice' ) );

        } elseif ( $this->_check_plugin_dependency_version_requirements() !== true ) {

            // Display notice that some dependent plugin did not meet the required version.
            add_action( 'admin_notices' , array( $this , 'invalid_plugin_dependency_version_notice' ) );

        } else {

            // Lock 'n Load
            $this->_initialize_plugin_components();
            $this->_run_plugin();

        }

    }

    /**
     * Ensure that only one instance of Plugin Boilerplate is loaded or can be loaded (Singleton Pattern).
     *
     * @since 1.0.0
     * @access public
     *
     * @return ThirstyAffiliates_Pro
     */
    public static function get_instance() {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self();

        return self::$_instance;

    }

    /**
     * Check for external plugin dependencies.
     *
     * @since 1.0.0
     * @access private
     *
     * @return mixed Array if there are missing plugin dependencies, True if all plugin dependencies are present.
     */
    private function _check_plugin_dependencies() {

        // Makes sure the plugin is defined before trying to use it
        if ( !function_exists( 'is_plugin_active' ) )
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $this->failed_dependencies = array();

        if ( ! is_plugin_active( 'thirstyaffiliates/thirstyaffiliates.php' ) ) {

            $this->failed_dependencies[] = array(
                'plugin-key'       => 'thirstyaffiliates',
                'plugin-name'      => 'ThirstyAffiliates', // We don't translate this coz this is the plugin name
                'plugin-base-name' => 'thirstyaffiliates/thirstyaffiliates.php'
            );

        }

        return !empty( $this->failed_dependencies ) ? $this->failed_dependencies : true;

    }

    /**
     * Check plugin dependency version requirements.
     *
     * @since 1.0.0
     * @since 1.2.2 TAP 1.2.2 requires TA 3.2.2
     * @since 1.3.0 TAP 1.3.0 requires TA 3.3.1
     * @since 1.3.3 TAP 1.3.3 requires TA 3.4.0
     * @access private
     *
     * @return boolean True if plugin dependency version requirement is meet, False otherwise.
     */
    private function _check_plugin_dependency_version_requirements() {


        $ta_plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/thirstyaffiliates/thirstyaffiliates.php', false, false );

        // TAP 1.1.0 requires TA 3.1.1
        // TAP 1.2.2 requires TA 3.2.2
        // TAP 1.3.0 requires TA 3.3.1
        // TAP 1.3.3 requires TA 3.4.0
        return version_compare( $ta_plugin_data[ 'Version' ] , '3.4.0' , ">=" );
    }

    /**
     * Add notice to notify users that some plugin dependencies of this plugin is missing.
     *
     * @since 1.0.0
     * @access public
     */
    public function missing_plugin_dependencies_notice() {

        if ( !empty( $this->failed_dependencies ) ) {

            $admin_notice_msg = '';

            foreach ( $this->failed_dependencies as $failed_dependency ) {

                $failed_dep_plugin_file = trailingslashit( WP_PLUGIN_DIR ) . plugin_basename( $failed_dependency[ 'plugin-base-name' ] );

                if ( file_exists( $failed_dep_plugin_file ) )
                    $failed_dep_install_text = '<a href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $failed_dependency[ 'plugin-base-name' ] . '&amp;plugin_status=all&amp;s' , 'activate-plugin_' . $failed_dependency[ 'plugin-base-name' ] ) . '" title="' . __( 'Activate this plugin' , 'thirstyaffiliates-pro' ) . '" class="edit">' . __( 'Click here to activate &rarr;' , 'thirstyaffiliates-pro' ) . '</a>';
                else
                    $failed_dep_install_text = '<a href="' . wp_nonce_url( 'update.php?action=install-plugin&amp;plugin=' . $failed_dependency[ 'plugin-key' ] , 'install-plugin_' . $failed_dependency[ 'plugin-key' ] ) . '" title="' . __( 'Install this plugin' , 'thirstyaffiliates-pro' ) . '">' . __( 'Click here to install from WordPress.org repo &rarr;' , 'thirstyaffiliates-pro' ) . '</a>';

                $admin_notice_msg .= sprintf( __( '<br/>Please ensure you have the <a href="%1$s" target="_blank">%2$s</a> plugin installed and activated.<br/>' , 'thirstyaffiliates-pro' ) , 'http://wordpress.org/plugins/' . $failed_dependency[ 'plugin-key' ] . '/' , $failed_dependency[ 'plugin-name' ] );
                $admin_notice_msg .= $failed_dep_install_text . '<br/>';

            } ?>

            <div class="error">
                <p>
                    <?php _e( '<b>ThirstyAffiliates Pro</b> plugin missing dependency.<br/>' , 'thirstyaffiliates-pro' ); ?>
                    <?php echo $admin_notice_msg; ?>
                </p>
            </div>

        <?php }

    }

    /**
     * Add notice to notify user that some plugin dependencies did not meet the required version for the current version of this plugin.
     *
     * @since 1.0.0
     * @access public
     */
    public function invalid_plugin_dependency_version_notice() {

        $thirsty_affiliates_basename = plugin_basename( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'thirstyaffiliates' . DIRECTORY_SEPARATOR . 'thirstyaffiliates.php' );

        $update_text = sprintf( __( '<a href="%1$s">Click here to update ThirstyAffiliates &rarr;</a>' , 'thirstyaffiliates-pro' ) , wp_nonce_url( 'update.php?action=upgrade-plugin&plugin=' . $thirsty_affiliates_basename , 'upgrade-plugin_' . $thirsty_affiliates_basename ) ); ?>

        <div class="error">
            <p><?php echo sprintf( __( 'Please ensure you have the latest version of <a href="%1$s" target="_blank">ThirstyAffiliates</a> plugin installed and activated.' , 'thirstyaffiliates-pro' ) , 'http://wordpress.org/plugins/thirstyaffiliates/' ); ?></p>
            <p><?php echo $update_text; ?></p>
        </div>

        <?php

    }

    /**
     * Function that gets executed always whether dependency are present/valid or not.
     * There will be instances that a plugin is activated but the activation code is not executed, how? if dependencies are not present.
     * WP Plugins doesn't requires an activation and deactivation callbacks. If none is provided ( or none is presented coz of failed dependency ) then it continues activating the plugin.
     * Same can be said with deactivation procedure. That's why we need this function.
     *
     * @since 1.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param boolean $network_wide Flag that determines whether the plugin has been activated network wid ( on multi site environment ) or not.
     */
    public function general_deactivation_code( $network_wide ) {

        // Delete the flag that determines if plugin activation code is triggered
        global $wpdb;

        // check if it is a multisite network
        if ( is_multisite() ) {

            // check if the plugin has been activated on the network or on a single site
            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    delete_option( 'tap_activation_code_triggered' );
                    delete_site_option( 'tap_installed_version' );

                }

                restore_current_blog();

            } else {

                delete_option( 'tap_activation_code_triggered' ); // activated on a single site, in a multi-site
                delete_site_option( 'tap_installed_version' );

            }

        } else {

            delete_option( 'tap_activation_code_triggered' ); // activated on a single site
            delete_option( 'tap_installed_version' );

        }

    }

    /**
     * Initialize plugin components.
     *
     * @since 1.0.0
     * @access private
     */
    private function _initialize_plugin_components() {

        $plugin_constants   = Plugin_Constants::get_instance( $this );
        $helper_functions   = Helper_Functions::get_instance( $this , $plugin_constants );
        $plugin_visibility  = Plugin_Visibility::get_instance( $this , $plugin_constants , $helper_functions );
        $autolinker         = Autolinker::get_instance( $this , $plugin_constants , $helper_functions );
        $geolocation        = Geolocation::get_instance( $this , $plugin_constants , $helper_functions );
        $amazon             = Amazon::get_instance( $this , $plugin_constants , $helper_functions );
        $amazon_settings    = Amazon_Settings::get_instance( $this , $plugin_constants , $helper_functions );
        $adv_reporting      = Advanced_Reporting::get_instance( $this , $plugin_constants , $helper_functions );
        $htaccess           = Htaccess::get_instance( $this , $plugin_constants , $helper_functions );
        $event_notification = Event_Notification::get_instance( $this , $plugin_constants , $helper_functions );
        $update             = Update::get_instance( $this , $plugin_constants , $helper_functions );
        $guided_tour        = Guided_Tour::get_instance( $this , $plugin_constants , $helper_functions );
        $health_checker     = Link_Health_Checker::get_instance( $this , $plugin_constants , $helper_functions );
        $url_shortener      = URL_Shortener::get_instance( $this , $plugin_constants , $helper_functions );
        $input_link_picker  = Input_Link_Picker::get_instance( $this , $plugin_constants , $helper_functions );
        $preset_click_data  = Preset_Click_Data::get_instance( $this , $plugin_constants , $helper_functions );
        $settings           = Settings_Extension::get_instance( $this , $plugin_constants , $helper_functions );
        $mothership_settings = Mothership_Settings::get_instance( $this , $plugin_constants , $helper_functions, $update );

        $activatables   = array( $autolinker , $geolocation , $htaccess , $guided_tour , $health_checker );
        $initiables     = array( $plugin_visibility , $autolinker , $geolocation , $amazon , $amazon_settings , $adv_reporting , $htaccess , $event_notification , $guided_tour , $url_shortener , $input_link_picker , $settings , $health_checker , $preset_click_data , $mothership_settings );
        $deactivatables = array( $update , $htaccess );

        Bootstrap::get_instance( $this , $plugin_constants , $helper_functions , $activatables , $initiables , $deactivatables );
        Script_Loader::get_instance( $this , $plugin_constants , $helper_functions , $amazon , $guided_tour );
        CSV_Import_Export::get_instance( $this , $plugin_constants , $helper_functions );
        Google_Click_Tracking::get_instance( $this , $plugin_constants , $helper_functions );
        Affiliate_Link_Extend::get_instance( $this , $plugin_constants , $helper_functions );
        Metaboxes::get_instance( $this , $plugin_constants , $helper_functions );
        Link_Scheduler::get_instance( $this , $plugin_constants , $helper_functions );

    }

    /**
     * Run the plugin. ( Runs the various plugin components ).
     *
     * @since 1.0.0
     * @access private
     */
    private function _run_plugin() {

        foreach ( $this->__all_models as $model )
            if ( $model instanceof Model_Interface )
                $model->run();

    }

}

/**
 * Returns the main instance of ThirstyAffiliates_Pro to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return ThirstyAffiliates_Pro Main instance of the plugin.
 */
function ThirstyAffiliates_Pro() {

    return ThirstyAffiliates_Pro::get_instance();

}

// Let's Roll!
$GLOBALS[ 'thirstyaffiliates_pro' ] = ThirstyAffiliates_Pro();
