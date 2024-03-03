<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<table class="form-table">
    <tbody>
        <tr id="tap_slmw_activation_email-row" valign="top" style="display: none;">
            <th scope="row" class="titledesc">
                <label for="tap_slmw_activation_email"><?php esc_html_e( 'Activation Email' , 'thirstyaffiliates-pro' ); ?></label>
            </th>
            <td class="forminp forminp-text">
                <input type="text" id="tap_slmw_activation_email" class="regular-text ltr" />
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="tap_mothership_license_key"><?php esc_html_e( 'License Key', 'thirstyaffiliates-pro' ); ?></label>
            </th>
            <td class="forminp forminp-text">
                <input type="text" id="tap_mothership_license_key" class="regular-text ltr" />
                <button type="button" id="tap-activate-license-key" class="button button-primary"><?php esc_html_e( 'Activate License Key', 'thirstyaffiliates-pro' ); ?></button>
            </td>
        </tr>
    </tbody>
</table>
