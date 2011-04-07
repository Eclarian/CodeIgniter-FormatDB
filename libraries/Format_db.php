<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *	Format Database
 *
 *	This library allows you to target format your entire database
 *	for the specific display scenario you find yourself in (e.g. table)
 *
 *	USAGE EXAMPLES
 *	  + BASIC USAGE
 *		$query = $this->db->get();
 *		$this->format_db->run($query); // Query is now formatted!
 *
 *	  + MULTIPLE FORMAT -> Allows you to have a default format and then supplement it.
 *		$query = $this->db->get();
 *		$this->format_db->run($query); // Query is now formatted!
 *		$this->format_db->config('new_config', TRUE)->run($query); // Query run through new Format!
 *		
 *
 *	@author		Eclarian Dev Team (Joel Kallman & Joseph Moss)
 *	@copyright	Copyright (c) 2009-2011, Eclarian LLC
 *	@license	MIT
 *	@link		http://www.eclarian.com/codeigniter-format-database-library/
 *	@version 	1.0
 */
class Format_db {

	/**
	 *	Format Configuration for Fields
	 */
	private $config = array();
	private $config_set = FALSE;
	
	/**
	 *	Format DB Configuration
	 */
	private $default_format_config = 'format_general';
	private $method_map = array();
	
	/**
	 *	New Value allows for Proper Chaining
	 */
	private $new_val = '';
	
	/**
	 *	Constructor
	 *
	 *	Loads the configuration for the class
	 */
	public function __construct()
	{
		$CI =& get_instance();
		$CI->config->load('format_config', TRUE);
		$config = $CI->config->item('format_config');
		
		foreach($config as $prop => $val)
		{
			$this->$prop = $val;
		}
	}
	
	/**
	 *	Reset Config
	 *
	 *	Clears Configuration
	 *	
	 *	@return	object
	 */
	public function reset_config()
	{
		unset($this->config);
		$this->config = array();
		return $this;
	}
	
	
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
			$CI =& get_instance();
			$config = ($config !== '') ? $config: $this->default_format_config;
			$CI->config->load($config, TRUE, TRUE); // Suppressing Errors if no File Included
			$this->config = $CI->config->item($config);
			$this->config_set = TRUE;
		}
		
		return $this;
	}
	
	
	/**
	 *	Run
	 *
	 *	Formats the Results based on the configuration setup
	 *
	 *	@param	array	Can be a Result Array or Row Array
	 *	@param	string	Specify a Different Configuration
	 *	@return	object
	 */
	public function run(&$array)
	{
		$CI =& get_instance();	
		$this->config(); // Load Default Configuration if None Loaded Yet
		
		// Format the Data
		if(is_array($array) && ! empty($array))
		{
			foreach($array as $k => &$row)
			{
				if(is_array($row))
				{
					foreach($row as $field => &$val)
					{
						$array[$k][$field] = $this->filter($field, $val, $CI);
					}
				}
				else
				{
					$array[$k] = $this->filter($k, $row, $CI);
				}
			}
		}
		
		return $this;
	}
	
		
	/**
	 *	Filter
	 *
	 *	Processes the Current Field|Value Combination
	 *	based on the configuration settings
	 *	
	 *	@param	string	
	 *	@param	string	
	 *	@param	object	
	 *	@return	string
	 */
	private function filter($field, $value, $CI)
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
					array_unshift($params, $this->new_val);
					
					// Check if Function is a Registered Class Method
					if(isset($this->method_map[$method]))
					{
						$class = $this->method_map[$method];
					}					
					
					// Run Class Method Formatting
					if($class !== '' && method_exists($CI->$class, $method))
					{
						$this->new_val = call_user_func_array(array($CI->$class, $method), $params);
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
					
					// Handle Errors
					if($this->new_val === FALSE)
					{
						// Log Error - Function Didn't work for some reason
						log_message('error', "Class: $class | Function: $method -- call_user_func() failed to work properly.  Format_db Library");
						return $value;
					}
				}
				
				return $this->new_val;
			}			
		}		
	}
	
	
}

/* End of file ./libraries/Format_db.php */