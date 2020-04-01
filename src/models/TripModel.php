<?php


namespace src\models;


class TripModel
{
    private $trip_id;
    private $service_id;
    private $trip_name;
    private $wheelchair_accessible;
    private $bikes_allowed;

    private $route;
    private $dl;

    public function __construct($trip_id, $service_id, $trip_name,
                                $wheelchair_accessible, $bikes_allowed, $route, DataLoader $dl)
    {
        $this->trip_id = $trip_id;
        $this->service_id = $service_id;
        $this->trip_name = $trip_name;
        $this->wheelchair_accessible = $wheelchair_accessible;
        $this->bikes_allowed = $bikes_allowed;

        $this->route = $route;
        $this->dl = $dl;
    }

    public function getKey()
    {
        return $this->trip_id;
    }

    public function getStopTimes()
    {
        assert(!empty($this->dl));
        return $this->dl->loadTripStopTimes($this);
    }


}