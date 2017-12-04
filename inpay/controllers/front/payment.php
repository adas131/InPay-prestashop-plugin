<?php

class InpayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent () {
        global $cookie;

        parent::initContent();

        $cart = $this->context->cart;

        $currency = new Currency((int)($cart->id_currency));
        $customer = new Customer(intval($cart->id_customer));
        $address = new Address(intval($cart->id_address_invoice));

        $invoice_country = new Country($address->id_country);

        $shippingState = NULL;
        if ($address->id_state)
            $invoice_state = new State((int)($address->id_state));

        $amount = $cart->getOrderTotal(true, Cart::BOTH);

        $products = $cart->getProducts();
        $pdesk = '';
        foreach ($products AS $product) {
            $pdesk .= $product['name'] . ', ';
        }
        $pdesk = rtrim($pdesk, ', ');

        $protocol = "http://";
        if(Configuration::get('PS_SSL_ENABLED')) {
          $protocol = "https://";
        }
        $successUrl = $protocol . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') .
                      __PS_BASE_URI__ . 'index.php?controller=order-confirmation&id_cart=' . $cart->id .
                      '&id_module=' . $this->module->id .
                      '&key=' . $customer->secure_key . '&slowvalidation';

        $callbackUrl = Context::getContext()->link->getModuleLink('inpay', 'validation');

        $failUrl = $protocol .
                    htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') .
                    __PS_BASE_URI__ . 'index.php?controller=order&step=1';

        $data = array("apiKey" => $this->module->api_key,
            "amount" => number_format($amount, 2, '.', ""),
            "currency" => strtoupper($currency->iso_code),
            "optData" => 'cartId=' . $cart->id,
            "description" => $pdesk,
            "customerName" => $address->firstname . ' ' . $address->lastname,
            "customerAddress1" => $address->address1,
            "customerAddress2" => $address->address2,
            "customerCity" => $address->city,
            "customerState" => $invoice_state->iso_code,
            "customerZip" => $address->postcode,
            "customerCountry" => $invoice_country->iso_code,
            "customerEmail" => $customer->email,
            "customerPhone" => $address->phone,
            "callbackUrl" => $callbackUrl,
            "successUrl" => $successUrl,
            "failUrl" => $failUrl,
            "minConfirmations" => '1'
        );

        if ($this->module->test_mode == 1)
            $gateway_url = 'https://api.test-inpay.pl/invoice/create';
        else
            $gateway_url = 'https://api.inpay.pl/invoice/create';

        $request = http_build_query($data,'', '&');

        $result = $this->sendRequest($gateway_url, $request);
        $result = json_decode($result);

        $redirect_url = '';
        $error = '';
        if ($result->success) {
            $redirect_url = $result->redirectUrl;
        } elseif (!$result->success) {
            if (isset($result->message))
                $error = $result->message;
            elseif (isset($result->error->amount))
                $error = $result->error->amount;
            else
                $error = 'Payment failed. Please contact InPay info@inpay.pl';

        } else {
            $error = 'Payment failed, unable to connect to InPay gateway';
        }

        $this->context->smarty->assign(array(
            'redirect_url' => $redirect_url,
            'error' => $error
        ));

    }

    public function sendRequest ($gateway_url, $request)
    {
        $cr = curl_init();
        curl_setopt($cr, CURLOPT_URL, $gateway_url);
        curl_setopt($cr, CURLOPT_POST, 1);
        curl_setopt($cr, CURLOPT_POSTFIELDS, $request);
        curl_setopt($cr, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($cr, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($cr, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($cr);
        $error = curl_error($cr);

        if (!empty($error)) {
            die($error);
        }

        curl_close($cr);
        $res = json_decode($result, true);

        header('Location: '.$res['redirectUrl']);
    }

}
