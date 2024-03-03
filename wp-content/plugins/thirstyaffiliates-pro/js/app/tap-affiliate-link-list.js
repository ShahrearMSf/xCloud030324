jQuery( document ).ready( function($) {

    var AffiliateLinkList = {

        /**
         * Link health status tooltip
         *
         * @since 1.1.0
         */
        init_tooltip : function() {

            $link_health_tooltip.tipTip({
                "attribute"       : "data-tip",
                "defaultPosition" : "top",
                "fadeIn"          : 50,
                "fadeOut"         : 50,
                "delay"           : 200
            });
        },

        generate_short_url : function() {

            $short_url_column.on( 'click' , 'button' , function() {

                var $this    = $(this),
                    $parent  = $this.closest( 'td.shortened_url' ),
                    $input   = $parent.find( 'input[type="text"]' ),
                    $spinner = $parent.find( '.tap-spinner' ),
                    link_id  = $this.data( 'link_id' ),
                    nonce    = $this.data( 'nonce' );

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

                        $input.val( response.short_url ).show();
                        $this.hide();

                    } else {

                        // TODO: changed to VEX modal
                        alert( response.error_msg );

                        $this.prop( 'disabled' , false );
                    }

                }, 'json' );

            } );
        }
    };

    var $link_health_column  = $( "td.link_health_status" ),
        $link_health_tooltip = $link_health_column.find( ".tooltip" ),
        $short_url_column    = $( "td.shortened_url" );


    AffiliateLinkList.init_tooltip();
    AffiliateLinkList.generate_short_url();
});
