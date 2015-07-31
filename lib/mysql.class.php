<?php
class Lib_Mysql {
	private $rowCount = 0;
	private $lastInsertId = false;

	private $lastError = null;
	private $queryBatch = array();
	private $queryBatchId = false;

	private $debug = false;

	public function __construct($dsConfig) {
		try {
			if(!is_resource($this->_link = mysql_connect($dsConfig->host . ':' . $dsConfig->port, $dsConfig->user, $dsConfig->pass))) {
				Log::write(mysql_error($this->_link));
				throw new Exception('OFFLINE');
			}
		}
		catch (Exception $e) {
			if($e->getMessage() == 'OFFLINE') {
				header('Location: http://offline.' . app_domain);
			}
			die($e->getMessage());
		}

		mysql_select_db(trim($dsConfig->path,'/'), $this->_link);
		mysql_query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8',
							character_set_database = 'utf8', character_set_collation = 'utf8', character_set_server = 'utf8'", $this->_link);
		return $this;
	}

	public function query($sql) {
		$this->rowCount = 0;
		$resultResoc = mysql_query($sql);
		if ($resultResoc === false) {
			exit();
		}
		else {
			$this->setRowCount($resultResoc);
		}

		$this->_last_result = $resultResoc;
		return $resultResoc;
	}

	public function fetchArray($result) {
		if (is_resource($result)) {
			return mysql_fetch_array($result, MYSQL_ASSOC);
		}
		else {
			return array();
		}
	}

	public function __destruct() {
		if(is_resource($this->_link)) {
			return mysql_close($this->_link);
		}
		else {
			Log::write(__METHOD__.': no live mysql connections found');
		}
	}

	public function __get($propertyName) {
		if($propertyName == 'rowCount') { return $this->rowCount; }
		if($propertyName == 'lastInsertId') { return $this->lastInsertId; }
	}

	private function setRowCount($result) {
		if(is_resource($result)) {
			$this->rowCount = mysql_num_rows($result);
		}
		else if ($result === true) {
			$this->rowCount = mysql_affected_rows($this->_link);
		}
	}

	public function startTransaction() {
		$this->query('START TRANSACTION;');
	}

	public function rollbackTransaction() {
		$this->query('ROLLBACK;');
	}

	public function commitTransaction() {
		$this->query('COMMIT;');
	}

	public function prepare($sql, $statementName = 'stmt') {
		$this->lastError = null;
		$this->queryBatchId = trim($statementName);
		$this->queryBatch[$this->queryBatchId] = array();
		array_push($this->queryBatch[$this->queryBatchId],'SET @sql_' . $this->queryBatchId . ' := "' . trim($sql, ';"') . '";');
		array_push($this->queryBatch[$this->queryBatchId],'PREPARE ' . $this->queryBatchId . ' FROM @sql_' . $this->queryBatchId . ';');
		return $this;
	}

	public function bindData($data) {
		foreach ($data as $variableName => $variableValue) {
			if ((is_object($variableValue)) || (is_array($variableValue))) {
				foreach ($variableValue as $key => $value) {
					$this->realBind($key,$value);
				}
			}
			else {
				$this->realBind($variableName,$variableValue);
			}
		}

		return $this;
	}

	private function realBind($fieldMappingName, $rawValue) {
		if(is_string($rawValue)) {
			 array_push($this->queryBatch[$this->queryBatchId],'SET @' . $fieldMappingName . ' = "' . mysql_real_escape_string($rawValue) . '";');
		}
		else if(is_bool($rawValue)) {
			 array_push($this->queryBatch[$this->queryBatchId],'SET @' . $fieldMappingName . ' = ' . (int)$rawValue . ';');
		}
		else if((is_int($rawValue)) || (is_float($rawValue))) {
			array_push($this->queryBatch[$this->queryBatchId],'SET @' . $fieldMappingName . ' = ' . $rawValue . ';');
		}
		return true;
	}

	public function execute($statementName='stmt') {
		Log::write(__METHOD__ . ' this is a deprecated method use `exec()` instead');
		array_push($this->queryBatch[$this->queryBatchId],'EXECUTE ' . trim($statementName) . ';');
		return $this->queryExecutor($statementName);
	}

    public function exec($object=null,$statementName='stmt') {
        array_push($this->queryBatch[$this->queryBatchId],'EXECUTE ' . trim($statementName) . ';');
        return new Lib_MysqlResult($this->queryExecutor($statementName),$object);
        //return $this->queryExecutor($statementName);
    }

	private function queryExecutor($statementName) {
		if((bool)count($this->queryBatch[$statementName])) {
			$i = 0;
			foreach ($this->queryBatch[$statementName] as $queryOrderId => $query) {
				$i++;
				if($this->debug) { Log::write($query); }
				$result = mysql_query($query, $this->_link);
				if($result === false) {
					throw new Exception(mysql_error($this->_link), mysql_errno($this->_link));
				}
			}

			if(is_resource($result)) {
				$this->rowCount = mysql_num_rows($result);

				if($this->debug) { Log::write($this->rowCount . ' rows found'); }
			}
			else if($result === true) {
				$this->lastInsertId = mysql_insert_id($this->_link);

				if($this->lastInsertId) {
					/* if($this->debug) {
						Log::write('last inserted row id is - ' . $this->lastInsertId);
					}  */
				}

				$this->rowCount = mysql_affected_rows($this->_link);

				if($this->debug) { Log::write($this->rowCount . ' rows affected'); }
			}
			else if($result === false) {
				if((bool)strlen($this->lastError = mysql_error($this->_link))) {
					if($this->debug) { Log::write(__CLASS__ . ' ' . $this->lastError); }
				}
			}

			return (object)array(
				'resoc'=>$result,
	        	'count'=>(int)$this->rowCount,
	        	'error'=>(string)$this->lastError,
	        	'last'=>(int)$this->lastInsertId,
				'link'=>$this->_link
        	);
		}
	}
}
?>