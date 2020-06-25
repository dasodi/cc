<?php
//datos sesion
session_start();
error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ALL);
ini_set('display_errors',1);

//configura uso horario de la aplicacion
date_default_timezone_set('Europe/Madrid');

require_once('inc/autoload.php');
require_once('inc/conn.php');

$db=new DB();

$ob=new App($db,4);
if($ob->Error){
    echo 'Error: '.$ob->Error;
    exit();
}
echo 'ID='.$ob->getID().'<br>';
if(!$ob->Delete()){
    echo 'Error: '.$ob->Error.'<br>';
    $ob=null;
    exit();
}
echo 'New ID='.$ob->getID().'<br>';

if(!$ob->Add(date('Y-m-d'),'titulo 4','mensaje 4', 1)){
    echo 'Error: '.$ob->Error.'<br>';
    $ob=null;
    exit();
}
echo 'New ID='.$ob->getID().'<br>';

$ob=null;
echo 'ok';