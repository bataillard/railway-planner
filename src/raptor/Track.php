<?php


namespace src\raptor;


use src\models\RouteModel;
use src\models\StopTimeModel;
use src\models\TrackModel;
use src\models\TripModel;

class Track
{
    private $model;
    private $is_marked;
    private $earliest_time;
    private $earliest_time_by_round;
    private $connections_by_round = [];

    public static function withEarliestTime(Track ...$tracks)
    {
        $earliest = $tracks[0];

        foreach ($tracks as $track) {
            if ($track->isEarlier($earliest)) {
                $earliest = $track;
            }
        }

        return $earliest;
    }

    public function __construct(TrackModel $model, Time $departure_time)
    {
        $this->model = $model;
        $this->is_marked = false;
        $this->earliest_time = Time::maxTime();
        $this->earliest_time_by_round = [$departure_time];
    }

    public function getKey() { return $this->model->getKey(); }

    public function routesServing()
    {
        return $this->model->getRoutes();
    }

    public function equals(Track $other) {
        return $this->model->getKey() == $other->model->getKey();
    }

    public function isEarlier(Track $other)
    {
        return $this->earliest_time->isEarlier($other->earliest_time);
    }

    public function getEarliestTime()
    {
        return $this->earliest_time;
    }

    public function getTimeAtRound(int $round)
    {
        if (array_key_exists($round, $this->earliest_time_by_round[$round])) {
            return $this->earliest_time_by_round[$round];
        } else {
            return Time::maxTime();
        }
    }

    public function getConnectionAtRound(int $round): Connection
    {
        return $this->connections_by_round[$round];
    }

    public function improveTransfer(int $round, Time $time, TrackModel $from_track)
    {
        if ($time->isEarlier($this->earliest_time)) {
            $this->earliest_time_by_round[$round] = $time;
            $this->connections_by_round[$round] = new Connection(Connection::FOOT_CONNECTION_TAG, $from_track, $this);
            return true;
        }

        return false;
    }

    public function improveTrip(int $round, $current_trip, Track $destination_track, $boarded_track) {
        $improved = false;

        if ($current_trip != null) {
            $trip_time = $current_trip->timeAtTrack($this->model);
            $earliest_time = Track::withEarliestTime($this, $destination_track)->getEarliestTime();

            if ($trip_time->isEarlier($earliest_time)) {
                $improved = true;

                $this->earliest_time_by_round[$round] = $trip_time;
                $this->connections_by_round[$round] = new Connection($current_trip, $this, $boarded_track);
                $this->earliest_time = $trip_time;
            }
        }

        return $improved;
    }

    public function timeAtTrip(TripModel $trip)
    {
        return $trip->timeAtTrack($this->model);
    }

    public function __toString()
    {
        return "Stop " . $this->model->getStopId() . " track " . $this->model->getTrackId();
    }
}