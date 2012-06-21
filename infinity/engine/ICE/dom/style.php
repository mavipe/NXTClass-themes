<?php
/**
 * ICE API: base style class file
 *
 * @author Marshall Sorenson <marshall@presscrew.com>
 * @link http://infinity.presscrew.com/
 * @copyright Copyright (C) 2010-2011 Marshall Sorenson
 * @license http://www.gnu.org/licenses/gpl.html GPLv2 or later
 * @package ICE
 * @subpackage dom
 * @since 1.0
 */

ICE_Loader::load( 'dom/asset' );

/**
 * Make styles for components easy
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style extends ICE_Asset
{
	/**
	 * The rules
	 *
	 * @var ICE_Map
	 */
	private $rules;

	/**
	 * Parent dir of last filename being fixed for css url() values
	 *
	 * @var string
	 */
	private $last_dirname;

	/**
	 * Constructor
	 */
	public function __construct( ICE_Component $component = null )
	{
		parent::__construct( $component );

		// init rules map
		$this->rules = new ICE_Map();
	}

	/**
	 */
	final public function enqueue( $handle )
	{
		nxt_enqueue_style( $handle );
	}

	/**
	 * Add/get a rule for a selector
	 * 
	 * @param string $selector CSS selector to affect
	 * @return ICE_Style_Rule
	 */
	public function rule( $selector )
	{
		// hash the selector to make a key
		$key = md5( trim( $selector ) );

		// get or create a rule
		if ( $this->rules->contains( $key ) ) {
			$rule = $this->rules->item_at( $key );
		} else {
			// new rule object
			$rule = new ICE_Style_Rule( $selector );
			// add it to rule map
			$this->rules->add( $key, $rule );
		}

		// return it for editing
		return $rule;
	}

	/**
	 * Generate CSS markup for this style's dynamic rules
	 *
	 * @return string
	 */
	public function export()
	{
		// the markup that will be returned
		$markup = parent::export();

		// render rules
		if ( $this->rules->count() ) {
			// get markup for each rule
			foreach ( $this->rules->to_array() as $rule ) {
				// append output of rule export
				$markup .= '/*+++ generating style ***/' . PHP_EOL;
				$markup .= $rule->export();
				$markup .= '/*--- style generation complete! */' . PHP_EOL . PHP_EOL;
			}
		}

		// all done
		return $markup;
	}

	/**
	 */
	protected function get_file_contents( $filename )
	{
		// run parent to get content
		$content = parent::get_file_contents( $filename );

		// get content?
		if ( $content ) {

			// new file info instance
			$fi = new ICE_File( $filename );

			// save last filename
			$this->last_dirname = $fi->getPath();

			// handle any pre-processing
			switch ( $fi->getExtension() ) {
				// its a LESS CSS file
				case 'less':
					// load less parser
					ICE_Loader::load( 'parsers/less' );
					// parse it
					$content = ICE_Less::parse( $content, $fi->getPath() );
					// done with less
					break;
			}
			
			// replace all CSS url() values
			return preg_replace_callback( '/url\s*\([\'\"\s]*([^\'\"\s]*)[\'\"\s]*\)/', array($this, 'fix_url_path'), $content );
		}
	}

	/**
	 * @internal
	 * @param array $matches
	 * @return string
	 */
	protected function fix_url_path( $matches )
	{
		// path is index 2
		$path = $matches[1];

		// fix if applicable
		switch ( true ) {
			// absolute path to doc root, leave alone
			case ( $path{0} == '/' ):
				break;
			// absolute URL, leave alone
			case ( preg_match( '/^https?:\/\//', $path ) ):
				break;
			// anything else needs to be resolved
			default:
				$path = ICE_Files::path_resolve( $this->last_dirname, $path );
				$path = ICE_Files::file_to_uri_path( $path );
		}

		// return fixed url value
		return sprintf( "url('%s')", $path );
	}
}

/**
 * Make rules for styles easy
 *
 * @package ICE
 * @subpackage dom
 * @property string $selector CSS selector to which apply declarations
 */
class ICE_Style_Rule extends ICE_Base
{
	/**
	 * The selector
	 *
	 * @var string
	 */
	private $selector;

	/**
	 * The declarations
	 *
	 * @var ICE_Map
	 */
	private $declarations;

