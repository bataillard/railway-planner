<?php

namespace src\models;

use src\models\interfaces\DataObject;
use src\models\interfaces\DataObjectBuilder;

class TrackModel implements DataObject
{
    private $stop_id;
    private $track;

    private $parent_stop = null;
    private $routes = [];
    private $transfers = [];

    public static function builder(): DataObjectBuilder
    {
        return new class implements DataObjectBuilder {
            public static function build(array $row, DataLoader $dl): DataObject
            {
                return new TrackModel($row);
            }
        };
    }

    public static function constructKey($stop_id, $track)
    {
        return $stop_id . "T" . $track;
    }

    public function  __construct(array $row) {
        $this->stop_id = $row["stop_id"];
        $this->track = $row["track"];
    }

    public function getKey(): string
    {
        return TrackModel::constructKey($this->stop_id, $this->track);
    }

    public function getStopId(): string
    {
        return $this->stop_id;
    }

    public function getTrackId()
    {
        return $this->track;
    }

    public function setStop(StopModel $stop)
    {
        $this->parent_stop = $stop;
    }

    public function getStop(): StopModel
    {
        assert(!empty($this->parent_stop));
        return $this->parent_stop;
    }

    public function addRoute(RouteModel $route)
    {
        array_push($this->routes, $route);
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function addTransfer(TrackModel $to_track, int $transfer_time)
    {
        array_push($this->transfers, new TransferModel($this, $to_track, $transfer_time));
    }

    public function getTransfers(): array
    {
        return $this->transfers;
    }




}