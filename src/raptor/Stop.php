<?php


namespace src\raptor;


use src\models\StopModel;

class Stop
{
    private $model;
    private $is_marked;

    private $earliest_arrival_time;

    public function __construct(StopModel $model)
    {
        $this->model = $model;
        $this->is_marked = false;
    }

    public function markAtTime() { $this->is_marked = true; }
    public function isMarked() { return $this->is_marked; }

}