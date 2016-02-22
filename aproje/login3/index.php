<?php
    $ROOT_D = str_replace("\\","/",dirname($_SERVER['PHP_SELF']));
    $ROOT_D = $ROOT_D=="." ? "" : "$ROOT_D";
    $ROOT_D = $ROOT_D=="/" ? "" : "$ROOT_D";

    $SERV_P = $_SERVER['SERVER_NAME'];
    if ($_SERVER['SERVER_PORT'] != "80") {
        $SERV_P .= ":" . $_SERVER['SERVER_PORT'];
    }
    
    $REAL_P = dirname($_SERVER['SCRIPT_FILENAME']);
    $REAL_P = dirname($REAL_P);
    $ASIS_T = "$REAL_P/APPLOGIN";
	
    session_start();

    $cAtime = time();
    $_SESSION["Auth"]  = "Evet";
    $_SESSION["Atime"] = $cAtime;
    $_SESSION["Ctime"] = $cAtime;
    $_SESSION["Login"] = 0;

    //	Driver={MySQL ODBC 5.2 ANSI Driver};
    //	Port=3306;
    //	Server=zaman-online.de;
    //	Database=zaman-20150311;
    //	Uid=zaman-20150311;
    //	Pwd=zaman2012online;
    //	Charset=latin5

            /*

    $_user_="root";
    $_pass_="altun-2015";
    $_host_="10.2.10.15";
    $_data_="";
            */

    $cUname = "zaman-20150311";
    $cPword = "zaman2012online";
    $cHost  = "zaman-online.de";
    $cDbase = "zaman-20150311";

    include_once("$ASIS_T/_login/connect_app.php");
    include_once("$ASIS_T/_login/session_app.php");

    //php kodlarİ
    
    $cSqlstr = "select * from iuser";
    $strClass = "cls$oAPP->cls";
    $CCC = new $strClass($oAPP->dblink, $cSqlstr);

    $CCC->open();
//   $CCC->open(null,null);

    include_once("$ASIS_T/_login/close_app.php");
?>
