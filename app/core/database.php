<?php
class db {
	private $dbh; // Database handle
	private $funcCall; // Last method call
	private $lastResult;
	private $colInfo;
	private $lastSQL; // Last SQL ran
	private $result;
	private $connected;
	private $selectedDatabase;
	
	public $fetchMode = "assoc";
	
	public function __construct($dbuser, $dbpassword, $dbname, $dbhost) {
		$this->dbh = @mysql_connect($dbhost,$dbuser,$dbpassword);
		
		if(!$this->dbh) {
			$this->_printError(
				"<ol>\n"
					."<b>Error establishing a database connection!</b>\n"
					."<li>Are you sure you have the correct user/password?</li>\n"
					."<li>Are you sure that you have typed the correct hostname?</li>\n"
					."<li>Are you sure that the database server is running?</li>\n"
				."</ol>"
			);
		} else {
			$this->connected = 1;
			$this->changeDatabase($dbname);
		}
	}
	
	// ==================================================================
	//Select a DB (if another one needs to be selected)
	public function changeDatabase($sDatabase) {
		if(!@mysql_select_db($sDatabase, $this->dbh)) {
			$this->_printError(
				"<ol>\n"
					."<b>Error selecting database <u>".$sDatabase."</u>!</b>\n"
					."<li>Are you sure it exists?</li>\n"
					."<li>Are you sure there is a valid database connection?</li>\n"
				."</ol>"
			);
		} else {
			$this->selectedDatabase = $sDatabase;
		}
	}
	
	public function isConnected() {
		if($this->connected == 1 && !empty($this->selectedDatabase)) {
			return true;
		} else {
			return false;
		}
	}
	
	// ==================================================================
	//Basic Query	- see docs for more detail
	public function query($query) {
		if($this->isConnected()) {
			// Log how the function was called
			$this->funcCall = "\$db->query(\"$query\")";		
		
			// Kill this
			$this->lastResult = null;
			$this->colInfo = null;
		
			// Keep track of the last query for debug..
			$this->lastSQL = $query;
		
			// Perform the query via std mysql_query function..
			$this->result = mysql_query($query,$this->dbh);
		
			if(mysql_error()) {
				// If there is an error then take note of it..
				$this->_printError();
			} else {
				// In other words if this was a select statement..
				if($this->result) {
					// =======================================================
					// Take note of column info
					$i=0;
					while ($i < @mysql_num_fields($this->result)) {
						$this->colInfo[$i] = @mysql_fetch_field($this->result);
						$i++;
					}
				
					// =======================================================				
					// Store Query Results
					$i=0;
					while($row = @mysql_fetch_object($this->result)) { 
						// Store relults as an objects within main array
						$this->lastResult[$i] = $row;
					
						$i++;
					}
				
					@mysql_free_result($this->result);
				
					// If there were results then return true for $db->query
					if($i) {
						return true;
					} else {
						return false;
					}
				}
			}
		} else {
			return false;
		}
	}
	
	// ==================================================================
	//Get one variable from the DB - see docs for more detail
	public function getOne($query=null,$x=0,$y=0) {
		// Log how the function was called
		$this->funcCall = "\$db->getOne(\"$query\",$x,$y)";
		
		if($this->isConnected()) {
			// If there is a query then perform it if not then use cached results..
			if($query) {
				$this->query($query);
			}
		
			if(!is_numeric($y)) {
				$y = 0;
			}
		
			if(!is_numeric($x)) {
				$x = 0;
			}
		
			// Extract var out of cached results based x,y vals
			if($this->lastResult[$y]) {
				$values = array_values(get_object_vars($this->lastResult[$y]));
			}
		
			// If there is a value return it else return null
			return $values[$x]?$values[$x]:null;
		} else {
			return false;
		}
	}
	
