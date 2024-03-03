<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<h3><?php _e( 'ThirstyAffiliates Automatic 404 Checker' , 'thirstyaffiliates-pro' ); ?></h3>

<p><?php _e( 'The scheduled link 404 check has been completed. Below are the results counter:' , 'thirstyaffiliates-pro' ); ?></p>

<ul>
    <li><?php _e( 'Okay:' , 'thirstyaffiliates-pro' ) ?> - <strong><?php echo $counter[ 'active' ]; ?></strong></li>
    <li><?php _e( 'Warning:' , 'thirstyaffiliates-pro' ) ?> - <strong><?php echo $counter[ 'warning' ]; ?></strong></li>
    <li><?php _e( 'Error:' , 'thirstyaffiliates-pro' ) ?> - <strong><?php echo $counter[ 'inactive' ] + $counter[ 'error' ]; ?></strong></li>
</ul>

<?php if ( $counter[ 'inactive' ] || $counter[ 'warning' ] || $counter[ 'error' ]  ) : ?>

<p><?php echo sprintf( __( 'Some issues were detected on your affiliate links. <a href="%s">Click here to view a more detailed report' , 'thirstyaffiliates-pro' ) , $issues_link ); ?></p>

<?php endif; ?>
