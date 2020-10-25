<?php
// Autor: Dario Soto Diaz
// Email: dasodi@gmail.com
// Date: 05-06-2020
//
// Esta aplicacion es software libre; puedes redistribuirlo y/o modificarlo 
// bajo los términos GNU General Public License version 2 (GPLv2)
// publicada por la Free Software Foundation.

/******************************************************************************
* Clase:        TableDescriptor
* Autor:        Dario Soto Diaz
* Version:      1.0
* Descripcion:  obtiene los datos de una tabla de una base de datos
* Fecha Ini:    05-06-2020
* Fecha Mod:    21-07-2020
*******************************************************************************/
class TableDescriptor {
    private $table;
    private $dblink;
    private $columns=array();
    private $primary_key='';
    private $primary_key_type='';
    public $Error='';

    public function __construct(&$db,$table){
        $this->table = $table;
        $this->dblink = $db;
        $this->Load();
    }
    
    public function Load(){
        $query = "SHOW COLUMNS FROM {$this->getTable()}";
        $result = $this->Query($query);
        while($row = $this->GetRow($result)){
            $this->AddColumn($row);
        }
    }
    
    public function Query($query){
        $rs = $this->dblink->prepare($query);
        try{
            $rs->execute(); 
        } catch (Exception $ex) {
            $this->Error='Error sql= '.$ex->getMessage();
            return false;
        }
        return $rs;
    }
    
    public function GetRow($result){
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    public function AddColumn($column){
        $pattern = "#([a-z]{1,})[\(]{0,}([0-9]{0,})[\)]{0,}#";
        $matches = array();
        preg_match($pattern, $column['Type'],$matches);
        $column['Type']   = $matches[1];
        $column['Length'] = $matches[2];
        $this->columns[] = $column;
        if( $column['Key'] == 'PRI' ){
            $this->primary_key = $column['Field'];
            $this->primary_key_type = $column['Type'];
        }
    }

    public function getTable() {
        return $this->table;
    }
    
    public function getColumns() {
        return $this->columns; 
    }
    
    public function getPrimaryKey() {
        return $this->primary_key;
    }
    
    public function getPrimaryKeyType() {
        return $this->primary_key_type;
    }
    
    public function __destruct(){
        if( is_resource($this->dblink) ){
            $this->dblink=null;
        }
    }
}

/******************************************************************************
* Clase:        CreateClassDB
* Autor:        Dario Soto Diaz
* Version:      1.0
* Descripcion:  Genera funciones get/set, load/add/update/delete para una tabla 
                espefifica de una base de datos.
* Fecha Ini:    05-06-2020
* Fecha Mod:    21-07-2020
*******************************************************************************/
class CreateClassDB{
    private $buffer = '';
    private $prefijo = '';
    private $autor = '';
    private $descripcion = '';
    private $version = '';
    private $spanish = false;
    public $table_descriptor;
    public $class_name = '';
    public $pk = '';
    public $pk_type = '';
    public $Error = '';
    private $variable_types = array(
        "int"       => "int",
        "text"      => "string",
        "bool"      => "bool",
        "date"      => "date",
        "time"      => "string",
        "enum"      => "string",
        "blob"      => "int",
        "float"     => "int",
        "double"    => "int",
        "bigint"    => "int",
        "tinyint"   => "int",
        "longint"   => "int",
        "varchar"   => "string",
        "smallint"  => "int",
        "decimal"  => "int",
        "datetime"  => "string",
        "timestamp" => "string"
        );
    private $variable_defaults = array(
        "int"       => "0",
        "text"      => "''",
        "bool"      => "false",
        "date"      => "''",
        "time"      => "''",
        "enum"      => "''",
        "blob"      => "0",
        "float"     => "0",
        "double"    => "0",
        "bigint"    => "0",
        "tinyint"   => "0",
        "longint"   => "0",
        "varchar"   => "''",
        "smallint"  => "0",
        "decimal"  => "0",
        "datetime"  => "''",
        "timestamp" => "''"
        );
    
