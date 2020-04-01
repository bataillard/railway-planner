<?php

namespace src\models;

use src\models\interfaces\DataObject;
use src\models\interfaces\DataObjectBuilder;

class StopModel implements DataObject
{
    private $stop_id;
    private $stop_lat;
    private $stop_long;
    private $stop_name;

    private $tracks = [];

    public static function builder(): DataObjectBuilder
    {
        return new class implements DataObjectBuilder {
            public static function build(array $row, DataLoader $dl): DataObject
            {
                return new StopModel($row);
            }
        };
    }

    public function  __construct(array $row) {
        $this->stop_id = $row["stop_id"];
        $this->stop_lat = $row["stop_lat"];
        $this->stop_long = $row["stop_long"];
        $this->stop_name = $row["stop_name"];
    }

    public function getKey(): string
    {
        return $this->stop_id;
    }

    public function addTrack(TrackModel $track)
    {
        array_push($this->tracks, $track);
    }

    public function getTracks(): array
    {
        return $this->tracks;
    }

    public function getRoutes(): array
    {
        $routes = [];

        foreach ($this->tracks as $track) {
            array_merge($routes, $track->getRoutes());
        }

        return $routes;
    }

}