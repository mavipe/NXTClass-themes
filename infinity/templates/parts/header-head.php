<?php 
/**
 * Infinity Theme: Header-Head
 *
 * All the stuff that's needed for the <head> section of
 * a NXTClass Theme
 *
 * @author Bowe Frankema <bowe@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Bowe Frankema
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package Infinity
 * @subpackage templates
 * @since 1.0
 */
?>
<head profile="http://gmpg.org/xfn/11">
	<?php
		do_action( 'open_head' );
	?>
	<!-- basic title -->
		<title>
		<?php /*SEO optimized Titles if Yoast SEO Plugin is not installed. If it is, use default nxt_title */ if ( function_exists('yoast_breadcrumb') ) : 
			nxt_title();
			else:
			infinity_base_title();
			endif;
		?>	
	</title>		<!-- core meta tags -->
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<meta name="generator" content="NXTClass <?php bloginfo('version'); ?>" />
	<!-- core link tags -->
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> <?php _e( 'Blog Posts RSS Feed', infinity_text_domain ) ?>" href="<?php bloginfo('rss2_url'); ?>" />
	<link rel="alternate" type="application/atom+xml" title="<?php bloginfo('name'); ?> <?php _e( 'Blog Posts Atom Feed', infinity_text_domain ) ?>" href="<?php bloginfo('atom_url'); ?>" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />
	<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	<?php
		nxt_head();
		do_action( 'close_head' );
	?>
</head>
