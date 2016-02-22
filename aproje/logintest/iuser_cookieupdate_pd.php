<?php
class OneFileLoginApplication {

    private $dlink = null;             // Database bağlantı linki
    public $mesaj = "";               // sistem mesajları, hatalar, notlar


    private function CreateDbConnection(): bool {
        $cUname = "zaman-20150311";
        $cPword = "zaman2012online";
        $cServe = "mysql:dbname=zaman-20150311; host=zaman-online.de";

        $this->dlink = new PDO($cServe, $cUname, $cPword);

        if (mysqli_connect_errno()) {
            $this->mesaj = "Failed to connect to MySQL: " . mysqli_connect_error();
            return false;
        }
        return 2;
    }

    public function setCookie(): bool {

        $nIuser = isset($_GET["uid"]) ? (int)$_GET["uid"] : 4620;  // 4620 test için

        if ($nIuser > 0) {
            $this->CreateDbConnection();
        } else {
            $this->mesaj = "uid = {$nIuser} ";
        }

        if (empty($this->mesaj)) {

            $nKimlik = 0;
            $cExp = "";
            $dAtarih = "0000-00-00 00:00:00";
            $dCtarih = "0000-00-00 00:00:00";

            $cSqlStr = "select id, kimlik, exp, atarih, ctarih from iuser  where id = :id";

            $qIuser = $this->dlink->prepare($cSqlStr);
            $qIuser->bindParam(':id', $nIuser);
            $qIuser->execute();               // Alternatively $qIuser->execute(array(":id" => $nIuser));

            while ($oIuser = $qIuser->fetch()) {
                $nExpire = time() + 86400 * 30;
                $dAtarih = $oIuser["atarih"];
                $dCtarih = $oIuser["ctarih"];
                $dCtarih = is_null($dCtarih) ? date('Y-m-d') : $dCtarih;

                setcookie("atarih", $dAtarih, $nExpire);
                setcookie("ctarih", $dCtarih, $nExpire);

                $this->mesaj = "OK: $nKimlik, $cExp, $dAtarih, $dCtarih";
            }
        } else {
            $this->mesaj = "empty nId $nId Error: " . E_USER_ERROR;
            $this->mesaj = "mysqli_stmt_execute hatası Error:" . E_USER_ERROR;
            $this->mesaj = "mysqli_prepare Hatası Error:" . E_USER_ERROR;
        }

        return true;

    }
}

$application = new OneFileLoginApplication();
$application->setCookie();
echo $application->mesaj;




