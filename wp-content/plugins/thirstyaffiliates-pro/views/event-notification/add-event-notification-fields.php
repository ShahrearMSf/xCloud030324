<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="form-field form-required term-xname-wrap">
  <label for="tag-name">
        <?php _e( 'Name:' , 'thirstyaffiliates-pro' ); ?>
    </label>
  <input name="tag-name" id="tag-name" type="text" value="" size="40" aria-required="true" />
  <p><?php _e( 'Give your event notification a name for internal reference.' , 'thirstyaffiliates-pro' ); ?></p>
</div>

<div class="form-field term-recipient-wrap">
  <label for="tag-name">
        <?php _e( 'Recipient Email:' , 'thirstyaffiliates-pro' ); ?>
    </label>
  <input name="recipient_email" id="recipient_email" type="email" value="" size="40" aria-required="true" placeholder="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>" />
</div>

<div class="form-field form-required event-nofication-type-wrap">
    <label for="event_notification_type">
        <?php _e( 'Notification Type:' , 'thirstyaffiliates-pro' ); ?>
    </label>
    <div class="radio-list" id="tap_event_notification_type">
        <?php foreach ( $notification_types as $key => $label ) : ?>
            <label>
                <input type="radio" name="tap_event_notification_type" value="<?php echo esc_attr( $key ); ?>" checked required>
                <?php echo $label; ?>
            </label>
        <?php endforeach; ?>
    </div>
</div>

<div class="form-field form-required event-notification-trigger-value-wrap">
    <label>
        <?php _e( 'Trigger Value:' , 'thirstyaffiliates-pro' ); ?>
    </label>
    <input type="number" id="tap_event_notification_trigger_value" name="tap_event_notification_trigger_value" min="1" required>
</div>
