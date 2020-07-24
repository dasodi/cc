<?php
/*
Aplicacion:     CreateClassDB
Autor:          Dario Soto Diaz
Version:        1.0
Descripcion:    establece valores iniciales y globales de la aplicacion
Fecha Ini.:     02-06-2020
Fecha Mod.:     21-07-2020
*/

//datos sesion
session_start();
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ALL);
ini_set('display_errors',1);

//configura uso horario de la aplicacion
date_default_timezone_set('Europe/Madrid');

//obtiene nombre del script
$pos=strrpos($_SERVER['PHP_SELF'],'/');
$pagina=substr($_SERVER['PHP_SELF'],$pos+1);

//establece constantes globales de la app
define('CONF_APP_NOMBRE','ClassCreateDB');
define('CONF_APP_COLOR_CORP','0066FF');//azul claro
define('CONF_APP_COLOR_CORP_2','33CCFF');//cyan 33CCFF
define('CONF_APP_VERSION','1.0');
define('CONF_APP_DIR_INC',CONF_APP_DIR.DIRECTORY_SEPARATOR.'inc');
define('CONF_APP_DIR_CLASSES',CONF_APP_DIR_INC.DIRECTORY_SEPARATOR.'classes');
define('CONF_APP_CREATE_SPANISH_METHODS',true);//crea los nombre de los metodos publicos en spanish

//variables notificacion funciones
$m_error='';
$msg='';
$res='';

//establece el comando a ejecutar
if(isset($_GET['cmd'])){
    $comando=$_GET['cmd'];
}elseif(isset($_POST['cmd'])){
    $comando=$_POST['cmd'];
}else{
    $comando=0;
}