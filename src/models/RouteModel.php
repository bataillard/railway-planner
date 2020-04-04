<?php

namespace src\models;

use src\models\interfaces\DataObject;
use src\models\interfaces\DataObjectBuilder;
use src\raptor\Time;
use src\raptor\Track;

class RouteModel implements DataObject
{
    private $route_id;
    private $agency_id;
    private $route_type;
    private $route_name;

    private $tracks = [];
    private $track_indices = [];
    private $n_tracks = 0;

    private $trips = [];
    private $trips_finalized = false;

    private $dl;

    public static function builder(): DataObjectBuilder
    {
        return new class implements DataObjectBuilder {
            public static function build(array $row, DataLoader $dl): DataObject
            {
                return new RouteModel($row, $dl);
            }
        };
    }

    public function  __construct(array $row, DataLoader $dl)
    {
        $this->route_id = $row["route_id"];
        $this->agency_id = $row["agency_id"];
        $this->route_type = $row["route_type"];
        $this->route_name = $row["route_name"];

        $this->dl = $dl;
    }

    public function addTrack(TrackModel $track)
    {
        array_push($this->tracks, $track);
        $this->track_indices[$track->getKey()] = $this->n_tracks;
        $this->n_tracks++;
    }

    public function getTracks(): array
    {
        assert(!empty($this->tracks));
        return $this->tracks;
    }

    public function getOffsetFrom(Track $track) {
        return $this->track_indices[$track->getKey()];
    }

    public function getKey(): string
    {
        return $this->route_id;
    }

    public function getTrips(): array
    {
        return $this->trips;
    }

    public function addTrip(TripModel $trip)
    {
        array_push($this->trips, $trip);
    }

    public function numberOfTrips(): int
    {
        return count($this->trips);
    }

    public function getStopTimes(): array
    {
        assert(!empty($this->dl));
        return $this->dl->loadRouteStopTimes($this);
    }

    public function trackComesBefore(Track $track1, Track $track2)
    {
        $offset1 = $this->track_indices[$track1->getKey()];
        $offset2 = $this->track_indices[$track2->getKey()];

        return $offset1 < $offset2;
    }



}