	/**
	 * Constructor
	 *
	 * @param string $selector CSS selector expression
	 */
	public function __construct( $selector )
	{
		// set selector
		$this->selector = $selector;

		// init declarations
		$this->declarations = new ICE_Map();
	}

	/**
	 */
	public function __get( $name )
	{
		switch ( $name ) {
			case 'selector':
				return $this->selector;
			default:
				return parent::__get( $name );
		}
	}

	/**
	 */
	public function __isset( $name )
	{
		switch ( $name ) {
			case 'selector':
				return isset( $this->selector );
			default:
				return parent::__isset( $name );
		}
	}

	/**
	 * Add a declaration
	 *
	 * @param string $property
	 * @param mixed $value
	 */
	public function add_declaration( $property, $value )
	{
		$this->declarations->add( $property, $value );
	}

	/**
	 * Add a declaration (shorthand)
	 *
	 * @param string $property
	 * @param mixed $value
	 */
	public function ad( $property, $value )
	{
		return $this->add_declaration( $property, $value );
	}

	/**
	 * Generate CSS markup for this rule
	 *
	 * @param array $declarations
	 * @return string
	 */
	public function export( $declarations = null )
	{
		// the markup that will be returned
		$markup = null;

		// declarations passed in?
		if ( is_array( $declarations ) ) {
			// merge over existing decs?
			if ( is_array( $this->declarations ) ) {
				$declarations = array_merge( $this->declarations->to_array(), $declarations );
			}
		} else {
			$declarations = $this->declarations->to_array();
		}

		// open declarations with the selector
		$markup = $this->selector . " {" . PHP_EOL;

		// add each dec
		foreach ( $declarations as $property => $value ) {
			$markup .= sprintf( "\t%s: %s;", $property, $value ) . PHP_EOL;
		}

		// close
		$markup .= '}' . PHP_EOL;

		// all done
		return $markup;
	}
}

/**
 * Any style value which has a unit should implement this interface
 *
 * @package ICE
 * @subpackage dom
 */
interface ICE_Style_Unitable
{
	/**
	 * Return the style unit object assigned to this object
	 *
	 * @return ICE_Style_Unit
	 */
	public function unit();
}

/**
 * A unit of measure for a style value
 *
 * @package ICE
 * @subpackage dom
 */
abstract class ICE_Style_Unit extends ICE_Base
{
	/**
	 * The current unit
	 *
	 * @var string
	 */
	private $value;

	/**
	 */
	public function __get( $name )
	{
		switch( $name ) {
			case 'unit':
			case 'value':
				return (string) $this->value;
			case 'units':
			case 'values':
				return $this->units();
			default:
				return parent::__get( $name );
		}
	}

	/**
	 */
	public function __set( $name, $value )
	{
		switch( $name ) {
			case 'unit':
			case 'value':
				return $this->set( $value );
			default:
				return parent::__set( $name );
		}
	}

	/**
	 */
	public function __isset( $name )
	{
		switch( $name ) {
			case 'unit':
			case 'value':
				return !is_null( $this->value );
			default:
				return parent::__isset( $name );
		}
	}

	/**
	 */
	public function __unset( $name )
	{
		switch( $name ) {
			case 'unit':
			case 'value':
				return $this->value = null;
			default:
				return parent::__unset( $name );
		}
	}

	/**
	 * Validate the given unit against the list of allowed units
	 *
	 * @param string $unit The unit to validate
	 * @return boolean
	 */
	protected function validate( $unit )
	{
		return in_array( $unit, $this->units(), true );
	}

	/**
	 * Set the unit
	 *
	 * @param string $unit The unit to set
	 * @return boolean
	 */
	public function set( $unit )
	{
		$unit = (string) $unit;
		
		if ( $this->validate( $unit ) ) {
			$this->value = $unit;
			return true;
		}

		return false;
	}

	/**
	 * Return an array of allowed units
	 *
	 * @return array
	 */
	abstract public function units();
}

/**
 * A style unit representing "no unit"
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Unit_None extends ICE_Style_Unit
{
	/**
	 * Allowed units
	 *
	 * @var array
	 */
	private $units = array( null, '' );

	/**
	 */
	public function units()
	{
		return $this->units;
	}
}

