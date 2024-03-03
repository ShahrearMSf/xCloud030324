<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<tr class="form-field form-required term-xname-wrap">
    <th>
      <label for="tag-name">
            <?php _e( 'Name:' , 'thirstyaffiliates-pro' ); ?>
        </label>
    </th>
    <td>
      <input name="name" id="tag-name" type="text" value="<?php echo esc_attr( $term_name ); ?>" size="40" aria-required="true" />
      <p class="description"><?php _e( 'Give your event notification a name for internal reference.' , 'thirstyaffiliates-pro' ); ?></p>
    </td>
</tr>

<tr class="form-field term-recipient-wrap">
    <th scope="row">
        <label for="tag-name">
            <?php _e( 'Recipient Email:' , 'thirstyaffiliates-pro' ); ?>
        </label>
    </th>
    <td>
      <input name="recipient_email" id="recipient_email" type="text" value="<?php echo esc_attr( $recipient_email ); ?>" size="40" aria-required="true" placeholder="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>" />
    </td>
</tr>

<tr class="form-field form-required event-nofication-type-wrap">
    <th scope="row">
        <label for="event_notification_type">
            <?php _e( 'Notification Type:' , 'thirstyaffiliates-pro' ); ?>
        </label>
    </th>
    <td>
        <div class="radio-list" id="tap_event_notification_type">
            <?php foreach ( $notification_types as $key => $label ) : ?>
                <label>
                    <input type="radio" name="tap_event_notification_type" value="<?php echo esc_attr( $key ); ?>" <?php checked( $notification_type , $key ); ?> required>
                    <?php echo $label; ?>
                </label>
            <?php endforeach; ?>
        </div>
    </td>
</tr>

<tr class="form-field form-required event-notification-trigger-value-wrap">
    <th scope="row">
        <label for="event_notification_trigger_value">
            <?php _e( 'Trigger Value:' , 'thirstyaffiliates-pro' ); ?>
        </label>
    </th>
    <td>
        <input type="number" id="tap_event_notification_trigger_value" name="tap_event_notification_trigger_value" min="1" value="<?php echo esc_attr( $trigger_value ); ?>" required>
    </td>
</tr>
