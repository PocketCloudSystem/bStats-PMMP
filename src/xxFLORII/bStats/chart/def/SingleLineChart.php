<?php

namespace xxFLORII\bStats\chart\def;

use Closure;
use pocketcloud\cloud\util\Utils;
use xxFLORII\bStats\chart\CallbackChart;

final class SingleLineChart extends CallbackChart {

    public function __construct(string $custom_id, Closure $callback) {
        parent::__construct($custom_id, $callback);
        Utils::validateCallbackSignature($callback, [], "int");
    }

    protected function getChartData(): array {
        $value = $this->call();
        if ($value === null) return [];
        return ["value" => $value];
    }
}