/**
 * A style value length unit of measure
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Unit_Length extends ICE_Style_Unit
{
	/**
	 * Allowed units
	 *
	 * @var array
	 */
	private $units =
		array(
			'em', 'ex', 'in', 'cm', 'mm', 'pt', 'pc', 'px'
		);

	/**
	 */
	public function units()
	{
		return $this->units;
	}
}

/**
 * A style value percentage unit of measure
 * 
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Unit_Percentage extends ICE_Style_Unit
{
	/**
	 * Allowed units
	 *
	 * @var array
	 */
	private $units = array( '%' );

	/**
	 */
	public function units()
	{
		return $this->units;
	}
}

/**
 * A style value container and formatter
 *
 * @package ICE
 * @subpackage dom
 */
abstract class ICE_Style_Value
	extends ICE_Base implements ICE_Style_Unitable
{
	/**
	 * The current value
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * The unit object for this value
	 *
	 * @var ICE_Style_Unit
	 */
	private $unit;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->unit = $this->get_unit();
	}

	/**
	 */
	public function __get( $name )
	{
		switch( $name ) {
			case 'value':
				return $this->value;
			default:
				return parent::__get( $name );
		}
	}

	/**
	 */
	public function __set( $name, $value )
	{
		switch( $name ) {
			case 'value':
				return $this->set( $value );
			default:
				return parent::__set( $name );
		}
	}

	/**
	 */
	public function __isset( $name )
	{
		switch( $name ) {
			case 'value':
				return !is_null( $this->value );
			default:
				return parent::__isset( $name );
		}
	}

	/**
	 */
	public function __unset( $name )
	{
		switch( $name ) {
			case 'value':
				return $this->value = null;
			default:
				return parent::__unset( $name );
		}
	}

	/**
	 */
	public function __toString()
	{
		return $this->format();
	}

	/**
	 * Set the value and unit for this value container
	 *
	 * @param mixed $value The value to set
	 * @param string $unit The unit to set (optional)
	 * @return type
	 */
	public function set( $value, $unit = null )
	{
		// is value null?
		if ( is_null( $value ) ) {

			// yep, treat like an unset
			$this->value = null;
			// successful
			return true;

		} elseif ( $this->unit()->set( $unit ) && $this->validate( $value ) ) {
			// value and unit are valid, assign value
			$this->value = $value;
			return true;
		}

		// set value failed
		return false;
	}

	/**
	 */
	public function unit()
	{
		return $this->unit;
	}

	/**
	 * Return the correct unit type for this value
	 *
	 * @return ICE_Style_Unit_None
	 */
	protected function get_unit()
	{
		return new ICE_Style_Unit_None();
	}

	/**
	 * Format the value as a string
	 *
	 * @return string
	 */
	public function format()
	{
		return $this->value . $this->unit()->value;
	}

	/**
	 * Validate the value
	 *
	 * @param mixed $value
	 */
	abstract protected function validate( $value );
}

/**
 * A style integer value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Integer extends ICE_Style_Value
{
	/**
	 */
	public function validate( $value )
	{
		throw new Exception( 'I need some work' );
	}
}

/**
 * A style number value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Number extends ICE_Style_Value
{
	/**
	 */
	public function validate( $value )
	{
		return ( is_numeric( $value ) );
	}
}

/**
 * A style string value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_String extends ICE_Style_Value
{
	/**
	 */
	public function validate( $value )
	{
		// pretty much have to allow anything here
		return true;
	}

	/**
	 */
	public function format()
	{
		return sprintf( '"%s"', esc_attr( $this->value ) );
	}
}

/**
 * A style color value
 * 
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Color extends ICE_Style_Value
{
	/**
	 */
	public function validate( $value )
	{
		// color must be a hex or alpha string
		return ( preg_match( '/^(#[0-9a-f]{3,6})|([a-z-]+)$/', $value ) );
	}
}

/**
 * A style URI value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Uri extends ICE_Style_Value
{
	/**
	 */
	public function validate( $value )
	{
		return true;
	}

	/**
	 * @return type
	 */
	public function format()
	{
		// simply wrap it with a URL call
		return sprintf( "url('%s')", $this->value );
	}
}

