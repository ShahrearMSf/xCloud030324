<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="clearfix">

    <span class="status-wrap">
        <strong><?php _e( 'Status:' , 'thirstyaffiliates-pro' ); ?></strong>
        <?php echo $status_markup; ?>
    </span>

    <button id="trigger-manual-health-check" type="button" class="button"
        data-button-text="<?php _e( 'Check Health' , 'thirstyaffiliates-pro' ); ?>"
        data-button-alt="<?php _e( 'Checking...' , 'thirstyaffiliates-pro' ); ?>"
        data-linkid="<?php echo esc_attr( $link_id ); ?>"
        data-nonce="<?php echo wp_create_nonce( 'tap_check_single_link_health_' . $link_id ); ?>">
        <?php _e( 'Check Health' , 'thirstyaffiliates-pro' ); ?>
    </button>

</div>

<script type="text/javascript">
jQuery( document ).ready( function($) {

    var tapLinkHealth = {

        /**
         * Initialize link health check (single affiliate link).
         *
         * @since 1.3.0
         */
        init : function() {
            $metabox.on( 'click' , '#trigger-manual-health-check' , tapLinkHealth.manual_health_check );
        },

        /**
         * Initialize link health tooltip.
         *
         * @since 1.3.0
         */
        init_tooltip : function() {

            $metabox.find( '.status-wrap .tooltip' ).tipTip({
                "attribute"       : "data-tip",
                "defaultPosition" : "top",
                "fadeIn"          : 50,
                "fadeOut"         : 50,
                "delay"           : 200
            });
        },

        /**
         * Trigger manual link health check.
         *
         * @since 1.3.0
         */
        manual_health_check : function() {

            var data = {
                action  : 'tap_check_single_link_health',
                link_id : $button.data( 'linkid' ),
                nonce   : $button.data( 'nonce' )
            };

            $button.prop( 'disabled' , true ).text( $button.data( 'button-alt' ) );

            $.post( ajaxurl , data , function( response ) {

                if ( response.status == 'success' ) {

                    $metabox.find( '.status-wrap .tooltip' ).replaceWith( response.markup );
                    tapLinkHealth.init_tooltip();

                } else {
                    // TODO: changed to VEX modal
                    alert( response.error_msg );
                }

                $button.prop( 'disabled' , false ).text( $button.data( 'button-text' ) );

            }, 'json' );
        },

    };

    var $metabox = $( '#tap-link-health-metabox-side' ),
        $button  = $metabox.find( '#trigger-manual-health-check' );

    tapLinkHealth.init();
    tapLinkHealth.init_tooltip();
});
</script>
