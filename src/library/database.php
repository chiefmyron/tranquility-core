<?php

namespace Tranquility;

class Database extends \PDO {

    protected $_log = null;
    protected $_transactionLevel = 0;
    protected $_fetchMode = 0;

    public function __construct($log, $dsn, $username = null, $password = null, $options = array()) {
        // Assign log object
        $this->_log = $log;
        
        // Set default fetch mode
        $this->setFetchMode(Utility::extractValue($options, 'fetchMode', \PDO::FETCH_OBJ));

        // Construct PDO object
        $this->_log->debug('Creating new PDO object [DSN: "'.$dsn.'", Username: "'.$username.'"]');
        return parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * Run a statement against the database (alias of select())
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function query($query, $bindings = array()) {
        return $this->select($query, $bindings);
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = array()) {
        $records = $this->select($query, $bindings);
        if (count($records) > 0) {
            return reset($records);
        }

        // No records selected - return null
        return null;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function select($query, $bindings = array()) {
        return $this->_run($query, $bindings, function($db, $query, $bindings) {
            $stmt = $db->prepare($query);
            $stmt->execute($bindings);

            return $stmt->fetchAll($this->getFetchMode());
        });
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function insert($query, $bindings = array()) {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, $bindings = array()) {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($query, $bindings = array()) {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, $bindings = array()) {
        return $this->_run($query, $bindings, function($db, $query, $bindings) {
            $stmt = $db->prepare($query);
            $result = $stmt->execute($bindings);
            if (!$result) {
                $logMessage = "SQL error occurred: ".implode(" | ", $stmt->errorInfo());
                $this->_log->error($logMessage." (Query:".$query.")", $bindings);
                throw new Exception($logMessage);
            }
            
            return $result;
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = array()) {
        return $this->_run($query, $bindings, function($db, $query, $bindings) {
            $stmt = $db->prepare($query);
            $result = $stmt->execute($bindings);
            if (!$result) {
                $logMessage = "SQL error occurred: ".implode(" | ", $stmt->errorInfo());
                $this->_log->error($logMessage." (Query:".$query.")", $bindings);
                throw new Exception($logMessage);
            }

            return $stmt->rowCount();
        });
    }

    /**
     * Start a new database transaction.
     *
     * @return void
     */
    public function beginTransaction() {
        $this->_transactionLevel++;

        if ($this->_transactionLevel == 1) {
            parent::beginTransaction();
        }
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit() {
        if ($this->_transactionLevel == 1) {
            parent::commit();
        }

        $this->_transactionLevel--;
    }

    /**
     * Rollback the active database transaction.
     *
     * @return void
     */
    public function rollback() {
        if ($this->_transactionLevel == 1) {
            $this->_log->warning('Rollback issued!');
            $this->_transactionLevel = 0;
            parent::rollBack();
        } else {
            $this->_transactionLevel--;
        }
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string   $query
     * @param  array    $bindings
     * @param  Closure  $callback
     * @return mixed
     *
     * @throws QueryException
     */
    protected function _run($query, $bindings, $callback) {
        $start = microtime(true);

        // Use the callback method to actually execute the SQL statement
        try {
            $result = $callback($this, $query, $bindings);
        } catch (\Exception $ex) {
            $this->rollback();
            throw new Exception($ex->getMessage().' Statement: '.$query);
        }

        // Get elapsed time, log query and return result
        $time = $this->_getElapsedTime($start);
        $this->_logQuery($query, $bindings, $time);
        return $result;
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int    $start
     * @return float
     */
    protected function _getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Write query, parameters and total execution time to DB log file
     * 
     * @param string $query
     * @param array  $bindings
     * @param float  $time
     */
    protected function _logQuery($query, $bindings, $time) {
        $logString  = 'Query: '.$query."\n";
        $logString .= 'Parameters: '.print_r($bindings, true)."\n";
        $logString .= 'Time elapsed: '.$time;
        $this->_log->info($logString);
    }
    
    public function setFetchMode($mode) {
        $this->_fetchMode = $mode;
    }
    
    public function getFetchMode() {
        return $this->_fetchMode;
    }
}