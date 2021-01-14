<?php
/*
Aplicacion:     CreateClassDB
Autor:          Dario Soto Diaz
Version:        1.0
Descripcion:    Encapsula conexion a BD mediante PDO
Fecha Ini.:     02-06-2020
Fecha Mod.:     15-01-2021
*/

//define carpeta app
define('CONF_APP_DIR',__DIR__);

//inicio y configuracion de la app
require_once('cc/conf.php');

//en desarrollo para generar clases desde BD
require_once("cc/funs.php");
require_once('cc/dbpdo.php');
require_once('cc/template.php');
require_once('cc/createclassdb.php');

//ejecuta comando
switch($comando){
case 1://elimina archivo conexion
    setDeleteConnFile();
    header("Location: $pagina"); 
    break;

case 2://muestra tablas con formulario de seleccion
    if(!isset($_POST['host']) || !isset($_POST['dbname']) || !isset($_POST['user']) || !isset($_POST['pass'])){
        $m_error='Faltan parametros de la conexion';
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    if(strlen($_POST['host'])==0 || strlen($_POST['dbname'])==0 || strlen($_POST['user'])==0){
        $m_error='Faltan parametros de la conexion';
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    define('DB_HOST',strtolower(trim($_POST['host'])));
    define('DB_NAME',strtolower(trim($_POST['dbname'])));
    define('DB_USER',strtolower(trim($_POST['user'])));
    define('DB_PASS',trim($_POST['pass']));
    
    //establece conexion db
    $db=getConnection();
    if($db->Error){
        $m_error='Error conexion: '.$db->Error;
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    
    //escribe archivo de conexion
    setWriteConnFile(DB_HOST, DB_NAME, DB_USER, DB_PASS);
    
    //obtiene prefijo del nombre de las tablas
    if(isset($_POST['prefijo'])){
        $prefijo = trim($_POST['prefijo']);
    }else{
        $prefijo = '';
    }
    
    //obtiene carpeta donde guardar las clases creadas
    // y si no existe crea una por defecto
    if(isset($_POST['dirclases'])){
        $dir_classes=$_POST['dirclases'];
    }else{
        $dir_classes=__DIR__.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'classes';
        @ mkdir($dir_classes);
    }
    if(!is_dir($dir_classes)){
        $m_error='La carpeta de clases '.$dir_classes.' no existe';
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    //convierte dir_classes para pasarlo por get
    $dir_classes_url = str_replace(DIRECTORY_SEPARATOR, '__', $dir_classes);
    
    //obtiene autor
    if(isset($_POST['autor'])){
        $autor=trim($_POST['autor']);
    }else{
        $autor='Anonimo';
    }
    
    //obtiene version
    if(isset($_POST['version'])){
        $version=trim($_POST['version']);
    }else{
        $version='1.0';
    }
    
    $tablas=getTablasDeUnaBaseDeDatos($db,DB_NAME,$prefijo,$m_error);
    if(!is_array($tablas)){
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }

    $plan=new Template;
    $plan->set_file("inicio","cc/p/cc_tb.htm");
    $plan->set_var("Aplicacion",CONF_APP_NOMBRE);
    $plan->set_var("ColorCorp",CONF_APP_COLOR_CORP);
    $plan->set_var("ColorCorp2",CONF_APP_COLOR_CORP_2);
    $plan->set_var("PaginaDestino",$pagina);
    $plan->set_var("BaseDatos",DB_NAME);
    $plan->set_block("inicio","F1","tabla1");
    foreach ($tablas as $t){
        //muestra nombre tabla
        $plan->set_var("TableF1",$t['nombre']);
        //muestra los campos de la tabla y su descripcion
        $campos=getCamposDeUnaTabla($db,DB_NAME, $t['nombre'],$m_error);
        if(!($campos)){
            header("Location: error.php?pag=$pagina&err_des=$m_error"); 
            exit();
        }
        $plan->set_var("FieldsF1",'<ul>'. $campos . '</ul>');
        //muestra formulario
        $form = '<form name="form1" method="post" action="'.$pagina.'">'."\n";
        $form .= '<table class="normal_p" border="0">'."\n";
        $form .= '<tr>'."\n";
        $form .= '<td><b>nombre clase:</b></td>';
        $form .= '<td><input class="normal_p" type="text" name="classname" size="50" maxlength="50" value=""></td>';
        $form .= '</tr>'."\n";
        $form .= '<tr>'."\n";
        $form .= '<td><b>espacio nombres:</b></td>';
        $form .= '<td><input class="normal_p" type="text" name="namespace" size="50" maxlength="50" value=""></td>';
        $form .= '</tr>'."\n";
        $dir_classes = str_replace('__',DIRECTORY_SEPARATOR, $dir_classes_url);
        if(file_exists($dir_classes.DIRECTORY_SEPARATOR.'validate.php')){
            $form .= '<tr>'."\n";
            $form .= '<td><b>crear clase <i>validate</i>:</b></td>';
            $form .= '<td>ya existe</td>';
            $form .= '</tr>'."\n";
        }else{
            $form .= '<tr>'."\n";
            $form .= '<td><b>crear clase <i>validate</i>:</b></td>';
            $form .= '<td><input class="normal_p" type="checkbox" name="validate"></td>';
            $form .= '</tr>'."\n";
        }
        $form .= '<tr>'."\n";
        $form .= '<td><b>metodos en spanish:</b></td>';
        $form .= '<td>';
        if(CONF_APP_CREATE_SPANISH_METHODS){
            $form .= '<input class="normal_p" type="checkbox" name="spanish" checked>';
        }else{
            $form .= '<input class="normal_p" type="checkbox" name="spanish">';
        }
        $form .= "&nbsp;&nbsp;&nbsp;&nbsp;<b>tipo conexion PDO: </b>";
        if(CONF_APP_PDO_CONNECTION){
            $form .= '<input class="normal_p" type="checkbox" name="pdo" checked>';
        }else{
            $form .= '<input class="normal_p" type="checkbox" name="pdo">';
        }
        $form .= '</td>';
        $form .= '</tr>'."\n";
        $form .= '<tr>'."\n";
        $form .= '<td><b>descripcion:</b></td>';
        $form .= '<td><input class="normal_p" type="text" name="descripcion" size="50" maxlength="255" value=""></td>';
        $form .= '</tr>'."\n";
        $form .= '<tr>'."\n";
        $form .= '<td><b>crear coleccion:</b></td>';
        $form .= '<td><input class="normal_p" type="checkbox" name="coleccion">';
        $form .= '&nbsp;&nbsp;&nbsp;&nbsp;<b>campo padre:</b> <input class="normal_p" type="text" name="campo_padre" size="20" maxlength="255" value="">';
        $form .= '&nbsp;&nbsp;&nbsp;&nbsp;<b>ordenada por:</b> <input class="normal_p" type="text" name="coleccion_order" size="20" maxlength="255" value="">';
        $form .= '</td></tr>'."\n";
        $form .= '<tr>'."\n";
        $form .= '<td>&nbsp;</td>';
        $form .= '<td><input class="normal_p" type="submit" value="crear clase"></td>';
        $form .= '</tr>'."\n";
        $form .= '</table>'."\n";
        $form .= '<input type="hidden" name="cmd" value="3">';
        $form .= '<input type="hidden" name="dirclasses" value="'.$dir_classes_url.'">';
        $form .= '<input type="hidden" name="tb" value="'.$t['nombre'].'">';
        $form .= '<input type="hidden" name="prefijo" value="'.$prefijo.'">';
        $form .= '<input type="hidden" name="autor" value="'.$autor.'">';
        $form .= '<input type="hidden" name="version" value="'.$version.'">';
        $form .= '</form>';
        $plan->set_var("FormF1",$form);
        //envia linea
        $plan->parse("tabla1","F1",true);
    }

    $plan->pparse("salida","inicio");

    break;
    
case 3:
    //comprueba archivo de conexion
    $conn=CONF_APP_DIR_INC.DIRECTORY_SEPARATOR.'conn.php';
    if(!file_exists($conn)){
        $m_error='No se ha creado el archivo de conexion';
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    require_once($conn);
    
    //estable conexion a bd
    $db=getConnection();
    if($db->Error){
        $m_error='Error conexion: '.$db->Error;
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    
    //obtiene el nombre de la tabla
    if(!isset($_POST['tb'])){
        $m_error='No se ha pasado el nombre de la tabla a crear';
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    $tabla=$_POST['tb'];
    
    //obtiene prefijo del nombre de la tabla
    if(isset($_POST['prefijo'])){
        $prefijo = trim($_POST['prefijo']);
    }else{
        $prefijo = '';
    }
    
    //obtiene autor
    if(isset($_POST['autor'])){
        $autor=trim($_POST['autor']);
    }else{
        $autor='Anonimo';
    }

    //obtiene version
    if(isset($_POST['version'])){
        $version=trim($_POST['version']);
    }else{
        $version='1.0';
    }
    
    //obtiene descripcion
    if(isset($_POST['descripcion'])){
        $descripcion=trim($_POST['descripcion']);
    }else{
        $descripcion='Anonimo';
    }

    //obtiene nombre de la clase
    if(isset($_POST['classname'])){
        $classname=trim($_POST['classname']);
    }else{
        $classname='';
    }

    //obtiene espacio de nombres
    if(isset($_POST['namespace'])){
        $namespace=trim($_POST['namespace']);
    }else{
        $namespace='';
    }

    //crea coleccion de la clase
    if(isset($_POST['coleccion'])){
        $coleccion = true;
        if(isset($_POST['coleccion_order'])){
            $coleccion_order = $_POST['coleccion_order'];
        }else{
            $coleccion_order = '';
        }
        if(isset($_POST['campo_padre'])){
            $campo_padre = $_POST['campo_padre'];
        }else{
            $campo_padre = '';
        }
    }else{
        $coleccion = false;
        $coleccion_order = '';
        $campo_padre = '';
    }

    //establece si crea o no clase validate adicional
    if(isset($_POST['validate'])){
        $validate = true;
    }else{
        $validate = false;
    }

    //establece si crea los nombres de los metodos en spanish
    if(isset($_POST['spanish'])){
        $spanish = true;
    }else{
        $spanish = false;
    }
    
    //establece si crea conexion a bd tipo PDO o MySQLi
    if(isset($_POST['pdo'])){
        $pdo = true;
    }else{
        $pdo = false;
    }

    //construye la clase para la tabla seleccionada
    $c = new CreateClassDB($db,$tabla,$classname,$namespace,$prefijo,$autor,$descripcion,$version,$spanish,$pdo);
    if($c->Error){
        $m_error='Error: '.$c->Error;
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    
    //almacela buffer con la clase
    $code_class = $c->getClass();
    
    //obtiene carpeta de clases
    if(!isset($_POST['dirclasses'])){
        $m_error='No se ha pasado la carpeta de alojamiento de las clases';
        header("Location: error.php?pag=$pagina&err_des=$m_error"); 
        exit;
    }
    $dir_classes = str_replace('__',DIRECTORY_SEPARATOR, $_POST['dirclasses']);

    //crea el archivo con el codigo de la clase
    $file=$dir_classes.DIRECTORY_SEPARATOR.strtolower($c->class_name).'.php';
    if(file_exists($file)){
        unlink($file);
    }
    file_put_contents($file, '<?php'."\n".$code_class);

    if($coleccion){
        $file=$dir_classes.DIRECTORY_SEPARATOR.strtolower($c->class_name).'Col.php';
        if(file_exists($file)){
            unlink($file);
        }
        $code_coleccion = $c->getCollection($coleccion_order,$campo_padre);
        if($code_coleccion === false){
            $m_error='Error: Se ha creado la clase pero no se ha podido crear la coleccion: <br><br>'.$c->Error;
            header("Location: error.php?pag=$pagina&err_des=$m_error"); 
            exit;
        }
        file_put_contents($file, '<?php'."\n".$code_coleccion);
    }

    //crea la clase de validacion
    if($validate && !file_exists($dir_classes.DIRECTORY_SEPARATOR.'validate.php')){
        //obtiene el codigo
        $code_validate = $c->getValidate();
        //crea el archivo
        file_put_contents($dir_classes.DIRECTORY_SEPARATOR.'validate.php', '<?php'."\n".$code_validate);
    }
        
    //muestra la clase
    $plan=new Template;
    $plan->set_file("inicio","cc/p/cc_code.htm");
    $plan->set_var("Aplicacion",CONF_APP_NOMBRE);
    $plan->set_var("ColorCorp",CONF_APP_COLOR_CORP);
    $plan->set_var("ColorCorp2",CONF_APP_COLOR_CORP_2);
    $plan->set_var("PaginaDestino",$pagina);
    $plan->set_var("BaseDatos",DB_NAME);
    $plan->set_var("ClassName",$classname);
    $plan->set_var("FileClass",$file);
    $plan->set_var("Code",$code_class.'<br>'.$code_coleccion.'<br>'.$code_validate);
    //convierte dir_classes para pasarlo por get
    $dir_classes_url=  str_replace(DIRECTORY_SEPARATOR, '__', $dir_classes);
    $btn = '<center><button onClick="javascript:window.location=\''.$pagina.'?dirclasses='.$dir_classes_url.'&prefijo='.$prefijo.'&autor='.$autor.'&version='.$version.'\'">volver</button></center>';
    $plan->set_var("ButtonBack",$btn);

    $plan->pparse("salida","inicio");

    break;

default :
    $plan=new Template;
    $plan->set_file("inicio","cc/p/cc.htm");
    $plan->set_var("Aplicacion",CONF_APP_NOMBRE);
    $plan->set_var("ColorCorp",CONF_APP_COLOR_CORP);
    $plan->set_var("ColorCorp2",CONF_APP_COLOR_CORP_2);
    $plan->set_var("PaginaDestino",$pagina);
    $plan->set_var("DirConexion",CONF_APP_DIR_INC);
    $conn=CONF_APP_DIR_INC.DIRECTORY_SEPARATOR.'conn.php';
    if(file_exists($conn)){
        require_once('inc/conn.php');
        $host=DB_HOST;
        $dbname=DB_NAME;
        $user=DB_USER;
        $pass=DB_PASS;
        $btn_eliminar='&nbsp;&nbsp;&nbsp;&nbsp;'.'<input type="button" class="normal" value="Eliminar conn.php" onClick="javascript:window.location=\''.$pagina.'?cmd=1'.'\'">';
    }else{
        $host='localhost';
        $dbname='';
        $user='root';
        $pass='';
        $btn_eliminar='';
    }
    $plan->set_var("BotonEliminar",$btn_eliminar);
    
    //obtiene resto de datos
    if(isset($_GET['prefijo'])){
        $prefijo=$_GET['prefijo'];
    }else{
        $prefijo='';
    }
    if(isset($_GET['dirclasses'])){
        $dir_classes=$dir_classes = str_replace('__',DIRECTORY_SEPARATOR, $_GET['dirclasses']);
    }else{
        $dir_classes=CONF_APP_DIR_CLASSES;
    }
    if(isset($_GET['autor'])){
        $autor=$_GET['autor'];
    }else{
        $autor='';
    }
    if(isset($_GET['version'])){
        $version=$_GET['version'];
    }else{
        $version='';
    }
    $plan->set_var("Host",$host);
    $plan->set_var("DBName",$dbname);
    $plan->set_var("User",$user);
    $plan->set_var("Pass",$pass);
    $plan->set_var("Prefijo",$prefijo);
    $plan->set_var("DirClases",$dir_classes);
    $plan->set_var("Autor",$autor);
    $plan->set_var("Version",$version);
    $plan->set_var("Comando",2);
    $plan->pparse("salida","inicio"); 
}
        
exit();

//*********************************************************************************************************
//******************** FUNCIONES DE LA PAGINA *************************************************************
//*********************************************************************************************************

function setWriteConnFile($host,$dbname,$user,$pass){
    $file = CONF_APP_DIR_INC.DIRECTORY_SEPARATOR.'conn.php';
    if(file_exists($file)){
        @ unlink($file);
    }
    $buf = "";
    $buf .= "define('DB_HOST','$host');"."\n";
    $buf .= "define('DB_NAME','$dbname');"."\n";
    $buf .= "define('DB_USER','$user');"."\n";
    $buf .= "define('DB_PASS','$pass');"."\n";
    //create file
    file_put_contents($file, '<?php'."\n".$buf);
}

function setDeleteConnFile(){
    $file = CONF_APP_DIR_INC.DIRECTORY_SEPARATOR.'conn.php';
    if(file_exists($file)){
        @ unlink($file);
    }
}

function getCamposDeUnaTabla(&$db,$dbname,$tabla,&$error=''){
    $sql="SELECT COLUMN_NAME AS columna, COLUMN_TYPE AS tipo, ORDINAL_POSITION AS orden, IS_NULLABLE AS isnull, COLUMN_DEFAULT AS valor, COLUMN_KEY AS pk, EXTRA as extras
            FROM information_schema.columns WHERE
            table_schema = :db
            AND table_name = :tabla ORDER BY orden";
    //$db = getConnection();
    $rs = $db->prepare($sql);
    $rs->bindValue(':db', $dbname); 
    $rs->bindValue(':tabla', $tabla); 
    try{
        $rs->execute(); 
    } catch (Exception $ex) {
        $error='Error sql= '.$ex->getMessage();
        return false;
    }
    //devuelve array con todos los resultados
    $res = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs=null;
    $cad='';
    foreach ($res as $c){
        $cad .= '<li><b>'.$c['columna'].'</b>, '.$c['tipo'];
        if($c['pk']){
            if($c['pk']=='PRI'){
                $cad .= ', Primary Key';
            }elseif($c['pk']=='UNI'){
                $cad .= ', Unique Key';
            }else{
                $cad .= ', '.$c['pk'];
            }
        }elseif($c['isnull']=='YES'){
            $cad .= ', NULL';
        }else{
            $cad .= ', required';
        }
        if($c['extras']){
            $cad .= ', '.$c['extras'];
        }
        if($c['valor']){
            $cad .= ', default='.$c['valor'];
        }
        $cad .= '</li>';
    }
    return $cad;
}

function getTablasDeUnaBaseDeDatos(&$db,$dbname,$prefijo='',&$error=''){
    if($prefijo != ''){
        $sql="SELECT table_name AS nombre FROM information_schema.tables WHERE table_schema = :db AND table_name LIKE '$prefijo%'";
    }else{
        $sql="SELECT table_name AS nombre FROM information_schema.tables WHERE table_schema = :db";
    }
    $rs = $db->prepare($sql);
    $rs->bindValue(':db', $dbname); 
    try{
        $rs->execute(); 
    } catch (Exception $ex) {
        $error='Error sql= '.$ex->getMessage();
        return false;
    }
    $res = $rs->fetchAll(PDO::FETCH_ASSOC);
    $rs=null;
    
    return $res;
}