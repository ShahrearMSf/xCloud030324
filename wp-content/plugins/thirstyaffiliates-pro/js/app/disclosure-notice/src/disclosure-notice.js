/* global jQuery , thirsty_global_vars , tap_disclosure_notice_vars */

/**
 * Initialize disclosure notice events.
 *
 * @since 1.4.0
 */
export default function disclosure_notice() {

    initialize_disclosure_notice();

    jQuery( "body" ).on( "mouseenter" , ".tap-disclosure-notice-icon" , display_disclosure_notice_tooltip );
    jQuery( "body" ).on( "mouseleave" , ".tap-disclosure-notice-tooltip,.tap-disclosure-notice-icon" , remove_disclosure_notice_tooltip );
    jQuery( "body" ).on( "mouseenter", ".tap-disclosure-notice-tooltip" , persist_disclosure_notice_tooltip );
}

/**
 * Initialize disclosure notice script.
 *
 * @since 1.4.0
 */
function initialize_disclosure_notice() {

    const allLinks = document.querySelectorAll( "body a" );
    let count = 0;

    if ( typeof allLinks != "object" || allLinks.length < 0 ) return;

    for ( let link of allLinks ) {
        if ( typeof link == "object" && ( isThirstyLink( link.getAttribute( "href" ) ) || link.dataset.linkid ) ) {
            append_disclosure_icon( link );
            count++;
        }
    }

    // only display bottom of post disclosure notice if post has affiliate links in it.
    if ( count && tap_disclosure_notice_vars.display_bottom_post ) display_disclosure_notice_bottom_post();
}

function display_disclosure_notice_bottom_post() {

    const contentSelector = ( tap_disclosure_notice_vars.content_selector || "body #post-%d" ).replace( /%d/g, tap_disclosure_notice_vars.post_id ),
        hentry = document.querySelectorAll( contentSelector ),
        link     = `<a href="${ tap_disclosure_notice_vars.disclosure_page }" target="_blank">${ tap_disclosure_notice_vars.notice_link_text }</a>`,
        message  = tap_disclosure_notice_vars.bottom_post_message.replace( "{{disclosure_link}}" , link ),
        markup   = `<div class="tap_disclosure_notice_bottom_post">${ message }</div>`;

    jQuery( hentry ).append( markup );
}

/**
 * Function to check if the loaded link is a ThirstyAffiliates link or not.
 *
 * @param {string} href
 */
function isThirstyLink( href ) {

    if ( typeof href != "string" || ! href ) return;

    href = href.replace( "http:" , "{protocol}" ).replace( "https:" , "{protocol}" );

    const link_prefixes = jQuery.map( thirsty_global_vars.link_prefixes , ( value ) => [value] );

    let link_uri = href.replace( thirsty_global_vars.home_url , "" ).replace( "{protocol}" , "" ),
        link_prefix, new_href;

    link_uri    = link_uri.indexOf( "/" ) == 0 ? link_uri.replace( "/" , "" ) : link_uri;
    link_prefix = link_uri.substr( 0 , link_uri.indexOf( "/" ) );
    new_href    = href.replace( "/" + link_prefix + "/" , "/" + thirsty_global_vars.link_prefix + "/" ).replace( "{protocol}" , window.location.protocol );

    return ( link_prefix && jQuery.inArray( link_prefix , link_prefixes ) > -1 ) ? new_href : false;
}

/**
 * Append disclosure icon.
 *
 * @param {object} link
 */
function append_disclosure_icon( link ) {

    if ( ! tap_disclosure_notice_vars.display_icon ) return;

    const icon = `
        <span class="tap-disclosure-notice-icon">
            <i class="dashicons dashicons-info"></i>
        </span>
    `;

    jQuery( link ).append( icon );
}

/**
 * Display disclosure notice tooltip.
 */
function display_disclosure_notice_tooltip() {

    const $icon = jQuery(this),
        $body   = jQuery("body");

    if ( $icon.data( "markup" ) == true ) return;

    const link  = `<a href="${ tap_disclosure_notice_vars.disclosure_page }" target="_blank">${ tap_disclosure_notice_vars.notice_link_text }</a>`,
        message = tap_disclosure_notice_vars.notice_icon_message.replace( "{{disclosure_link}}" , link ),
        markup  = `<div class="tap-disclosure-notice-tooltip">${ message }</div>`;

    $body.find( ".tap-disclosure-notice-tooltip" ).remove();
    $body.append( markup );
    $icon.data( "markup" , true );

    const $tooltip = $body.find( ".tap-disclosure-notice-tooltip" );

    let leftOffset = $icon.offset().left - ( $tooltip.width() / 2 ),
        topOffset  = $icon.offset().top - $tooltip.height() - 20;

    $tooltip.css({
        top  : topOffset,
        left : leftOffset
    }).addClass( "show" )
    .data( "icon" , $icon );

}

/**
 * Remove disclosure notice tooltip (after hover).
 */
function remove_disclosure_notice_tooltip() {

    const $tooltip = jQuery( ".tap-disclosure-notice-tooltip" );

    $tooltip.addClass( "fade" );
    $tooltip.data( "showtimeout" , setTimeout( () => $tooltip.removeClass( "show" ) , 500 ) );
    jQuery( ".tap-disclosure-notice-icon" ).data( "markup" , false );
}

/**
 * Persist disclosure notice tooltip (when hovered back to the area of tooltip).
 */
function persist_disclosure_notice_tooltip() {

    const $tooltip = jQuery( this );

    clearTimeout( $tooltip.data( "showtimeout" ) );
    $tooltip.removeClass( "fade" );
    $tooltip.addClass( "show" );
}
