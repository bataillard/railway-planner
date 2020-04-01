<?php

namespace src\models;

use src\models\interfaces\DataObject;
use src\models\interfaces\DataObjectBuilder;

class RouteModel implements DataObject
{
    private $route_id;
    private $agency_id;
    private $route_type;
    private $route_name;

    private $tracks = null;
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
    }

    public function getTracks(): array
    {
        assert(!empty($tracks));
        return $tracks;
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



}