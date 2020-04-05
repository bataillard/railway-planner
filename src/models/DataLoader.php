<?php

namespace src\models;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use mysqli_sql_exception;
use src\models\interfaces\DataObjectBuilder;
use src\raptor\Time;

class DataLoader
{
    private const DB_CFG_PATH = "/../config/db_cfg.ini";
    private $LOG_PATH = __DIR__ . "/../../log/db.log";

    private const ROUTE_QUERY = "SELECT * FROM Route";
    private const STOP_QUERY = "SELECT * FROM Stop";
    private const TRACK_QUERY = "SELECT * FROM Track";
    private const TRANSFER_QUERY = "SELECT * FROM Transfer";
    private const TRIPS_QUERY = "
        SELECT route_id, departure_time, 
               TIME_FORMAT(departure_time, '%H') AS departure_hours,
               TIME_FORMAT(departure_time, '%i') AS departure_minutes,
               trip_id, service_id, trip_name, wheelchair_accessible, bikes_allowed
        FROM Trip T NATURAL JOIN StopTime ST
        WHERE stop_sequence = 1
        GROUP BY route_id, departure_time, departure_hours, departure_minutes, trip_id, service_id, trip_name, 
                 wheelchair_accessible, bikes_allowed
        ORDER BY departure_time";

    private const ROUTE_TRACKS_QUERY = "
        WITH SingleTripPerRoute AS (
            SELECT RMain.route_id, TMain.trip_id
            FROM Route RMain NATURAL JOIN Trip TMain INNER JOIN (
                    SELECT route_id, GROUP_CONCAT(trip_id ORDER BY trip_id) AS grouped_trip_ids
                    FROM Route R NATURAL JOIN Trip T
                    GROUP BY R.route_id) GroupedRouteTrips
                ON RMain.route_id = GroupedRouteTrips.route_id 
                   AND FIND_IN_SET(TMain.trip_id, grouped_trip_ids) BETWEEN 1 AND 1)
        SELECT DISTINCT STPR.route_id, ST.stop_id, ST.track, ST.stop_sequence
        FROM SingleTripPerRoute STPR NATURAL JOIN StopTime ST
        ORDER BY STPR.route_id, ST.stop_sequence;
    ";

    private const ROUTE_STOPTIMES_QUERY = "
        SELECT DISTINCT stop_id, track, stop_sequence, arrival_time, departure_time
        FROM Route R NATURAL JOIN Trip T NATURAL JOIN StopTime ST
        WHERE route_id LIKE ?
        ORDER BY stop_sequence;
    ";

    private const TRIP_STOPTIMES_QUERY = "
        SELECT ST.*
        FROM Trip T NATURAL JOIN StopTime ST
        WHERE trip_id LIKE ?
        ORDER BY ST.stop_sequence;
    ";

    private const TRIP_STOP_ARRIVAL_TIME_QUERY = "
        SELECT DISTINCT TIME_FORMAT(arrival_time, '%H'), TIME_FORMAT(arrival_time, '%i')
        FROM Trip T NATURAL JOIN StopTime ST
        WHERE trip_id LIKE ? AND stop_id LIKE ? AND track LIKE ?;
    ";

    // =====================================================================================================
    private const CLOSEST_STOPS = "
        SELECT stop_id, stop_name, stop_lat, stop_long,
               (2 * 6371 * ASIN(SQRT(
                   POW(SIN((RADIANS(stop_lat) - targ_lat) / 2), 2) +
                   COS(RADIANS(stop_lat)) * COS(targ_lat) * POW(SIN((RADIANS(stop_long) - targ_long) / 2), 2))))
                    AS distance
        FROM Stop CROSS JOIN (SELECT RADIANS(?) AS targ_lat, RADIANS(?) AS targ_long) Target
        HAVING distance < ?
        ORDER BY distance
        LIMIT 0, 25";

