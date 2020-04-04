<?php

use src\models\DataLoader;

require_once "../src/includes/autoload.php";

$username = $name = $password = $confirm_password = "";
$username_err = $name_err = $password_err = $confirm_password_err = $submit_status = "";

const MAX_NAME_VARCHARS = 200;
const MAX_NAME_BYTES = 4 * MAX_NAME_VARCHARS;
const MIN_PASS_LENGTH = 8;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = test_input($_POST["username"], $username, $username_err, "Username",
        function ($u) {return !preg_match('/[^A-Za-z0-9_]/', $u);});

    if ($valid && DataLoader::getInstance()->getPassenger($username)["id"] != null) {
        $username_err = "Username " . $username . " already exists";
        $valid = false;
    }


    $valid &= test_input($_POST["name"], $name, $name_err, "Full Name",
        function ($n) {return 0 < strlen($n) && strlen($n) < MAX_NAME_BYTES; });

    $valid &= test_input($_POST["password"], $password, $password_err, "Password",
        function ($p) { return strlen($p) >= MIN_PASS_LENGTH; }, false);
    $valid &= test_input($_POST["confirm_password"], $confirm_password, $confirm_password_err,
        "Password confirmation ", function ($p) { return strlen($p) > 0; }, false);

    if ($valid && $password == $confirm_password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (DataLoader::getInstance()->addPassenger($username, $name, $hash)) {
            $submit_status = "Registration successful!";
            header("location: login.php");
        } else {
            $submit_status = "Something went wrong. Please try again later.";
        }
    }

    $password = $confirm_password =  "";
}

?>

<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>

    <link rel="stylesheet" type="text/css" href="css/register.css">
    <script src="js/register.js" ></script>
</head>
<body>
    <h1>Railway Planner</h1>

    <main>
        <h2>Register</h2>
        <p>Please fill this form to create an account:</p>
        <div>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div>
                    <label for="user-input">Username</label>
                    <input type="text" name="username" id="user-input" class=""
                           value="<?php echo $username;?>" />
                    <span class="help-block"><?php echo $username_err; ?></span>
                </div>
                <div>
                    <label for="name-input">Full Name</label>
                    <input type="text" name="name" id="name-input" class=""
                           value="<?php echo $name;?>" />
                    <span class="help-block"><?php echo $name_err; ?></span>
                </div>
                <div>
                    <label for="pass-input">Password</label>
                    <input type="password" name="password" id="pass-input" class=""
                           value="<?php echo $password;?>" />
                    <span class="help-block"><?php echo $password_err; ?></span>
                </div>
                <div>
                    <label for="confirm_input">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_input" class=""
                           value="<?php echo $confirm_password;?>" />
                    <span class="help-block"><?php echo $confirm_password_err; ?></span>
                </div>
                <div>
                    <button type="submit" class="" >Register</button>
                    <button type="reset" class="">Reset</button>
                </div>
                <p>Already have an account? <a href="login.php">Login here</a>.</p>
            </form>
        </div>
    </main>
</body>
