<?php

namespace App\Extensions\SMSc;

use Psr\Log\LoggerInterface;

class SMSc
{
    const TYPE_HTTP = 0;
    const TYPE_SMTP = 1;

    const COST_DEFAULT = 0;
    const COST_ONLY_GET = 1;
    const COST_SEND = 2;
    const COST_SEND_WITH_BALANCE = 3;

    /** @var int */
    private $cost = self::COST_DEFAULT;
    /** @var string */
    private $smtp_from = 'api@smsc.ua';
    /** @var bool */
    private $force_post = false;
    /** @var bool */
    private $https = true;
    /** @var string */
    private $charset = 'windows-1251';
    /** @var LoggerInterface */
    private $logger;
    /** @var array */
    private $credentials = [];

    /**
     * SMSc constructor.
     * @param string $login
     * @param string $password
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $login, string $password, LoggerInterface $logger = null)
    {
        $this->credentials['login'] = $login;
        $this->credentials['password'] = $password;
        $this->logger = $logger;
    }

    public function getBalance()
    {
        $response = $this->sendCmd('balance'); // (balance) или (0, -error)

        if ($this->logger) {
            if (!isset($response[1])) {
                $this->logger->info('Сумма на счете: ' . $response[0]);
            } else {
                $this->logger->error('Ошибка #' . -$response[1]);
            }
        }

        return isset($response[1]) ? false : $response[0];
    }

    public function getStatus($id, $phone, $all = 0)
    {
        $response = $this->sendCmd("status", "phone=" . urlencode($phone) . "&id=" . urlencode($id) . "&all=" . (int)$all);

        // (status, time, err, ...) или (0, -error)

        if (!strpos($id, ",")) {
            if ($this->logger) {
                if ($response[1] != "" && $response[1] >= 0) {
                    $this->loggerinfo("Статус SMS = $response[0]" . $response[1] ? ', время изменения статуса - ' . date('d.m.Y H:i:s', $response[1]) : '');
                } else {
                    $this->logger->error('Ошибка #' . -$response[1]);
                }
            }

            if ($all && count($response) > 9 && (!isset($response[$idx = $all == 1 ? 14 : 17]) || $response[$idx] != "HLR")) {
                $response = explode(",", implode(",", $response), $all == 1 ? 9 : 12);
            } // ',' в сообщении
        } else {
            if (count($response) == 1 && strpos($response[0], "-") == 2) {
                return explode(",", $response[0]);
            }

            foreach ($response as $k => $v)
            {
                $response[$k] = explode(",", $v);
            }
        }

        return $response;
    }

    /**
     * @param int $cost
     */
    public function setCost(int $cost)
    {
        $this->cost = $cost;
    }

    private function httpSend(Message $message)
    {
        $response = $this->sendCmd('send',
            'cost=' . $this->cost
            . '&phones=' . urlencode($message->getPhones())
            . '&mes=' . urlencode($message->getMessage())
            . '&translit=' . $message->getTranslit()
            . '&id=' . $message->getId()
            . ($message->getFormat() ? '&' . $message->getFormat() : '')
            . ($message->getSender() === false ? '' : '&sender=' . urlencode($message->getSender()))
            . ($message->getTime() ? '&time=' . urlencode($message->getTime()) : '')
            . ($message->getQuery() ? '&' . $message->getQuery() : ''), $message->getFiles());

        // (id, cnt, cost, balance) или (id, -error)

        if ($this->logger) {
            if ($response[1] > 0) {
                if ($this->cost == self::COST_ONLY_GET) {
                    $this->logger->info("Стоимость рассылки: $response[0]. Всего SMS: $response[1]");
                } else {
                    $this->logger->info("Сообщение отправлено успешно. ID: $response[0], всего SMS: $response[1]" . ($this->cost >= self::COST_SEND ? ", стоимость: $response[2]" : '') . ($this->cost >= self::COST_SEND_WITH_BALANCE ? ", баланс: $response[3]." : '.'));
                }
            } else {
                $this->logger->error('Ошибка №' . -$response[1] . $response[0] ? ', ID: ' . $response[0] : '');
            }
        }

        return $response;
    }

    private function smtpSend(Message $message)
    {
        $body = $this->credentials['login'] . ':' . $this->credentials['password']
            . ':' . $message->getId()
            . ':' . $message->getTime()
            . ':' . $message->getTranslit()
            . ':' . $message->getFormat()
            . ':' . $message->getSender()
            . ':' . $message->getPhones()
            . ':' . $message->getMessage();

        return mail('send@send.smsc.ua', '', $body, 'From: ' . $this->smtp_from . "\nContent-Type: text/plain; charset=" . $this->charset . "\n");
    }

