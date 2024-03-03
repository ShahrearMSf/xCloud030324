(function(){
    window.TAP = window.TAP ||{ Admin: {} };
}());

var Test;

(function($){

    var Tour;

    function Tour() {

        var current_screen = ( tap_guided_tour_params.screenid == 'thirstylink' ) ? tap_guided_tour_params.screen[ 'intro' ] : tap_guided_tour_params.screen;

        if ( ! current_screen.elem )
            return;

        this.initPointer( current_screen );

    }

    Tour.prototype.initPointer = function( current_screen ){

        var self = this;
        self.$elem = $( current_screen.elem ).pointer({
            content: current_screen.html,
            width: 1000,
            position: {
                align: current_screen.align,
                edge: current_screen.edge,
            },
            buttons: function( event, t ){
                return self.createButtons( current_screen , t );
            },
        }).pointer( 'open' );

        var pointer_width = ( typeof current_screen.width !== 'undefined' ) ?  current_screen.width : 320,
            $wp_pointer   = $( '.wp-pointer' );

        $wp_pointer.css( 'width' , pointer_width );

        // adjust the arrow pointer on the settings screen
        if ( tap_guided_tour_params.screenid == 'thirstylink_page_thirsty-settings' ) {

            var tab_width    = self.$elem.width() + 20,
                panel_width  = $wp_pointer.width(),
                arrow_offset;

            if ( current_screen.align == 'left' && current_screen.edge == 'top' )
                arrow_offset = ( tab_width / 2 ) - 13;
            else if ( current_screen.align == 'right' && current_screen.edge == 'top' )
                arrow_offset = panel_width - ( tab_width / 2 ) - 13;

            if ( arrow_offset )
                $wp_pointer.find( '.wp-pointer-arrow' ).css( 'left' , arrow_offset );
        }
    };

    Tour.prototype.createButtons = function( current_screen , t ) {

        this.$buttons = $( '<div></div>', {
            'class': 'ta-tour-buttons'
        });

        if ( tap_guided_tour_params.screen.btn_tour_done )
            this.createTourCompleteButton( current_screen , t );

        this.createCloseButton( current_screen , t );
        this.createPrevButton( current_screen , t );
        this.createNextButton( current_screen , t );

        return this.$buttons;

    };

    Tour.prototype.createCloseButton = function( current_screen , t ) {

        var $btnClose = $( '<button></button>', {
            'class': 'button button-large',
            'type': 'button'
        }).html( tap_guided_tour_params.texts.btn_close_tour );

        $btnClose.click(function() {

            var data = {
                action : tap_guided_tour_params.actions.close_tour,
                nonce  : tap_guided_tour_params.nonces.close_tour,
            };

            $.post( tap_guided_tour_params.urls.ajax, data, function( response ) {

                if ( response.status == 'success' )
                    Tour.$elem.pointer( 'close' );

            } , 'json' );

        });

        this.$buttons.append($btnClose);

    };

    Tour.prototype.createPrevButton = function( current_screen , t ) {

        if ( ! current_screen.prev )
            return;

        var $btnPrev = $( '<button></button>' , {
            'class': 'button button-large',
            'type': 'button'
        } ).html( tap_guided_tour_params.texts.btn_prev_tour );

        $btnPrev.click( function(){

            if ( current_screen.prev.indexOf( '@' ) >= 0 ) {

                var prev_guide_id = current_screen.prev.replace( '@' , '' ),
                    prev_screen   = tap_guided_tour_params.screen[ prev_guide_id ];

                Tour.$elem.pointer( 'close' );

                Tour.gotoSection( prev_screen.elem );
                Tour.initPointer( prev_screen );

            } else
                window.location.href = current_screen.prev;

        });

        this.$buttons.append( $btnPrev );

    };

    Tour.prototype.createNextButton = function( current_screen , t ) {

        if ( ! current_screen.next )
            return;

        // Check if this is the first screen of the tour.
        var text = ( ! current_screen.prev ) ? tap_guided_tour_params.texts.btn_start_tour : tap_guided_tour_params.texts.btn_next_tour;

        // Check if this is the last screen of the tour.
        text = ( current_screen.btn_tour_done ) ? current_screen.btn_tour_done : text;

        var $btnStart = $( '<button></button>', {
            'class' : 'button button-large button-primary',
            'type'  : 'button'
        }).html( text );

        $btnStart.click( function() {

            if ( current_screen.next.indexOf( '@' ) >= 0 ) {

                var next_guide_id = current_screen.next.replace( '@' , '' ),
                    next_screen   = tap_guided_tour_params.screen[ next_guide_id ];

                Tour.$elem.pointer( 'close' );

                Tour.gotoSection( next_screen.elem );
                Tour.initPointer( next_screen );

            } else
                window.location.href = current_screen.next;

        } );

        this.$buttons.append( $btnStart );

    };

    Tour.prototype.createTourCompleteButton = function( current_screen , t ) {

        var $btnTourComplete = $( '<button></button>', {
            'class': 'button button-large button-primary',
            'type': 'button'
        }).html( current_screen.btn_tour_done );

        $btnTourComplete.click(function() {

            var data = {
                action : tap_guided_tour_params.actions.close_tour,
                nonce  : tap_guided_tour_params.nonces.close_tour,
            };

            // open link to TAP Pro on new tab
            window.open( current_screen.btn_tour_done_url );

            $.post( tap_guided_tour_params.urls.ajax, data, function( response ) {

                if ( response.status == 'success' )
                    Tour.$elem.pointer( 'close' );

            } , 'json' );

        });

        this.$buttons.append( $btnTourComplete );

    };

    Tour.prototype.gotoSection = function( elem ) {

        var top = $( elem ).offset().top - 50;
        $('html, body').animate( { scrollTop : top + 'px' } , 'fast' );
    };

    TAP.Admin.Tour = Tour;

    // DOM ready
    $( function() {
            Tour = new TAP.Admin.Tour();
            Test = Tour;
    });

}(jQuery));
