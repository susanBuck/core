<?php

// Library for re-usable debugging functions
// All methods should be static, accessed like: Debug::method(...);
class Debug {
	
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function printr($array) {
		
		echo "<pre>";
		print_r($array);
		echo "</pre>";
		
	}
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function log($msg) {
		if(!IN_PRODUCTION) {
			$log = new Log(LOG_PATH."Debug", Log::INFO );
			$log->logInfo($msg);
		}
	}
	
	/*-------------------------------------------------------------------------------------------------
	Dumper function, currently a wrapper for krumo::dump
	-------------------------------------------------------------------------------------------------*/
	public static function dump($data, $label = NULL, $backtrace = TRUE) {
		
		ob_start();
		
		//if(Utils::is_ajax()) {
		//	echo $data;
		//}
		//else {
			krumo::dump($data, $label, $backtrace);
		//}

		return ob_get_clean();
		
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	General debug info for page footer
	-------------------------------------------------------------------------------------------------*/
	public static function info() {
	
		// disable in production
		if (isset($_GET['debug']) && strtolower($_GET['debug']) != "false") {

			// debug block w/ execution time and router info
			echo PHP_EOL.'<div style="font-family: monospace; font-size: 13px; width: 80%; margin: 20px auto;"><b style="color: #008800;">DEBUG INFO</b><br/>'.PHP_EOL;
			echo '<b>Routed Controller/Method:</b> '.Router::$controller.'/'.Router::$method.'<br/>'.PHP_EOL;
			echo '<b>Execution Time:</b> '.EXECUTION_TIME.' sec'.PHP_EOL;
			
			// show mysql query history
			echo DB::instance()->query_history().PHP_EOL;
			
			// show included files
			krumo::includes(FALSE).PHP_EOL;
			
			echo '<br/></div>'.PHP_EOL;

		// disable krumo in production
		} elseif (IN_PRODUCTION) {
			
			// disable krumo output
			krumo::disable();
			
		}
		
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function console($message) {
	    echo '<script type="text/javascript">';
	    echo 'console.log("'.$message.'");';    
	    echo '</script>';
	
	}
	
}