    /**
     * @param string $smtp_from
     */
    public function setSmtpFrom(string $smtp_from)
    {
        $this->smtp_from = $smtp_from;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset)
    {
        $charset = strtolower($charset);
        if (in_array($charset, ['utf-8', 'koi8-r', 'windows-1251'])) {
            $this->charset = $charset;
        }
    }

    /**
     * @param bool $https
     */
    public function setHttps(bool $https)
    {
        $this->https = $https;
    }

    /**
     * @param bool $force_post
     */
    public function setForcePost(bool $force_post)
    {
        $this->force_post = $force_post;
    }

    /**
     * @param array|Message $messages
     * @param int $type
     * @return array
     * @throws \Exception
     */
    public function send($messages, int $type = self::TYPE_HTTP)
    {
        switch ($type) {
            case self::TYPE_HTTP:
                $sender = 'httpSend';
                break;
            case self::TYPE_SMTP:
                $sender = 'smtpSend';
                break;
            default:
                throw new \Exception('Unknown type');
        }

        if ( is_array($messages) ) {
            $responses = [];
            foreach ($messages as $message)
            {
                $responses[] = $this->{$sender}($message);
            }

            return $responses;
        }

        return $this->{$sender}($messages);
    }

    private function sendCmd(string $cmd, $args = '', $files = [])
    {
        $path = '/sys/' . $cmd . '.php?'
            . 'login=' . urlencode($this->credentials['login'])
            . '&psw=' . urlencode($this->credentials['password'])
            . '&fmt=1&charset=' . $this->charset . '&' . $args;

        $tries = 0;
        $response = '';

        do {
            if ($tries++) {
                $url = ($this->https ? 'https' : 'http') . '://www' . $tries . '.smsc.ua' . $path;
                $response = $this->get($url, $files, 3 + $tries);
            }
        } while ($response == '' && $tries < 5);

        if ($response == '') {
            if ($this->logger) {
                $this->logger->error('Ошибка чтения адреса: ' . $url);
            }

            $response = ','; // фиктивный ответ
        }

        $delimiter = ",";

        if ($cmd == "status") {
            parse_str($args, $m);

            if (strpos($m["id"], ",")) {
                $delimiter = "\n";
            }
        }

        return explode($delimiter, $response);
    }

    private function get($url, $files, $tm = 5)
    {
        $response = '';
        $post = $this->force_post || strlen($url) > 2000 || $files;

        if (function_exists('curl_init')) {
            $c = 0; // keepalive

            if (!$c) {
                $c = curl_init();
                curl_setopt_array($c, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => $tm,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => ['Expect:']
                ]);
            }

            curl_setopt($c, CURLOPT_POST, $post);

            if ($post) {
                list($url, $post) = explode('?', $url, 2);

                if ($files) {
                    parse_str($post, $m);

                    foreach ($m as $k => $v)
                    {
                        $m[$k] = isset($v[0]) && $v[0] == '@' ? sprintf("\0%s", $v) : $v;
                    }

                    $post = $m;
                    foreach ($files as $i => $path) {
                        if (file_exists($path)) {
                            $post["file" . $i] = function_exists('curl_file_create') ? curl_file_create($path) : '@' . $path;
                        }
                    }
                }

                curl_setopt($c, CURLOPT_POSTFIELDS, $post);
            }

            curl_setopt($c, CURLOPT_URL, $url);

            $response = curl_exec($c);
        } elseif ($files) {
            if ($this->logger) {
                $this->logger->warning('Не установлен модуль curl для передачи файлов');
            }
        } else {
            if (!$this->https && function_exists("fsockopen")) {
                $m = parse_url($url);

                if (!$fp = fsockopen($m["host"], 80, $errno, $errstr, $tm)) {
                    $fp = fsockopen("212.24.33.196", 80, $errno, $errstr, $tm);
                }

                if ($fp) {
                    stream_set_timeout($fp, 60);

                    fwrite($fp, ($post ? "POST $m[path]" : "GET $m[path]?$m[query]")." HTTP/1.1\r\nHost: smsc.ua\r\nUser-Agent: PHP".($post ? "\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($m['query']) : "")."\r\nConnection: Close\r\n\r\n".($post ? $m['query'] : ""));

                    while (!feof($fp))
                    {
                        $response .= fgets($fp, 1024);
                    }
                    list(, $response) = explode("\r\n\r\n", $response, 2);

                    fclose($fp);
                }
            } else {
                $response = file_get_contents($url);
            }
        }

        return $response;
    }
}
