<?php

class TranzilaErrorModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function initContent(){
		$this->display_column_left = true;
		parent::initContent();
			
		$this->setTemplate('error.tpl');
	}

}