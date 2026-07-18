<?php 

namespace Rafgrando\Portunus;

class GuaritaIP
{   
    public $deviceIpAddress = '192.168.0.10';
    public $deviceTcpPort = 9000;
    public $deviceAccessCode = '';
    public $timeout = 4;


    private function normalizeDeviceType($deviceType): string|false {
        // Normaliza o tipo de dispositivo para o formato aceito pelo GuaritaIP.
        // 1 = TX; 2 = TAG Ativo; 3 = CT; 5 = Biometria; 6 = TAG Passivo; 7 = Senha
        switch ($deviceType) {
            case '1':
            case '01':
            case 'RF':
            case 'rf':
            case 'TX':
            case 'tx':
                return '01';
            case '2':
            case '02':
            case 'TA':
            case 'ta':
                return '02';
            case '3':
            case '03':
            case 'CT':
            case 'ct':
            case 'CTW':
            case 'ctw':
            case 'CTWB':
            case 'ctwb':
                return '03';
            case '5':
            case '05':
            case 'BM':
            case 'bm':
            case 'BIO':
            case 'bio':
                return '05';
            case '6':
            case '06':
            case 'TP':
            case 'tp':
                return '06';
            case '7':
            case '07':
            case 'SN':
            case 'sn':
                return '07';
            default:
                return false;
        }
    }

    private function normalizeDeviceNumber($deviceNumber): string|false {
        // Normaliza o número do dispositivo para o formato aceito pelo GuaritaIP.
        // CAN 1 a CAN 8; valores de 0 a 7
        switch ($deviceNumber) {
            case '00':
            case '01':
            case '02':
            case '03':
            case '04':
            case '05':
            case '06':
            case '07':
                return str_pad(strval($deviceNumber), 2, '0', STR_PAD_LEFT);
            default:
                return false;
        }
    }

    protected function calculateChecksum($input) {
        // Calcula o checksum, concatena-o ao final de $input e retorna o valor.
        
        $dec = str_split($input, 2);
        foreach ($dec as $k => $val) {
            $dec[$k] = hexdec($val);
        }
        
        $cs = array_sum($dec);

        if ($cs > 255) {
            $cs = dechex($cs);
            $cs = substr($cs, -2);
        } else {
            $cs = dechex($cs);
        }

        return $input . str_pad($cs, 2, '0', STR_PAD_LEFT);
    }



    protected function removeExtraByte($input) {
        // Remove o byte extra 0x00 retornado por receptores Multifunção-4A com firmware 2.005y
        
        if (substr($input, 0, 2) == substr($input, 2, 2) && substr($input, 0, 2) == '00') {
            return substr($input, 2);
        } else {
            return $input;
        }        
    }



