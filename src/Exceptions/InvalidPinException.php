<?php 

namespace Rafgrando\Portunus\Exceptions;

class InvalidPinException extends DeviceCommandException
{
    public function __construct(
        public readonly int $outputNumber,
        public readonly string $deviceCode,
        public readonly string $deviceMessage,
    ) {
        parent::__construct(
            "Invalid pin/output number {$outputNumber}: device responded '{$deviceCode} {$deviceMessage}'"
        );
    }
}