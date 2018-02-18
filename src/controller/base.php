<?php
/**
 * CONTROLLER Base Controller
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        controller
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 
require_once (SYSBASE . '/controller/mother.php');

class BaseController extends MotherController {
	
	/**
	 * 
	 * @param database $db
	 * @param AuthHandler $auth
	 * @param template $template
	 */
	function __construct($db, $auth, $template){
		parent::__construct($db, $auth, $template);
	}
	
	/**
	 * ACTION home
	 */
	public function home(){
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__);
		$this->t->printPageFooter();
	}
}