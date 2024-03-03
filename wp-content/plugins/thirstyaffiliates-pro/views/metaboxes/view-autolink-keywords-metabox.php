<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<p>
    <label class="info-label block" for="tap_autolink_keyword_list">
        <?php _e( 'Enter a list of keywords you wish to automatically link with this affiliate link (case-insensitive)' , 'thirstyaffiliates-pro' ); ?>
    </label>
    <textarea id="tap_autolink_keyword_list" name="tap_autolink_keyword_list" style="width:100%; height: 100px;"><?php echo $thirstylink->get_prop( 'autolink_keyword_list' ); ?></textarea>
    <span class="ta-input-description">
        <?php _e( 'Note: Place your keywords in order of precedence. eg. If "web design" is mentioned first and "web design course" second, it will link "web design" as first preference. Also, please type your entries rather than copy/pasting from the front end of your site. This will eliminate weird character encoding issues, especially relating to apostrophes.' , 'thirstyaffiliates-pro' ); ?>
    </span>
</p>

<p>
    <label class="info-label" for="tap_autolink_keyword_limit">
        <?php _e( 'Limit (per keyword):' , 'thirstyaffiliates-pro' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'How many links per keyword should the autolinker place before stopping? Set value to 0 to follow the global value. Set value to -1 to disable keyword limit for this affiliate link.' , 'thirstyaffiliates-pro' ); ?>"></span>
    </label>
    <input type="number" class="ta-form-input" id="tap_autolink_keyword_limit" name="tap_autolink_keyword_limit" min="-1" step="1" value="<?php echo esc_attr( $thirstylink->get_prop( 'autolink_keyword_limit' ) ); ?>" placeholder="<?php echo esc_attr( get_option( 'tap_autolink_keyword_limit' ) ); ?>" style="width: 100px;">
</p>

<?php if ( get_option( 'tap_use_revamped_autolinker' , 'old' ) === 'old' ) : ?>
<p>
    <label class="info-label">
        <?php _e( 'Enabled auto linking inside headings?' , 'thirstyaffiliates-pro' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'Should the autolinker add links to matches inside heading tags? eg. &#0139;h1&#0155;, &#0139;h2&#0155;, &#0139;h3&#0155;, etc. Note this only links if the heading is part of the actual content, not the post/page title.' , 'thirstyaffiliates-pro' ); ?>"></span>
    </label>
    <select id="tap_autolink_inside_heading" name="tap_autolink_inside_heading">
        <option value="global" <?php selected( $thirstylink->get_prop( 'autolink_inside_heading' ) , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'thirstyaffiliates-pro' ) , $global_autolink_inside_heading ); ?></option>
        <option value="yes" <?php selected( $thirstylink->get_prop( 'autolink_inside_heading' ) , 'yes' ); ?>><?php _e( 'Yes' , 'thirstyaffiliates-pro' ); ?></option>
        <option value="no" <?php selected( $thirstylink->get_prop( 'autolink_inside_heading' ) , 'no' ); ?>><?php _e( 'No' , 'thirstyaffiliates-pro' ); ?></option>
    <select>
</p>

<p>
    <label class="info-label">
        <?php _e( 'Random Placement?:' , 'thirstyaffiliates-pro' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'Whether to pick random instances of matching keywords in the content to link or to link keywords sequentially as it finds them.' , 'thirstyaffiliates-pro' ); ?>"></span>
    </label>
    <select id="tap_autolink_random_placement" name="tap_autolink_random_placement">
        <option value="global" <?php selected( $thirstylink->get_prop( 'autolink_random_placement' ) , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'thirstyaffiliates-pro' ) , $global_autolink_random_placement ); ?></option>
        <option value="yes" <?php selected( $thirstylink->get_prop( 'autolink_random_placement' ) , 'yes' ); ?>><?php _e( 'Yes' , 'thirstyaffiliates-pro' ); ?></option>
        <option value="no" <?php selected( $thirstylink->get_prop( 'autolink_random_placement' ) , 'no' ); ?>><?php _e( 'No' , 'thirstyaffiliates-pro' ); ?></option>
    <select>
</p>
<?php endif; ?>

<script type="text/javascript">
jQuery( document ).ready( function($) {

    $( "#tap-autolink-keywords-metabox label .tooltip" ).tipTip({
        "attribute"       : "data-tip",
        "defaultPosition" : "top",
        "fadeIn"          : 50,
        "fadeOut"         : 50,
        "delay"           : 200
    });

    $( '#tap_autolink_keyword_list' ).selectize({
        plugins   : [ 'restore_on_backspace' , 'remove_button' , 'drag_drop' ],
        delimiter : ',',
        persist   : false,
        create    : function(input) {
            return {
                value: input,
                text: input
            }
        }
    });


});
</script>