/**
 * A style counter value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Counter extends ICE_Style_Value
{
	/**
	 */
	public function validate( $value )
	{
		throw new Exception( 'I need some work' );
	}
}

/**
 * A style identifier value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Identifier extends ICE_Style_Value
{
	/**
	 */
	public function validate( $value )
	{
		throw new Exception( 'I need some work' );
	}
}

/**
 * A style enumeration value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Enum extends ICE_Style_Value
{
	/**
	 * @var ICE_Map
	 */
	private $values;

	/**
	 * Constructor
	 *
	 * @param ICE_Map $values A map of possible values
	 */
	public function __construct( ICE_Map $values = null )
	{
		// run parent contructor
		parent::__construct();

		// were values passed?
		if ( $values ) {
			// yes, use them
			$this->values = $values;
		} else {
			// no, use new empty map
			$this->values = new ICE_Map();
		}

		// every property allows inherit
		$this->values->add( 'inherit', __( 'Inherit', infinity_text_domain ) );
	}

	/**
	 */
	public function __get( $name )
	{
		switch( $name ) {
			case 'values':
				return $this->values;
			default:
				return parent::__get( $name );
		}
	}

	/**
	 * Add an allowed value to this enumeration
	 *
	 * @param string $string A valid property value
	 * @param string $desc Short description of the value
	 * @return ICE_Map
	 */
	public function add( $string, $desc )
	{
		return $this->values->add( $string, $desc );
	}

	/**
	 * Check that given value is an allowed value
	 *
	 * @param string $value
	 * @return boolean
	 */
	protected function validate( $value )
	{
		return $this->values->contains( $value );
	}
}

/**
 * A style length value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Length extends ICE_Style_Value
{
	/**
	 * @return ICE_Style_Unit_Length
	 */
	protected function get_unit()
	{
		return new ICE_Style_Unit_Length();
	}

	/**
	 */
	public function validate( $value )
	{
		return is_numeric( $value );
	}
}

/**
 * A style percentage value
 *
 * @package ICE
 * @subpackage dom
 */
class ICE_Style_Value_Percentage extends ICE_Style_Value
{
	/**
	 * @return ICE_Style_Unit_Percentage
	 */
	protected function get_unit()
	{
		return new ICE_Style_Unit_Percentage();
	}

	/**
	 */
	public function validate( $value )
	{
		return is_numeric( $value );
	}
}

/**
 * Make style properties easy
 *
 * @package ICE
 * @subpackage dom
 */
abstract class ICE_Style_Property extends ICE_Base
{
	/**
	 * The property name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Constructor
	 *
	 * @param string $name The name of the property
	 */
	public function __construct( $name )
	{
		$this->name = $name;
	}

	/**
	 */
	public function __get( $name )
	{
		switch( $name ) {
			case 'name':
				return $this->name;
			default:
				return parent::__get( $name );
		}
	}

	/**
	 * Static factory method
	 *
	 * @param string $name Name of the property to create
	 * @return ICE_Style_Property
	 */
	abstract static public function create( $name );

	/**
	 * Get the current style value object for this property
	 *
	 * @return ICE_Style_Value
	 */
	abstract public function get_value();

	/**
	 * Set the value for this property
	 * 
	 * @param mixed $value The value to set
	 * @param string $unit The unit for the value (not supported by every property)
	 * @return boolean
	 */
	abstract public function set_value( $value, $unit = null );
	
	/**
	 * Get the style unit object which is set for the current style value object
	 * 
	 * @return string
	 */
	abstract public function get_unit();
	
	/**
	 * Set the unit for the style unit object which is set for the current style value object
	 *
	 * @param string $value The unit to set
	 * @return boolean
	 */
	abstract public function set_unit( $value );

	/**
	 * Return the formatted property
	 *
	 * @param string $format An alternate sprintf() format to use instead of the default
	 * @return string
	 */
	abstract public function format( $format = null );
}

/**
 * A simple style property which has a single value
 * 
 * @package ICE
 * @subpackage dom
 */
final class ICE_Style_Property_Primitive extends ICE_Style_Property
{
	/**
	 * Integer values map key
	 */
	const KEY_INTEGER = 1;

	/**
	 * Number values map key
	 */
	const KEY_NUMBER = 2;

	/**
	 * String values map key
	 */
	const KEY_STRING = 4;

