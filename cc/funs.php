<?php
/*
Aplicacion:     CreateClassDB
Autor:          Dario Soto Diaz
Version:        1.0
Descripcion:    funciones globales de la aplicacion
Fecha Ini.:     02-06-2020
Fecha Mod.:     02-06-2020
*/

function getConnection(){

    $db = DbPDO::getInstance();
    $conn = $db->getConnection();

    return $conn;
}
