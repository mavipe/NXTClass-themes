<?php
/**
 * ICE API: markdown class file
 *
 * @author Marshall Sorenson <marshall@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Marshall Sorenson
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package ICE
 * @subpackage parsers
 * @since 1.0
 */

/**
 * Disable automatic nxt post parsing
 * @internal
 */
@define( 'MARKDOWN_nxt_POSTS', false );

/**
 * Disable automatic nxt comment parsing
 * @internal
 */
@define( 'MARKDOWN_nxt_COMMENTS', false );

/**
 * Load the markdown lib
 */
require_once ICE_LIB_PATH . '/markdown/markdown.php';

/**
 * Make Markdown parsing easy
 *
 * @package ICE
 * @subpackage parsers
 */
final class ICE_Markdown extends ICE_Base
{
	/**
	 * Parse markdown markup and return HTML
	 *
	 * @param string $text
	 * @return string
	 */
	public static function parse( $text )
	{
		return Markdown( $text );
	}
}

?>
