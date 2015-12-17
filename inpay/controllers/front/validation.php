<?php

class InpayValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	 
	public function postProcess()
	{
        parse_str($_POST['optData'], $optData);
		$id_cart = (int) $optData['cartId'];
		$cart = new Cart($id_cart);
		
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			die('Cannot create order for this cart.');
	
		
		$customer = new Customer($cart->id_customer);

		if (!Validate::isLoadedObject($customer))
			die('No customer for this order.');
			
		$currency = new Currency((int)($cart->id_currency));
		
		$paid_amount = $_POST['amount'];
		$order_amount = $cart->getOrderTotal(true, Cart::BOTH);
		
		$apiHash = $_SERVER['HTTP_API_HASH'];
		$query = http_build_query($_POST);
		$hash = hash_hmac("sha512", $query, $this->module->secret_key);
		

		if( $apiHash == $hash && $paid_amount == $order_amount) {
			//success
			$this->module->validateOrder($cart->id, Configuration::get('PS_OS_PAYMENT'), $paid_amount, $this->module->displayName, 'Invoice Code: ' .$_POST['invoiceCode'], array(), (int)$currency->id, false, $customer->secure_key);
		} else {
			//failed transaction
		}
	}
}