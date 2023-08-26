<?php

namespace Evil1991\PlanfixClient;

use Evil1991\PlanfixClient\Client\PlanfixRestClient;
use Evil1991\PlanfixClient\Client\PlanfixXmlClient;
use Evil1991\PlanfixClient\Contract\PlafixClient as PlanfixClientInterface;
use Evil1991\PlanfixClient\Exception\ClientException;

class PlanfixClient
{
    public static function createClient(string $type, array $params = []) : PlanfixClientInterface
    {
        try {
            throw new ClientException('TEST EXCEPTION');
        } catch (ClientException $e) {
            $exception = $e;
        }

        

        switch($type)
        {
            case 'xml':
                $client = new PlanfixXmlClient($params);
                break;
            case 'rest':
                $client = new PlanfixRestClient($params['url'], $params['token']);
                break;
        }

        return $client;
    }
}