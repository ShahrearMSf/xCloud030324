<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>


<div class="save-link-performance-report report-action">

    <div class="input-wrap report-name-field">
        <input type="text" name="link_performance_report_name" value="" placeholder="<?php _e( 'Enter report name...' , 'thirstyaffiliates-pro' ); ?>">
    </div>

    <div class="button-wrap">
        <button type="button" class="button-primary" id="save-link-performance-report"><?php _e( 'Save Report' , 'thirstyaffiliates-pro' ); ?></button>
    </div>

    <input type="hidden" name="action" value="tap_save_link_performance_report">
    <?php wp_nonce_field( 'tap_save_link_performance_report_nonce' ); ?>

</div>
