<?php

namespace xxFLORII\bStats\settings;

final class MetricsSettings {

    public const string METRICS_VERSION = "3.1.1-SNAPSHOT";

    public function __construct(
        private bool $enabled,
        private int $pluginId,
        private bool $log_failed_requests,
        private bool $log_sent_data,
        private bool $log_response_status_text,
        private string $serverUUID,
        private string $pluginName,
    ) {}

    public function setEnabled(bool $enabled): void {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }

    public function setPluginId(int $pluginId): void {
        $this->pluginId = $pluginId;
    }

    public function getPluginId(): int {
        return $this->pluginId;
    }

    public function setLogFailedRequests(bool $log_failed_requests): void {
        $this->log_failed_requests = $log_failed_requests;
    }

    public function isLogFailedRequests(): bool {
        return $this->log_failed_requests;
    }

    public function setLogSentData(bool $log_sent_data): void {
        $this->log_sent_data = $log_sent_data;
    }

    public function isLogSentData(): bool {
        return $this->log_sent_data;
    }

    public function setLogResponseStatusText(bool $log_response_status_text): void {
        $this->log_response_status_text = $log_response_status_text;
    }

    public function isLogResponseStatusText(): bool {
        return $this->log_response_status_text;
    }

    public function setServerUUID(string $serverUUID): void {
        $this->serverUUID = $serverUUID;
    }

    public function getServerUUID(): string {
        return $this->serverUUID;
    }

    public function setPluginName(string $pluginName): void {
        $this->pluginName = $pluginName;
    }

    public function getPluginName(): string {
        return $this->pluginName;
    }

    public function getMetricsVersion(): string {
        return self::METRICS_VERSION;
    }
}