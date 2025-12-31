<?php
declare(strict_types=1);

namespace xxFLORII\bStats\charts\defaults\other;

use xxFLORII\bStats\charts\CallbackChart;

/**
 * Example:
 * ```php
 * $chart = new WordCloudChart("example", function() {
 *     return [
 *         ["name" => "OpenAI", "weight" => 100],
 *         ["name" => "PHP", "weight" => 80],
 *         ["name" => "JavaScript", "weight" => 60]
 *     ];
 * });
 * ```
 */
class WordCloudChart extends CallbackChart {

    public static function getType(): string {
        return "wordcloud";
    }

    protected function getValue(): mixed {
        $value = $this->call();
        if (empty($value)) return null;
        return $value;
    }
}
