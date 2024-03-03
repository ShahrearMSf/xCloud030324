<?php

namespace ThirstyAffiliates_Pro\Models\Mothership;

use ThirstyAffiliates_Pro\Abstracts\Abstract_Main_Plugin_Class;

use ThirstyAffiliates_Pro\Interfaces\Model_Interface;
use ThirstyAffiliates_Pro\Interfaces\Deactivatable_Interface;

use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;
use ThirstyAffiliates_Pro\Helpers\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class Update implements Model_Interface, Deactivatable_Interface {

    /**
     * Property that holds the single main instance of Update.
     *
     * @var Update
     */
    private static $_instance;

    /**
     * Model that houses all the plugin constants.
     *
     * @var Plugin_Constants
     */
    private $_constants;

    /**
     * Property that houses all the helper functions of the plugin.
     *
     * @var Helper_Functions
     */
    private $_helper_functions;

    /**
     * Class constructor.
     *
     * @param Abstract_Main_Plugin_Class $main_plugin Main plugin object.
     * @param Plugin_Constants $constants Plugin constants object.
     * @param Helper_Functions $helper_functions Helper functions object.
     */
    public function __construct( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {
        $this->_constants        = $constants;
        $this->_helper_functions = $helper_functions;

        $main_plugin->add_to_all_plugin_models( $this );
    }

    /**
     * Ensure that only one instance of this class is loaded or can be loaded ( Singleton Pattern ).
     *
     * @param Abstract_Main_Plugin_Class $main_plugin      Main plugin object.
     * @param Plugin_Constants           $constants        Plugin constants object.
     * @param Helper_Functions           $helper_functions Helper functions object.
     *
     * @return Update
     */
    public static function get_instance( Abstract_Main_Plugin_Class $main_plugin, Plugin_Constants $constants, Helper_Functions $helper_functions ) {
        if ( ! self::$_instance instanceof self ) {
            self::$_instance = new self( $main_plugin, $constants, $helper_functions );
        }

        return self::$_instance;
    }

    /**
     * Has the plugin license been activated?
     *
     * @return bool
     */
    public function is_activated() {
        $license_key = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_KEY );
        $activated = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED );

        if ( $activated === 'no' ) {
            $activated = false;
        }

        return !empty($license_key) && !empty($activated);
    }

    /**
     * Check if the license is valid.
     *
     * @return void
     */
    public function check_license_activation() {
        $aov = get_option( 'tap_activation_override' );

        if ( ! empty( $aov ) ) {
            $this->_helper_functions->update_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED, true );
            do_action( 'tap_license_activated', array( 'aov' => 1 ) );

            return;
        }

        $license_key = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_KEY );

        if ( empty( $license_key ) ) {
            return;
        }

        if ( $this->_helper_functions->is_legacy_license_key( $license_key ) ) {
            return;
        }

        // Only check the key once per day
        $option_key = "tap_license_check_{$license_key}";

        if ( get_site_transient( $option_key ) ) {
            return;
        }

        $check_count = $this->_helper_functions->get_option( $option_key, 0 ) + 1;
        $this->_helper_functions->update_option( $option_key, $check_count );

        set_site_transient( $option_key, true, ( $check_count > 3 ? 72 : 24 ) * HOUR_IN_SECONDS );

        $domain = urlencode( $this->_helper_functions->get_site_domain() );
        $args = compact( 'domain' );

        try {
            $act = $this->_helper_functions->send_mothership_request( "/license_keys/check/{$license_key}", $args );

            if ( ! empty( $act ) && is_array( $act ) ) {
                $license_expired = false;

                if ( isset( $act['expires_at'] ) ) {
                    $expires_at = strtotime( $act['expires_at'] );

                    if ( $expires_at && $expires_at < time() ) {
                        $license_expired = true;
                        $this->_helper_functions->update_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED, false );
                        do_action( 'tap_license_expired', $act );
                    }
                }

                if ( isset( $act['status'] ) && ! $license_expired ) {
                    if ( $act['status'] == 'enabled' ) {
                        $this->_helper_functions->update_option( $option_key, 0 );
                        $this->_helper_functions->update_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED, true );
                        do_action( 'tap_license_activated', $act );
                    } elseif ( $act['status'] == 'disabled' ) {
                        $this->_helper_functions->update_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED, false );
                        do_action( 'tap_license_invalidated', $act );
                    }
                }
            }
        } catch ( \Exception $e ) {
            if ( $e->getMessage() == 'Not Found' ) {
                $this->_helper_functions->update_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED, false );
                do_action( 'tap_license_invalidated' );
            }
        }
    }

    /**
     * Check the license activation status.
     *
     * @return void
     */
    public function maybe_activate() {
        $activated = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED );

        if ( $activated === 'no' ) {
            $activated = false;
        }

        if ( ! $activated ) {
            $this->check_license_activation();
        }
    }

    /**
     * Activate the license from a PHP define.
     */
    public function activate_from_define() {
        $license_key = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_KEY );

        if($this->_helper_functions->is_legacy_license_key($license_key)) {
            return;
        }

        if ( defined( 'THIRSTYAFFILIATES_PRO_LICENSE_KEY' ) && $license_key != THIRSTYAFFILIATES_PRO_LICENSE_KEY ) {
            try {
                if ( ! empty( $license_key ) ) {
                    // Deactivate the old license key
                    $this->deactivate_license();
                }

                // If we're using defines then we have to do this with defines too
                $this->_helper_functions->delete_option( Plugin_Constants::OPTION_EDGE_UPDATES );

                $act = $this->activate_license( THIRSTYAFFILIATES_PRO_LICENSE_KEY );

                $message = $act['message'];
                $callback = function () use ( $message ) {
                    printf(
                        '<div class="notice notice-success"><p>%s</p></div>',
                        esc_html( $message )
                    );
                };
            } catch ( \Exception $e ) {
                $error = $e->getMessage();
                $callback = function () use ( $error ) {
                    printf(
                        '<div class="notice notice-error"><p>%s</p></div>',
                        esc_html( sprintf( __( 'Error with THIRSTYAFFILIATES_PRO_LICENSE_KEY: %s', 'thirstyaffiliates-pro' ), $error ) )
                    );
                };
            }

            if ( is_multisite() ) {
                add_action( 'network_admin_notices', $callback );
            } else {
                add_action( 'admin_notices', $callback );
            }

        }
    }

    /**
     * Activate the license with the given key
     *
     * @param string $license_key The license key
     * @return array The license data
     * @throws \Exception If there was an error activating the license
     */
    public function activate_license( $license_key ) {

        $args = array(
            'domain' => urlencode( $this->_helper_functions->get_site_domain() ),
            'product' => TAP_EDITION,
        );

        $act = $this->_helper_functions->send_mothership_request( "/license_keys/activate/{$license_key}", $args, 'post' );

        $this->_helper_functions->update_option( Plugin_Constants::OPTION_LICENSE_KEY, $license_key );

        $option_key = "tap_license_check_{$license_key}";
        delete_site_transient( $option_key );
        delete_option( $option_key );

        delete_site_transient( 'tap_update_info' );

        do_action( 'tap_license_activated_before_queue_update' );

        $this->manually_queue_update();

        // Clear the add-ons cache
        delete_site_transient( 'tap_addons' );
        delete_site_transient( 'tap_all_addons' );

        do_action( 'tap_license_activated', $act );

        return $act;

    }

    /**
     * Deactivate the license
     *
     * @return array
     */
    public function deactivate_license() {
        $license_key = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_KEY );
        $act = array( 'message' => __( 'License key deactivated', 'thirstyaffiliates-pro' ) );

        if ( ! empty( $license_key ) && ! $this->_helper_functions->is_legacy_license_key( $license_key ) ) {
            try {
                $args = array(
                    'domain' => urlencode( $this->_helper_functions->get_site_domain() ),
                );

                $act = $this->_helper_functions->send_mothership_request( "/license_keys/deactivate/{$license_key}", $args, 'post' );
            } catch ( \Exception $e ) {
                // Catching here to allow invalid license keys to be deactivated
            }
        }

        $this->_helper_functions->delete_option( Plugin_Constants::OPTION_LICENSE_KEY );

        $option_key = "tap_license_check_{$license_key}";
        delete_site_transient( $option_key );
        delete_option( $option_key );

        delete_site_transient( 'tap_update_info' );

        do_action( 'tap_license_deactivated_before_queue_update' );

        $this->manually_queue_update();

        // Don't need to check the mothership for this one ... we just deactivated
        $this->_helper_functions->delete_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED );

        // Clear the cache of the license and add-ons
        delete_site_transient( 'tap_license_info' );
        delete_site_transient( 'tap_addons' );
        delete_site_transient( 'tap_all_addons' );

        do_action( 'tap_license_deactivated', $act );

        return $act;
    }

    /**
     * Inject our plugin update into the WP plugin update transient.
     *
     * @param \stdClass $transient The WP plugin update transient object.
     * @param false     $force     Whether to force the update check.
     *
     * @return \stdClass
     */
    public function queue_update( $transient, $force = false ) {
        $license_key = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_KEY );

        if ( $this->_helper_functions->is_legacy_license_key( $license_key ) ) {
            if ( false === get_site_transient( 'tap_legacy_update_checked' ) ) {
                // Attempt this at most once per hour
                set_site_transient( 'tap_legacy_update_checked', '1', HOUR_IN_SECONDS );

                $activation_email = $this->_helper_functions->get_option( Plugin_Constants::OPTION_ACTIVATION_EMAIL );

                $result = $this->_helper_functions->get_mothership_key_from_legacy_key( $license_key, $activation_email );

                if ( $result['status'] == 'success' ) {
                    $license_key = $result['license_key'];

                    $this->_helper_functions->update_option( Plugin_Constants::OPTION_LICENSE_KEY, $license_key );

                    $activate_args = array(
                        'domain' => urlencode( $this->_helper_functions->get_site_domain() ),
                        'product' => TAP_EDITION,
                    );

                    try {
                        $this->_helper_functions->send_mothership_request( "/license_keys/activate/{$license_key}", $activate_args, 'post' );
                    } catch ( \Exception $e ) {
                        // ignore
                    }

                    // Check for updates using the new key
                    delete_site_transient( 'tap_update_info' );
                } else {
                    // Failed to get mothership license key, bail
                    return $transient;
                }
            } else {
                // Attempted to migrate already in the last hour, bail
                return $transient;
            }
        }

        if ( $force || ( false === ( $update_info = get_site_transient( 'tap_update_info' ) ) ) ) {
            $args = array();

            $edge_updates = $this->_helper_functions->get_option( Plugin_Constants::OPTION_EDGE_UPDATES );

            if ( $edge_updates || ( defined( 'THIRSTYAFFILIATES_PRO_EDGE_UPDATES' ) && THIRSTYAFFILIATES_PRO_EDGE_UPDATES ) ) {
                $args['edge'] = 'true';
            }

            if ( empty( $license_key ) ) {
                try {
                    // Just here to query for the current version
                    $version_info = $this->_helper_functions->send_mothership_request( '/versions/latest/' . TAP_EDITION, $args );
                    $curr_version = $version_info['version'];
                    $download_url = '';
                } catch ( \Exception $e ) {
                    if ( isset( $transient->response[ $this->_constants->PLUGIN_BASENAME() ] ) ) {
                        unset( $transient->response[ $this->_constants->PLUGIN_BASENAME() ] );
                    }

                    return $transient;
                }
            } else {
                try {
                    $args['domain'] = urlencode( $this->_helper_functions->get_site_domain() );

                    $license_info = $this->_helper_functions->send_mothership_request( "/versions/info/{$license_key}", $args );
                    $curr_version = $license_info['version'];
                    $download_url = $license_info['url'];

                    set_site_transient( 'tap_license_info', $license_info, 24 * HOUR_IN_SECONDS );

                    if ( $this->_helper_functions->is_incorrect_edition_installed() ) {
                        $download_url = '';
                    }
                } catch ( \Exception $e ) {
                    try {
                        // Just here to query for the current version
                        $version_info = $this->_helper_functions->send_mothership_request( '/versions/latest/' . TAP_EDITION, $args );
                        $curr_version = $version_info['version'];
                        $download_url = '';
                    } catch ( \Exception $e ) {
                        if ( isset( $transient->response[ $this->_constants->PLUGIN_BASENAME() ] ) ) {
                            unset( $transient->response[ $this->_constants->PLUGIN_BASENAME() ] );
                        }

                        $this->check_license_activation();
                        return $transient;
                    }
                }
            }

            set_site_transient(
                'tap_update_info',
                compact( 'curr_version', 'download_url' ),
                12 * HOUR_IN_SECONDS
            );
        } else {
            $curr_version = isset( $update_info['curr_version'] ) ? $update_info['curr_version'] : Plugin_Constants::VERSION;
            $download_url = isset( $update_info['download_url'] ) ? $update_info['download_url'] : '';
        }

        if ( isset( $curr_version ) && version_compare( $curr_version, Plugin_Constants::VERSION, '>' ) ) {
            global $wp_version;

            $transient->response[ $this->_constants->PLUGIN_BASENAME() ] = (object) [
                'id'          => $this->_constants->PLUGIN_BASENAME(),
                'slug'        => $this->_constants->PLUGIN_DIRNAME(),
                'plugin'      => $this->_constants->PLUGIN_BASENAME(),
                'new_version' => $curr_version,
                'url'         => 'https://thirstyaffiliates.com/',
                'package'     => $download_url,
                'tested'      => $wp_version
            ];
        } else {
            unset( $transient->response[ $this->_constants->PLUGIN_BASENAME() ] );

            // Enables the "Enable auto-updates" link
            $transient->no_update[ $this->_constants->PLUGIN_BASENAME() ] = (object) [
                'id'          => $this->_constants->PLUGIN_BASENAME(),
                'slug'        => $this->_constants->PLUGIN_DIRNAME(),
                'plugin'      => $this->_constants->PLUGIN_BASENAME(),
                'new_version' => Plugin_Constants::VERSION,
                'url'         => 'https://thirstyaffiliates.com/',
                'package'     => ''
            ];
        }

        $this->check_license_activation();
        return $transient;
    }

    /**
     * Manually queue a plugin update check.
     *
     * @return void
     */
    public function manually_queue_update() {
        $transient = get_site_transient( 'update_plugins' );
        set_site_transient( 'update_plugins', $this->queue_update( $transient, true ) );
    }

    /**
     * Display plugin information.
     *
     * @param false|object|array $api    The result object or array. Default false.
     * @param string             $action The type of information being requested from the Plugin Installation API.
     * @param object             $args   Plugin API arguments.
     * @return false|object
     */
    public function plugin_info($api, $action, $args) {
        if ( ! isset( $action ) || $action != 'plugin_information' ) {
            return $api;
        }

        if ( ! isset( $args->slug ) || $args->slug != $this->_constants->_PLUGIN_DIRNAME ) {
            return $api;
        }

        $args = array();

        $license_key = $this->_helper_functions->get_option( Plugin_Constants::OPTION_LICENSE_KEY );
        $edge_updates = $this->_helper_functions->get_option( Plugin_Constants::OPTION_EDGE_UPDATES );

        if ( $edge_updates || ( defined( 'THIRSTYAFFILIATES_PRO_EDGE_UPDATES' ) && THIRSTYAFFILIATES_PRO_EDGE_UPDATES ) ) {
            $args['edge'] = 'true';
        }

        if ( empty( $license_key ) || $this->_helper_functions->is_legacy_license_key( $license_key ) ) {
            try {
                // Just here to query for the current version
                $version_info = $this->_helper_functions->send_mothership_request( '/versions/latest/' . TAP_EDITION, $args );
                $curr_version = $version_info['version'];
                $version_date = $version_info['version_date'];
                $download_url = '';
            } catch ( \Exception $e ) {
                return $api;
            }
        } else {
            try {
                $args['domain'] = urlencode( $this->_helper_functions->get_site_domain() );

                $license_info = $this->_helper_functions->send_mothership_request( "/versions/info/{$license_key}", $args );
                $curr_version = $license_info['version'];
                $version_date = $license_info['version_date'];
                $download_url = $license_info['url'];

                if ( $this->_helper_functions->is_incorrect_edition_installed() ) {
                    $download_url = '';
                }
            } catch ( \Exception $e ) {
                try {
                    // Just here to query for the current version
                    $version_info = $this->_helper_functions->send_mothership_request( '/versions/latest/' . TAP_EDITION, $args );
                    $curr_version = $version_info['version'];
                    $version_date = $license_info['version_date'];
                    $download_url = '';
                } catch ( \Exception $e ) {
                    return $api;
                }
            }
        }

        global $wp_version;

        return (object) array(
            'slug' => $this->_constants->_PLUGIN_DIRNAME,
            'name' => 'ThirstyAffiliates Pro',
            'author' => tap_get_plugin_info( 'AuthorName' ),
            'author_profile' => tap_get_plugin_info( 'AuthorURI' ),
            'contributors' => array(
                'caseproof' => array(
                    'profile' => tap_get_plugin_info( 'AuthorURI' ),
                    'avatar' => 'https://secure.gravatar.com/avatar/762b61e36276ff6dc0d7b03b8c19cfab?s=96&d=monsterid&r=g',
                    'display_name' => tap_get_plugin_info( 'AuthorName' ),
                ),
            ),
            'homepage' => tap_get_plugin_info( 'PluginURI' ),
            'version' => TAP_VERSION,
            'new_version' => $curr_version,
            'requires' => '5.0',
            'tested' => $wp_version,
            'compatibility' => array( $wp_version => array( $curr_version => array( 100, 0, 0 ) ) ),
            'last_updated' => $version_date,
            'sections' => array(
                'description' => '<p>' . tap_get_plugin_info( 'Description' ) . '</p>',
                'faq' => '<p>' . sprintf( esc_html__( 'You can access in-depth information about ThirstyAffiliates Pro at %1$sthe ThirstyAffiliates Knowledge Base%2$s.', 'thirstyaffiliates-pro' ), '<a href="https://thirstyaffiliates.com/knowledge-base">', '</a>' ) . '</p>',
                'changelog' => '<p>' . sprintf( esc_html__( 'See the %1$splugin changelog%2$s.', 'thirstyaffiliates-pro' ), '<a href="https://thirstyaffiliates.com/thirstyaffiliates-pro-changelog">', '</a>' ) . '</p>',
            ),
            'download_link' => $download_url,
            'icons' => array(
                '1x' => 'https://ps.w.org/thirstyaffiliates/assets/pro-icon-128x128.jpg',
                '2x' => 'https://ps.w.org/thirstyaffiliates/assets/pro-icon-256x256.jpg',
                'default' => 'https://ps.w.org/thirstyaffiliates/assets/pro-icon-256x256.jpg',
            ),
            'banners' => array(
                'low'  => 'https://ps.w.org/thirstyaffiliates/assets/pro-banner-772x250.jpg',
                'high' => 'https://ps.w.org/thirstyaffiliates/assets/pro-banner-1544x500.jpg',
            ),
        );
    }

    /**
     * Activate license notice.
     */
    public function activation_warning() {

        if ( $this->is_activated() ) {
            return;
        }

        if ( is_multisite() && isset($_GET['page']) && $_GET['page'] == 'tap-ms-license-settings') {
            return;
        }
        elseif ( ! is_multisite() && isset( $_GET['page'], $_GET['tab'] ) && $_GET['page'] == 'thirsty-settings' && $_GET['tab'] == 'tap_mothership_settings_section' ) {
            return;
        }

        $screen      = get_current_screen();
        $dismissible = ( strpos( $screen->id , 'thirstylink' ) === false && $screen->id !== 'edit-tap-event-notification' ) ? 'is-dismissible' : '';

        // When notice is dismissed on non-TA pages, then don't show it anymore.
        if ( $dismissible && get_option( 'tap_activation_notice_dismissed' ) == 'yes' ) {
            return;
        }

        if ( is_multisite() ) {
            $license_settings_url = network_admin_url('admin.php?page=tap-ms-license-settings');
        } else {
            $license_settings_url = admin_url('edit.php?post_type=thirstylink&page=thirsty-settings&tab=tap_mothership_settings_section');
        }

        ?>

        <div class="notice notice-error <?php echo $dismissible; ?> tap-activate-license-notice">
            <p class="tap-activate-license-notice">
                <?php echo sprintf( __( 'Please <a href="%1$s">activate</a> your copy of ThirstyAffiliates Pro to get the latest updates and have access to support.' , 'thirstyaffiliates-pro' ) , esc_url( $license_settings_url ) ); ?>
            </p>
        </div>

        <?php if ( $dismissible ) { ?>

            <script>
                jQuery( document ).ready( function( $ ) {

                    $( '.tap-activate-license-notice' ).on( 'click' , '.notice-dismiss' , function() {
                        $.post( window.ajaxurl, { action : 'tap_dismiss_activation_notice' } );
                    } );

                } );
            </script>

        <?php }

    }

    /**
     * Explain how to restore automatic updates if the incorrect edition is installed.
     */
    public function check_incorrect_edition() {
        if ($this->_helper_functions->is_incorrect_edition_installed()) {
            if ( is_multisite() ) {
                $license_settings_url = network_admin_url('admin.php?page=tap-ms-license-settings');
            } else {
                $license_settings_url = admin_url('edit.php?post_type=thirstylink&page=thirsty-settings&tab=tap_mothership_settings_section');
            }

            printf(
                /* translators: %1$s: open link tag, %2$s: close link tag */
                ' <strong>' . esc_html__('To restore automatic updates, %1$sinstall the correct edition%2$s of ThirstyAffiliates Pro.', 'thirstyaffiliates-pro') . '</strong>',
                sprintf('<a href="%s">', esc_url($license_settings_url)),
                '</a>'
            );
        }
    }

    /**
     * Clear the update transients.
     */
    public function clear_update_transients() {
        delete_site_transient('update_plugins');
        delete_site_transient('tap_update_info');
        delete_site_transient('tap_addons');
        delete_site_transient('tap_all_addons');
    }

    /**
     * Execute code base that needs to be run on plugin deactivation.
     *
     * @implements \ThirstyAffiliates\Interfaces\Deactivatable_Interface
     */
    public function deactivate() {

        // Delete plugin update data
        delete_site_transient( 'tap_update_info' );
        delete_site_transient( 'tap_legacy_update_info' );
        delete_site_transient( 'tap_legacy_update_checked' );

    }

    /**
     * Execute Model.
     *
     * @implements \ThirstyAffiliates\Interfaces\Model_Interface
     */
    public function run() {

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'queue_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
        add_action( "in_plugin_update_message-{$this->_constants->_PLUGIN_BASENAME}", array( $this, 'check_incorrect_edition' ) );
        add_action( 'tap_plugin_edition_changed', array( $this, 'clear_update_transients' ) );
        add_action( 'admin_init', array( $this, 'activate_from_define' ) );
        add_action( 'admin_init', array( $this, 'maybe_activate' ) );

        if ( is_multisite() ) {
            add_action( 'network_admin_notices', array( $this, 'activation_warning' ) );
        } else {
            add_action( 'admin_notices', array( $this, 'activation_warning' ) );
        }

    }
}
