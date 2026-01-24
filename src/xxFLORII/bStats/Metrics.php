<?php

namespace xxFLORII\bStats;

use pocketcloud\cloud\console\log\CloudLogger;
use pocketcloud\cloud\PocketCloud;
use pocketcloud\cloud\util\AsyncExecutor;
use pocketcloud\cloud\util\misc\Tickable;
use pocketcloud\cloud\util\promise\Promise;
use xxFLORII\bStats\chart\CustomChart;
use xxFLORII\bStats\settings\MetricsSettings;

class Metrics implements Tickable {

    public const int FIXED_SUBMISSION_INTERVAL = 20 * 60 * 30; // <- ticks | ms: 1000 * 60 * 30

    private bool $startSubmission = false;
    private int $initialDelayTick = 0;
    private int $nextSubmissionTick = 0;

    /** @var CustomChart[] $charts */
    private array $charts = [];

    public function __construct(private readonly MetricsSettings $metricsSettings) {}

    public function addChart(CustomChart $chart): self {
        $this->charts[$chart->getCustomId()] = $chart;
        return $this;
    }

    public function removeChart(string $customId): self {
        if (isset($this->charts[$customId])) unset($this->charts[$customId]);
        return $this;
    }

    public function startSubmitting(): void {
        $this->startSubmission = true;

        $initialDelayMs = (int) (1000 * 60 * (3 + mt_rand() / mt_getrandmax() * 3));
        $secondDelayMs = (int) (1000 * 60 * (mt_rand() / mt_getrandmax() * 30));

        $this->initialDelayTick = PocketCloud::getInstance()->getTick() + ($secondDelayMs * 0.02);
        $this->nextSubmissionTick = (($initialDelayMs + $secondDelayMs) * 0.02) + PocketCloud::getInstance()->getTick();
    }

    public function tick(int $currentTick): void {
        if ($this->initialDelayTick !== 0) {
            if ($currentTick >= $this->initialDelayTick) {
                $this->sendData();
                $this->initialDelayTick = 0;
            }
        } else {
            if ($currentTick >= $this->nextSubmissionTick) {
                $this->sendData();
                $this->nextSubmissionTick = $currentTick + self::FIXED_SUBMISSION_INTERVAL;
            }
        }
    }

    protected function sendData(): Promise {
        if (!$this->startSubmission || !$this->metricsSettings->isEnabled()) return Promise::rejected();
        $promise = new Promise();
        $customCharts = [];
        foreach ($this->charts as $chart) {
            if (($serializedChart = $chart->jsonSerialize()) !== null) {
                $customCharts[] = $serializedChart;
            }
        }

        if (stristr(PHP_OS, 'win')) {
            $output = trim(shell_exec('wmic cpu get NumberOfCores'));
            $coreCount = preg_match_all('/\d+/', $output, $matches) ? (int)$matches[0][0] : 0;
        } else {
            $coreCount = (int) shell_exec('nproc');
        }

        $optional_data = [
            "osName" => php_uname("s"),
            "osArch" => php_uname("m"),
            "osVersion" => php_uname("v"),
            "coreCount" => $coreCount
        ];

        $data = json_encode([
            ...$optional_data,
            "serverUUID" => $this->metricsSettings->getServerUUID(),
            "metricsVersion" => $this->metricsSettings->getMetricsVersion(),
            "service" => [
                "id" => $this->metricsSettings->getPluginId(),
                "customCharts" => $customCharts
            ]
        ], JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) return $promise->reject(json_last_error_msg());

        if ($this->metricsSettings->isLogSentData()) CloudLogger::get()->forceDebug("Sending following data to bStats: " . $data);
        AsyncExecutor::execute(static function () use($data): array {
            $url = 'https://bstats.org/api/v2/data/server-implementation';
            $ch = curl_init($url);
            $data = zlib_encode($data, ZLIB_ENCODING_GZIP);

            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Content-Length: " . strlen($data),
            ]);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            return [
                $response,
                $error,
                $statusCode
            ];
        }, function (array $result) use($promise): void {
            [$response, $error, $status] = $result;
            if ($response === false || $error !== "") {
                $promise->reject($result);
                return;
            }

            if (str_starts_with((string) $status, "4")) {
                $promise->reject($result);
                return;
            }

            $promise->resolve($response);
        });

        return $promise;
    }

    public function getCharts(): array {
        return $this->charts;
    }

    public function getSettings(): MetricsSettings {
        return $this->metricsSettings;
    }
}