	// ==================================================================
	//Get one row from the DB - see docs for more detail
	public function getRow($query = null, $y = 0, $sFetchMode = null) {	
		// Log how the function was called
		$this->funcCall = "\$db->get_row(\"$query\",$y,$fetchMode)";
		
		if($this->isConnected()) {
			// If there is a query then perform it if not then use cached results..
			if($query) {
				$this->query($query);
			}
		
			if(!is_numeric($y)) {
				$y = 0;
			}
		
			if(empty($sFetchMode)) {
				$sFetchMode = $this->fetchMode;
			}
		
			switch($sFetchMode) {
				case "object":
					// If the fetchMode is an object then return object using the row offset..
					return $this->lastResult[$y]?$this->lastResult[$y]:null;
					break;
				case "assoc":
					// If the fetchMode is an associative array then return row as such..
					return $this->lastResult[$y]?get_object_vars($this->lastResult[$y]):null;
					break;
				default: //ordered
					// If the fetchMode is an numerical array then return row as such..
					return $this->lastResult[$y]?array_values(get_object_vars($this->lastResult[$y])):null;
			}
		} else {
			return false;
		}
	}
	
	// ==================================================================
	//Function to get 1 column from the cached result set based in X index
	// se docs for usage and info
	public function getCol($query=null,$x=0) {
		if($this->isConnected()) {
			// If there is a query then perform it if not then use cached results..
			if($query) {
				$this->query($query);
			}
		
			// Extract the column values
			for($i=0; $i < count($this->lastResult); $i++) {
				$new_array[$i] = $this->getOne(null,$x,$i);
			}
		
			return $new_array;
		} else {
			return false;
		}
	}
	
	// ==================================================================
	// Return the the query as a result set - see docs for more details
	public function getAll($query = null, $fetchMode = null) {
		// Log how the function was called
		$this->funcCall = "\$db->get_results(\"$query\", $fetchMode)";
		
		// If there is a query then perform it if not then use cached results..
		if ($query) {
			$this->query($query);
		}
		
		if(empty($fetchMode)) {
			$fetchMode = $this->fetchMode;
		}
		
		// Send back array of objects. Each row is an object		
		if($fetchMode == "object") {
			return $this->lastResult; 
		} elseif($fetchMode == "assoc" || $fetchMode == "ordered") {
			if($this->lastResult) {
				$i=0;
				foreach($this->lastResult as $row) {
					$new_array[$i] = get_object_vars($row);
					
					if ($fetchMode == "ordered") {
						$new_array[$i] = array_values($new_array[$i]);
					}
					
					$i++;
				}

				return $new_array;
			} else {
				return null;	
			}
		}
	}
	
	// ==================================================================
	// Retrieve last AUTO_INCREMENT id created by this connection
	public function lastInsertID() {
        // not using mysql_insert_id() due to http://pear.php.net/bugs/bug.php?id=8051
        return $this->getOne('SELECT LAST_INSERT_ID()');
    }
	
	// ==================================================================
	// Retrieve number of rows affected by last query
	public function affectedRows() {
        return mysql_affected_rows($this->dbh);
    }
	
	// ==================================================================
	// Removes all stored info of previous query
	public function free() {
		unset($this->lastResult);
		unset($this->colInfo);
		unset($this->lastSQL);
		unset($this->result);
	}
	
	// Close connection to database
	public function disconnect() {
		$this->free();
		@mysql_close($this->dbh);
	}

	// ==================================================================
	// Dumps the contents of any input variable to screen in a nicely
	// formatted and easy to understand way - any type: Object, Var or Array
	public function vardump($mixed) {
		echo "<blockquote><span style=\"font-family: Arial;font-size:10px;color:#666;\"><pre>";
		print_r($mixed);	
		echo "\n\n<b>Last Query:</b> ".($this->lastSQL?$this->lastSQL:"NULL")."\n";
		echo "<b>Last Function Call:</b> " . ($this->funcCall?$this->funcCall:"None")."\n";
		echo "<b>Last Rows Returned:</b> ".count($this->lastResult)."\n";
		echo "</pre></span></blockquote>";
		echo "\n<hr size=1 noshade color=dddddd>";
	}
	
