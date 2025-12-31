<?php
declare(strict_types=1);

namespace xxFLORII\bStats\charts\defaults\other;

use xxFLORII\bStats\charts\CallbackChart;

/**
 * Example:
 * ```php
 * $chart = new ErrorBarChart("example", function() {
 *     return [
 *         ["x" => 1, "y" => 10, "errorLow" => 1, "errorHigh" => 2],
 *         ["x" => 2, "y" => 20, "errorLow" => 1.5, "errorHigh" => 2.5],
 *         ["x" => 3, "y" => 30, "errorLow" => 2, "errorHigh" => 3]
 *     ];
 * });
 * ```
 */
class ErrorBarChart extends CallbackChart {

    public static function getType(): string {
        return "errorbar";
    }

    protected function getValue(): mixed {
        $value = $this->call();
        if (empty($value)) return null;
        return $value;
    }
}
