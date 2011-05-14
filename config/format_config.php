<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Default Format Config
|--------------------------------------------------------------------------
|
| The name of your default configuration file
|
*/

$config['default_format_config'] = 'format_general';


/*
|--------------------------------------------------------------------------
| Enable Psuedo Bool Conversion
|--------------------------------------------------------------------------
|
| This allows you to pass true, false, null as strings in your configuration
| which will be parsed and interpreted as booleans to the functions they
| are passed to
|
| @since 1.0.2
*/

$config['enable_psuedo_bool_conversion'] = TRUE;


/*
|--------------------------------------------------------------------------
| Parse Types Supported
|--------------------------------------------------------------------------
|
| The Parse Types Supported by Default
|
| If you add more parsable strings, such as "array()" to array(),
| you can add them below
|
| @since 1.0.2
*/

$config['parse_types_supported'] = array('true' => TRUE, 'false' => FALSE, 'null' => NULL);


/*
|--------------------------------------------------------------------------
| Method Map
|--------------------------------------------------------------------------
|
| Allows you to specify where specific methods are located.  Procedural functions (helpers)
| should NOT be included in the list below.  Only methods that occur within a class.
| This allows you to take full advantage of the CI singleton by accessing any publicly 
| accessible method from ANY class loaded at the time of this libraries method call.
|
| NOTE: The array format should be array('METHOD_NAME' => 'CLASS_NAME');
*/

$config['method_map'] = array(
	'clean_for_json' => 'utilities', // @example	Method 'clean_for_json' in Class 'utilities'
);
