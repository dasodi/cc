<?php
class DbPDO
{
    private $_connection;
    private static $_instance; //The single instance
    private $_host = DB_HOST;
    private $_username = DB_USER;
    private $_password = DB_PASS;
    private $_database = DB_NAME;
    private $_port = 3306;
    public $Error='';
    
    /*
    Get an instance of the Database
    @return Instance
    */
    public static function getInstance()
    {
        if (!self::$_instance) { // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    // Constructor
    private function __construct()
    {
        try {
            $this->_connection  = new \PDO("mysql:host=$this->_host;port=$this->_port;dbname=$this->_database", $this->_username, $this->_password);
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            $this->Error=$e->getMessage();
            return false;
        }
    }
    
    // Magic method clone is empty to prevent duplication of connection
    private function __clone()
    {
    }
    
    // Get mysql pdo connection
    public function getConnection()
    {
        return $this->_connection;
    }
}