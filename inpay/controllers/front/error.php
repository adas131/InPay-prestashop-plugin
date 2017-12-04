<?php

class TranzilaErrorModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function initContent(){
		$this->display_column_left = true;
		parent::initContent();
			
        $this->context->smarty->fetch('inpay/views/templates/front/error.tpl');
	}

}