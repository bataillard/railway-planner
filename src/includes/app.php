<?php

use src\models\DataLoader;
use src\models\StopModel;
use src\raptor\Pathfinder;
require_once 'autoload.php';

$pf = new Pathfinder();
$start_stop = DataLoader::getInstance()->getStop("8504300");
$end_stop = DataLoader::getInstance()->getStop("8500102");
echo $pf->pathfind($start_stop, $end_stop, 12, 00);