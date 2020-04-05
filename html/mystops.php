<?php

namespace MyStops;
use src\models\DataLoader;

require_once "../src/includes/autoload.php";

// ====================================
// EXPORTED VARIABLES/CONSTANTS

const SUBMIT_STOPS_ARG = "submitted_stops";
const SUBMIT_STOPS_ACTION_ID = "submit_stop";
const MAX_STOP_ID_LENGTH = 100;
const MAX_ROUTE_ID_LENGTH = 200;

const MY_STOPS_SELECT = "my_stops_select";
const MY_STOPS_ACTION_ID = "selected_action";
const MY_STOPS_ACTION_DELETE = "delete_stops";
const MY_STOPS_ACTION_FIND_ROUTES = "find_routes";

const ROUTE_SELECT = "route_select";
const ROUTE_SELECT_ACTION_ID = "routes";
const ROUTE_SELECT_ACTION_FIND_STOPS = "find_stops";

$stop_save_error = ""; // Used in findstops.php
function getStopSaveErr() { global $stop_save_error; return $stop_save_error; }

// ====================================
// SESSION START HANDLING

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ====================================
// REQUEST FUNCTIONS

function sanitize_data_object($data, $name, $max_length, callable $predicate)
{
    $valid = true;
    $stops = [];
    $stop_errs = [];

    // Sanitize input and check that stops exist
    foreach ($data as $unclean_stop) {
        $stop = $stop_err = "";
        $valid &= test_input($unclean_stop, $stop, $stop_err, $name,
            function ($s) use ($predicate) {
                return 0 < strlen($s)
                    && strlen($s) < MAX_STOP_ID_LENGTH
                    && call_user_func_array($predicate, [$s]);
            });
        array_push($stops, $stop);
        array_push($stop_errs, $stop_err);
    }

    return ["valid" => $valid, "data" => $stops, "errors" => $stop_errs];
}

function handle_stop_submit($data)
{
    global $stop_save_error;

    $sanitized = sanitize_data_object($data, "Stop", MAX_STOP_ID_LENGTH,
        function ($s) { return !empty(DataLoader::getInstance()->getStop($s)); });
    $valid = $sanitized["valid"];
    $stops = $sanitized["data"];
    $stop_errs = $sanitized["errors"];

    $stop_save_error = array_merge([], array_map(function ($err) {return "<p>$err</p>";}, $stop_errs));

    if ($valid) {
        // Check not already in Passenger Stops
        // Add stops to Passenger Stops
        foreach ($stops as $stop) {
            if (!DataLoader::getInstance()->addPassengerStop($_SESSION["id"], $stop)) {
                $stop_save_error .= "<p>Something went wrong while saving your stops, please try again later</p>";
            } else {
                $stop_save_error = "";
            }
        }
    }
}

$my_stops_status_err = "";
function handle_stop_delete($data)
{
    global $my_stops_status_err;

    $sanitized = sanitize_data_object($data, "Stop", MAX_STOP_ID_LENGTH,
        function ($s) { return !empty(DataLoader::getInstance()->getStop($s)); });
    $valid = $sanitized["valid"];
    $stops = $sanitized["data"];
    $stop_errs = $sanitized["errors"];

    foreach ($stop_errs as $err) {
        $my_stops_status_err .= "<p>$err</p>";
    }

    if ($valid) {
        foreach ($stops as $stop) {
            if (!DataLoader::getInstance()->deletePassengerStop($_SESSION["id"], $stop)) {
                $my_stops_status_err .= "<p>Couldn't delete these stops, please try again later</p>";
            } else {
                $my_stops_status_err = "";
            }
        }
    }
}


$route_table = "";
function handle_stops_find_routes($data) {
    global $my_stops_status_err, $route_table;

    $sanitized = sanitize_data_object($data, "Stop", MAX_STOP_ID_LENGTH,
        function ($s) { return !empty(DataLoader::getInstance()->getStop($s)); });
    $valid = $sanitized["valid"];
    $stop_ids = $sanitized["data"];
    $stop_errs = $sanitized["errors"];

    foreach ($stop_errs as $err) {
        $my_stops_status_err .= "<p>$err</p>";
    }

    if ($valid) {
        $res = DataLoader::getInstance()->findThroughAllStops($stop_ids);
        if ($res === null) {
            $my_stops_status_err = "<p>An error occured while finding the routes, please try again later</p>";
        } else if (empty($res)) {
            $my_stops_status_err = "<p>There are no routes connecting all these stops</p>";
        } else {
            $route_rows = [["", "Type", "Name", "ID"]];

            foreach ($res as $row) {
                $checkbox = buildCheckboxForGroup(ROUTE_SELECT, $row["route_id"], "");
                array_push($route_rows, [$checkbox, $row["route_type"],
                    $row["route_name"], $row["route_id"]]);
            }

            // Build the table
            $table = buildTable($route_rows);
            $self = htmlspecialchars($_SERVER["PHP_SELF"]);
            $route_table = "
            <form method='post' action='$self'>
                <div class='table-scroll-container'>
                    $table
                </div>
                <input type='hidden' id='submit-action' name='". ROUTE_SELECT_ACTION_ID ."' value='true'/>
                <button type='submit' 
                    onclick='$(\"submit-action\").value = \"". ROUTE_SELECT_ACTION_FIND_STOPS ."\"'>
                    Find Stops
                </button>
            </form>";

        }
    }
}

