<?php

/* ------------------------------------------------
   BuddyPress Functions
------------------------------------------------ */

define( 'BP_AVATAR_THUMB_WIDTH', 35 );
define( 'BP_AVATAR_THUMB_HEIGHT', 35 );

add_action( 'nxt_head', 'huddle_bp_unlink_profile_fields' );

function huddle_bp_unlink_profile_fields() {
	remove_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 9 );
}

remove_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 9 );

add_filter( 'bp_get_add_friend_button', 'huddle_bp_get_add_friend_button' );
add_filter( 'bp_get_group_join_button', 'huddle_bp_get_add_friend_button' );

add_filter( 'bp_get_the_profile_field_value', 'huddle_bp_get_the_profile_field_value', 3 );

function huddle_bp_get_the_profile_field_value( $value, $type = '', $id = '' ) {
	global $field;

	if( substr_count( strtolower( $field->name ), 'twitter' ) ) {
		if( !substr_count( $field->data->value, 'twitter.com' ) ) {
			$value = 'http://twitter.com/' . $value;
		}
	} elseif( substr_count( strtolower( $field->name ), 'about' ) ) {
		
	} else {
		$values = explode( ',', $value );

		if ( $values ) {
			foreach ( (array)$values as $value ) {
				$value = trim( $value );

				// If the value is a URL, skip it and just make it clickable.
				if ( preg_match( '@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $value ) ) {
					$new_values[] = make_clickable( $value );
				} else {
					if ( count( explode( ' ', $value ) ) > 5 ) {
						$new_values[] = $value;
					} else {
						$new_values[] = '<a href="' . site_url( bp_get_members_root_slug() ) . '/?s=' . strip_tags( $value ) . '" rel="nofollow">' . $value . '</a>';
					}
				}
			}

			$value = implode( ', ', $new_values );
		}
	}

	return $value;
}

function huddle_bp_get_add_friend_button( $button ) {
	$button['link_class'] .= ' btn-gray';
	return $button;
}

add_filter( 'bp_get_group_member_count', 'huddle_bp_get_group_member_count' );

function huddle_bp_get_group_member_count( $text ) {
	return ucwords( $text );
}



/* ------------------------------------------------
	BuddyPress Create Blog
------------------------------------------------ */

function huddle_bp_show_blog_signup_form($blogname = '', $blog_title = '', $errors = '') {
	global $current_user, $current_site;
	global $bp;

	if ( isset($_POST['submit']) ) {
		huddle_bp_blogs_validate_blog_signup();
	} else {
		if ( ! is_nxt_error($errors) ) {
			$errors = new nxt_Error();
		}

		// allow definition of default variables
		$filtered_results = apply_filters('signup_another_blog_init', array('blogname' => $blogname, 'blog_title' => $blog_title, 'errors' => $errors ));
		$blogname = $filtered_results['blogname'];
		$blog_title = $filtered_results['blog_title'];
		$errors = $filtered_results['errors'];

		if ( $errors->get_error_code() ) {
			echo "<p>" . __('There was a problem, please correct the form below and try again.', 'buddypress') . "</p>";
		}
		?>
		<p><?php printf(__("By filling out the form below, you can <strong>add a site to your account</strong>. There is no limit to the number of sites that you can have, so create to your heart's content, but blog responsibly!", 'buddypress'), $current_user->display_name) ?></p>

		<p><?php _e("If you&#8217;re not going to use a great domain, leave it for a new user. Now have at it!", 'buddypress') ?></p>

		<form class="standard-form" id="setupform" method="post" action="">

			<input type="hidden" name="stage" value="gimmeanotherblog" />
			<?php do_action( 'signup_hidden_fields' ); ?>

			<?php huddle_bp_blogs_signup_blog($blogname, $blog_title, $errors); ?>
			
			<p class="editfield">
				<input id="submit" type="submit" name="submit" class="btn-gray submit" value="<?php _e('Create Site', 'buddypress') ?>" />
			</p>

			<?php nxt_nonce_field( 'bp_blog_signup_form' ) ?>
		</form>
		<?php
	}
}

function huddle_bp_blogs_signup_blog( $blogname = '', $blog_title = '', $errors = '' ) {
	global $current_site;

	echo '<p class="editfield">';

	// Blog name
	if( !is_subdomain_install() )
		echo '<label for="blogname">' . __('Site Name:', 'buddypress') . '</label>';
	else
		echo '<label for="blogname">' . __('Site Domain:', 'buddypress') . '</label>';

	if ( !is_subdomain_install() )
		echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span> &nbsp; <input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /><br />';
	else
		echo '<input name="blogname" type="text" id="blogname" value="'.$blogname.'" maxlength="50" /> <span class="suffix_address">.' . preg_replace( '|^www\.|', '', $current_site->domain ) . $current_site->path . '</span><br />';

	if ( !is_user_logged_in() ) {
		print '(<strong>' . __( 'Your address will be ' , 'buddypress');

		if ( !is_subdomain_install() ) {
			print $current_site->domain . $current_site->path . __( 'blogname' , 'buddypress');
		} else {
			print __( 'domain.' , 'buddypress') . $current_site->domain . $current_site->path;
		}

		echo '.</strong> ' . __( 'Must be at least 4 characters, letters and numbers only. It cannot be changed so choose carefully!)' , 'buddypress') . '</p>';
	}

	if ( $errmsg = $errors->get_error_message('blogname') ) { ?>

		<span class="error"><?php echo $errmsg ?></span>

	<?php }

	echo '</p>';
	?>

	<p class="editfield">
		<label for="blog_title"><?php _e('Site Title:', 'buddypress') ?></label>
		<input name="blog_title" type="text" id="blog_title" value="<?php echo esc_html($blog_title, 1) ?>" /></p>

		<?php if ( $errmsg = $errors->get_error_message('blog_title') ) { ?>
			<span class="error"><?php echo $errmsg ?></span>
		<?php }
		?>
	</p>

	<p class="editfield">
		<label for="blog_public_on"><?php _e('Privacy:', 'buddypress') ?></label>

		<label class="checkbox no" for="blog_public_on">
			<input type="radio" id="blog_public_on" name="blog_public" value="1" <?php if( !isset( $_POST['blog_public'] ) || '1' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
			<strong><?php _e( 'Yes' , 'buddypress'); ?></strong>
		</label>&nbsp;
		<label class="checkbox no" for="blog_public_off">
			<input type="radio" id="blog_public_off" name="blog_public" value="0" <?php if( isset( $_POST['blog_public'] ) && '0' == $_POST['blog_public'] ) { ?>checked="checked"<?php } ?> />
			<strong><?php _e( 'No' , 'buddypress'); ?></strong>
		</label>
		&nbsp;&nbsp;&nbsp;
		<?php _e( 'I would like my site to appear in search engines, and in public listings around this network.', 'buddypress' ); ?>
	</p>

	<?php
	do_action('signup_blogform', $errors);
}

function huddle_bp_blogs_validate_blog_signup() {
	global $nxtdb, $current_user, $blogname, $blog_title, $errors, $domain, $path, $current_site;

	if ( !check_admin_referer( 'bp_blog_signup_form' ) )
		return false;

	$current_user = nxt_get_current_user();

	if( !is_user_logged_in() )
		die();

	$result = bp_blogs_validate_blog_form();
	extract($result);

	if ( $errors->get_error_code() ) {
		unset($_POST['submit']);
		huddle_bp_show_blog_signup_form( $blogname, $blog_title, $errors );
		return false;
	}

	$public = (int) $_POST['blog_public'];

	$meta = apply_filters( 'signup_create_blog_meta', array( 'lang_id' => 1, 'public' => $public ) ); // depreciated
	$meta = apply_filters( 'add_signup_meta', $meta );

	// If this is a subdomain install, set up the site inside the root domain.
	if ( is_subdomain_install() )
		$domain = $blogname . '.' . preg_replace( '|^www\.|', '', $current_site->domain );

	nxtmu_create_blog( $domain, $path, $blog_title, $current_user->id, $meta, $nxtdb->siteid );
	bp_blogs_confirm_blog_signup($domain, $path, $blog_title, $current_user->user_login, $current_user->user_email, $meta);
	return true;
}



/* ------------------------------------------------
	Setup Theme
------------------------------------------------ */

if ( ! isset( $content_width ) ) $content_width = 584;

add_action( 'after_setup_theme', 'huddle_theme_setup' );

function huddle_theme_setup() {

	load_theme_textdomain( 'huddle', TEMPLATEPATH . '/languages' );

	$locale = get_locale();
	$locale_file = TEMPLATEPATH . "/languages/$locale.php";
	if ( is_readable( $locale_file ) ) require_once( $locale_file );

	add_theme_support( 'automatic-feed-links' );

	register_nav_menus( array(
		'main-menu'		=> __( 'Main Menu', 'huddle' ),
		'footer-menu'	=> __( 'Footer Menu', 'huddle' )
	) );

	add_theme_support( 'post-thumbnails' );

	set_post_thumbnail_size( 600, 303, true );

	add_image_size( 'post-medium', 290, 145, true );
	add_image_size( 'post-small', 35, 35, true );

}



/* ------------------------------------------------
   Load Theme Options
------------------------------------------------ */

if( ! function_exists( 'of_get_option' ) ) {
	function of_get_option( $name, $default = false ) {
		$config = get_option( 'optionsframework' );

		if ( ! isset( $config['id'] ) ) {
			return $default;
		}

		$options = get_option( $config['id'] );

		if ( isset( $options[$name] ) && ! empty( $options[$name] ) ) {
			return $options[$name];
		}

		return $default;
	}
}

if ( ! function_exists( 'optionsframework_init' ) ) {

	if ( STYLESHEETPATH == TEMPLATEPATH ) {
		define('OPTIONS_FRAMEWORK_URL', TEMPLATEPATH . '/admin/');
		define('OPTIONS_FRAMEWORK_DIRECTORY', get_template_directory_uri() . '/admin/');
	} else {
		define('OPTIONS_FRAMEWORK_URL', STYLESHEETPATH . '/admin/');
		define('OPTIONS_FRAMEWORK_DIRECTORY', get_stylesheet_directory_uri() . '/admin/');
	}

	require_once (OPTIONS_FRAMEWORK_URL . 'options-framework.php');
}



/* ------------------------------------------------
   Register Dependant Javascript Files
------------------------------------------------ */

add_action('nxt_enqueue_scripts', 'huddle_load_js');

if( ! function_exists( 'huddle_load_js' ) ) {
	function huddle_load_js() {
		global $is_IE;

		if ( is_admin() ) {

			

		} else {

			nxt_deregister_script( 'jquery' );
			nxt_deregister_script( 'l10n' );

			nxt_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js' );
			nxt_register_script( 'html5shim', 'http://html5shim.googlecode.com/svn/trunk/html5.js' );
			nxt_register_script( 'superfish', get_template_directory_uri() . '/js/superfish.js', array( 'jquery' ) );
			nxt_register_script( 'buddypress', get_template_directory_uri() . '/js/buddypress.js', array( 'jquery' ) );
			nxt_register_script( 'colorbox', get_template_directory_uri() . '/js/jquery.colorbox-min.js', array( 'jquery' ) );
			nxt_register_script( 'theme_custom', get_template_directory_uri() . '/js/custom.js', array( 'jquery' ), 1.0, TRUE );

			nxt_enqueue_script( array( 'superfish', 'colorbox', 'buddypress', 'theme_custom' ) );

			if( is_single() ) nxt_enqueue_script( 'comment-reply' );
			if( $is_IE ) nxt_enqueue_script( 'html5shim' );
		}
	}
}



/* ------------------------------------------------
   Remove Extra <p> Tags From Excerpt Output
------------------------------------------------ */

function huddle_excerpt($content) {
	$content = preg_replace(array('#<p>#', '#</p>#'), '', $content);
    return $content;
}
add_filter('the_excerpt', 'huddle_excerpt');



/* ------------------------------------------------
	Excerpt Length
------------------------------------------------ */

add_filter( 'excerpt_length', 'huddle_excerpt_length' );

function huddle_excerpt_length( $length ) {
	return 40;
}



/* ------------------------------------------------
	Excerpt More Link
------------------------------------------------ */

add_filter( 'excerpt_more', 'huddle_auto_excerpt_more' );

if( ! function_exists( 'huddle_auto_excerpt_more' ) ) {
	function huddle_auto_excerpt_more( $more ) {
		return '.';
	}
}



/* ------------------------------------------------
	Register Sidebars
------------------------------------------------ */

add_action( 'widgets_init', 'huddle_widgets_init' );

if( ! function_exists( 'huddle_widgets_init' ) ) {
	function huddle_widgets_init() {

		register_sidebar( array(
			'name' => __( 'Homepage Sidebar', 'huddle' ),
			'id' => 'sidebar-home',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		register_sidebar( array(
			'name' => __( 'Blog Sidebar', 'huddle' ),
			'id' => 'sidebar-blog',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div><div class='clear'></div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		register_sidebar( array(
			'name' => __( 'Page Sidebar', 'huddle' ),
			'id' => 'sidebar-page',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div><div class='clear'></div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		register_sidebar( array(
			'name' => __( 'Footer Sidebar', 'huddle' ),
			'id' => 'sidebar-footer',
			'description' => __( 'An optional widget area for your site footer', 'huddle' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => "</div><div class='clear'></div>",
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

	}
}



/* ------------------------------------------------
   Expanding Body Class
------------------------------------------------ */

add_filter( 'body_class', 'huddle_body_class' );

if( ! function_exists( 'huddle_body_class' ) ) {

	function huddle_body_class( $classes ) {
		
		if( isset( $_GET['w-iframe'] ) ) $classes[] = 'with-iframe';

		if( isset( $_GET['style'] ) ) {
			$classes[] = $_GET['style'];
		} else {
			$classes[] = of_get_option( 'style' );
		}

		if( ! is_user_logged_in() ) {
			$classes[] = 'not-logged-in';
		}

		return $classes;
	}

}


/* ------------------------------------------------
   Comments Template
------------------------------------------------ */

if( ! function_exists( 'huddle_comments' ) ) {
	function huddle_comments($comment, $args, $depth) {
	   $path = get_template_directory_uri();
	   $GLOBALS['comment'] = $comment;
	   ?>
	   
		<li <?php comment_class('clf'); ?> id="li-comment-<?php comment_ID() ?>">
			<div class="comment-box" id="comment-<?php comment_ID(); ?>">
				<div class="comment-avatar fl"><?php echo get_avatar( $comment, 35 ); ?></div>

				<div class="comment-meta fl">
					<p class="comment-author"><?php echo get_comment_author_link() ?></p>
					<p class="comment-date">
						<a class="comment-date-link" href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>"><em><?php comment_date('m.d.Y') ?></em></a>
						<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'before' => ' &middot; ' ) ) ); ?>
					</p>
				</div>

				<div class="comment-body fl">
					<?php if ( $comment->comment_approved == '0' ) : ?>
						<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'huddle' ); ?></em>
						<br />
					<?php endif; ?>

					<?php echo get_comment_text(); ?>
				</div>
				
				<div class="clear"></div>
			</div><!-- #comment-##  -->
	<?php
	}
}



/* ------------------------------------------------
   Load Files
------------------------------------------------ */

include 'functions/widgets/widget-enews.php';		// E-news Widget
include 'functions/widgets/widget-flickr.php';		// Flickr Widget
include 'functions/widgets/widget-recent-posts.php';// Recent Posts Widget
include 'functions/widgets/widget-tab-archive.php';	// Archives Widget
include 'functions/widgets/widget-tab-posts.php';	// Posts Widget
include 'functions/widgets/widget-tweets.php';		// Tweets Widget
include 'functions/widgets/widget-video.php';		// Video Widget

include 'functions/shortcodes.php';					// Shortcodes

include 'functions-buddypress.php';					// BuddyPress Functions



/* ------------------------------------------------
   Useful functions
------------------------------------------------ */

if( ! function_exists( 'print_pre' ) ) {
	function print_pre( $s, $var_dump = false ) {
		echo '<pre>';
		$var_dump ? var_dump( $s ) : print_r( $s );
		echo '</pre>';
	}
}

if( ! function_exists( 'nxt_trim_words' ) ) {
	function nxt_trim_words( $excerpt, $charlength, $more = '...' ) {
		$o = '';
	   $charlength++;
	   if(strlen($excerpt)>$charlength) {
	       $subex = substr($excerpt,0,$charlength-5);
	       $exwords = explode(" ",$subex);
	       $excut = -(strlen($exwords[count($exwords)-1]));
	       if($excut<0) {
	            $o .= substr($subex,0,$excut);
	       } else {
	       	    $o .= $subex;
	       }
	       $o .= $more;
	   } else {
		   $o .= $excerpt;
	   }
		return $o;
	}
}