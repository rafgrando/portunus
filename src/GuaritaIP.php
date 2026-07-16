<?php 

namespace Rafgrando\Portunus;

class GuaritaIP
{   
    public $endereco_disp = '192.168.0.10';
    public $porta_disp = 9000;
    public $codigo_acesso = '';
    public $timeout = 4;


    protected function calculaChecksum($input) {
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
        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
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
        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
            fgets($fp, 12);
            $message = hex2bin('000c0c');
            fwrite($fp, $message);
            $response = fgets($fp, 10);
            fclose($fp);
            return bin2hex($response);
          }
    }



    // PC 13: Acionar saídas (Relés dos Receptores)
    public function acionaRele($tipo_disp, $num_disp, $rele, $gera_evt = 1) {
        $tipo_disp = strval($tipo_disp); /* 1 = TX; 2 = TAG Ativo; 3 = CT; 5 = Biometria; 6 = TAG Passivo; 7 = Senha */
        $num_disp = str_pad(strval($num_disp), 2, '0', STR_PAD_LEFT); /* CAN 1 a CAN 8; valores de 0 a 7 */
        $rele = str_pad(strval($rele), 2, '0', STR_PAD_LEFT); /* 1, 2, 3 ou 4 */
        $gera_evt = strval($gera_evt);
        
        
        switch ($tipo_disp) {
            case '1':
            case '01':
            case 'RF':
            case 'rf':
            case 'TX':
            case 'tx':
                $tipo_disp = '01';
                break;
            case '2':
            case '02':
            case 'TA':
            case 'ta':
                $tipo_disp = '02';
                break;
            case '3':
            case '03':
            case 'CT':
            case 'ct':
            case 'CTW':
            case 'ctw':
            case 'CTWB':
            case 'ctwb':
                $tipo_disp = '03';
                break;
            case '5':
            case '05':
            case 'BM':
            case 'bm':
            case 'BIO':
            case 'bio':
                $tipo_disp = '05';
                break;
            case '6':
            case '06':
            case 'TP':
            case 'tp':
                $tipo_disp = '06';
                break;
            case '7':
            case '07':
            case 'SN':
            case 'sn':
                $tipo_disp = '07';
                break;
            default:
                return false;
        }
        
        switch ($num_disp) {
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
        
        switch ($rele) {
            case '01':
            case '02':
            case '03':
            case '04':
                break;
            default:
                return false;
        }
        
        if (!$gera_evt || $gera_evt == '0') {
            $gera_evt = '00';
        } else {
            $gera_evt = '01';
        }

        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
            fgets($fp, 12);
            $message = hex2bin($this->calculaChecksum('000d' . $tipo_disp . $num_disp . $rele . $gera_evt));
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }
    
    
    
    // PC 18: Reiniciar Guarita (Efetiva Config. Ethernet)
    public function reiniciaGuaritaIP() {
        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
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
        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
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
        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
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
    public function ativaModoRemotoProg($tipo_disp, $num_disp, $tempo) {
        $tipo_disp = strval($tipo_disp); /* 1 = TX; 2 = TAG Ativo; 3 = CT; 5 = Biometria; 6 = TAG Passivo; 7 = Senha */
        $num_disp = str_pad(strval($num_disp), 2, '0', STR_PAD_LEFT); /* CAN 1 a CAN 8; valores de 0 a 7 */
        
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
        
        
        switch ($tipo_disp) {
            case 'TODOS':
            case 'todos':
            case 'ALL':
            case 'all':
            case 'FF':
            case 'ff':
                $tipo_disp = 'ff';
                break;
            case '1':
            case '01':
            case 'RF':
            case 'rf':
            case 'TX':
            case 'tx':
                $tipo_disp = '01';
                break;
            case '2':
            case '02':
            case 'TA':
            case 'ta':
                $tipo_disp = '02';
                break;
            case '3':
            case '03':
            case 'CT':
            case 'ct':
            case 'CTW':
            case 'ctw':
            case 'CTWB':
            case 'ctwb':
                $tipo_disp = '03';
                break;
            case '5':
            case '05':
            case 'BM':
            case 'bm':
            case 'BIO':
            case 'bio':
                $tipo_disp = '05';
                break;
            case '6':
            case '06':
            case 'TP':
            case 'tp':
                $tipo_disp = '06';
                break;
            case '7':
            case '07':
            case 'SN':
            case 'sn':
                $tipo_disp = '07';
                break;
            default:
                return false;
        }
        
        switch ($num_disp) {
            case 'TODOS':
            case 'todos':
            case 'ALL':
            case 'all':
            case 'FF':
            case 'ff':
                $tipo_disp = 'ff';
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


        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
            fgets($fp, 12);
            $message = hex2bin($this->calculaChecksum('0027' . $tipo_disp . $num_disp . $tempo));
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }



    // PC 61: Ler versão do Receptor (Firmware)
    public function leVersaoReceptor($tipo_disp, $num_disp, $fp = null) {
        $tipo_disp = strval($tipo_disp); /* 1 = TX; 2 = TAG Ativo; 3 = CT; 5 = Biometria; 6 = TAG Passivo; 7 = Senha */
        $num_disp = str_pad(strval($num_disp), 2, '0', STR_PAD_LEFT); /* CAN 1 a CAN 8; valores de 0 a 7 */
        
        switch ($tipo_disp) {
            case '1':
            case '01':
            case 'RF':
            case 'rf':
            case 'TX':
            case 'tx':
                $tipo_disp = '01';
                break;
            case '2':
            case '02':
            case 'TA':
            case 'ta':
                $tipo_disp = '02';
                break;
            case '3':
            case '03':
            case 'CT':
            case 'ct':
            case 'CTW':
            case 'ctw':
            case 'CTWB':
            case 'ctwb':
                $tipo_disp = '03';
                break;
            case '5':
            case '05':
            case 'BM':
            case 'bm':
            case 'BIO':
            case 'bio':
                $tipo_disp = '05';
                break;
            case '6':
            case '06':
            case 'TP':
            case 'tp':
                $tipo_disp = '06';
                break;
            case '7':
            case '07':
            case 'SN':
            case 'sn':
                $tipo_disp = '07';
                break;
            default:
                return false;
        }
        
        switch ($num_disp) {
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
        
        if (!$fp) {
            $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        }
            
        if (!$fp) {
            return false;
        } else {
            if ($this->codigo_acesso) {
                fwrite($fp, $this->codigo_acesso);
                $auth = fgets($fp, 11);
                if ($auth == 'Autorizado') {
                    $message = hex2bin($this->calculaChecksum('003d' . $tipo_disp . $num_disp));
                    fwrite($fp, $message);
                    $res = strval(fgets($fp, 11));
                    echo hex2bin(substr(bin2hex($this->removeExtraByte($res)), 8, 17)), PHP_EOL;
                } else { 
                    return false;
                  }
            } else {
                $message = hex2bin($this->calculaChecksum('003d' . $tipo_disp . $num_disp));
                fwrite($fp, $message);
                $res = strval(fgets($fp, 11));
                echo hex2bin(substr(bin2hex($this->removeExtraByte($res)), 8, 17)), PHP_EOL;
            }
          }
    }
    
    
    
    // PC 66: Ler entradas digitais - RECEPTOR
    public function leSensor($tipo_disp, $num_disp, $sensor = 0) {
        $tipo_disp = strval($tipo_disp); /* 1 = TX; 2 = TAG Ativo; 3 = CT; 5 = Biometria; 6 = TAG Passivo; 7 = Senha */
        $num_disp = str_pad(strval($num_disp), 2, '0', STR_PAD_LEFT); /* CAN 1 a CAN 8; valores de 0 a 7 */
        
        switch ($tipo_disp) {
            case '1':
            case '01':
            case 'RF':
            case 'rf':
            case 'TX':
            case 'tx':
                $tipo_disp = '01';
                break;
            case '2':
            case '02':
            case 'TA':
            case 'ta':
                $tipo_disp = '02';
                break;
            case '3':
            case '03':
            case 'CT':
            case 'ct':
            case 'CTW':
            case 'ctw':
            case 'CTWB':
            case 'ctwb':
                $tipo_disp = '03';
                break;
            case '5':
            case '05':
            case 'BM':
            case 'bm':
            case 'BIO':
            case 'bio':
                $tipo_disp = '05';
                break;
            case '6':
            case '06':
            case 'TP':
            case 'tp':
                $tipo_disp = '06';
                break;
            case '7':
            case '07':
            case 'SN':
            case 'sn':
                $tipo_disp = '07';
                break;
            default:
                return false;
        }
        
        switch ($num_disp) {
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
        
        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            if ($this->codigo_acesso) {
                fwrite($fp, $this->codigo_acesso);
                $auth = fgets($fp, 11);
                if ($auth == 'Autorizado') {
                    $message = hex2bin($this->calculaChecksum('0042' . $tipo_disp . $num_disp));
                    fwrite($fp, $message);
                    $sensor = str_split(bin2hex(fgets($fp, 7)), 2);
                    //$sensor = strval(fgets($fp, 7));
                    echo $sensor[0], $sensor[1], $sensor[2], $sensor[3], $sensor[4], $sensor[5];
                } else { 
                    return false;
                  }
            } else {
                $message = hex2bin($this->calculaChecksum('0042' . $tipo_disp . $num_disp));
                fwrite($fp, $message);
                //$sensor = str_split(bin2hex(fgets($fp, 7)), 2);
                $sensor = strval(fgets($fp, 7));
                echo $sensor[0], $sensor[1], $sensor[2], $sensor[3], $sensor[4], $sensor[5];
            }
          }
    }
    
    
    
    // PC 92: Acionar saídas (Relés dos Receptores) - AVANÇADO
    public function acionaReleAvancado($tipo_disp, $num_disp, $rele, $tempo = 1, $gera_evt = 1) {
        $tipo_disp = strval($tipo_disp); /* 1 = TX; 2 = TAG Ativo; 3 = CT; 5 = Biometria; 6 = TAG Passivo; 7 = Senha */
        $num_disp = str_pad(strval($num_disp), 2, '0', STR_PAD_LEFT); /* CAN 1 a CAN 8; valores de 0 a 7 */
        $rele = str_pad(strval($rele), 2, '0', STR_PAD_LEFT); /* 1, 2, 3 ou 4 */
        $gera_evt = strval($gera_evt);
        
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
        
        
        switch ($tipo_disp) {
            case '1':
            case '01':
            case 'RF':
            case 'rf':
            case 'TX':
            case 'tx':
                $tipo_disp = '01';
                break;
            case '2':
            case '02':
            case 'TA':
            case 'ta':
                $tipo_disp = '02';
                break;
            case '3':
            case '03':
            case 'CT':
            case 'ct':
            case 'CTW':
            case 'ctw':
            case 'CTWB':
            case 'ctwb':
                $tipo_disp = '03';
                break;
            case '5':
            case '05':
            case 'BM':
            case 'bm':
            case 'BIO':
            case 'bio':
                $tipo_disp = '05';
                break;
            case '6':
            case '06':
            case 'TP':
            case 'tp':
                $tipo_disp = '06';
                break;
            case '7':
            case '07':
            case 'SN':
            case 'sn':
                $tipo_disp = '07';
                break;
            default:
                return false;
        }
        
        switch ($num_disp) {
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
        
        switch ($rele) {
            case '01':
            case '02':
            case '03':
            case '04':
                break;
            default:
                return false;
        }
        
        if (!$gera_evt || $gera_evt == '0') {
            $gera_evt = '00';
        } else {
            $gera_evt = '01';
        }

        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            fwrite($fp, $this->codigo_acesso);
            fgets($fp, 12);
            $message = hex2bin($this->calculaChecksum('005c' . $tipo_disp . $num_disp . $rele . $gera_evt . $tempo));
            fwrite($fp, $message);
            fgets($fp, 2);
            fclose($fp);
            return true;
          }
    }
    
    
    
    // PC 93: Ler entradas digitais (Avançado) - RECEPTOR
    public function leSensorAvancado($tipo_disp, $num_disp, $leitor = 0, $entrada_digital = 0) {
        //   Se especificados "$leitor" e "$entrada_digital", retorna 0 (desligado) ou 1 (ligado).
        //   Caso não sejam especificados "$leitor" e "$entrada_digital", retorna uma string representando um
        // número binário de 16 posições, sendo 4 posições para cada leitor, da esquerda para direta, do maior
        // para o menor (ex: 0000 -> ED4, ED3, ED2, ED1).
    
        $tipo_disp = strval($tipo_disp); /* 1 = TX; 2 = TAG Ativo; 3 = CT; 5 = Biometria; 6 = TAG Passivo; 7 = Senha */
        $num_disp = str_pad(strval($num_disp), 2, '0', STR_PAD_LEFT); /* CAN 1 a CAN 8; valores de 0 a 7 */
        
        switch ($tipo_disp) {
            case '1':
            case '01':
            case 'RF':
            case 'rf':
            case 'TX':
            case 'tx':
                $tipo_disp = '01';
                break;
            case '2':
            case '02':
            case 'TA':
            case 'ta':
                $tipo_disp = '02';
                break;
            case '3':
            case '03':
            case 'CT':
            case 'ct':
            case 'CTW':
            case 'ctw':
            case 'CTWB':
            case 'ctwb':
                $tipo_disp = '03';
                break;
            case '5':
            case '05':
            case 'BM':
            case 'bm':
            case 'BIO':
            case 'bio':
                $tipo_disp = '05';
                break;
            case '6':
            case '06':
            case 'TP':
            case 'tp':
                $tipo_disp = '06';
                break;
            case '7':
            case '07':
            case 'SN':
            case 'sn':
                $tipo_disp = '07';
                break;
            default:
                return false;
        }
        
        switch ($num_disp) {
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
        
        switch ($entrada_digital) {
            case 'e1':
            case 'E1':
                switch ($leitor) {
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
                switch ($leitor) {
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
                switch ($leitor) {
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
                switch ($leitor) {
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
                $entrada_digital = 0;
        }
        
        $fp = fsockopen($this->endereco_disp, $this->porta_disp, $errno, $errstr, $this->timeout);
        if (!$fp) {
            return false;
        } else {
            if ($this->codigo_acesso) {
                fwrite($fp, $this->codigo_acesso);
                $auth = fgets($fp, 11);
                if ($auth == 'Autorizado') {
                    $message = hex2bin($this->calculaChecksum('005d' . $tipo_disp . $num_disp));
                    fwrite($fp, $message);
                    $sensor = strval(fgets($fp, 8));
                    $sensor = substr($this->removeExtraByte(bin2hex($sensor)), 8, 4);
                    $sensor = strval(base_convert($sensor, 16, 2));
                    $sensor = str_pad($sensor, 16, '0', STR_PAD_LEFT);
                    if (!$entrada_digital) {
                        return $sensor;
                    } else {
                        $sensor = array_reverse(str_split($sensor, 1));
                        return $sensor[$ed];
                    }
                } else { 
                    return false;
                  }
            } else {
                $message = hex2bin($this->calculaChecksum('005d' . $tipo_disp . $num_disp));
                fwrite($fp, $message);
                $sensor = strval(fgets($fp, 8));
                $sensor = substr($this->removeExtraByte(bin2hex($sensor)), 8, 4);
                $sensor = strval(base_convert($sensor, 16, 2));
                $sensor = str_pad($sensor, 16, '0', STR_PAD_LEFT);
                if (!$entrada_digital) {
                    return $sensor;
                } else {
                    $sensor = array_reverse(str_split($sensor, 1));
                    return $sensor[$ed];
                }
            }
          }
    }
}
