<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Activatable_Interface;
use ThirstyAffiliates_Pro\Interfaces\Deactivatable_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic of 'Bootstraping' the plugin.
 *
 * @since 1.0.0
 */
class Bootstrap implements Model_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Bootstrap.
     *
     * @since 1.0.0
     * @access private
     * @var Bootstrap
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
     * Array of models implementing the ThirstyAffiliates_Pro\Interfaces\Activatable_Interface.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_activatables;

    /**
     * Array of models implementing the ThirstyAffiliates_Pro\Interfaces\Initiable_Interface.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_initiables;

    /**
     * Array of models implementing the ThirstyAffiliates_Pro\Interfaces\Deactivatable_Interface.
     *
     * @since 1.0.0
     * @access private
     * @var array
     */
    private $_deactivatables;




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
     * @param array                      $activatables     Array of models implementing ThirstyAffiliates_Pro\Interfaces\Activatable_Interface.
     * @param array                      $initiables       Array of models implementing ThirstyAffiliates_Pro\Interfaces\Initiable_Interface.
     * @param array                      $deactivatables   Array of models implementing ThirstyAffiliates_Pro\Interfaces\Deactivatable_Interface.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , array $activatables = array() , array $initiables = array() , array $deactivatables = array() ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;
        $this->_activatables     = $activatables;
        $this->_initiables       = $initiables;
        $this->_deactivatables   = $deactivatables;

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
     * @param array                      $activatables     Array of models implementing ThirstyAffiliates_Pro\Interfaces\Activatable_Interface.
     * @param array                      $initiables       Array of models implementing ThirstyAffiliates_Pro\Interfaces\Initiable_Interface.
     * @param array                      $deactivatables   Array of models implementing ThirstyAffiliates_Pro\Interfaces\Deactivatable_Interface.
     * @return Bootstrap
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions , array $activatables = array() , array $initiables = array() , array $deactivatables = array() ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions , $activatables , $initiables , $deactivatables );

        return self::$_instance;

    }

    /**
     * Load plugin text domain.
     *
     * @since 1.0.0
     * @access public
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain( Plugin_Constants::TEXT_DOMAIN , false , $this->_constants->PLUGIN_DIRNAME() . '/i18n' );

    }

    /**
     * Method that houses the logic relating to activating the plugin.
     *
     * @since 1.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param boolean $network_wide Flag that determines whether the plugin has been activated network wid ( on multi site environment ) or not.
     */
    public function activate_plugin( $network_wide ) {

        global $wpdb;

        if ( is_multisite() ) {

            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_activate_plugin( $blog_id );

                }

                restore_current_blog();

            } else
                $this->_activate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site

        } else
            $this->_activate_plugin( $wpdb->blogid ); // activated on a single site

    }

    /**
     * Method to initialize a newly created site in a multi site set up.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int    $blogid Blog ID of the created blog.
     * @param int    $user_id User ID of the user creating the blog.
     * @param string $domain Domain used for the new blog.
     * @param string $path Path to the new blog.
     * @param int    $site_id Site ID. Only relevant on multi-network installs.
     * @param array  $meta Meta data. Used to set initial site options.
     */
    public function new_mu_site_init( $blog_id , $user_id , $domain , $path , $site_id , $meta ) {

        if ( is_plugin_active_for_network( 'thirstyaffiliates-pro/thirstyaffiliates-pro.php' ) ) {

            switch_to_blog( $blog_id );
            $this->_activate_plugin( $blog_id );
            restore_current_blog();

        }

    }

    /**
     * Initialize plugin settings options.
     * This is a compromise to my idea of 'Modularity'. Ideally, bootstrap should not take care of plugin settings stuff.
     * However due to how WooCommerce do its thing, we need to do it this way. We can't separate settings on its own.
     *
     * @since 1.0.0
     * @access private
     */
    private function _initialize_plugin_settings_options() {

        // Enable geolocation module on first activation
        if ( !get_option( 'tap_enable_geolocation' , false ) === false )
            update_option( 'tap_enable_geolocation' , 'yes' );

        // Amazon Table Columns ( Screen Options )
        if ( !get_option( Plugin_Constants::AMAZON_TABLE_COLUMNS , false ) === false )
            update_option( Plugin_Constants::AMAZON_TABLE_COLUMNS , array( 'price' => 'yes' , 'item-stock' => 'yes' , 'sales-rank' => 'yes' ) );

        // Amazon Search Result Caching
        if ( !get_option( 'tap_enable_azon_transient' , false ) === false )
            update_option( 'tap_enable_azon_transient' , 'yes' );

        if ( !get_option( 'tap_azon_transient_lifespan' , false ) === false )
            update_option( 'tap_azon_transient_lifespan' , 7 );

        // Help settings section options

        // Set initial value of 'no' for the option that sets the option that specify whether to delete the options on plugin uninstall. Optionception.
        if ( !get_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS , false ) === false )
            update_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS , 'no' );

    }

    /**
     * Actual function that houses the code to execute on plugin activation.
     *
     * @since 1.0.0
     * @since 1.6 Refactor support for multisite setup.
     * @access private
     *
     * @param int $blogid Blog ID of the created blog.
     */
    private function _activate_plugin( $blogid ) {

        /**
         * Previously multisite installs site store license options using normal get/add/update_option functions.
         * These stores the option on a per sub-site basis. We need move these options network wide in multisite setup
         * via get/add/update_site_option functions.
         */
        if ( is_multisite() ) {

            if ( $license_email = get_option( Plugin_Constants::OPTION_ACTIVATION_EMAIL ) ) {

                update_site_option( Plugin_Constants::OPTION_ACTIVATION_EMAIL , $license_email );

                delete_option( Plugin_Constants::OPTION_ACTIVATION_EMAIL );

            }

            if ( $license_key = get_option( Plugin_Constants::OPTION_LICENSE_KEY ) ) {

                update_site_option( Plugin_Constants::OPTION_LICENSE_KEY , $license_key );

                delete_option( Plugin_Constants::OPTION_LICENSE_KEY );

            }

            if ( $installed_version = get_option( Plugin_Constants::INSTALLED_VERSION ) ) {

                update_site_option( Plugin_Constants::INSTALLED_VERSION , $installed_version );

                delete_option( Plugin_Constants::INSTALLED_VERSION );

            }

        }

        // Initialize settings options
        $this->_initialize_plugin_settings_options();

        // Execute 'activate' contract of models implementing ThirstyAffiliates_Pro\Interfaces\Activatable_Interface
        foreach ( $this->_activatables as $activatable )
            if ( $activatable instanceof Activatable_Interface )
                $activatable->activate();

        // Update current installed plugin version
        if ( is_multisite() )
            update_site_option( Plugin_Constants::INSTALLED_VERSION , Plugin_Constants::VERSION );
        else
            update_option( Plugin_Constants::INSTALLED_VERSION , Plugin_Constants::VERSION );

        flush_rewrite_rules();

        update_option( 'tap_activation_code_triggered' , 'yes' );

    }

    /**
     * Method that houses the logic relating to deactivating the plugin.
     *
     * @since 1.0.0
     * @access public
     *
     * @global wpdb $wpdb Object that contains a set of functions used to interact with a database.
     *
     * @param boolean $network_wide Flag that determines whether the plugin has been activated network wid ( on multi site environment ) or not.
     */
    public function deactivate_plugin( $network_wide ) {

        global $wpdb;

        // check if it is a multisite network
        if ( is_multisite() ) {

            // check if the plugin has been activated on the network or on a single site
            if ( $network_wide ) {

                // get ids of all sites
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    $this->_deactivate_plugin( $wpdb->blogid );

                }

                restore_current_blog();

            } else
                $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site, in a multi-site

        } else
            $this->_deactivate_plugin( $wpdb->blogid ); // activated on a single site

    }

    /**
     * Actual method that houses the code to execute on plugin deactivation.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int $blogid Blog ID of the created blog.
     */
    private function _deactivate_plugin( $blogid ) {

        // Execute 'deactivate' contract of models implementing ThirstyAffiliates_Pro\Interfaces\Deactivatable_Interface
        foreach ( $this->_deactivatables as $deactivatable )
            if ( $deactivatable instanceof Deactivatable_Interface )
                $deactivatable->deactivate();

        flush_rewrite_rules();

    }

    /**
     * Add custom links to the plugin's action links.
     *
     * @since 1.0.0
     * @access public
     */
    public function plugin_action_links( $links ) {

        $new_links = array(
            '<a href="edit.php?post_type=thirstylink&page=thirsty-settings">' . __( 'Settings' , 'thirstyaffiliates-pro' ) . '</a>'
        );

        return array_merge( $new_links , $links );
    }

    /**
     * Method that houses codes to be executed on init hook.
     *
     * @since 1.0.0
     * @access public
     */
    public function initialize() {

        $installed_version = is_multisite() ? get_site_option( Plugin_Constants::INSTALLED_VERSION , false ) : get_option( Plugin_Constants::INSTALLED_VERSION , false );

        // Execute activation codebase if not yet executed on plugin activation ( Mostly due to plugin dependencies )
        if ( version_compare( $installed_version , Plugin_Constants::VERSION , '!=' ) || get_option( 'tap_activation_code_triggered' , false ) !== 'yes' ) {

            if ( ! function_exists( 'is_plugin_active_for_network' ) )
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

            $network_wide = is_plugin_active_for_network( 'thirstyaffiliates-pro/thirstyaffiliates-pro.php' );
            $this->activate_plugin( $network_wide );

        }

        // Execute 'initialize' contract of models implementing ThirstyAffiliates_Pro\Interfaces\Initiable_Interface
        foreach ( $this->_initiables as $initiable )
            if ( $initiable instanceof Initiable_Interface )
                $initiable->initialize();

    }

    /**
     * Execute plugin bootstrap code.
     *
     * @since 1.0.0
     * @access public
     */
    public function run() {

        // Internationalization
        add_action( 'plugins_loaded' , array( $this , 'load_plugin_textdomain' ) );

        // Execute plugin activation/deactivation
        register_activation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'activate_plugin' ) );
        register_deactivation_hook( $this->_constants->MAIN_PLUGIN_FILE_PATH() , array( $this , 'deactivate_plugin' ) );

        // Execute plugin initialization ( plugin activation ) on every newly created site in a multi site set up
        add_action( 'wpmu_new_blog' , array( $this , 'new_mu_site_init' ) , 10 , 6 );

        add_filter( 'plugin_action_links_' . $this->_constants->PLUGIN_BASENAME() , array( $this , 'plugin_action_links' ) );

        // Execute codes that need to run on 'init' hook
        add_action( 'init' , array( $this , 'initialize' ) );
    }

}
