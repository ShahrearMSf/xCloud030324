<?php
require_once ( 'Helpers/Plugin_Constants.php' );
use ThirstyAffiliates_Pro\Helpers\Plugin_Constants;

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

/**
 * Function that houses the code that cleans up the plugin on un-installation.
 *
 * @since 1.0.0
 * @since 1.6 Refactor codebase to improve support for multisite installs.
 */
function tap_plugin_cleanup() {

    if ( is_multisite() ) {

        delete_site_option( Plugin_Constants::OPTION_ACTIVATION_EMAIL );
        delete_site_option( Plugin_Constants::OPTION_LICENSE_KEY );
        delete_site_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED );
        delete_site_option( Plugin_Constants::INSTALLED_VERSION );

    } else {

        delete_option( Plugin_Constants::OPTION_ACTIVATION_EMAIL );
        delete_option( Plugin_Constants::OPTION_LICENSE_KEY );
        delete_option( Plugin_Constants::OPTION_LICENSE_ACTIVATED );
        delete_option( Plugin_Constants::INSTALLED_VERSION );

    }

    if ( get_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS , false ) == 'yes' ) {

        // Help settings section options
        delete_option( Plugin_Constants::CLEAN_UP_PLUGIN_OPTIONS );

    }

}

if ( function_exists( 'is_multisite' ) && is_multisite() ) {

    global $wpdb;

    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

    foreach ( $blog_ids as $blog_id ) {

        switch_to_blog( $blog_id );
        tap_plugin_cleanup();

    }

    restore_current_blog();

    return;

} else
    tap_plugin_cleanup();