$route_stops_table = "";
$route_stops_err = "";
function handle_routes_find_stops($data) {
    global $route_stops_err, $route_stops_table;


    $sanitized = sanitize_data_object($data, "Route", MAX_ROUTE_ID_LENGTH,
        function ($r) { return !empty(DataLoader::getInstance()->getRoute($r)); });
    $valid = $sanitized["valid"];
    $route_ids = $sanitized["data"];
    $route_errs = $sanitized["errors"];

    echo "OKEKEK";

    foreach ($route_errs as $err) {
        $route_stops_err .= "<p>$err</p>";
    }

    if ($valid) {
        $table = "";
        foreach ($route_ids as $route_id) {
            $res = DataLoader::getInstance()->getSingleRouteTracks($route_id);
            if ($res === null) {
                $route_stops_err = "<p>An error occured while finding the stops, please try again later</p>";
            } else if (empty($res)) {
                $route_stops_err = "<p>There are no stops on route $route_id</p>";
            } else {
                $stop_rows = [["", "","Name", "ID", "Latitude", "Longitude"]];

                foreach ($res as $row) {
                    $checkbox = buildCheckboxForGroup(SUBMIT_STOPS_ARG, $row["stop_id"], "");
                    array_push($stop_rows, [$checkbox, $row["stop_sequence"], $row["stop_name"],
                        $row["stop_id"], number_format($row["stop_lat"], 5), number_format($row["stop_long"], 5)]);
                }

                // Build the table
                $table .= "<div>Stops for route: $route_id";
                $table .= buildTable($stop_rows);
                $table .= "</div>";
            }
        }
        $self = htmlspecialchars($_SERVER["PHP_SELF"]);
        $route_stops_table .= "
                <form method='post' action='$self'>
                    <div class='table-scroll-container'>
                        $table
                    </div>
                    <input type='hidden' id='submit-action' name='". SUBMIT_STOPS_ACTION_ID ."' value='true'/>
                    <button type='submit' >
                        Save stops
                    </button>
                </form>";
    }
}

// ====================================
// REQUEST HANDLING

// Handle save stops request
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST[SUBMIT_STOPS_ARG])
    && !empty($_POST[SUBMIT_STOPS_ACTION_ID])) {
    $data = is_array($_POST[SUBMIT_STOPS_ARG]) ? $_POST[SUBMIT_STOPS_ARG] : [$_POST[SUBMIT_STOPS_ARG]];
    handle_stop_submit($data);
}

// Handle stop deletion request
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST[MY_STOPS_SELECT])
    && $_POST[MY_STOPS_ACTION_ID] == MY_STOPS_ACTION_DELETE) {
    $data = is_array($_POST[MY_STOPS_SELECT]) ? $_POST[MY_STOPS_SELECT] : [$_POST[MY_STOPS_SELECT]];
    handle_stop_delete($data);
}

// Handle stop route find request
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST[MY_STOPS_SELECT])
    && $_POST[MY_STOPS_ACTION_ID] == MY_STOPS_ACTION_FIND_ROUTES) {
    $data = is_array($_POST[MY_STOPS_SELECT]) ? $_POST[MY_STOPS_SELECT] : [$_POST[MY_STOPS_SELECT]];
    handle_stops_find_routes($data);
}


// Handle stop route find request
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST[ROUTE_SELECT])
    && $_POST[ROUTE_SELECT_ACTION_ID] == true) {
    echo "HKJEDFKADJ";
    $data = is_array($_POST[ROUTE_SELECT]) ? $_POST[ROUTE_SELECT] : [$_POST[ROUTE_SELECT]];
    handle_routes_find_stops($data);
}

// ====================================
// MAIN SECTION CODE

// Build My Stops Table
$my_stops_err = "";
$my_stops_table = "You currently have no saved stops";

$passenger_id = $_SESSION["id"];
$stops_res = DataLoader::getInstance()->getPassengerStops($passenger_id);

if (!empty($stops_res)) {
    $stop_rows = [["", "Name", "ID", "Latitude", "Longitude"]];

    foreach ($stops_res as $row) {
        $checkbox = buildCheckboxForGroup(MY_STOPS_SELECT, $row["stop_id"], "");
        array_push($stop_rows, [$checkbox, $row["stop_name"],
            $row["stop_id"], number_format($row["stop_lat"], 5), number_format($row["stop_long"], 5)]);
    }

    // Build the table
    $table = buildTable($stop_rows);
    $self = htmlspecialchars($_SERVER["PHP_SELF"]);
    $my_stops_table = "
            <form method='post' action='$self'>
                <div class='table-scroll-container'>
                    $table
                </div>
                <input type='hidden' id='submit-action' name='". MY_STOPS_ACTION_ID ."' value=''/>
                <button type='submit' 
                    onclick='$(\"submit-action\").value = \"". MY_STOPS_ACTION_FIND_ROUTES ."\"'>
                    Find Routes
                </button>
                <button type='submit' 
                    onclick='$(\"submit-action\").value = \"". MY_STOPS_ACTION_DELETE ."\"'>
                    Delete Stops
                </button>
                <div>$stop_save_error</div>
            </form>";
} else if ($stops_res === null) {
    $my_stops_err .= "<p>Something went wrong while retrieving your saved stops, please try again later</p>";
    $my_stops_table = "";
}

?>

<section>
    <h3>Your saved stops</h3>
    <div>
        <?php echo $my_stops_table?>
    </div>
    <div>
        <?php echo $my_stops_err ?>
        <?php echo $my_stops_status_err; ?>
    </div>
    <div>
        <?php echo $route_table; ?>
    </div>
    <div>
        <?php echo $route_stops_err ?>
        <?php echo $route_stops_table ?>
    </div>
</section>



