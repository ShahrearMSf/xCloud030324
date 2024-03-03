import $ from "jquery";

/**
 * Initialize event notification edit page code.
 *
 * @since 1.0.0
 */
export default function event_notification_edit_page() {

    let $wrap = $( "#wpbody-content > .wrap" ),
        $edit_event_notification = $wrap.find( "#edittag" );

    // remove unneeded field options on load
    $edit_event_notification.find( ".term-slug-wrap" ).remove();
    $edit_event_notification.find( ".term-parent-wrap" ).remove();
    $edit_event_notification.find( ".term-description-wrap" ).remove();

}
