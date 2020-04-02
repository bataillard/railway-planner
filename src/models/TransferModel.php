<?php

namespace src\models;

class TransferModel
{
    private $from_track;
    private $to_track;
    private $transfer_time;

    public function  __construct($from_track, $to_track, $transfer_time) {
        $this->from_track = $from_track;
        $this->to_track = $to_track;
        $this->transfer_time = $transfer_time;
    }

    public function getToTrack()
    {
        return $this->to_track;
    }

    public function getTransferTime()
    {
        return $this->transfer_time;
    }



}