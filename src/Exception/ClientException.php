<?php

namespace Evil1991\PlanfixClient\Exception;

use Exception;

class ClientException extends Exception
{
    public function render($message)
    {
        return response('', 500)->json([
            'error' => $message,
        ]);
    }
}