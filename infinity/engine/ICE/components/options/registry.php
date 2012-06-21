<?php
/**
 * ICE API: options registry
 *
 * @author Marshall Sorenson <marshall@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Marshall Sorenson
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package ICE-components
 * @subpackage options
 * @since 1.0
 */

ICE_Loader::load( 'base/registry', 'components/options/factory', 'utils/ajax' );

/**
 * Make keeping track of options easy
 *
 * @package ICE-components
 * @subpackage options
 */
abstract class ICE_Option_Registry extends ICE_Registry
{
	/**
	 * Enqueue required scripts
	 */
	public function init_scripts()
	{
		// call parent
		parent::init_scripts();

		// jQuery UI is always needed
		nxt_enqueue_script( 'jquery-ui-accordion' );
		nxt_enqueue_script( 'jquery-ui-button' );
		nxt_enqueue_script( 'jquery-ui-dialog' );
		nxt_enqueue_script( 'jquery-ui-progressbar' );
		nxt_enqueue_script( 'jquery-ui-tabs' );

		// call localize script *LAST*
		$this->localize_script();
	}

	/**
	 * Template method to allow localization of scripts
	 */
	protected function localize_script()
	{
		// override this to apply special localizations that apply to your implementation
	}

	public function load_feature_options_file( ICE_Feature $feature, $filename )
	{
		// try to parse the options file into INI sections
		$sections = parse_ini_file( $filename, true );

		// get any sections?
		if ( is_array( $sections ) && count( $sections ) ) {
			// inject required feature directive into every option config
			foreach ( $sections as $name => &$config) {
				// set the feature
				$config['feature'] = $feature->name;
			}
		}

		// load the sections as normal
		return $this->load_config_sections( $sections );
	}
	
	/**
	 * Load option config as a feature option if syntax of name matches pattern
	 * or if the "feature" directive is set.
	 *
	 * @param string $name
	 * @param array $config
	 * @return boolean
	 */
	public function load_feature_option( $name, $config )
	{
		// defaults
		$option_name = null;

		// feature explicitly set?
		if ( isset( $config['feature'] ) ) {
			$option_name = $config['feature'] . self::SUB_OPTION_GLUE . $name;
		} else {
			// split for possible sub option syntax
			$parts = explode( self::SUB_OPTION_DELIM, $name );
			// if has exactly two parts its a feature option
			if ( count($parts) == 2 ) {
				// feature name is the first string
				$config['feature'] = $parts[0];
				// option name is both strings glued with a hyphen
				$option_name = implode( self::SUB_OPTION_GLUE, $parts );
			}
		}

		// have all feature option details?
		if ( isset( $config['feature'] ) && $option_name ) {
			// set required feature
			$config['required_feature'] = $config['feature'];
			// clean up parent
			if ( isset( $config['parent'] ) ) {
				$config['parent'] = $this->normalize_name( $config['parent'] );
			}
			// call parent config loader
			if ( $this->load_config_map( $option_name, $config ) ) {
				// successfully loaded feature sub option
				return true;
			} else {
				// this is really bad
				throw new Exception( sprintf(
					'Failed to load "%s" as an option for feature "%s"', $option_name, $config['feature'] ) );
			}
		}

		// not a feature option
		return false;
	}

	/**
	 * Return sibling options as an array
	 *
	 * @param ICE_Option $option
	 * @return array
	 */
	public function get_siblings( ICE_Option $option )
	{
		// options to return
		$options = array();

		// render options that require this one
		foreach ( $this->get_all() as $sibling_option ) {
			if ( $option->name == $sibling_option->parent ) {
				$options[] = $sibling_option;
			}
		}

		return $options;
	}

	/**
	 * Return registered options as an array
	 *
	 * @param ICE_Section $section Limit options to one section by passing a section object
	 * @return array
	 */
	public function get_for_section( ICE_Section $section )
	{
		// options to return
		$options = array();

		// loop through and compare names
		foreach ( parent::get_all() as $option ) {

			// do section names match?
			if ( $section->name != $option->section ) {
				continue;
			}

			// add to array
			$options[] = $option;
		}

		// return them
		return $options;
	}

	/**
	 * Return registered options that are valid in a menu
	 *
	 * It does not make sense to list an option in a menu which requires another option,
	 * so this helper method will return an array without them.
	 *
	 * @param ICE_Section $section Limit options to one section
	 * @return array
	 */
	public function get_menu_options( ICE_Section $section = null )
	{
		// get all options for section
		$options = $this->get_for_section( $section );

		foreach ( $options as $key => $option ) {
			// remove options that require another option
			if ( $option->parent ) {
				unset( $options[$key] );
			}
			// remove options that aren't supported
			if ( !$option->supported() ) {
				unset( $options[$key] );
			}
		}

		return $options;
	}

	/**
	 * Look through POST vars for options from this registry and try to save them
	 *
	 * @return integer Number of options saved
	 */
	public function process_form()
	{
		if ( empty( $_POST ) ) {
			return false;
		} elseif ( isset( $_POST[ICE_Option_Renderer::FIELD_MANIFEST] ) ) {

			$manifest = $_POST[ICE_Option_Renderer::FIELD_MANIFEST];

			// "save only these" option names if param is set
			$save_options =
				!empty( $_POST['option_names'] ) ?
				explode( ',', $_POST['option_names'] ) : null;

			// do a reset if option reset param is set
			$reset_options =
				!empty( $_POST['option_reset'] ) ?
				( (boolean) $_POST['option_reset'] ) : false;

			// keep track of how many were updated
			$save_count = 0;

			// loop through manifest options
			foreach ( $manifest as $option_name ) {

				// skip options that don't exist in save options if set
				if ( !empty( $save_options ) && !in_array( $option_name, $save_options ) ) {
					continue;
				}

				// is this option registered?
				if ( $this->has( $option_name ) ) {
					// get the option
					$option = $this->get( $option_name );
					// look for option name as POST key
					if ( array_key_exists( $option->name, $_POST ) ) {
						// reset?
						if ( $reset_options ) {
							$option->delete();
						} else {
							// get new value
							$new_value = $_POST[$option->name];
							// strip slashes from new value?
							if ( is_scalar( $new_value ) ) {
								$new_value = stripslashes( $_POST[$option->name] );
							}
							// update it
							$option->update( $new_value );
						}
					} else {
						// not in POST, delete it
						$option->delete();
					}
					// increment the count
					$save_count++;
				}
			}

			// hard refresh all scheme exports
			ICE_Scheme::instance()->exports_refresh( true );
			
			// done saving
			return $save_count;

		} else {
			throw new Exception( 'No manifest was rendered' );
		}
	}

	/**
	 * Process the form and generate an AJAX response
	 *
	 * @see process_form
	 */
	public function process_form_ajax()
	{
		// process the form
		$save_count = $this->process_form();

		// any options saved successfuly?
		if ( $save_count == 1 ) {
			ICE_Ajax::responseStd( true, sprintf( __('%d option successfully updated.', infinity_text_domain), $save_count ) );
		} elseif ( $save_count > 1 ) {
			ICE_Ajax::responseStd( true, sprintf( __('%d options successfully updated.', infinity_text_domain), $save_count ) );
		} else {
			ICE_Ajax::responseStd( false, __('An error has occurred. No options were updated.', infinity_text_domain) );
		}
	}
}

?>
