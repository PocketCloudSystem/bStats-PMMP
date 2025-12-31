<?php
declare(strict_types=1);

namespace xxFLORII\bStats\charts\defaults\advanced;

use xxFLORII\bStats\charts\CallbackChart;

/**
 * Example:
 * ```php
 * $chart = new GaugeChart("example", function() {
 *     return 75;
 * });
 * ```
 */
class GaugeChart extends CallbackChart {

    public static function getType(): string {
        return "gauge";
    }

    protected function getValue(): mixed {
        $value = $this->call();
        if (empty($value)) return null;
        return $value;
    }
}