<?php
$Asist_World = 1;

$aDbase[-1] = null;
$oAPP = create_APP(isset($cUname) ? $cUname : null,
                   isset($cPword) ? $cPword : null,
                   isset($cHost)  ? $cHost  : null,
                   isset($cDbase) ? $cDbase : null);

include_once("$ASIS_T/_class/data_mysql.php");
class_alias("clsMysql", "clsApp");

/*
function connect_datab($nDbase){
	global $ASIS_T,$aDbase,$oAPP;

	if(isset($aDbase[$nDbase]))return $aDbase[$nDbase];

	$res=mysqli_query($oAPP->dblink, "select * from asist.datab where id=$nDbase");
	$oDB=mysqli_fetch_object($res);
	$oDB->CUR_II=0;
	mysqli_free_result($res);
	include_once("$ASIS_T/_class/data_$oDB->cls.php");
	$func="connect_$oDB->cls";
	$oDB->dblink=$func($oDB->pars);
	$oDB->utf8=preg_match("/utf-8|utf8/i",$oDB->pars);
	$aDbase[$nDbase]=$oDB;
	return $oDB;
}

function CUR_II($nDbase){
	$oDB=connect_datab($nDbase);
	$oDB->CUR_II++;
	return "CUR_$oDB->CUR_II";
}
*/


function create_APP($cUname, $cPword, $cHost, $cDbase){
    global $aDbase;
    
    $nDbase = -1;

    if (isset($aDbase[$nDbase])) {
        return $aDbase[$nDbase];
    }

    $oAPP=(object)array();
    $oAPP->host  = $cHost;
    $oAPP->uname = $cUname;
    $oAPP->pword = $cPword;
    $oAPP->dbase = $cDbase;

    // $oAPP->CUR_II = 0;

    $oAPP->id     = -1;
    $oAPP->exp    = "Default";
    $oAPP->cls    = "app";
    $oAPP->pars   = "";
    $oAPP->fro_s  = ".";
    $oAPP->dblink = null;

    $oAPP->dblink = mysqli_connect($oAPP->host, $oAPP->uname, $oAPP->pword) or die("cannot connect");

    mysqli_select_db($oAPP->dblink, "$oAPP->dbase") or die("cannot select DB $oAPP->host:$oAPP->dbase");
    mysqli_query($oAPP->dblink, "set character set 'utf8' ");
    $aDbase[$nDbase] = $oAPP;
    $oAPP->utf8 = true;
    
    return $oAPP;
}
?>