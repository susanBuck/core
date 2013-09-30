<?php

# Database Class
class DB {

	# Instance connection
	public $connection;

	# Last connected database
	public $database;

	# Singleton DB instance
	private static $instance;
	
	# Toggle whether to always re-select the database -- it is a performance drain
	public static $always_select = FALSE;

	# Debugging, don't send queries
	public static $debug = FALSE;

	# Store all queries
	public $query_history = array();
	
	# Store all query benchmarks
	public $query_benchmarks = array();

	# Private constructor to enforce singleton access
	private function __construct($db = NULL) {

		# Connect to database using credentials supplied by environment.php		
		$this->connection =	new mysqli(DB_HOST, DB_USER, DB_PASS);
		
		# If there are problems connecting...Show full message on local, email message and die gracefully on live
		if(mysqli_connect_errno()) {
			if (IN_PRODUCTION) {
	
					# Email app owner
					$subject = "SQL Error";
					$body    = "<h2>SQL Error</h2>";
					$body   .= mysqli_connect_error();
					$body   .= "<h2>Query History</h2>";
									
					foreach($this->query_history as $k => $v) {
						$body .= $k." = ".$v."<br>";
					}
					
					Utils::alert_admin($subject, $body);
					
					# Show a nice cryptic error
				    die("<h2>There's been an error processing your request (#DB49)</h2>");
			
				} else {				
			 		die("SQL Error: ".mysqli_connect_error());
				}
		} 
	
		# Use utf8 character encoding
		$this->connection->set_charset("utf8");

	}


	/*-------------------------------------------------------------------------------------------------
	singleton pattern:
	DB::instance(DB_NAME)->query('...');
	-------------------------------------------------------------------------------------------------*/
	public static function instance($db = NULL) {

		# Use existing instance
		if (! isset(self::$instance)) {

			# Create a new instance
			self::$instance = new DB($db);
		}

		# Select database
		self::$instance->select_db($db);
		
		# Return instance
		return self::$instance;

	}


	/*-------------------------------------------------------------------------------------------------

	-------------------------------------------------------------------------------------------------*/
	public function select_db($db = NULL) {
		
		# Start benchmark	
		$this->benchmark_start = microtime(TRUE);
	
		# Only select database if it hasn't already or a new database was specified
		if ($this->database === NULL || $db != $this->database || self::$always_select === TRUE) {
			
			# Store specified database
			$this->database = $db;

			# Select database
			$this->connection->select_db($this->database);
			
		}

	}


	/*-------------------------------------------------------------------------------------------------
	Perform a query with connected database
	This method is the go-to method for all the other methods in this class,
	-------------------------------------------------------------------------------------------------*/
	public function query($sql) {

		# If debugging, just return the query (if you want to see what the query looks like before executing it)
		# TODO: this should return an EXPLAIN of the query which gives us the benchmark as well
		if (self::$debug)
			return $sql;

		# Store query history
		$this->query_history[] = $sql;
			
		# Send query
		$result = $this->connection->query($sql); 
		
		# Store query benchmark
		$this->query_benchmarks[] = number_format(microtime(TRUE) - $this->benchmark_start, 4);
		
		# Handle MySQL errors
		if (!$result) {

			# Don't show error and sql query in production, email it to app owner instead
			if (IN_PRODUCTION) {

				# Email app owner
				$subject = "SQL Error";
				$body    = "<h2>SQL Error</h2> ".$sql." ".$this->connection->error;
				$body   .= "<h2>Query History</h2>";
				foreach($this->query_history as $k => $v) {
					$body .= $k." = ".$v."<br>";
				}
				
				$body  .= "<h2>SERVER</h2>";
				$body  .= "<pre>".print_r($_SERVER, true)."</pre>";
				
				Utils::alert_admin($subject, $body);
				
				# Show a nice cryptic error
			    die("<h2>There's been an error processing your request (#DB138)</h2>");
		
			} else {
		 		die("ERROR: ".$this->connection->error."<br>SQL: ".$sql);
			}
		}		
		
		# return sucessful result
		return $result;

	}


