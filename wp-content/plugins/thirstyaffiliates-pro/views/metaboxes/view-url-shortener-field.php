<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="shortened-url-field"
    data-link_id="<?php echo esc_attr( $thirstylink->get_id() ); ?>"
    data-nonce="<?php echo wp_create_nonce( 'tap_shorten_affiliate_link_destination_url' ); ?>">

    <?php if ( $is_api_active || $shortened_url ) : ?>
        <label class="info-label" for="ta_destination_url">
            <?php _e( 'Shortened URL:' , 'thirstyaffiliates-prop' ); ?>
        </label>
    <?php endif; ?>

    <input type="url" class="ta-form-input" id="ta_shortened_url" value="<?php echo esc_attr( $shortened_url ); ?>" readonly style="width: 200px; display:<?php echo $shortened_url ? 'inline-block' : 'none'; ?>;">

    <?php if ( $service_used !== $active_service && $is_api_active ) : ?>
        <div class="button-wrap">
            <button type="button" class="button" id="shorten_url_trigger">
                <?php echo esc_html( $button_text ); ?>
            </button>
            <span class="tap-spinner"></span>
            <span class="success-msg"><?php _e( 'Success!' , 'thirstyaffiliates-pro' ); ?></span>
        </div>
        <div class="cloaked-url-change-warning">
            <?php _e( "The cloaked url value has been changed. You'll be able to generate the short URL after saving the affiliate link." , 'thirstyaffiliates-pro' ); ?>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery( document ).ready( function( $ ) {

    var $shortened_url_field = $( '.shortened-url-field' ),
        $cloaked_field       = $( 'input#ta_cloaked_url' ),
        cloaked_url          = '<?php echo $cloaked_url; ?>';

    $shortened_url_field.on( 'click' , '#shorten_url_trigger' , function() {

        var $this    = $(this),
            $input   = $shortened_url_field.find( '#ta_shortened_url' ),
            $spinner = $shortened_url_field.find( '.button-wrap .tap-spinner' ),
            link_id  = $shortened_url_field.data( 'link_id' ),
            nonce    = $shortened_url_field.data( 'nonce' );

        if ( $cloaked_field.val() != cloaked_url )
            return;

        $this.prop( 'disabled' , true );
        $spinner.css( 'display' , 'inline-block' );

        // AJAX call
        $.post( ajaxurl , {
            action  : 'tap_shorten_url',
            link_id : link_id,
            nonce   : nonce
        } , function( response ) {

            $spinner.hide();

            if ( response.status == 'success' ) {

                var $success_msg = $shortened_url_field.find( '.button-wrap .success-msg' );

                $input.val( response.short_url ).show();
                $success_msg.show();
                $this.hide();

                setTimeout( function(){ $success_msg.fadeOut( 'fast' ); } , 1500 );

            } else {

                // TODO: changed to VEX modal
                alert( response.error_msg );

                $this.prop( 'disabled' , false );
            }

        }, 'json' );

    } );

    $( '#ta-urls-metabox' ).on( 'click' , 'button.save-ta-slug' , function() {
        setTimeout(function(){

            var toggle  = ( $cloaked_field.val() != cloaked_url ) ? true : false,
                display = toggle ? 'block' : 'none';

            $shortened_url_field.find( '#shorten_url_trigger' ).prop( 'disabled' , toggle );
            $shortened_url_field.find( '.cloaked-url-change-warning' ).css( 'display' , display );
        }, 300);
    } );
});
</script>
