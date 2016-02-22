<?php

class OneFileLoginApplication
{
    private $dlink    = null;             // Database bağlantı linki
    private $islogged = false;            // kullanıcı login oldumu
    public  $mesaj    = "";               // sistem mesajları, hatalar, notlar

    public function __construct() {
        $this->runApplication();
    }
    
    // This is basically the controller that handles the entire flow of the application.
    public function runApplication() {

        // isset returns TRUE if var exists and has value other than NULL, FALSE otherwise. (=nvl)
        $gAction = isset($_GET["action"]) ? $_GET["action"] : "" ; 
                
        if ($gAction == "register") {
            $this->doRegistration();
            $this->showPageRegistration();
        } else {
           
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // check for possible user interactions (login with session/post data or logout)           
            $this->performUserLoginAction();
            
            // show "page", according to user's login status
            if ($this->islogged) {
                $this->showPageLoggedIn();
            } else {
                $this->showPageLoginForm();
            }
        }
    }
       
    private function createDatabaseConnection()
    {
        $cServe = "zaman-online.de";
        $cUname = "zaman-20150311";
        $cPword = "zaman2012online";
        $cDbase = "zaman-20150311";

        $this->dlink = mysqli_connect($cServe, $cUname, $cPword, $cDbase);

        if (mysqli_connect_errno() ) {
            echo "<div> Failed to connect to MySQL: " . mysqli_connect_error()."</div>";
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
     * data or a login with post data will be performed
     */
    private function performUserLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        } elseif (!empty($_SESSION['uname']) && ($_SESSION['islogged'])) {
             $this->islogged = true;
        } elseif (isset($_POST["login"])) {
            $this->doLoginWithPostData();
        }
    }

  
    private function doLoginWithPostData()
    {
        if ($this->checkLoginFormDataNotEmpty()) {
            if ($this->createDatabaseConnection()) {
                $this->checkPasswordCorrectnessAndLogin();
            }
        }
    }

