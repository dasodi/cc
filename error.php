<?php
/*
Aplicacion:     CreateClassDB
Autor:          Dario Soto Diaz
Version:        1.0
Descripcion:    gestiona los errores de la aplicacion
Fecha Mod.:     05-06-2020
*/
session_start();
//parametros
if(isset($_GET["pag"])){
    $pagina=$_GET["pag"];
}else{
    $pagina='';
}
if(isset($_GET["cmd"])){
    $comando=$_GET["cmd"];
}else{
    $comando=0;
}
if(isset($_GET["err_des"])){
    $error=$_GET["err_des"];
}else{
    $error='';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Error</title>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
    <link href="cc/css/adm_est.css" rel="stylesheet" type="text/css">
</head>
<body leftmargin="0" topmargin="0">
    <table width="800" border="1" bordercolor="#ff2222" cellspacing="0" align="center" class="normal">
        <tr> 
            <td bgcolor="#cccccc" align="center"><b>No se ha podido realizar la operacion:</b></td>
        </tr>
        <tr> 
            <td bgcolor="#eeeeee" align="center">
                <?php
                p_MostrarError($pagina, $error, $comando);
                ?>
            </td>
        </tr>
    </table>
</body>
</html>

<?
exit();

//------------------------------------ Funciones pagina -------------------------------------------------------
function p_MostrarError($pagina='',$error='',$comando=0){
    if($error){
        echo '<p><b>Atencion:</b> '.$error.'</p>';
    }else{
        echo '<p>Error no determinado, consulte con el administrador</p>';
    }
    
    if($pagina){
        if($comando){
            echo '<p><button class="normal" onclick="javascript:window.location=\''.$pagina.'?cmd=$comando\'">Volver</button></p>';
        }else{
            echo '<p><button class="normal" onclick="javascript:window.location=\''.$pagina.'\'">Volver</button></b>';
        }
        
    }else{
        echo '<p><button class="normal" onclick="javascript:window.location=\'cc.php\'">Volver</button></p>';
    }
}