<?php

namespace Evil1991\PlanfixClient\Client;

use Evil1991\PlanfixClient\Contract\PlanfixClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class PlanfixXmlClient implements PlanfixClient
{
    private $url = 'https://apiru.planfix.ru/xml';
    private $key;
    private $secret;
    private $account;
    private $login;
    private $password;
    private $sid;

    /**
     * @param array $params
     * - key - ключ API
     * - secret - секретный ключ
     * - account - аккаунт
     * - login - логин
     * - password - пароль
     */
    public function __construct(array $params = [])
    {
        $this->key = $params['key'];
        $this->secret = $params['secret'];
        $this->account = $params['account'];
        $this->login = $params['login'];
        $this->password = $params['password'];
    }

    /**
     * Создаем xml
     * @param string $method
     * @return SimpleXMLElement
     * @throws \Exception
     */
    protected function createXml(string $method) : SimpleXMLElement
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request method="' . $method . '"></request>');
        $xml->account = $this->account;
        return $xml;
    }

    /**
     * Добавление параметров в xml
     * @param SimpleXMLElement $xml
     * @param array $params
     * 
     * @return SimpleXMLElement
     */
    protected function setParams(SimpleXMLElement $xml, array $params = []) : SimpleXMLElement
    {
        foreach ($params as $key => $value) {
            if(is_array($value)) {
                if(is_string(array_key_first($value)) === true) {
                    $xml->$key = new SimpleXMLElement("<$key/>");
                    $this->setParams($xml->$key, $value);
                } else {
                    foreach($value as $val) {
                        $xml->addChild($key, $val);
                    }
                }
            } else {
                $xml->addChild($key, $value);
            }
        }

        return $xml;
    }

    /**
     * Создание hash-подписи
     * @param $xml
     * @return string
     */
    private function getSignature(SimpleXMLElement $xml) : string
    {
        return md5($this->normalizeXml($xml) . $this->secret);
    }

    /**
     * Normalize the XML request
     *
     * @param SimpleXMLElement $node The XML request
     * @return string the Normalized string
     */
    private function normalizeXml($node) {
        $node = (array) $node;
        ksort($node);

        $normStr = '';

        foreach ($node as $child) {
            if (is_array($child)) {
                $normStr .= implode('', array_map(array($this,'normalizeXml'), $child));
            } elseif (is_object($child)) {
                $normStr .= $this->normalizeXml($child);
            } else {
                $normStr .= (string) $child;
            }
        }

        return $normStr;
    }

    /**
     * Авторизация
     * @return mixed|null
     * @throws \Exception
     */
    private function auth()
    {
        if(!isset($this->sid)) {

            $xml = $this->createXml('auth.login');

            $this->setParams($xml, [
                'login' => $this->login,
                'password' => $this->password,
            ]);

            $sign = $this->getSignature($xml);
            $this->setParams($xml, [
                'signature' => $sign
            ]);

            $response = $this->post($xml);

            if(!isset($response['sid'])) {
                // TODO: Переделать на Response
                throw new BadRequestException('Fail auth');
            }

            $this->sid = $response['sid'];
        }
    }

    public function send($params = []): array
    {
        if(!isset($params['action'])) {
            return $this->response();
        }

        $this->auth();

        $xml = $this->createXml($params['action']);
        $xml->sid = $this->sid;

        if(isset($params['fields']) && is_array($params['fields'])) {
            $this->setParams($xml, $params['fields']);
        }

        return $this->post($xml);
    }

    public function response(Response $response = null): array
    {
        if ($response && $response->successful()) {
            
            $xml = new SimpleXMLElement($response->body());
            $result = json_decode(json_encode($xml), true);

            $params = $result['@attributes'];
            unset($result['@attributes']);

            $return = $result;

            if($params['status'] === 'error') { 
                $return = [
                    'result' => 'error',
                    'error' => 'Error. Message: ' . $result['message'] . '. Code: ' . $result['code'],
                    'response' => $response
                ];
            } else {
                $return['result'] = 'success';
            }
            
        } else {
            $return = [
                'result' => 'error',
                'error' => 'Request error 2',
                'response' => $response
            ];
        }

        return $return;
    }

    /**
     * Отправка post-запроса
     * @param SimpleXMLElement $xml
     * @return array
     */
    private function post(SimpleXMLElement $xml) : array
    {
        $response = Http::withBasicAuth($this->key, '')
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->withBody($xml->asXML(), 'application/xml')
            ->post($this->url);

        return $this->response($response);
    }
}
