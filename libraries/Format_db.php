<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Format Database
 *
 * This library allows you to target format your entire database (or any array)
 * for the specific display scenario you find yourself in (e.g. table)
 *
 * USAGE EXAMPLES
 *   + BASIC USAGE
 * 	$query = $this->db->get();
 * 	$result = $query->result();
 * 	$this->format_db->run($result); // Query is now formatted!
 *
 *   + MULTIPLE FORMAT -> Allows you to have a default format and then supplement it.
 * 	$query = $this->db->get();
 * 	$result = $query->result();
 * 	$this->format_db->run($result);
 * 	$this->format_db->config('new_config', TRUE)->run($result); 
 * 	
 *
 * @author		Eclarian Dev Team (Joel Kallman & Joseph Moss)
 * @copyright	Copyright (c) 2011, Eclarian LLC
 * @license	MIT
 * @link		http://www.eclarian.com/codeigniter-format-database-library/
 * @version 	1.0.3
 */
class Format_db {

	/**
	 * CodeIgniter Object
	 */
	protected $ci;

	/**
	 * Format Configuration for Fields
	 */
	protected $config						= array();
	protected $old_config					= array(); // @since 1.0.3
	protected $config_set					= FALSE;
	protected $disallow_formatting			= FALSE;
	
	/**
	 * Format DB Configuration
	 */
	protected $default_format_config			= 'format_general';
	protected $enable_psuedo_bool_conversion	= TRUE;	// @since 1.0.2
	protected $method_map						= array();
	
	/**
	 * Allows for Proper Function Chaining
	 */
	protected $new_val						= '';
	
