
<?php
if (isset($_SESSION["tmptables"])){
	foreach ($_SESSION["tmptables"] as $tmpname)
		mysqli_query($oAPP->dblink,"drop table temp.$tmpname");
}
$sesSure=$_SESSION["sesCZaman"]-$_SESSION["sesAZaman"];
session_destroy();
?>
<html>
<head>
<title>  World Media Web Servisi </title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1254">
</head>
<html>
<body>
<p>Oturum kapatıldı</p>
<p><?php echo "Oturum süresi: $sesSure";?></p>
</body>
</html>