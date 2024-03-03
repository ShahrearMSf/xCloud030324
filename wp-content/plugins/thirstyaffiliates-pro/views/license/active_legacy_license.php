<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="tap-license-activated-notice" class="notice notice-success inline">
    <p>
        <?php
            echo esc_html( sprintf(
                /* translators: %s: the license key */
                __( 'License activated with key %s', 'thirstyaffiliates-pro' ),
                '***-********-****-****-****-******' . substr( $license_key, - 12 )
            ) );
        ?>
    </p>
</div>
<div id="tap-license-deactivate">
    <button type="button" class="button button-primary"><?php echo esc_html( sprintf( __( 'Deactivate License Key on %s', 'thirstyaffiliates-pro' ), $site_domain ) ); ?></button>
</div>