    private function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->user_is_logged_in = false;
        $this->mesaj = "You were just logged out.";
    }


    private function doRegistration()
    {
        if ($this->checkRegistrationData()) {
            if ($this->createDatabaseConnection()) {
                $this->createNewUser();
            }
        }

        return false;
    }

   
    private function checkLoginFormDataNotEmpty()
    {
        if (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->mesaj = "Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->mesaj = "Password field was empty.";
        }
        // default return
        return false;
    }


    private function checkPasswordCorrectnessAndLogin()
    {
        
        $lReturn = false;
        $cUname  = $_POST['user_name'];            
        $nKimlik = (int)$cUname;
        $cPword  = $_POST['user_password'];
        $cPkodu  = $cPword;
        $nId     = 0;
        $cExp    = "";
        
		
        $cSqlStr = 'select id,
                           kimlik, 
                           exp 
                      from iuser  
                     where (indexp = ? and plz = ?)
                        or (kimlik = ? and plz = ?)
                        or (uname  = ? and pword = ?)
                      limit 1';
        
        $qIuser  = mysqli_prepare($this->dlink, $cSqlStr);
        
        if ($qIuser) {
            mysqli_stmt_bind_param($qIuser, "ssisss", $cIndexp, $cPkodu, $nKimlik, $cPkodu, $cUname, $cPword );
            
            if (mysqli_stmt_execute($qIuser)) {

                mysqli_stmt_bind_result($qIuser, $nId, $nKimlik, $cExp);
                mysqli_stmt_fetch($qIuser);
                
                if($nId <> 0) {
                    $_SESSION['exp']      = $cExp;
                    $_SESSION['uname']    = $cUname;
                    $_SESSION['islogged'] = true;

                    $this->islogged       = true;
                    $lReturn              = true;
                    printf ("%n %n %s<br>", $nId, $nKimlik, $cExp);
                }else{
                    $this->mesaj = "empty nId $nId Error: " . E_USER_ERROR;
                }
            } else {
                $this->mesaj = "mysqli_stmt_execute hatası Error:" . E_USER_ERROR;
            }
        } else {
            $this->mesaj = "mysqli_prepare Hatası Error:" . E_USER_ERROR;
        }
        
        /* debug modda bug var 
        mysqli_stmt_close($qIuser);
        mysqli_close($this->dlink);
        */
    }

    
    /*
     * Validates the user's registration input
     * @return bool Success status of user's registration data validation
     */

    private function checkRegistrationData()
    {
        // if no registration form submitted: exit the method
        if (!isset($_POST["register"])) {
            return false;
        }

        if (!empty($_POST['user_name']) && !empty($_POST['user_password_new'])) {
            return true;
        }
        return false;
    }

    /**
     * Creates a new user.
     * @return bool Success status of user registration
     */
    private function createNewUser()
    {
        // remove html code etc. from username and email
        $user_name = htmlentities($_POST['user_name'], ENT_QUOTES);
        $user_email = htmlentities($_POST['user_email'], ENT_QUOTES);
        $user_password = $_POST['user_password_new'];
        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 char hash string.
        // the constant PASSWORD_DEFAULT comes from PHP 5.5 or the password_compatibility_library
        $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = 'SELECT * FROM users WHERE user_name = :user_name OR user_email = :user_email';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':user_email', $user_email);
        $query->execute();

        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->mesaj = "Sorry, that username / email is already taken. Please choose another one.";
        } else {
            $sql = 'INSERT INTO users (user_name, user_password_hash, user_email)
                    VALUES(:user_name, :user_password_hash, :user_email)';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->bindValue(':user_password_hash', $user_password_hash);
            $query->bindValue(':user_email', $user_email);
            // PDO's execute() gives back TRUE when successful, FALSE when not
            // @link http://stackoverflow.com/q/1661863/1114320
            $registration_success_state = $query->execute();

            if ($registration_success_state) {
                $this->mesaj = "Your account has been created successfully. You can now log in.";
                return true;
            } else {
                $this->mesaj = "Sorry, your registration failed. Please go back and try again.";
            }
        }
        // default return
        return false;
    }

    /**
     * Simple demo-"page" that will be shown when the user is logged in.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoggedIn()
    {
        if ($this->mesaj) {
            echo $this->mesaj . "<br/><br/>";
        }

        echo 'Hello ' . $_SESSION['uname'] . ', you are logged in.<br/><br/>';
        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout">Log out</a>';
    }

    /**
     * Simple demo-"page" with the login form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoginForm()
    {
        if ($this->mesaj) {
            echo $this->mesaj . "<br/><br/>";
        }

        echo '<h2>Login</h2>
             <form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '" name="loginform">
             <label for="login_input_username">Username (or email)</label>
             <input id="login_input_username" type="text" name="user_name" required />
             <label for="login_input_password">Password</label>
             <input id="login_input_password" type="password" name="user_password" required />
             <input type="submit"  name="login" value="Log in" />
             </form>
             <a href="' . $_SERVER['SCRIPT_NAME'] . '?action=register">Register new account</a>';
    }

    
    /**
     * Simple demo-"page" with the registration form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageRegistration()
    {
        if ($this->mesaj) {
            echo $this->mesaj . "<br/><br/>";
        }

        echo '<h2>Registration</h2>';

        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '?action=register" name="registerform">';
        echo '<label for="login_input_username">Username (only letters and numbers, 2 to 64 characters)</label>';
        echo '<input id="login_input_username" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required />';
        echo '<label for="login_input_email">User\'s email</label>';
        echo '<input id="login_input_email" type="email" name="user_email" required />';
        echo '<label for="login_input_password_new">Password (min. 6 characters)</label>';
        echo '<input id="login_input_password_new" class="login_input" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" />';
        echo '<label for="login_input_password_repeat">Repeat password</label>';
        echo '<input id="login_input_password_repeat" class="login_input" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" />';
        echo '<input type="submit" name="register" value="Register" />';
        echo '</form>';

        echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '">Homepage</a>';
    }
}

$application = new OneFileLoginApplication();
