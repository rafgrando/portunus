<?php 

namespace Rafgrando\Portunus\Comm5;

use Rafgrando\Portunus\Comm5\DTO\DeviceResponse;
use Rafgrando\Portunus\Enums\OutputState;
use Rafgrando\Portunus\Comm5\Comm5Protocol;
use Rafgrando\Portunus\Exceptions as PortunusExceptions;

class ModuloAcionamento
{
    public function __construct(
        private readonly Comm5Protocol $protocol,
        private readonly string $deviceIpAddress = '192.168.0.103',
        private readonly int $deviceTcpPort = 5000,
        private readonly int $timeout = 4,
    ) {}

    private function connect(): mixed 
    {
        $connection = @fsockopen(
            $this->deviceIpAddress, 
            $this->deviceTcpPort, 
            $errno, 
            $errstr, 
            $this->timeout
        );

            if ($connection === false) {
                throw new PortunusExceptions\DeviceConnectionException(
                    "Failed to connect to {$this->deviceIpAddress}:{$this->deviceTcpPort} ({$errno}: {$errstr})"
                );
            }

            stream_set_timeout($connection, $this->timeout);

            return $connection;
        }

    private function sendCommand(mixed $connection, string $command): string 
    {
        //Read and do nothing with the greeting message
        fgets($connection);
        fwrite($connection, $command . "\r\n");
        $response = fgets($connection);
        $streamInfo = stream_get_meta_data($connection);
        fclose($connection);

        if ($streamInfo['timed_out']) {
            throw new PortunusExceptions\DeviceCommandException(
                "Timed out waiting for device response to command: {$command}"
            );
        }

        if ($response === false) {
            throw new PortunusExceptions\DeviceCommandException(
                "No response received from device for command: {$command}"
            );
        }

        return $response;
    }

    private function mapErrorResponse(DeviceResponse $response, int $outputNumber): PortunusExceptions\DeviceCommandException
    {

        return match ($response->code) {
            '410' => new PortunusExceptions\InvalidPinException($outputNumber, $response->code, $response->message),
            default => new PortunusExceptions\DeviceCommandException("Device error {$response->code}: {$response->message}"),
        };
    }

    public function setOutputState(int $outputNumber, OutputState $state): void 
    {
        $connection = $this->connect();

        $command = match ($state) {
            OutputState::ON => "SET {$outputNumber}",
            OutputState::OFF => "RESET {$outputNumber}",
        };
        
        $rawResponse = $this->sendCommand($connection, $command);
        $response = $this->protocol->parseResponse($rawResponse);

        if (!$this->protocol->isSuccess($response)) {
            throw $this->mapErrorResponse($response, $outputNumber);
        }
          
    }

}
