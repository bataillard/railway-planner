<?php
    // Initialize the session
    session_start();

    // Check if the user is logged in, if not then redirect him to login page
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
        header("location: login.php");
        exit;
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
        <a href="account.php" class="button">Manage your account</a>
        <a href="logout.php" class="button">Log out</a>
    </header>
    <div class="container">
        <main>
            <?php include "mystops.php"; ?>
            <?php include "findstop.php"; ?>
        </main>
    </div>
</body>
</html>