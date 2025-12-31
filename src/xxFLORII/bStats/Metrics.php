<?php

namespace xxFLORII\bStats;

use pocketcloud\cloud\console\log\CloudLogger;
use pocketcloud\cloud\player\CloudPlayerManager;
use pocketcloud\cloud\util\AsyncExecutor;
use pocketcloud\cloud\util\promise\Promise;
use pocketcloud\cloud\util\VersionInfo;
use xxFLORII\bStats\charts\CustomChart;
use xxFLORII\bStats\settings\MetricsSettings;

class Metrics {

    /** @var CustomChart[] $charts */
    private array $charts = [];

    public function __construct(private readonly MetricsSettings $metricsSettings) {}

    /**
     * @return MetricsSettings
     */
    public function getMetricsSettings(): MetricsSettings {
        return $this->metricsSettings;
    }

    public function add(CustomChart $chart): self {
        $this->charts[$chart->getCustomId()] = $chart;
        return $this;
    }

    public function remove(string $custom_id): self {
        if (isset($this->charts[$custom_id])) unset($this->charts[$custom_id]);
        return $this;
    }

    public function sendData(): Promise {
        $promise = new Promise();
        $customCharts = [];

        foreach ($this->charts as $chart) {
            $customCharts[] = $chart->jsonSerialize();
        }

        if (stristr(PHP_OS, 'win')) {
            $output = trim(shell_exec('wmic cpu get NumberOfCores'));
            $coreCount = preg_match_all('/\d+/', $output, $matches) ? (int)$matches[0][0] : 0;
        } else {
            $coreCount = (int) shell_exec('nproc');
        }

        $optional_data = [
            "playerAmount" => count(CloudPlayerManager::getInstance()->getAll()),
            "bukkitName" => "PocketCloud@v" . VersionInfo::VERSION,
            "osName" => php_uname("s"),
            "osArch" => php_uname("m"),
            "osVersion" => php_uname("v"),
            "coreCount" => $coreCount,
        ];

        $data = json_encode([
            ...$optional_data,
            "serverUUID" => $this->getMetricsSettings()->getServerUUID(),
            "metricsVersion" => $this->getMetricsSettings()->getMetricsVersion(),
            "service" => [
                "id" => $this->getMetricsSettings()->getPluginId(),
                "customCharts" => $customCharts
            ]
        ], JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) return $promise->reject(json_last_error_msg());

        if ($this->metricsSettings->isLogSentData()) CloudLogger::get()->forceDebug("Sending following data to bStats: " . $data);
        AsyncExecutor::execute(static function () use($data): array {
            $url = 'https://bstats.org/api/v2/data/bukkit';
            $ch = curl_init($url);

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
}