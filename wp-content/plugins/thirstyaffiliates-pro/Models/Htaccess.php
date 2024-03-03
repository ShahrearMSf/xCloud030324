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
 * Model that houses the logic for the Htaccess module.
 *
 * @since 1.0.0
 */
class Htaccess implements Model_Interface , Activatable_Interface , Deactivatable_Interface , Initiable_Interface {

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
     * Write data to htaccess debug log file.
     *
     * @since 1.0.0
     * @access public
     *
     * @param mixed Data to log.
     */
    private function write_htaccess_debug_log( $log ) {

        error_log( "\n[" . current_time( 'mysql' ) . "]: " . $log , 3 , $this->_constants->LOGS_ROOT_PATH() . 'htaccess-debug.log' );

    }

    /**
     * Initialize htaccess module by adding the ThirstyAffiliates htaccess area.
     *
     * @since 1.0.0
     * @since 1.3.0 Move out generate htacess area code into its own function.
     * @access private
     */
    private function init_htaccess_module() {

        // generate htaccess area.
        $this->generate_htaccess_area();

        // record all existing links to htaccess
        $this->write_htaccess_debug_log( "========== START: Adding all affiliate links to htacess (triggered on module activation) ==========" );
        $this->register_all_existing_links_to_htaccess();
        $this->write_htaccess_debug_log( "========== END: Adding all affiliate links to htacess (triggered on module activation) ==========\n\n" );

    }

    /**
     * Generate htaccess area.
     *
     * @since 1.3.0
     * @access private
     */
    private function generate_htaccess_area() {

        // Add ThirstyAffiliates htaccess area to put link entries on site htaccess
        $htaccess_contents   = $this->get_htaccess_contents();
        $tap_htaccess_area   = "\n#BEGIN ThirstyAffiliates\n";
        $tap_htaccess_area  .= "<IfModule mod_rewrite.c>\n";
        $tap_htaccess_area  .= "RewriteEngine On\n";
        $tap_htaccess_area  .= "</IfModule>\n";
        $tap_htaccess_area  .= "#END ThirstyAffiliates";

        if ( ! preg_match( "/#[\s]*BEGIN ThirstyAffiliates.*?#[\s]*END ThirstyAffiliates/is" , $htaccess_contents , $match ) )
            $this->write_htaccess_contents( $tap_htaccess_area , true );
    }

    /**
     * Remove the ThirstyAffiliates htaccess area from the htaccess file upon plugin deactivation.
     *
     * @since 1.0.0
     * @access private
     */
    private function remove_thirstyaffiliates_htaccess_area() {

        $htaccess_contents = $this->get_htaccess_contents();
        $htaccess_contents = preg_replace( "/[\n]*#[\s]*BEGIN ThirstyAffiliates.*?#[\s]*END ThirstyAffiliates/is" , "" , $htaccess_contents );
        $this->write_htaccess_contents( $htaccess_contents , false );
    }

    /**
     * Register all existing links to the htaccess file by triggering the save_post action of each affiliate link.
     *
     * @since 1.0.0
     * @access public
     */
    public function register_all_existing_links_to_htaccess() {

        $args = array(
            'post_type'      => Plugin_Constants::AFFILIATE_LINKS_CPT,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids'
        );
        $status_count = array(
            'success' => 0,
            'fail'    => 0,
            'exist'   => 0
        );

        $query = new \WP_Query( $args );

        foreach ( $query->posts as $post_id ) {

            $key = $this->save_affiliate_link_to_htaccess( $post_id );

            if ( isset( $status_count[ $key ] ) )
                $status_count[ $key ]++;
        }

        return $status_count;
    }

