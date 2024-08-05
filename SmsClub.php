<?php

namespace YourVendor\SmsClub;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SmsClub
{
    const SMSCLUB_API_HOST = 'https://im.smsclub.mobi';
    const SMSCLUB_URL_SMS_SEND = '/sms/send';
    const SMSCLUB_URL_SMS_STATUS = '/sms/status';
    const SMSCLUB_URL_SMS_ORIGINATOR = '/sms/originator';
    const SMSCLUB_URL_SMS_BALANCE = '/sms/balance';
    const SMSCLUB_ARRAY_LIMIT = 100;

    private $login;
    private $token;
    private $integrationId;
    private $client;

    public function __construct($login, $token)
    {
        $this->client = new Client();
        $this->login = $login;
        $this->token = $token;
    }

    public function setLogin($login)
    {
        $this->checkLogin($login);
        $this->login = $login;
    }

    public function setToken($token)
    {
        $this->checkToken($token);
        $this->token = $token;
    }

    public function smsSend($alphaName, $message, $phoneList)
    {
        $this->checkAlphaName($alphaName)
            ->checkMessage($message);

        $data = [
            'phone' => $this->preparePhone($phoneList),
            'message' => $message,
            'src_addr' => $alphaName,
        ];

        if (!empty($this->integrationId)) {
            $data['integration_id'] = $this->integrationId;
        }

        return $this->sendCommand(self::SMSCLUB_URL_SMS_SEND, $data);
    }

    public function smsStatus($smsIdList)
    {
        return $this->sendCommand(self::SMSCLUB_URL_SMS_STATUS, [
            'id_sms' => $this->prepareSmsIdList($smsIdList),
        ]);
    }

    public function getSignatures()
    {
        return $this->sendCommand(self::SMSCLUB_URL_SMS_ORIGINATOR);
    }

    public function getBalance()
    {
        return $this->sendCommand(self::SMSCLUB_URL_SMS_BALANCE);
    }

    private function checkLogin($login)
    {
        if (!preg_match('/^380\d{9}$/', $login)) {
            throw new \InvalidArgumentException('Login must be in format: 380YYXXXXXXX (YY - operator code, XXXXXXX - abonent number)');
        }

        return $this;
    }

    private function checkToken($token)
    {
        if (!is_string($token)) {
            throw new \InvalidArgumentException('Wrong token. Must be string');
        }

        if (empty($token)) {
            throw new \InvalidArgumentException('Token can\'t be empty');
        }

        return $this;
    }

    private function checkIntegrationId($integrationId)
    {
        if (!is_numeric($integrationId)) {
            throw new \InvalidArgumentException('Wrong type of integration ID. Should be numeric');
        }

        return $this;
    }

    private function checkAlphaName($alphaName)
    {
        if (!preg_match('/^[\w\s.-]{1,11}$/', $alphaName)) {
            throw new \InvalidArgumentException('Wrong alpha-name');
        }

        return $this;
    }

    private function checkMessage($message)
    {
        if (!is_string($message)) {
            throw new \InvalidArgumentException('Message must be string');
        }

        return $this;
    }

    private function checkPhone($phone)
    {
        if (!preg_match('/^380\d{9}$/', $phone)) {
            throw new \InvalidArgumentException('Wrong phone number: ' . $phone);
        }

        return $this;
    }

    private function checkSmsId($smsId)
    {
        if (!is_numeric($smsId)) {
            throw new \InvalidArgumentException('Wrong SMS ID: ' . $smsId);
        }

        return $this;
    }

    private function preparePhone($phoneList)
    {
        if (!is_array($phoneList)) {
            $phoneList = [$phoneList];
        } elseif (count($phoneList) > self::SMSCLUB_ARRAY_LIMIT) {
            throw new \InvalidArgumentException(
                'One-time sending limit has been exceeded. Should be no more than ' . self::SMSCLUB_ARRAY_LIMIT . ' numbers in the array'
            );
        }

        return array_map(function ($phone) {
            $phone = preg_replace('/[^\d]/', '', $phone);
            $this->checkPhone($phone);

            return $phone;
        }, $phoneList);
    }

    private function prepareSmsIdList($smsIdList)
    {
        if (!is_array($smsIdList)) {
            $smsIdList = [$smsIdList];
        } elseif (count($smsIdList) > self::SMSCLUB_ARRAY_LIMIT) {
            throw new \InvalidArgumentException(
                'One-time sending limit has been exceeded. Should be no more than ' . self::SMSCLUB_ARRAY_LIMIT . ' IDs in the array'
            );
        }

        return array_map(function ($smsId) {
            $this->checkSmsId($smsId);

            return $smsId;
        }, $smsIdList);
    }

    private function sendCommand($url, $data = [])
    {
        try {
            $response = $this->client->request('POST', self::SMSCLUB_API_HOST . $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json'
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
