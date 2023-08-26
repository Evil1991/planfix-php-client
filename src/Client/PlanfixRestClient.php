<?php

namespace Evil1991\PlanfixClient\Client;

use Evil1991\PlanfixClient\Contract\PlanfixClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PlanfixRestClient implements PlanfixClient
{
    private $url;

    private $token;

    public function __construct($url, $token)
    {
        $this->url = $url;
        $this->token = $token;
    }

    public function send(array $params = []): array
    {
        if(!isset($params['method']) || !isset($params['action'])) {
            return $this->response();
        }

        switch ($params['method']) {
            case 'get':

                $response = Http::withToken($this->token)
                    ->acceptJson()
                    ->get($this->url . '/' . $params['action'], isset($params['fields']) ? $params['fields'] : []);

                break;
            case 'post':

                $response = Http::withToken($this->token)
                    ->acceptJson()
                    ->post($this->url . '/' . $params['action'], isset($params['fields']) ? $params['fields'] : []);

                break;
            default:
                // TODO: возврат ошибки через Response
                return $this->response();
        }

        return $this->response($response);
    }

    public function response(Response $response = null): array
    {
        if ($response && $response->successful()) {
            $return = $response->json();
        } else {
            $return = [
                'result' => 'error',
                'error' => 'Request error'
            ];
        }

        return $return;
    }
}
