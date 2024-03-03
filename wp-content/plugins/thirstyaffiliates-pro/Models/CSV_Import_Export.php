<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Activatable_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

use ThirstyAffiliates_Pro\Models\CSV_Importer\CSV_Importer;
use ThirstyAffiliates_Pro\Models\CSV_Importer\CSV_Exporter;

// Data Models
use ThirstyAffiliates\Models\Affiliate_Link;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the CSV Import/Export module.
 *
 * @since 1.0.0
 */
class CSV_Import_Export implements Model_Interface {

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
     * Add submenu pages for the CSV importer and exporter under the thirstylink post type menu.
     *
     * @since 1.0.0
     * @access public
     */
    public function add_submenu_pages() {

        add_submenu_page(
        'edit.php?post_type=thirstylink',
        __( 'Import CSV' , 'thirstyaffiliates-pro' ),
        __( 'Import CSV' , 'thirstyaffiliates-pro' ),
        'manage_options',
        'thirsty_csv_import',
        array( $this , 'csv_importer_redirector' )
      );

        add_submenu_page(
        'edit.php?post_type=thirstylink',
        __( 'Export CSV' , 'thirstyaffiliates-pro' ),
        __( 'Export CSV' , 'thirstyaffiliates-pro' ),
        'manage_options',
        'thirsty_csv_export',
        array( $this , 'csv_exporter_redirector' )
      );

    }

    /**
     * CSV importer redirector.
     *
     * @since 1.0.0
     * @access public
     */
    public function csv_importer_redirector() {

        if ( ! current_user_can( 'manage_options' ) )
        wp_die( __( 'You do not have suffifient permissions to access this page.' , 'thirstyaffiliates-pro' ) );

        $redirector_title = __( 'Redirecting to the ThirstyAffiliates CSV Importer...' , 'thirstyaffiliates-pro' );
        $redirector_url   = admin_url('admin.php?import=tap_csv_importer');

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'csv-importer/view-redirector.php' );
    }

    /**
     * CSV importer redirector.
     *
     * @since 1.0.0
     * @access public
     */
    public function csv_exporter_redirector() {

        if ( ! current_user_can( 'manage_options' ) )
        wp_die( __( 'You do not have suffifient permissions to access this page.' , 'thirstyaffiliates-pro' ) );

        $redirector_title = __( 'Redirecting to the ThirstyAffiliates CSV Exporter...' , 'thirstyaffiliates-pro' );
        $redirector_url   = admin_url('admin.php?import=tap_csv_exporter');

        include_once( $this->_constants->VIEWS_ROOT_PATH() . 'csv-importer/view-redirector.php' );
    }

    /**
     * Register importers.
     *
     * @since 1.0.0
     * @access public
     */
    public function register_importers() {

        // Load Importer API
         require_once( ABSPATH . 'wp-admin/includes/import.php' );

        if ( ! class_exists( 'WP_Importer' ) ) {

            $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

            if ( file_exists( $class_wp_importer ) )
                require( $class_wp_importer );
        }

        $csv_importer = new CSV_Importer( $this->_constants , $this->_helper_functions );
        $csv_exporter = new CSV_Exporter( $this->_constants , $this->_helper_functions );

        // Register the CSV Importer with WordPress
      register_importer(
        'tap_csv_importer',
        __( 'ThirstyAffiliates CSV Importer' , 'thirstyaffiliates-pro' ),
        __( 'Import affiliate links via a properly formatted CSV file, handy for bulk importing of affiliate links.' , 'thirstyaffiliates-pro' ),
        array( $csv_importer , 'handler' )
      );

        // Register the CSV Export with WordPress (as an importer because WP is still silly when it comes to exporters)
      register_importer(
        'tap_csv_exporter',
        __( 'ThirstyAffiliates CSV Exporter' , 'thirstyaffiliates-pro' ),
        __( 'Export affiliate links to a properly formatted CSV file, handy for transferring to another site using the CSV Import.' , 'thirstyaffiliates-pro' ),
            array( $csv_exporter , 'handler' )
      );
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

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_csv_importer' , 'yes' ) !== 'yes' )
            return;

        add_action( 'admin_init' , array( $this , 'register_importers' ) );
        add_action( 'admin_menu' , array( $this , 'add_submenu_pages' ) , 15 );
    }

}
