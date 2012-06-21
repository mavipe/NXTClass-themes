<?php
/**
 * ICE API: file system helper class file
 *
 * @author Marshall Sorenson <marshall@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Marshall Sorenson
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package ICE
 * @subpackage utils
 * @since 1.0
 */

ICE_Loader::load( 'utils/file_cache' );

/**
 * Make the File System Easy
 *
 * @package ICE
 * @subpackage utils
 * @uses ICE_Files_Exception
 */
final class ICE_Files extends ICE_Base
{
	/**
	 * File cache instance
	 * 
	 * @var ICE_File_Cache
	 */
	private static $fcache;

	/**
	 * Cached doc root
	 *
	 * @var string
	 */
	static private $document_root;

	/**
	 * Cached doc root length
	 *
	 * @var string
	 */
	static private $document_root_length;

	/**
	 * Set the doc root variables if not already set
	 *
	 * @return string
	 */
	public static function document_root()
	{
		if ( empty( self::$document_root ) ) {
			if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
				self::$document_root = realpath( $_SERVER['DOCUMENT_ROOT'] );
			} else {
				$theme_root = get_theme_root();
				$theme_root_uri = get_theme_root_uri();
				$uri_parts = parse_url( $theme_root_uri );
				$path_length = strlen( $uri_parts['path'] );
				$document_root = substr_replace( $theme_root, '', -$path_length );
				self::$document_root = realpath( $document_root );
			}
			self::$document_root_length = strlen( self::$document_root );
		}
		
