<?php 

namespace Rafgrando\Portunus\Comm5\DTO;

final class DeviceResponse
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
    ) {}
}