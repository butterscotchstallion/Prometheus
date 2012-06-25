<?php
/**
 * Adapter - MySQL database adapter
 *
 */
namespace Prometheus\Adapter\MySQL;

use PDO;

class Adapter
{
    static $lastError;
    private $connection;
    
    /**
     * @param mixed $connection - db access
     *
     */
    function __construct()
    {
        $this->setConnection(new PDO(DB_DSN, DB_USER, DB_PASSWORD));
    }
    
    /**
     * Run an UPDATE, INSERT, or DELETE command
     * and return the number of affected rows
     *
     * @param string $query
     * @param array $params (optional)
     * @return int
     *
     */
    function save($query, $params = array())
    {
        $stmt     = $this->getStatement($query, $params);
        $lquery   = strtolower($query);
        $result   = true;
        
        $isUpdate = strpos($lquery, 'update') === 0;
        $isDelete = strpos($lquery, 'delete') === 0;
        $isInsert = strpos($lquery, 'insert') === 0;
        $isCreate = strpos($lquery, 'create') === 0;
        
        // If this is an update/delete query, return rows affected
        if ($isUpdate || $isDelete || $isCreate) {
            $result = $stmt->rowCount();
        }
        
        // If this is an insert query, return primary key
        if ($isInsert) {
            $result = $this->getConnection()->lastInsertId();
        }
        
        return $result;
    }
    
    /**
     * Fetch a single result
     * @param string $query
     * @param array $params
     *
     */
    function fetch($query, $params = array())
    {
        $stmt = $this->getStatement($query, $params);
        
        return $stmt->fetch($query);
    }
    
    /**
     * Fetch more than one result
     * @param string $query
     * @param array $params
     *
     */
    function fetchAll($query, $params = array())
    {
        $stmt = $this->getStatement($query, $params);
        
        return $stmt->fetchAll($query);
    }
    
    /**
     * Get PDOStatement for use with execute
     * @param string $query
     * @param array $params
     *
     */
    function getStatement($query, $params)
    {
        $stmt = $this->getConnection()->prepare($query);
        
        if ($params) {
            $result = $stmt->execute($params);
        } else {
            $result = $stmt->execute();
        }
        
        /**
         * PDO::errorInfo() only retrieves error information for operations performed 
         * directly on the database handle. If you create a PDOStatement object through 
         * PDO::prepare() or PDO::query() and invoke an error on the statement handle, 
         * PDO::errorInfo() will not reflect the error from the statement handle. You 
         * must call PDOStatement::errorInfo() to return the error information for an 
         * operation performed on a particular statement handle.
         * @see http://www.php.net/manual/en/pdo.errorinfo.php
         *
         */
        self::$lastError = $stmt->errorInfo();
        
        return $stmt;
    }
    
    static function getLastError()
    {
        return self::$lastError ? self::$lastError[2] : '';
    }
    
    function setConnection($connection)
    {
        $this->connection = $connection;
    }
    
    function getConnection()
    {
        return $this->connection;
    }
}