<?php

namespace src;

class db{

    private $db;


    public function __construct($hostname, $dbname, $username, $pwd){

        $this->hostname = $hostname;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->pwd = $pwd;

        $this->connect();

    }


    public function close(){
        $this->db = null;
    }

    public function connect(){

        try {
            $this->db = new \PDO ("dblib:host=$this->hostname;dbname=$this->dbname", "$this->username", "$this->pwd");
            return $this->db;

        } catch (PDOException $e) {
            $this->logsys .= "Failed to get DB handle: " . $e->getMessage() . "\n";
        }

    }

}

?>
