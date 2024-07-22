<?php

namespace NGenius;

use NGenius\Config\Config;
use Ngenius\NgeniusCommon\Formatter\ValueFormatter;

class Model
{
    const ID_ORDER_LITERAL = 'id_order ="';

    /**
     * Place Ngenius Order
     *
     * @param array $data
     *
     * @return bool
     */
    public function placeNgeniusOrder($data)
    {
        $currencyCode = $data['currency'];

        $amount = $data['amount'];

        $amount = ValueFormatter::formatOrderStatusAmount($currencyCode, $amount);

        $insertData = array(
            'id_cart'      => (int)$data['id_cart'],
            'id_order'     => (int)$data['id_order'],
            'amount'       => (float)($amount / 100),
            'currency'     => pSQL($currencyCode),
            'reference'    => pSQL($data['reference']),
            'action'       => pSQL($data['action']),
            'status'       => pSQL($data['status']),
            'state'        => pSQL($data['state']),
            'outlet_id'    => pSQL($data['outlet_id']),
            'id_payment'   => null,
            'capture_amt'  => null,
            'refunded_amt' => null,
        );

        if (self::getNgeniusOrderByCartId($insertData['id_cart'])) {
            return self::updateNgeniusOrderByCartId($insertData);
        }

        return (\Db::getInstance()->insert("ning_online_payment", $insertData))
            ? (bool)true : (bool)false;
    }

    /**
     * Gets Ngenius Order
     *
     * @param int $orderId
     *
     * @return bool|array
     */
    public static function getNgeniusOrder($orderId)
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from("ning_online_payment")
            ->where(self::ID_ORDER_LITERAL . pSQL($orderId) . '"');