	// ==================================================================
	// Displays the last query string that was sent to the database & a 
	// table listing results (if there were any). 
	// (abstracted into a seperate file to save server overhead).
	public function debug() {
		echo "<div style=\"font-family: Arial;font-size: 14px;color: #000000;\">\n";
		echo "\t<p>\n";
		echo "\t\t<span style=\"font-weight: bold;\">Query</span> - [".$this->lastSQL."]\n";
		echo "\t</p>\n";
		echo "\t<p>\n";
		echo "\t\t<span style=\"font-weight: bold;\">Query Result..</span>\n";
				
		if($this->colInfo) {
			// =====================================================
			// Results top rows
	
			echo "\t\t<table cellpadding=\"5\" cellspacing=\"1\" bgcolor=\"555555\">";
			echo "\t\t\t<tr bgcolor=\"eeeeee\">";
			echo "\t\t\t\t<td nowrap valign=\"bottom\">\n";
			echo "\t\t\t\t\t<span style=\"color: #555599;font-weight: bold;\">(row)</span>\n";
			echo "\t\t\t\t</td>\n";
	
			for($i=0; $i < count($this->colInfo); $i++) {
				echo "\t\t\t\t<td nowrap align=\"left\" valign=\"top\">\n";
				echo "\t\t\t\t\t<span style=\"font-size: 10px;color: #555599;\">".$this->colInfo[$i]->type." ".$this->colInfo[$i]->max_length."</span><br>\n";
				echo "\t\t\t\t\t<span style=\"font-weight: bold;\">".$this->colInfo[$i]->name."</span>\n";
				echo "\t\t\t\t</td>\n";
			}

			echo "\t\t\t</tr>\n";

			// ======================================================
			// print main results
	
			if($this->lastResult) {
				$i=0;
				$aResults = $this->getAll(null, "ordered");
				foreach($aResults as $one_row) {
					$i++;
					echo "\t\t\t<tr bgcolor=\"ffffff\">\n";
					echo "\t\t\t\t<td bgcolor=\"eeeeee\" nowrap align=\"middle\">\n";
					echo "\t\t\t\t\t<font style=\"color: #555599\">".$i."</span>\n";
					echo "\t\t\t\t</td>\n";
			
					foreach($one_row as $item) {
						echo "\t\t\t\t<td nowrap>\n";
						echo "\t\t\t\t\t".$item."\n";
						echo "\t\t\t\t</td>\n";	
					}
			
					echo "\t\t\t</tr>\n";				
				}
			} else {
				// if last result
				echo "\t\t\t<tr bgcolor=\"ffffff\">\n";
				echo "\t\t\t\t<td colspan=\"".(count($this->colInfo)+1)."\">\n";
				echo "\t\t\t\t\tNo Results\n";
				echo "\t\t\t\t</td>\n";
				echo "\t\t\t</tr>\n";			
			}
	
			echo "\t\t</table>\n";		
		} else {
			// if colInfo
			echo "\t\tNo Results\n";			
		}
		echo "\t</p>\n";
		echo "</div>\n";
	}
	
	// ==================================================================
	//Print SQL/DB error.
	private function _printError($sError = "") {
		if(empty($sError)) {
			$sError = mysql_error();
		}
		
		// If there is an error then take note of it
		print "<div style=\"font-family: Arial;font-size: 14px;color: #000000\">\n";
		print "\t<span style=\"color: #ff0000\">SQL/DB Error</span>\n<br>";
		print "\t".$sError."\n";
		print "</div>";	
	}
	
	// ==================================================================
	// Function to get column meta data info pertaining to the last query
	// see docs for more info and usage
	private function _getColInfo($info_type = "name", $col_offset = -1) {
		if($this->colInfo) {
			if($col_offset == -1) {
				$i=0;
				foreach($this->colInfo as $col) {
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			} else {
				return $this->colInfo[$col_offset]->{$info_type};
			}
		}
	}
}