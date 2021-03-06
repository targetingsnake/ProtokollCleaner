<?php
use SILMPH\File;
/**
 * CONTROLLER Invitation Controller
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
 
require_once (SYSBASE . '/framework/class._MotherController.php');

class InvitationController extends MotherController {
	
	/**
	 * 
	 * @param Database $db
	 * @param AuthHandler $auth
	 * @param Template $template
	 */
	function __construct($db, $auth, $template){
		parent::__construct($db, $auth, $template);
	}
	
	// HELPER ==========================================================================
	/**
	 * 
	 * @param array $proto Protocoll array, with additional fields: gname, membernames
	 * @param array $user user data ['username' => xxx]
	 * @param false|array $openProtos array of unreconciled protocols
	 * @param string $additional_message additional mail message
	 */
	public function send_mail_invitation($proto, $user = NULL, $openProtos = false, $additional_message = ''){
		$settings=$this->db->getSettings();
		$mailer = new MailHandler();
		$mailer->setLogoImagePath('/../public/images/logo_f.png');
		$initOk = $mailer->init($settings);
		// mail initialisation failed
		if (!$initOk) return false;
		
		$pdate = date_create($proto['date']);
		$mail_address = parent::$protomap[$proto['gname']][3];
		$tops_tmp = $this->db->getTopsOpen($proto['gname']);
		$tops = [];
		foreach ($tops_tmp as $id => $top){
			if (!$top['skip_next']){
				$tops[$id] = $top;
			}
		}
		
		$resorts = $this->db->getResorts($proto['gname']);
		
		if (is_string($mail_address)){
			$mailer->mail->addAddress($mail_address);
		} elseif (is_array($mail_address)) {
			foreach ($mail_address as $mail_addr){
				$mailer->mail->addAddress($mail_addr);
			}
		}
		
		$mailer->mail->Subject = 'Sitzungseinladung - '.ucfirst(strtolower($proto['gname'])).' - '.$pdate->format('d.m.Y H:i');
		
		$mailer->bindVariables([
			'sender' 	=> ($user != NULL)? $user['username'] : '',
			'message' 	=> $additional_message,
			'committee' => $proto['gname'],
			'room'		=> $proto['room'],
			'tops' 		=> $tops,
			'proto'		=> $proto, 
			'resorts'	=> $resorts,
			'protoInternLink' => WIKI_URL.'/'.parent::$protomap[$proto['gname']][0].'/',
			'protoPublicLink' => WIKI_URL.'/'.parent::$protomap[$proto['gname']][1].'/',
			'unreconciled_protocols' => $openProtos,
			'topLink' 	=> BASE_URL.BASE_SUBDIRECTORY.'invite',
			'protoLink' => BASE_URL.BASE_SUBDIRECTORY.'protolist'
		]);
		
		$mailer->setTemplate('proto_invite');
		if($mailer->send(false, false, true, true)){
			return true;
		} else {
			error_log('Es konnte keine Mail versendet werden. Prüfen Sie bitte die Konfiguration. '.((isset($mailer->mail) && isset($mailer->mail->ErrorInfo))? $mailer->mail->ErrorInfo: '' ));
			return false;
		}
	}
	
	/**
	 * send
	 * @param array $newproto
	 * @param string $gremium
	 */
	public function send_management_info_mail($newproto, $gremium){
		$settings=$this->db->getSettings();
		$mailer = new MailHandler();
		$mailer->setLogoImagePath('/../public/images/logo_f.png');
		$initOk = $mailer->init($settings);
		// mail initialisation failed
		if (!$initOk) return false;
		
		$mail_address = parent::$protomap[$gremium][3];
		
		//mail data
		//$newproto
		$pdate = date_create($newproto['date']);
		$members = $this->db->getMembers($gremium);
		$leitung = (isset($newproto['management']) && isset($members[$newproto['management']]['name']) )? $members[$newproto['management']]['name'] : '';
		$protokollv = (isset($newproto['protocol']) && isset($members[$newproto['protocol']]['name']) )? $members[$newproto['protocol']]['name'] : '';
		
		if (is_string($mail_address)){
			$mailer->mail->addAddress($mail_address);
		} elseif (is_array($mail_address)) {
			foreach ($mail_address as $mail_addr){
				$mailer->mail->addAddress($mail_addr);
			}
		}
		
		$mailer->mail->Subject = 'Info: Nächste '.ucfirst(strtolower($gremium)).' Sitzung: Leitung und Protokollierung';
		
		$mailer->bindVariables([
			'committee' => $gremium,
			'date' => $pdate,
			'leitung' => $leitung,
			'protokoll' => $protokollv,
			'newproto' => $newproto,
			'toolLink' => BASE_URL.BASE_SUBDIRECTORY
		]);
		
		$mailer->setTemplate('newproto_info');
		if($mailer->send(false, false, true, true)){
			return true;
		} else {
			error_log('Es konnte keine Mail versendet werden. Prüfen Sie bitte die Konfiguration. '.((isset($mailer->mail) && isset($mailer->mail->ErrorInfo))? $mailer->mail->ErrorInfo: '' ));
			return false;
		}
	}
	
	// ACTIONS =========================================================================

	/**
	 * ACTION home
	 */
	public function ilist(){
		$perm = 'stura';
		$this->t->appendCSSLink('invite.css');
		$this->t->appendJsLink('libs/jquery-ui.min.js');
		$s = $this->t->getJsLinks();
		$this->t->setJsLinks([$s[0], $s[3], $s[1], $s[2]]);
		$this->t->appendJsLink('libs/jquery-dateFormat.min.js');
		$this->t->appendJsLink('libs/jquery_ui_widget_combobox.js');
		$this->t->appendJsLink('wiki2html.js');
		$this->t->appendJsLink('invite.js');
		$this->t->setTitlePrefix('Einladung');
		$this->t->setExtraBodyClass('invite');
		$tops = $this->db->getTopsOpen($perm, true);
		$resorts = $this->db->getResorts($perm);
		$member = $this->db->getMembersCounting($perm);
		$committee = $this->db->getCommitteebyName($perm);
		$newproto = $this->db->getNewprotos($perm);
		$settings = $this->db->getSettings();
		$legis = $this->db->getCurrentLegislatur();
		$oldproto = $this->db->getProtocolsByLegislatur($perm, $legis['number']);
		$sett = [];
		$sett['auto_invite'] = intval($settings['AUTO_INVITE_N_HOURS']);
		$sett['disable_restore'] = intval($settings['DISABLE_RESTORE_OLDER_DAYS']);
		$sett['meeting_hour'] = intval($committee['default_meeting_hour']);
		$sett['meeting_room'] = ($committee['default_room']);
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [
			'tops' => $tops,
			'resorts' => $resorts,
			'member' => $member,
			'committee' => $perm,
			'newproto' => $newproto,
			'settings' => $sett,
			'legislatur' => $legis,
			'protomap' => self::$protomap[$perm],
			'nth-proto' => (count($oldproto)+1)
		]);
		$this->t->printPageFooter();
	}

	/**
	 * ACTION public invition
	 */
	public function ipublic(){
		$perm = 'stura';
		$this->t->appendCSSLink('invitestuds.css');
		$this->t->appendJsLink('libs/jquery-ui.min.js');
		$s = $this->t->getJsLinks();
		$this->t->setJsLinks([$s[0], $s[3], $s[1], $s[2]]);
		$this->t->setTitlePrefix('Einladung');
		$this->t->setExtraBodyClass('invitestuds');
		$tops = $this->db->getTopsOpen($perm, true);
		$resorts = $this->db->getResorts($perm);
		$member = $this->db->getMembersCounting($perm);
		$committee = $this->db->getCommitteebyName($perm);
		$newproto = $this->db->getNextprotos($perm, date_create_from_format('H:i:s', '00:00:00')->modify('-1 day'));
		$settings = $this->db->getSettings();
		$legis = $this->db->getCurrentLegislatur();
		$oldproto = $this->db->getProtocolsByLegislatur($perm, $legis['number']);
		$sett = [];
		$sett['auto_invite'] = intval($settings['AUTO_INVITE_N_HOURS']);
		$sett['disable_restore'] = intval($settings['DISABLE_RESTORE_OLDER_DAYS']);
		$sett['meeting_hour'] = intval($committee['default_meeting_hour']);
		$sett['meeting_room'] = ($committee['default_room']);
		$this->t->printPageHeader();
		$this->includeTemplate(__FUNCTION__, [
			'tops' => $tops,
			'resorts' => $resorts,
			'member' => $member,
			'committee' => $perm,
			'newproto' => $newproto,
			'settings' => $sett,
			'legislatur' => $legis,
			'protomap' => self::$protomap[$perm],
			'nth-proto' => (count($oldproto)+1)
		]);
		$this->t->printPageFooter();
	}
	
	/**
	 * POST action
	 * delete top by id hash and committee
	 */
	public function tdelete(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'error' => 'Topkennung hat das falsche Format.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			
			$top = $this->db->getTopById($vali->getFiltered('tid'));
			if (!$top 
				|| $top['gname'] != $vali->getFiltered('committee') 
				|| $top['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Top nicht gefunden');
			} else {
				//delete files/attachements
				require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
				$fh = new FileHandler($this->db);
				$fh->deleteFilesByLinkId($top['id']);
				//remove top
				$ok = $this->db->deleteTopById($top['id']);
				if ($ok){
					$this->json_result = [
						'success' => true,
						'msg' => 'Top wurde gelöscht.'
					];
				} else {
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Top konnte nicht gelöscht werden.'
					];
				}
				$this->print_json_result();
			}
		}
	}
	
	/**
	 * POST action
	 * delete top by id hash and committee
	 */
	public function tpause(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'error' => 'Topkennung hat das falsche Format.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$top = $this->db->getTopById($vali->getFiltered('tid'));
			if (!$top
				|| $top['gname'] != $vali->getFiltered('committee')
				|| $top['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Top nicht gefunden');
			} else {
				$top['skip_next'] = ($top['skip_next']==1)?0:1;
				$ok = $this->db->updateTop($top);
				if ($ok){
					$this->json_result = [
						'success' => true,
						'msg' => 'Top wurde geändert.',
						'skipnext' => ($top['skip_next']==1)
					];
				} else {
					$this->json_result = [
						'success' => false,
						'eMsg' => 'Top nicht geändert.',
						'skipnext' => ($top['skip_next']==1)
					];
				}
				$this->print_json_result();
			}
		}
	}
	
	/**
	 * POST action
	 * sort tops
	 */
	public function tsort(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'list' => ['array',
				'minlength' => 2,
				'validator' => ['integer',
					'min' => '1',
					'error' => 'Ungültige Id.'
				],
				'error' => 'Kein Array.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$tops = $this->db->getTopsOpen($vali->getFiltered('committee'));
			$sortpos = 1;
			$ok = true;
			foreach($vali->getFiltered('list') as $sortid){
				if (!isset($tops[$sortid])) continue;
				if (isset($tops[$sortid]['used_on'])) continue;
				if (isset($tops[$sortid]['resort'])) continue;
				if ($tops[$sortid]['order'] != $sortpos){
					$tops[$sortid]['order'] = $sortpos;
					$ok = $this->db->updateTop($tops[$sortid]);
				}
				if (!$ok) break;
				$sortpos++;
			}
			if ($ok){
				$this->json_result = [
					'success' => true,
					'msg' => 'Tops sortiert.'
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Top nicht geändert.',
				];
			}
			$this->print_json_result();
		}
	}

	/**
	 * POST action
	 * toggle sleeping ('ruhend') state of committee member
	 */
	public function mptoggle(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Gremium zu bearbeiten.'
			],
			'mid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		$this->handleValidatorError($vali);

		$member = $this->db->getMemberById($vali->getFiltered('mid'));
		if (!$member || $member['gname'] != $vali->getFiltered('committee')){
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Mitglied nicht gefunden nicht gefunden.'
			];
		} else {
			$isSleeping = (!empty($member['overwrite']) && false!== strpos($member['overwrite'],'(ruhend)'));
			if ($isSleeping){
				$over = trim(str_replace('(ruhend)', '', $member['overwrite']));
				if (empty($over)) $over = NULL;
				$member['overwrite'] = $over;
			} else {
				if ($member['overwrite']===NULL) $member['overwrite'] = '';
				$member['overwrite'] = $member['overwrite'] . '(ruhend)';
			}

			//return result
			if ($this->db->updateMemberById($member)){
				$this->json_result = [
					'success' => true,
					'msg' => 'Mitgliedstatus aktualisiert.'
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim Aktualisieren des Mitgliedstatus.'
				];
			}
		}
		$this->print_json_result();
	}

	/**
	 * POST action
	 * delete committee member
	 */
	public function mdelete(){
		$this->json_result = [
			'success' => false,
			'eMsg' => 'Fehler beim Löschen. - Disabled'
		];
		$this->print_json_result();
		die();
	}
	
	/**
	 * POST action
	 * add committee member
	 */
	public function madd(){
		$this->json_result = [
			'success' => false,
			'eMsg' => 'Fehler beim Erstellen. - Disabled'
		];
		$this->print_json_result();
		die();
	}
	
	/**
	 * ACTION top edit|create - only create formular
	 */
	public function itopedit(){
		$perm = 'stura';
		if (!isset($_GET['committee'])){
			$_GET['committee'] = $perm;
		}
		//create accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_GET, $validator_map, false);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$top = NULL;
			if (isset($vali->getFiltered()['tid'])){
				$t = $this->db->getTopById($vali->getFiltered('tid'));
				if ($t && $t['used_on'] == NULL && $t['gname'] == $vali->getFiltered('committee')){
					$top = $t;
				}
			}
			$resorts = $this->db->getResorts($vali->getFiltered('committee'));
			$member = $this->db->getMembers($vali->getFiltered('committee'));
			$this->includeTemplate(__FUNCTION__, [
				'top' => $top,
				'resorts' => $resorts,
				'member' => $member
			]);
		}
	}
	
	/**
	 * POST action
	 * itop update or create top in database 
	 */
	public function itopupdate(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'headline' => [ 'regex',
				'pattern' => '/^[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]{1}[a-zA-Z0-9\-_;,.:!?+\*%()#\/\\ äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9\-_;,.:!?+\*%()#\/\\äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]{1}$/',
				'error' => 'Ungültige Überschrift'
			],
			'resort' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Resort Id.'
			],
			'person' => ['name',
				'minlength' => '3',
				'error' => 'Ungültige Zeichen im Namen.',
				'empty',
				'multi' => ',',
				'multi_add_space'
			],
			'duration' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Dauer'
			],
			'goal' => ['regex',
				'pattern' => '/^[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9, äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+[a-zA-Z0-9äöüÄÖÜéèêóòôáàâíìîúùûÉÈÊÓÒÔÁÀÂÍÌÎÚÙÛß]+$/',
				'empty',
				'error' => 'Ungültige Zielsetzung'
			],
			'guest' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültiger "Gast" Status'
			],
			'intern' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültiger "Intern" Status'
			],
			'text' => ['regex',
				'pattern' => '/^(.|\r|\n)*$/',
				'strsplit' => 2040,
				'empty',
				'noTagStrip',
				'noTrim',
				'trimLeft' => "\n\r\0\x0B",
				'trimRight' => " \t\n\r\0\x0B",
				'error' => 'Ungültiger Text',
				'replace' => [['<del>','</del>'], ['%[[del]]%','%[[/del]]%']],
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Topkennung hat das falsche Format.'
			],
			'tid' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Top Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$filtered = $vali->getFiltered();
			$filtered['text'] = str_replace(['%[[del]]%','%[[/del]]%'], ['<del>','</del>'], strip_tags($filtered['text']));
			
			$top = [];
			if ($filtered['tid'] > 0){
				$top = $this->db->getTopById($filtered['tid']);
				if (!$top
					|| $top['gname'] != $filtered['committee']
					|| $top['hash'] != $filtered['hash']){
					$this->json_not_found('Top nicht gefunden');
					return;
				}
			}
			$resort = false;
			if ($filtered['resort'] > 0){
				$resorts = $this->db->getResorts($filtered['committee']);
				if (isset($resorts[$filtered['resort']])){
					$resort = $resorts[$filtered['resort']];
				}
			}
			$gremium = $this->db->getCommitteebyName($filtered['committee']);

			//check if there is planned protocol and mail invitation is done
			$newprotos = $this->db->getNewprotos($filtered['committee']);
			$newprotoMailDone = false;
			foreach ($newprotos as $proto){
				if (!$proto['generated_url'] && $proto['invite_mail_done']){
					$newprotoMailDone = true;
					break;
				}
			}
			if ($newprotoMailDone){
				if (!isset($top['id']) && !is_array($resort)){
					//if is new top and there is no resort set automatically to 'skip_next' = true
					$top['skip_next'] = 1;
				} elseif(isset($top['id']) && $top['resort'] && !is_array($resort)){ 
					//if resort top is refactored to normal top set automatically to 'skip_next' = true
					$top['skip_next'] = 1;
				}
			}
			
			$top['headline'] = $filtered['headline'];
			$top['resort'] = (is_array($resort))? $resort['id']: NULL;
			$top['person'] = $filtered['person']? $filtered['person'] : NULL ;
			$top['expected_duration'] = $filtered['duration'];
			$top['goal'] = $filtered['goal']? $filtered['goal']: NULL;
			$top['text'] = $filtered['text'];
			$top['guest'] = $filtered['guest'];
			$top['intern'] = $filtered['intern'];
			$top['gremium'] = $gremium['id'];
			$top['hash'] = (isset($top['hash']) && $top['hash'])? $top['hash'] : md5($top['headline'].date_create()->getTimestamp().$filtered['committee'].$vali->getFiltered('committee').mt_rand(0, 640000));
			
			//create 
			$newtid = 0;
			if (!isset($top['id'])){
				$newtid = $this->db->createTop($top);
				if (!$newtid) {
					$this->json_not_found('Top konnte nicht erstellt werden.');
					return;
				} else {
					$top['id'] = $newtid;
				}
			} else { //or update top 
				if (!$this->db->updateTop($top)) {
					$this->json_not_found('Top konnte nicht aktualisiert werden.');
					return;
				}
			}
			$top = $this->db->getTopById($top['id'], true);
			$top['addedOn'] = date_create($top['added_on'])->format('d.m.Y H:i');
			if ($resort) {
				$top['resort'] = $resort;
			}
			$top['isNew'] = ($newtid>0)? 1:0;
			$top['goal'] = ($top['goal'])?$top['goal']:'';
			$top['person'] = ($top['person'])?$top['person']:'---';
			$top['expected_duration'] = ($top['expected_duration'])?$top['expected_duration']:0;
			
			//return result
			$this->json_result = [
				'top' => $top,
				'success' => true,
				'msg' => ($filtered['tid'])?'Top aktualisiert':'Top erstellt.'
			];
			
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * new protocol create / update
	 */
	public function npupdate(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'date' => ['date',
				'parse' => 'Y-m-d',
				'error' => 'Ungültiges Datum'
			],
			'time' => ['time',
				'format' => 'H:i',
				'error' => 'Ungültige Uhrzeit'
			],
			'room' => ['regex',
			   'pattern' => '/^(([0-9]|[a-z]|[A-Z]|[ \/\-])*)$/',
			   'empty',
			   'maxlength' => 63,
			   'error' => 'Ungültige Ortsangabe'
			],
			'management' => ['name',
				'empty',
				'error' => 'Ungültiger Name: Sitzungsleitung'
			],
			'protocol' => ['name',
				'empty',
				'error' => 'Ungültige Name: Protokoll'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Top Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$nproto = [];
			if ($vali->getFiltered('npid') > 0){
				$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
				if (!$nproto
					|| $nproto['gname'] != $vali->getFiltered('committee')
					|| $nproto['hash'] != $vali->getFiltered('hash')){
					$this->json_not_found('Protokoll nicht gefunden');
					return;
				}
				$state = ($nproto['generated_url'] != null)? 2 : (($nproto['invite_mail_done'])? 1 : 0);
				if ($state == 2){
					$this->json_access_denied('Protokoll kann nicht geändert werden');
					return;
				}
			}
			//don't allow old date
			$validateDate = date_create_from_format('Y-m-d H:i:s', $vali->getFiltered('date').' '.$vali->getFiltered('time').':00');
			$now = date_create();
			
			$diff = $now->getTimestamp() - $validateDate->getTimestamp();
			if ($diff > 3600 * 24) { //one day
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Das Datum kann nicht in der Vergangenheit liegen.'
				];
				$this->print_json_result();
				return;
			}
			
			$gremium = $this->db->getCreateCommitteebyName($vali->getFiltered('committee'));
			$members = $this->db->getMembers($vali->getFiltered('committee'));
			$memberLink = ['proto'=> NULL, 'manag'=> NULL];
			foreach ($members as $member){
				if ($member['name'] === $vali->getFiltered('management')){
					$memberLink['manag'] = $member['id'];
				}
				if ($member['name'] === $vali->getFiltered('protocol')){
					$memberLink['proto'] = $member['id'];
				}
			}
			
			$send_infomail = false;
			
			$nproto['gremium'] = $gremium['id'];
			$nproto['date'] = $vali->getFiltered('date').' '.$vali->getFiltered('time').':00';
			if (isset($nproto['management']) && $nproto['management'] != $memberLink['manag'] || (!isset($nproto['management']) || !$nproto['management']) && $memberLink['manag'] ){
				$send_infomail = true;
			}
			if (isset($nproto['protocol']) && $nproto['protocol'] != $memberLink['proto'] || (!isset($nproto['protocol']) || !$nproto['protocol']) && $memberLink['proto'] ){
				$send_infomail = true;
			}
			$nproto['room'] = $vali->getFiltered('room')? $vali->getFiltered('room') : NULL;
			if ($nproto['room'] == $gremium['default_room']) $nproto['room'] = NULL;
			$nproto['management'] = $memberLink['manag'];
			$nproto['protocol'] = $memberLink['proto'];
			$nproto['hash'] = (isset($nproto['hash']) && $nproto['hash'])? $nproto['hash'] : md5($nproto['date'].date_create()->getTimestamp().$vali->getFiltered('committee').mt_rand(0, 640000));
			$nproto['created_by'] = $this->auth->getUsername();
			$nproto['created_on'] = $now->format('Y-m-d H:i:00');
			
			//create
			$newnpid = 0;
			if (!isset($nproto['id'])){
				$newnpid = $this->db->createNewproto($nproto);
				if (!$newnpid) {
					$this->json_not_found('Sitzung konnte nicht geplant werden.');
					return;
				} else {
					$nproto['id'] = $newnpid;
				}
			} else { //or update newproto
				if (!$this->db->updateNewproto($nproto)) {
					$this->json_not_found('Geplant Sitzung konnte nicht aktualisiert werden.');
					return;
				}
			}
			$nproto = $this->db->getNewprotoById($nproto['id']);
			$settings = $this->db->getSettings();
	
			$state = ($nproto['generated_url'] != null)? 2 : (($nproto['invite_mail_done'])? 1 : 0);
			$disable_restore = false;
			if ($state == 2){
				$today = date_create();
				$npdate = date_create($nproto['date']);
				$npdate->setTime(0, 0);
				$diff1 = $today->getTimestamp() - $npdate->getTimestamp();
				if ( $diff1 > 3600 * 24 * intval($settings['DISABLE_RESTORE_OLDER_DAYS']) ){
					$disable_restore = true;
				}
			}
			
			//send mail information
			if($send_infomail){
				$this->send_management_info_mail($nproto, $nproto['gname']);
			}
			
			$out = [
				'state' => 	$state,
				'stateLong' => (['Geplant', 'Eingeladen', 'Erstellt'])[$state],
				'disableRestore' => 	$disable_restore,
				'generatedUrl' => 		$nproto['generated_url'],
				'inviteMailDone' =>	$nproto['invite_mail_done'],
				'id' 	=> 	$nproto['id'],
				'hash' 	=> 	$nproto['hash'],
				'date'  =>	date_create($nproto['date'])->format('d.m.Y H:i'),
				'isNew' =>	($newnpid==0)? 1 : 0,
				'm' 	=>  $nproto['management']?$nproto['management']:'' ,
				'p' 	=>  $nproto['protocol']?$nproto['protocol']:'',
				'room' 	=>  $nproto['room']?$nproto['room']:(($gremium['default_room'])?$gremium['default_room']:'')
			];
			//return result
			$this->json_result = [
				'np' => $out,
				'success' => true,
				'msg' => ($vali->getFiltered('npid'))?'Neue Sitzung aktualisiert':'Neue Sitzung geplant.'
			];
				
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * delete new protocol
	 */
	public function npdelete(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Top Id.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
			if (!$nproto
				|| $nproto['gname'] != $vali->getFiltered('committee')
				|| $nproto['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Protokoll nicht gefunden');
				return;
			}
			$state = ($nproto['generated_url'] != null)? 2 : (($nproto['invite_mail_done'])? 1 : 0);
			if ($state == 2){
				$this->json_access_denied('Protokoll kann nicht geändert werden');
				return;
			}
			$ok = $this->db->deleteNewprotoById($nproto['id']);
			if ($ok){
				$this->json_result = [
					'success' => true,
					'msg' => 'Sitzungseinladung wurde gelöscht.'
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Sitzungseinladung konnte nicht gelöscht werden.'
				];
			}
			$this->print_json_result();
		}
	}

	/**
	 * POST action
	 * pdf member list
	 */
	public function npmemberpdf(){
		$perm = 'stura';
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Top Id.'
			],
			'd' => ['integer', 'optional',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültige Parameter.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		$this->handleValidatorError($vali);
		$filtered = $vali->getFiltered();

		$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
		if (!$nproto
			|| $nproto['gname'] != $vali->getFiltered('committee')
			|| $nproto['hash'] != $vali->getFiltered('hash')){
			$this->json_not_found('Protokoll nicht gefunden');
			return;
		}

		$date = date_create($nproto['date']);
		$members = $this->db->getMembers($perm);
		$members_elected = [];
		$members_active = [];
		$members_stuff = [];
		$members_ref = [];

		foreach($members as $m) {
			$name = $m['name'];
			$job = $m['job'];
			if (!empty($m['overwrite']) && false !== strpos($m['overwrite'], '(ruhend)')) {
				$name .= ' (ruhend)';
			}
			if ($m['flag_stuff']) {
				$members_stuff[] = [
					'name' => $name,
					'job' => $job,
					'text' => ($m['overwrite'])? $m['overwrite'] : '',
				];
			} else if ($m['flag_elected']) {
				$members_elected[] = [
					'name' => $name,
					'job' => $job,
					'text' => ($m['overwrite'])? $m['overwrite'] : '',
				];
			} else if ($m['flag_ref']) {
				$members_ref[] = [
					'name' => $name,
					'job' => $job,
					'text' => ($m['overwrite'])? $m['overwrite'] : '',
				];
			} else if ($m['flag_active']) {
				$members_active[] = [
					'name' => $name,
					'job' => $job,
					'text' => ($m['overwrite'])? $m['overwrite'] : '',
				];
			}
		}
		//do pdf api call
		$pdfout = [
			'APIKEY' => FUI2PDF_APIKEY,
			'action' => 'protocolmemberlist',
			'date' => $date->format('Y-m-d'),
			'member_elected' => $members_elected,
			'member_stuff' => $members_stuff,
			'member_ref' => $members_ref,
			'member_active' => $members_active,

			'nth' => 42,			//skip implementation, not used anymore
			'legislatur' => 42,		//skip implementation, not used anymore
			'leitung' => ($nproto['management'] && isset($members[$nproto['management']]))? $members[$nproto['management']]['name']: '',
			'protocol' => ($nproto['protocol'] && isset($members[$nproto['protocol']]))? $members[$nproto['protocol']]['name']: '',
		];

		$result = do_post_request2(FUI2PDF_URL . '/pdfbuilder', $pdfout, FUI2PDF_AUTH);

		// return result to user
		if ($result['success'] && !isset($filtered['d']) || isset($filtered['d']) && $filtered['d'] == 0){
			if (isset($result['data']['success']) && $result['data']['success']){
				$this->json_result = [
					'success' => true,
					'container' => 'object',
					'headline' =>
					//direct link
						'<form method="POST" action="' . BASE_SUBDIRECTORY . 'invite/npmemberlist">' .
						'<a href="#" class="modal-form-fallback-submit text-white">' .
						"Sitzungsliste_" . $date->format('Y-m-d') . 	'.pdf' .
						'</a>' .
						'<input type="hidden" name="committee" value="' . $filtered['committee'] . '">' .
						'<input type="hidden" name="hash" value="' . $filtered['hash'] . '">' .
						'<input type="hidden" name="npid" value="' . $filtered['npid'] . '">' .
						'<input type="hidden" name="d" value="1">' . '</form>',
					'attr' => [
						'type' => 'application/pdf',
						'width' => '100%',
						'download' =>
							"Sitzungsliste_" . $date->format('Y-m-d') . 	'.pdf' ,
					],
					'fallback' => '<form method="POST" action="' . BASE_SUBDIRECTORY . 'invite/npmemberlist">Die Datei kann leider nicht angezeigt werden, kann aber unter diesem ' .
						'<a href="#" class="modal-form-fallback-submit">Link</a> heruntergeladen werden.' .
						'<input type="hidden" name="committee" value="' . $filtered['committee'] . '">' .
						'<input type="hidden" name="hash" value="' . $filtered['hash'] . '">' .
						'<input type="hidden" name="npid" value="' . $filtered['npid'] . '">' .
						'<input type="hidden" name="d" value="1">' .
						'</form>',
					'datapre' => 'data:application/pdf;base64,',
					'data' => $result['data']['data'],
				];
			}else{
				$this->json_result = [
					'success' => false,
					'type' => 'modal',
					'subtype' => 'server-error',
					'status' => '200',
					'eMsg' => '<div style="white-space:pre-wrap;">' . print_r((isset($result['data']['error'])) ? $result['data']['error'] : $result['data'], true) . '</div>',
				];
			}
		}else if ($result['success'] && isset($filtered['d']) && $filtered['d'] == 1){
			header("Content-Type: application/pdf");
			header('Content-Disposition: attachment; filename="' . 'Sitzungsliste_' . $date->format('Y-m-d') . '.pdf'.'"');
			echo base64_decode($result['data']['data']);
			die();
		}else{
			$this->json_result = [
				'success' => false,
				'status' => '200',
				'eMsg' => 'Error during PDF creation.',
				'type' => 'modal',
				'subtype' => 'server-error',
				'reload' => false
			];
			error_log('ERROR: npmemberpdf: [PDF-Creation]:'. print_r($result, true));
		}
		$this->print_json_result();
	}
	
	/**
	 * POST action
	 * send invitation for new protocol
	 */
	public function npinvite(){
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Sitzungskennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
			'text' => ['regex',
				'pattern' => '/^(.|\r|\n)*$/',
				'empty',
				'error' => 'Ungültiger Text'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
			if (!$nproto
				|| $nproto['gname'] != $vali->getFiltered('committee')
				|| $nproto['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Protokoll nicht gefunden');
				return;
			}
			// room
			if (!$nproto['room']){
				$committee = $this->db->getCommitteebyName($nproto['gname']);
				$nproto['room'] = $committee['default_room'];
			}
			//don't allow dates in the past
			$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
			$now = date_create();
			$diff = $now->getTimestamp() - $validateDate->getTimestamp();
			
			if ($diff > 3600) { //one hour
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Einladungen für vergangene Sitzungen können nicht gesendet werden'
				];
				$this->print_json_result();
				return;
			}
			
			$members = $this->db->getMembers($vali->getFiltered('committee'));
			$membernames = [
				'p'=> ($nproto['protocol'] && isset($members[$nproto['protocol']]))? $members[$nproto['protocol']] : NULL, 
				'm'=> ($nproto['management'] && isset($members[$nproto['management']]))? $members[$nproto['management']] : NULL 
			];
			$nproto['membernames'] = $membernames;
			// open protocols // not aggreed
			$notAgreedProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, true, false, " AND P.ignore = 0 AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
			$draftStateProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, false, true, " AND P.ignore = 0 AND (P.public_url IS NULL) AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
		
			$ok = $this->send_mail_invitation(
				$nproto,
				[	'username' => $this->auth->getUsername(), 
					'userFullname' => $this->auth->getUserFullName(), 
					'mail' => $this->auth->getUserMail()	],
				['notAgreed' => $notAgreedProtocols, 'draftState' => $draftStateProtocols ],
				$vali->getFiltered('text')
			);
			if ($ok){
				// update proto
				$nproto['invite_mail_done'] = true;
				$this->db->updateNewproto($nproto);
				
				$this->json_result = [
					'success' => true,
					'msg' => 'Einladung erfolgreich versendet.'
				];
			} else {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim Senden der Einladung.'
				];
			}
			$this->print_json_result();
		}
	}
	
	/**
	 * POST action
	 * create protocol in wiki
	 */
	public function nptowiki(){
		$memberStateOptions = ['Fixme', 'J', 'E', 'N'];
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
			'legislatur' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Legislatur.'
			],
			'nthproto' => ['integer',
				'min' => '0',
				'error' => 'Ungültige Sitzungsnummer'
			],
			'reaskdone' => ['integer',
				'min' => '0',
				'max' => '1',
				'error' => 'Ungültiger Parameter: reaskdone'
			],
			'management' => ['name',
				'empty',
				'error' => 'Ungültiger Name: Sitzungsleitung'
			],
			'protocol' => ['name',
				'empty',
				'error' => 'Ungültige Name: Protokoll'
			],
			'member' => [ 'array',
				'key' => ['integer',
					'min' => '1',
					'error' => 'Invalid Member Id.'
				],
				'validator' => ['integer',
					'min' => '0',
					'max' => max(0, count($memberStateOptions)-1),
					'error' => 'Invalid Member State.'
				],
			]
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		if ($vali->getIsError()){
			if($vali->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($vali->getLastErrorCode() == 404){
				$this->json_not_found();
			} else {
				http_response_code ($vali->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $vali->getLastErrorMsg()];
				$this->print_json_result();
			}
		} else if (!checkUserPermission($vali->getFiltered('committee'))) {
			$this->json_access_denied();
		} else {
			$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
			if (!$nproto
				|| $nproto['gname'] != $vali->getFiltered('committee')
				|| $nproto['hash'] != $vali->getFiltered('hash')){
				$this->json_not_found('Protokoll nicht gefunden');
				return;
			}
			$nproto['name'] = date_create($nproto['date'])->format('Y-m-d');
			//don't allow dates in the past
			$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
			$now = date_create();
			$diff = $now->getTimestamp() - $validateDate->getTimestamp();
				
			if ($diff > 3600 * 4) { //4 hours ---> extend this time for Geko, if still not enought adjust time of protocol
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Vergangene Sitzungen können nicht im Wiki erzeugt werden.'
				];
				$this->print_json_result();
				return;
			}
			// dont allow duplicate creation on same newprotoelement
			if ($nproto['generated_url']) {
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Abeschlossene Protokolle können nicht noch einmal erzeugt werden.'
				];
				$this->print_json_result();
				return;
			}
			// check if page exists on wiki -> reask user if it should be overwritten
			require_once (SYSBASE.'/framework/class.wikiClient.php');
			$x = new wikiClient(WIKI_URL, WIKI_USER, WIKI_PASSWORD, WIKI_XMLRPX_PATH);
			if (!$vali->getFiltered('reaskdone')) {
				$a = $x->getPage(parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name']);
				if ($a != ''){
					$this->json_result = [
						'success' => true,
						'reask' => true,
						'msg' => '<div class="alert alert-danger" style="color: #770000;">Im Wiki existiert bereits ein Protokoll mit dem Namen "'.$nproto['name'].'".<br>Soll das Protokoll wirklich überschrieben werden?</div>'
					];
					$this->print_json_result();
					return;
				}
			}
			//management , protocol, member statemap
			$members = $this->db->getMembers($vali->getFiltered('committee'));
			foreach ($members as $id => $member){
				//management , protocol
				if ($member['name'] === $vali->getFiltered('management')){
					$nproto['management'] = $member['id'];
				}
				if ($member['name'] === $vali->getFiltered('protocol')){
					$nproto['protocol'] = $member['id'];
				}
				// member statemap
				$members[$id]['state'] = (isset($vali->getFiltered('member')[$member['id']]))? $vali->getFiltered('member')[$member['id']] : 0;
				$members[$id]['stateName'] = $memberStateOptions[$members[$id]['state']];
			}
			// open protocols // not aggreed
			$notAgreedProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, true, false, " AND P.ignore = 0 AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
			$draftStateProtocols = $this->db->getProtocols($vali->getFiltered('committee'), false, false, false, true, " AND P.ignore = 0 AND (P.public_url IS NULL) AND LENGTH(P.name) = 10 AND P.date > '2017-01-01' AND date < '".date_create()->format('Y-m-d')."'");
			$newprotoProtocols_tmp = $this->db->getNewprotos($vali->getFiltered('committee'));
			$newprotoProtocols = [];
			foreach ($newprotoProtocols_tmp as $np) {
				$newprotoProtocols[ date_create($np['date'])->format('Y-m-d') ] = $np;
			}
			//tops and gather file ids
			$files = [];
			require_once (FRAMEWORK_PATH.'/class.fileHandler.php');
			$fh = new FileHandler($this->db);
			$tops_tmp = $this->db->getTopsOpen($nproto['gname'], true);
			$tops = [];
			$skipped = [];
			foreach ($tops_tmp as $id => $top){
				if (!$top['skip_next']){
					$tops[$id] = $top;
					if ($top['filecounter'] > 0) $files[$top['id']] = $fh->filelist($top['id']);
				} else {
					$skipped[$id] = $top;
				}
			}
			//resortalias
			$resorts = $this->db->getResorts($vali->getFiltered('committee'));
			
			//create protocoltext
			ob_start();
			$this->includeTemplate('protocol_template_'.strtolower($vali->getFiltered('committee')), [
				'legislatur' => $vali->getFiltered('legislatur'),
				'nthproto' => $vali->getFiltered('nthproto'),
				'proto' => $nproto,
				'members' => $members,
				'date' => date_create($nproto['date']),
				'protoInternLink' => WIKI_URL.'/'.parent::$protomap[$vali->getFiltered('committee')][0].'/',
				'protoPublicLink' => WIKI_URL.'/'.parent::$protomap[$vali->getFiltered('committee')][1].'/',
				'openProtocols' => ['notAgreed' => $notAgreedProtocols, 'draftState' => $draftStateProtocols, 'newproto' => $newprotoProtocols ],
				'protoAttachBasePath' => parent::$protomap[$vali->getFiltered('committee')][0],
				'files' => $files,
				'tops' => $tops,
				'resorts' => $resorts,
			]);
			$prot_text = ob_get_clean();
			
			if (DEBUG >= 2){
				echo '<pre>'; var_dump($prot_text); echo '</pre>';
				return;
			}
			
			$ok = false;
			//write protocol to wiki
			$ok = $x->putPage(
				parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'],
				$prot_text,
				['sum' => 'GENERIERT mit '.BASE_TITLE.' von ('. $this->auth->getUsername().')']
				);
			if ($ok == false){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim Schreiben des Protokolls im Wiki. (Code: '.$x->getStatusCode().') (Blockiert bereits ein anderer Nutzer das Protokoll?)'
				];
				error_log('NewProto -> WIKI: Could not write. (Protocol may be blocked by other user?) Request: Put Page - '.parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'].' - Wiki respond: '.$x->getStatusCode().' - '.(($x->isError())?$x->getError():''));
				$this->print_json_result();
				return;
			}
			//upload files
			$attach_base = parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'];
			foreach ($files as $tid => $filelist){
				/* @var $file File */
				foreach ($filelist as $file){
					$ok = false;
					$wikipath = $attach_base.':'.str_replace(' ', '_', $file->filename).(($file->fileextension)?'.'.$file->fileextension:'');
					$ok = $x->putAttachement($wikipath,$fh->fileToBase64($file),['ow' => true]);
					if ($ok == false){
						$this->json_result = [
							'success' => false,
							'eMsg' => 'Fehler beim Dateiupload. (Code: '.$x->getStatusCode().')'
						];
						error_log('NewProto -> WIKI: Could not write. Request: Put Page - '.parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'].' - Wiki respond: '.$x->getStatusCode().' - '.(($x->isError())?$x->getError():''));
						$this->print_json_result();
						return;
					}
				}
			}
			// update tops
			foreach ($tops as $top){
				$top['used_on'] = $nproto['id'];
				$this->db->updateTop($top);
			}
			// unskip skipped for next week
			foreach ($skipped as $top){
				$top['skip_next'] = 0;
				$this->db->updateTop($top);
			}
			
			// update newproto 
			$nproto['generated_url'] = $nproto['name'];
			$this->db->updateNewproto($nproto);
			if (!$ok){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim DB Update.'
				];
				$this->print_json_result();
				return;
			}

			$this->json_result = [
				'success' => true,
				'msg' => 'Protokoll im Wiki erstellt.',
				'url' => WIKI_URL.'/'.str_replace(':', '/', parent::$protomap[$vali->getFiltered('committee')][0].':'.$nproto['name'])
			];
			$this->print_json_result();
			
		}
	}

	private function handleValidatorError($validator){
		if ($validator->getIsError()){
			if($validator->getLastErrorCode() == 403){
				$this->json_access_denied();
			} else if($validator->getLastErrorCode() == 404){
				$this->json_not_found();
				die();
			} else {
				http_response_code ($validator->getLastErrorCode());
				$this->json_result = ['success' => false, 'eMsg' => $validator->getLastErrorMsg()];
				$this->print_json_result();
				die();
			}
		} else if (!checkUserPermission($validator->getFiltered('committee'))) {
			$this->json_access_denied();
			die();
		}
	}

	/**
	 * POST action
	 * partial restore of newproto used tops
	 * create copy in database
	 * return list
	 */
	public function itopnplist() {
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		$this->handleValidatorError($vali);

		$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
		if (!$nproto
			|| $nproto['gname'] != $vali->getFiltered('committee')
			|| $nproto['hash'] != $vali->getFiltered('hash')){
			$this->json_not_found('Protokoll nicht gefunden');
			return;
		}
		$nproto['name'] = date_create($nproto['date'])->format('Y-m-d');
		//don't allow dates in the past
		$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
		$now = date_create();
		$diff = $now->getTimestamp() - $validateDate->getTimestamp();
		$settings = $this->db->getSettings();
		if ($diff > 3600 * 24 * intval($settings['DISABLE_RESTORE_OLDER_DAYS'])) { // default 3 weeks
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Vergangene Sitzungeneinladungen können nicht wiederhergestellt werden.'
			];
			$this->print_json_result();
			return;
		}
		// dont allow duplicate creation on same newprotoelement
		if (!$nproto['generated_url']) {
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Nicht abeschlossene Einladungen können nicht wiederhergestellt werden.'
			];
			$this->print_json_result();
			return;
		}

		//tops
		$tops_tmp = $this->db->getTopsByNewproto($nproto['id']);
		// update tops
		$top_out = [];
		foreach ($tops_tmp as $top){
			$top_out[] = [
				'id' => $top['id'],
				'hash' => $top['hash'],
				'headline' => $top['headline'],
				'resort' => $top['resort'],
				'person' => $top['person'],
				'text' => (strlen($top['text']) > 153) ? substr($top['text'],0,150).'...' : $top['text'],
			];
		}
		$this->json_result = ['success' => true, 'msg' => "", 'list' => $top_out];
		$this->print_json_result();
		die();
	}

	/**
	 * POST action
	 * partial restore of newproto used tops
	 * handle restore
	 */
	public function itoprecreate() {
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Kein Protokoll hash.'
			],
			'tid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Topid'
			],
			'thash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Kein Top hash.'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		$this->handleValidatorError($vali);

		$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
		if (!$nproto
			|| $nproto['gname'] != $vali->getFiltered('committee')
			|| $nproto['hash'] != $vali->getFiltered('hash')){
			$this->json_not_found('Protokoll nicht gefunden');
			return;
		}
		$nproto['name'] = date_create($nproto['date'])->format('Y-m-d');
		//don't allow dates in the past
		$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
		$now = date_create();
		$diff = $now->getTimestamp() - $validateDate->getTimestamp();
		$settings = $this->db->getSettings();
		if ($diff > 3600 * 24 * intval($settings['DISABLE_RESTORE_OLDER_DAYS'])) { // default 3 weeks
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Vergangene Sitzungeneinladungen können nicht wiederhergestellt werden.'
			];
			$this->print_json_result();
			return;
		}
		// dont allow duplicate creation on same newprotoelement
		if (!$nproto['generated_url']) {
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Nicht abeschlossene Einladungen können nicht wiederhergestellt werden.'
			];
			$this->print_json_result();
			return;
		}

		//tops
		$top = $this->db->getTopById($vali->getFiltered('tid'), true);
		if (!$top
			|| $top['gname'] != $vali->getFiltered('committee')
			|| $top['hash'] != $vali->getFiltered('thash')
			|| $top['used_on'] != $vali->getFiltered('npid') ){
			$this->json_not_found('Top nicht gefunden.');
			die();
		}
		// update tops
		$top['used_on'] = NULL;
		$top['hash'] = md5($top['headline'].date_create()->getTimestamp().$vali->getFiltered('committee').mt_rand(0, 640000));

		if (!$this->db->updateTop($top)) {
			$this->json_not_found('Top konnte nicht erstellt werden.');
			return;
		} else {
			$top['isNew'] = 1;
			$top['addedOn'] = date_create($top['added_on'])->format('d.m.Y H:i');
			$top['goal'] = ($top['goal'])?$top['goal']:'';
			$top['person'] = ($top['person'])?$top['person']:'---';
			$top['expected_duration'] = ($top['expected_duration'])?$top['expected_duration']:0;
		}

		$this->json_result = [
			'top' => $top,
			'success' => true,
			'msg' => 'Top wiederhergestellt.'
		];
		$this->print_json_result();
	}

	/**
	 * POST action
	 * restore newproto + used tops
	 */
	public function nprestore(){
		$memberStateOptions = ['Fixme', 'J', 'E', 'N'];
		//calculate accessmap
		$validator_map = [
			'committee' => ['regex',
				'pattern' => '/'.implode('|', array_keys(PROTOMAP)).'/',
				'maxlength' => 10,
				'error' => 'Du hast nicht die benötigten Berechtigungen, um dieses Protokoll zu bearbeiten.'
			],
			'hash' => ['regex',
				'pattern' => '/^([0-9a-f]{32})$/',
				'empty',
				'error' => 'Protokollkennung hat das falsche Format.'
			],
			'npid' => ['integer',
				'min' => '1',
				'error' => 'Ungültige Sitzungsid'
			],
		];
		$vali = new Validator();
		$vali->validateMap($_POST, $validator_map, true);
		$this->handleValidatorError($vali);

		$nproto = $this->db->getNewprotoById($vali->getFiltered('npid'));
		if (!$nproto
			|| $nproto['gname'] != $vali->getFiltered('committee')
			|| $nproto['hash'] != $vali->getFiltered('hash')){
			$this->json_not_found('Protokoll nicht gefunden');
			return;
		}
		$nproto['name'] = date_create($nproto['date'])->format('Y-m-d');
		//don't allow dates in the past
		$validateDate = date_create_from_format('Y-m-d H:i:s', $nproto['date']);
		$now = date_create();
		$diff = $now->getTimestamp() - $validateDate->getTimestamp();
		$settings = $this->db->getSettings();
		if ($diff > 3600 * 24 * intval($settings['DISABLE_RESTORE_OLDER_DAYS'])) { // default 3 weeks
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Vergangene Sitzungeneinladungen können nicht wiederhergestellt werden.'
			];
			$this->print_json_result();
			return;
		}
		// dont allow duplicate creation on same newprotoelement
		if (!$nproto['generated_url']) {
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Nicht abeschlossene Einladungen können nicht wiederhergestellt werden.'
			];
			$this->print_json_result();
			return;
		}

		//tops
		$tops_tmp = $this->db->getTopsByNewproto($nproto['id']);
		// update tops
		foreach ($tops_tmp as $top){
			$top['used_on'] = NULL;
			$ok = $this->db->updateTop($top);
			if (!$ok){
				$this->json_result = [
					'success' => false,
					'eMsg' => 'Fehler beim DB Update.'
				];
				$this->print_json_result();
				return;
			}
		}

		// update newproto
		$nproto['generated_url'] = NULL;
		$ok = $this->db->updateNewproto($nproto);
		if (!$ok){
			$this->json_result = [
				'success' => false,
				'eMsg' => 'Fehler beim DB Update.'
			];
			$this->print_json_result();
			return;
		}

		$this->json_result = [
			'success' => true,
			'msg' => 'Protokoll wiederhergestellt.',
		];
		$this->print_json_result();
	}
}
