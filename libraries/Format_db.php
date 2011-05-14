<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *	Format Database
 *
 *	This library allows you to target format your entire database (or any array)
 *	for the specific display scenario you find yourself in (e.g. table)
 *
 *	USAGE EXAMPLES
 *	  + BASIC USAGE
 *		$query = $this->db->get();
 *		$result = $query->result();
 *		$this->format_db->run($result); // Query is now formatted!
 *
 *	  + MULTIPLE FORMAT -> Allows you to have a default format and then supplement it.
 *		$query = $this->db->get();
 *		$result = $query->result();
 *		$this->format_db->run($result);
 *		$this->format_db->config('new_config', TRUE)->run($result); 
 *		
 *
 *	@author		Eclarian Dev Team (Joel Kallman & Joseph Moss)
 *	@copyright	Copyright (c) 2011, Eclarian LLC
 *	@license	MIT
 *	@link		http://www.eclarian.com/codeigniter-format-database-library/
 *	@version 	1.0.2
 */
class Format_db {

	/**
	 *	CodeIgniter Object
	 */
	private $ci;

	/**
	 *	Format Configuration for Fields
	 */
	private $config							= array();
	private $config_set						= FALSE;
	private $disallow_formatting			= FALSE;
	
	/**
	 *	Format DB Configuration
	 */
	private $default_format_config			= 'format_general';
	private $enable_psuedo_bool_conversion	= TRUE;	// @since 1.0.2
	private $method_map						= array();
	
	/**
	 *	Allows for Proper Function Chaining
	 */
	private $new_val						= '';
	
	/**
	 *	Parse to Types Supported Conversions
	 */
	private $parse_types_supported			= array('true' => TRUE, 'false' => FALSE, 'null' => NULL);
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 *	Constructor
	 *
	 *	Loads the configuration for the class
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
	 *	Reset Config
	 *
	 *	Clears Configuration
	 *	
	 *	@param	bool	$disallow_formatting	No Formatting Performed for Subsequent Run()
	 *	@return	object
	 */
	public function reset_config($disallow_formatting = FALSE)
	{
		$this->disallow_formatting = $disallow_formatting;
		// This solves a potential bug in certain versions of PHP that don't reset the array with just a redeclaration.		
		unset($this->config);	
		$this->config = array();
		return $this;
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 *	Enable Formatting
	 *	
	 *	This allows you to re-enable formatting after it has 
	 *	been disabled with a second configuration.
	 *	
	 *	This can be useful if you are formatting your DB result multiple times
	 *	and one of your formatting methods runs a DB query which when run with
	 *	the current formatting causes an fatal error because of the infinite 
	 *	recursion which results in the PHP memory limit being reached.  
	 *	
	 *	@return object
	 */
	public function enable_formatting()
	{
		$this->disallow_formatting = FALSE;
		return $this;
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 *	Config
	 *
	 *	Specifies the field|method array configuration
	 *	To prevent multiple load calls, config_set is used.
	 *
	 *	@param	string	Specify a Different Configuration
	 *	@param	bool	Allows you to overwrite previous configuration
	 *	@return	object
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
	 *	Run
	 *
	 *	Formats the Results based on the configuration setup
	 *
	 *	Accepts Arrays (e.g. row_array() and result_array()) 
	 *	and Objects since 1.0.2 (e.g. row() and result())
	 *
	 *	@param	mixed
	 *	@return	object
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
	 *	Array Loop
	 *
	 *	@param	mixed	Array or String
	 *	@param	string
	 *	@return	array
	 */
	private function _array_loop($row, $k)
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
	 *	Object Loop
	 *
	 *	@param	object
	 *	@return	object
	 */
	private function _object_loop($row)
	{
		foreach(get_object_vars($row) as $field => $val)
		{
			$row->$field = $this->filter($field, $val);
		}
		
		return $row;
	}
	
	// ----------------------------------------------------------------------------------------	
		
	/**
	 *	Filter
	 *
	 *	Processes the Current Field|Value Combination
	 *	based on the configuration settings
	 *	
	 *	@param	string	
	 *	@param	string	
	 *	@return	string
	 */
	private function filter($field, $value)
	{		
		if( ! isset($this->config[$field]))
		{
			return $value;
		}
		else
		{
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
	}
	
	// ----------------------------------------------------------------------------------------
	
	/**
	 *	Parse to Type
	 *	
	 *	Takes a string and parses it to boolean or null.
	 *	NOTE: This is not evaluating the expression as to whether
	 *	it is TRUE or FALSE, but rather checking for explicit 
	 *	references to "true", "false", or "null".
	 *
	 *	THIS FUNCTION IS CASE SENSITIVE.  All references to must be lowercase
	 *
	 *	@since	1.0.2
	 *	@param	array
	 *	@return	array
	 */
	private function parse_to_type($params = array())
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