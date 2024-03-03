import $ from "jquery";

/**
 * Initialize event notification taxonomy list code.
 *
 * @since 1.0.0
 */
export default function event_notification_taxonomy_list() {

    let $container = $( "#col-container" ),
        $add_event_notification = $container.find( "#addtag" );

    // remove unneeded field options on load
    $add_event_notification.find( ".term-name-wrap" ).remove();
    $add_event_notification.find( ".term-slug-wrap" ).remove();
    $add_event_notification.find( ".term-parent-wrap" ).remove();
    $add_event_notification.find( ".term-description-wrap" ).remove();

    $( "#posts-filter" ).on( "DOMNodeInserted" , function () {

        $add_event_notification.trigger( "reset" );
    });

}
