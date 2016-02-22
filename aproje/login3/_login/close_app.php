<?php 
    header('Content-Type: text/html; charset=utf-8');

    if (isset($oAPP->dblink)) {
        mysqli_close($oAPP->dblink);
        echo "Uygulama kapatıldı...";
    }
?>