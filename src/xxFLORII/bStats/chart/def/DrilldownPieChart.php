<?php

namespace xxFLORII\bStats\chart\def;

use Closure;
use pocketcloud\cloud\util\Utils;
use xxFLORII\bStats\chart\CallbackChart;

final class DrilldownPieChart extends CallbackChart {

    public function __construct(string $custom_id, Closure $callback) {
        parent::__construct($custom_id, $callback);
        Utils::validateCallbackSignature($callback, [], "array");
    }

    protected function getChartData(): ?array {
        $values = [];
        $callbackMap = $this->call();
        if (empty($callbackMap)) return null;
        $allSkipped = true;

        foreach ($callbackMap as $key => $stringIntegerMap) {
            if (!is_string($key) || !is_array($stringIntegerMap)) continue;
            if (empty($stringIntegerMap)) continue;
            if (array_all($stringIntegerMap, fn($value, $key) => is_int($value) && is_string($key))) {
                $allSkipped = false;
                $values[$key] = $stringIntegerMap;
            }
        }

        if ($allSkipped) return null;
        return ["values" => $values];
    }
}