    // PC 3: Ler identificação (Linha 2 e 3 - Display)
    public function leIdentificacao() {
        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin('000303');
            fwrite($fp, $message);
            $response = fgets($fp, 44);
            fclose($fp);
            return $response;
          }
    }



    // PC 12: Ler data e hora (Relógio)
    public function leDataHora() {
        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin('000c0c');
            fwrite($fp, $message);
            $response = fgets($fp, 10);
            fclose($fp);
            return bin2hex($response);
          }
    }



    // PC 13: Acionar saídas (Relés dos Receptores)
    public function acionaRele($deviceType, $deviceNumber, $deviceOutputNumber, $shouldGenerateEvent = 1) {
        $deviceType = $this->normalizeDeviceType($deviceType);
        $deviceNumber = $this->normalizeDeviceNumber($deviceNumber);
        $deviceOutputNumber = str_pad(strval($deviceOutputNumber), 2, '0', STR_PAD_LEFT); /* 1, 2, 3 ou 4 */
        $shouldGenerateEvent = strval($shouldGenerateEvent);
        
        switch ($deviceOutputNumber) {
            case '01':
            case '02':
            case '03':
            case '04':
                break;
            default:
                return false;
        }
        
        $shouldGenerateEvent = $shouldGenerateEvent ? '01' : '00';

        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin($this->calculateChecksum('000d' . $deviceType . $deviceNumber . $deviceOutputNumber . $shouldGenerateEvent));
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }
    
    
    
    // PC 18: Reiniciar Guarita (Efetiva Config. Ethernet)
    public function reiniciaGuaritaIP() {
        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin('001212');
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }
    
    
    
    // PC 24: RESET remoto (Tecla RESET do Guarita)
    public function pressionaRESET() {
        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin('001818');
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }
    
    
    
    // PC 29: Atualizar Receptores
    public function atualizaReceptores() {
        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin('001d1d');
            fwrite($fp, $message);
            if (bin2hex(fgets($fp, 5)) == '001d001d') {
                fclose($fp);
                return true;
            } else {
                fclose($fp);
                return false;
            }
          }
    }
    
    
    
    // PC 39: Ativar modo remoto (RECEPTORES) - Programável
    public function ativaModoRemotoProg($deviceType, $deviceNumber, $tempo) {
        $deviceType = $this->normalizeDeviceType($deviceType);
        $deviceNumber = str_pad(strval($deviceNumber), 2, '0', STR_PAD_LEFT); /* CAN 1 a CAN 8; valores de 0 a 7 */
        
        if (is_int($tempo) || ctype_digit($tempo)) {
            if ((0 <= intval($tempo)) && (intval($tempo) <= 255)) { /* Tempo deve estar entre 0 e 255 segundos */
                $tempo = dechex($tempo);
                $tempo = str_pad(strval($tempo), 2, '0', STR_PAD_LEFT);
            } else {
                return false;
            }
        } else {
            return false;
        }
        
        switch ($deviceNumber) {
            case 'TODOS':
            case 'todos':
            case 'ALL':
            case 'all':
            case 'FF':
            case 'ff':
                $deviceType = 'ff';
                break;
            case '00':
            case '01':
            case '02':
            case '03':
            case '04':
            case '05':
            case '06':
            case '07':
                break;
            default:
                return false;
        }


        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin($this->calculateChecksum('0027' . $deviceType . $deviceNumber . $tempo));
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }



    // PC 61: Ler versão do Receptor (Firmware)
    public function leVersaoReceptor($deviceType, $deviceNumber, $fp = null) {
        $deviceType = $this->normalizeDeviceType($deviceType);
        $deviceNumber = $this->normalizeDeviceNumber($deviceNumber);
        
        if (!$fp) {
            $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        }
            
        if (!$fp) {
            return false;
        } else {
            if ($this->deviceAccessCode) {
                fwrite($fp, $this->deviceAccessCode);
                $auth = fgets($fp, 11);
                if ($auth == 'Autorizado') {
                    $message = hex2bin($this->calculateChecksum('003d' . $deviceType . $deviceNumber));
                    fwrite($fp, $message);
                    $res = strval(fgets($fp, 11));
                    echo hex2bin(substr(bin2hex($this->removeExtraByte($res)), 8, 17)), PHP_EOL;
                } else { 
                    return false;
                  }
            } else {
                $message = hex2bin($this->calculateChecksum('003d' . $deviceType . $deviceNumber));
                fwrite($fp, $message);
                $res = strval(fgets($fp, 11));
                echo hex2bin(substr(bin2hex($this->removeExtraByte($res)), 8, 17)), PHP_EOL;
            }
          }
    }
    
    
    
    // PC 66: Ler entradas digitais - RECEPTOR
    public function leSensor($deviceType, $deviceNumber, $sensor = 0) {
        $deviceType = $this->normalizeDeviceType($deviceType);
        $deviceNumber = $this->normalizeDeviceNumber($deviceNumber);
        
        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            if ($this->deviceAccessCode) {
                fwrite($fp, $this->deviceAccessCode);
                $auth = fgets($fp, 11);
                if ($auth == 'Autorizado') {
                    $message = hex2bin($this->calculateChecksum('0042' . $deviceType . $deviceNumber));
                    fwrite($fp, $message);
                    $sensor = str_split(bin2hex(fgets($fp, 7)), 2);
                    echo $sensor[0], $sensor[1], $sensor[2], $sensor[3], $sensor[4], $sensor[5];
                } else { 
                    return false;
                  }
            } else {
                $message = hex2bin($this->calculateChecksum('0042' . $deviceType . $deviceNumber));
                fwrite($fp, $message);
                //$sensor = str_split(bin2hex(fgets($fp, 7)), 2);
                $sensor = strval(fgets($fp, 7));
                echo $sensor[0], $sensor[1], $sensor[2], $sensor[3], $sensor[4], $sensor[5];
            }
          }
    }
    
    
    
    // PC 92: Acionar saídas (Relés dos Receptores) - AVANÇADO
    public function acionaReleAvancado($deviceType, $deviceNumber, $deviceOutputNumber, $tempo = 1, $shouldGenerateEvent = 1) {
        $deviceType = $this->normalizeDeviceType($deviceType);
        $deviceNumber = $this->normalizeDeviceNumber($deviceNumber);
        $deviceOutputNumber = str_pad(strval($deviceOutputNumber), 2, '0', STR_PAD_LEFT); /* 1, 2, 3 ou 4 */
        $shouldGenerateEvent = strval($shouldGenerateEvent);
        
        if (is_int($tempo) || ctype_digit($tempo)) {
            if ((0 <= intval($tempo)) && (intval($tempo) <= 255)) { /* Tempo deve estar entre 0 e 255 segundos */
                $tempo = dechex($tempo);
                $tempo = str_pad(strval($tempo), 2, '0', STR_PAD_LEFT);
            } else {
                return false;
            }
        } else {
            return false;
        }
        
        switch ($deviceOutputNumber) {
            case '01':
            case '02':
            case '03':
            case '04':
                break;
            default:
                return false;
        }
        
        $shouldGenerateEvent = $shouldGenerateEvent ? '01' : '00';
        

        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->deviceAccessCode);
            fgets($fp, 12);
            $message = hex2bin($this->calculateChecksum('005c' . $deviceType . $deviceNumber . $deviceOutputNumber . $shouldGenerateEvent . $tempo));
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }
    
    
    
    // PC 93: Ler entradas digitais (Avançado) - RECEPTOR
    public function leSensorAvancado($deviceType, $deviceNumber, $reader = 0, $digitalInput = 0) {
        //   Se especificados "$reader" e "$digitalInput", retorna 0 (desligado) ou 1 (ligado).
        //   Caso não sejam especificados "$reader" e "$digitalInput", retorna uma string representando um
        // número binário de 16 posições, sendo 4 posições para cada leitor (reader), da esquerda para direta, do maior
        // para o menor (ex: 0000 -> ED4, ED3, ED2, ED1).
    
        $deviceType = $this->normalizeDeviceType($deviceType);
        $deviceNumber = $this->normalizeDeviceNumber($deviceNumber);
        
        switch ($digitalInput) {
            case 'e1':
            case 'E1':
                switch ($reader) {
                    case 1:
                        $ed = 0;
                        break;
                    case 2:
                        $ed = 4;
                        break;
                    case 3:
                        $ed = 8;
                        break;
                    case 4:
                        $ed = 12;
                        break;
                }
                break;
            case 'e2':
            case 'E2':
                switch ($reader) {
                    case 1:
                        $ed = 1;
                        break;
                    case 2:
                        $ed = 5;
                        break;
                    case 3:
                        $ed = 9;
                        break;
                    case 4:
                        $ed = 13;
                        break;
                }
                break;
            case 'e3':
            case 'E3':
                switch ($reader) {
                    case 1:
                        $ed = 2;
                        break;
                    case 2:
                        $ed = 6;
                        break;
                    case 3:
                        $ed = 10;
                        break;
                    case 4:
                        $ed = 14;
                        break;
                }
                break;
            case 'e4':
            case 'E4':
                switch ($reader) {
                    case 1:
                        $ed = 3;
                        break;
                    case 2:
                        $ed = 7;
                        break;
                    case 3:
                        $ed = 11;
                        break;
                    case 4:
                        $ed = 15;
                        break;
                }
                break;
            default:
                $digitalInput = 0;
        }
        
        $fp = fsockopen($this->deviceIpAddress, $this->deviceTcpPort, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            if ($this->deviceAccessCode) {
                fwrite($fp, $this->deviceAccessCode);
                $auth = fgets($fp, 11);
                if ($auth == 'Autorizado') {
                    $message = hex2bin($this->calculateChecksum('005d' . $deviceType . $deviceNumber));
                    fwrite($fp, $message);
                    $sensor = strval(fgets($fp, 8));
                    $sensor = substr($this->removeExtraByte(bin2hex($sensor)), 8, 4);
                    $sensor = strval(base_convert($sensor, 16, 2));
                    $sensor = str_pad($sensor, 16, '0', STR_PAD_LEFT);
                    if (!$digitalInput) {
                        return $sensor;
                    } else {
                        $sensor = array_reverse(str_split($sensor, 1));
                        return $sensor[$ed];
                    }
                } else { 
                    return false;
                  }
            } else {
                $message = hex2bin($this->calculateChecksum('005d' . $deviceType . $deviceNumber));
                fwrite($fp, $message);
                $sensor = strval(fgets($fp, 8));
                $sensor = substr($this->removeExtraByte(bin2hex($sensor)), 8, 4);
                $sensor = strval(base_convert($sensor, 16, 2));
                $sensor = str_pad($sensor, 16, '0', STR_PAD_LEFT);
                if (!$digitalInput) {
                    return $sensor;
                } else {
                    $sensor = array_reverse(str_split($sensor, 1));
                    return $sensor[$ed];
                }
            }
          }
    }
}
