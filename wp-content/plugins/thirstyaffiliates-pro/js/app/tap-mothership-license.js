jQuery( document ).ready( function( $ ) {

    var $license = $( '#tap-license-content' ),
        working = false;

    $( '.ta-settings-form form' ).on( 'submit', function ( e ) {
        e.preventDefault();
    } );

    $( 'p.submit' ).hide();

    $license.on( 'keyup blur', '#tap_mothership_license_key', function () {
        var value = $( this ).val();

        if ( value && value.substring( 0, 4 ) === 'TAP-' ) {
            $( '#tap_slmw_activation_email-row' ).show();
        } else {
            $( '#tap_slmw_activation_email-row' ).hide();
        }
    } );

    $license.on('click', '#tap-activate-license-key', function () {
        var $button = $( this ),
            button_width = $button.width(),
            button_html = $button.html(),
            license_key = $.trim( $( '#tap_mothership_license_key' ).val() ),
            activation_email = $.trim( $( '#tap_slmw_activation_email' ).val() );

        if ( ! license_key) {
            vex.dialog.alert( tap_mothership_args.i18n_please_fill_activation_creds );
            return;
        }

        if ( license_key.substring( 0, 4 ) === 'TAP-' && ! activation_email ) {
            vex.dialog.alert( tap_mothership_args.i18n_please_fill_activation_email );
            return;
        }

        if ( working ) {
            return;
        }

        working = true;
        $button.width( button_width ).html( tap_mothership_args.loading_image );
        $license.find( '.notice' ).remove();

        $.ajax( {
            url: tap_mothership_args.ajax_url,
            type: 'POST',
            data: {
                action : 'tap_mothership_activate_license',
                _ajax_nonce: tap_mothership_args.nonce_activate_license,
                'license-key': license_key
            },
            dataType: 'json'
        } )
        .done( function( response ) {
            if ( response && typeof response.success === 'boolean' ) {
                if ( response.success ) {
                    if ( response.data === true ) {
                        window.location.reload();
                    } else {
                        $license.html( response.data );
                    }
                } else {
                    activate_license_error( response.data );
                }
            } else {
                activate_license_error();
            }
        } )
        .fail( function( jqxhr ) {
            activate_license_error();
            console.log( jqxhr );
        } )
        .always( function() {
            $button.html( button_html ).width( 'auto' );
            working = false;
        } );

    } );

    function activate_license_error (message) {
        $( '<div class="notice notice-error">' ).append(
            $( '<p>' ).html( message || tap_mothership_args.i18n_server_error_contact_support )
        ).prependTo( $license );
    }

    $license.on( 'keypress', '#tap_mothership_license_key, #tap_slmw_activation_email', function ( e ) {
        if ( e.which === 13 ) {
            e.preventDefault();
            $( '#tap-activate-license-key' ).trigger( 'click' );
        }
    } );

    $license.on( 'click', '#tap-license-deactivate button', function () {
        var $button = $( this ),
            button_width = $button.width(),
            button_html = $button.html();

        if ( working ) {
            return;
        }

        working = true;

        $button.width( button_width ).html( tap_mothership_args.loading_image );

        $.ajax( {
            url: tap_mothership_args.ajax_url,
            type: 'POST',
            data: {
                action: 'tap_mothership_deactivate_license',
                _ajax_nonce: tap_mothership_args.nonce_deactivate_license
            },
            dataType: 'json'
        } )
        .done( function ( response ) {
            if ( response && typeof response.success === 'boolean' ) {
                if ( response.success ) {
                    $( '#tap-license-content' ).html( response.data );
                } else {
                    vex.dialog.alert( response.data );
                }
            } else {
                vex.dialog.alert( tap_mothership_args.i18n_server_error_contact_support );
            }
        } )
        .fail( function ( jqxhr ) {
            vex.dialog.alert( tap_mothership_args.i18n_server_error_contact_support );
            console.log( jqxhr );
        } )
        .always( function () {
            $button.html( button_html ).width( 'auto' );
            working = false;
        } );
    } );

    $license.on( 'click', '#tap_mothership_edge_updates', function () {
        var $checkbox = $( this ),
            $loading = $( '#tap_mothership_edge_updates-loading' );

        if (working) {
            return;
        }

        working = true;
        $checkbox.prop( 'disabled', true );
        $loading.show();

        $.ajax( {
            url: tap_mothership_args.ajax_url,
            type: 'POST',
            data: {
                action : 'tap_toggle_edge_updates',
                _ajax_nonce: tap_mothership_args.nonce_toggle_edge_updates,
                'edge-updates': $checkbox.prop( 'checked' ) ? '1' : '0'
            },
            dataType: 'json'
        } )
        .done( function( response ) {
            if ( response && typeof response.success === 'boolean' ) {
                if ( ! response.success ) {
                    vex.dialog.alert( response.data );
                }
            } else {
                vex.dialog.alert( tap_mothership_args.i18n_server_error_contact_support );
            }
        } )
        .fail( function( jqxhr ) {
            vex.dialog.alert( tap_mothership_args.i18n_server_error_contact_support );
            console.log( jqxhr );
        } )
        .always( function() {
            $checkbox.prop('disabled', false);
            $loading.hide();
            working = false;
        } );
    });

    $license.on( 'click', '#tap-install-license-edition', function ( e ) {
        e.preventDefault();

        $( '#tap-install-license-edition-loading' ).css( 'display', 'inline-block' );

        $.ajax( {
            url: tap_mothership_args.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'tap_install_license_edition',
                _ajax_nonce: tap_mothership_args.install_license_edition_nonce
            }
        } )
        .done( function ( response ) {
            if ( response && typeof response.success === 'boolean' ) {
                vex.dialog.alert( {
                    message: response.data,
                    callback: function () {
                        if ( response.success ) {
                            window.location.reload();
                        }
                    }
                } );
            } else {
                vex.dialog.alert( tap_mothership_args.i18n_error_installing_license_edition );
            }
        } )
        .fail( function () {
            vex.dialog.alert( tap_mothership_args.i18n_error_installing_license_edition );
        } )
        .always( function () {
            $( '#tap-install-license-edition-loading' ).hide();
        } );
    } );

    $license.on( 'click', '#tap-activate-new-license', function ( e ) {
        e.preventDefault();

        var license_key = $( this ).data( 'license-key' );

        setTimeout( function () {
            $( '#tap_mothership_license_key' ).val( license_key );
            $( '#tap-activate-license-key' ).trigger( 'click' );
        }, 250 );
    } );

} );
