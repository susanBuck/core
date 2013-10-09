<?php

// library for re-usable utility functions
// All methods should be static, accessed like: Utils::method(...);
class Utils {

	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function make_urls_links($text) {
	
		$text = trim($text);
		
		while ($text != stripslashes($text)) { 
			$text = stripslashes($text); 
		}    
		
		$text = preg_replace("/(?<!http:\/\/)www\./","http://www.",$text);
		$text = preg_replace( "/((http|ftp)+(s)?:\/\/[^<>\s]+)/i", "<a href=\"\\0\" target=\"_blank\">\\0</a>",$text);
	
		return $text;
		
	}


	/*-------------------------------------------------------------------------------------------------
	Truncates a string to a certain char length, stopping on a word if not specified otherwise.
	-------------------------------------------------------------------------------------------------*/
	public static function truncate($string, $length, $stopanywhere = FALSE) {

	    if (strlen($string) > $length) {
	    
	        # limit hit
	        $string = substr($string,0,($length -3));
	    
	        # Stop anywhere
	        if ($stopanywhere) {
	            $string .= '...';
	        # Stop on a word
	        } else {
	            $string = substr($string,0,strrpos($string,' ')).'...';
	        }
	    }
	    return $string;
	}


	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function generate_random_string($length = 6) {
	
		$vowels     = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		$string     = '';
		
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++) {
			if ($alt == 1) {
				$string .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			} else {
				$string .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		
		return $string;

	}


	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function postfix($string_to_add, $file_name) {

		# Get the extension
		$extension = strrchr($file_name, '.');
		
		# Now chop off the extension
		$file_name = str_replace($extension, "", $file_name);
		
		# Now piece it all back together
		return $file_name.$string_to_add.$extension;
	   
	}


	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function set_visit_time($identifier = NULL) {
		
		$cookie_name = "visit_".Router::$controller."_".Router::$method."_".$identifier;
		$cookie_value = Time::now();
				
		# Suppress notice for instances when cookie does not exist		
		$last_visit = @$_COOKIE[$cookie_name];	
				
		setcookie($cookie_name, $cookie_value, strtotime('+1 year'), '/');
		
		return $last_visit;
	
	}

	/*-------------------------------------------------------------------------------------------------
	Use: array_sort_by_column($array, 'order');
	-------------------------------------------------------------------------------------------------*/
	public static function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {

	    $sort_col = array();
	    
	    if(empty($arr)) return;
	  	     	       
	    foreach ($arr as $key => $row) {

		    # If we can't find the column, return
		    if(!array_key_exists($col, $row)) {
		    	return;		    
		    }
		    	
	        $sort_col[$key] = $row[$col];
	       
	    }
	
	    array_multisort($sort_col, $dir, $arr);
	    
	}


	/*-------------------------------------------------------------------------------------------------
	NOTE this is configured for sending XML...
	-------------------------------------------------------------------------------------------------*/
	public static function curl($url, $timeout = 0, $ssl = true, $password = NULL, $post_fields = NULL, $xml = false) {
	
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$ch 		= curl_init();
		
		if($xml) {
			$header[]   = "Content-type: ".$type.";charset=\"utf-8\"";
			curl_setopt($ch, CURLOPT_HEADER, $header);
		}
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		
		# Convert to miliseconds		
		$timeout = $timeout * 1000;

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
		
		
		if($ssl) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);	
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		} 
		
		if($password)
			curl_setopt($ch, CURLOPT_USERPWD, $password);
	
