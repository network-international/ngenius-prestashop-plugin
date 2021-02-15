<?php

/**
 * Hosted Session Ngenius Delete saved card Module Front Controller
 *
 * @category   NetworkInternational
 * @package    NetworkInternational_Hsngenius
 * @author     Abzer <info@abzer.com>
 */

include_once _PS_MODULE_DIR_.'hsngenius/classes/Command.php';

class HsngeniusDeletesavedcardModuleFrontController extends ModuleFrontController
{
      
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $command = new Command();
        if (isset($_POST['svid'])) {
            $savedCardId =  $_POST['svid'];
            if ($command->deleteCustomerSavedCard($_POST['svid'])) {
                echo $savedCardId;
                exit;
            }
        }
    }
}
