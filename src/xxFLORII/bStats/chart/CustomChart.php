<?php

declare(strict_types=1);

namespace xxFLORII\bStats\chart;

use JsonSerializable;

abstract class CustomChart implements JsonSerializable {

    public function __construct(private readonly string $customId) {}

    public function getCustomId(): string {
        return $this->customId;
    }

    public function jsonSerialize(): ?array {
        $json = ["chartId" => $this->customId];
        $data = $this->getChartData();
        if ($data === null) return null;
        $json["data"] = $data;
        return $json;
    }

    protected abstract function getChartData(): ?array;
}
