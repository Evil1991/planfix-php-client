<?php

namespace Evil1991\PlanfixClient\Contract;

use Illuminate\Http\Client\Response;

interface PlafixClient
{
    /**
     * Преобразование ответа в массив
     * @param Response $response
     * 
     * @return array
     */
    public function response(Response $response) : array;

    /**
     * Делает запрос к API
     * @param array $params
     * - method - тип запроса post|get
     * - action - метод API
     * - fields - параметры
     * 
     * @return Response
     */
    public function send(array $params) : array;
}