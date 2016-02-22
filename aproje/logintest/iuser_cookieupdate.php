<?php

class OneFileLoginApplication
{
    private $dlink    = null;             // Database bağlantı linki
    private $islogged = false;            // kullanıcı login oldumu
    public  $mesaj    = "";               // sistem mesajları, hatalar, notlar

      
    private function CreateDbConnection() {
        $cServe = "zaman-online.de";
        $cUname = "zaman-20150311";
        $cPword = "zaman2012online";
        $cDbase = "zaman-20150311";

        $this->dlink = mysqli_connect($cServe, $cUname, $cPword, $cDbase);

        if (mysqli_connect_errno() ) {
            $this->mesaj = "Failed to connect to MySQL: " . mysqli_connect_error();
            return false;
        }
        return true;
    }
      
    public function setCookie() {
        
        $nIuser = isset($_GET["uid"]) ? (int)$_GET["uid"] : 4620 ;  // 4620 test için
       
        if ($nIuser > 0) {
            $this->CreateDbConnection();
        } else {    
            $this->mesaj = "uid = {$nIuser} ";
        }
        
        if (empty($this->mesaj)) {
        
            $nKimlik = 0;
            $cExp    = "";
            $dAtarih = "0000-00-00 00:00:00";
            $dCtarih = "0000-00-00 00:00:00";

            $cSqlStr = "select id, kimlik, exp, atarih, ctarih from iuser  where id = ?" ;

            $qIuser  = mysqli_prepare($this->dlink, $cSqlStr);

            if ($qIuser) {
                mysqli_stmt_bind_param($qIuser, "i", $nIuser);

                if (mysqli_stmt_execute($qIuser)) {


                    mysqli_stmt_bind_result($qIuser, $nId, $nKimlik, $cExp, $dAtarih, $dCtarih);
                    mysqli_stmt_fetch($qIuser);


                    if($nId <> 0) {
                        $nExpire = time()+86400*30 ;
                        $dCtarih = is_null($dCtarih) ? date('Y-m-d') : $dCtarih ;
                                
                        setcookie("atarih", $dAtarih, $nExpire);
                        setcookie("ctarih", $dCtarih, $nExpire);
        
                        $this->mesaj = "OK: $nKimlik, $cExp, $dAtarih, $dCtarih";

                    }else{
                        $this->mesaj = "empty nId $nId Error: " . E_USER_ERROR;
                    }
                } else {
                    $this->mesaj = "mysqli_stmt_execute hatası Error:" . E_USER_ERROR;
                }
            } else {
                $this->mesaj = "mysqli_prepare Hatası Error:" . E_USER_ERROR;
            }
        }
    }    
}

$application = new OneFileLoginApplication();
$application->setCookie();
echo $application->mesaj;