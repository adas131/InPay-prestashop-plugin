<?php

/*
{*
* 2014 InPay
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author InPay S.A. <info@inpay.pl>
*  @copyright  2014 InPay S.A.
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  Registered Trademark & Property of InPay S.A.
*}
*/




if (!defined('_PS_VERSION_'))
    exit;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;


class Inpay extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();

    public function __construct ()
    {
        $this->name = 'inpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.12';
        $this->author = 'InPay S.A.';

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('INPAY_API_KEY', 'INPAY_SECRET_KEY', 'INPAY_TEST_MODE'));

        if (isset($config['INPAY_API_KEY']))
            $this->api_key = $config['INPAY_API_KEY'];

        if (isset($config['INPAY_SECRET_KEY']))
            $this->secret_key = $config['INPAY_SECRET_KEY'];

        if (isset($config['INPAY_TEST_MODE']))
            $this->test_mode = $config['INPAY_TEST_MODE'];

        parent::__construct();

        // this->l('Inpay');??
        $this->displayName = $this->l('Bitcoin payments using InPay.pl');
        $this->description = $this->l('Accept Bitcoin payments using InPay gateway');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your merchant data?');
    }

    public function install ()
    {
//        $severity = 1;
//        $message = '123';
//        PrestaShopLogger::addLog($message, $severity);
        if (!parent::install() ||
            !$this->registerHook('paymentOptions') ||
            !$this->registerHook('payment') ||
            !$this->registerHook('paymentReturn'))
        {

            return false;

        }

        return true;
    }

    public function uninstall ()
    {
        if (!Configuration::deleteByName('INPAY_API_KEY') || !Configuration::deleteByName('INPAY_SECRET_KEY')
            || !Configuration::deleteByName('INPAY_TEST_MODE')
            || !parent::uninstall()
        )

            return false;
        return true;
    }

    private function _postValidation ()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('api_key'))
                $this->_postErrors[] = $this->l('API Key is required.');

            if (!Tools::getValue('secret_key'))
                $this->_postErrors[] = $this->l('Secret Key is required.');

        }

    }

    private function _postProcess ()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('INPAY_API_KEY', Tools::getValue('api_key'));
            Configuration::updateValue('INPAY_SECRET_KEY', Tools::getValue('secret_key'));
            Configuration::updateValue('INPAY_TEST_MODE', Tools::getValue('test_mode'));
        }

        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));

    }

    private function _displayCheckoutPayment ()
    {
        $this->_html .= '<img src="../modules/inpay/logo.png" style="float:left; margin-right:15px;"><b>' .
        'This module allows you to accept Bitcoin payments by InPay.<br/>If you require any help contact info@inpay.pl' .
        '</b><br /><br />';
    }

    private function _displayForm () {
        $orderStates = OrderState::getOrderStates((int)$this->context->cookie->id_lang);
        $this->_html .=
          '<form action="' . Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']) . '" method="post">
            <fieldset>
              <legend>' . $this->l('Merchant configuration') . '</legend>
              <table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
                <tr><td width="200" style="height: 35px;">' . $this->l('API Key') . '</td><td><input type="text" name="api_key" value="' . htmlentities(Tools::getValue('api_key', $this->api_key), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
                <tr><td width="200" style="height: 35px;">' . $this->l('Secret Key') . '</td><td><input type="text" name="secret_key" value="' . htmlentities(Tools::getValue('secret_key', $this->secret_key), ENT_COMPAT, 'UTF-8') . '" style="width: 300px;" /></td></tr>
                <tr>
                  <td>' . $this->l('Use Testnet') . '</td>
                  <td>
                    <input type="radio" name="test_mode" value="1" ' . (Tools::getValue('test_mode', $this->test_mode) == '1' ? 'checked' : '') . '>&nbsp;Yes&nbsp;
                    <input type="radio" name="test_mode" value="0" ' . (Tools::getValue('test_mode', $this->test_mode) == '0' ? 'checked' : '') . '>&nbsp;No
                    </td>
                </tr>
                
                        <tr><td></td><td><input class="button" name="btnSubmit" value="' . $this->l('Update settings') . '" type="submit" /></td></tr>

                
              </table>
            </fieldset>
          </form>';
    }


    public function getContent ()
    {
        $this->_html = '<h2>' . $this->displayName . '</h2>';
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            }
            else {
              foreach ($this->_postErrors as $err) {
                $this->_html .= '<div class="alert error">' . $err . '</div>';
              }
            }
        } else {
            $this->_html .= '<br />';
        }
        $this->_displayCheckoutPayment();
        $this->_displayForm();
        return $this->_html;
    }

    public function hookPaymentOptions ($params)
    {
        global $cookie;

        if (!$this->active)
            return;

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->l('InPay (Bitcoin)'))
            ->setAction(Context::getContext()->link->getModuleLink('inpay', 'payment'));

        $payment_options = [
            $newOption
        ];

        return $payment_options;

    }

    public function hookPaymentReturn ($params)
    {
//        return $this->display(__FILE__, 'payment_return.tpl');
        return $this->fetch('module:inpay/views/templates/hook/payment_return.tpl');
//        return $this->smarty->fetch('inpay/views/payment_return.tpl');
    }
}
