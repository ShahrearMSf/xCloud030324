import $ from "jquery";
import event_notification_taxonomy_list from "./event-notification-taxonomy-list";
import event_notification_edit_page from "./event-notification-edit-page";

import "./assets/styles/index.scss";

$( document ).ready( function() {

    event_notification_taxonomy_list();
    event_notification_edit_page();

    // validate email field
    $( "#addtag,#edittag" ).on( "change" , "#recipient_email" , function() {

        const regex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        let $this   = $(this),
            $input_wrap = $(this).closest( ".form-field" ),
            $submit = $( "#addtag,#edittag" ).find( "input[type='submit']" );

        // clear error first before validating.
        $input_wrap.find( ".field-error" ).remove();

        if ( regex.test( $this.val() ) || ! $this.val() ) {

            $submit.prop( "disabled" , false );

        } else {

            if ( $input_wrap.find( "td" ).length >= 1 )
                $input_wrap.find( "td" ).append( "<p class='field-error'>Please enter a valid email</p>" );
            else
                $input_wrap.append( "<p class='field-error'>Please enter a valid email</p>" );

            $submit.prop( "disabled" , true );
        }
    } );

} );
