<?php


namespace src\raptor;


use src\models\RouteModel;
use src\models\TrackModel;

class RouteScanner
{
    private $routes;
    private $routes_map;
    private $route_scan_positions = [];

    public function __construct($routes, $routes_map)
    {
        $this->routes = $routes;
        $this->routes_map = $routes_map;
    }

    public function getEarliestTrip(RouteModel $route, Track $track, Time $start_time)
    {
        $route_id = $route->getKey();
        $trips = $route->getTrips();

        if (!array_key_exists($route_id, $this->route_scan_positions)) {
            $this->route_scan_positions[$route_id] = count($trips) - 1;
        }

        $last_trip_found = null;

        for ($i = $this->route_scan_positions[$route_id]; $i >= 0; $i--) {
            $trip = $trips[$i];

            // If trip is reachable from start time
            if (!$track->timeAtTrip($trip)->isEarlier($start_time)) {
                $last_trip_found = $trip;
            }

            if (!$last_trip_found || $last_trip_found->getKey() == $trip->getKey()) {
                $this->route_scan_positions[$route_id] = $i;
            }
        }

        return $last_trip_found;
    }


}