<?php
namespace ThirstyAffiliates_Pro\Models;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Model that houses the logic for the Preset_Click_Data module.
 *
 * @since 1.4.0
 */
class Preset_Click_Data implements Model_Interface , Initiable_Interface {

    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Property that holds the single main instance of Preset_Click_Data.
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
     * @return Preset_Click_Data
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin , Plugin_Constants $constants , Helper_Functions $helper_functions ) {

        if ( !self::$_instance instanceof self )
            self::$_instance = new self( $main_plugin , $constants , $helper_functions );

        return self::$_instance;

    }



    /*
    |--------------------------------------------------------------------------
    | Module implementation
    |--------------------------------------------------------------------------
    */

    /**
     * Implement preset click data.
     *
     * @since 1.4.0
     * @access public
     *
     * @param array          $click_data  Click data to be saved.
     * @param Affiliate_Link $thirstylink Affiliate link object.
     * @return array Filtered click data to be saved.
     */
    public function implement_preset_click_data( $click_data , $thirstylink ) {

        $qcode        = isset( $_GET[ 'q' ] ) ? sanitize_text_field( $_GET[ 'q' ] ) : null;
        $ip_address   = isset( $click_data[ 'user_ip_address' ] ) ? $click_data[ 'user_ip_address' ] : null;
        $preset_click = null;

        if ( $qcode ) {
            $preset_data  = get_post_meta( $thirstylink->get_id() , 'tap_preset_click_data' , true );
            $preset_click = isset( $preset_data[ $qcode ] ) ? $preset_data[ $qcode ] : array();
        }

        if ( is_array( $preset_click ) && ! empty( $preset_click ) )
            foreach( $preset_click as $key => $preset_value )
                $click_data[ $key ] = ! isset( $click_data[ $key ] ) || ! $click_data[ $key ] ? $preset_value : $click_data[ $key ];

        // if click data ip address is not valid, then replace it with preset data.
        if ( ! filter_var( $ip_address , FILTER_VALIDATE_IP , FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) && isset( $preset_click[ 'user_ip_address' ] ) && $preset_click[ 'user_ip_address' ] )
            $click_data[ 'user_ip_address' ] = $preset_click[ 'user_ip_address' ];

        return $click_data;
    }




    /*
    |--------------------------------------------------------------------------
    | CRUD Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Save preset click data.
     *
     * @since 1.4.0
     * @access private
     *
     * @param int    $link_id Affiliate link ID.
     * @param array  $data    Preset click data.
     * @param string $qcode   Delete preset click data.
     */
    private function _update_preset_click_data( $link_id , $data , $qcode = null ) {

        $preset_data = get_post_meta( $link_id , 'tap_preset_click_data' , true );
        $qcode       = $qcode ? $qcode : uniqid();
        $preset_data = is_array( $preset_data ) && ! empty( $preset_data ) ? $preset_data : array();

        $preset_data[ $qcode ] = $data;

        $check = update_post_meta( $link_id , 'tap_preset_click_data' , $preset_data );

        return $check ? $qcode : false;
    }

    /**
     * Delete preset click data.
     *
     * @since 1.4.0
     * @access private
     *
     * @param int    $link_id Affiliate link ID.
     * @param string $qcode   Delete preset click data.
     */
    private function _delete_preset_click_data( $link_id , $qcode ) {

        $preset_data = get_post_meta( $link_id , 'tap_preset_click_data' , true );
        $preset_data = is_array( $preset_data ) && ! empty( $preset_data ) ? $preset_data : array();

        unset( $preset_data[ $qcode ] );
        update_post_meta( $link_id , 'tap_preset_click_data' , $preset_data );
    }

    /**
     * Get preset click data row markup.
     *
     * @since 1.4.0
     * @access private
     *
     * @param string $qcode Delete preset click data.
     * @param array  $data  Preset click data.
     */
    private function _get_preset_click_data_row_markup( $qcode , $link_id , $data ) {

        ?>
            <tr data-preset="<?php echo esc_attr( json_encode( $data ) ); ?>">
                <td class="qcode" data-qcode="<?php echo esc_html( $qcode ); ?>">
                    <input class="qcode-url" type="text" value="<?php echo sprintf( '%s?q=%s' , get_permalink( $link_id ) , esc_html( $qcode ) ); ?>" readonly>
                </td>
                <td class="ip-address"><?php echo esc_html( $data[ 'user_ip_address' ] ); ?></td>
                <td class="referrer"><?php echo esc_html( $data[ 'http_referer' ] ); ?></td>
                <td class="keyword"><?php echo esc_html( $data[ 'keyword' ] ); ?></td>
                <td class="actions">
                    <a class="edit" href="javascript:void(0);">
                        <span class="dashicons dashicons-edit"></span>
                    </a>
                    <a class="remove" href="javascript:void(0);">
                        <span class="dashicons dashicons-no"></span>
                    </a>
                </td>
            </tr>
        <?php
    }

    /**
     * Get no results row markup.
     *
     * @since 1.4.0
     * @access private
     */
    private function _get_no_results_row_markup() {
        ?>
            <tr class="no-result">
                <td colspan="5"><?php _e( 'No preset click data parameters saved yet.' , 'thirstyaffiliates-pro' ); ?></td>
            </tr>
        <?php
    }

    /**
     * AJAX Load preset click data.
     *
     * @since 1.4.0
     * @access public
     */
    public function ajax_load_preset_click_data() {

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        if ( ! isset( $_POST[ 'link_id' ] ) || ! $_POST[ 'link_id' ] )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Missing required parameters.' , 'thirstyaffiliates-pro' ) );
        else {

            $link_id     = absint( $_POST[ 'link_id' ] );
            $preset_data = get_post_meta( $link_id , 'tap_preset_click_data' , true );

            ob_start();

            if ( is_array( $preset_data ) && ! empty( $preset_data ) ) {

                foreach( $preset_data as $qcode => $data )
                    $this->_get_preset_click_data_row_markup( $qcode , $link_id , $data );

            } else
                $this->_get_no_results_row_markup();

            $markup = ob_get_clean();

            $response = array(
                'status' => 'success',
                'markup' => $markup
            );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX Save preset click data.
     *
     * @since 1.4.0
     * @access public
     */
    public function ajax_save_edit_preset_click_data() {

        $link_id    = isset( $_POST[ '_preset_link_id' ] ) ? absint( $_POST[ '_preset_link_id' ] ) : 0;
        $qcode      = isset( $_POST[ '_preset_qcode' ] ) ? sanitize_text_field( $_POST[ '_preset_qcode' ] ) : null;
        $ip_address = isset( $_POST[ '_preset_ip_address' ] ) ? sanitize_text_field( $_POST[ '_preset_ip_address' ] ) : '';
        $referrer   = isset( $_POST[ '_preset_http_referrer' ] ) ? esc_url_raw( $_POST[ '_preset_http_referrer' ] ) : '';
        $keyword    = isset( $_POST[ '_preset_keyword' ] ) ? sanitize_text_field( $_POST[ '_preset_keyword' ] ) : '';

        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ '_preset_click_data_nonce' ] ) || ! wp_verify_nonce( $_POST[ '_preset_click_data_nonce' ], 'tap_save_preset_click_data' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        elseif ( ! $link_id || ( ! $ip_address && ! $referrer && ! $keyword ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'There is nothing to save.' , 'thirstyaffiliates-pro' ) );
        else {

            // sanitize data
            $data = array(
                'user_ip_address' => $ip_address,
                'http_referer'    => $referrer,
                'keyword'         => $keyword,
            );
            $qcode = $this->_update_preset_click_data( $link_id , $data , $qcode );

            if ( $qcode ) {

                ob_start();
                $this->_get_preset_click_data_row_markup( $qcode , $link_id , $data );
                $markup = ob_get_clean();

                $response = array( 'status' => 'success', 'markup' => $markup );


            } else
                $response = array( 'status' => 'fail' , 'error_msg' => __( 'There was a problem saving the data. Please try again.' , 'thirstyaffiliates-pro' ) );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();
    }

    /**
     * AJAX Delete preset click data.
     *
     * @since 1.4.0
     * @access public
     */
    public function ajax_delete_preset_click_data() {

        $link_id = isset( $_POST[ '_preset_link_id' ] ) ? absint( $_POST[ '_preset_link_id' ] ) : 0;
        $qcode   = isset( $_POST[ '_preset_qcode' ] ) ? sanitize_text_field( $_POST[ '_preset_qcode' ] ) : null;


        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX call' , 'thirstyaffiliates-pro' ) );
        elseif ( ! isset( $_POST[ 'nonce' ] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], 'tap_save_preset_click_data' ) )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'You are not allowed to do this.' , 'thirstyaffiliates-pro' ) );
        elseif ( ! $link_id || ! $qcode )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'There is nothing to save.' , 'thirstyaffiliates-pro' ) );
        else {

            $this->_delete_preset_click_data( $link_id , $qcode );
            $response = array( 'status' => 'success' );
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
     * @since 1.4.0
     * @access public
     * @inherit ThirstyAffiliates_Pro\Interfaces\Initiable_Interface
     */
    public function initialize() {

        // When module is disabled in the settings, then it shouldn't run the whole class.
        if ( get_option( 'tap_enable_preset_click_data' , 'yes' ) !== 'yes' )
            return;

        add_action( 'wp_ajax_tap_save_edit_preset_click_data' , array( $this , 'ajax_save_edit_preset_click_data' ) , 10 );
        add_action( 'wp_ajax_tap_delete_preset_click_data' , array( $this , 'ajax_delete_preset_click_data' ) , 10 );
        add_action( 'wp_ajax_tap_load_preset_click_data' , array( $this , 'ajax_load_preset_click_data' ) , 10 );
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

        if ( get_option( 'tap_enable_preset_click_data' , 'yes' ) !== 'yes' )
            return;

        add_filter( 'ta_save_click_data' , array( $this , 'implement_preset_click_data' ) , 20 , 2 );
    }

}