	/*-------------------------------------------------------------------------------------------------
	Dump the last query
	-------------------------------------------------------------------------------------------------*/
	public function last_query($dump = TRUE) {
		
		# last query
		$last_query = end($this->query_history);

		# last query benchmarks
		$last_query_benchmark = end($this->query_benchmarks);

		# toggle dumping output or just returning query string
		return ($dump) ? Debug::dump("($last_query_benchmark sec) ".$last_query, "Last MySQL Query") : $last_query;

	}
	

	/*-------------------------------------------------------------------------------------------------
	Show entire query history w/benchmarks
	-------------------------------------------------------------------------------------------------*/
	public function query_history($dump = TRUE) {

		$history = array();
		
		# store total execution time
		$total_execution = 0;
		
		# build array with benchmarks
		foreach ($this->query_history as $i => $query) {

			if (isset($this->query_benchmarks[$i])) {
				$query = '('.$this->query_benchmarks[$i].' sec) '.$query;
				$total_execution += $this->query_benchmarks[$i];
			}
				
			$history[] = $query;
				
		}
	
		# Add total query execution time to end
		$history[] = "MySQL Total Execution: $total_execution sec";		
				
		# Toggle dumping output or just returning query history array
		return ($dump) ? Debug::dump($history, "MySQL Query History", FALSE) : $history;
	
	}


	/*-------------------------------------------------------------------------------------------------
	When you just want to get one single value from the database
	Does *not* sanitize
	Returns the value (no array)
	
	Ex:
	$user_id = DB::instance(DB_NAME)->select_field("SELECT user_id FROM users WHERE id = 55");
	-------------------------------------------------------------------------------------------------*/
	public function select_field($sql) {

		$result = $this->query($sql);
		$row 	= $result->fetch_row();
		$field  = $row[0];
		return $field;

	}


	/*-------------------------------------------------------------------------------------------------
	Select a single row from the database
	Optional $type can be 'assoc', 'array' or 'object'
	Does *not* sanitize
	Returns an array
	
	Ex:
	$user_details = DB::instance(DB_NAME)->select_row("SELECT * FROM users WHERE id = 55");
	-------------------------------------------------------------------------------------------------*/	
	public function select_row($sql, $type = 'assoc') {

		$result = $this->query($sql);
		$mysqli_fetch = 'mysqli_fetch_'.$type;
		return $mysqli_fetch($result);

	}
	
	
	/*-------------------------------------------------------------------------------------------------
	Returns all the rows in an array
	Does *not* sanitize
	Optional $type can be 'assoc', 'array' or 'object'
	-------------------------------------------------------------------------------------------------*/
	public function select_rows($sql, $type = 'assoc') {

		$rows = array();
		$mysqli_fetch = 'mysqli_fetch_'.$type;

		$result = $this->query($sql);

		while($row = $mysqli_fetch($result)) {
			$rows[] = $row;
		}

		return $rows;

	}
	
	
	/*-------------------------------------------------------------------------------------------------
	Alias to select_row for objects
	Does *not* sanitize
	-------------------------------------------------------------------------------------------------*/
	public function select_object($sql) {
		
		return $this->select_row($sql, 'object');
		
	}
		
		
	/*-------------------------------------------------------------------------------------------------
	Return a key->value array given two columns
	Does *not* sanitize
	Ex:
	$users = DB::instance(DB_NAME)->select_kv("SELECT user_id, first_name FROM users", 'user_id', 'name');
	-------------------------------------------------------------------------------------------------*/
	public function select_kv($sql, $key_column, $value_column) {
				
		$array = array();
		
		foreach ($this->select_rows($sql) as $row) {
			
			# avoid empty keys, but 0 is okay
			if ($row[$key_column] !== NULL && $row[$key_column] !== "")
				$array[$row[$key_column]] = $row[$value_column];
		}
		
		return $array;
		
	}
	
	
	/*-------------------------------------------------------------------------------------------------
	Takes select_rows one step further by making the index of the results array some specified field
	For example, if you wanted a full array of users where the index was the user_id, you could use this.
	Key column must be unique, otherwise data will overwrite itself in the array.
	Does *not* sanitize
	
	Ex: 
	$users = DB::instance(DB_NAME)->select_array('SELECT * FROM users', 'user_id');
	-------------------------------------------------------------------------------------------------*/
	public function select_array($sql, $key_column) {
	
		$array = array();
		
		foreach ($this->select_rows($sql) as $row) {
			
			# avoid empty keys, but 0 is okay
			if ($row[$key_column] !== NULL && $row[$key_column] !== "")
				$array[$row[$key_column]] = $row;
		}
		
		return $array;
	
	}


