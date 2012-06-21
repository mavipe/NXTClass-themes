<?php
/**
 * Infinity Theme: dashboard loader
 *
 * @author Marshall Sorenson <marshall@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Marshall Sorenson
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package Infinity
 * @subpackage dashboard
 * @since 1.0
 */

//
// Redirect on activation
//

// make this global just to be safe
global $pagenow;

// was i just activated?
if (
	$pagenow == 'themes.php' &&
	isset( $_GET['activated'] )
) {
	// yes, redirect
	nxt_redirect( admin_url( 'themes.php?page=' . INFINITY_ADMIN_PAGE ) );
	// no more exec
	exit;
}

//
// Files
//

/**
 * Include control panel functions
 */
require_once( INFINITY_ADMIN_PATH . '/cpanel.php' );


//
// Functions
//

/**
 * Adds the Infinity submenu item to the NXTClass menu
 *
 * @package Infinity
 * @subpackage dashboard
 */
function infinity_dashboard_menu_setup()
{
	// get name of current theme
	$theme_name = get_current_theme();

	// format title/page name
	$menu_title = __( sprintf( '%s Options', $theme_name ), infinity_text_domain );

	// add appearance submenu item
	add_theme_page(
		$menu_title,
		$menu_title,
		'manage_options',
		INFINITY_ADMIN_PAGE,
		'infinity_dashboard_cpanel_screen'
	);
}
add_action( 'admin_menu', 'infinity_dashboard_menu_setup' );

/**
 * Locate a dashboard template relative to the template dir root
 *
 * @package Infinity
 * @subpackage dashboard
 * @param string $rel_path Relative path to template from dashboard template root
 * @return string
 */
function infinity_dashboard_locate_template( $rel_path )
{
	// format template path
	$template = INFINITY_ADMIN_DIR . '/templates/' . $rel_path;

	// locate the template
	return infinity_locate_template( $template );
}

/**
 * Load a dashboard template relative to the template dir root
 *
 * @package Infinity
 * @subpackage dashboard
 * @param string $rel_path Relative path to template from dashboard template root
 * @param array|stdClass $args Variables to inject into template
 * @param array|stdClass $defaults Default values of variables being injected into template
 */
function infinity_dashboard_load_template( $rel_path, $args = null, $defaults = null )
{
	// populate local scope
	extract( nxt_parse_args( $args, (array) $defaults ) );

	// locate and include the template
	include( infinity_dashboard_locate_template( $rel_path ) );
}

/**
 * Return path to a dashboard image
 *
 * @package Infinity
 * @subpackage dashboard
 * @param string $name image file name
 * @return string
 */
function infinity_dashboard_image( $name )
{
	return INFINITY_ADMIN_URL . '/assets/images/' . $name;
}

/**
 * Return URL to a screen component route
 *
 * @package Infinity
 * @subpackage dashboard
 * @param string $params,...
 * @return string
 */
function infinity_dashboard_screen_url()
{
	$args = func_get_args();
	
	$url = INFINITY_AJAX_URL . call_user_func_array( 'infinity_screens_route', $args );
	
	return apply_filters( 'infinity_dashboard_screen_url', $url, $args );
}

/**
 * Publish a document page
 *
 * @package Infinity
 * @subpackage dashboard
 * @param string $page Name of page to publish
 */
function infinity_dashboard_doc_publish( $page = null )
{
	ICE_Loader::load( 'utils/docs' );
	$doc = new ICE_Docs( ICE_Scheme::instance()->theme_documentation_dirs(), $page );
	$doc->set_pre_filter( 'infinity_dashboard_doc_filter' );
	$doc->publish();
}

/**
 * Publish a developer (core) document page
 *
 * @package Infinity
 * @subpackage dashboard
 * @param string $page Name of page to publish
 */
function infinity_dashboard_devdoc_publish( $page = null )
{
	ICE_Loader::load( 'utils/docs' );
	$doc = new ICE_Docs( INFINITY_ADMIN_DOCS_PATH, $page );
	$doc->set_pre_filter( 'infinity_dashboard_doc_filter' );
	$doc->publish();
}

/**
 * Pre filter doc contents before parsing
 *
 * @package Infinity
 * @subpackage dashboard
 * @param string $contents
 * @return string
 */
function infinity_dashboard_doc_filter( $contents )
{
	// replace internal URLs with valid URLs (infinity://admin:foo/cpanel/docs/foo_page)
	return preg_replace_callback( '/infinity:\/\/([a-z]+)(:([a-z]+))?((\/[\w\.]+)*)/', 'infinity_dashboard_doc_filter_cb', $contents );
}

/**
 * Pre filter callback
 *
 * @package Infinity
 * @subpackage dashboard
 * @param array $match
 * @return string
 */
function infinity_dashboard_doc_filter_cb( $match )
{
	// where are we?
	$location = $match[1];

	// call type
	$call_type = $match[3];

	// the route
	$route = trim( $match[4], INFINITY_ROUTE_DELIM );

	switch ( $location ) {
		case 'admin':
			switch( $call_type ) {
				case '':
				case 'action':
					return infinity_dashboard_screen_url( $route );
				case 'image':
					return infinity_dashboard_image( $route );
				case 'doc':
					return infinity_dashboard_screen_url( 'cpanel', 'ddocs', $route );
			}
		case 'theme':
			switch( $call_type ) {
				case '':
				case 'image':
					return infinity_image_url( $route );
				case 'doc':
					return infinity_dashboard_screen_url( 'cpanel', 'docs', $route );
			}
	}
}

?>
