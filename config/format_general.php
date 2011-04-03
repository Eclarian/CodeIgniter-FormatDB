<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*
|--------------------------------------------------------------------------
| Format Database
|--------------------------------------------------------------------------
|
| This Configuration file defines the formatting of all the columns in the database
| Database Column Field name should be the $config array key
| Value should be function reference separated by | for multiple functions
*/

// Multiple Function Reference
$config['field_name']		= 'strtolower|mailto';

// Multiple Parameters
$config['field_name']		= 'wordwrap.100'; // RETURNS wordwrap($field_name, 100);

// Multiple Functions and Multiple Parameters
$config['field_name']		= 'wordwrap.100|trim|ucwords';

// Closure Example
$config['field_name']		= function($string) {								
								return "PHP 5.3+ Installed: " . $string;
							};
	
