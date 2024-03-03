<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="wrap">

    <table class="thirstyTable form-table" cellspacing="0" cellpadding="0">
    <tr>
            <td>
          <h3><?php echo esc_html( $redirector_title ); ?></h3>
          <p><?php echo sprintf( __( '<a href="%s">Click here</a> if you still see this page after 5 seconds.' , 'thirstyaffiliates-pro' ) , esc_url_raw( $redirector_url ) ); ?></p>
            </td>
    </tr>
  </table>
</div>

<script type="text/javascript">
window.location.assign("<?php echo esc_url_raw( $redirector_url ); ?>");
</script>
