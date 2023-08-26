<?php

namespace Evil1991\PlanfixClient\Exception;

use Exception;

class ClientException extends Exception
{
    public function render()
    {
        return response('', 500)->json([
            'error' => $this->getMessage(),
        ]);
    }
}