	/**
	 * Color values map key
	 */
	const KEY_COLOR = 8;

	/**
	 * Length values map key
	 */
	const KEY_LENGTH = 16;

	/**
	 * Percentage values map key
	 */
	const KEY_PERCENTAGE = 32;

	/**
	 * URI values map key
	 */
	const KEY_URI = 64;

	/**
	 * Counter values map key
	 */
	const KEY_COUNTER = 128;

	/**
	 * Identifier values map key
	 */
	const KEY_IDENTIFIER = 256;

	/**
	 * Enumeration values map key
	 */
	const KEY_ENUM = 512;

	/**
	 * A map of possible style value objects for this property
	 *
	 * @var ICE_Map
	 */
	private $valmap;

	/**
	 */
	public function __construct( $name )
	{
		parent::__construct( $name );

		// set possible value types
		$this->valmap = new ICE_Map();
	}

	/**
	 */
	public function __get( $name )
	{
		switch( $name ) {
			case 'values':
				return $this->valmap;
			default:
				return parent::__get( $name );
		}
	}

	/**
	 */
	static public function create( $name )
	{
		return new self( $name );
	}

	/**
	 * Return an array of values which are usable as a list
	 *
	 * Basically just test if one of the possible values is an enumeration and
	 * return all of the enumerations as an array.
	 *
	 * @return array
	 */
	public function get_list_values()
	{
		foreach ( $this->valmap as $style_value ) {
			if ( $style_value instanceof ICE_Style_Value_Enum ) {
				return $style_value->values->to_array();
			}
		}

		throw new Exception( sprintf(
			'The "%s" property has no value type compatible with a list', $this->name ) );
	}

	/**
	 */
	public function get_value()
	{
		foreach ( $this->valmap as $style_value ) {
			if ( isset( $style_value->value ) ) {
				return $style_value;
			}
		}

		return null;
	}

	/**
	 */
	public function set_value( $value, $unit = null )
	{
		foreach ( $this->valmap as $style_value ) {
			if ( $style_value->set( $value, $unit ) === true ) {
				return true;
			}
		}

		return false;
	}

	/**
	 */
	public function get_unit()
	{
		return $this->get_value()->unit();
	}

	/**
	 */
	public function set_unit( $unit )
	{
		return $this->get_value()-unit()->set( $unit );
	}

	/**
	 */
	public function format( $format = null )
	{
		if ( is_null( $format ) ) {
			$format = '%s: %s';
		}

		return sprintf( $format, $this->name, $this->get_value()->format() );
	}

	/**
	 * Add color as a possible value type
	 */
	public function add_color()
	{
		if ( !$this->valmap->contains( self::KEY_COLOR ) ) {
			$this->valmap->add( self::KEY_COLOR, new ICE_Style_Value_Color() );
		}

		return $this;
	}

	/**
	 * Add enumeration as a possible value type
	 */
	public function add_enum( $string, $desc = null )
	{
		if ( !$this->valmap->contains( self::KEY_ENUM ) ) {
			$this->valmap->add( self::KEY_ENUM, new ICE_Style_Value_Enum() );
		}

		$this->valmap->item_at( self::KEY_ENUM )->add( $string, $desc );

		return $this;
	}

	/**
	 * Add length as a possible value type
	 */
	public function add_length()
	{
		if ( !$this->valmap->contains( self::KEY_LENGTH ) ) {
			$this->valmap->add( self::KEY_LENGTH, new ICE_Style_Value_Length() );
		}

		return $this;
	}

	/**
	 * Add number as a possible value type
	 */
	public function add_number()
	{
		if ( !$this->valmap->contains( self::KEY_NUMBER ) ) {
			$this->valmap->add( self::KEY_NUMBER, new ICE_Style_Value_Number() );
		}

		return $this;
	}

	/**
	 * Add percentage as a possible value type
	 */
	public function add_percentage()
	{
		if ( !$this->valmap->contains( self::KEY_PERCENTAGE ) ) {
			$this->valmap->add( self::KEY_PERCENTAGE, new ICE_Style_Value_Percentage() );
		}

		return $this;
	}