    public function __construct(&$db,$table,$classname='',$prefijo='',$autor='',$descripcion='',$version='',$spanish=false,$es_coleccion=false){
        if($table==''){
            $this->Error='Falta nombre de la tabla';
            return false;
        }
        $this->table_descriptor = new TableDescriptor($db,$table);
        $this->pk = $this->table_descriptor->getPrimaryKey();
        $this->pk_type = $this->variable_types[$this->table_descriptor->getPrimaryKeyType()];
        if($classname){
            $this->class_name=$classname;
        }else{
            //elimina el prefijo de la tabla para el nombre de la clase
            $this->class_name = substr($this->table_descriptor->getTable(),strlen($this->prefijo));
            //quita s del plural
            if(!$es_coleccion && substr($this->class_name,-1)=='s'){
                $this->class_name = substr($this->class_name,0,strlen($this->class_name)-1);
            }
        }
        $this->prefijo = $prefijo;
        //para cabecera de la clase a generar
        $this->autor = $autor;
        $this->descripcion = $descripcion;
        $this->version = $version;
        $this->spanish = $spanish;
        //carga clase
        $this->Load();
    }

    private function Load(){
        if($this->pk == ''){
            echo 'Error: '.'No se ha definido en la tabla una clave primaria';
            exit();
        }
        
        $buf = "";
        
        //crea buffer con el codigo de la clase
        $buf .= "\n"."/******************************************************************************"."\n";
        $buf .= "* Clase: ". $this->class_name . ' para ' . DB_NAME . "." . $this->table_descriptor->getTable() . "\n";
        $buf .= "* Autor: " . $this->autor . "\n";
        $buf .= "* Version: " . $this->version . "\n";
        $buf .= "* Descripcion: " . $this->descripcion . "\n";
        $buf .= "* Fecha Ini: " . date('d-m-Y') . "\n";
        $buf .= "* Fecha Mod: " . date('d-m-Y') . "\n";
        $buf .= "*******************************************************************************/"."\n"."\n";
        $buf .= "class {$this->class_name}"."\n"."{"."\n";
        //crea propiedad publica para mostrar errores
        $buf .= "\t"."/**"."\n";
        $buf .= "\t"."* @var string\n";
        $buf .= "\t"."* muestra los errores de la clase"."\n";
        $buf .= "\t"."*/\n";
        $buf .= "\t"."public \$Error = '';"."\n"."\n";
        //crea propiedad privada para conexion base de datos
        $buf .= "\t"."/**"."\n";
        $buf .= "\t"."* @var dbPDO\n";
        $buf .= "\t"."* conexion base de datos de la clase"."\n";
        $buf .= "\t"."*/\n";
        $buf .= "\t"."private \$dblink;"."\n"."\n";
        
        //crea propiedades
        foreach($this->table_descriptor->getColumns() as $column){
            $column_name = str_replace('-','_',$column['Field']);
            $buf .= "\t"."/**"."\n";
            $buf .= "\t"."* @var {$this->variable_types[$column['Type']]}"."\n";
            if( $column['Field'] == $this->pk ){
                if($this->variable_types[$column['Type']] == 'int'){
                    $buf .= "\t"."* Class Unique ID"."\n";
                    $buf .= "\t"."*/"."\n";
                    $buf .= "\t"."public \$ID = 0;"."\n"."\n";
                }elseif($this->variable_types[$column['Type']] == 'string'){
                    $buf .= "\t"."* Class Unique ID"."\n";
                    $buf .= "\t"."*/"."\n";
                    $buf .= "\t"."public \$ID = '';"."\n"."\n";
                }else{
                    echo 'Error: '.'La clave primaria debe ser un numero entero o un string';
                    exit();
                }
                    
            }else{
                $buf .= "\t"."*/"."\n";
                $buf .= "\t"."public \$$column_name = {$this->variable_defaults[$column['Type']]};"."\n"."\n";
            }
        }
        
        //-------------------------- __construct() ------------------------------------------------------------
        if($this->pk_type == 'int'){
            $buf .= "\t"."public function __construct(&\$db,\$$this->pk=0){"."\n";
            $buf .= "\t"."\t"."\$this->dblink = \$db;"."\n"."\n";
            $buf .= "\t"."\t"."if(\$$this->pk === 0){"."\n";
        }else{
            $buf .= "\t"."public function __construct(&\$db,\$$this->pk=''){"."\n";
            $buf .= "\t"."\t"."\$this->dblink = \$db;"."\n"."\n";
            $buf .= "\t"."\t"."if(\$$this->pk === ''){"."\n";
        }
        $buf .= "\t"."\t"."\t"."return true;"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."\$this->ID = \$$this->pk;"."\n";
        $buf .= "\t"."\t"."if(!\$this->Load()){\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
		$buf .= "\t"."\t"."}"."\n"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";

        //-------------------------- Load() ------------------------------------------------------------
        $buf .= "\t"."private function Load(){"."\n";
        $buf .= "\t"."\t"."\$query = \"SELECT * FROM " . $this->table_descriptor->getTable() . " WHERE `$this->pk` = :id\";"."\n";
        $buf .= "\t"."\t"."\$rs=array();"."\n";
        $buf .= "\t"."\t"."\$rs[0]['campo'] = 'id';"."\n";
        $buf .= "\t"."\t"."\$rs[0]['valor'] = \$this->ID;"."\n";
        $buf .= "\t"."\t"."\$this->dblink->query(\$query,\$rs);"."\n";
        $buf .= "\t"."\t"."if(\$this->dblink->Error){"."\n";
        if($this->pk_type == 'int'){
            $buf .= "\t"."\t"."\t"."\$this->ID = 0;//borra ID"."\n";
        }else{
            $buf .= "\t"."\t"."\t"."\$this->ID = '';//borra ID"."\n";
        }
        $buf .= "\t"."\t"."\t"."\$this->Error = \$this->dblink->Error;"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n"."\n";
        $buf .= "\t"."\t"."//comprueba existe item"."\n";
		$buf .= "\t"."\t"."if(\$this->dblink->num_rows() == 0){"."\n";
        if($this->pk_type == 'int'){
            $buf .= "\t"."\t"."\t"."\$this->ID = 0;//borra ID"."\n";
        }else{
            $buf .= "\t"."\t"."\t"."\$this->ID = '';//borra ID"."\n";
        }
		$buf .= "\t"."\t"."\t"."\$this->Error = 'item inexistente';"."\n";
		$buf .= "\t"."\t"."\t"."return false;"."\n";
		$buf .= "\t"."\t"."}"."\n"."\n";
        $buf .= "\t"."\t"."while(\$row = \$this->dblink->next_record()){"."\n";
        $buf .= "\t"."\t"."\t"."foreach(\$row as \$key => \$value)"."\n";
        $buf .= "\t"."\t"."\t"."{"."\n";
        $buf .= "\t"."\t"."\t"."\t"."\$column_name = str_replace('-','_',\$key);"."\n";
        $buf .= "\t"."\t"."\t"."\t"."if(\$column_name == '$this->pk'){"."\n";
        $buf .= "\t"."\t"."\t"."\t"."\t"."\$this->ID = \$value;"."\n";
        $buf .= "\t"."\t"."\t"."\t"."}else{"."\n";
        $buf .= "\t"."\t"."\t"."\t"."\t"."\$this->{\$column_name} = \$value;"."\n";
        $buf .= "\t"."\t"."\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";
        
        //-------------------------- Add() ------------------------------------------------------------
        $insert_vars = "";
        $check_vars = "";
        $insert_columns = "";
        $insert_values  = "";
        $params = "";
        foreach($this->table_descriptor->getColumns() as $column){
            $column_name = str_replace('-','_',$column['Field']);
            if($column['Null']=='NO'){
                if($this->pk_type == 'int'){
                    if( $column['Field'] != $this->pk ){
                        $insert_vars .= "\${$column['Field']},";
                        $insert_columns .= "`{$column['Field']}`,";
                        $params .= ":".strtolower($column_name).",";
                        $insert_values  .= "\$this->$column_name,";
                    }
                }else{
                    $insert_vars .= "\${$column['Field']},";
                    $insert_columns .= "`{$column['Field']}`,";
                    $params .= ":".strtolower($column_name).",";
                    if($column_name == $this->pk){
                        $insert_values  .= "\$this->ID,";
                    }else{
                        $insert_values  .= "\$this->$column_name,";
                    }
                }
            }
        }
        $insert_vars = rtrim($insert_vars,',');
        $insert_columns = rtrim($insert_columns,',');
        $insert_values  = rtrim($insert_values,',');
        $params = rtrim($params,',');

        if($this->spanish){
            $buf .= "\t"."public function Agregar(". $insert_vars."){"."\n";
        }else{
            $buf .= "\t"."public function Add(". $insert_vars."){"."\n";
        }

        //comprueba si la clase esta instanciada
        $buf .= "\t"."\t"."if(\$this->ID){"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Error = 'Clase ya instanciada';"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n"."\n";

        //almacena valores en propiedades
        $vars = explode(',',$insert_vars);
        foreach($vars as $v){
            $vv=substr($v,1);
            if($vv == $this->pk){
                $buf .= "\t"."\t"."\$this->ID = $v;"."\n";
            }else{
                $buf .= "\t"."\t"."\$this->$vv = $v;"."\n";
            }
        }
        $buf .= "\n";
        
        //inserta funcion comprobacion requisitos de agregacion
        $buf .= "\t"."\t"."//comprueba requisitos agregacion"."\n";
        if($this->spanish){
            $buf .= "\t"."\t"."if(!\$this->checkAgregar()){"."\n";
        }else{
            $buf .= "\t"."\t"."if(!\$this->checkAdd()){"."\n";
        }
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n"."\n";

        //crea consulta de agregacion
        $buf .= "\t"."\t"."\$query =\"INSERT INTO {$this->table_descriptor->getTable()} ($insert_columns) VALUES ($params);\";\n";
        $params = str_replace(':','',$params);//elimina los :
        $params = explode(',',$params);
        $values = explode(',',$insert_values);
        $cont=0;
        $buf .= "\t"."\t"."\$rs=array();"."\n";
        foreach($params as $p){
            $buf .= "\t"."\t"."\$rs[$cont]['campo'] = '$p';"."\n";
            $buf .= "\t"."\t"."\$rs[$cont]['valor'] = $values[$cont];"."\n";
            $cont++;
        }
        $buf .= "\t"."\t"."\$this->dblink->query(\$query,\$rs);"."\n";
        $buf .= "\t"."\t"."if(\$this->dblink->Error){"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Error = \$this->dblink->Error;"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n"; 
        $buf .= "\t"."\t"."}"."\n"."\n";

        //si la pk es int y autoincrement obtiene id creado
        if($this->pk_type == 'int'){
            $buf .= "\t"."\t"."\$this->ID = \$this->dblink->lastInsertId();"."\n";
        }

        //recarga propiedades clase
        $buf .= "\t"."\t"."if(!\$this->Load()){"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n"; 
        $buf .= "\t"."\t"."}"."\n"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";
        
        //-------------------------- Update() ------------------------------------------------------------
        $update_columns = "";
        $columns="";
        foreach($this->table_descriptor->getColumns() as $column){
            if( $column['Field'] != $this->pk ){
                $column_name = str_replace('-','_',$column['Field']);
                $columns .= $column_name.",";
                $param = ":".strtolower($column_name);
                $update_columns .= "\n"."\t"."\t"."\t"."\t"."\t`{$column['Field']}` = $param ,";
            }
        }
        $update_columns = rtrim($update_columns,',');
        $columns = rtrim($columns,',');

        if($this->spanish){
            $buf .= "\t"."public function Guardar(){"."\n";
        }else{
            $buf .= "\t"."public function Update(){"."\n";
        }
        
        //comprueba si la clase esta instanciada
        $buf .= "\t"."\t"."if(!\$this->ID){"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Error = 'Clase no instanciada';"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n"."\n";

        $buf .= "\t"."\t"."\$query = \"UPDATE " . $this->table_descriptor->getTable() . " SET $update_columns "."\n";
        $buf .= "\t"."\t"."\t"."\t"."\t"."WHERE `$this->pk`=:id\";"."\n";
        //forma array de valores
        $columns = explode(',',$columns);
        $cont=0;
        $buf .= "\t"."\t"."\$rs=array();"."\n";
        foreach($columns as $c){
            $buf .= "\t"."\t"."\$rs[$cont]['campo'] = '".strtolower($c)."';"."\n";
            $buf .= "\t"."\t"."\$rs[$cont]['valor'] = \$this->$c;"."\n";
            $cont++;
        }
        $buf .= "\t"."\t"."\$rs[$cont]['campo'] = 'id';"."\n";
        $buf .= "\t"."\t"."\$rs[$cont]['valor'] = \$this->ID;"."\n";
        $buf .= "\t"."\t"."\$this->dblink->query(\$query,\$rs);"."\n";
        $buf .= "\t"."\t"."if(\$this->dblink->Error){"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Error = \$this->dblink->Error;"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n"; 
        $buf .= "\t"."\t"."}"."\n"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";
        
        //-------------------------- UpdateID() ----------------------------------------------------------
        if($this->pk_type != 'int'){
            if($this->spanish){
                $buf .= "\t"."public function ActualizarID(\$newid){"."\n";
            }else{
                $buf .= "\t"."public function UpdateID(\$newid){"."\n";
            }
            
            //comprueba si la clase esta instanciada
            $buf .= "\t"."\t"."if(!\$this->ID){"."\n";
            $buf .= "\t"."\t"."\t"."\$this->Error = 'Clase no instanciada';"."\n";
            $buf .= "\t"."\t"."\t"."return false;"."\n";
            $buf .= "\t"."\t"."}"."\n"."\n";

            $buf .= "\t"."\t"."\$query = \"UPDATE " . $this->table_descriptor->getTable() . " SET "."\n";
            $buf .= "\t"."\t"."\t"."\t"."\t"."`" . $this->pk . "` = :newid "."\n";
            $buf .= "\t"."\t"."\t"."\t"."\t"."WHERE `$this->pk`=:id\";"."\n";
            $buf .= "\t"."\t"."\$rs=array();"."\n";
            $buf .= "\t"."\t"."\$rs[0]['campo'] = 'newid';"."\n";
            $buf .= "\t"."\t"."\$rs[0]['valor'] = \$newid;"."\n";
            $buf .= "\t"."\t"."\$rs[1]['campo'] = 'id';"."\n";
            $buf .= "\t"."\t"."\$rs[1]['valor'] = \$this->getID();"."\n";
            $buf .= "\t"."\t"."\$this->dblink->query(\$query,\$rs);"."\n";
            $buf .= "\t"."\t"."if(\$this->dblink->Error){"."\n";
            $buf .= "\t"."\t"."\t"."\$this->Error = \$this->dblink->Error;"."\n";
            $buf .= "\t"."\t"."\t"."return false;"."\n"; 
            $buf .= "\t"."\t"."}"."\n"."\n";
            $buf .= "\t"."\t"."\$this->setID(\$newid);"."\n"."\n";
            $buf .= "\t"."\t"."if(!\$this->Load()){"."\n";
            $buf .= "\t"."\t"."\t"."return false;"."\n";
            $buf .= "\t"."\t"."}"."\n"."\n";
            $buf .= "\t"."\t"."return true;"."\n";
            $buf .= "\t"."}"."\n"."\n";
        }

        //-------------------------- Delete() ------------------------------------------------------------
        if($this->spanish){
            $buf .= "\t"."public function Eliminar(){"."\n";
        }else{
            $buf .= "\t"."public function Delete(){"."\n";
        }

        //comprueba si la clase esta instanciada
        $buf .= "\t"."\t"."if(!\$this->ID){"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Error = 'Clase no instanciada';"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n"."\n";

        //inserta funcion comprobacion requisitos de eliminacion
        $buf .= "\t"."\t"."//comprueba requisitos eliminacion"."\n";
        if($this->spanish){
            $buf .= "\t"."\t"."if(!\$this->checkEliminar()){"."\n";
        }else{
            $buf .= "\t"."\t"."if(!\$this->checkDelete()){"."\n";
        }
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n"."\n";

        $buf .= "\t"."\t"."\$query = \"DELETE FROM " . $this->table_descriptor->getTable() . " WHERE `$this->pk` = :id\";"."\n";
        $buf .= "\t"."\t"."\$rs=array();"."\n";
        $buf .= "\t"."\t"."\$rs[0]['campo'] = 'id';"."\n";
        $buf .= "\t"."\t"."\$rs[0]['valor'] = \$this->ID;"."\n";
        $buf .= "\t"."\t"."\$this->dblink->query(\$query,\$rs);"."\n";
        $buf .= "\t"."\t"."if(\$this->dblink->Error){;"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Error = \$this->dblink->Error;"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n"; 
        $buf .= "\t"."\t"."}"."\n"."\n";
        $buf .= "\t"."\t"."//borra ID"."\n";
        if($this->pk_type == 'int'){
            $buf .= "\t"."\t"."\$this->ID = 0;"."\n"."\n";
        }else{
            $buf .= "\t"."\t"."\$this->ID = '';"."\n"."\n";
        }
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";
        
        //-------------------------- checkAdd() ------------------------------------------------------------
        if($this->spanish){
            $buf .= "\t"."private function checkAgregar(){"."\n";
        }else{
            $buf .= "\t"."private function checkAdd(){"."\n";
        }
        $buf .= "\t"."\t"."//TODO: comprobar requisitos"."\n"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";

        //-------------------------- checkDelete() ------------------------------------------------------------
        if($this->spanish){
            $buf .= "\t"."private function checkEliminar(){"."\n";
        }else{
            $buf .= "\t"."private function checkDelete(){"."\n";
        }
        $buf .= "\t"."\t"."//TODO: comprobar requisitos"."\n"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";

        //-------------------------- __destruct() ------------------------------------------------------------
        $buf .= "\t"."public function __destruct(){"."\n";
        $buf .= "\t"."\t"."\$this->dblink=null;"."\n";
        $buf .= "\t"."}"."\n"."\n";

        $buf .= "} // END class {$this->class_name}"."\n"."\n";
        
        $this->buffer = $buf;
    }

