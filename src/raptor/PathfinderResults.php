<?php


namespace src\raptor;


use src\models\TrackModel;

class PathfinderResults
{
    private $n_rounds;
    private $tracks;
    private $origin_track;
    private $destination_track;
    private $departure_time;

    public function __construct(int $n_rounds, $tracks, Track $origin_track,
                                Track $destination_track, Time $departure_time)
    {
        $this->n_rounds = $n_rounds;
        $this->tracks = $tracks;
        $this->origin_track = $origin_track;
        $this->destination_track = $destination_track;
        $this->departure_time = $departure_time;
    }

    public function finalize()
    {
        $results = [];
        for ($k = 0; $k < $this->n_rounds; $k++)
        {
            $legs = $this->getLegs($k);
            array_push($results, $legs);
        }

        return $results;
    }

    private function getLegs($k)
    {
        $legs = [];
        $destination = $this->destination_track;

        for ($i = $k; $i < 0; $i--)
        {
            $connection = $destination->getConnectionAtRound($i);
            array_push($legs, $connection);
            $destination = $connection->getStartTrack();
        }

        return array_reverse($legs);
    }
}