	/**
	 * Add string as a possible value type
	 */
	public function add_string()
	{
		if ( !$this->valmap->contains( self::KEY_STRING ) ) {
			$this->valmap->add( self::KEY_STRING, new ICE_Style_Value_String() );
		}

		return $this;
	}

	/**
	 * Add URI as a possible value type
	 */
	public function add_uri()
	{
		if ( !$this->valmap->contains( self::KEY_URI ) ) {
			$this->valmap->add( self::KEY_URI, new ICE_Style_Value_Uri() );
		}

		return $this;
	}
}

/**
 * A complex style property which has a two or more values
 *
 * @package ICE
 * @subpackage dom
 */
final class ICE_Style_Property_Composite extends ICE_Style_Property
{
	/**
	 * List of primitive properties which compose this composite's value
	 *
	 * @var ICE_Map
	 */
	private $properties;

	/**
	 * Constructor
	 *
	 * @param string $name Name of the property to create
	 * @param ICE_Stack $primitives Stack of primitive properties which make up this composite
	 */
	public function __construct( $name, ICE_Stack $primitives )
	{
		throw new Exception( 'Composite style properties are not yet fully supported!' );

		parent::__construct( $name );

		$this->properties = new ICE_Map();

		foreach ( $primitives as $primitive ) {
			if ( $primitive instanceof ICE_Style_Property_Primitive ) {
				$this->properties->add( $primitive->name, $primitive );
			} else {
				throw new Exception( 'Only primitive properties can be added to a composite property' );
			}
		}
	}

	/**
	 * @return ICE_Style_Property_Composite
	 */
	static public function create( $property )
	{
		return new self( $property );
	}

	/**
	 */
	public function get_value()
	{
		return $this->properties;
	}

	/**
	 */
	public function set_value( $value, $unit = null )
	{
		if ( !is_array( $value ) || !$value instanceof ArrayAccess ) {
			throw new Exception( 'Value must be an array or accessible as an array' );
		}

		if ( !is_null( $unit ) && ( !is_array( $unit ) || !$unit instanceof ArrayAccess ) ) {
			throw new Exception( 'Unit must be an array or accessible as an array' );
		}

		foreach ( $this->properties as $property ) {
			if ( isset( $value[$property->name] ) ) {
				$property->set_value(
					$value[$property->name],
					isset( $unit[$property->name] ) ? $unit[$property->name] : null
				);
			}
		}
		
		return true;
	}

	/**
	 */
	public function get_unit()
	{
		$map = new ICE_Map();

		foreach ( $this->properties as $property ) {
			$style_value = $property->get_value();
			if ( $style_value instanceof ICE_Style_Unitable ) {
				$map->add( $property->name, $style_value->unit() );
			}
		}

		return $map;
	}

	/**
	 */
	public function set_unit( $unit )
	{
		if ( $unit instanceof ICE_Map ) {
			foreach ( $this->properties as $property ) {
				if ( $unit->contains( $property->name ) ) {
					$property->set_unit( $unit->item_at( $property->name ) );
				}
			}
		} else {
			foreach ( $this->properties as $property ) {
				$property->set_unit( $unit );
			}
		}

		return true;
	}

	/**
	 */
	public function format( $format = null )
	{
		// array of values to format
		$values = array();

		// loop all properties
		foreach( $this->properties as $primitive ) {
			// is value set on this property?
			if ( isset( $primitive->value ) ) {
				// yes, push it onto the values array
				array_push( $values, $primitive->get_value()->format() );
			}
		}

		// implode all values with a space delimeter
		return sprintf( '%s: %s', implode( ' ', $values ) );
	}
}

/**
 * A style property factory
 *
 * @package ICE
 * @subpackage dom
 */
final class ICE_Style_Property_Factory extends ICE_Base
{
	/**
	 * Singleton instance
	 *
	 * @var ICE_Style_Property
	 */
	static private $instance;

	/**
	 * Singleton constructor
	 */
	private function __construct()
	{
		// this is a singleton
	}

