<?php

namespace xxFLORII\bStats\chart\def;

use Closure;
use pocketcloud\cloud\util\Utils;
use xxFLORII\bStats\chart\CallbackChart;

final class AdvancedPieChart extends CallbackChart {

    public function __construct(string $custom_id, Closure $callback) {
        parent::__construct($custom_id, $callback);
        Utils::validateCallbackSignature($callback, [], "array");
    }

    protected function getChartData(): ?array {
        $values = [];
        $callbackMap = $this->call();
        if (empty($callbackMap)) return null;
        $allSkipped = true;
        foreach ($callbackMap as $key => $integer) {
            if (!is_string($key) || !is_int($integer)) continue;
            if ($integer == 0) continue;
            $allSkipped = false;
            $values[$key] = $integer;
        }

        if ($allSkipped) return null;
        return ["values" => $values];
    }
}