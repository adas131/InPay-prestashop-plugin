<?php

class InpayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */

    public function postProcess()
    {

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['invoiceCode'] && $_POST['status'] && $_POST['optData']) {
            $apiHash = $_SERVER['HTTP_API_HASH'];
            $query = http_build_query($_POST);
            $hash = hash_hmac("sha512", $query, $this->module->secret_key);

            if ($apiHash == $hash) {
                parse_str($_POST['optData'], $optData);
                $id_cart = intval($optData['cartId']);
                $query = "SELECT * from " . _DB_PREFIX_ . "orders where id_cart='" . $id_cart . "'";
                $row = Db::getInstance()->getRow($query);

                if ($_POST['status'] == 'confirmed' && $row['current_state'] != null) {
                    $sql = "UPDATE " . _DB_PREFIX_ . "orders SET current_state='12' WHERE id_cart='" . $id_cart . "'";

                    if(Db::getInstance()->Execute($sql))
                        die('Payment actualised');

                } else {
                    $cart = new Cart($id_cart);

                    if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
                        die('Cannot create order for this cart.');
                    }

                    $customer = new Customer($cart->id_customer);

                    if (!Validate::isLoadedObject($customer)) {
                        die('No customer for this order.');
                    }

                    $currency = new Currency((int)($cart->id_currency));
                    $paid_amount = $_POST['amount'];
                    $order_amount = $cart->getOrderTotal(true, Cart::BOTH);

                    if ($_POST['status'] == 'confirmed') {
                        $paymentId = 12;
                    }
                    elseif ($_POST['status'] == 'received') {
                        $paymentId = 2;
                    }

                    $result = $this->module->validateOrder(
                        $cart->id,
                        $paymentId,
                        $order_amount,
                        $this->module->displayName,
                        'Invoice Code: ' . $_POST['invoiceCode'],
                        array(),
                        intval($currency->id),
                        false,
                        $customer->secure_key
                    );
                    die($result);
                }
            } else {
                return null;
            }


        }

    }
}