	/**
	 * Return the singleton instance
	 *
	 * @return ICE_Style_Property_Factory
	 */
	final static public function instance()
	{
		if ( !self::$instance instanceof self ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}

	/**
	 * @return ICE_Style_Property_Primitive
	 */
	public function create( $property )
	{
		// format the method name
		$method_name = 'prop_' . str_replace( '-', '_', $property );

		// does method exist?
		if ( method_exists( $this, $method_name ) ) {
			// yes, call it to return property object
			return call_user_func( array( $this, $method_name ) );
		} else {
			// no, not good
			throw new Exception( sprintf( 'The method "%s" has not been implemented yet', $method_name ) );
		}
	}

	/**
	 * Return a new background-color property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_background_color()
	{
		return $this->prop_color( 'background-color' );
	}

	/**
	 * Return a new background-image property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_background_image()
	{
		return
			ICE_Style_Property_Primitive::create( 'background-image' )
				->add_uri()
				->add_enum( 'none' );
	}

	/**
	 * Return a new background-repeat property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_background_repeat()
	{
		return
			ICE_Style_Property_Primitive::create( 'background-repeat' )
				->add_enum( 'repeat', __( 'Full Tiling', infinity_text_domain ) )
				->add_enum( 'repeat-x', __( 'Tile Horizontally Only', infinity_text_domain ) )
				->add_enum( 'repeat-y', __( 'Tile Vertically Only', infinity_text_domain ) )
				->add_enum( 'no-repeat', __( 'Disable Tiling', infinity_text_domain ) );
	}

	/**
	 * Return a new border-color property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_border_color()
	{
		return $this->prop_color( 'border-color' );
	}

	/**
	 * Return a new border-width property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_border_width()
	{
		return
			ICE_Style_Property_Primitive::create( 'border-width' )
				->add_length()
				->add_percentage()
				->add_enum( 'thin', __( 'Thin', infinity_text_domain ) )
				->add_enum( 'medium', __( 'Medium', infinity_text_domain ) )
				->add_enum( 'thick', __( 'Thick', infinity_text_domain ) );
	}

	/**
	 * Return a new color property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_color( $property = 'color' )
	{
		return
			ICE_Style_Property_Primitive::create( $property )
				->add_color()
				->add_enum( 'black', __( 'Black', infinity_text_domain ) )
				->add_enum( 'silver', __( 'Silver', infinity_text_domain ) )
				->add_enum( 'gray', __( 'Gray', infinity_text_domain ) )
				->add_enum( 'white', __( 'White', infinity_text_domain ) )
				->add_enum( 'maroon', __( 'Maroon', infinity_text_domain ) )
				->add_enum( 'red', __( 'Red', infinity_text_domain ) )
				->add_enum( 'purple', __( 'Purple', infinity_text_domain ) )
				->add_enum( 'fuchsia', __( 'Fuchsia', infinity_text_domain ) )
				->add_enum( 'green', __( 'Green', infinity_text_domain ) )
				->add_enum( 'lime', __( 'Lime', infinity_text_domain ) )
				->add_enum( 'olive', __( 'Olive', infinity_text_domain ) )
				->add_enum( 'yellow', __( 'Yellow', infinity_text_domain ) )
				->add_enum( 'navy', __( 'Navy', infinity_text_domain ) )
				->add_enum( 'blue', __( 'Blue', infinity_text_domain ) )
				->add_enum( 'teal', __( 'Teal', infinity_text_domain ) )
				->add_enum( 'aqua', __( 'Aqua', infinity_text_domain ) );
	}

	/**
	 * Return a new content property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_content()
	{
		return
			ICE_Style_Property_Primitive::create( 'content' )
				->add_string();
	}

	/**
	 * Return a new font-family property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_font_family()
	{
		return
			ICE_Style_Property_Primitive::create( 'font-family' )
				->add_enum( 'serif', __( 'Times (serif)', infinity_text_domain ) )
				->add_enum( 'sans-serif', __( 'Helvetica (sans-serif)', infinity_text_domain ) )
				->add_enum( 'monospace', __( 'Courier (monospace)', infinity_text_domain ) )
				->add_enum( 'cursive', __( 'Zapf-Chancery (cursive)', infinity_text_domain ) )
				->add_enum( 'fantasy', __( 'Western (fantasy)', infinity_text_domain ) );
	}

	/**
	 * Return a new font-weight property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_font_weight()
	{
		return
			ICE_Style_Property_Primitive::create( 'font-weight' )
				->add_enum( 'lighter', __( 'Lighter', infinity_text_domain ) )
				->add_enum( 'normal', __( 'Normal', infinity_text_domain ) )
				->add_enum( 'bold', __( 'Bold', infinity_text_domain ) )
				->add_enum( 'bolder', __( 'Bolder', infinity_text_domain ) )
				->add_enum( '100', __( 'One Hundred', infinity_text_domain ) )
				->add_enum( '200', __( 'Two Hundred', infinity_text_domain ) )
				->add_enum( '300', __( 'Three Hundred', infinity_text_domain ) )
				->add_enum( '400', __( 'Four Hundred', infinity_text_domain ) )
				->add_enum( '500', __( 'Five Hundred', infinity_text_domain ) )
				->add_enum( '600', __( 'Six Hundred', infinity_text_domain ) )
				->add_enum( '700', __( 'Seven Hundred', infinity_text_domain ) )
				->add_enum( '800', __( 'Eight Hundred', infinity_text_domain ) )
				->add_enum( '900', __( 'Nine Hundred', infinity_text_domain ) );
	}

	/**
	 * Return a new height property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_height()
	{
		return
			ICE_Style_Property_Primitive::create( 'height' )
				->add_length()
				->add_percentage()
				->add_enum( 'auto' );
	}

	/**
	 * Return a new margin property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_margin( $property = 'margin' )
	{
		return
			ICE_Style_Property_Primitive::create( $property )
				->add_length()
				->add_percentage();
	}

	/**
	 * Return a new margin-top property object
	 * 
	 * @return ICE_Style_Property
	 */
	protected function prop_margin_top()
	{
		return $this->prop_margin( 'margin-top' );
	}

	/**
	 * Return a new margin-right property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_margin_right()
	{
		return $this->prop_margin( 'margin-right' );
	}

	/**
	 * Return a new margin-bottom property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_margin_bottom()
	{
		return $this->prop_margin( 'margin-bottom' );
	}

	/**
	 * Return a new margin-left property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_margin_left()
	{
		return $this->prop_margin( 'margin-left' );
	}
	
	/**
	 * Return a new max length property object
	 *
	 * @return ICE_Style_Property
	 */
	private function prop_max_length( $property )
	{
		return
			ICE_Style_Property_Primitive::create( $property )
				->add_enum( 'none' )
				->add_length()
				->add_percentage();
	}

	/**
	 * Return a new max-height property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_max_height()
	{
		return $this->prop_max_length( 'max-height' );
	}

	/**
	 * Return a new max-width property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_max_width()
	{
		return $this->prop_max_length( 'max-width' );
	}

	/**
	 * Return a new min length property object
	 *
	 * @return ICE_Style_Property
	 */
	private function prop_min_length( $property )
	{
		return
			ICE_Style_Property_Primitive::create( $property )
				->add_length()
				->add_percentage();
	}

	/**
	 * Return a new min-height property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_min_height()
	{
		return $this->prop_min_length( 'min-height' );
	}

	/**
	 * Return a new min-width property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_min_width()
	{
		return $this->prop_min_length( 'min-width' );
	}

	/**
	 * Return a new opacity property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_opacity()
	{
		return
			ICE_Style_Property_Primitive::create( 'opacity' )
				->add_number();
	}

	/**
	 * Return a new padding property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_padding( $property = 'padding' )
	{
		return
			ICE_Style_Property_Primitive::create( $property )
				->add_length()
				->add_percentage();
	}

	/**
	 * Return a new padding-top property object
	 * @return ICE_Style_Property
	 */
	protected function prop_padding_top()
	{
		return $this->prop_padding( 'padding-top' );
	}

	/**
	 * Return a new padding-right property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_padding_right()
	{
		return $this->prop_padding( 'padding-right' );
	}

	/**
	 * Return a new padding-bottom property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_padding_bottom()
	{
		return $this->prop_padding( 'padding-bottom' );
	}

	/**
	 * Return a new padding-left property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_padding_left()
	{
		return $this->prop_padding( 'padding-left' );
	}

	/**
	 * Return a new width property object
	 *
	 * @return ICE_Style_Property
	 */
	protected function prop_width( $property = 'width' )
	{
		return
			ICE_Style_Property_Primitive::create( $property )
				->add_length()
				->add_percentage()
				->add_enum( 'auto' );
	}
}

?>
