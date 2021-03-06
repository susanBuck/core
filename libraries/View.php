<?php 
/*
View Library
Acts as an object wrapper for HTML pages with embedded PHP, called "views". 
Variables can be assigned with the view object and referenced locally within the view.
*/

class View {

	# Array of global variables
	protected static $_global_data = array();
	
	# View filename
	protected $_file;

	# Array of local variables
	protected $_data = array();	
	
	
	/*-------------------------------------------------------------------------------------------------
	Sets the initial view filename and local data. Views should almost
	always only be created using [View::instance].
	$view = new View($file);
	-------------------------------------------------------------------------------------------------*/
	public function __construct($file = NULL, array $data = NULL) {

		if ($file !== NULL) {

			$this->set_filename($file);

		}

		if ($data !== NULL) {

			// Add the values to the current data
			$this->_data = $data + $this->_data;

		}
	}

	/*-------------------------------------------------------------------------------------------------
	Returns a new View object. If you do not define the "file" parameter, you must call [View::set_filename].
	$view = View::instance($file);	
	-------------------------------------------------------------------------------------------------*/
	public static function instance($file = NULL, array $data = NULL) {
		return new View($file, $data);		
	}
	
		
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function load_assets($head_or_body) {
		
		$contents        = "";
		$controller      = Router::$controller;
		$method          = str_replace('-','_',Router::$method);
		
		# Get the app's client files configuration
		$assets = Spyc::YAMLLoad(COMBINED_ASSETS_CONFIG);		
				
		# COMPRESSED -->
		if(USE_COMBINED_ASSETS) {
			
			foreach(Array('.css','.js') as $ext) {
				
				# Master compressed
				$file_name = 'master_master_'.$head_or_body.$ext;
				if(file_exists(COMBINED_ASSETS_PATH.$file_name)) {
					$contents .= self::__create_client_include_string($file_name, TRUE);
				}
				
				# Method compressed
				$file_name = $controller.'_'.$method.'_'.$head_or_body.$ext;
				if(file_exists(COMBINED_ASSETS_PATH.$file_name)) {
					$contents .= self::__create_client_include_string($file_name, TRUE);
				}
			}
		
		}
		
		# UNCOMPRESSED -->
		# If LOCAL, check client_files.yml to see what we need to load
		else {
		
			# Master files (all of them individually, not compressed)
			$master_files = $assets['master']['master'][$head_or_body];	
			foreach($master_files as $file_name) {
				$contents .= self::__create_client_include_string($file_name);
			}
		
			# Method files
			if(isset($assets[$controller][$method][$head_or_body])) {
				$files = $assets[$controller][$method][$head_or_body];
				
				# If body or head was empty, this may be empty so check first
				if(is_array($files)) {
					foreach($files as $file_name) {
		            	$contents .= self::__create_client_include_string($file_name);
					}
				}
			}
	       
		}
		
		if(isset($_GET['debug'])) {
			echo "<pre>";		
			echo $head_or_body.":";
			echo str_replace('<','<br>&lt;',$contents);
			echo "</pre>";
			echo "<br>";
		}
		
		return $contents;
		
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	Returns a HTML <script> (for JS) or <link> for (CSS) element
	If compressed is set to TRUE it will look for the file in COMBINED_ASSETS_PATH
	-------------------------------------------------------------------------------------------------*/
	private static function __create_client_include_string($file_name, $compressed = FALSE) {
	
		$path = ($compressed) ? COMBINED_ASSETS_URL.self::__extract_file_name_from_path($file_name) : $file_name;
		
		# Cache bust should be set in app's config file
		if(defined('CACHE_BUST')) {
			$path .= "?cb=".CACHE_BUST;
		}
		
		# CSS
		if(strstr($path,".css")) {
			return '<link rel="stylesheet" type="text/css" href="'.$path.'">';
		}
		# JS           
        elseif(strstr($path,".js")) {		
        	return '<script src="'.$path.'"></script>';	
        }
		
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	given something like /path/to/file.txt, returns just file.txt
	-------------------------------------------------------------------------------------------------*/
	private static function __extract_file_name_from_path($path) {
		
		$path      = explode('/', $path);
		$file_name = array_pop($path);
		return $file_name;
		
	}

	// Captures the output that is generated when a view is included.
	// The view data will be extracted to make local variables. 
	// This method is static to prevent object scope resolution.	
	// $output = View::capture($file, $data);
	protected static function capture($view_filename, array $view_data) {
		
		// Import the view variables to local namespace
		extract($view_data, EXTR_SKIP);

		if (View::$_global_data) {

			// Import the global view variables to local namespace
			extract(View::$_global_data, EXTR_SKIP);
		}

		// Capture the view output
		ob_start();

		try {
			// Load the view within the current scope
			include $view_filename;
			
		} catch (Exception $e){
			
			// Delete the output buffer
			ob_end_clean();

			// Re-throw the exception
			throw $e;
		}

		// Get the captured output and close the buffer
		return ob_get_clean();
	}

	// Sets a global variable, similar to [View::set], except that the
	// variable will be accessible to all views.	
	// View::set_global($name, $value);
	public static function set_global($key, $value = NULL) {
		
		if (is_array($key)) {
			
			foreach ($key as $key2 => $value) {
				
				View::$_global_data[$key2] = $value;
			}
			
		} else {

			View::$_global_data[$key] = $value;

		}
	}

	// Assigns a global variable by reference, similar to [View::bind], except
	// that the variable will be accessible to all views.
	// View::bind_global($key, $value);
	public static function bind_global($key, & $value) {
		
		View::$_global_data[$key] =& $value;

	}

	

	// Magic method, searches for the given variable and returns its value.
	// Local variables will be returned before global variables.
	// $value = $view->foo;
	// [!!] If the variable has not yet been set, an exception will be thrown.
	public function & __get($key) {

		if (array_key_exists($key, $this->_data)) {

			return $this->_data[$key];

		} elseif (array_key_exists($key, View::$_global_data)) {

			return View::$_global_data[$key];
			
		} else {
			
			throw new Exception("View variable is not set: $key");
			
		}
	}

	// Magic method, calls [View::set] with the same parameters.
	// $view->foo = 'something';
	public function __set($key, $value) {

		$this->set($key, $value);

	}

	// Magic method, determines if a variable is set.
	// isset($view->foo);
	// [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
	public function __isset($key) {

		return (isset($this->_data[$key]) OR isset(View::$_global_data[$key]));

	}

	// Magic method, unsets a given variable.
	// unset($view->foo);
	public function __unset($key) {

		unset($this->_data[$key], View::$_global_data[$key]);

	}

	/// Magic method, returns the output of [View::render].
	public function __toString() {
		
		try {
			
			return $this->render();
			
		} catch (Exception $e) {
			
			// Display the exception message
			return $e->getMessage();

		}
	}

	// Sets the view filename.
	// $view->set_filename($file);
	public function set_filename($file, $search_path = NULL) {
	
		if (($path = File::find('views/'.$file, $search_path)) === FALSE) {

			throw new Exception("The requested view $file could not be found");

		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}

	// Assigns a variable by name. Assigned values will be available as a variable within the view file:
	// This value can be accessed as $foo within the view
	// $view->set('foo', 'my value');
	// You can also use an array to set several values at once:
	// Create the values $food and $beverage in the view
	// $view->set(array('food' => 'bread', 'beverage' => 'water'));
	public function set($key, $value = NULL) {

		if (is_array($key)) {

			foreach ($key as $name => $value) {

				$this->_data[$name] = $value;
			}
		} else {

			$this->_data[$key] = $value;

		}

		return $this;
	}

	// Assigns a value by reference. The benefit of binding is that values can
	// be altered without re-setting them. It is also possible to bind variables
	// before they have values. Assigned values will be available as a variable within the view file:

	// This reference can be accessed as $ref within the view
	// $view->bind('ref', $bar);
	public function bind($key, & $value) {

		$this->_data[$key] =& $value;

		return $this;
	}

	// Renders the view object to a string. Global and local data are merged and extracted to create local variables within the view file.
	// $output = $view->render();
	// Global variables with the same key name as local variables will be overwritten by the local variable.
	public function render($file = NULL) {

		if ($file !== NULL) {

			$this->set_filename($file);

		}

		if (empty($this->_file)) {
			
			throw new Exception('You must set the file to use within your view before rendering');
			
		}

		// Combine local and global data and capture the output
		return View::capture($this->_file, $this->_data);
	}

}
