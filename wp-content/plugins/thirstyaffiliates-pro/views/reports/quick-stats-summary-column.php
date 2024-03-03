<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="stats-summary-wrap">
    <span class="total">
        <?php echo sprintf( __( 'Total clicks: <strong>%s</strong>' , 'thirstyaffiliates-pro' ) , $total_clicks ); ?>
    </span>
    <span class="week">
        <?php echo sprintf( __( 'Last 7 days: <strong>%s</strong>' , 'thirstyaffiliates-pro' ) , $week_clicks ); ?>
    </span>
    <span class="month">
        <?php echo sprintf( __( 'Last 30 days: <strong>%s</strong>' , 'thirstyaffiliates-pro' ) , $month_clicks ); ?>
    </span>
</div>
