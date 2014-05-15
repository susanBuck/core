<?php

class Router {

	# Configured routes
	public static $routes = array();

	# Request uri
	public static $current_uri;

	# Routed uri
	public static $routed_uri;

	# Controller instance
	public static $instance;

	# Routed controller
	public static $controller;

	# Routed method, index is default
	public static $method = 'index';

	# uri arguments
	public static $arguments = array();

	# Find URI from CLI or PHP_SELF
	public static function uri() {
			
		# get URI from command line argument if running from CLI
		if (PHP_SAPI === 'cli') {

			# use first command line argument or "/"
			$uri = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : '/';

		} else {

			# REQUEST_URI and PHP_SELF include the docroot and index
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
		
		# Remove front router (index.php) if it exists in URI
		if (($pos = strpos($uri, ROUTER)) !== FALSE) {
			$uri = (string) substr($uri, $pos + strlen(ROUTER));
		}
		
		# Remove start and ending slashes
		return trim($uri, '/');

	}


	# Simple router, takes uri and maps controller, method and arguments
	public static function init() {

		# Find URI
		$uri = self::uri();
								
		# Remove query string from URI
		if (($query = strpos($uri, '?')) !== FALSE) {

			# Split URI on question mark
			list ($uri, $query) = explode('?', $uri, 2);

			# Parse the query string into $_GET if using CLI
			# warning: converts spaces and dots to underscores
			if (PHP_SAPI === 'cli')
				parse_str($query, $_GET);

		}

		# Store requested URI on first run only
		if (self::$current_uri === NULL)
			self::$current_uri = trim($uri, '/');

		# Matches a defined route
		$matched = FALSE;

		# Match URI against route
		foreach (self::$routes as $route => $callback) {

			# Trim slashes
			$route = trim($route, '/');
			$callback = trim($callback, '/');

			if (preg_match('#^'.$route.'$#u', self::$current_uri)) {

				if (strpos($callback, '$') !== FALSE) {

					# Use regex routing
					self::$routed_uri = preg_replace('#^'.$route.'$#u', $callback, self::$current_uri);

				} else {

					# standard routing
					self::$routed_uri = $callback;

				}

				# Valid route has been found
				$matched = TRUE;
				break;

			}
		}

		# No route matches found, use actual uri
		if (! $matched)
			self::$routed_uri = self::$current_uri;

		# Use default route if requesting /
		if (empty(self::$routed_uri))
			self::$routed_uri = self::$routes['_default'];

		# Decipher controller/method
		$segments = explode('/', self::$routed_uri);
	
		# Controller is first segment
		self::$controller = $segments[0];
		
		# Use default method if none specified
		self::$method = (isset($segments[1])) ? $segments[1] : self::$method;

		# Remaining arguments
		self::$arguments = array_slice($segments, 2);

		# Instatiate controller
		self::execute();

	}

	# Instantiate controller
	public static function execute() {

		# Only run once per request
		if (self::$instance === NULL) {

			try {
			
				# Start validation of the controller
				# Replace any hypens with underscores (want to use hypens in url, but underscores in method name)
				self::$controller = str_replace("-", "_", self::$controller);
				$class = new ReflectionClass(self::$controller.'_controller');


			} catch (ReflectionException $e) {
			
				# Controller does not exist			
				return self::__404();

			}

			# Create a new controller instance
			self::$instance = $class->newInstance();

			try {

				# Load the controller method
				# Replace any hypens with underscores (want to use hypens in url, but underscores in method name)
				$method = $class->getMethod(str_replace("-", "_", self::$method));

				# <ethods prefixed with an underscore are hidden
				if (self::$method[0] === '_') {

					# Do not allow access to hidden methods
					return self::__404();
				}

				if ($method->isProtected() or $method->isPrivate()) {

					# Do not attempt to invoke protected methods
					return self::__404();
				}

				# Default arguments
				$arguments = self::$arguments;

			} catch (ReflectionException $e) {

				try {

					# Try to use __call instead
					$method = $class->getMethod('__call');

					# Use arguments in __call format
					$arguments = array(self::$method, self::$arguments);

				} catch (ReflectionException $e) {

					// method or __call does not exist
					return self::__404();

				}

			}

			# Execute the controller method
			$method->invokeArgs(self::$instance, $arguments);

		}

	}

	# Simple 404 message
	public static function __404() {

		# Send 404 header
		header('HTTP/1.1 404 File Not Found');

		# Show 404 message
		echo '<h1>Error 404 - File Not Found (#ROUTER209)</h1>';

	}


	# For simple redirects
	public static function redirect($url = '/') {

		header('Location: '.$url);
		exit;

	}

}