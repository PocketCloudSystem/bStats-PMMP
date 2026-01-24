<?php

namespace xxFLORII\bStats\chart\def;

use Closure;
use pocketcloud\cloud\util\Utils;
use xxFLORII\bStats\chart\CallbackChart;

final class SimplePieChart extends CallbackChart {

    public function __construct(string $custom_id, Closure $callback) {
        parent::__construct($custom_id, $callback);
        Utils::validateCallbackSignature($callback, [], "string");
    }

    protected function getChartData(): array {
        $value = $this->call();
        if (empty($value)) return [];
        return ["value" => $value];
    }
}