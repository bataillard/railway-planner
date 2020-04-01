<?php


namespace src\models;


class StopTimeModel
{
    private $trip_id;
    private $stop_id;
    private $track;
    private $stop_sequence;
    private $arrival_time;
    private $departure_time;

    public function __construct($trip_id, $stop_id, $track, $stop_sequence, $arrival_time, $departure_time)
    {
        $this->trip_id = $trip_id;
        $this->stop_id = $stop_id;
        $this->track = $track;
        $this->stop_sequence = $stop_sequence;
        $this->arrival_time = $arrival_time;
        $this->departure_time = $departure_time;
    }


}