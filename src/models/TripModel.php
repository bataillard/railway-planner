<?php


namespace src\models;


use src\models\interfaces\DataObject;
use src\models\interfaces\DataObjectBuilder;
use src\raptor\Time;

class TripModel implements DataObject
{
    private $trip_id;
    private $route_id;
    private $service_id;
    private $trip_name;
    private $wheelchair_accessible;
    private $bikes_allowed;

    private $departure_time;

    private $dl;

    public function __construct(array $row, DataLoader $dl)
    {
        $this->trip_id = $row["trip_id"];
        $this->route_id = $row["route_id"];
        $this->service_id = $row["service_id"];
        $this->trip_name = $row["trip_name"];
        $this->wheelchair_accessible = $row["wheelchair_accessible"];
        $this->bikes_allowed = $row["bikes_allowed"];

        $this->departure_time = Time::from($row["departure_hours"], $row["departure_minutes"]);
        $this->dl = $dl;
    }

    public function getDepartureTime(): Time
    {
        return $this->departure_time;
    }

    public static function cmp(TripModel $trip1, TripModel $trip2)
    {
        return Time::cmp($trip1->departure_time, $trip2->departure_time);
    }

    public function getKey(): string
    {
        return $this->trip_id;
    }

    public function getRouteId()
    {
        return $this->route_id;
    }

    public function getStopTimes()
    {
        assert(!empty($this->dl));
        return $this->dl->loadTripStopTimes($this);
    }

    public function timeAtTrack(TrackModel $track): Time
    {
        return $this->dl->loadTripArrivalAtTrack($this, $track);
    }


    public static function builder(): DataObjectBuilder
    {
        return new class implements DataObjectBuilder {
            public static function build(array $row, DataLoader $dl): DataObject
            {
                return new TripModel($row, $dl);
            }
        };
    }

    public function __toString()
    {
        return "Trip " . $this->trip_id . " on route " . $this->route_id;
    }
}