<?php


namespace src\raptor;


use src\models\TripModel;

class Connection
{
    private $trip;
    private $start_track;
    private $end_track;

    public const FOOT_CONNECTION_TAG = "FOOT_CONNECTION";

    public function __construct($trip, $start_track, $end_track)
    {
        $this->trip = $trip;
        $this->start_track = $start_track;
        $this->end_track = $end_track;
    }

    public function getTrip(): TripModel
    {
        return $this->trip;
    }

    public function getStartTrack()
    {
        return $this->start_track;
    }

    public function getEndTrack()
    {
        return $this->end_track;
    }

    public function isTransfer()
    {
        return $this->trip == self::FOOT_CONNECTION_TAG;
    }

    public function __toString()
    {
        return "Connection on trip " . $this->trip . " from stop "
            . $this->start_track . " to track " . $this->end_track;
    }
}