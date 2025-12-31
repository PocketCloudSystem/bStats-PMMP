<?php
declare(strict_types=1);

namespace xxFLORII\bStats\charts;

use Closure;
use pocketcloud\cloud\console\log\CloudLogger;
use ReflectionClass;
use Throwable;

abstract class CallbackChart extends CustomChart {

    public function __construct(
        string $custom_id,
        protected readonly Closure $callback
    ) {
        parent::__construct($custom_id);
    }

    protected function call(): mixed {
        try {
            return ($this->callback)();
        } catch (Throwable $t) {
            CloudLogger::get()->error("Error while executing callback in " . new ReflectionClass($this)->getShortName() . " class:");
            CloudLogger::get()->exception($t);
            return null;
        }
    }
}