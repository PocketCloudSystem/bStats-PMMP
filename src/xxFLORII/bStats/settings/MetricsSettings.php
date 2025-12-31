<?php

namespace xxFLORII\bStats\settings;

final readonly class MetricsSettings {

    public const string METRICS_VERSION = "3.1.1-SNAPSHOT";

    public function __construct(
        private bool $enabled,
        private int $pluginId,
        private bool $log_failed_requests,
        private bool $log_sent_data,
        private bool $log_response_status_text,
        private string $serverUUID,
    ) {}

    public function isEnabled(): bool {
        return $this->enabled;
    }

    public function getPluginId(): int {
        return $this->pluginId;
    }

    public function isLogFailedRequests(): bool {
        return $this->log_failed_requests;
    }

    public function isLogSentData(): bool {
        return $this->log_sent_data;
    }

    public function isLogResponseStatusText(): bool {
        return $this->log_response_status_text;
    }

    public function getServerUUID(): string {
        return $this->serverUUID;
    }

    public function getMetricsVersion(): string {
        return self::METRICS_VERSION;
    }
}