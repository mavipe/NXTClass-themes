<?php
/**
 * ICE API: scheme helper class file
 *
 * @author Marshall Sorenson <marshall@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Marshall Sorenson
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package ICE
 * @subpackage schemes
 * @since 1.0
 */

/**
 * Make Scheming Easy
 *
 * @package ICE
 * @subpackage schemes
 */
final class ICE_Scheme extends ICE_Base
{
	/**
	 * Parent theme directive
	 */
	const DIRECTIVE_PARENT_THEME = 'parent_theme';
	/**
	 * Image root directive
	 */
	const DIRECTIVE_IMAGE_ROOT = 'image_root';
	/**
	 * Style root directive
	 */
	const DIRECTIVE_STYLE_ROOT = 'style_root';
	/**
	 * Script root directive
	 */
	const DIRECTIVE_SCRIPT_ROOT = 'script_root';
	/**
	 * Feature ini section
	 */
	const DIRECTIVE_FEATURE = 'feature';
	/**
	 * Style ini section
	 */
	const DIRECTIVE_STYLE_DEFS = 'style';
	/**
	 * Style depends ini section
	 */
	const DIRECTIVE_STYLE_DEPS = 'style_depends';
	/**
	 * Style actions ini section
	 */
	const DIRECTIVE_STYLE_ACTS = 'style_actions';
	/**
	 * Style conditions ini section
	 */
	const DIRECTIVE_STYLE_CONDS = 'style_conditions';
	/**
	 * Script ini section
	 */
	const DIRECTIVE_SCRIPT_DEFS = 'script';
	/**
	 * Script depends ini section
	 */
	const DIRECTIVE_SCRIPT_DEPS = 'script_depends';
	/**
	 * Script actions ini section
	 */
	const DIRECTIVE_SCRIPT_ACTS = 'script_actions';
	/**
	 * Script conditions ini section
	 */
	const DIRECTIVE_SCRIPT_CONDS = 'script_conditions';
	/**
	 * Advanced settings ini section
	 */
	const DIRECTIVE_ADVANCED = 'advanced';
	/**
	 * jQuery UI stylesheet path directive
	 */
	const DIRECTIVE_UI_STYLESHEET = 'ui_stylesheet';
	/**
	 * Script domain directive
	 */
	const DIRECTIVE_SCRIPT_DOMAIN = 'script_domain';
	/**
	 * Options save single directive
	 */
	const DIRECTIVE_OPT_SAVE_SINGLE = 'options_save_single';

	/**
	 * Singleton instance
	 *
	 * @var ICE_Scheme
	 */
	static private $instance;

	/**
	 * Name of the root theme
	 *
	 * @var string
	 */
	private $root_theme;

	/**
	 * Relative path to the config dir relative to the theme's template path
	 *
	 * @var string
	 */
	private $config_dir;

	/**
	 * Name of the configuration ini file that is preferred by the API
	 *
	 * @var string
	 */
	private $config_file;

	/**
	 * Relative path to the exts dir relative to the theme's template path
	 *
	 * @var string
	 */
	private $exts_dir;

	/**
	 * Relative path to the docs dir relative to the theme's template path
	 *
	 * @var string
	 */
	private $docs_dir;

	/**
	 * Stack of config files that have been loaded
	 *
	 * @var ICE_Stack
	 */
	private $config_files_loaded;
	
	/**
	 * Theme stack
	 *
	 * @var ICE_Stack
	 */
	private $themes;

	/**
	 * Cache of reversed theme stack array
	 *
	 * @var array
	 */
	private $theme_stack_topdown;

	/**
	 * The directives registry instance
	 * 
	 * @var ICE_Init_Directive_Registry
	 */
	private $directives;

	/**
	 * The enqueue helper instance
	 * 
	 * @var ICE_Scheme_Enqueue
	 */
	private $enqueue;

	/**
	 * The exports manager instance
	 * 
	 * @var ICE_Export_Manager
	 */
	private $exports;

	/**
	 * This is a singleton
	 */
	private function __construct()
	{
		// initialize themes map
		$this->themes = new ICE_Stack();
		$this->directives = new ICE_Init_Directive_Registry();
		$this->config_files_loaded = new ICE_Stack();

		// set up exports
		$this->exports = new ICE_Export_Manager();
		$this->exports->add( 'styles', new ICE_Component_Style_Export( 'dynamic', 'css' ) );
		$this->exports->add( 'scripts', new ICE_Component_Script_Export( 'dynamic', 'js' ) );
	}

