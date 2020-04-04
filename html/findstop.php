<?php

namespace FindStop;
use src\models\DataLoader;
use const MyStops\SUBMIT_STOPS_ARG;
use function MyStops\getStopSaveErr as getMyStopSaveErr;

require_once "../src/includes/autoload.php";

// uses $stop_save_error from mystops.php
$lat_err = $lon_err = $max_dist_err = "";
$lat = $lon = $stops_table = "";
$max_dist = 0;

// Handle GET request where any of the attributes are set (i.e. someone pressed the find stops button)
if ($_SERVER["REQUEST_METHOD"] == "GET" && !(empty($_GET["lat"]) && empty($_GET["lon"]) && empty($_GET["max_dist"]))) {
    // Sanitize input
    $valid_lat = test_input($_GET["lat"], $lat, $lat_err, "Latitude",
        function ($x) {return is_numeric($x); });
    $valid_lon = test_input($_GET["lon"], $lon, $lon_err, "Longitude",
        function ($x) {return is_numeric($x); });
    $valid_max_dist = test_input($_GET["max_dist"], $max_dist, $max_dist_err, "Maximum distance",
        function ($x) {return is_numeric($x) && in_array($x, [1,5,10,25]); });

    if ($valid_lat && $valid_lon && $valid_max_dist) {
        // Get stops
        $dl = DataLoader::getInstance();
        $results = $dl->getClosestStops($lat, $lon, $max_dist);

        // Build a row of data for buildTable for each stop
        $rows = [["", "Distance", "Name", "ID", "Latitude", "Longitude"]];
        foreach ($results as $row) {
            $dist = number_format($row["distance"], 3) . " km";
            $checkbox = buildCheckboxForGroup(SUBMIT_STOPS_ARG, $row["id"], "");
            array_push($rows, [$checkbox, $dist, $row["name"], $row["id"], $row["latitude"], $row["longitude"]]);
        }

        // Build the table
        $table = buildTable($rows);
        $self = htmlspecialchars($_SERVER["PHP_SELF"]);
        $stop_save_err = getMyStopSaveErr();
        $stops_table = "
            <form method='post' action='$self'>
                <div class='table-scroll-container'>
                    $table
                </div>
                <button type='submit'>Save stops</button>
                <div>$stop_save_err</div>
            </form>";
    }
}
?>

<section>
    <h3>Find Stops by location</h3>
    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
        <div class="container-row">
            <div>
                <button class="close-loc-btn" type="button" onclick="getLocation()">Find my location</button>
            </div>
            <div class="close-grid">
                <label for="close_lat">Latitude:</label>
                <input type="text" name="lat" id="close_lat" value="<?php echo $lat;?>"/>
                <div><?php echo $lat_err;?></div>
                <label for="close_lon">Longitude:</label>
                <input type="text" name="lon" id="close_lon" value="<?php echo $lon;?>"/>
                <div><?php echo $lon_err;?></div>
                <label for="close_dist">Maximum distance:</label>
                <select id="close_dist" name="max_dist">
                    <option value="1">1 km</option>
                    <option value="5">5 km</option>
                    <option value="10" selected>10 km</option>
                    <option value="25">25 km</option>
                </select>
                <div><?php echo $max_dist_err;?></div>
            </div>
        </div>
        <div class="container-row">
            <div><button type="submit">Find closest stops</button></div>
            <div id="close_feedback"></div>
        </div>
    </form>
    <div>
        <?php echo $stops_table?>
    </div>
</section>