        return \Db::getInstance()->getRow($sql);
    }

    /**
     * Gets Ngenius Order
     *
     * @param $cartId
     *
     * @return array|bool
     */
    public static function getNgeniusOrderByCartId($cartId): array|bool
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from("ning_online_payment")
            ->where('id_cart ="' . pSQL($cartId) . '"');

        return \Db::getInstance()->getRow($sql);
    }

    /**
     * Updates Ngenius Order
     *
     * @param $cartId
     *
     * @return array|bool
     */
    public static function updateNgeniusOrderByCartId($data): bool
    {
        return \Db::getInstance()->update(
            'ning_online_payment',
            $data,
            'id_cart = "' . pSQL($data['id_cart']) . '"'
        );
    }

    /**
     * Deletes ngenius order by reference
     *
     * @param string $reference
     *
     * @return void
     */
    public static function deleteNgeniusOrder(int $cartId): void
    {
        $tableName = 'ning_online_payment';

        $db = \Db::getInstance();

        $sql = "DELETE FROM `" . _DB_PREFIX_ . "$tableName` WHERE `id_cart` = '" . pSQL($cartId) . "'";

        $db->execute($sql);
    }

    /**
     * Gets Ngenius Order
     *
     * @param int $orderId
     *
     * @return bool
     */
    public static function getNgeniusOrderReference($orderRef)
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from("ning_online_payment")
            ->where('reference ="' . pSQL($orderRef) . '"');

        return \Db::getInstance()->getRow($sql);
    }

    /**
     * Update Nngenius Networkinternational order table
     *
     * @param array $data
     *
     * @return bool
     */
    public static function updateNgeniusNetworkinternational($data)
    {
        \Db::getInstance()->update(
            'ning_online_payment',
            $data,
            'reference = "' . pSQL($data['reference']) . '"'
        );
    }

    /**
     * Gets Customer Thread
     *
     * @param array $order
     *
     * @return array|bool
     */
    public static function getCustomerThread($order)
    {
        $sql = new \DbQuery();
        $sql->select('*')->from("customer_thread")->where(self::ID_ORDER_LITERAL . (int)$order->id . '"');
        if ($thread = \Db::getInstance()->getRow($sql)) {
            return $thread;
        } else {
            return false;
        }
    }

    /**
     * set Ngenius Order Email Content
     *
     * @param array $data
     *
     * @return bool
     */
    public function addNgeniusOrderEmailContent($data)
    {
        return (\Db::getInstance()->insert("ning_order_email_content", $data)) ? (bool)true : (bool)false;
    }

    /**
     * Gets Ngenius Order Email Content
     *
     * @param int $customerId
     * @param int $savedCardId
     *
     * @return bool
     */
    public function getNgeniusOrderEmailContent($idOrder)
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from("ning_order_email_content")
            ->where(self::ID_ORDER_LITERAL . pSQL($idOrder) . '"');

        return \Db::getInstance()->getRow($sql);
    }

    /**
     * Update Ngenius Order Email Content
     *
     * @param array $data
     *
     * @return bool
     */
    public static function updateNgeniusOrderEmailContent($data)
    {
        return \Db::getInstance()->update(
            'ning_order_email_content',
            $data,
            'id_order = "' . pSQL($data['id_order']) . '"'
        );
    }

    /**
     * Set Ngenius cron schedule
     *
     * @return bool
     */
    public function addNgeniusCronSchedule()
    {
        $seconds      = \Configuration::get('NING_CRON_SCHEDULE');
        $created_at   = date("Y-m-d h:i:s");
        $scheduled_at = date("Y-m-d H:i:00", (strtotime(date($created_at)) + $seconds));
        $data         = [
            'created_at'   => $created_at,
            'scheduled_at' => $scheduled_at,
        ];

        return (\Db::getInstance()->insert("ning_cron_schedule", $data)) ? (bool)true : (bool)false;
    }

    /**
     * Gets Ngenius cron schedule
     *
     * @return bool
     */
    public function getNgeniusCronSchedule()
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from("ning_cron_schedule")
            ->where('status ="' . pSQL('pending') . '"');
        if ($result = \Db::getInstance()->getRow($sql)) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Update Ngenius cron schedule
     *
     * @param array $data
     *
     * @return bool
     */
    public static function updateNgeniusCronSchedule($data)
    {
        return \Db::getInstance()->update(
            'ning_cron_schedule',
            $data,
            'id = "' . pSQL($data['id']) . '"'
        );
    }

    /**
     * Gets Ngenius cron schedule
     *
     * @return bool
     */
    public function validateNgeniusCronSchedule()
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from("ning_cron_schedule")
            ->where('status ="' . pSQL('pending') . '" AND scheduled_at <= "' . date("Y-m-d h:i:s") . '"');
        if ($result = \Db::getInstance()->executeS($sql)) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Gets Authorization Transaction
     *
     * @param array $ngeniusOrder
     *
     * @return array|bool
     */
    public static function getAuthorizationTransaction($ngeniusOrder)
    {
        if (!empty($ngeniusOrder['id_payment'])
            && !empty($ngeniusOrder['reference'])
            && $ngeniusOrder['state'] == 'AUTHORISED') {
            return $ngeniusOrder;
        } else {
            return false;
        }
    }

    /**
     * Gets Refunded Transaction
     *
     * @param array $ngeniusOrder
     *
     * @return array|bool
     */
    public static function getRefundedTransaction($ngeniusOrder)
    {
        if (isset($ngeniusOrder['id_capture'])
            && !empty($ngeniusOrder['id_capture'])
            && $ngeniusOrder['capture_amt'] > 0
            && $ngeniusOrder['state'] == 'CAPTURED') {
            return $ngeniusOrder;
        } else {
            return false;
        }
    }

    /**
     * Gets Delivery Transaction
     *
     * @param array $ngeniusOrder
     *
     * @return array|bool
     */
    public static function getDeliveryTransaction(array $ngeniusOrder): bool|array
    {
        if (isset($ngeniusOrder['id_payment'])
            && !empty($ngeniusOrder['id_capture'])
            && $ngeniusOrder['capture_amt'] > 0) {
            return $ngeniusOrder;
        } else {
            return false;
        }
    }

    /**
     * Gets Order Details Core
     *
     * @param $idOrderDetail
     *
     * @return bool|array
     */
    public function getOrderDetailsCore($idOrderDetail): bool|array
    {
        $sql = new \DbQuery();
        $sql->select('*')
            ->from("order_detail")
            ->where('id_order_detail ="' . pSQL($idOrderDetail) . '"');

        return \Db::getInstance()->getRow($sql);
    }
}
