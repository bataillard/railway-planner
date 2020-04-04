<?php

namespace MyStops;
use src\models\DataLoader;

require_once "../src/includes/autoload.php";

const SUBMIT_STOPS_ARG = "submitted_stops";
const DELETE_STOPS_ARG = "delete_stops";

const MAX_STOP_ID_LENGTH = 100;

$stop_save_error = ""; // Used in findstops.php
function getStopSaveErr() { global $stop_save_error; return $stop_save_error; }

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}



function sanitize_stop($s)
{
    return 0 < strlen($s)
        && strlen($s) < MAX_STOP_ID_LENGTH
        && DataLoader::getInstance()->getStop($s) != null;
}

function handle_stop_submit()
{

}




// Handle save stops request
if ($_SERVER["REQUEST_METHOD"] == "POST" && !(empty($_POST[SUBMIT_STOPS_ARG]))) {
    $valid = true;
    $stops = [];
    $stop_errs = [];

    $data = is_array($_POST[SUBMIT_STOPS_ARG]) ? $_POST[SUBMIT_STOPS_ARG] : [$_POST[SUBMIT_STOPS_ARG]];

    // Sanitize input and check that stops exist
    foreach ($data as $unclean_stop) {
        $stop = $stop_err = "";
        $valid &= test_input($unclean_stop, $stop, $stop_err, "Stop",
            function ($s) { return sanitize_stop($s);});
        array_push($stops, $stop);
        array_push($stop_errs, $stop_err);
    }

    $stop_save_error = array_merge([], array_map(function ($err) {return "<p>$err</p>";}, $stop_errs));

    if ($valid) {
        // Check not already in Passenger Stops
        // Add stops to Passenger Stops
        foreach ($stops as $stop) {
            if (!DataLoader::getInstance()->addPassengerStop($_SESSION["id"], $stop)) {
                $stop_save_error .= "<p>Something went wrong, please try again later</p>";
            } else {
                $stop_save_error = "";
            }
        }
    }
}

// Build My Stops Table
$my_stops_err = "";
$my_stops_table = "You currently have no saved stops";

$passenger_id = $_SESSION["id"];
$stops_res = DataLoader::getInstance()->getPassengerStops($passenger_id);

if (!empty($stops_res)) {
    $stop_rows = [["", "Distance", "Name", "ID", "Latitude", "Longitude"]];

    foreach ($stops_res as $row) {
        $checkbox = buildCheckboxForGroup(DELETE_STOPS_ARG, $row["stop_id"], "");
        array_push($stop_rows, [$checkbox, $row["stop_name"],
            $row["stop_id"], $row["stop_lat"], $row["stop_long"]]);
    }

    // Build the table
    $table = buildTable($stop_rows);
    $self = htmlspecialchars($_SERVER["PHP_SELF"]);
    $my_stops_table = "
            <form method='post' action='$self'>
                <div class='table-scroll-container'>
                    $table
                </div>
                <button type='submit'>Delete Stops</button>
                <div>$stop_save_error</div>
            </form>";
} else if ($stops_res == null) {
    $my_stops_err .= "<p>Something went wrong, please try again later</p>";
}

?>

<section>
    <h3>Your saved stops</h3>
    <div>
        <?php echo $my_stops_err ?>
        <?php echo $stop_save_error ?>
    </div>
    <div>
        <?php echo $my_stops_table?>
    </div>
</section>



