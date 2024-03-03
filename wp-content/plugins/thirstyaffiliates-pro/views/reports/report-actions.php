<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="report-actions">

    <label><?php _e( 'Report actions' , 'thirstyaffiliates-pro' ); ?></label>
    <div class="input-wrap">
        <select class="report-actions-list" id="report-actions-list">
            <option value=""><?php _e( 'Select action' , 'thirstyaffiliates-pro' ); ?></option>
            <?php foreach ( $actions as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>