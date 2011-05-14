# Format DB - Targetting Array Field and Object Property Formatting

NOTE: In order to use closures, you must be running PHP 5.3.0 or higher
[PHP Docs on Callbacks](http://www.php.net/manual/en/language.pseudo-types.php#language.types.callback)

# Core Features
This class supports formatting for arrays and objects:

-	result_array()
-	row_array()
-	result()
-	row()

# Usage Examples
## Setting Up Your Configuration Files
There are by default two config files as part of this spark: 

1.	format_config  - Allows you to configure the functionality of the spark and add your own methods to the method_map
2.	format_general - The default config file loaded to format the result arrays and objects.

In format_general, the array key will be the same as the one that corresponds to the array key or object properties in the data.
The value will be function(s) you want to use to format the value.  Remember when chaining functions that the order in which they are placed will be the order in which they are executed.
	
	// Configuration (format_general.php)
	$config['date_registered'] = 'convert_sql_date';

In order to set the configuration to run multiple functions, separate your function calls by a "|" 
	$config['date_registered'] = 'trim|convert_sql_date';

In order to pass multiple parameters to a specific function, separate them with periods "."  
You can also have the words "false", "true", and "null" converted into their corresponding types by enabling it in your config file.  This is to provide you a way to pass booleans or NULL when required by a formatting function
However, remember that the string from your database results will ALWAYS be the first parameter of the function.
	
	$config['date_registered'] = 'wordwrap.100|trim|ucwords';
	$config['date_registered'] = 'function_that_requires_bool.true'; // The string "true" will be converted to the boolean TRUE
	
And last but not least, you may also use a closure if you are running PHP 5.3.0
	
	$config['date_registered']	= function($string) {
		// Put whatever you want in here to format this field.  
		return "FORMATTED: $string";
	};
	
## Example Usage in Controller/Model	
	
	// Following Data Formats are accepted
	$query = $this->db->get();
	
	// $query->result();
	$data = stdClass Object(
		array('date_registered' => '2010-04-01 22:41:55'),
		array('date_registered' => '2010-04-01 22:41:55')
	);
	
	// $query->row();
	$data = stdClass Object(
		'date_registered' => '2010-04-01 22:41:55'
	);
	
	// $query->result_array();
	$data = array(
		array('date_registered' => '2010-04-01 22:41:55'),
		array('date_registered' => '2010-04-01 22:41:55')
	);
	
	// $query->row_array();
	$data = array('date_registered' => '2010-04-01 22:41:55');
	
	
	// All the above formats will be processed 
	$this->format_db->run($data); // assumes format_db has been loaded
	
	print_r($data); // OUTPUT: array('date_registered' => 'Jan 5, 2010');

## Multiple Formatting Loops
While you are going to want to limit the number of times you send your result array or object through a loop, some scenarios demand that you have a default formatting for the whole app and specific formatting for certain sections (e.g. tables).
Here is how you can run multiple formatting configurations on the same data.

	$data = array('date_registered' => '2010-04-01 22:41:55');
	$this->format_db->run($data);
	$this->format_db->config('new_config', TRUE)->run($data);
	
	// You can also chain it if you want.  This would produce the same result.
	$this->format_db->run($data)->config('new_config', TRUE)->run($data);

Well, I hope you find some use out of this class!  Please report any bugs you find.  

As one final tip, I highly suggest you create a MY_Model class which allows you to have this formatting done automatically for the majority of your queries.
It speeds up development time significantly and also allows you to NEVER have to put a formatting function call in your controllers (and your views) ever again.
Your data is ready to display when you receive it and you also can easily go to a single location to adjust that display whenever necessary.

Thanks!!
	
[Suggestions or Bug Reports](https://github.com/Eclarian/CodeIgniter-FormatDB/issues)


