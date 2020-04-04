<?php

use src\models\DataLoader;
use src\models\StopModel;
use src\raptor\Pathfinder;
require_once 'autoload.php';

$pf = new Pathfinder();
$start_stop = DataLoader::getInstance()->getStop("8504300");
$end_stop = DataLoader::getInstance()->getStop("8500102");
$pf_results = $pf->pathfind($start_stop, $end_stop, 12, 00);
$results = $pf_results->finalize();

foreach ($results as $res) {
    echo "============== Result ==============\n";
    foreach ($res as $leg) {
        echo "\tLeg:" . $leg . "\n";
    }
}