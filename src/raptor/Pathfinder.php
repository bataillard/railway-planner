<?php


namespace src\raptor;


use src\models\DataLoader;
use src\models\StopModel;

class Pathfinder
{
    private const MAX_ROUNDS = 50;

    private $dl;

    private $routes;
    private $routes_map;
    private $stop;
    private $track_models;
    private $track_map;

    private $tracks = [];
    private $tracks_map = [];
    private $marked_tracks = [];
    private $origin_track = null;
    private $destination_track = null;
    private $departure_time = null;
    private $route_scanner = null;
    private $results = null;

    public function __construct()
    {
        $this->dl = DataLoader::getInstance();
        $data = $this->dl->getBaseData();
        $this->routes = $data["routes"];
        $this->routes_map = $data["routes_map"];
        $this->stop = $data["stops"];
        $this->track_models = $data["tracks"];
    }

    private function initializeData(StopModel $origin_model, StopModel $dest_model, int $dep_hour, int $dep_minute)
    {
        $this->departure_time = Time::from($dep_hour, $dep_minute);

        $origin_track = $origin_model->getTracks()[0];
        $destination_track = $dest_model->getTracks()[0];

        foreach ($this->track_models as $track_model) {
            $track = new Track($track_model, $this->departure_time);
            array_push($this->tracks, $track);
            $this->tracks_map[$track->getKey()] = $track;

            if ($origin_track->getKey() == $track->getKey()) {
                $this->origin_track = $track;
            }

            if ($destination_track->getKey() == $track->getKey()) {
                $this->destination_track = $track;
            }
        }

        $this->marked_tracks = $this->tracks;
        $this->route_scanner = new RouteScanner($this->routes, $this->routes_map);
    }

    public function pathfind(StopModel $origin_model, StopModel $destination_model, int $dep_hour, int $dep_minute)
    {
        echo "Starting pathfinding from " . $origin_model->getKey()
            . " to " . $destination_model->getKey()
            . " at time " . $dep_hour . ":" . $dep_minute . "\n";

        $this->initializeData($origin_model, $destination_model, $dep_hour, $dep_minute);
        $round = 1; // Round 0 was completed in initialization

        do {
            echo "Start of pathfinding round " . $round . "\n";

            $result_set = [];

            $timit = microtime(true);
            echo "\tRound " . $round . ": start of phase 1\n" ;

            // Accumulate routes serving marked tracks from previous round
            foreach ($this->marked_tracks as $marked_track) {
                foreach ($marked_track->routesServing() as $route) {
                    $route_key = $route->getKey();

                    // If current track comes before the stop in the result set on the route, substitute it
                    if (array_key_exists($route_key, $result_set)
                            && $route->trackComesBefore($marked_track, $result_set[$route_key])) {
                        $result_set[$route_key] = $marked_track;
                    } else {
                        $result_set[$route->getKey()] = $marked_track;
                    }
                }
            }

            echo "\tRound " . $round . ": end of phase 1 - time taken: " . (microtime(true) - $timit) . "\n";

            $this->marked_tracks = [];

            $timit = microtime(true);
            echo "\tRound " . $round . ": start of phase 2\n" ;

            // Traverse each route
            foreach ($result_set as $route_id => $start_track) {
                $route = $this->routes_map[$route_id];
                $current_trip = null;
                $boarded_track = null;

                $route_tracks = $route->getTracks();
                $size = count($route_tracks);
                $offset = $route->getOffsetFrom($start_track);

                for ($i = $offset; $i < $size; $i++) {
                    $next_track_model = $route_tracks[$i];
                    $track = $this->tracks_map[$next_track_model->getKey()];
                    $previous_arrival = $track->getTimeAtRound($round - 1);

                    $improved = $track->improveTrip($round, $current_trip, $this->destination_track, $boarded_track);
                    if ($improved) {
                        array_push($this->marked_tracks, $track);
                    }

                    if (!$current_trip || $previous_arrival->isEarlier($track->timeAtTrip($current_trip))) {
                        $current_trip = $this->route_scanner->getEarliestTrip($route, $track, $previous_arrival);
                        $boarded_track = $track;
                    }
                }
            }

            echo "\tRound " . $round . ": end of phase 2 - time taken: " . (microtime(true) - $timit) . "\n";

            $timit = microtime(true);
            echo "\tRound " . $round . ": start of phase 3\n" ;


            // Look at foot-paths
            foreach ($this->marked_tracks as $marked_track) {
                foreach ($marked_track->getTransfers() as $transfer) {
                    $to_track = $this->tracks_map[$transfer->getToTrack()->getKey()];
                    $time = $marked_track->getTimeAtRound($round)->add($transfer->getTransferTime());

                    $improved = $to_track->improveTransfer($round, $time, $marked_track);
                    if ($improved) {
                        array_push($this->marked_tracks, $to_track);
                    }
                }
            }

            echo "\tRound " . $round . ": end of phase 3 - time taken: " . (microtime(true) - $timit) . "\n";
            echo "Finished pathfinding round " . $round . "\n";
            $round++;
        } while (!empty($marked_track) && $round < self::MAX_ROUNDS);

        return new PathfinderResults($round, $this->tracks,
            $this->origin_track, $this->destination_track, $this->departure_time);
    }
}