    public function getClass() {
        return $this->buffer; 
    }

    public function getValidate(){
        $buf .= "\n"."/******************************************************************************"."\n";
        $buf .= "* Clase: validate" . "\n";
        $buf .= "* Autor: " . $this->autor . "\n";
        $buf .= "* Version: " . $this->version . "\n";
        $buf .= "* Descripcion: crea funciones de validacion de propiedades" . "\n";
        $buf .= "* Fecha Ini: " . date('d-m-Y') . "\n";
        $buf .= "* Fecha Mod: " . date('d-m-Y') . "\n";
        $buf .= "*******************************************************************************/"."\n"."\n";
        $buf .= "class validate {"."\n";
        
        $buf .= "\t"."public static function isstring(\$string){"."\n";
        $buf .= "\t"."\t"."return (is_string(\$string));"."\n";
        $buf .= "\t".'}'."\n";
        $buf .= "\n";
        
        $buf .= "\t"."public static function isint(\$int){"."\n";
        $buf .= "\t"."\t"."return (preg_match(\"/^([0-9.,-]+)$/\", \$int) > 0);"."\n";
        $buf .= "\t".'}'."\n";
        $buf .= "\n";
        
        $buf .= "\t"."public static function isbool(\$bool){"."\n";
        $buf .= "\t"."\t"."\$b = 1 * \$bool;"."\n";
        $buf .= "\t"."\t"."return (\$b == 1 || \$b == 0);"."\n";
        $buf .= "\t".'}'."\n";
        $buf .= "\n";
        
        $buf .= "\t"."public static function isdate(\$date,\$sql=true){"."\n";
        $buf .= "\t"."\t"."if(is_null(\$date)){"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."\$usdate=str_replace('/', '-', \$date);"."\n";
        $buf .= "\t"."\t"."if(\$sql){"."\n";
        $buf .= "\t"."\t"."\t"."list(\$year,\$month,\$day) = explode('-',\$usdate);"."\n";
        $buf .= "\t"."\t"."}else{"."\n";
        $buf .= "\t"."\t"."\t"."list(\$day,\$month,\$year) = explode('-',\$usdate);"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."if(!checkdate(\$month, \$day, \$year)){"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n";
        $buf .= "\n";
        
        $buf .= "}"."\n";
        $buf .= "\n";

        return $buf;
    }