	/**
	 * Parse to Types Supported Conversions
	 */
	protected $parse_types_supported			= array('true' => TRUE, 'false' => FALSE, 'null' => NULL);
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 * Constructor
	 *
	 * Loads the configuration for the class
	 */
	public function __construct()
	{
		$this->ci = get_instance();
		$this->ci->config->load('format_config', TRUE);
		$config = $this->ci->config->item('format_config');
		
		foreach($config as $prop => $val)
		{
			$this->$prop = $val;
		}
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 * Reset Config
	 *
	 * Clears Configuration
	 * 
	 * @since	1.0.1
	 * @param	bool	$disallow_formatting	No Formatting Performed for Subsequent Run()
	 * @return	object
	 */
	public function reset_config($disallow_formatting = FALSE)
	{
		$this->disallow_formatting = $disallow_formatting;
		$this->old_config = $this->config; // Save Current Configuration to allow Re-enabling within the same loop.
		// This solves a potential bug in certain versions of PHP that don't reset the array with just a redeclaration.		
		unset($this->config);	
		$this->config = array();
		return $this;
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 * Enable Formatting
	 * 
	 * This allows you to re-enable formatting after it has 
	 * been disabled with a second configuration.
	 * 
	 * This can be useful if you are formatting your DB result multiple times
	 * and one of your formatting methods runs a DB query which when run with
	 * the current formatting causes an fatal error because of the infinite 
	 * recursion which results in the PHP memory limit being reached.  
	 * 
	 * Added $reuse_config in Version 1.0.3 to allow for formatting functions
	 * within the formatting loop to disable_formatting and enable_formatting
	 * for a specific query without removing the configuration for the rest of the loop.
	 * 
	 * @since	1.0.2
	 * @param	bool
	 * @return	object
	 */
	public function enable_formatting($reuse_config = TRUE)
	{
		// Reset to Previous Configuration
		if($reuse_config === TRUE && ! empty($this->old_config))
		{
			$this->config = $this->old_config;
		}

		$this->disallow_formatting = FALSE;
		return $this;
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 * Config
	 *
	 * Specifies the field|method array configuration
	 * To prevent multiple load calls, config_set is used.
	 *
	 * @since	1.0.0
	 * @param	string	Specify a Different Configuration
	 * @param	bool	Allows you to overwrite previous configuration
	 * @return	object
	 */
	public function config($config = '', $reset = FALSE)
	{
		if( ! $this->config_set OR $reset)
		{			
			// Load Specified Configuration for Formatting
			$config = ($config !== '') ? $config: $this->default_format_config;
			$this->ci->config->load($config, TRUE, TRUE); // Suppressing Errors if no File Included
			$this->config = $this->ci->config->item($config);
			$this->config_set = TRUE;
		}
		
		return $this;
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 * Run
	 *
	 * Formats the Results based on the configuration setup
	 *
	 * Accepts Arrays (e.g. row_array() and result_array()) 
	 * and Objects since 1.0.2 (e.g. row() and result())
	 *
	 * @since	1.0.0
	 * @param	mixed
	 * @return	object
	 */
	public function run(&$mixed)
	{
		if($this->disallow_formatting === TRUE) 
		{
			return $this->enable_formatting();
		}
		
		$this->config(); // Load Default Configuration if None Loaded Yet
			
		// Row or Result Array OR possible Result Object
		if(is_array($mixed))
		{
			foreach($mixed as $k => $row)
			{
				if(is_object($row))
				{
					$mixed[$k] = $this->_object_loop($row);
				}
				else
				{
					$mixed[$k] = $this->_array_loop($row, $k);
				}
			}			
		}
		// Row Object
		elseif(is_object($mixed))
		{
			$mixed = $this->_object_loop($mixed);
		}
			
		return $this;
	}
	
	// ----------------------------------------------------------------------------------------	
	
	/**
	 * Array Loop
	 *
	 * @since	1.0.2
	 * @param	mixed	Array or String
	 * @param	string
	 * @return	array
	 */
	protected function _array_loop($row, $k)
	{
		$array = array();		
		if(is_array($row))
		{
			foreach($row as $field => &$val)
			{
				$array[$field] = $this->filter($field, $val);
			}
		}
		else
		{
			$array = $this->filter($k, $row);
		}
		
		return $array;
	}
	
	// ----------------------------------------------------------------------------------------	
	
	/**
	 * Object Loop
	 *
	 * @since	1.0.2
	 * @param	object
	 * @return	object
	 */
	protected function _object_loop($row)
	{
		foreach(get_object_vars($row) as $field => $val)
		{
			$row->$field = $this->filter($field, $val);
		}
		
		return $row;
	}
	
	// ----------------------------------------------------------------------------------------	
		
	/**
	 * Filter
	 *
	 * Processes the Current Field|Value Combination
	 * based on the configuration settings
	 * 
	 * @since	1.0.0
	 * @param	string	
	 * @param	string	
	 * @return	string
	 */
	protected function filter($field, $value)
	{
		// No formatting set for this field
		if( ! isset($this->config[$field])) return $value;

		// Closure Support
		if(is_object($this->config[$field]) && is_callable($this->config[$field]))
		{
			return $this->config[$field]($value);
		}		
		
		// Fetch Functions to Apply to the Value
		$methods = explode('|', $this->config[$field]);
					
		if(empty($methods))
		{
			return $value; // No Methods Set For this Field
		}
		else
		{
			// Default Values
			$this->new_val = $value;
			$class = '';
			
			foreach($methods as $method)
			{
				// Allow for additional parameters separated by periods
				$params = explode('.', $method);
				$method = array_shift($params);
				
				// Parse the parameters to their corresponding types
				if($this->enable_psuedo_bool_conversion === TRUE)
				{
					$params = $this->parse_to_type($params);
				}					
				
				// Add the Value as the FIRST parameter of the function
				array_unshift($params, $this->new_val);
				
				// Check if Function is a Registered Class Method
				if(isset($this->method_map[$method]))
				{
					$class = $this->method_map[$method];
				}					
				
				// Run Class Method Formatting
				if($class !== '' && method_exists($this->ci->$class, $method))
				{
					$this->new_val = call_user_func_array(array($this->ci->$class, $method), $params);
				}
				// Run Procedural Function Formatting
				elseif(function_exists($method))
				{
					$this->new_val = call_user_func_array($method, $params);
				}
				else
				{
					log_message('debug', "Class: $class | Function: $method -- Nothing was Called.  Format_db Library");
				}
				
				// Handle Errors >> REFERENCE: http://php.net/manual/en/function.call-user-func-array.php
				if($this->new_val === FALSE)
				{
					log_message('error', "Class: $class | Function: $method -- call_user_func_array() failed to work properly.  Format_db Library");
					return $value;
				}
			}
			
			return $this->new_val;
		}		
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 * Parse to Type
	 * 
	 * Takes a string and parses it to boolean or null.
	 * NOTE: This is not evaluating the expression as to whether
	 * it is TRUE or FALSE, but rather checking for explicit 
	 * references to "true", "false", or "null".
	 *
	 * THIS FUNCTION IS CASE SENSITIVE.  All references to must be lowercase
	 *
	 * @since	1.0.2
	 * @param	array
	 * @return	array
	 */
	protected function parse_to_type($params = array())
	{
		if(empty($params))
		{
			return $params;
		}
		
		$new = array();		
		foreach($params as $param)
		{
			$new[] = (isset($this->parse_types_supported[$param])) ? $this->parse_types_supported[$param]: $param;
		}
		
		return $new;
	}

}
/* End of file ./libraries/Format_db.php */