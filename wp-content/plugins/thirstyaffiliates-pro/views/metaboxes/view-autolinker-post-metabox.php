<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

wp_nonce_field( 'ta_autolinker_post_nonce', '_autolinker_post_nonce' ); ?>

<p>
    <label class="info-label">
        <input type="checkbox" id="tap_disable_autolinker" name="tap_disable_autolinker" value="yes" <?php checked( $disable_autolinker , true ) ?>>
        <?php _e( 'Disable autolinker for this post?' , 'thirstyaffiliates-pro' ); ?>
    </label>
</p>

<?php if ( get_option( 'tap_use_revamped_autolinker' , 'old' ) === 'new' ) : ?>
    <p>
        <label class="info-label">
            <?php _e( 'Post autolinker limit' , 'thirstyaffiliate-pro' ); ?>
        </label>
        <input type="number" class="ta-form-input" id="tap_post_autolinker_limit" name="tap_post_autolinker_limit" min="0" value="<?php echo $post_autolinker_limit; ?>">
    </p>
    <p>
        <label class="info-label">
            <?php _e( 'Enabled auto linking inside headings?' , 'thirstyaffiliates-pro' ); ?>
            <span class="tooltip" data-tip="<?php esc_attr_e( 'Should the autolinker add links to matches inside heading tags? eg. &#0139;h1&#0155;, &#0139;h2&#0155;, &#0139;h3&#0155;, etc. Note this only links if the heading is part of the actual content, not the post/page title.' , 'thirstyaffiliates-pro' ); ?>"></span>
        </label><br>
        <select id="tap_autolink_inside_heading" name="tap_autolink_inside_heading">
            <option value="global" <?php selected( $autolink_inside_heading , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'thirstyaffiliates-pro' ) , $global_autolink_inside_heading ); ?></option>
            <option value="yes" <?php selected( $autolink_inside_heading , 'yes' ); ?>><?php _e( 'Yes' , 'thirstyaffiliates-pro' ); ?></option>
            <option value="no" <?php selected( $autolink_inside_heading , 'no' ); ?>><?php _e( 'No' , 'thirstyaffiliates-pro' ); ?></option>
        <select>
    </p>

    <p>
        <label class="info-label">
            <?php _e( 'Random Placement?:' , 'thirstyaffiliates-pro' ); ?>
            <span class="tooltip" data-tip="<?php esc_attr_e( 'Whether to pick random instances of matching keywords in the content to link or to link keywords sequentially as it finds them.' , 'thirstyaffiliates-pro' ); ?>"></span>
        </label><br>
        <select id="tap_autolink_random_placement" name="tap_autolink_random_placement">
            <option value="global" <?php selected( $autolink_random_placement , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'thirstyaffiliates-pro' ) , $global_autolink_random_placement ); ?></option>
            <option value="yes" <?php selected( $autolink_random_placement , 'yes' ); ?>><?php _e( 'Yes' , 'thirstyaffiliates-pro' ); ?></option>
            <option value="no" <?php selected( $autolink_random_placement , 'no' ); ?>><?php _e( 'No' , 'thirstyaffiliates-pro' ); ?></option>
        <select>
    </p>
<?php endif; ?>