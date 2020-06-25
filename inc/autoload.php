<?php
/*
Aplicacion:     Sirce
Autor:          Dario Soto Diaz
Version:        1.0
Descripcion:    Local Autoload file
Fecha Ini.:     20-09-2019
Fecha Mod.:     20-09-2019
*/

spl_autoload_register('mi_autoloader');
function mi_autoloader($class){
    $base_dir = __DIR__ . '/classes/';
    $file = $base_dir . str_replace('\\', '/',  strtolower($class) ) . '.php';
    if (file_exists($file)) {
        require $file;
    }
}