    private const SINGLE_ROUTE_TRACKS_QUERY = "
        WITH SingleTripPerRoute AS (
            SELECT RMain.route_id, TMain.trip_id
            FROM Route RMain NATURAL JOIN Trip TMain INNER JOIN (
                    SELECT route_id, GROUP_CONCAT(trip_id ORDER BY trip_id) AS grouped_trip_ids
                    FROM Route R NATURAL JOIN Trip T
                    WHERE route_id = ?
                    GROUP BY R.route_id) GroupedRouteTrips
                ON RMain.route_id = GroupedRouteTrips.route_id 
                   AND FIND_IN_SET(TMain.trip_id, grouped_trip_ids) BETWEEN 1 AND 1)
        SELECT DISTINCT route_id, stop_sequence, stop_name, stop_id, stop_lat, stop_long
        FROM SingleTripPerRoute NATURAL JOIN StopTime NATURAL JOIN Stop
        ORDER BY stop_sequence";

    private const SINGLE_ROUTE = "SELECT * FROM Route WHERE route_id = ?";

    private const CLOSEST_STOPS_NO_POS = "
        SELECT stop_id, stop_name,
               (2 * 6371 * ASIN(SQRT(
                   POW(SIN((RADIANS(stop_lat) - targ_lat) / 2), 2) +
                   COS(RADIANS(stop_lat)) * COS(targ_lat) * POW(SIN((RADIANS(stop_long) - targ_long) / 2), 2))))
                    AS distance
        FROM Stop CROSS JOIN (SELECT RADIANS(?) AS targ_lat, RADIANS(?) AS targ_long) Target
        HAVING distance < ?
        ORDER BY distance
        LIMIT 0, 25";

    private const GET_PASSENGER_QUERY = "SELECT * FROM Passenger where passenger_username LIKE ?;";

    private const ADD_PASSENGER_QUERY = "
        INSERT INTO Passenger (passenger_username, passenger_name, passenger_password) 
        VALUES (?, ?, ?)";

    private const GET_PASSENGER_STOPS = "
        SELECT S.*
        FROM PassengerStop PS NATURAL JOIN Stop S 
        WHERE PS.passenger_id = ?";

    private const GET_SINGLE_STOP = "
        SELECT * 
        FROM Stop 
        WHERE stop_id = ?
    ";

    private const GET_PASSENGER_STOP_SINGLE = "
        SELECT *
        FROM PassengerStop
        WHERE passenger_id = ? && stop_id = ?";

    private const ADD_PASSENGER_STOPS = "
        INSERT INTO PassengerStop 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE stop_id = stop_id";

    private const DELETE_PASSENGER_STOP = "
        DELETE FROM PassengerStop
        WHERE passenger_id = ? && stop_id = ?
    ";

    private const UPDATE_PASSENGER_NAME = "
        UPDATE Passenger
        SET passenger_name = ?
        WHERE passenger_id = ?
    ";

    private const UPDATE_PASSENGER_PASS = "
        UPDATE Passenger
        SET passenger_password = ?
        WHERE passenger_id = ?
    ";

    private const DELETE_PASSENGER = "
        DELETE FROM Passenger
        WHERE passenger_id = ?
    ";


    // =====================================================================================================

    private $db;
    private $log;
    private $data_loaded = false;
    private $route_stoptimes_stmt;
    private $trip_stoptimes_stmt;
    private $trip_stop_arrival_time_stmt;

    private $routes;
    private $routes_map;
    private $stops;
    private $stops_map;
    private $tracks;
    private $tracks_map;
    private $trips;
    private $trips_map;

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new DataLoader();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $cfg = parse_ini_file(dirname(__DIR__) . self::DB_CFG_PATH);

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->db = mysqli_connect($cfg["host"], $cfg["user"], $cfg["password"], $cfg["database"]);
        $this->log = new Logger('DataLoader');
        $this->log->pushHandler(new StreamHandler($this->LOG_PATH, Logger::WARNING));

