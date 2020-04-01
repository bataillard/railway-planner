<?php

namespace src\models\interfaces;

use src\models\DataLoader;

interface DataObjectBuilder {
    public static function build(array $row, DataLoader $dl): DataObject;
}