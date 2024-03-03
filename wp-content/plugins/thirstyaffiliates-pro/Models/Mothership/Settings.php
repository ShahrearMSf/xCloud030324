<?php

namespace ThirstyAffiliates_Pro\Models\Mothership;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Initiable_Interface;
use ThirstyAffiliates_Pro\Interfaces\Model_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class Settings implements Model_Interface, Initiable_Interface {

    /**
     * Property that holds the single main instance of Settings.
     *
     * @since 1.0.0
     * @access private
     * @var Settings
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
     * @since 1.7.14
     * @access private
     * @var \ThirstyAffiliates_Pro\Models\Mothership\Update
     */
    private $_update;

    /**
     * Class constructor.
     *
     * @param Abstract_Main_Plugin_Class $main_plugin Main plugin object.
     * @param Plugin_Constants $constants Plugin constants object.
     * @param Helper_Functions $helper_functions Helper functions object.
     * @param Update $update Update model object.
     *
     * @since 1.0.0
     * @access public
     *
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions, Update $update ) {

        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;
        $this->_update           = $update;

        $main_plugin->add_to_all_plugin_models( $this );

    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @param Abstract_Main_Plugin_Class $main_plugin Main plugin object.
     * @param Plugin_Constants $constants Plugin constants object.
     * @param Helper_Functions $helper_functions Helper functions object.
     * @param Update $update Update model object.
     *
     * @return Settings
     *
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions, Update $update ) {

        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self( $main_plugin, $constants, $helper_functions, $update );
        }

        return self::$_instance;

    }

    /**
     * Register mothership settings menu.
     */
    public function register_mothership_settings_menu() {

        add_menu_page(
            __( "TAP License", "thirstyaffiliates-pro" ),
            __( "TAP License", "thirstyaffiliates-pro" ),
            "manage_sites",
            "tap-ms-license-settings",
            array( $this, "generate_settings_page" )
        );

    }

    /**
     * Register mothership settings page.
     */
    public function generate_settings_page() {

        ?>

        <div id="tap_mothership_settings" class="wrap">

            <h2><?php esc_html_e( 'ThirstyAffiliates Pro License', 'thirstyaffiliates-pro' ); ?></h2>

            <table class="form-table">
                <tbody>
                    <?php $this->generate_activation_table_rows(); ?>
                </tbody>
            </table>

            <p class="submit">
                <input type="button" id="submit" class="button button-primary"
                       value="<?php esc_attr_e( 'Save Changes', 'thirstyaffiliates-pro' ); ?>" />
                <span class="spinner"
                      style="float: none; position: relative; top: -2px; margin-top: 0; display: none; visibility: visible;"></span>
            </p>

        </div>

        <?php

    }

    /**
     * Render the HTML for the activation table rows
     */
    private function generate_activation_table_rows() {

        $license_activated = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED );
        $license_key = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_KEY );
        $is_legacy_license_key = $this->_helper_functions->is_legacy_license_key($license_key);
        $edge_updates = $this->_helper_functions->get_option( Plugin_Constants::OPTION_EDGE_UPDATES ) === 'yes';
        $site_domain = $this->_helper_functions->get_site_domain();

        if ( ! empty( $license_key ) && ! $is_legacy_license_key ) {
            $license = get_site_transient( 'tap_license_info' );

            if ( empty( $license ) ) {
                $this->_update->manually_queue_update();
                $license = get_site_transient( 'tap_license_info' );
            }
        }

        ?>

        <tr>
            <td id="tap-license-content" colspan="2">

                <?php

                    if ( ! empty( $license ) && is_array( $license ) ) {

                        require $this->_constants->VIEWS_ROOT_PATH() . 'license/active_license.php';

                    } elseif ( $license_activated == 'yes' && $is_legacy_license_key ) {

                        require $this->_constants->VIEWS_ROOT_PATH() . 'license/active_legacy_license.php';

                    } else {

                        require $this->_constants->VIEWS_ROOT_PATH() . 'license/inactive_license.php';

                    }

                ?>
            </td>
        </tr>

        <?php

    }

    /**
     * Register mothership settings section.
     *
     * @param array $settings_sections Array of settings sections.
     *
     * @return array Filtered array of settings sections.
     *
     */
    public function register_settings_section( $settings_sections ) {

        if ( array_key_exists( 'tap_mothership_settings_section', $settings_sections ) ) {
            return $settings_sections;
        }

        $settings_sections['tap_mothership_settings_section'] = array(
            'title' => __( 'License', 'thirstyaffiliates-pro' ),
            'desc'  => sprintf( __( 'Enter the license key given to you after purchasing ThirstyAffiliates Pro. You can find this information by logging into your <a href="%1$s" target="_blank">Account</a> on our website or in the purchase confirmation email sent to your email address.', 'thirstyaffiliates-pro' ), "https://thirstyaffiliates.com/account" )
        );

        return $settings_sections;

    }

    /**
     * Register mothership settings section options.
     *
     * @param array $settings_section_options Array of options per settings sections.
     *
     * @return array Filtered array of options per settings sections.
     *
     */
    public function register_settings_section_options( $settings_section_options ) {

        if ( array_key_exists( 'tap_mothership_settings_section', $settings_section_options ) ) {
            return $settings_section_options;
        }

        $settings_section_options['tap_mothership_settings_section'] = apply_filters(
            'tap_mothership_settings_section_options',
            array(
                array(
                    'id'    => 'tap_mothership_activation',
                    'type'  => 'mothership_activation',
                    'title' => '',
                    'desc'  => ''
                )
            )
        );

        return $settings_section_options;

    }

    /**
     * Register mothership Activation Field
     *
     * @param array $supported_field_types Array of all supported field types for the WPB Settings API.
     *
     * @return array Filtered array of supported field types.
     *
     */
    public function register_activation_field( $supported_field_types ) {

        if ( array_key_exists( 'mothership_activation', $supported_field_types ) ) {
            return $supported_field_types;
        }

        $supported_field_types['mothership_activation'] = function () {
            $this->generate_activation_table_rows();
        };

        return $supported_field_types;
    }

    /**
     * AJAX activate license for this site.
     */
    public function ajax_activate_license() {

        if ( ! wp_doing_ajax() ) {
            wp_send_json_error( __( 'Invalid AJAX Operation', 'thirstyaffiliates-pro' ) );
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to do this', 'thirstyaffiliates-pro' ) );
        } elseif ( ! isset( $_POST['license-key'] ) || ! isset( $_POST['_ajax_nonce'] ) ) {
            wp_send_json_error( __( 'Required parameters not supplied', 'thirstyaffiliates-pro' ) );
        } elseif ( ! check_ajax_referer( 'tap_activate_license', false, false ) ) {
            wp_send_json_error( __( 'Security check failed', 'thirstyaffiliates-pro' ) );
        }

        $license_key = is_string( $_POST['license-key'] ) ? sanitize_text_field( wp_unslash( $_POST['license-key'] ) ) : '';
        $activation_email = isset( $_POST['activation-email'] ) && is_string( $_POST['activation-email'] ) ? sanitize_text_field( wp_unslash( $_POST['activation-email'] ) ) : '';

        if ( $this->_helper_functions->is_legacy_license_key( $license_key ) ) {
            $result = $this->_helper_functions->get_mothership_key_from_legacy_key( $license_key, $activation_email );

            if ( $result['status'] == 'success' ) {
                $license_key = $result['license_key'];
            } else {
                wp_send_json_error( $result['error_msg'] );
            }
        }

        try {

            $this->_update->activate_license( $license_key );
            $license = get_site_transient( 'tap_license_info' );
            $output = sprintf( '<div class="notice notice-success"><p>%s</p></div>', esc_html__( 'The license was successfully activated.', 'thirstyaffiliates-pro' ) );

            if ( is_array( $license ) ) {
                $editions = $this->_helper_functions->is_incorrect_edition_installed();

                if ( is_array( $editions ) && $editions['license']['index'] > $editions['installed']['index'] ) {
                    // The installed plugin is a lower edition, try to upgrade to the higher license edition
                    if ( ! empty( $license['url'] ) && $this->_helper_functions->is_url( $license['url'] ) ) {
                        $result = $this->_helper_functions->install_plugin_silently( $license['url'], array( 'overwrite_package' => true ) );

                        if ( $result === true ) {
                            do_action( 'tap_plugin_edition_changed' );
                            wp_send_json_success( true );
                        }
                    }
                }

                $edge_updates = $this->_helper_functions->get_option( Plugin_Constants::OPTION_EDGE_UPDATES ) === 'yes';
                $site_domain = $this->_helper_functions->get_site_domain();

                ob_start();
                require $this->_constants->VIEWS_ROOT_PATH() . 'license/active_license.php';
                $output .= ob_get_clean();
            } else {
                $output .= sprintf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html__( 'The license information is not available, try refreshing the page.', 'thirstyaffiliates-pro' ) );
            }

            wp_send_json_success( $output );

        } catch ( \Exception $e ) {

            try {
                $expires = $this->_helper_functions->send_mothership_request( "/license_keys/expires_at/$license_key" );

                if ( isset( $expires['expires_at'] ) ) {
                    $expires_at = strtotime( $expires['expires_at'] );

                    if ( $expires_at && $expires_at < time() ) {
                        $licenses = $this->_helper_functions->send_mothership_request( "/license_keys/list_keys/$license_key" );

                        if ( ! empty( $licenses ) && is_array( $licenses ) ) {
                            $highest_edition_index = - 1;
                            $highest_license = null;

                            foreach ( $licenses as $license ) {
                                $edition = $this->_helper_functions->get_edition( $license['product_slug'] );

                                if ( is_array( $edition ) && $edition['index'] > $highest_edition_index ) {
                                    $highest_edition_index = $edition['index'];
                                    $highest_license = $license;
                                }
                            }

                            if ( is_array( $highest_license ) ) {
                                wp_send_json_error(
                                    sprintf(
                                        /* translators: %1$s: the product name, %2$s: open link tag, %3$s: close link tag */
                                        esc_html__( 'This License Key has expired, but you have an active license for %1$s, %2$sclick here%3$s to activate using this license instead.', 'thirstyaffiliates-pro' ),
                                        '<strong>' . esc_html( $highest_license['product_name'] ) . '</strong>',
                                        sprintf( '<a href="#" id="tap-activate-new-license" data-license-key="%s">', esc_attr( $highest_license['license_key'] ) ),
                                        '</a>'
                                    )
                                );
                            }
                        }
                    }
                }
            } catch ( \Exception $ignore ) {
                // Nothing we can do, let it fail.
            }

            wp_send_json_error( $e->getMessage() );

        }

    }

    /**
     * AJAX deactivate license for this site.
     */
    public function ajax_deactivate_license() {

        if ( ! wp_doing_ajax() ) {
            wp_send_json_error( __( 'Invalid AJAX Operation', 'thirstyaffiliates-pro' ) );
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to do this', 'thirstyaffiliates-pro' ) );
        } elseif ( ! isset( $_POST['_ajax_nonce'] ) ) {
            wp_send_json_error( __( 'Required parameters not supplied', 'thirstyaffiliates-pro' ) );
        } elseif ( ! check_ajax_referer( 'tap_deactivate_license', false, false ) ) {
            wp_send_json_error( __( 'Security check failed', 'thirstyaffiliates-pro' ) );
        }

        $this->_update->deactivate_license();

        $output = sprintf( '<div class="notice notice-success"><p>%s</p></div>', esc_html__( 'The license was successfully deactivated.', 'thirstyaffiliates-pro' ) );

        ob_start();
        require $this->_constants->VIEWS_ROOT_PATH() . 'license/inactive_license.php';
        $output .= ob_get_clean();

        wp_send_json_success( $output );

    }

    /**
     * AJAX install license edition.
     */
    public function ajax_install_license_edition() {

        if ( ! wp_doing_ajax() ) {
            wp_send_json_error( __( 'Invalid AJAX Operation', 'thirstyaffiliates-pro' ) );
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to do this', 'thirstyaffiliates-pro' ) );
        } elseif ( ! isset( $_POST['_ajax_nonce'] ) ) {
            wp_send_json_error( __( 'Required parameters not supplied', 'thirstyaffiliates-pro' ) );
        } elseif ( ! check_ajax_referer( 'tap_install_license_edition', false, false ) ) {
            wp_send_json_error( __( 'Security check failed', 'thirstyaffiliates-pro' ) );
        }

        $li = get_site_transient( 'tap_license_info' );

        if ( ! empty( $li ) && is_array( $li ) && ! empty( $li['url'] ) && $this->_helper_functions->is_url( $li['url'] ) ) {
            $result = $this->_helper_functions->install_plugin_silently( $li['url'], array( 'overwrite_package' => true ) );

            if ( $result instanceof \WP_Error ) {
                wp_send_json_error( $result->get_error_message() );
            } elseif ( $result === true ) {
                do_action( 'tap_plugin_edition_changed' );

                wp_send_json_success( __( 'The correct edition of ThirstyAffiliates Pro has been installed successfully.', 'thirstyaffiliates-pro' ) );
            } else {
                wp_send_json_error( __( 'Failed to install the correct edition of ThirstyAffiliates Pro, please download it from thirstyaffiliates.com and install it manually.', 'thirstyaffiliates-pro' ) );
            }
        } else {
            wp_send_json_error( __( 'License data not found', 'thirstyaffiliates-pro' ) );
        }

    }

    /**
     * AJAX dismiss activate notice.
     */
    public function ajax_dismiss_activate_notice() {

        if ( !defined( "DOING_AJAX" ) || !DOING_AJAX )
            $response = array( 'status' => 'fail' , 'error_msg' => __( 'Invalid AJAX Operation' , 'thirstyaffiliates-pro' ) );
        else {

            $this->_helper_functions->update_option( 'tap_activation_notice_dismissed' , 'yes' );

            $response = array( 'status' => 'success' );
        }

        @header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo wp_json_encode( $response );
        wp_die();

    }

    /**
     * AJAX toggle edge updates.
     */
    public function ajax_toggle_edge_updates() {

        if ( ! wp_doing_ajax() ) {
            wp_send_json_error( __( 'Invalid AJAX Operation', 'thirstyaffiliates-pro' ) );
        } elseif ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to do this', 'thirstyaffiliates-pro' ) );
        } elseif ( ! isset( $_POST['edge-updates'] ) ) {
            wp_send_json_error( __( 'Required parameters not supplied', 'thirstyaffiliates-pro' ) );
        } elseif ( ! check_ajax_referer( 'tap_toggle_edge_updates', false, false ) ) {
            wp_send_json_error( __( 'Security check failed', 'thirstyaffiliates-pro' ) );
        }

        $edge_updates = $_POST[ 'edge-updates' ] === '1';

        if ( $edge_updates ) {
            $this->_helper_functions->update_option( Plugin_Constants::OPTION_EDGE_UPDATES, 'yes' );
        } else {
            $this->_helper_functions->delete_option( Plugin_Constants::OPTION_EDGE_UPDATES );
        }

        wp_send_json_success();

    }

    /**
     * Execute plugin script loader.
     *
     * @implements \ThirstyAffiliates\Interfaces\Initiable_Interface
     */
    public function initialize() {

        add_action( 'wp_ajax_tap_mothership_activate_license', array( $this, 'ajax_activate_license' ) );
        add_action( 'wp_ajax_tap_mothership_deactivate_license', array( $this, 'ajax_deactivate_license' ) );
        add_action( 'wp_ajax_tap_install_license_edition', array( $this, 'ajax_install_license_edition' ) );
        add_action( 'wp_ajax_tap_dismiss_activation_notice', array( $this, 'ajax_dismiss_activate_notice' ) );
        add_action( 'wp_ajax_tap_toggle_edge_updates', array( $this, 'ajax_toggle_edge_updates' ) );

    }

    /**
     * Execute plugin script loader.
     *
     * @implements \ThirstyAffiliates\Interfaces\Model_Interface
     */
    public function run() {

        if ( is_multisite() ) {

            // Add Mothership Settings In Multi-Site Environment
            add_action( 'network_admin_menu', array( $this, 'register_mothership_settings_menu' ) );

        } else {

            add_filter( 'ta_supported_field_types', array( $this, 'register_activation_field' ) );
            add_filter( 'ta_settings_option_sections', array( $this, 'register_settings_section' ) );
            add_filter( 'ta_settings_section_options', array( $this, 'register_settings_section_options' ) );

        }

    }
}
