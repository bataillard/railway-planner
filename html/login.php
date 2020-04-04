<?php

require_once "../src/includes/autoload.php";

use src\models\DataLoader;

session_start();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
$username = $password  = "";
$username_err = $password_err  = $submit_status = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = test_input($_POST["username"], $username, $username_err, "Username",
        function ($u) {return strlen($u) > 0;});

    $valid &= test_input($_POST["password"], $password, $password_err, "Password",
        function ($p) { return true; }, false);

    if ($valid) {
        $result = DataLoader::getInstance()->getPassenger($username);

        if ($result["id"] != null && $result["password"] != null
                && password_verify($password, $result["password"])) {

            $_SESSION["loggedin"] = true;
            $_SESSION["id"] = $result["id"];
            $_SESSION["username"] = $result["username"];
            $_SESSION["name"] = $result["name"];
            header("location: index.php");
        } else {
            $submit_status = "Invalid username or password.";
            $password = "";
        }
    } else {
        $password = "";
    }
}
?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log In</title>

    <link rel="stylesheet" type="text/css" href="css/login.css">
    <script src="js/login.js" ></script>
</head>
<body>
    <h1>Railway Planner</h1>

    <main>
        <h2>Log In</h2>
        <p>Please fill in your credentials to login:</p>
        <div class="container">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div>
                    <label for="user-input">Username</label>
                    <input type="text" name="username" id="user-input" class=""
                           value="<?php echo $username;?>" />
                    <span class="help-block"><?php echo $username_err; ?></span>
                </div>
                <div>
                    <label for="pass-input">Password</label>
                    <input type="password" name="password" id="pass-input" class=""
                           value="<?php echo $password;?>" />
                    <span class="help-block"><?php echo $password_err; ?></span>
                </div>
                <div>
                    <button type="submit" class="button button-submit" >Log In</button>
                </div>
                <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
            </form>
        </div>
    </main>
</body>