		return self::$document_root;
	}

	/**
	 * Split a path at forward '/' OR backward '\' slashes
	 *
	 * @param string $path
	 * @return array
	 */
	public static function path_split ( $path )
	{
		// the path which will be clean
		$path_clean = $path;

		// windows install?
		if ( DIRECTORY_SEPARATOR == '\\' ) {
			// strip out leading drive letters (Windows)
			$path_clean = preg_replace( '/^[a-z]{1}:/i', '', $path );
			// replace double back slashes with single backslash
			$path_clean = str_replace( '\\', '/', $path_clean );
		}
			
		// split at forward and back slashes
		return preg_split( '/\/|\\\/', $path_clean, null, PREG_SPLIT_NO_EMPTY );
	}

	/**
	 * Resolve a file path like realpath() does, but without following symlinks,
	 * and without requiring that the file exists
	 *
	 * @param array|string $file_names,... One array or an unlimited number of file names
	 * @return string
	 */
	public static function path_resolve()
	{
		// get all args
		$args = func_get_args();

		// if two or more args, we got some file names
		if ( is_array($args[0]) ) {
			$file_names = $args[0];
		} else {
			$file_names = $args;
		}

		// maybe files array
		$file_names_maybe = array();

		// merge all paths
		foreach( $file_names as $file_name ) {
			// split and add to maybe list
			foreach( self::path_split( $file_name ) as $sub_path ) {
				$file_names_maybe[] = $sub_path;
			}
		}

		// final files array
		$file_names_clean = array();

		// resolve
		foreach( $file_names_maybe as $maybe_file ) {
			// handle relative traversal in absolute paths
			if ( $maybe_file == '' ) {
				// empty file, skip
				continue;
			} elseif ( $maybe_file == '.' ) {
				// single dot, skip
				continue;
			} elseif ( $maybe_file == '..' ) {
				// two dots, remove last dir "up"
				array_pop( $file_names_clean );
				continue;
			}
			// push file name onto final array
			$file_names_clean[] = $maybe_file;
		}

		// format the final path
		return '/' . implode( '/', $file_names_clean );
	}

	/**
	 * Copy a path from one location to another
	 *
	 * Warning: For security reasons, this method does NOT copy ANY kind of dot files
	 *
	 * @param string $src Source path
	 * @param string $dst Destination path
	 * @param boolean $recurse Recursively copy directories?
	 * @return boolean
	 */
	public function path_copy( $src, $dst, $recurse = true )
	{
		$dir = @opendir( $src );

		if ( !$dir ) {
			return false;
		}

		if ( is_dir( $dst ) ) {
			if ( !is_writable( $dst ) ) {
				return false;
			}
		} elseif ( !@mkdir( $dst ) ) {
			return false;
		}

		while ( false !== ( $file = @readdir( $dir ) ) ) {

			// skip dot files
			if ( $file{0} == '.' ) {
				continue;
			}

			// format paths
			$src_path = sprintf( '%s/%s', $src, $file );
			$dst_path = sprintf( '%s/%s', $dst, $file );

			// is src a dir?
			if ( is_dir( $src_path ) ) {
				// yep, recurse if applic
				if ( true === $recurse ) {
					self::path_copy( $src_path, $dst_path, true );
				}
			} elseif ( !@copy( $src_path, $dst_path ) ) {
				// copy failed
				return false;
			}
		}

		@closedir( $dir );

		return true;
	}
	
	/**
	 * Returns fstat instance for a file from the cache
	 *
	 * @param string $filename
	 * @return ICE_File
	 */
	public static function cache( $filename )
	{
		return self::file_cache()->get($filename);
	}
	
	/**
	 * Return file cache instance
	 *
	 * @return ICE_File_Cache
	 */
	private static function file_cache()
	{
		if ( !self::$fcache instanceof ICE_File_Cache ) {
			self::$fcache = new ICE_File_Cache();
		}
		
		return self::$fcache;
	}

	/**
	 * List all files in a directory filtered by a regular expression
	 *
	 * @param string $dir Absolute path to directory
	 * @param string $regex Valid PCRE expression
	 * @param boolean $absolute Set to true to return abolute path to file
	 * @return array
	 */
	public function list_filtered( $dir, $regex, $absolute = false )
	{
		// does the directory exist?
		if ( is_dir( $dir ) ) {
			// try to open the dir
			$dh = opendir( $dir );
			// check that handle is valid
			if ( $dh ) {
				// list of files to return
				$return_files = array();
				// loop through and add only files that match regex to list
				while (($file = readdir($dh)) !== false) {
					// check regex
					if ( preg_match($regex, $file) ) {
						// build file path
						$file_path = ( $absolute ) ? $dir . '/' . $file : $file;
						// push onto return array
						$return_files[$file] = $file_path;
					}
				}
				// destroy handle
				closedir($dh);
				// sort the files (by key)
				ksort( $return_files );
				// done
				return $return_files;
			} else {
				throw new ICE_Files_Exception( 'Unable to open the directory: ' . $dir );
			}
		} else {
			throw new ICE_Files_Exception( 'The directory does not exist: ' . $dir );
		}
	}

	/**
	 * Return path to a theme's root (parent directory)
	 *
	 * @param string $theme
	 * @return string
	 */
	static public function theme_root( $theme )
	{
		return realpath( get_theme_root( $theme ) );
	}

	/**
	 * Return URL to a theme directory's root (parent directory) URL
	 *
	 * @param string $theme
	 * @return string
	 */
	static public function theme_root_url( $theme )
	{
		$url = get_theme_root_uri( $theme );

		if ( is_ssl() ) {
			return preg_replace( '/http:\/\//', 'https://', $url, 1 );
		} else {
			return $url;
		}
	}
	
	/**
	 * Return path to a theme directory
	 *
	 * @param string $theme
	 * @return string
	 */
	static public function theme_dir( $theme )
	{
		return self::theme_root( $theme ) . '/' . $theme;
	}

	/**
	 * Return URL to a theme directory
	 *
	 * @param string $theme
	 * @return string
	 */
	static public function theme_dir_url( $theme )
	{
		return self::theme_root_url( $theme ) . '/' . $theme;
	}

	/**
	 * Return URL to a theme file
	 *
	 * @param string $theme
	 * @param string|array $file_names,... Zero or more file name parameters
	 */
	static public function theme_file_url( $theme )
	{
		// get all args except the first
		$file_names = func_get_args();
		array_shift($file_names);

		// were file names passed as an array?
		if ( is_array( current( $file_names ) ) ) {
			$file_names = current( $file_names );
		}

		return self::theme_dir_url( $theme ) . '/' . implode( '/', $file_names );
	}

	/**
	 * Return path to a theme file relative to theme root
	 *
	 * @param string $file_path
	 */
	static public function theme_file_to_rel( $file_path )
	{
		// convert path to be realtive to themes root
		return str_replace( realpath( get_theme_root() ) . '/', '', $file_path );
	}

	/**
	 * Return URL to a theme file given an absolute file system path
	 *
	 * @param string $file_path
	 */
	static public function theme_file_to_url( $file_path )
	{
		// convert path to be relative to themes root
		$relative_path = self::theme_file_to_rel( $file_path );
		// split it up
		$file_names = self::path_split( $relative_path );
		// theme is first arg, beautiful!
		$theme = array_shift( $file_names );
		// return as url
		return self::theme_file_url( $theme, $file_names );
	}

	/**
	 * Convert fully qualified file path to absolute uri path
	 *
	 * @param string $file_name File name
	 * @return string
	 */
	static public function file_to_uri_path( $file_name )
	{
		// make sure doc root is cached
		self::document_root();

		// return only uri path portion
		return substr( $file_name, self::$document_root_length );
	}

	/**
	 * Create a class name from a file name
	 *
	 * @param string $file_name File name
	 * @param string $prefix Optional class prefix
	 * @return string
	 */
	static public function file_to_class( $file_name, $prefix = null )
	{
		// the parts to merge
		$parts = array();

		// is file name already an array?
		if ( is_array( $file_name ) ) {
			// yep, clean it up
			foreach ( $file_name as $file_part ) {
				$parts[] = self::file_to_class( $file_part );
			}
		} else {
			// split at common delimeters
			$parts = preg_split( '/[_.\/\\-]/', $file_name );
		}

		// if last part is php, kill it
		if ( end( $parts ) == 'php' ) {
			array_pop( $parts );
		}

		// upper case the first char of every part
		foreach ( $parts as &$ext_part ) {
			$ext_part = ucfirst( $ext_part );
		}

		// add prefix if necessary
		if ( strlen( $prefix ) ) {
			array_unshift( $parts, $prefix );
		}

		// join them with underscores
		return implode( '_', $parts );
	}
}

/**
 * ICE File Exception
 *
 * @package ICE
 * @subpackage utils
 */
final class ICE_Files_Exception extends Exception {}

?>
