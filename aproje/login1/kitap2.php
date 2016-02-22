<?php
// include_once("/var/www/vhosts/zaman-online.de/httpdocs/web_zaman/wp-content/themes/2015/functions/class_element.php");
header('Content-Type: text/html; charset=utf-8');

$oApp = new OneFileLoginApplication();
$oApp->getBookXml();


class OneFileLoginApplication {
    
    public  $mesaj    = "";               // sistem mesajları, hatalar, notlar
      
    private function CreateDbConnection() {
        $cServe = "zaman-online.de";
        $cUname = "zaman-20150311";
        $cPword = "zaman2012online";
        $cDbase = "zaman-20150311";

        $oDbase = mysqli_connect($cServe, $cUname, $cPword, $cDbase);

        if (mysqli_connect_errno() ) {
            $this->mesaj = "Hata: Failed to connect to MySQL: " . mysqli_connect_error();
        }
        return $oDbase;
    }
      
    public function getBookXml() {
        
        $oDbase = $this->CreateDbConnection();
        
        if (empty($this->mesaj)) {
        
            $cExp    = "";

            $cSqlStr = "select * from book  where id > 0" ;

            $qKitap  = mysqli_prepare($oDbase, $cSqlStr);

            if ($qKitap) {
                //mysqli_stmt_bind_param($qKitap, "i", $nKitap);

                mysqli_stmt_execute($qKitap);
                $result = mysqli_stmt_result_metadata($qKitap);
                
                while ($fieldinfo=mysqli_fetch_fields($result))
                {
                printf("Name: %s\n",$fieldinfo->name);
                printf("Table: %s\n",$fieldinfo->table);
                printf("max. Len: %d\n",$fieldinfo->max_length);
                }

         


/*                    
                    mysqli_stmt_bind_result($qKitap, $cExp);
                    mysqli_stmt_fetch($qKitap);

                     if ($result = mysqli_query($link, $query)) {

        // Get field information for all columns 
        $finfo = mysqli_fetch_fields($result);


        
        
                    if($nId <> 0) {
                        $nExpire = time()+86400*365 ;
                        $dCtarih = is_null($dCtarih) ? date("Y-m-d", $nExpire) : $dCtarih ;
                                
        
                        $this->mesaj = "OK: $nKimlik, $cExp, $dAtarih, $dCtarih";

                    }else{
                        $this->mesaj = "Hata: empty nId $nId Error: " . E_USER_ERROR;
                    }
                } else {
                    $this->mesaj = "Hata:mysqli_stmt_execute hatası Error:" . E_USER_ERROR;
                }
            } else {
                $this->mesaj = "Hata: mysqli_prepare Hatası Error:" . E_USER_ERROR;
            }
            setcookie("mesaj", $this->mesaj, $nExpire);
        }
 * */
 
    }    
}
}
}


/*

function getBookXml() {

    header('Content-Type: text/xml; charset=UTF-8');
    $xml = "<?xml version='1.0' encoding='UTF-8' ?>\n";
    $xml .= "<root>\n";

    $book = new Book();
    foreach ($book->IDs as $id) {
        $book->auswaehlen($id);
        $xml .= "\t<book>\n";
        $xml .= "\t\t<id>$id</id>\n";
        $xml .= "\t\t<title>".$book->obj->title."</title>\n";
        $xml .= "\t\t<description>".$book->obj->description."</description>\n";
        $xml .= "\t\t<descriptionLong>".$book->obj->descriptionLong."</descriptionLong>\n";
        $xml .= "\t\t<authorName>".$book->obj->authorName."</authorName>\n";
        $xml .= "\t\t<date>".$book->obj->date."</date>\n";
        $xml .= "\t\t<url>http://zaman-online.de/kitap/".$book->obj->url."</url>\n";
        $xml .= "\t\t<image>http://zaman-online.de/kitap/".$book->obj->image."</image>\n";
        $xml .= "\t\t<imageBig>http://zaman-online.de/kitap/".$book->obj->imageBig."</imageBig>\n";
        $xml .= "\t\t<mediaType>".$book->obj->mediaType."</mediaType>\n";
        $xml .= "\t\t<type>".$book->obj->type."</type>\n";
        $xml .= "\t\t<publisher>".$book->obj->publisher."</publisher>\n";
        $xml .= "\t\t<language>".$book->obj->language."</language>\n";
        $xml .= "\t\t<size>".$book->obj->size."</size>\n";
        $xml .= "\t\t<page>".$book->obj->page."</page>\n";
        $xml .= "\t\t<ktarih>".$book->obj->ktarih."</ktarih>\n";
        $xml .= "\t\t<grup>".$book->obj->grup."</grup>\n";
        $xml .= "\t</book>\n";
    }

    $xml .= "</root>";
    echo $xml;
}    
*/