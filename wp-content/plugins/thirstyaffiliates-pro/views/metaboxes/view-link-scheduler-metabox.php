<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<p class="link-start-date-field date-field">
    <label class="info-label" for="ta_link_start_date">
        <?php _e( 'Start date:' , 'thirstyaffiliates-pro' ); ?>
    </label>
    <input type="text" class="ta-form-input range_datepicker start" id="ta_link_start_date" name="ta_link_start_date" value="<?php echo esc_attr( $thirstylink->get_prop( 'link_start_date' ) ); ?>" placeholder="yyyy-mm-dd" pattern="(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))">
    <span class="dashicons dashicons-calendar-alt"></span>
</p>

<p class="link-before-start-redirect-field url-field">
    <label class="info-label" for="ta_before_start_redirect">
        <?php _e( 'Before start redirect URL:' , 'thirstyaffiliates-pro' ); ?>
    </label>
    <input type="url" class="ta-form-input" id="ta_before_start_redirect" name="ta_before_start_redirect" value="<?php echo esc_attr( $thirstylink->get_prop( 'before_start_redirect' ) ); ?>" placeholder="<?php echo esc_url( $global_before_start_redirect_url ); ?>">
</p>

<p class="link-expire-date-field date-field">
    <label class="info-label" for="ta_link_expire_date">
        <?php _e( 'Expire date:' , 'thirstyaffiliates-pro' ); ?>
    </label>
    <span class="dashicons dashicons-calendar-alt"></span>
    <input type="text" class="ta-form-input range_datepicker expire" id="ta_link_expire_date" name="ta_link_expire_date" value="<?php echo esc_attr( $thirstylink->get_prop( 'link_expire_date' ) ); ?>" placeholder="yyyy-mm-dd" pattern="(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))">
</p>

<p class="link-after-expire-redirect-field url-field">
    <label class="info-label" for="ta_after_expire_redirect">
        <?php _e( 'Link expiration redirect URL:' , 'thirstyaffiliates-pro' ); ?>
    </label>
    <input type="url" class="ta-form-input" id="ta_after_expire_redirect" name="ta_after_expire_redirect" value="<?php echo esc_attr( $thirstylink->get_prop( 'after_expire_redirect' ) ); ?>" placeholder="<?php echo esc_url( $global_link_expire_redirect_url ); ?>">
</p>

<script type="text/javascript">
jQuery( document ).ready( function($){

    var tapLinkScheduler = {

        /**
         * Initialize date picker function
         *
         * @since 1.2.0
         */
        rangeDatepicker : function() {

            var start  = $link_scheduler.find( '.range_datepicker.start' ),
                expire = $link_scheduler.find( '.range_datepicker.expire' );

            start.datepicker({
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                expire.datepicker( "option" , "minDate" , tapLinkScheduler.getDate( this ) );
            } ).trigger( 'change' );

            expire.datepicker({
                minDate    : 0,
                dateFormat : 'yy-mm-dd'
            }).on( "change" , function() {
                start.datepicker( "option", "maxDate", tapLinkScheduler.getDate( this ) );
            } ).trigger( 'change' );
        },

        /**
         * Get the date value of the datepicker element.
         *
         * @since 1.2.0
         */
        getDate : function( element ) {

            var date;

      try {
        date = $.datepicker.parseDate( date_format, element.value );
      } catch( error ) {
        date = null;
                console.log( error );
      }

      return date;
        },
    };

    var $link_scheduler = $( '#tap-link-scheduler-metabox' ),
        date_format     = 'yy-mm-dd';

    // initialize link schedule datepicker fields.
    tapLinkScheduler.rangeDatepicker();
});
</script>
