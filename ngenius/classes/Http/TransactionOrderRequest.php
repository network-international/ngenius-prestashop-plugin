<?php

namespace NGenius\Http;

use NGenius\Command;
use NGenius\Config\Config;
use NGenius\Http\AbstractTransaction;

class TransactionOrderRequest extends AbstractTransaction
{
    /**
     * Processing of API response
     *
     * @param $responseString
     * @return array|null
     */
    public function postProcess($responseString): ?array
    {
        if ($responseString) {
            return json_decode($responseString, true);
        }
        return null;
    }
}