	/**
	 * Return the singleton instance of the scheme
	 *
	 * @return ICE_Scheme
	 */
	static public function instance()
	{
		if ( !self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * One time initialization helper
	 *
	 * @param string $root_theme
	 * @param string $config_dir
	 * @param string $config_file
	 * @return ICE_Scheme
	 */
	public function init( $root_theme )
	{
		// do not init same scheme twice
		if ( $this->root_theme ) {
			return;
		}

		// setup config
		$this->set_root_theme( $root_theme );

		// load it
		$this->load();

		// add filters
		$this->add_filters();

		// run theme feature support helper
		$this->feature_support();

		// some scheme initializations must occur after nxt theme setup
		add_action( 'after_setup_theme', array($this, 'init_enqueueing') );
		add_action( 'after_setup_theme', array($this, 'load_functions') );
		add_action( 'ice_enqueue_styles', array($this, 'exports_refresh'), 0 );
		add_action( 'ice_enqueue_scripts', array($this, 'exports_refresh'), 0 );

		return $this;
	}

	/**
	 * Return directives registry
	 *
	 * @return ICE_Init_Directive_Registry
	 */
	final public function directives()
	{
		return $this->directives;
	}

	/**
	 * Get scheme enqueue helper
	 *
	 * @return ICE_Scheme_Enqueue
	 */
	public function enqueue()
	{
		if ( $this->enqueue instanceof ICE_Scheme_Enqueue ) {
			return $this->enqueue;
		}

		throw new Exception( 'The enqueuer has not been initialized yet' );
	}

	/**
	 * Get exports manager
	 *
	 * @return ICE_Export_Manager
	 */
	public function exports()
	{
		if ( $this->exports instanceof ICE_Export_Manager ) {
			return $this->exports;
		}

		throw new Exception( 'The export manager has not been initialized yet' );
	}

	/**
	 * Don't ever call this manually
	 *
	 * @internal
	 */
	public function init_enqueueing()
	{
		if ( !$this->enqueue instanceof ICE_Scheme_Enqueue ) {
			$this->enqueue = new ICE_Scheme_Enqueue( $this );
		}
	}

	/**
	 * Set the name of the root theme
	 *
	 * @param string $name
	 * @return boolean
	 */
	private function set_root_theme( $name )
	{
		// is root theme empty?
		if ( empty( $this->root_theme ) ) {
			// yes, ok to set it
			$this->root_theme = $name;
		}

		// maintain the chain
		return $this;
	}

	/**
	 * Set the name of the dir under the child themes where the config file lives
	 *
	 * @param string $dir_name
	 * @return boolean
	 */
	final public function set_config_dir( $dir_name )
	{
		if ( empty( $this->config_dir ) ) {
			$this->config_dir = $dir_name;
		} else {
			throw new Exception( 'Cannot set config dir, already set' );
		}

		return $this;
	}

	/**
	 * Set the name of the config file that your API uses
	 *
	 * @param string $file_name
	 * @return boolean
	 */
	final public function set_config_file( $file_name )
	{
		if ( empty( $this->config_file ) ) {
			$this->config_file = $file_name;
		} else {
			throw new Exception( 'Cannot set config file, already set' );
		}

		return $this;
	}

	/**
	 * Set the name of the dir under the child themes where the documents directory lives
	 *
	 * @param string $dir_name
	 * @return boolean
	 */
	final public function set_docs_dir( $dir_name )
	{
		if ( empty( $this->docs_dir ) ) {
			$this->docs_dir = $dir_name;
		} else {
			throw new Exception( 'Cannot set docs dir, already set' );
		}

		return $this;
	}

	/**
	 * Set the name of the dir under the child themes where the extensions directory lives
	 *
	 * @param string $dir_name
	 * @return boolean
	 */
	final public function set_exts_dir( $dir_name )
	{
		// only set if not already
		if ( empty( $this->exts_dir ) ) {
			// set property
			$this->exts_dir = $dir_name;
		} else {
			throw new Exception( 'Cannot set extensions dir, already set' );
		}

		return $this;
	}

	/**
	 * Add template filters
	 */
	private function add_filters()
	{
		// only add filters if there is at least one theme
		if ( count( $this->themes ) ) {

			// template filter callback
			$filter = array( $this, 'filter_template' );

			// add filters
			add_filter( '404_template', $filter );
			add_filter( 'search_template', $filter );
			add_filter( 'taxonomy_template', $filter );
			add_filter( 'front_page_template', $filter );
			add_filter( 'home_template', $filter );
			add_filter( 'attachment_template', $filter );
			add_filter( 'single_template', $filter );
			add_filter( 'page_template', $filter );
			add_filter( 'category_template', $filter );
			add_filter( 'tag_template', $filter );
			add_filter( 'author_template', $filter );
			add_filter( 'date_template', $filter );
			add_filter( 'archive_template', $filter );
			add_filter( 'comments_popup_template', $filter );
			add_filter( 'paged_template', $filter );
			add_filter( 'index_template', $filter );
			add_filter( 'comments_template', $filter );

		}
	}

	/**
	 * Load the scheme, using a theme as the starting point for the stack
	 *
	 * This method recursively crawls UP the theme hiearachy
	 *
	 * @param string $theme Theme's *directory name*
	 * @return boolean
	 */
	public function load( $theme = null )
	{
		if ( empty( $theme ) ) {
			$theme = ICE_ACTIVE_THEME;
		}

		// get path to config file
		$ini_file = $this->theme_config_file( $theme );

		// does ini file exist?
		if ( ICE_Files::cache($ini_file)->is_readable() ) {
			// parse it
			$ini = parse_ini_file( $ini_file, true );
			// push onto loaded stack
			$this->config_files_loaded->push( $ini_file );
		} else {
			// yipes, theme has no ini file.
			// assume that parent theme is the root theme
			$ini[self::DIRECTIVE_PARENT_THEME] = $this->root_theme;
		}

		// make sure we got something
		if ( $ini !== false ) {

			// parent theme?
			$parent_theme =
				isset( $ini[self::DIRECTIVE_PARENT_THEME] )
					? $ini[self::DIRECTIVE_PARENT_THEME]
					: false;

			// recurse up the theme stack if necessary
			if ( $parent_theme ) {
				// load it
				$this->load( $parent_theme );
			}

			// push onto the stack AFTER recursion
			$this->themes->push( $theme );

			// loop through directives and set them
			foreach ( $ini as $name => $value ) {
				if ( $name == self::DIRECTIVE_ADVANCED ) {
					if ( is_array( $value ) ) {
						foreach ( $value as $name_adv => $value_adv ) {
							$this->directives()->set( $theme, $name_adv, $value_adv, true );
						}
					}
					continue;
				} else {
					$this->directives()->set( $theme, $name, $value, true );
				}
			}

			// add extension dir to extension loader
			ICE_Ext_Loader::path( $this->theme_file( $theme, $this->exts_dir ) );

		} else {
			throw new Exception( 'Failed to parse theme ini file: ' . $ini_file );
		}
	}

	/**
	 * Enable/disable feature support
	 */
	private function feature_support()
	{
		// any features set?
		if ( $this->directives()->has( self::DIRECTIVE_FEATURE ) ) {
			// at least one feature was set, get map
			$map = $this->directives()->get_map( self::DIRECTIVE_FEATURE );
			// loop through and add theme support for each feature
			foreach ( $map as $directive ) {
				// loop all features
				foreach( $directive->get_value() as $feature => $toggle ) {
					// toggled on?
					if ( (boolean) $toggle === true ) {
						add_theme_support( $feature );
					} else {
						remove_theme_support( $feature );
					}
				}
			}
		}
	}

	/**
	 * Try to load function files for themes in stack
	 *
	 * @internal
	 */
	public function load_functions()
	{
		// loop through theme stack
		foreach ( $this->theme_stack() as $theme  ) {
			// load functions file if it exists
			$filename = $this->theme_file( $theme, 'functions.php' );
			// try to load it
			if ( ICE_Files::cache($filename)->is_readable() ) {
				require_once $filename;
			}
		}
	}

	/**
	 * Refresh export files if necessary
	 */
	public function exports_refresh( $force = false )
	{
		// hard refresh toggle
		static $hard_refresh = false;

		// export instance depends on which filter is being run
		switch( current_filter() ) {
			// enqueue styles action
			case 'ice_enqueue_styles':
				// grab the styles exporter
				$export = $this->exports()->get( 'styles' );
				break;
			// enqueue scripts action
			case 'ice_enqueue_scripts':
				// grab the scripts exporter
				$export = $this->exports()->get( 'scripts' );
				break;
			// no matching filter... might be a hard refresh
			default:
				// force toggled on?
				if ( true === $force ) {
					// toggle hard refresh ON
					$hard_refresh = true;
					// manually call style enqueuer
					ICE_Enqueue::instance()->do_enqueue_styles();
					// manually call script enqueuer
					ICE_Enqueue::instance()->do_enqueue_scripts();
					// toggle hard refresh OFF
					$hard_refresh = false;
				}
				// return either way
				return;
		}

		// are we using the cache?
		if ( true == ICE_CACHE_EXPORTS && false == $hard_refresh ) {
			// yes, loop all config files
			foreach ( $this->config_files_loaded as $file ) {
				// get file last mod time
				$mtime = @filemtime( $file );
				// check if stale
				if ( $export->stale( $mtime ) ) {
					// call export refresher
					return $this->export_refresh( $export );
				}
			}
			// cache is up to date, did NOT refresh
			return false;
		}

		// update the export
		return $this->export_refresh( $export );
	}

	/**
	 * Refresh one export
	 *
	 * @param ICE_Export $export
	 * @return boolean
	 */
	final protected function export_refresh( ICE_Export $export )
	{
		// loop all component registries and pass them the exporters
		foreach ( ICE_Policy::all() as $policy ) {
			// call accept on the registry for the export
			$policy->registry()->accept( $export );
		}

		// update it
		$export->update();
		
		// all done
		return true;
	}

	/**
	 * Enable components for the scheme by passing a valid policy object
	 *
	 * @param ICE_Policy $policy
	 * @param string $ini_file_name
	 * @return boolean
	 */
	public function enable_component( ICE_Policy $policy )
	{
		// loop through entire theme stack BOTTOM UP and try to load options
		foreach( $this->theme_stack( false ) as $theme ) {

			// path to ini file
			$ini_file = $this->theme_file( $theme, $this->config_dir, $policy->get_handle() . '.ini' );

			// load the option config if it exists
			if ( ICE_Files::cache($ini_file)->is_readable() ) {

				// skip loaded files
				if ( $this->config_files_loaded->contains( $ini_file ) ) {
					continue;
				}
				
				// try to load ini file
				if ( $policy->registry()->load_config_file( $ini_file, $theme ) ) {
					// push onto loaded stack
					$this->config_files_loaded->push( $ini_file );
				}
			}
		}

		return true;
	}

	/**
	 * If template exists in scheme, return it, otherwise return the original template
	 *
	 * @internal
	 * @param string $template
	 * @return string
	 */
	public function filter_template( $template )
	{
		// fall back to index
		if ( empty( $template ) ) {
			$template = 'index.php';
		}

		// see if it exists in the scheme
		$scheme_template = $this->locate_template( array( basename( $template ) ) );

		// return scheme template?
		if ( $scheme_template ) {
			return $scheme_template;
		} else {
			return $template;
		}
	}

	/**
	 * Find and optionally load template(s) if it exists anywhere in the scheme
	 *
	 * @param string|array $template_names
	 * @param boolean $load Auto load template if set to true
	 * @return string
	 */
	public function locate_template( $template_names, $load = false )
	{
		// must have at least one theme to search
		if ( count( $this->themes ) ) {

			// convert string arg to array
			if ( !is_array( $template_names ) ) {
				$template_names = array( $template_names );
			}

			// loop through all templates
			foreach ( $template_names as $template_name ) {

				// get all possible template paths
				$template_paths = $this->theme_dirs( $template_name );

				// loop through all templates
				foreach ( $template_paths as $template_path ) {

					// does it exist?
					if ( ICE_Files::cache($template_path)->is_readable() ) {
						// load it?
						if ($load) {
							load_template( $template_path );
						}
						// return the located template path
						return $template_path;
					}
				}
			}
		}

		// didn't find a template
		return '';
	}

	/**
	 * Return theme stack as an array
	 *
	 * @param boolean $top_down
	 * @return array
	 */
	public function theme_stack( $top_down = true )
	{
		// is there anything in the stack yet?
		if ( $this->themes->count() === 0 ) {
			// not good. throw an exception here so we don't have to
			// act paranoid and check the result of every call to this.
			throw new Exception( 'You are trying to get the theme stack before it has been loaded' );
		}

		// top down?
		if ( true === $top_down ) {
			// empty cache?
			if ( null === $this->theme_stack_topdown ) {
				// populate cache
				$this->theme_stack_topdown = $this->themes->to_array( true );
			}
			// return reversed array
			return $this->theme_stack_topdown;
		} else {
			// return array as is
			return $this->themes->to_array();
		}
		
	}

	/**
	 * Return path to a theme directory
	 *
	 * @param string $theme
	 * @return string
	 */
	public function theme_dir( $theme )
	{
		return ICE_Files::theme_dir( $theme );
	}

	/**
	 * Return array of all theme root directory paths
	 *
	 * @param string $file_names,...
	 * @return array
	 */
	public function theme_dirs()
	{
		// get all args
		$file_names = func_get_args();

		// paths to return
		$paths = array();

		// loop through theme stack
		foreach ( $this->theme_stack() as $theme ) {
			$paths[] = $this->theme_file( $theme, $file_names );
		}

		return $paths;
	}

	/**
	 * Return array of all theme documentation dirs
	 *
	 * @return array
	 */
	public function theme_documentation_dirs()
	{
		return $this->theme_dirs( $this->docs_dir );
	}

	/**
	 * Return path to a theme file
	 *
	 * @param string $theme
	 * @param string $file_names,...
	 */
	public function theme_file( $theme = null )
	{
		// get all args
		$args = func_get_args();
		array_shift($args);

		// handle empty theme
		if ( empty( $theme ) ) {
			$theme = $this->root_theme;
		}

		// one or more args left, then we got some file names
		if ( count($args) >= 1 ) {
			if ( count($args) == 1 && is_array(reset($args)) ) {
				$file_names = $args[0];
			} else {
				$file_names = $args;
			}
		} else {
			throw new Exception( 'No file names passed' );
		}

		return $this->theme_dir( $theme ) . '/' . implode( '/', $file_names );
	}

	/**
	 * Return path to a specific theme's main configuration file
	 *
	 * @param string $theme
	 * @return string
	 */
	private function theme_config_file( $theme )
	{
		return $this->theme_file( $theme, $this->config_dir, $this->config_file . '.ini' );
	}

	/**
	 * Locate a theme file, giving priority to top themes in the stack
	 *
	 * If first argument is a ICE_Map instance, it is expected to be
	 * a map of theme directives whose values are relative path prefixes.
	 *
	 * @param ICE_Map $prefix_map Optional map of directives which define path prefixes
	 * @param string $file_names,... The file names that make up the RELATIVE path to the theme root
	 * @return string|false
	 */
	public function locate_file()
	{
		// get all args
		$file_names = func_get_args();

		// no prefix map by default
		$prefix_map = null;

		// prefixes map?
		if ( !empty( $file_names ) ) {
			if ( $file_names[0] instanceof ICE_Map ) {
				$prefix_map = array_shift( $file_names );
			}
		}

		// still have something?
		if ( empty( $file_names ) ) {
			return false;
		}

		// loop through theme stack
		foreach ( $this->theme_stack() as $theme ) {

			// build path to stackfile
			$stack_file = $this->theme_dir( $theme );

			// inject prefix?
			if ( $prefix_map && $prefix_map->contains($theme) ) {
				$stack_file .= '/' . $prefix_map->item_at($theme)->value;
			}

			// append requested path
			$stack_file .= '/' . implode( '/', $file_names );

			// does stack file exist?
			if ( ICE_Files::cache($stack_file)->is_readable() ) {
				return $stack_file;
			}
		}

		return false;
	}

	/**
	 * Locate a theme config file, giving priority to top themes in the stack
	 *
	 * @param string $file_names,... The file names that make up the RELATIVE path to the theme config root
	 * @return string|false
	 */
	public function locate_config_file()
	{
		// get all args
		$args = func_get_args();
		// config root is first arg
		array_unshift( $args, $this->config_dir );
		// locate it
		return call_user_func_array( array($this,'locate_file'), $args );
	}

	/**
	 * Locate a theme component extension file, giving priority to top themes in the stack
	 *
	 * @param string $file_names,... The file names that make up the RELATIVE path to the theme extensions root
	 * @return string|false
	 */
	public function locate_extension_file()
	{
		// get all args
		$args = func_get_args();
		// extension root is first arg
		array_unshift( $args, $this->exts_dir );
		// locate it
		return call_user_func_array( array($this,'locate_file'), $args );
	}

	/**
	 * Locate a theme asset, giving priority to top themes in the stack
	 *
	 * @param string $path_directive The scheme directive which contains the asset path
	 * @return string|false
	 */
	private function locate_asset( $path_directive )
	{
		// image root must be set
		if ( $this->directives()->has( $path_directive ) ) {
			// get all args
			$args = func_get_args();
			// throw out the first one
			array_shift( $args );
			// add directive path
			array_unshift( $args, $this->directives()->get($path_directive)->value );
			// locate it
			return call_user_func_array( array($this,'locate_file'), $args );
		}

		return false;
	}

	/**
	 * Locate a theme image
	 *
	 * @param string $file_names,... The file names that make up the RELATIVE path to the theme root
	 * @return string|false
	 */
	public function locate_image()
	{
		$args = func_get_args();
		array_unshift( $args, self::DIRECTIVE_IMAGE_ROOT );
		return call_user_func_array( array($this, 'locate_asset'), $args );
	}

	/**
	 * Locate a theme style
	 *
	 * @param string $file_names,... The file names that make up the RELATIVE path to the theme root
	 * @return string|false
	 */
	public function locate_style()
	{
		$args = func_get_args();
		array_unshift( $args, self::DIRECTIVE_STYLE_ROOT );
		return call_user_func_array( array($this, 'locate_asset'), $args );
	}

	/**
	 * Locate a theme script
	 *
	 * @param string $file_names,... The file names that make up the RELATIVE path to the theme root
	 * @return string|false
	 */
	public function locate_script()
	{
		$args = func_get_args();
		array_unshift( $args, self::DIRECTIVE_SCRIPT_ROOT );
		return call_user_func_array( array($this, 'locate_asset'), $args );
	}

	/**
	 * Look for a header in the scheme stack
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function get_header( $name = null )
	{
		$templates = array();

		if ( isset($name) )
			$templates[] = "header-{$name}.php";

		$templates[] = "header.php";

		$located_template = $this->locate_template( $templates );

		if ( $located_template ) {
			do_action( 'get_header', $name );
			return load_template( $located_template );
		} else {
			return get_header( $name );
		}
	}

	/**
	 * Look for a footer in the scheme stack
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function get_footer( $name = null )
	{
		$templates = array();

		if ( isset($name) )
			$templates[] = "footer-{$name}.php";

		$templates[] = "footer.php";

		$located_template = $this->locate_template( $templates );

		if ( $located_template ) {
			do_action( 'get_footer', $name );
			return load_template( $located_template );
		} else {
			return get_footer( $name );
		}
	}

	/**
	 * Look for a sidebar in the scheme stack
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function get_sidebar( $name = null )
	{
		$templates = array();

		if ( isset($name) )
			$templates[] = "sidebar-{$name}.php";

		$templates[] = "sidebar.php";

		$located_template = $this->locate_template( $templates );

		if ( $located_template ) {
			do_action( 'get_sidebar', $name );
			return load_template( $located_template );
		} else {
			return get_sidebar( $name );
		}
	}

	/**
	 * Look for a template part in the scheme stack
	 *
	 * @param string $slug The slug name for the generic template.
	 * @param string $name The name of the specialised template.
	 */
	function get_template_part( $slug, $name = null )
	{
		$templates = array();
		if ( isset($name) )
			$templates[] = "{$slug}-{$name}.php";

		$templates[] = "{$slug}.php";

		$located_template = $this->locate_template( $templates );

		if ( $located_template ) {
			do_action( "get_template_part_{$slug}", $slug, $name );
			load_template( $located_template, false );
		} else {
			get_template_part( $slug, $name );
		}
	}

	/**
	 * Look for a search form in the scheme stack
	 *
	 * @param boolean $echo Set to false to return markup instead of printing
	 * @return mixed
	 */
	function get_search_form( $echo = true )
	{
		$located_template = $this->locate_template( 'searchform.php' );

		if ( $located_template ) {
			do_action( 'get_search_form' );
			load_template( $located_template, false );
		} else {
			return get_search_form( $echo );
		}
	}
}

?>
