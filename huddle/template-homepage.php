<?php
/**
 * Template Name: Homepage
 */

	get_header(); ?>

		<div id="billboard">

			<div class="slide">
				<div class="slide-content" style="background-image: url(<?php echo of_get_option( 'heading_image' ) ? of_get_option( 'heading_image' ) : get_template_directory_uri() . '/images/image-slide.png' ?>)">
					<h2><?php echo of_get_option( 'heading_text' ) ?></h2>
					<p><?php echo of_get_option( 'sub_heading_text' ) ?></p>
					
					<?php if( ! is_user_logged_in() && ( $bp_pages = get_option( 'bp-pages' ) ) ) : ?>
						<p><a href="<?php echo add_query_arg( 'w-iframe', '1', get_permalink( $bp_pages['register'] ) ) ?>" class="btn quick-register cboxElement"><span><?php _e( 'Create An Account →', 'huddle' ) ?></span></a> <span class="sign-in"><?php _e( 'Or sign-in to your account', 'huddle' ) ?></span></p>
					<?php endif ?>
				</div><!-- .slide-content -->
			</div><!-- .slide -->

			<div class="clear"></div>

			<div class="column first">
				<img src="<?php echo of_get_option( 'column_1_icon', get_template_directory_uri() . '/images/icon-profile.png' ) ?>" alt="<?php echo of_get_option( 'column_1_title' ) ?>" />
				<h2><?php echo of_get_option( 'column_1_title' ) ?></h2>
				<p><?php echo of_get_option( 'column_1_text' ) ?></p>
			</div><!-- .column -->

			<div class="column">
				<img src="<?php echo of_get_option( 'column_2_icon', get_template_directory_uri() . '/images/icon-groups.png' ) ?>" alt="<?php echo of_get_option( 'column_2_title' ) ?>" />
				<h2><?php echo of_get_option( 'column_2_title' ) ?></h2>
				<p><?php echo of_get_option( 'column_2_text' ) ?></p>
			</div><!-- .column -->

			<div class="column">
				<img src="<?php echo of_get_option( 'column_3_icon', get_template_directory_uri() . '/images/icon-users.png' ) ?>" alt="<?php echo of_get_option( 'column_3_title' ) ?>" />
				<h2><?php echo of_get_option( 'column_3_title' ) ?></h2>
				<p><?php echo of_get_option( 'column_3_text' ) ?></p>
			</div><!-- .column -->

			<div class="clear"></div>

		</div><!-- #billboard -->

	<?php get_sidebar( 'home' ) ?>

	<?php get_footer(); ?>
