<?php

class Router {

	// configured routes
	public static $routes = array();

	// request uri
	public static $current_uri;

	// routed uri
	public static $routed_uri;

	// controller instance
	public static $instance;

	// routed controller
	public static $controller;

	// routed method, index is default
	public static $method = 'index';

	// uri arguments
	public static $arguments = array();

	// find URI from CLI or PHP_SELF
	public static function uri() {
			
		// get URI from command line argument if running from CLI
		if (PHP_SAPI === 'cli') {

			// use first command line argument or "/"
			$uri = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : '/';

		} else {

			// Get Requested URI not including APP_NAME or containing directories
			//if ( ! empty($_SERVER['PATH_INFO'])) {
			if (1==2) {
				$uri = $_SERVER['PATH_INFO']; # PATH_INFO does not contain the docroot or index
			}
			else {
				// REQUEST_URI and PHP_SELF include the docroot and index
				if (isset($_SERVER['REQUEST_URI'])) {
					/*
					We use REQUEST_URI as the fallback value. The reason
					for this is we might have a malformed URL such as:
					
					http://localhost/http://example.com/judge.php
					
					which parse_url can't handle. So rather than leave empty
					handed, we'll use this.
					*/
					$uri = $_SERVER['REQUEST_URI'];
	
					// Valid URL path found, set it.
					if ($request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) 
						$uri = $request_uri;
	
					// Decode the request URI
					$uri = rawurldecode($uri);
				}
				elseif (isset($_SERVER['PHP_SELF'])) {
					$uri = $_SERVER['PHP_SELF'];
				}
				elseif (isset($_SERVER['REDIRECT_URL'])) {
					$uri = $_SERVER['REDIRECT_URL'];
				}
				else {
					die('Error: Unable to detect the URI using PATH_INFO, REQUEST_URI, PHP_SELF or REDIRECT_URL');
				}
			}
		
		}
		
		// remove front router (index.php) if it exists in URI
		if (($pos = strpos($uri, ROUTER)) !== FALSE) {
			$uri = (string) substr($uri, $pos + strlen(ROUTER));
		}
		
		// remove start and ending slashes
		return trim($uri, '/');

	}


	// simple router, takes uri and maps controller, method and arguments
	public static function init() {

		// find URI
		$uri = self::uri();
								
		// remove query string from URI
		if (($query = strpos($uri, '?')) !== FALSE) {

			// split URI on question mark
			list ($uri, $query) = explode('?', $uri, 2);

			// parse the query string into $_GET if using CLI
			// warning: converts spaces and dots to underscores
			if (PHP_SAPI === 'cli')
				parse_str($query, $_GET);

		}

		// store requested URI on first run only
		if (self::$current_uri === NULL)
			self::$current_uri = trim($uri, '/');

		// matches a defined route
		$matched = FALSE;

		// match URI against route
		foreach (self::$routes as $route => $callback) {

			// trim slashes
			$route = trim($route, '/');
			$callback = trim($callback, '/');

			if (preg_match('#^'.$route.'$#u', self::$current_uri)) {

				if (strpos($callback, '$') !== FALSE) {

					// use regex routing
					self::$routed_uri = preg_replace('#^'.$route.'$#u', $callback, self::$current_uri);

				} else {

					// standard routing
					self::$routed_uri = $callback;

				}

				// valid route has been found
				$matched = TRUE;
				break;

			}
		}

		// no route matches found, use actual uri
		if (! $matched)
			self::$routed_uri = self::$current_uri;

		// use default route if requesting /
		if (empty(self::$routed_uri))
			self::$routed_uri = self::$routes['_default'];

		// decipher controller/method
		$segments = explode('/', self::$routed_uri);
	
		// controller is first segment
		self::$controller = $segments[0];
		
		// use default method if none specified
		self::$method = (isset($segments[1])) ? $segments[1] : self::$method;

		// remaining arguments
		self::$arguments = array_slice($segments, 2);

		// instatiate controller
		self::execute();

	}

	// instantiate controller
	public static function execute() {

		// only run once per request
		if (self::$instance === NULL) {

			try {
			
				// start validation of the controller
				// Replace any hypens with underscores (want to use hypens in url, but underscores in method name)
				self::$controller = str_replace("-", "_", self::$controller);
				$class = new ReflectionClass(self::$controller.'_controller');
				//Utils::alert_admin("Test", "Controller:".self::$controller); // @@ Sabra test

			} catch (ReflectionException $e) {
			
				// controller does not exist
				
				return self::__404();

			}

			// create a new controller instance
			self::$instance = $class->newInstance();

			try {

				// load the controller method
				// Replace any hypens with underscores (want to use hypens in url, but underscores in method name)
				$method = $class->getMethod(str_replace("-", "_", self::$method));

				// methods prefixed with an underscore are hidden
				if (self::$method[0] === '_') {

					// do not allow access to hidden methods
					return self::__404();
				}

				if ($method->isProtected() or $method->isPrivate()) {

					// do not attempt to invoke protected methods
					return self::__404();
				}

				// default arguments
				$arguments = self::$arguments;

			} catch (ReflectionException $e) {

				try {

					// try to use __call instead
					$method = $class->getMethod('__call');

					// use arguments in __call format
					$arguments = array(self::$method, self::$arguments);

				} catch (ReflectionException $e) {

					// method or __call does not exist
					return self::__404();

				}

			}

			// execute the controller method
			$method->invokeArgs(self::$instance, $arguments);

		}

	}

	// simple 404 message
	public static function __404() {

		// send 404 header
		header('HTTP/1.1 404 File Not Found');

		// show 404 message
		echo '<h1>Error 404 - File Not Found (#ROUTER209)</h1>';

	}


	// for simple redirects
	public static function redirect($url = '/') {

		header('Location: '.$url);
		exit;

	}

}