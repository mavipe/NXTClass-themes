<?php do_action( 'bp_before_profile_loop_content' ) ?>

<?php $ud = get_userdata( bp_displayed_user_id() ); ?>

<?php do_action( 'bp_before_profile_field_content' ) ?>

	<div class="bp-widget nxt-profile">
		<h4><?php bp_is_my_profile() ? _e( 'My Profile', 'huddle' ) : printf( __( "%s's Profile", 'huddle' ), bp_get_displayed_user_fullname() ); ?></h4>

		<table class="nxt-profile-fields">

			<?php if ( $ud->display_name ) : ?>

				<tr id="nxt_displayname">
					<td class="label"><?php _e( 'Name', 'huddle' ); ?></td>
					<td class="data"><?php echo $ud->display_name; ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->user_description ) : ?>

				<tr id="nxt_desc">
					<td class="label"><?php _e( 'About Me', 'huddle' ); ?></td>
					<td class="data"><?php echo $ud->user_description; ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->user_url ) : ?>

				<tr id="nxt_website">
					<td class="label"><?php _e( 'Website', 'huddle' ); ?></td>
					<td class="data"><?php echo make_clickable( $ud->user_url ); ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->jabber ) : ?>

				<tr id="nxt_jabber">
					<td class="label"><?php _e( 'Jabber', 'huddle' ); ?></td>
					<td class="data"><?php echo $ud->jabber; ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->aim ) : ?>

				<tr id="nxt_aim">
					<td class="label"><?php _e( 'AOL Messenger', 'huddle' ); ?></td>
					<td class="data"><?php echo $ud->aim; ?></td>
				</tr>

			<?php endif; ?>

			<?php if ( $ud->yim ) : ?>

				<tr id="nxt_yim">
					<td class="label"><?php _e( 'Yahoo Messenger', 'huddle' ); ?></td>
					<td class="data"><?php echo $ud->yim; ?></td>
				</tr>

			<?php endif; ?>

		</table>
	</div>

<?php do_action( 'bp_after_profile_field_content' ) ?>

<?php do_action( 'bp_profile_field_buttons' ) ?>

<?php do_action( 'bp_after_profile_loop_content' ) ?>
