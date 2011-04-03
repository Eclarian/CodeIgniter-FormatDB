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
| Method Map
|--------------------------------------------------------------------------
|
| Allows you to specify where specific methods are located.  Procedural functions (helpers)
| should NOT be included in the list below.  Only methods that occur within a class.
| This allows you to take full advantage of the CI singleton by accessing any publically  
| accessible method from ANY class loaded at the time of this libraries method call.
|
| NOTE: The array format should be array('METHOD_NAME' => 'CLASS_NAME');
*/

$config['method_map'] = array(
	'convert_sql_date' => 'utilities',
	// ADD YOURS HERE !! 
);