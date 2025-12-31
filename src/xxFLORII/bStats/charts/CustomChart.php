<?php
declare(strict_types=1);

namespace xxFLORII\bStats\charts;

use ErrorException;
use JsonSerializable;
use Throwable;

abstract class CustomChart implements JsonSerializable {

    public function __construct(private readonly string $custom_id) {}

    public abstract static function getType(): string;

    public function getCustomId(): string {
        return $this->custom_id;
    }

    public function jsonSerialize(): array {
        $json = ["chartId" => $this->custom_id,];
        try {
            $data = $this->getValue();
            if ($data === null) throw new ErrorException('$data cannot be null');
            $json["data"] = $data;
        } catch (Throwable) {}

        return $json;
    }

    protected abstract function getValue(): mixed;
}
