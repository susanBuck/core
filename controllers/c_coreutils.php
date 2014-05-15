<?php
/*
General app specific controller stored at the core level.
Allows for some general tasks like managing cookies, running tests, etc.
*/
class coreutils_controller {


	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public function combiner() {

		if(IN_PRODUCTION) die('Combiner can not be run when IN_PRODUCTION=true');		
	
		echo "<pre>";
				
		# Get yml config file that maps all the client files to controllers/methods
			$assets = Spyc::YAMLLoad(COMBINED_ASSETS_CONFIG);
			
			if(sizeof($assets) < 2) {
				die('Error loading assets. Make sure '.COMBINED_ASSETS_CONFIG.' exists and has data');
			}
					
		# Make sure our desination directory exists and is writable	
			if(!file_exists(COMBINED_ASSETS_PATH)) {
				die('ERROR: '.COMBINED_ASSETS_PATH.' is missing; create it and try again.');
			}
			elseif(!is_writable(COMBINED_ASSETS_PATH)) {
				die('ERROR: '.COMBINED_ASSETS_PATH.' is not writable.');
			}
		
		# Debugging info
			echo '<a href="'.COMBINED_ASSETS_URL.'" target="_blank">View combined directory</a><br><br>';
			echo "COMBINED_ASSETS_CONFIG: ".COMBINED_ASSETS_CONFIG."<br>"; 
			echo "COMBINED_ASSETS_URL: ".COMBINED_ASSETS_URL."<br>"; 
			echo "COMBINED_ASSETS_PATH: ".COMBINED_ASSETS_PATH."<br><br>"; 

		//array_map('unlink', glob(COMBINED_ASSETS_PATH));

		# Build files
		foreach($assets as $controller_name => $methods):
			foreach($methods as $method_name => $locations):
				if(is_array($locations)) {		
				foreach($locations as $location_name => $location):
					
					# Fresh slate for each method
					$compress_css = Array();
					$compress_js  = Array();
					
					if(is_array($location)) {
					# Split CSS and JS
					foreach($location as $k => $file):
					
						# Use APP_URL here instead of APP_PATH because the paths in assets.yml are client paths, not server paths
						if(strstr($file,'.css')) {
							$compress_css[] = APP_URL.$file;
						}
						elseif(strstr($file,'.js')) {
							$compress_js[] = APP_URL.$file;
						}
						
					endforeach;
					}
					
					# Process CSS
					if(!empty($compress_css)) {
						$destination = COMBINED_ASSETS_PATH.$controller_name.'_'.$method_name.'_'.$location_name.'.css';
						$c = new Compress($compress_css, $destination);
						$c->save();
					}
					
					# Process JS
					if(!empty($compress_js)) {
						$destination = COMBINED_ASSETS_PATH.$controller_name.'_'.$method_name.'_'.$location_name.'.js';
						$c = new Compress($compress_js, $destination);
						$c->save();
					}
	
				endforeach;	
				}
			endforeach;
		endforeach;
		
		echo "</pre>";
		echo Debug::dump($assets,"Made");
				
	} # eom
	

	/*-------------------------------------------------------------------------------------------------
		
	-------------------------------------------------------------------------------------------------*/
	public function index() {
					    
	    # Cookies
		    echo Debug::dump($_COOKIE,"Cookies");

	}
	
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public function clear_cookies() {
	
		foreach ( $_COOKIE as $key => $value ) {
			setcookie( $key, $value, time() - 3600, '/' );
		}
	
		echo "Cleared Cookies";
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	This is an example controller to demonstrate unit testing 
	using PHP SimpleTest (http://www.simpletest.org/)
	
	To run, download SimpleTest and set the correct path below. 
	Then, run this method (ex: http://localhost/coreutils/test-database)
	
	To see what tests are being run, open /core/libraries/DB_Test.php.
	
	If you wish to get into testing, you should also check out PHPUnit (https://github.com/sebastianbergmann/phpunit/) 
	as a possible alternative to SimpleTest. It's not as easy out of the gate, but more robust.
	-------------------------------------------------------------------------------------------------*/
	public function test_database() {
		
		if(IN_PRODUCTION) die('This method can not be run while in poduction.');
		
		if(REMOTE_DB) die('This method can not be run on the remote database.');
		
		/*
		If running simpletest from this example controller, a copy of SimpleTest 
		needs to be downloaded and put in the following location.
		*/
		$simpletest_path = DOC_ROOT."shared/vendors/simpletest/autorun.php";
		
		if(file_exists($simpletest_path)) {
			include($simpletest_path);
		}
		else {
			die('simpletest could not be located at '.$simpletest_path);
		}
		
		# Run tests
		$test = New DB_Test();
			
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	fakemail is fake mail server that captures e-mails as files for general testing or acceptance testing
	
	To use this, first, start the fakemail server via Command Line by moving into the /core/vendors 
	directory (where fakemail.py lives) and running this command, replacing /my/path with the directory where you want the
	email files to go.
		
		fakemail.py install --path=/my/path
		
	Leave this CL window open as long as you want the fakemail server to run.
	
	Next, make sure fakemail is enabled (preferrably in environment.php since this is only something you'd do locally)
	
		define('FAKEMAIL', TRUE);
		
	Now, any email being sent from your application will be routed to the directory above.
	You can test it out with this method below.
	-------------------------------------------------------------------------------------------------*/
	public function test_fakemail() {
	
		echo "See /core/controllers/c_app.php test_fakemail() for more instructions.<br>";
	
		if(!FAKEMAIL) {
			die('FAKEMAIL is false, so this test can not be run.');
		}
		
		$to[]    = Array("name" => APP_NAME, "email" => SYSTEM_EMAIL);
		$from    = Array("name" => APP_NAME, "email" => APP_EMAIL);
		$subject = "Testing fakemail ".Time::display(Time::now());							
		$body    = $subject;
		
		# Debug
		echo Debug::dump($to,"to");
		echo Debug::dump($from,"from");
			
		# Send email
		echo "Send email: ".Email::send($to, $from, $subject, $body, true, '');
		
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public function test_email() {
		
		$to[]    = Array("name" => APP_NAME, "email" => SYSTEM_EMAIL);
		$from    = Array("name" => APP_NAME, "email" => APP_EMAIL);
		$subject = "Testing email ".Time::display(Time::now());
		$body    = $subject;
			
		# Debug
		echo Debug::dump($to,"to");
		echo Debug::dump($from,"from");
		
		# Send email
		echo "Send email:" .Email::send($to, $from, $subject, $body, true, '');
	}
	

} // eoc