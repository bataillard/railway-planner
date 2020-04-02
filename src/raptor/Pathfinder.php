<?php


namespace src\raptor;


use DateTime;
use src\models\DataLoader;
use src\models\StopModel;

class Pathfinder
{
    private $dl;

    private $routes;
    private $routes_map;
    private $stop;
    private $track_models;
    private $track_map;

    private $tracks = [];
    private $tracks_map = [];
    private $marked_tracks = [];
    private $source_track = null;
    private $target_track = null;
    private $departure_time;

    public function __construct()
    {
        $this->dl = DataLoader::getInstance();
        $data = $this->dl->getBaseData();
        $this->routes = $data["routes"];
        $this->routes_map = $data["routes_map"];
        $this->stop = $data["stops"];
        $this->track_models = $data["tracks"];
    }

    private function initializeData(StopModel $source_model, StopModel $target_model, int $dep_hour, int $dep_minute)
    {
        $this->departure_time = Time::from($dep_hour, $dep_minute);

        $source_track = $source_model->getTracks()[0];
        $target_track = $target_model->getTracks()[0];

        foreach ($this->track_models as $track_model) {
            $track = new Track($track_model, $this->departure_time);
            array_push($this->tracks, $track);
            $this->tracks_map[$track->getKey()] = $track;

            if ($source_track->getKey() == $track->getKey()) {
                $this->source_track = $track;
            }

            if ($target_track->getKey() == $track->getKey()) {
                $this->target_track = $track;
            }
        }

        $this->marked_tracks = $this->tracks;
    }

    public function pathfind(StopModel $source_model, StopModel $target_model, int $dep_hour, int $dep_minute)
    {
        $this->initializeData($source_model, $target_model, $dep_hour, $dep_minute);
        $round = 1; // Round 0 was completed in initialization

        do {
            $result_set = [];

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

            $this->marked_tracks = [];

            // Traverse each route
            foreach ($result_set as $route_id => $track) {
                $route = $this->routes_map[$route_id];
                $current_trip = null;

                foreach ($route->tracksFrom($track) as $next_track) {
                    // Can the label on that track be improved in this round?
                    $improved = $next_track->improveTrip($round, $current_trip, $this->target_track);

                    if ($improved) {
                        array_push($this->marked_tracks, $next_track);
                    }

                    // Can we catch an earlier trip at next_track?
                    $current_trip = $next_track->findEarlierTrip($round, $route, $current_trip);
                }
            }

            // Look at foot-paths
            foreach ($this->marked_tracks as $marked_track) {
                foreach ($marked_track->getTransfers() as $transfer) {
                    $to_track = $this->tracks_map[$transfer->getToTrack()->getKey()];
                    $time = $marked_track->getTimeAtRound($round)->add($transfer->getTransferTime());

                    $improved = $to_track->improveTime($round, $time);
                    if ($improved) {
                        array_push($this->marked_tracks, $to_track);
                    }
                }
            }

            $round++;
        } while (empty($marked_track));

        return $result_set;
    }
}