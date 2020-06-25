<?php
/*
Aplicacion:     CreateClassDB
Autor:          Dario Soto Diaz
Version:        1.0
Descripcion:    Encapsula conexion a BD mediante PDO
Fecha Ini.:     02-06-2020
Fecha Mod.:     02-06-2020
*/
class DB 
{ 
    private $host = DB_HOST; 
    private $user = DB_USER; 
    private $pass = DB_PASS; 
    private $dbname = DB_NAME; 
    private $rs;//consulta
    private $db;//base de datos
    private $record;//registro
    public $Error=false;//muestra errores
    
    public function __construct($host=null,$dbname=null,$user=false,$pass=null){
        if(!is_null($host) && !is_null($dbname) && !is_null($user) && !is_null($pass)){
            $this->host=$host;
            $this->dbname=$dbname;
            $this->user=$user;
            $this->pass=$pass;
        }
        
        // Set DSN
        if(is_null($host) || is_null($dbname) || is_null($user) || is_null($pass)){
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        }
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        // Set options 
        $options = array( 
            PDO::ATTR_PERSISTENT => true, 
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION 
        ); 
        // Create a new PDO instanace 
        try{ 
            $this->db = new PDO($dsn, $this->user, $this->pass, $options); 
        } 
        // Catch any errors 
        catch(PDOException $e){ 
            $this->Error = $e->getMessage(); 
        } 
    } 

    /* 
    El metodo query crea la variable $query para mantener la consulta
    El metodo query tambien inicia la funcion PDO::prepare
    La funcion prepare permite enlazar valores en tus sentencias SQL,
    evita la inyeccion SQL Injection y permite mayor velocidad y eficiencia en las consultas
    */ 
    public function query($query,&$values=null){
        $this->close();
        
        $this->rs = $this->db->prepare($query); 
        if(isset($values) && is_array($values)){
            $this->bindAll($values);
        }
        $values=null;
        
        $this->execute();
    } 

    
    /* 
    Este metodo asocia campo con su valor usando el metodo PDOStatement::bindValue 
    Se podran pasar 3 argumentos ala funcion: 
    @param: nombre del campo, ejemplo :name. 
    @value: valor que queremos dar al campo, ejemplo 'John Smith'
    @type: es el tipo del campo, ejemplo string
    */ 
    public function bind($param, $value, $type = null){ 
        if (is_null($type)) { 
            switch (true) { 
            case is_int($value): 
                $type = PDO::PARAM_INT;
                break; 

            case is_bool($value): 
                $type = PDO::PARAM_BOOL;
                break; 

            case is_null($value): 
                $type = PDO::PARAM_NULL;
                break; 

            default: 
            $type = PDO::PARAM_STR;
            } 
        } 
        $this->rs->bindValue(':'.$param, $value, $type); 
    } 
    
    
    //enlaza una matriz completa de campo:valor
    public function bindAll($values){
        if(!is_array($values)){
            $this->Error='no ha pasado un array';
            return false;
        }
        foreach ($values as $v){
            $this->bind($v['campo'], $v['valor']);
        }
    }
    
    
    /* 
    Ejecuta la consulta preparada 
    */ 
    public function execute(){
        try{
            $this->rs->execute(); 
        } catch (Exception $ex) {
            $this->Error=$ex->getMessage();
            $pos=strpos($this->Error,'Duplicate entry');
            if($pos !== false){
                $this->Error='registro duplicado';
            }
            return false;
        }
        return true;
    } 
    
    
    /* 
    Devuelve un array de resultados.
    */ 
    public function resultset(){ 
        return $this->rs->fetchAll(PDO::FETCH_ASSOC); 
    } 

    /* 
    Devuelve un objeto con los resultados.
    */ 
    public function resultset_o(){ 
        return $this->rs->fetchAll(PDO::FETCH_OBJ); 
    } 
    
    /* 
    Devuelven el registro siguiente o primer registro
    */ 
    public function single(){ 
        return $this->rs->fetch(PDO::FETCH_ASSOC); 
    }
    public function next_record(){ 
        $this->record=$this->rs->fetch(PDO::FETCH_ASSOC);
        return $this->record; 
    }
    
    
    /* 
    Devuelven el valor de un campo del registro
    */
    public function f($Name) {
        if (isset($this->record[$Name])) {
            return $this->record[$Name];
        }
    }
    
    
    /* 
    Devuelven el numero de registros afectados por la ultima consulta 
    */ 
    public function rowCount(){ 
        return $this->rs->rowCount(); 
    }
    public function num_rows(){ 
        return $this->rs->rowCount(); 
    } 
    
    
    /* 
    Devuelve el ID del ultimo registro insertado 
    */ 
    public function lastInsertId(){ 
        return $this->db->lastInsertId(); 
    } 
    
    
    /* 
    Transacciones 
    */ 
    public function beginTransaction(){ 
        return $this->db->beginTransaction(); 
    } 

    public function endTransaction(){ 
        return $this->db->commit(); 
    } 

    public function cancelTransaction(){ 
        return $this->db->rollBack(); 
    } 
    
    public function num_fields($table='') {
        
        $sql="show columns from :table";
        $rs[0]['campo']='table';
        $rs[0]['valor']=$table;
        $this->query($sql, $rs);

        $i = 0;
        $res=array();
        while ($this->next_record()) {
            $res[$i]=$this->f('column');
            $i++;
        }
    
        return count($res);
    }

    function get_fields($table='') {
        $sql="show columns from :table";
        $rs[0]['campo']='table';
        $rs[0]['valor']=$table;
        $this->query($sql, $rs);

        $i = 0;
        $res=array();
        while ($this->next_record()) {
            $res[$i]=$this->f('column');
            $i++;
        }
        
        return $res;
    }
    
    /* 
    Depuracion 
    */ 
    public function debugDumpParams(){ 
        return $this->rs->debugDumpParams(); 
    }
    
    
    /* 
    Vacia la consulta actual
    */
    public function close(){
        if(isset($this->rs)){
            $this->rs=null;
        }
    }
} 