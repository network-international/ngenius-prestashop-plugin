<?php

namespace NGenius\Http;

use NGenius\Config\Config;
use NGenius\Logger;
use stdClass;
use Exception;
use Ngenius\NgeniusCommon\NgeniusHTTPCommon;
use Ngenius\NgeniusCommon\NgeniusHTTPTransfer;

abstract class AbstractTransaction
{
    public const CUP_RESULTS_LITERAL = "cnp:china_union_pay_results";
    public const NGENIUS_CAPTURE_LITERAL = 'cnp:capture';
    public const NGENIUS_REFUND_LITERAL = 'cnp:refund';

    /**
     * Processing of API request body
     *
     * @param array $data
     * @return string
     */
    protected function preProcess(array $data): string
    {
        return json_encode($data);
    }

    /**
     * Processing of API response
     *
     * @param string $responseString
     * @return array|null
     */
    abstract protected function postProcess(string $responseString): ?array;
}
