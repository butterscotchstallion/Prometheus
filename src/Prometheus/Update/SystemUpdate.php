<?php
/**
 * SystemUpdate - This class provides database access
 * and utility methods for updates. 
 *
 */
namespace Prometheus\Update;

use Prometheus\Console\Console,
    Prometheus\Adapter\MySQL\Adapter;

class SystemUpdate
{
    private $console;
    private $adapter;
    
    public function __construct()
    {
        $this->console    = new Console();
        $this->adapter    = new Adapter();
        $this->connection = $this->getAdapter()->getConnection();
    }
    
    function disableForeignKeyChecks()
    {
        $this->getAdapter()->save('SET foreign_key_checks = 0');
    }
    
    function save($query, $params = array()) 
    {
        $this->getConsole()->info(sprintf('Running query "%s"...', 
                                          substr(trim($query), 0, 19)));
        
        $this->disableForeignKeyChecks();
        
        $result    = $this->getAdapter()->save($query, $params);
        $lastError = Adapter::getLastError();
        
        if (!$result || $lastError) {           
            $this->getConsole()->error($lastError);
        } else {
            //$this->getConsole()->ok(sprintf("Result: %s", $result));
        }
        
        return $result;
    }
    
    /**
     * Check if a table exists
     * @param string $table
     * @return bool
     *
     */
    function hasTable($table)
    {    
        $q = 'SELECT COUNT(*) as tblCount
              FROM information_schema.tables 
              WHERE 1=1
              AND table_name = :table';
        
        $stmt = $this->getConnection()->prepare($q);
        $stmt->execute(array(':table' => $table));
        
        $result = $stmt->fetch();
        
        return $result ? $result['tblCount'] > 0 : false;
    }
    
    /**
     * Check if a column in a table exists
     * @param string $table
     * @param string $column
     * @return bool
     *
     */
    function hasColumn($table, $column)
    {
        // Note: binding the table name is not possible
        // because it would be quoted, causing a syntax
        // error.
        $q = sprintf('SHOW COLUMNS 
                      FROM `%s` LIKE :col', $table);
                      
        $stmt = $this->getConnection()->prepare($q);
        $stmt->execute(array(':col' => $column));
        $result = $stmt->fetch($q);
        
        return $result ? count($result) > 0 : false;
    }
    
    /**
     * Check if a table has a foreign key
     * @param string $key
     * @return bool
     *
     */
    function hasForeignKey($key)
    {
        
    }
    
    /**
     * Check if a table has an index
     * @param string $index
     * @return bool
     *
     */
    function hasIndex($index)
    {
        $q = sprintf('SHOW INDEXES 
                      FROM `%s` LIKE :index', $index);
                      
        $stmt   = $this->getConnection()->prepare($q);
        $stmt->execute(array(':index' => $index));
        $result = $stmt->fetch($q);
        
        return $result ? count($result) > 0 : false;
    }
    
    function setConsole($console)
    {
        $this->console = $console;
    }
    
    function getConsole()
    {
        return $this->console;
    }
    
    function getConnection()
    {
        if ($this->connection) {
            return $this->connection;
        } else {
            throw new \RuntimeException('Connection unavailable!');
        }
    }
    
    function getAdapter()
    {
        return $this->adapter;
    }
}