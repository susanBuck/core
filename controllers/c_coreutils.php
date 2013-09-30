<?php
/*
General app specific controller stored at the core level.
Allows for some general tasks like managing cookies, running tests, etc.
*/
class coreutils_controller {

	public function __construct() {	
	}


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
	Then, run this method (ex: http://localhost/app/test-database)
	
	To see what tests are being run, open /core/libraries/DB_Test.php.
	
	If you wish to get into testing, you should also check out PHPUnit (https://github.com/sebastianbergmann/phpunit/) 
	as a possible alternative to SimpleTest. It's not as easy out of the gate, but more robust.
	-------------------------------------------------------------------------------------------------*/
	public function test_database() {
		
		# Correct with your actual path to SimpleTest
		require_once(DOC_ROOT."/simpletest/autorun.php");
		
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