    /**
     * AJAX Register all existing links to the htaccess file by triggering the save_post action of each affiliate link.
     *
     * @since 1.0.0
     * @since 1.3.0 When tap_show_htaccess_warning_on_setting_save option is true, then we need to remove the htaccess area and recreate it.
     * @access public
     */
    public function ajax_register_all_existing_links_to_htaccess() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! current_user_can( 'administrator' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this action.' , 'thirstyaffiliates-pro' ) );
        else {

            if ( get_option( 'tap_show_htaccess_warning_on_setting_save' ) ) {

                $this->remove_thirstyaffiliates_htaccess_area();
                $this->generate_htaccess_area();
            }

            $this->write_htaccess_debug_log( "========== START: Adding all affiliate links to htacess (triggered on Settings help tab) ==========" );
            $status_count = $this->register_all_existing_links_to_htaccess();
            $this->write_htaccess_debug_log( "========== END: Adding all affiliate links to htacess (triggered on Settings help tab) ==========\n\n" );

            $message   = sprintf( __( '<span class="success"><strong>%s</strong> affiliate links successfully added.</span> ' , 'thirstyaffiliates-pro' ) , $status_count[ 'success' ] );
            $message  .= sprintf( __( '<span class="fail"><strong>%s</strong> failed.</span> ' , 'thirstyaffiliates-pro' ) , $status_count[ 'fail' ] );
            $message  .= sprintf( __( '<span class="exist"><strong>%s</strong> already exist.</span> ' , 'thirstyaffiliates-pro' ) , $status_count[ 'exist' ] );
            $message  .= __( 'View the htaccess debug log to view full report' , 'thirstyaffiliates-pro' ) . '<code>wp-content/plugins/' . $this->_constants->PLUGIN_DIRNAME() . '/logs/htaccess-debug.log</code>';
            $response  = array( 'status' => 'success' , 'message' => $message );

            delete_option( 'tap_show_htaccess_warning_on_setting_save' );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * Save single affiliate link to the htaccess file.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $post_id Affiliate Link post ID.
     */
    public function save_affiliate_link_to_htaccess( $post_id ) {

        $thirstylink = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_affiliate_link( $post_id );

        if ( ! $this->affiliate_link_filter( $post_id ) || ! $this->_is_link_eligible_for_htaccess( $thirstylink ) ) {

            $this->write_htaccess_debug_log( 'The record for affiliate link #' . $post_id . ' cannot be created. Affiliate links with geolocation / scheduler data are skipped.' );
            $this->delete_link_entry_from_htaccess( $post_id );
            return 'fail';
        }

        $original_link_url = esc_url_raw( $thirstylink->get_prop( 'destination_url' ) );
        $cloaked_url       = $this->get_cloaked_url( $thirstylink->get_prop( 'permalink' ) );

        if ( ! $cloaked_url || ! $original_link_url ) {
            $this->write_htaccess_debug_log( 'The record for affiliate link #' . $post_id . ' cannot be created. The affiliate link doesn\'t have a valid destination URL.' );
            return 'fail';
        }

        $htaccess_contents = $this->get_htaccess_contents();
        $string_index      = strpos( $htaccess_contents , $cloaked_url );

        // skip if affiliate link is already on htaccess file.
        if ( $string_index !== false ) {

            $this->write_htaccess_debug_log( 'The record for affiliate link #' . $post_id . ' already exists in the htaccess file.' );
            return 'exist';
        }

        // We have to insert to the proper area, and not to wp area
        $redirect_type    = $thirstylink->get_redirect_type();
        $thirstylink_area = "#BEGIN ThirstyAffiliates\n<IfModule mod_rewrite.c>\nRewriteEngine On\n";
        $link_entry       = "Redirect " . $redirect_type . " " . $cloaked_url . " " . $original_link_url . "\n";

        if ( strpos( $htaccess_contents , $thirstylink_area ) === false ) {
            $this->write_htaccess_debug_log( 'The record for affiliate link #' . $post_id . ' cannot be created. The ThirstyAffiliates area on the htaccess file doesn\'t exist.' );
            return 'fail';
        }

        // Write changes to htaccess file
        $htaccess_contents = str_replace( $thirstylink_area , $thirstylink_area . $link_entry , $htaccess_contents );
        $this->write_htaccess_contents( $htaccess_contents , false );
        $this->write_htaccess_debug_log( 'Created record for affiliate link #' . $post_id . ' on the htaccess file.' );

        return 'success';
    }

    /**
     * Delete single affiliate link from the htaccess file.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $post_id Affiliate Link post ID.
     */
    public function delete_link_entry_from_htaccess( $post_id ) {

        if ( ! $this->affiliate_link_filter( $post_id ) )
            return;

        $thirstylink       = ThirstyAffiliates()->helpers[ 'Helper_Functions' ]->get_affiliate_link( $post_id );
        $htaccess_contents = $this->get_htaccess_contents();
        $redirect_type     = $thirstylink->get_redirect_type();
        $cloaked_url       = $this->get_cloaked_url( $thirstylink->get_prop( 'permalink' ) );
        $string_index      = strpos( $htaccess_contents , $cloaked_url );

        // skip if affiliate link doesn't exist on the htaccess file.
        if ( $string_index === false )
            return;

        // construct link entry from and to
        $link_entry_from = "\nRedirect " . $redirect_type . " " . $cloaked_url;
        $link_entry_to   = $this->get_substr_until_newline( $htaccess_contents , $link_entry_from );

        // Remove link entry
        $htaccess_contents = str_replace( $link_entry_from . $link_entry_to , "" , $htaccess_contents );
        $this->write_htaccess_contents( $htaccess_contents , false );
        $this->write_htaccess_debug_log( 'Deleted record for affiliate link #' . $post_id . ' on the htaccess file.' );
    }

    /**
     * Filter callback to show plugin notes on link save.
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $messages Post messages.
     * @return array Filtered post messages
     */
    public function show_note_after_link_save( $messages ) {

        global $post_ID;

        $msg  = sprintf( __( 'Link updated. <a href="%s">View link</a>' , 'thirstyaffiliates-pro' ) , esc_url( get_permalink( $post_ID ) ) );
        $msg .= '<br/>';
        $msg .= __( "<p>Because the <b>ThirstyAffiliates Pro Htaccess module</b> is on, links may appear cached in your browser. To test a link after you change the destination, always use a Private Browsing/Incognito window to ensure a fresh browser session.</p>" , 'thirstyaffiliates-pro' );

        $messages[ 'thirstylink' ] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => $msg,
        );

        return $messages;
    }

    /**
     * Trigger htaccess init function when module is enabled.
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $old_value Option old value.
     * @param string $value Option old value.
     */
    public function toggle_htaccess_when_module_is_enabled_disabled( $old_value , $value ) {

        if ( $value === 'yes' )
            $this->init_htaccess_module();
        else
            $this->remove_thirstyaffiliates_htaccess_area();
    }

    /**
     * Fix all existing links in .htaccess file.
     *
     * @since 1.1.3
     * @access public
     *
     * @param array $link_ids All published affiliate link IDs.
     */
    public function fix_all_links_in_htaccess( $link_ids ) {

        $htaccess_contents = $this->get_htaccess_contents();

        preg_match( '/[\n]*#[\s]*BEGIN ThirstyAffiliates.*?#[\s]*END ThirstyAffiliates/is' , $htaccess_contents , $matches );

        if ( ! isset( $matches[0] ) )
            return;

        $ta_htacces_rules  = str_replace( array( '&amp;amp;' , '&amp;' , '&#038;' ) , '&' , $matches[0] );
        $htaccess_contents = preg_replace( "/[\n]*#[\s]*BEGIN ThirstyAffiliates.*?#[\s]*END ThirstyAffiliates/is" , $ta_htacces_rules , $htaccess_contents );
        $this->write_htaccess_contents( $htaccess_contents , false );
    }

    /**
     * Filter nojs redirect attribute, so it will be "true" when affiliate link is eligible for htaccess redirect.
     *
     * @since 1.3.0
     * @access public
     *
     * @param bool           $return      Filter return value.
     * @param Affiliate_Link $thirstylink Affiliate_Link object.
     * @return bool True if affiliate link should not be redirected via JS, false otherwise.
     */
    public function filter_affiliate_nojs_redirect_attribute( $return , $thirstylink ) {

        return $this->_is_link_eligible_for_htaccess( $thirstylink );
    }

    /**
     * Add nojs redirect attribute to the $other_atts variable in the link picker.
     *
     * @since 1.3.0
     * @access public
     *
     * @param array          $other_atts  List of affiliate link additional attributes.
     * @param Affiliate_Link $thirstylink Affiliate_Link object.
     * @return array Filtered list of affiliate link additional attributes.
     */
    public function add_nojs_redirect_attribute_to_other_atts( $other_atts , $thirstylink ) {

        $other_atts[ 'data-nojs' ] = $this->_is_link_eligible_for_htaccess( $thirstylink );
        return $other_atts;
    }




    /*
    |--------------------------------------------------------------------------
    | Private utility methods
    |--------------------------------------------------------------------------
    */

    /**
     * The purpose of this function is to filter posts if it is a thirsty post type, validate nonce, check if it an auto save,
     * check if the current user have admin privileges and check if it is a revision.
     *
     * @since 1.0.0
     * @access private
     *
     * @param int $post_id Affiliate Link post ID.
     * @return bool true if passed, false otherwise.
     */
    private function affiliate_link_filter( $post_id ) {

        if( wp_is_post_autosave( $post_id )
            || wp_is_post_revision( $post_id )
            || ! current_user_can( 'edit_page' , $post_id )
            || get_post_type( $post_id ) != Plugin_Constants::AFFILIATE_LINKS_CPT )
            return false;
        else
            return true;
    }

    /**
     * Get affiliate link cloaked url.
     *
     * @since 1.0.0
     * @access private
     *
     * @param string $permalink Affiliate link permalink URL.
     * @return string Affiliate link cloaked url
     */
    private function get_cloaked_url( $permalink ) {

        $parsed_url  = parse_url( $permalink );
        $cloaked_url = $parsed_url[ 'path' ];

        if ( array_key_exists( 'query' , $parsed_url ) )
            $cloaked_url .= $parsed_url[ 'query' ];

        if ( array_key_exists( 'fragment' , $parsed_url ) )
            $cloaked_url .= $parsed_url[ 'fragment' ];

        $cloaked_url = str_replace( '__trashed' , '' , $cloaked_url );

        return $cloaked_url;
    }

    /**
     * Get htaccess contents.
     *
     * @since 1.0.0
     * @access private
     *
     * @return string Htaccess contents.
     */
    private function get_htaccess_contents() {

        return file_get_contents( $this->_constants->HTACCESS_FILE() );
    }

    /**
     * Set htaccess contents.
     *
     * @since 1.0.0
     *
     * @param string $content Content to write.
     * @param bool   $append  Flag to determine either to append or not the content.
     */
    private function write_htaccess_contents( $content , $append ) {

        if( $append )
            file_put_contents( $this->_constants->HTACCESS_FILE() , $content , FILE_APPEND );
        else
            file_put_contents( $this->_constants->HTACCESS_FILE() , $content );
    }

    /**
     * Get all characters "after" the needle "up to" the first newline character encountered.
     *
     * @since 1.0.0
     * @access private
     *
     * @param string $haystack Content to search from.
     * @param string $needle   Content to search for.
     * @return string Substring until the first newline character.
     */
    private function get_substr_until_newline( $haystack , $needle ) {

        // length of needle
        $len = strlen( $needle );

        //matches $needle until hits a \n or \r
        if( preg_match( "#$needle([^\r\n]+)#i" , $haystack , $match ) ) {

            $rsp  = strlen($match[0]); //length of matched text
            $back = $rsp - $len;       //determine what to remove

            return substr( $match[0] , - $back );
        }
    }

    /**
     * Check if link is eligible for htaccess.
     *
     * @since 1.3.0
     * @access private
     *
     * @param Affiliate_Link $thirstylink  Affiliate Link object.
     * @return bool True if eligible, otherwise false.
     */
    private function _is_link_eligible_for_htaccess( $thirstylink ) {

        $geolinks = $thirstylink->get_prop( 'geolocation_links' );
        return empty( $geolinks ) && $this->_helper_functions->get_thirstylink_schedule( $thirstylink ) === false;
    }

    /**
     * Recreate htaccess entries on setting save.
     *
     * @since 1.3.0
     * @access public
     *
     * @param mixed  $old_value Old option value.
     * @param mixed  $value     New option value.
     * @param string $option    Option name.
     */
    public function trigger_show_recreate_htaccess_warning( $old_value , $value , $option ) {

        // if the option value hasn't changed then skip.
        if ( $old_value === $value || get_option( 'tap_show_htaccess_warning_on_setting_save' ) ) return;

        update_option( 'tap_show_htaccess_warning_on_setting_save' , $option );
    }

    /**
     * Recreate htaccess entries on setting save. Runs on pre update option trigger.
     *
     * @since 1.3.3
     * @access public
     *
     * @param mixed  $value     New option value.
     * @param mixed  $old_value Old option value.
     * @param string $option    Option name.
     */
    public function pre_update_trigger_show_recreate_htaccess_warning( $value , $old_value , $option ) {

        // if the option value hasn't changed then skip.
        if ( $old_value === $value || get_option( 'tap_show_htaccess_warning_on_setting_save' ) ) return $value;

        update_option( 'tap_show_htaccess_warning_on_setting_save' , $option );

        return $value;
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

        if ( get_option( 'tap_enable_htaccess' ) !== 'yes' )
            return;

        $this->init_htaccess_module();
    }

    /**
     * Execute codes that needs to run plugin deactivation.
     *
     * @since 1.0.0
     * @access public
     * @implements ThirstyAffiliates_Pro\Interfaces\Deactivatable_Interface
     */
    public function deactivate() {

        $this->remove_thirstyaffiliates_htaccess_area();
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
        if ( get_option( 'tap_enable_htaccess' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_tap_htaccess_record_all_links' , array( $this , 'ajax_register_all_existing_links_to_htaccess' ) );
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

        // this function needs to be hooked always, even when the module is not enabled for it to work.
        add_action( 'update_option_tap_enable_htaccess' , array( $this , 'toggle_htaccess_when_module_is_enabled_disabled' ) , 10 , 2 );

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_htaccess' ) !== 'yes' )
            return;

        // We need to execute it at the very end so other functions hooking to this gets executed. Well at least all the post meta are saved.
        add_action( 'pre_post_update', array( $this , 'delete_link_entry_from_htaccess' ), 1000000 );
        add_action( 'trashed_post' , array( $this , 'delete_link_entry_from_htaccess' ), 1000000 );
        add_action( 'tap_after_add_edit_single_geolink' , array( $this , 'delete_link_entry_from_htaccess' ), 10 );

        add_action( 'ta_after_save_affiliate_link_post' , array( $this , 'save_affiliate_link_to_htaccess' ) );
        add_filter( 'post_updated_messages' , array( $this , 'show_note_after_link_save' ) );

        add_action ( 'tap_fix_all_links' , array( $this , 'fix_all_links_in_htaccess' ) , 10 , 1 );

        add_filter( 'ta_nojs_redirect_attribute' , array( $this , 'filter_affiliate_nojs_redirect_attribute' ) , 10 , 2 );
        add_filter( 'ta_link_insert_extend_data_attributes' , array( $this , 'add_nojs_redirect_attribute_to_other_atts' ) , 10 , 2 );

        add_action( 'update_option_ta_link_prefix' , array( $this , 'trigger_show_recreate_htaccess_warning' ) , 10 , 3 );
        add_action( 'update_option_ta_link_prefix_custom' , array( $this , 'trigger_show_recreate_htaccess_warning' ) , 10 , 3 );
        add_action( 'update_option_ta_show_cat_in_slug' , array( $this , 'trigger_show_recreate_htaccess_warning' ) , 10 , 3 );
        add_action( 'pre_update_option_ta_link_redirect_type' , array( $this , 'pre_update_trigger_show_recreate_htaccess_warning' ) , 10 , 3 );
    }

}
