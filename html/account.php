<?php

namespace Account;

use src\models\DataLoader;

require_once "../src/includes/autoload.php";

// ====================================
// EXPORTED VARIABLES/CONSTANTS

const MAX_NAME_VARCHARS = 200;
const MAX_NAME_BYTES = 4 * MAX_NAME_VARCHARS;
const MIN_PASS_LENGTH = 8;

const NEW_NAME_ID = "name-change-requested";
const NEW_NAME_ARG = "new_name";


const CHANGE_PASSWORD_ID = "password-change-requested";
const OLD_PASSWORD = "old-pass";
const NEW_PASSWORD = "new-pass";
const CONFIRM_NEW_PASS = "confirm-pass";

const DELETE_ACCOUNT_ID = "delete_account";
const DELETE_ACCOUNT_PASS = "delete_account_pass";

// ====================================
// SESSION START HANDLING

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// =====================================
// REQUEST FUNCTIONS

$submit_status = "";

$new_name = $new_name_err = "";
function handle_name_change($data)
{
    global $new_name, $new_name_err;

    $name = "";

    $valid = test_input($data, $name, $new_name_err, "Full Name",
        function ($n) {return 0 < strlen($n) && strlen($n) < MAX_NAME_BYTES; });

    if ($valid) {
        if (DataLoader::getInstance()->updatePassengerName($_SESSION["id"], $name)) {
            $new_name = "";
            $_SESSION["name"] = $name;
        } else {
            $new_name_err = "Something went wrong, please try again later.";
        }
    }
}

function check_pass($password, &$password_err)
{
    $result = DataLoader::getInstance()->getPassenger($_SESSION["username"]);

    if (empty($result) || empty($result["id"]) || empty($result["password"])) {
        $password_err = "Error while checking password, please try again later";
    }

    $valid = password_verify($password, $result["password"]);

    if (!$valid) {
        $password_err = "Incorrect password.";
    }

    return $valid;
}

$old_password = $new_password = $confirm_new_password = "";
$old_password_err = $new_password_err = $confirm_new_password_err = "";
function handle_password_change($old_data, $new_data, $confirm_data)
{
    global $old_password, $new_password, $confirm_new_password;
    global $old_password_err, $new_password_err, $confirm_new_password_err, $submit_status;

    $valid = test_input($old_data, $old_password, $old_password_err, "Password",
        function ($p) { return true; }, false);

    if ($valid && check_pass($old_password, $old_password_err)) {
        $valid &= test_input($new_data, $new_password, $new_password_err, "New Password",
            function ($p) { return strlen($p) >= MIN_PASS_LENGTH; }, false);
        $valid &= test_input($confirm_data, $confirm_new_password, $confirm_new_password_err,
            "Password confirmation ", function ($p) { return strlen($p) > 0; }, false);

        if ($valid && $new_password == $confirm_new_password) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            if (DataLoader::getInstance()->updatePassengerPassword($_SESSION["id"], $hash)) {
                $submit_status = "Password update sucessful!";
            } else {
                $submit_status = "Could not update password. Please try again later.";
            }
        }

        $new_password = $confirm_new_password =  "";

    }
}

$delete_password = "";
$delete_password_err = "";
function handle_account_deletion($data)
{
    global $delete_password, $delete_password_err, $submit_status;

    $valid = test_input($data, $delete_password, $delete_password_err, "Password",
        function ($p) { return true; }, false);

    if ($valid && check_pass($delete_password, $delete_password_err)) {
        if (DataLoader::getInstance()->deletePassenger($_SESSION["id"])) {
            header("location: logout.php");
            exit;
        } else {
            $submit_status = "Could not delete account";
        }
    }


}

// ====================================
// REQUEST HANDLING

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submit_status = "";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST[NEW_NAME_ID])) {
    handle_name_change($_POST[NEW_NAME_ARG]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST[CHANGE_PASSWORD_ID])) {
    handle_password_change($_POST[OLD_PASSWORD], $_POST[NEW_PASSWORD], $_POST[CONFIRM_NEW_PASS]);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST[DELETE_ACCOUNT_ID])) {
    handle_account_deletion($_POST[DELETE_ACCOUNT_PASS]);
}

?>


<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Railway Planner</title>

    <link rel="stylesheet" type="text/css" href="css/index.css">
    <script src="js/index.js" ></script>
</head>
<body>
    <header>
        <h1>Railway Planner</h1>
        <h2>Hi, <?php echo htmlspecialchars($_SESSION["name"]);?></h2>
        <a href="index.php">Back to main page</a>
        <a href="logout.php" class="button">Log out</a>
    </header>
    <div class="container">
        <main>
            <section>
                <h3>Change your name</h3>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div>
                        <label for="name-input">New Name: </label>
                        <input type="text" name="<?php echo NEW_NAME_ARG; ?>" id="name-input" class=""
                               value="<?php echo $new_name;?>" />
                        <input type="hidden" name="<?php echo NEW_NAME_ID;?>" value="true"/>
                        <span class="help-block"><?php echo $new_name_err; ?></span>
                    </div>
                    <div><button type="submit">Submit</button></div>
                </form>
            </section>
            <section>
                <h3>Change your password</h3>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div>
                        <label for="old-pass-input">Old password</label>
                        <input type="password" name="<?php echo OLD_PASSWORD ?>" id="old-pass-input" class=""
                               value="<?php echo $old_password;?>" />
                        <span class="help-block"><?php echo $old_password_err; ?></span>
                    </div>
                    <div>
                        <label for="new-pass-input">Password</label>
                        <input type="password" name="<?php echo NEW_PASSWORD ?>" id="new-pass-input" class=""
                               value="<?php echo $new_password;?>" />
                        <span class="help-block"><?php echo $new_password_err; ?></span>
                    </div>
                    <div>
                        <label for="confirm_input">Confirm Password</label>
                        <input type="password" name="<?php echo CONFIRM_NEW_PASS ?>" id="confirm_input" class=""
                               value="<?php echo $confirm_new_password;?>" />
                        <input type="hidden" name="<?php echo CHANGE_PASSWORD_ID;?>" value="true"/>
                        <span class="help-block"><?php echo $confirm_new_password_err; ?></span>
                    </div>
                    <div>
                        <button type="submit" class="" >Submit</button>
                    </div>
                </form>
            </section>
            <section>
                <h3>Delete your account</h3>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div>
                        <label for="delete_pass">Confirm Password</label>
                        <input type="password" name="<?php echo DELETE_ACCOUNT_PASS ?>" id="delete-pass" class=""
                               value="<?php echo $delete_password;?>" />
                        <span class="help-block"><?php echo $delete_password_err; ?></span>
                        <input type="hidden" name="<?php echo DELETE_ACCOUNT_ID;?>" value="true"/>
                    </div>
                    <div><button type="submit">Delete</button></div>
                </form>
            </section>
            <div>
                <?php echo $submit_status; ?>
            </div>
        </main>
    </div>
</body>
</html>