	/*-------------------------------------------------------------------------------------------------
	Insert a row given an array of key => values
	Returns the id of the row that was inserted
	Does sanitize
	
	Ex:
	$data    = Array("first_name" => "Joe", "last_name" => "Smith");
	$user_id = DB::instance(DB_NAME)->insert("users", $data);
	-------------------------------------------------------------------------------------------------*/
	# Alias 
	public function insert($table, $data) { return self::insert_row($table, $data); }
	public function insert_row($table, $data) {
						
		# setup insert statement
		$sql = "INSERT INTO $table SET";

		# add columns and values
		foreach ($data as $column => $value)
			$sql .= " $column = '".$this->connection->real_escape_string($value)."',";

		# remove trailing comma
		$sql = substr($sql, 0, -1);

		# perform query
		$this->query($sql);

		# return auto_increment id
		return $this->connection->insert_id;

	}
	
	
	/*-------------------------------------------------------------------------------------------------
	Accepts multi-dimensional $data array of rows
	Returns number of rows affected
	Does sanitize
	
	Ex:
	$data[] = Array("first_name" => "John", "last_name" => "Smith");
	$data[] = Array("first_name" => "Jane", "last_name" => "Doe");
		
	$results = DB::insert(DB_NAME)->insert_rows("users", $data);
	-------------------------------------------------------------------------------------------------*/
	public function insert_rows($table, $data) {
	
		# Fields
			$fields = "";
			foreach($data[0] as $field => $row) {
				$fields .= $field.",";
			}
			
			$fields = substr($fields, 0, -1);
							
		# Rows
			$row_string = "";
			$rows_string = "";
			foreach($data as $row) {				
				$row_string = "(";
				foreach($row as $field => $value) {
					$row_string .= "'".$this->connection->real_escape_string($value)."',";
				}	
				$row_string   = substr($row_string, 0, -1);
				$row_string  .= "),";
				$rows_string .= $row_string;
			}
			
			$rows_string = substr($rows_string, 0, -1);
			
		# Query
			$q = "INSERT INTO ".$table."
				  (".$fields.")
				VALUES
				  ".$rows_string;
				  				
		# Run it
			$run = $this->query($q);
			return $this->connection->affected_rows;
				 
	}


	/*-------------------------------------------------------------------------------------------------
	Update a single row given an array of key => values
	example $where_condition: "WHERE id = 1 LIMIT 1"
	Does sanitize
	
	Ex:
	$data = Array("first_name" => "John");
	DB::instance(DB_NAME)->update("users", $data, "WHERE user_id = 56");
	-------------------------------------------------------------------------------------------------*/
	# Alias
	public function update($table, $data, $where_condition) { return self::update_row($table, $data, $where_condition); }
	public function update_row($table, $data, $where_condition) {
	
		# setup update statement
		$sql = "UPDATE $table SET";

		# add columns and values
		foreach ($data as $column => $value) {
			# allow setting columns to NULL
			if ($value === NULL) {
				$sql .= " $column = NULL,";
			} else {
				$sql .= " $column = '".$this->connection->real_escape_string($value)."',";
			}
		}

		# remove trailing comma
		$sql = substr($sql, 0, -1);

		# Add condition
		$sql .= " ".$where_condition;

		# perform query
		$this->query($sql);
		
		return $this->connection->affected_rows;
		
	}	


	/*-------------------------------------------------------------------------------------------------
	If the primary key exists update row, otherwise insert row
	Requires primary id be first part of the data array - that's what it uses to check for duplicate
	Returns the created id
	Does sanitize
	
	Ex:
	$data    = Array("user_id" => 50", "first_name" => "Joe", "last_name" => "Smith");
	$user_id = DB::instance(DB_NAME)->update_or_insert_row("users", $data);
	-------------------------------------------------------------------------------------------------*/
	public function update_or_insert_row($table, $data) {
	
		# Build fields and values
			$fields = "";
			$values = "";
			$dup    = "";
			
			foreach($data as $field => $value) {
				$fields .= $field.",";
				$values .= "'".$this->connection->real_escape_string($value)."',";
				$dup    .= $field."="."'".$this->connection->real_escape_string($value)."',";
			}
			
			$fields = substr($fields, 0, -1);
			$values = substr($values, 0, -1);
			$dup    = substr($dup, 0, -1);
												
		# Query
			$q = "INSERT INTO ".$table."
				  (".$fields.")
				VALUES
				  (".$values.")
				 ON DUPLICATE KEY UPDATE ".$dup; 
				  ;
				  			
			$this->query($q);
		
		return $this->connection->insert_id;
	}


