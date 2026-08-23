<?php

namespace App\Services\Notifications\Contracts;

class DeliveryResult
{
    public function __construct(
        public bool $success,
        public ?int $emailProviderInstanceId = null,
        public ?string $errorMessage = null
    ) {}

    public static function success(?int $providerInstanceId = null): self
    {
        return new self(true, $providerInstanceId, null);
    }

    public static function failure(?string $errorMessage = null, ?int $providerInstanceId = null): self
    {
        return new self(false, $providerInstanceId, $errorMessage);
    }
}