		if($post_fields)
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);  
		
	
		$result = curl_exec ($ch);
		curl_close ($ch);
		return $result;
			
	}


	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function force_https($to_https = true) {
	
		$url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		//$url = str_replace("index.php/", "", $url);
	
        if ($to_https) {
            // force https if not already
            if (! isset($_SERVER["HTTPS"])) {            	
                Router::redirect("https://".$url);
            } 
        }
        else {
            // force http if not already
            if (isset($_SERVER["HTTPS"])) {
                Router::redirect("http://".$url);
            } 
        }
	 
	}


	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function alert_admin($subject = NULL, $body = NULL) {
	
		# Email app owner
		
		if(SYSTEM_EMAIL) 
			$email = SYSTEM_EMAIL;
		else 
			$email = APP_EMAIL;
		
		$to[]    = Array("name" => APP_NAME, "email" => $email);
		$from    = Array("name" => APP_NAME, "email" => APP_EMAIL);
		
		$subject = APP_NAME." ".$subject;
		
		# Add Router and execution time
		$body .= '<h2>Routed Controller/Method:</h2> '.Router::$controller.'/'.Router::$method.'<br/>';
		
		# Add cookies
		$body .= "<h2>Cookies</h2>";
		$body .= "<pre>".print_r($_COOKIE, true)."</pre>";

		# Add _POST
		$body .= "<h2>POST</h2>";
		$body .= "<pre>".print_r($_POST, true)."</pre>";
		
		# Add _GET
		$body .= "<h2>GET</h2>";
		$body .= "<pre>".print_r($_GET, true)."</pre>";
		
		# Add _SERVER
		$body .= "<pre>".print_r($_SERVER, true)."</pre>";
		
		# Fire email
		Email::send($to, $from, $subject, $body, true, '', '');
		
	}


	/*-------------------------------------------------------------------------------------------------
	types: message, error
	-------------------------------------------------------------------------------------------------*/
	public static function quick_view($template, $message, $title = NULL, $type = 'message') {
	
		# Setup view
			$template->content     		= View::instance('v_message');
			$template->title       		= $title;
			$template->content->message = $message;
			$template->content->type    = $type;
			$template->hide_menu 		= TRUE;
			$template->hide_footer 		= TRUE;
			$template->js_location      = 'head'; # Because we're using internal JS
		
		# Render view 
			echo $template;
	
	}
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function post_only($post, $destination) {
	
		if(!$post) 
			return Router::redirect($destination);
	
	}
	
	
	
	/*-------------------------------------------------------------------------------------------------
	A JS version of this exists in /core/js/code_mirror.js
	-------------------------------------------------------------------------------------------------*/
	public static function code_mirror_replace_tags($content, $insert_line_breaks = FALSE) {
	
		$content = str_replace("</textarea", "&lt;/textarea", $content);
		
		$content = str_replace("<code>", "<textarea class='code'>", $content);
		$content = str_replace("</code>", "</textarea>", $content);
	
		# Inline (cmi's) aren't showing up in IE..As a fix, replace with a basic <code> tag instead of a code mirror
		if(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			
			// For CMI's with html tags...Replace the first < to prevent it eating up everything after it. 
			$content = str_replace("<cmi><", "<code>&lt;", $content); 
			
			// Closing tag
			$content = str_replace("></cmi>", "&gt;</code>", $content); 
			
			// KNOWN ISSUE: When there's a start and end tag in a cmi
			// The closing tag ends up rendering
			// Class: <cmi><div class='footer'> © 2012 </div></cmi>
		
			// Now for CMI's without tags
			$content = str_replace("<cmi>", "<code>", $content); // Replace the first < to prevent it eating up everything after it. 
			
			// Closing cmi is same regardless of tag or not
			$content = str_replace("</cmi>", "</code>", $content);
			
		}
		# All other browsers can get code mirrored
		else {
			$content = str_replace("<cmi>", "<textarea class='code inline'>", $content);
			$content = str_replace("</cmi>", "</textarea>", $content);
		}
	
		// Don't include the closing > so that we can include attributes such as data_mode
		$content = str_replace("<cm", "<textarea class='code' ", $content);
		$content = str_replace("</cm>", "</textarea>", $content);
				
				
		if($insert_line_breaks) 
			$content = str_replace("\n", "<br>", $content);
		
		return $content;
	}


	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function print_colors($filename) {
	
		$results  = "";
		$handle   = fopen($filename, "rb");
		$colors   = fread($handle, filesize($filename));
		$colors   = explode(";", $colors);
		
		foreach($colors as $color_info) {
		
			$color = explode("#", $color_info);
			
			if(isset($color[1])) {
				$color = $color[1];
				$results .= "<div style='width:300px; padding:5px; margin-bottom:6px; background-color:#".$color."'>".$color_info."</div>";
			}
		
		}
		
		fclose($handle);
		
		return $results;
		
	}


	/*-------------------------------------------------------------------------------------------------
	given an array of k/v cookies, creates cookie(s) through PHP for the session	
	-------------------------------------------------------------------------------------------------*/
	public static function set_cookies($cookies) {
		
		// set cookies for current session (if headers haven't been sent already), not accessible until next request
		if (! headers_sent()) {
			foreach ($cookies as $name => $value)
				setcookie($name, $value, strtotime('+1 year'), '/');
		}
				
	}
	

    /*-------------------------------------------------------------------------------------------------
    
    -------------------------------------------------------------------------------------------------*/
    public static function load_client_files($files) {
    
    	$contents = "";
    
        foreach($files as $file) {
            
            if(strstr($file,".css")) {
            
            	if(strstr($file,"print_")) {
            		$contents .= '<link rel="stylesheet" type="text/css" href="'.$file.'" media="print">';
            	}
            	else {
	                $contents .= '<link rel="stylesheet" type="text/css" href="'.$file.'">';
	            }
            }
            else {
            	$contents .= '<script src="'.$file.'"></script>';	
            }

        }
        
        return $contents;
        
    }
    

	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function glob_recursive($pattern, $flags = 0) {
	
		$files = glob($pattern, $flags);
		
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
		    $files = array_merge($files, self::glob_recursive($dir.'/'.basename($pattern), $flags));
		}
		
		return $files;
	
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	Returns array of strings found between two target strings
	-------------------------------------------------------------------------------------------------*/
	public static function string_extractor($string,$start,$end) {
														
		# Setup
			$cursor = 0;
			$foundString = -1; 
			$stringExtractor_results = Array();
		 			 		
		# Extract  		
		while($foundString != 0) {
		
			$ini = strpos($string,$start,$cursor);
							
			if($ini != '') {
				$ini    += strlen($start);
				$len     = strpos($string,$end,$ini) - $ini;
				$cursor  = $ini;
				$result  = substr($string,$ini,$len);
				array_push($stringExtractor_results,$result);
				$foundString = strpos($string,$start,$cursor);	
			}
			else {
				$foundString = 0;
			}
		}
		
		return $stringExtractor_results;
		
	}
		
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function is_cron() {
		
		if(strstr($_SERVER['HTTP_USER_AGENT'], "curl")){
			return true;
		} else {
			return false;
		}
	
	}
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	// tests if the current request is an AJAX request by checking the X-Requested-With HTTP 
	// request header that most popular JS frameworks now set for AJAX calls.
	public static function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
	}	

	// given valid XML, returns a nicely formatted XML string with newlines and indenting
	public static function pretty_xml($xml) {

		$dom = new DOMDocument('1.0');
	  	$dom->preserveWhiteSpace = false;
	  	$dom->formatOutput = true;
	  	$dom->loadXML($xml);

	  	return $dom->saveXML();

	}

	// just converts to simplexml, then to array via simplexml_to_array
	public static function xml_to_array($xml, $attributesKey = null, $childrenKey = null) {

		$simpleXML = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		return Utils::simplexml_to_array($simpleXML);
		
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	
	-------------------------------------------------------------------------------------------------*/
	public static function simplexml_to_array($xml) {
	
		if (gettype($xml) == 'object' && get_class($xml) == 'SimpleXMLElement') {
			$attributes = $xml->attributes();
			foreach($attributes as $k=>$v) {
				if ($v) $a[$k] = (string) $v;
			}
			$x = $xml;
			$xml = get_object_vars($xml);
		}
		
		if (is_array($xml)) {
			if (count($xml) == 0) {
				return (string) $x; // for CDATA
			}
			foreach($xml as $key=>$value) {
				$r[$key] = Utils::simplexml_to_array($value);
			}
			if (isset($a)) $r['@attributes'] = $a;    // Attributes
			return $r;
		}
				
		return (string) $xml;
		
	}
	
	
	

} # eoc
