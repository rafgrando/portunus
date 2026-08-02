<?php 

namespace Rafgrando\Portunus\Comm5;

use Rafgrando\Portunus\Comm5\DTO\DeviceResponse;
use Rafgrando\Portunus\Exceptions\DeviceCommandException;

final class Comm5Protocol
{
    private const SUCCESS_CODES = ['200', '210', '220', '400'];

    public function parseResponse(string $rawResponse): DeviceResponse
    {
        $trimmed = trim($rawResponse);

        if (!preg_match('/^(\d{3})\s*(.*)$/', $trimmed, $matches)) {
            throw new DeviceCommandException("Unrecognized device response format: '{$rawResponse}'");
        }

        return new DeviceResponse($matches[1], $matches[2]);
    }

    public function isSuccess(DeviceResponse $response): bool
    {
        return in_array($response->code, self::SUCCESS_CODES, true);
    }
}