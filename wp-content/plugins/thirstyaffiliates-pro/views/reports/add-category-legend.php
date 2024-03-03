<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="add-category-legend report-action">
    <label for="add-category-report-data"><?php _e( 'Fetch report for category:' , 'thirstyaffiliates-pro' ); ?></label>
    <div class="input-wrap">
        <select class="category-list" id="add-category-report-data"
            data-range="<?php echo esc_attr( $current_range ); ?>"
            data-start-date="<?php echo esc_attr( $start_date ); ?>"
            data-end-date="<?php echo esc_attr( $end_date ); ?>">
            <option value=""><?php _e( 'Select category' , 'thirstyaffiliates-pro' ); ?></option>
            <option value="ta_general_report" disabled><?php _e( 'General (All links)' , 'thirstyaffiliates-pro' ); ?></option>
            <?php foreach ( $categories as $category ) : ?>
                <option value="<?php echo $category->slug; ?>"><?php echo $category->name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="input-wrap link-report-color-field" style="display:none;">
        <input type="text" class="color-field" id="category-report-color" value="#8e33c6">
    </div>
    <button type="button" class="button-primary" id="fetch-category-report"><?php _e( 'Fetch Report' , 'thirstyaffiliates-pro' ); ?></button>
</div>