    public function getCollection($order_col_name = ''){

        //comprueba que exista el campo introducido
        if($order_col_name != ''){
            $check_col = false;
            foreach($this->table_descriptor->getColumns() as $column){
                if( $column['Field'] === $order_col_name ){
                    $check_col = true;
                }
            }
            if(!$check_col){
                $this->Error = 'El campo para ordenar la coleccion no existe en la tabla '. $this->table_descriptor->getTable();
                return false;
            }
        }

        //pinta la clase coleccion agregando una s al final del nombre de la clase base
        $buf = "";
        $buf .= "\n"."/******************************************************************************"."\n";
        $buf .= "* Clase: " . $this->class_name.'s' . "\n";
        $buf .= "* Autor: " . $this->autor . "\n";
        $buf .= "* Version: " . $this->version . "\n";
        $buf .= "* Descripcion: crea coleccion clase " .$this->class_name. "\n";
        $buf .= "* Fecha Ini: " . date('d-m-Y') . "\n";
        $buf .= "* Fecha Mod: " . date('d-m-Y') . "\n";
        $buf .= "*******************************************************************************/"."\n"."\n";
        $buf .= "class ".$this->class_name."s"." {"."\n";

        //crea propiedad publica para mostrar errores
        $buf .= "\t"."/**"."\n";
        $buf .= "\t"."* @var string\n";
        $buf .= "\t"."* muestra los errores de la clase"."\n";
        $buf .= "\t"."*/\n";
        $buf .= "\t"."public \$Error = '';"."\n"."\n";

        //crea propiedad privada para conexion base de datos
        $buf .= "\t"."/**"."\n";
        $buf .= "\t"."* @var dbPDO\n";
        $buf .= "\t"."* conexion base de datos de la clase"."\n";
        $buf .= "\t"."*/\n";
        $buf .= "\t"."private \$dblink;"."\n"."\n";

        //crea propiedad privada para mostrar numero de registros
        $buf .= "\t"."/**"."\n";
        $buf .= "\t"."* @var int\n";
        $buf .= "\t"."* muestra numero de registros"."\n";
        $buf .= "\t"."*/\n";
        $buf .= "\t"."public \$NumRegs = 0;"."\n"."\n";

        //crea propiedad privada coleccion de registros
        $buf .= "\t"."/**"."\n";
        $buf .= "\t"."* @var array\n";
        $buf .= "\t"."* coleccion de registros"."\n";
        $buf .= "\t"."*/\n";
        $buf .= "\t"."public \$Detalle = array();"."\n"."\n";

        //-------------------------- __construct() ------------------------------------------------------------
        $buf .= "\t"."public function __construct(&\$db,\$id_excluir=0){"."\n";
        $buf .= "\t"."\t"."\$this->dblink = \$db;"."\n"."\n";
        $buf .= "\t"."\t"."if(!\$this->Load(\$id_excluir)){\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";
        
        //-------------------------- Load() ------------------------------------------------------------
        $buf .= "\t"."private function Load(\$id_excluir){"."\n";
        $buf .= "\t"."\t"."if(\$id_excluir != 0){"."\n";
        if($order_col_name != ''){
            $buf .= "\t"."\t"."\t"."\$sql='SELECT " . $this->pk . ", ". $order_col_name . " FROM " . $this->table_descriptor->getTable() . " WHERE " . $this->pk . " != :id ORDER BY " . $order_col_name . "';"."\n";
        }else{
            $buf .= "\t"."\t"."\t"."\$sql='SELECT " . $this->pk . " FROM " . $this->table_descriptor->getTable() . " WHERE " . $this->pk . " != :id ORDER BY " . $this->pk . "';"."\n";
        }
        $buf .= "\t"."\t"."\t"."\$rs[0]['campo'] = 'id';"."\n";
        $buf .= "\t"."\t"."\t"."\$rs[0]['valor'] = \$id_excluir;"."\n";
        $buf .= "\t"."\t"."\t"."\$this->dblink->query(\$sql,\$rs);"."\n";
        $buf .= "\t"."\t"."}else{"."\n";
        if($order_col_name){
            $buf .= "\t"."\t"."\t"."\$sql='SELECT " . $this->pk . ", ". $order_col_name. " FROM " . $this->table_descriptor->getTable() . " ORDER BY " . $order_col_name . "';"."\n";
        }else{
            $buf .= "\t"."\t"."\t"."\$sql='SELECT " . $this->pk . " FROM " . $this->table_descriptor->getTable() . " ORDER BY " . $this->pk . "';"."\n";
        }
        $buf .= "\t"."\t"."\t"."\$this->dblink->query(\$sql);"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."if(\$this->dblink->Error){"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Error = \$this->dblink->Error;"."\n";
        $buf .= "\t"."\t"."\t"."return false;"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."\$this->NumRegs = \$this->dblink->num_rows();"."\n";
        $buf .= "\t"."\t"."\$cont=0;"."\n";
        $buf .= "\t"."\t"."while(\$this->dblink->next_record()){"."\n";
        $buf .= "\t"."\t"."\t"."\$this->Detalle[\$cont] = \$this->dblink->f('". $this->pk ."');"."\n";
        $buf .= "\t"."\t"."\t"."\$cont++;"."\n";
        $buf .= "\t"."\t"."}"."\n";
        $buf .= "\t"."\t"."return true;"."\n";
        $buf .= "\t"."}"."\n"."\n";
            
        //-------------------------- __destruct() ------------------------------------------------------------
        $buf .= "\t"."public function __destruct(){"."\n";
        $buf .= "\t"."\t"."\$this->dblink = null;"."\n";
        $buf .= "\t"."}"."\n"."\n";
        
        $buf .= "} // END class {$this->class_name}s"."\n"."\n";

        return $buf;
    }
}