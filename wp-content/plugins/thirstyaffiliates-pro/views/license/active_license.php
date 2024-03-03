<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<?php
    if ( ! isset( $editions ) ) {
        $editions = $this->_helper_functions->is_incorrect_edition_installed();
    }

    if ( is_array( $editions ) ) {
        printf(
            '<div class="notice notice-warning inline"><p>%1$s<img id="tap-install-license-edition-loading" src="%2$s" alt="%3$s"></p></div>',
            sprintf(
                /* translators: %1$s: the license edition, %2$s: the installed edition, %3$s: open link tag, %4$s: close link tag */
                esc_html__( 'This License Key is for %1$s, but %2$s is installed. %3$sClick here%4$s to install the correct edition for the license (%1$s).', 'thirstyaffiliates-pro' ),
                '<strong>' . esc_html( $editions['license']['name'] ) . '</strong>',
                '<strong>' . esc_html( $editions['installed']['name'] ) . '</strong>',
                '<a id="tap-install-license-edition" href="#">',
                '</a>'
            ),
            esc_url( $this->_constants->_IMAGES_ROOT_URL . '/square-loader.gif' ),
            esc_attr__( 'Loading...', 'thirstyaffiliates-pro' )
        );
    }
?>
<div id="tap-license-active">
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php esc_html_e( 'Status', 'thirstyaffiliates-pro' ); ?></th>
                <td>
                    <strong><?php echo esc_html( sprintf( __( 'Active on %s', 'thirstyaffiliates-pro' ), $site_domain ) ); ?></strong>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'License Key', 'thirstyaffiliates-pro' ); ?></th>
                <td>
                    ********-****-****-****-<?php echo esc_html( substr( $license['license_key']['license'], - 12 ) ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Product', 'thirstyaffiliates-pro' ); ?></th>
                <td><?php echo esc_html( $license['product_name'] ); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Activations', 'thirstyaffiliates-pro' ); ?></th>
                <td>
                    <?php
                    printf(
                        /* translators: %1$s: open strong tag, %2$d: activation count, %3$s: max activations, %4$s: close strong tag */
                        esc_html__( '%1$s%2$d of %3$s%4$s sites have been activated with this license key', 'thirstyaffiliates-pro' ),
                        '<strong>',
                        esc_html( $license['activation_count'] ),
                        esc_html( ucwords( $license['max_activations'] ) ),
                        '</strong>'
                    );
                    ?>
                </td>
            </tr>
        </tbody>
    </table>
    <div id="tap-license-deactivate">
        <button type="button" class="button button-primary"><?php echo esc_html( sprintf( __( 'Deactivate License Key on %s', 'thirstyaffiliates-pro' ), $site_domain ) ); ?></button>
    </div>
</div>

<table class="form-table">
    <tbody>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="tap_mothership_edge_updates"><?php esc_html_e( 'Edge Updates', 'thirstyaffiliates-pro' ); ?></label>
            </th>
            <td class="forminp forminp-text">
                <input type="checkbox" id="tap_mothership_edge_updates" <?php echo checked( $edge_updates ); ?> />
                <label for="tap_mothership_edge_updates"><?php esc_html_e( 'Include ThirstyAffiliates Pro edge (development) releases in automatic updates (not recommended on production sites)', 'thirstyaffiliates-pro' ); ?></label>
                <img id="tap_mothership_edge_updates-loading" style="vertical-align: middle; display: none;" src="<?php echo esc_url(admin_url('images/loading.gif')); ?>" alt="Loading...">
            </td>
        </tr>
    </tbody>
</table>
