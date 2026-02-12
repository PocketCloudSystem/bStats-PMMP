<?php

namespace xxFLORII\bStats;

use pocketcloud\cloud\console\log\CloudLogger;
use pocketcloud\cloud\PocketCloud;
use pocketcloud\cloud\util\AsyncExecutor;
use pocketcloud\cloud\util\misc\Tickable;
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
        if (!$this->metricsSettings->isEnabled()) return;
        $this->startSubmission = true;

        $initialDelayMs = (int) (1000 * 60 * (3 + (mt_rand() / mt_getrandmax()) * 3));
        $secondDelayMs = (int) (1000 * 60 * ((mt_rand() / mt_getrandmax()) * 30));

        $this->initialDelayTick = round(PocketCloud::getInstance()->getTick() + ($secondDelayMs * 0.02));
        $this->nextSubmissionTick = round((($initialDelayMs + $secondDelayMs) * 0.02) + PocketCloud::getInstance()->getTick());

        CloudLogger::get()->debug("First bStats submission on tick: " . $this->initialDelayTick);
    }

    public function tick(int $currentTick): void {
        if (!$this->metricsSettings->isEnabled()) return;
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

    private function sendData(): void {
        if (!$this->startSubmission || !$this->metricsSettings->isEnabled()) return;
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

        if (json_last_error() !== JSON_ERROR_NONE) {
            CloudLogger::get()->error("Failed to submit data to bStats: §e{}", json_last_error_msg());
            return;
        }

        if ($this->metricsSettings->isLogSentData()) CloudLogger::get()->forceDebug("Sending following data to bStats: " . $data);
        else CloudLogger::get()->debug("Sending bStats data...");

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
                "User-Agent: Metrics-Service/1",
                "Connection: close",
                "Content-Encoding: gzip"
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
        }, function (array $result): void {
            [$response, $error, $status] = $result;
            if ($response === false || $error !== "" || str_starts_with((string) $status, "4")) {
                if ($this->metricsSettings->isLogFailedRequests()) CloudLogger::get()->error("Failed to submit data to bStats §8(§cHTTP Status Code §e{}§8)§c: §e{}", $status, ($error == "" ? ($response ?: "Unknown") : $error));
                return;
            }

            CloudLogger::get()->debug("Successfully submitted bStats data");
            if ($this->metricsSettings->isLogResponseStatusText()) CloudLogger::get()->forceDebug("bStats response: {}", $response);
        });
    }

    public function isStartSubmission(): bool {
        return $this->startSubmission;
    }

    public function getCharts(): array {
        return $this->charts;
    }

    public function getSettings(): MetricsSettings {
        return $this->metricsSettings;
    }
}