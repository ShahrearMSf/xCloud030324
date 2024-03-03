<?php
/**
 * @deprecated 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

wp_nonce_field( 'ta_disable_autolinker_nonce', '_disable_autolinker_nonce' ); ?>

<p>
    <label class="info-label">
        <input type="checkbox" id="tap_disable_autolinker" name="tap_disable_autolinker" value="on" <?php checked( $disable_autolinker , 'on' ) ?>>
        <?php _e( 'Disable autolinker for this post?' , 'thirstyaffiliates-pro' ); ?>
    </label>
</p>
