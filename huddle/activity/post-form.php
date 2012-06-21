
<form action="<?php bp_activity_post_form_action(); ?>" method="post" id="whats-new-form" name="whats-new-form" role="complementary">

	<?php do_action( 'bp_before_activity_post_form' ); ?>

	<div id="whats-new-avatar">
		<a href="<?php echo bp_loggedin_user_domain(); ?>">
			<?php bp_loggedin_user_avatar( 'width=' . bp_core_avatar_thumb_width() . '&height=' . bp_core_avatar_thumb_height() ); ?>
		</a>
	</div>

	<h5><?php if ( bp_is_group() )
			printf( __( "What's new in %s, %s?", 'huddle' ), bp_get_group_name(), bp_get_user_firstname() );
		else
			printf( __( "What's new, %s?", 'huddle' ), bp_get_user_firstname() );
	?></h5>

	<div id="whats-new-content">
		<div id="whats-new-textarea">
			<textarea name="whats-new" id="whats-new" cols="50" rows="10"><?php if ( isset( $_GET['r'] ) ) : ?>@<?php echo esc_attr( $_GET['r'] ); ?> <?php endif; ?></textarea>
		</div>

		<div id="whats-new-options">
			<div id="whats-new-submit">
				<input type="submit" class="btn-gray" name="aw-whats-new-submit" id="aw-whats-new-submit" value="<?php _e( 'Post Update', 'huddle' ); ?>" />
				
				<?php if ( bp_is_active( 'groups' ) && !bp_is_my_profile() && !bp_is_group() ) : ?>
					&nbsp;&nbsp;&nbsp;
					<?php _e( 'Post in', 'huddle' ) ?>:

					<select id="whats-new-post-in" name="whats-new-post-in">
						<option selected="selected" value="0"><?php _e( 'My Profile', 'huddle' ); ?></option>

						<?php if ( bp_has_groups( 'user_id=' . bp_loggedin_user_id() . '&type=alphabetical&max=100&per_page=100&populate_extras=0' ) ) :
							while ( bp_groups() ) : bp_the_group(); ?>

								<option value="<?php bp_group_id(); ?>"><?php bp_group_name(); ?></option>

							<?php endwhile;
						endif; ?>

					</select>
					<input type="hidden" id="whats-new-post-object" name="whats-new-post-object" value="groups" />

				<?php elseif ( bp_is_group_home() ) : ?>

					<input type="hidden" id="whats-new-post-object" name="whats-new-post-object" value="groups" />
					<input type="hidden" id="whats-new-post-in" name="whats-new-post-in" value="<?php bp_group_id(); ?>" />

				<?php endif; ?>

			</div>

			<?php do_action( 'bp_activity_post_form_options' ); ?>

		</div><!-- #whats-new-options -->
	</div><!-- #whats-new-content -->

	<?php nxt_nonce_field( 'post_update', '_nxtnonce_post_update' ); ?>
	<?php do_action( 'bp_after_activity_post_form' ); ?>

</form><!-- #whats-new-form -->
