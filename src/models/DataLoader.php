<?php

namespace src\models;

use src\models\interfaces\DataObjectBuilder;
use src\raptor\Time;

class DataLoader
{
    private const DB_CFG_PATH = "/../config/db_cfg.ini";

    private const ROUTE_QUERY = "SELECT * FROM Route";
    private const STOP_QUERY = "SELECT * FROM Stop";
    private const TRACK_QUERY = "SELECT * FROM Track";
    private const TRANSFER_QUERY = "SELECT * FROM Transfer";

    private const ROUTE_TRACKS_QUERY = "
        SELECT DISTINCT R.route_id, ST.stop_id, ST.track, ST.stop_sequence
        FROM Route R NATURAL JOIN Trip T NATURAL JOIN StopTime ST
        WHERE route_id LIKE ?
        ORDER BY ST.stop_sequence;
    ";
    private const ROUTE_TRIPS_QUERY = "
        SELECT *
        FROM Trip
        WHERE route_id LIKE ?
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
        SELECT DISTINCT TIME_FORMAT(arrival_time, \"%H\"), TIME_FORMAT(arrival_time, \"%i\")
        FROM Trip T NATURAL JOIN StopTime ST
        WHERE trip_id LIKE ? AND stop_id LIKE ? AND track LIKE ?;
    ";

    private const EARLIEST_TRIP_QUERY = "
        SELECT ST.trip_id, TIME_FORMAT(MIN(ST.departure_time), \"%H\"), TIME_FORMAT(MIN(ST.departure_time), \"%i\")
        FROM Route R NATURAL JOIN Trip T NATURAL JOIN StopTime ST
        WHERE route_id LIKE ? AND ST.departure_time >= ?  AND ST.stop_id LIKE ? AND ST.track LIKE ?
        GROUP BY ST.trip_id, ST.departure_time
        ORDER BY ST.departure_time
        LIMIT 1;
    ";

    private $db;
    private $route_tracks_stmt;
    private $route_trips_stmt;
    private $route_stoptimes_stmt;
    private $trip_stoptimes_stmt;
    private $trip_stop_arrival_time_stmt;
    private $earliest_trip_stmt;

    private $routes;
    private $routes_map;
    private $stops;
    private $stops_map;
    private $tracks;
    private $tracks_map;

    private static $instance = null;

    public static function getInstance() {
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

        $this->route_tracks_stmt = $this->db->prepare(self::ROUTE_TRACKS_QUERY);
        $this->route_trips_stmt = $this->db->prepare(self::ROUTE_TRIPS_QUERY);
        $this->route_stoptimes_stmt = $this->db->prepare(self::ROUTE_STOPTIMES_QUERY);
        $this->trip_stoptimes_stmt = $this->db->prepare(self::TRIP_STOPTIMES_QUERY);
        $this->trip_stop_arrival_time_stmt = $this->db->prepare(self::TRIP_STOP_ARRIVAL_TIME_QUERY);
        $this->earliest_trip_stmt = $this->db->prepare(self::EARLIEST_TRIP_QUERY);

        $this->loadBaseData();
    }

    public function getBaseData() {
        return ["routes" => $this->routes, "routes_map" => $this->routes_map,
            "stops" => $this->stops, "tracks" => $this->tracks, "tracks_map" => $this->tracks_map];
    }

    private function loadBaseData()
    {
        $routes_res = $this->loadDataObject(self::ROUTE_QUERY, RouteModel::builder());;
        $this->routes = &$routes_res["data"];
        $this->routes_map = &$routes_res["data_map"];

        $stops_res = $this->loadDataObject(self::STOP_QUERY, StopModel::builder());
        $this->stops = &$stops_res["data"];
        $this->stops_map = &$stops_res["data_map"];

        $tracks_res = $this->loadDataObject(self::TRACK_QUERY, TrackModel::builder());;
        $this->tracks = &$tracks_res["data"];
        $this->tracks_map = &$tracks_res["data_map"];

        $this->linkTrackStops();
        $this->addTrackTransfers();

        $this->linkRouteTracks();
        $this->addStopTimes();
    }

    public function loadTrips(RouteModel $route)
    {
        $this->route_trips_stmt->bind_param("s", $route->getKey());
        $this->route_trips_stmt->bind_result($trip_id, $service_id, $route_id,
            $trip_name, $wheelchair, $bikes);
        $this->route_trips_stmt->execute();

        $trips = [];

        while ($this->route_trips_stmt->fetch()) {
            $route = $this->routes_map[$route->getKey()];

            array_push($trips,
                new TripModel($trip_id, $service_id, $trip_name, $wheelchair, $bikes, $route, $this));
        }

        return $trips;
    }

    public function getStop(string $stop_id) {
        return $this->stops[$stop_id];
    }

    public function loadRouteStopTimes(RouteModel $route)
    {
        $this->route_stoptimes_stmt->bind_param("s", $route->getKey());
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
        $this->trip_stoptimes_stmt->bind_param("s", $trip->getKey());
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

    public function loadTripArrivalAtTrack(TripModel $trip, TrackModel $track)
    {
        $this->trip_stop_arrival_time_stmt->bind_param($trip->getKey(),$track->getStopId(),
            $track->getTrackId());
        $this->trip_stop_arrival_time_stmt->bind_result($hours, $minutes);
        $this->trip_stop_arrival_time_stmt->execute();

        $this->trip_stop_arrival_time_stmt->fetch();

        $time = Time::from($hours, $minutes);

        return $time;
    }

    public function findEarliestTrip(RouteModel $route, TrackModel $track, Time $start_time)
    {
        $trip = null;
        $time = null;

        $this->earliest_trip_stmt->bind_param("ssss", $route->getKey(),
            $start_time->formatDB(), $track->getStopId(), $track->getTrackId());
        $this->earliest_trip_stmt->bind_result($trip_id, $service_id, $route_id,
            $trip_name, $wheelchair, $bikes, $hours, $minutes);
        $this->earliest_trip_stmt->execute();

        if ($this->earliest_trip_stmt->fetch()) {
            $trip = new TripModel($trip_id, $service_id, $route_id, $trip_name, $wheelchair, $bikes, $this);
            $time = Time::from($hours, $minutes);
        }

        return ["trip" => $trip, "time" => $time];
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
        foreach ($this->routes as $route) {
            $route_id = $route->getKey();

            $this->route_tracks_stmt->bind_param("s", $route_id);
            $this->route_tracks_stmt->bind_result($unused, $stop_id, $track, $sequence);
            $this->route_tracks_stmt->execute();

            $tracks = [];

            while ($this->route_tracks_stmt->fetch()) {
                $track_key = TrackModel::constructKey($stop_id, $track);
                $track = $this->tracks_map[$track_key];

                $track->addRoute($route);
                array_push($tracks, $track);
            }

            $route->setTracks($tracks);
        }
    }

    private function addStopTimes()
    {
        foreach ($this->routes as $route) {
            $route->addDataLoader($this);
        }
    }
}

