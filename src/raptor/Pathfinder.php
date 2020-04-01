<?php


namespace src\raptor;


use src\models\DataLoader;
use src\models\StopModel;

class Pathfinder
{
    private $dl;

    private $routes;
    private $stops;
    private $tracks;

    private function __construct()
    {
        $this->dl = DataLoader::getInstance();
        $data = $this->dl->getBaseData();
        $this->routes = $data["routes"];
        $this->stops = $data["stops"];
        $this->tracks = $data["tracks"];
    }



    public function pathfind(StopModel $source, StopModel $target)
    {
        echo "Hello";
    }
}