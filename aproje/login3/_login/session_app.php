<?php
    $cAtime = time();
    $_SESSION["Ctime"] = $cAtime;
    $nLogin = $_SESSION["Login"];
    $nSure  = $cAtime-$_SESSION["Atime"];

    if(!empty($nLogin)){
            $strSql = "update asist.login set czaman=from_unixtime($cAtime),sure=$nSure where id=$nLogin";
            $res=mysqli_query($oAPP->dblink, $strSql);
    }
?>