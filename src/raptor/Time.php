<?php


namespace src\raptor;

class Time
{
    private const SECONDS_PER_HOUR = 60*60;
    private const SECONDS_PER_MINUTE = 60;

    private $seconds;

    public static function maxTime() { return new Time(PHP_INT_MAX); }

    public static function from($hour, $minute)
    {
        return new Time(self::SECONDS_PER_HOUR*$hour + self::SECONDS_PER_MINUTE * $minute);
    }

    public function formatDB()
    {
        return date("H:i:s", $this->seconds);
    }

    private function __construct($seconds) { $this->seconds = $seconds; }

    public static function cmp(Time $time1, Time $time2): int
    {
        if ($time1->seconds < $time2->seconds) {
            return -1;
        } else if ($time1->seconds == $time2->seconds) {
            return 0;
        } else {
            return 1;
        }
    }

    public function isEarlier(Time $other)
    {
        return $this->seconds < $other->seconds;
    }

    public function notLater(Time $other)
    {
        return $this->seconds <= $other->seconds;
    }

    public function add(Time $other)
    {
        return new Time($other->seconds + $this->seconds);
    }
}