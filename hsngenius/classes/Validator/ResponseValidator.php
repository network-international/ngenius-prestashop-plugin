<?php

/**
 * Hosted Session Ngenius Response Validator
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */
include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';

class ResponseValidator
{

    /**
     * Performs response validation for transaction
     *
     * @param array $response
     * @return array|bool
     */
    public function validate($responseEnc)
    {
        $response = json_decode($responseEnc);
        if (isset($response->orderReference)) {
            if ($response->state == 'AWAIT_3DS') {
                $command = new Command();
                $command->updateStatusAwait3ds($response);
            }
            return $responseEnc;
        } elseif (isset($response->message) && isset($response->code)) {
            $error =  array('error' => "Error! #".$response->code." : ".$response->message);
            return json_encode($error);
        } else {
            return '{ "error":"Oops something went wrong!." }';
        }
    }
}