        $this->route_stoptimes_stmt = $this->db->prepare(self::ROUTE_STOPTIMES_QUERY);
        $this->trip_stoptimes_stmt = $this->db->prepare(self::TRIP_STOPTIMES_QUERY);
        $this->trip_stop_arrival_time_stmt = $this->db->prepare(self::TRIP_STOP_ARRIVAL_TIME_QUERY);
    }

    public function getBaseData()
    {
        if (!$this->data_loaded) {
            $this->loadBaseData();
        }

        return ["routes" => $this->routes, "routes_map" => $this->routes_map,
            "stops" => $this->stops, "tracks" => $this->tracks, "tracks_map" => $this->tracks_map];
    }

    private function loadBaseData()
    {
        $routes_res = $this->loadDataObject(self::ROUTE_QUERY, RouteModel::builder());
        $this->routes = $routes_res["data"];
        $this->routes_map = $routes_res["data_map"];

        $stops_res = $this->loadDataObject(self::STOP_QUERY, StopModel::builder());
        $this->stops = $stops_res["data"];
        $this->stops_map = $stops_res["data_map"];

        $tracks_res = $this->loadDataObject(self::TRACK_QUERY, TrackModel::builder());
        $this->tracks = $tracks_res["data"];
        $this->tracks_map = $tracks_res["data_map"];

        $trips_res = $this->loadDataObject(self::TRIPS_QUERY, TripModel::builder());
        $this->trips = $trips_res["data"];
        $this->trips_map = $trips_res["data_map"];

        echo "Finished initial loading\n";

        $this->linkTrackStops();
        $this->addTrackTransfers();
        $this->linkRouteTracks();
        $this->addRouteTrips();

        echo "Loading and linking complete\n";
        $this->data_loaded = true;
    }

    // ===================================================================================================

    private function resultToString($table)
    {
        if ($table === null) {
            return "null";
        }

        $str = "";
        foreach ($table as $row) {
            foreach ($row as $column) {
                $str .= $column . ",";
            }
            $str .= "|";
        }

        return $str;
    }

    private function simpleNoReturnResultQuery(string $query, string $format, string ...$values): bool
    {
        $stmt = null;
        $result = false;

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param($format, ...$values);
            $result = $stmt->execute();
        } catch (mysqli_sql_exception $err) {
            $this->log->error($err->getMessage());
        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }

        $this->log->debug("Executed " . $query . " with result " . $result . " and params ". implode($values));

        return $result;
    }

    private function simpleGetQuery(string $query, string $format, string ...$params)
    {
        $stmt = null;
        $result = [];

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bind_param($format, ...$params);
            $stmt->execute();

            $sql_res = $stmt->get_result();
            if ($sql_res && $sql_res->num_rows > 0) {
                $result = $sql_res->fetch_all(MYSQLI_BOTH);
            }
        } catch (mysqli_sql_exception $err) {
            $this->log->error($err->getMessage());
            $result = null;
        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }

        $this->log->debug("Fetched: " . $this->resultToString($result) . " from query " . $query);

        return $result;
    }

    // ===================================================================================================

    private function getStopFromDB(string $stop_id)
    {
        $res = $this->simpleGetQuery(self::GET_SINGLE_STOP, "s", $stop_id);
        if (count($res) > 0) {
            return $res[0];
        }

        return null;
    }

    public function getClosestStops(float $lat, float $lon, float $max_dist, bool $show_pos)
    {
        $results = [];
        $query = $show_pos ? self::CLOSEST_STOPS : self::CLOSEST_STOPS_NO_POS;
        $res = $this->simpleGetQuery($query, "ddd", $lat, $lon, $max_dist);
        foreach ($res as $row) {
            $r = ["id" => $row["stop_id"], "name" => $row["stop_name"], "distance" => $row["distance"]];
            if ($show_pos) {
                $r["latitude"] = $row["stop_lat"];
                $r["longitude"] = $row["stop_long"];
            }
            array_push($results, $r);
        }

        return $results;
    }

    public function getPassenger(string $passenger_username)
    {
        $res = $this->simpleGetQuery(self::GET_PASSENGER_QUERY, "s", $passenger_username);
        if (count($res) > 0) {
            $res = $res[0];
            return ["id" => $res["passenger_id"], "username" => $res["passenger_username"],
                "name" => $res["passenger_name"], "password" => $res["passenger_password"]];
        }

        return null;
    }

    public function addPassenger(string $username, string $name, string $password): bool
    {
        return $this->simpleNoReturnResultQuery(self::ADD_PASSENGER_QUERY, "sss",
            $username, $name, $password);
    }

    public function updatePassengerPassword(string $id, string $new_password)
    {
        return $this->simpleNoReturnResultQuery(self::UPDATE_PASSENGER_PASS, "si",
            $new_password, $id);
    }

    public function updatePassengerName(string $passenger_id, string $passenger_name)
    {
        return $this->simpleNoReturnResultQuery(self::UPDATE_PASSENGER_NAME, "si",
            $passenger_name, $passenger_id);
    }

    public function deletePassenger(string $passenger_id)
    {
        return $this->simpleNoReturnResultQuery(self::DELETE_PASSENGER, "i", $passenger_id);
    }

    public function addPassengerStop(string $passenger_id, string $stop_id): bool
    {
        return $this->simpleNoReturnResultQuery(self::ADD_PASSENGER_STOPS, "is",
            $passenger_id, $stop_id);
    }

    public function getPassengerStops(int $passenger_id)
    {
        return $this->simpleGetQuery(self::GET_PASSENGER_STOPS, "i", $passenger_id);
    }

    public function deletePassengerStop(int $passenger_id, string $stop_id)
    {
        return $this->simpleNoReturnResultQuery(self::DELETE_PASSENGER_STOP, "is",
            $passenger_id, $stop_id);
    }

    public function findThroughAllStops($stop_ids) {
        $count = count($stop_ids);
        $clean_stops = "";
        foreach ($stop_ids as $stop_id) {
            $clean_stops .= '"' . $this->db->escape_string($stop_id) . '",';
        }
        $clean_stops = rtrim($clean_stops, ",");

        // Efficient division in MySQL! \o/
        $query = "                
            SELECT route_type, route_name, route_id 
            FROM Route 
            WHERE route_id IN 
                (SELECT DISTINCT route_id
                FROM Trip NATURAL JOIN StopTime
                WHERE stop_id IN ($clean_stops)
                GROUP BY trip_id
                HAVING COUNT(stop_id) = $count)
            ORDER BY RAND()
            LIMIT 50;
        ";

        try {
            $result = $this->db->query($query);
            $routes = [];
            while ($row = $result->fetch_assoc()) {
                array_push($routes, $row);
            }
        } catch (mysqli_sql_exception $err) {
            $this->log->error($err->getMessage());
            $routes = null;
        }

        return $routes;
    }

    public function getRoute(string $route_id)
    {
        return $this->simpleGetQuery(self::SINGLE_ROUTE, "s", $route_id);
    }

    public function getSingleRouteTracks(string $route_id)
    {
        return $this->simpleGetQuery(self::SINGLE_ROUTE_TRACKS_QUERY, "s", $route_id);
    }

    public function joinRoutesOn($route_ids, $join)
    {
        $clean_routes = "";
        foreach ($route_ids as $route_id) {
            $clean_routes .= '"' . $this->db->escape_string($route_id) . '",';
        }
        $clean_routes = rtrim($clean_routes, ",");

        $group_by = "agency_name, route_type, route_name, route_id";
        $order_by = "";
        $select = "route_type, route_name, route_id, agency_name";
        if ($join == "Trip") {
            $select = "route_type, route_name, route_id, trip_id, trip_name, headsign, MIN(departure_time) 
                AS earliest_departure";
            $join = "Trip NATURAL JOIN TripName NATURAL JOIN StopTime";
            $group_by = "route_type, route_name, route_id, trip_id, trip_name, headsign";
            $order_by = "ORDER BY earliest_departure";
        }


        $query = "         
            SELECT DISTINCT $select
            FROM Route NATURAL JOIN $join 
            WHERE route_id IN ($clean_routes)
            GROUP BY $group_by
            $order_by
            LIMIT 30;";


        try {
            $result = $this->db->query($query);
            $res = [];
            while ($row = $result->fetch_assoc()) {
                array_push($res, $row);
            }
        } catch (mysqli_sql_exception $err) {
            $this->log->error($err->getMessage());
            $res = null;
        }

        return $res;
    }


    // ====================================================================================================


    public function getStop(string $stop_id) {
        return $this->data_loaded ? $this->stops_map[$stop_id] : $this->getStopFromDB($stop_id);
    }

    public function loadRouteStopTimes(RouteModel $route)
    {
        $route_id = $route->getKey();
        $this->route_stoptimes_stmt->bind_param("s", $route_id);
        $this->route_stoptimes_stmt->bind_result($trip_id, $stop_id, $track,
            $stop_sequence, $arrival_time, $departure_time);
        $this->route_stoptimes_stmt->execute();

        $stoptimes = [];

        while ($this->route_stoptimes_stmt->fetch()) {
            array_push($trips,
                new StopTimeModel($trip_id, $stop_id, $track, $stop_sequence, $arrival_time, $departure_time));
        }

        return $stoptimes;
    }

    public function loadTripStopTimes(TripModel $trip)
    {
        $trip_id = $trip->getKey();
        $this->trip_stoptimes_stmt->bind_param("s", $trip_id);
        $this->trip_stoptimes_stmt->bind_result($trip_id, $stop_id, $track,
            $stop_sequence, $arrival_time, $departure_time);
        $this->trip_stoptimes_stmt->execute();

        $stoptimes = [];

        while ($this->trip_stoptimes_stmt->fetch()) {
            array_push($trips,
                new StopTimeModel($trip_id, $stop_id, $track, $stop_sequence, $arrival_time, $departure_time));
        }


        return $stoptimes;
    }

    public function loadTripArrivalAtTrack(TripModel $trip, $track): Time
    {
        $trip_id = $trip->getKey(); $stop_id = $track->getStopId(); $track_id = $track->getTrackId();
        $this->trip_stop_arrival_time_stmt->bind_param("sss", $trip_id,$stop_id, $track_id);
        $this->trip_stop_arrival_time_stmt->bind_result($hours, $minutes);
        $this->trip_stop_arrival_time_stmt->execute();

        $this->trip_stop_arrival_time_stmt->fetch();

        return Time::from($hours, $minutes);
    }


    private function loadDataObject(string $query, DataObjectBuilder $builder)
    {
        $result = $this->db->query($query);

        $data = [];
        $data_map = [];

        while ($row = $result->fetch_assoc()) {
            $object = $builder::build($row, $this);

            array_push($data, $object);
            $data_map[$object->getKey()] = $object;
        }

        return ["data" => &$data,"data_map" => &$data_map];
    }

    private function linkTrackStops()
    {
        foreach ($this->tracks as $track) {
            $stop = $this->stops_map[$track->getStopId()];

            $track->setStop($stop);
            $stop->addTrack($track);
        }
    }

    private function addTrackTransfers()
    {
        $result = $this->db->query(self::TRANSFER_QUERY);

        while ($row = $result->fetch_assoc()) {
            $from_track_id = TrackModel::constructKey($row["from_stop_id"], $row["from_track"]);
            $to_track_id = TrackModel::constructKey($row["to_stop_id"], $row["to_track"]);

            $from_track = $this->tracks_map[$from_track_id];
            $to_track = $this->tracks_map[$to_track_id];

            $from_track->addTransfer($to_track, $row["transfer_time"]);
        }
    }

    private function linkRouteTracks()
    {
            $result = $this->db->query(self::ROUTE_TRACKS_QUERY);

            while ($row = $result->fetch_assoc()) {
                $route = $this->routes_map[$row["route_id"]];
                $track_key = TrackModel::constructKey($row["stop_id"], $row["track"]);
                $track = $this->tracks_map[$track_key];

                $route->addTrack($track);
                $track->addRoute($route);
            }
    }

    private function addRouteTrips()
    {
        foreach ($this->trips as $trip) {
            $route = $this->routes_map[$trip->getRouteId()];
            $route->addTrip($trip);
        }
    }
}

