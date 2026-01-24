<?php

namespace xxFLORII\bStats\chart\def;

use Closure;
use pocketcloud\cloud\util\Utils;
use xxFLORII\bStats\chart\CallbackChart;

final class AdvancedBarChart extends CallbackChart {

    public function __construct(string $custom_id, Closure $callback) {
        parent::__construct($custom_id, $callback);
        Utils::validateCallbackSignature($callback, [], "array");
    }

    protected function getChartData(): ?array {
        $values = [];
        $callbackMap = $this->call();
        if (empty($callbackMap)) return null;
        $allSkipped = true;
        foreach ($callbackMap as $key => $integerMap) {
            if (!is_string($key) || !is_array($integerMap)) continue;
            if (empty($integerMap)) continue;
            if (array_all($integerMap, fn($value) => is_int($value))) {
                $allSkipped = false;
                $values[$key] = $integerMap;
            }
        }

        if ($allSkipped) return null;
        return ["values" => $values];
    }
}