	/*-------------------------------------------------------------------------------------------------
	Just like above method, but for multiple rows
	If the primary key exists update, otherwise insert
	
	Requires primary id be first part of the data array - that's what it uses to check for duplicate
	Requires all fields to be present, otherwise a missing field will get set to blank
	Does sanitize
	
	Example SQL string result:
	
		INSERT INTO tasks (person_id,first_name,email) 
		VALUES (1,'Ethel','ethel@aol.com'),(3,'Leroy','leroy@hotmail.com'),(3,'Francis','francis@gmail.com')
		ON DUPLICATE KEY UPDATE first_name=VALUES(first_name),email=VALUES(email)'
	
	Ex:
		$data[] = Array("person_id" => 1, "first_name" => 'Ethel', "email" => 'ethel@aol.com');
		$data[] = Array("person_id" => 2, "first_name" => 'Leroy', "email" => 'leroy@hotmail.com');
		$data[] = Array("person_id" => 3, "first_name" => 'Francis', "email" => 'francis@gmail.com.com');	
		$update = DB::instance("courses_webstartwomen_com")->update_or_insert_rows('people', $data);						
	-------------------------------------------------------------------------------------------------*/
	public function update_or_insert_rows($table, $data) {
	
		# Build the fields string. Ex: (person_id,first_name,email)
		# And the duplicate key update string. Ex: first_name=VALUES(first_name),email=VALUES(email)
		# We do this by using the indexes on the first row of data
		# NOTE: The index of the data array has to start at 0 in order for this to work
			$fields = ""; 
			$dup    = "";
			foreach($data[0] as $index => $value) {
				$fields .= $index.",";
				$dup    .= $index."=VALUES(".$index."),";
			}
			
			# Remove last comma
			$fields = substr($fields, 0, -1);
			$dup = substr($dup, 0, -1);
				
		# Build the data string. Ex: (1,'Ethel','ethel@aol.com'),(3,'Leroy','leroy@hotmail.com'),(3,'Francis','francis@gmail.com')
			$values = "";
			foreach($data as $row) {
				
				$values .= "(";
				foreach($row as $value) {
					$values .= "'".$this->connection->real_escape_string($value)."',";
				}
				$values = substr($values, 0, -1);
				$values .= "),";
			}
			# Remove last comma
			$values = substr($values, 0, -1);
					
		# Put it all together	
			$sql = "INSERT INTO ".$table." (".$fields.") 
					VALUES ".$values."
					ON DUPLICATE KEY UPDATE ".$dup;
		
		# Run it
			$run = $this->query($sql);
			return $this->connection->affected_rows;	
	}
		

	/*-------------------------------------------------------------------------------------------------
	Ex:
	DB::instance(DB_NAME)->delete('users', "WHERE email = 'max@gmail.com'");
	Does *not* sanitize
	
	Returns 1 if it found something to delete
	-------------------------------------------------------------------------------------------------*/
	public function delete($table, $where_condition) {

		$sql = 'DELETE FROM '.$table.' '.$where_condition; 

		return $this->query($sql);

	}
	
	
	/*-------------------------------------------------------------------------------------------------
	Accepts an array or string of data
	Returns escaped data
	
	Ex:
	$_POST = DB::instance(DB_NAME)->sanitize($_POST);
	-------------------------------------------------------------------------------------------------*/
	public function sanitize($data) {
	
		if(is_array($data)){
		
			foreach($data as $k => $v){
				if(is_array($v)){
					$data[$k] = self::sanitize($v);
				} else {
					$data[$k] = $this->connection->real_escape_string($v);
				}
			}
			
		} else {
			$data = $this->connection->real_escape_string($data);
		}

		return $data;
	
	}
	
	
}
