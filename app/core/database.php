<?php
class Database extends GW {
	private $dbh; // Database handle
	private $funcCall; // Last method call
	private $lastResult;
	private $colInfo;
	private $lastSQL; // Last SQL ran
	private $result;
	private $connected;
	private $selectedDatabase;
	
	public $fetchMode = 'assoc';
	
	public function __construct($dbuser = '', $dbpassword = '', $dbname = '', $dbhost = '') {
		if(!empty($dbuser) && !empty($dbname) && !empty($dbhost)) {
			$this->connect($dbuser, $dbpassword, $dbname, $dbhost, debug_backtrace());
		}
	}
	
	public function connect($dbuser, $dbpassword, $dbname, $dbhost, $aBacktrace = array()) {
		$this->dbh = @mysql_connect($dbhost,$dbuser,$dbpassword);
		
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		if(!$this->dbh) {
			$this->_printError('Error establishing a database connection! '.mysql_error(), $aBacktrace);
		} else {
			$this->connected = 1;
			$this->changeDatabase($dbname, $aBacktrace);
		}
	}
	
	// ==================================================================
	//Select a DB (if another one needs to be selected)
	public function changeDatabase($sDatabase, $aBacktrace = array()) {
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		if(!@mysql_select_db($sDatabase, $this->dbh)) {
			$this->_printError('Error selecting database <b>'.$sDatabase.'</b>', $aBacktrace);
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
	public function query($query, $aBacktrace = array()) {
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		// Log how the function was called
		$this->funcCall = '$db->query("'.$query.'")';
		
		if($this->isConnected()) {
			// Kill this
			$this->lastResult = null;
			$this->colInfo = null;
			
			// Keep track of the last query for debug..
			$this->lastSQL = $query;
			
			// Perform the query via std mysql_query function..
			$this->result = mysql_query($query,$this->dbh);
			
			if(mysql_error()) {
				// If there is an error then take note of it..
				$this->_printError(null, $aBacktrace);
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
			$this->_printError('Database connection was not found to execute query.', $aBacktrace);
		}
	}
	
	// ==================================================================
	//Creates a collection of all the get functions
	public function get($sQuery = null, $sType = 'all') {
		$sType = strtolower($sType);
		
		$this->funcCall = '$db->get("'.$sQuery.'", "'.$sType.'")';
		
		if($this->isConnected()) {
			switch($sType) {
				case 'one':
					return $this->getOne($sQuery, 0, 0, debug_backtrace());
					break;
				case 'row':
					return $this->getRow($sQuery, 0, null, debug_backtrace());
					break;
				case 'col':
					return $this->getCol($sQuery, 0, debug_backtrace());
					break;
				case 'all':
					return $this->getAll($sQuery, null, debug_backtrace());
					break;
			}
		} else {
			$this->_printError('Database connection was not found to execute query', debug_backtrace());
		}
	}
	
	// ==================================================================
	//Get one variable from the DB - see docs for more detail
	public function getOne($query = null, $x = 0, $y = 0, $aBacktrace = array()) {
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		// Log how the function was called
		$this->funcCall = '$db->getOne("'.$query.'", '.$x.', '.$y.')';
		
		if($this->isConnected()) {
			// If there is a query then perform it if not then use cached results..
			if($query) {
				$this->query($query, $aBacktrace);
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
			$this->_printError('Database connection was not found to execute query', debug_backtrace());
		}
	}
	
	// ==================================================================
	//Get one row from the DB - see docs for more detail
	public function getRow($query = null, $y = 0, $sFetchMode = null, $aBacktrace = array()) {
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		// Log how the function was called
		$this->funcCall = '$db->getCol("'.$query.'", '.$y.', '.$sFetchMode.')';
		
		if($this->isConnected()) {
			// If there is a query then perform it if not then use cached results..
			if($query) {
				$this->query($query, $aBacktrace);
			}
			
			if(!is_numeric($y)) {
				$y = 0;
			}
			
			if(empty($sFetchMode)) {
				$sFetchMode = $this->fetchMode;
			}
			
			switch($sFetchMode) {
				case 'object':
					// If the fetchMode is an object then return object using the row offset..
					return $this->lastResult[$y]?$this->lastResult[$y]:null;
					break;
				case 'assoc':
					// If the fetchMode is an associative array then return row as such..
					return $this->lastResult[$y]?get_object_vars($this->lastResult[$y]):null;
					break;
				default: //ordered
					// If the fetchMode is an numerical array then return row as such..
					return $this->lastResult[$y]?array_values(get_object_vars($this->lastResult[$y])):null;
			}
		} else {
			$this->_printError('Database connection was not found to execute query', $aBacktrace);
		}
	}
	
	// ==================================================================
	//Function to get 1 column from the cached result set based in X index
	// se docs for usage and info
	public function getCol($query = null, $x = 0, $aBacktrace = array()) {
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		$this->funcCall = '$db->getCol("'.$query.'", '.$x.')';
		
		if($this->isConnected()) {
			// If there is a query then perform it if not then use cached results..
			if($query) {
				$this->query($query, $aBacktrace);
			}
			
			// Extract the column values
			for($i=0; $i < count($this->lastResult); $i++) {
				$new_array[$i] = $this->getOne(null,$x,$i);
			}
			
			return $new_array;
		} else {
			$this->_printError('Database connection was not found to execute query', $aBacktrace);
		}
	}
	
	// ==================================================================
	// Return the the query as a result set - see docs for more details
	public function getAll($query = null, $fetchMode = null, $aBacktrace = array()) {
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		$this->funcCall = '$db->getAll("'.$query.'", '.$fetchMode.')';
		
		if($this->isConnected()) {
			// If there is a query then perform it if not then use cached results..
			if ($query) {
				$this->query($query, $aBacktrace);
			}
			
			if(empty($fetchMode)) {
				$fetchMode = $this->fetchMode;
			}
			
			// Send back array of objects. Each row is an object		
			if($fetchMode == 'object') {
				return $this->lastResult; 
			} elseif($fetchMode == 'assoc' || $fetchMode == 'ordered') {
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
		} else {
			$this->_printError('Database connection was not found to execute query', $aBacktrace);
		}
	}
	
	// ==================================================================
	// Retrieve last AUTO_INCREMENT id created by this connection
	public function lastInsertID() {
		if($this->isConnected()) {
			// not using mysql_insert_id() due to http://pear.php.net/bugs/bug.php?id=8051
			return $this->getOne('SELECT LAST_INSERT_ID()');
		} else {
			$this->_printError('Database connection was not found to execute query', debug_backtrace());
		}
	}
	
	// ==================================================================
	// Retrieve number of rows affected by last query
	public function affectedRows() {
		if($this->isConnected()) {
			return mysql_affected_rows($this->dbh);
		} else {
			$this->_printError('Database connection was not found to execute query', debug_backtrace());
		}
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
		$this->connected = 0;
		@mysql_close($this->dbh);
	}
	
	// ==================================================================
	// Displays the last query string that was sent to the database & a 
	// table listing results (if there were any). 
	// (abstracted into a seperate file to save server overhead).
	public function debug() {
		echo '<span style="font-family: Arial;font-size:14px;color:#666;">';
		echo '<b>Last Query:</b> '.($this->lastSQL?$this->lastSQL:'NULL').'<br>';
		echo '<b>Last Function Call:</b> '.($this->funcCall?$this->funcCall:"None").'<br>';
		echo '<b>Last Rows Returned:</b> '.count($this->lastResult).'<br>';
		echo '</span>';
		echo '<hr size="1" noshade color="dddddd">';
	}
	
	// ==================================================================
	//Print SQL/DB error.
	private function _printError($sError = "", $aBacktrace = array()) {
		if(empty($sError)) {
			$sError = mysql_error();
		}
		
		if(empty($aBacktrace)) {
			$aBacktrace = debug_backtrace();
		}
		
		$this->error->trigger($sError, 'error', $aBacktrace[0]);
		die;
	}
}