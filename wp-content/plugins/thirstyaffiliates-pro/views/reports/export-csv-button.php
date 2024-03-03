<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<li class="export-csv-button" data-report-type="<?php echo esc_attr( $report_type ); ?>">
    <a href="#" download="<?php echo 'report-' . $report_type . '-' . $today_date . '.csv'; ?>" class="export_csv" data-export="chart">
        <span class="dashicons dashicons-download"></span>
        <?php _e( 'Export CSV' , 'thirstyaffiliates-pro' ); ?>
    </a>
</li>
