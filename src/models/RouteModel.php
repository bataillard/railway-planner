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

    private $tracks = null;
    private $track_indices = [];
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

    public function addDataLoader(DataLoader $dl)
    {
        $this->dl = $dl;
    }

    public function setTracks(array $tracks)
    {
        $this->tracks = $tracks;

        for ($i = 0; $i < count($tracks); $i++) {
            $track = $tracks[$i];
            $this->track_indices[$track->getKey()] = $i;
        }
    }

    public function getTracks(): array
    {
        assert(!empty($tracks));
        return $tracks;
    }

    public function getTracksFrom(Track $track) {
        $offset = $this->track_indices[$track->getKey()];
        return array_slice($this->tracks, $offset);
    }

    public function getKey(): string
    {
        return $this->route_id;
    }

    public function getTrips(): array
    {
        assert(!empty($this->dl));
        return $this->dl->loadTrips($this);
    }

    public function getStopTimes(): array
    {
        assert(!empty($this->dl));
        return $this->dl->loadRouteStopTimes($this);
    }

    public function earliestTripAtTrack(TrackModel $track, Time $start_time)
    {
        return $this->dl->findEarliestTrip($this, $track, $start_time);
    }

    public function trackComesBefore(Track $track1, Track $track2)
    {
        $offset1 = $this->track_indices[$track1->getKey()];
        $offset2 = $this->track_indices[$track2->getKey()];

        return $offset1 < $offset2;
    }



}