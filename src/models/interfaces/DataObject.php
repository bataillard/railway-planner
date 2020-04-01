<?php

namespace src\models\interfaces;

interface DataObject
{
    public static function builder(): DataObjectBuilder;
    public function getKey(): string;
}
