<?php

	/**
	 * @package Plugin User Login Traking for Joomla! 2.5
	 * @version $Id: mod_XYZ.php 599 2010-03-20 23:26:33Z you $
	 * @author A. S. M. Sadiqul Islam
	 * @copyright (C) 2014- A. S. M. Sadiqul Islam
	 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
	**/

defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.plugin.plugin');

class plgUserUserlogintracking extends JPlugin
{

	public $username;
	public $userID;
	public $IP;
	public $realtime;
	public $fromname;
	public $mailfrom;
	public $adminEmail;
	public $sendMail; 
	public $sendMail2SupperUser;


	function plgUserUserlogintracking(& $subject, $config) {
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}


	public function sendMail() {
		if(!$this->sendMail){
			return true;
		}
		$subject = 'User Login Tracking!';
		$body =  'User Login Information:<br>'
				.'Username: '.$this->username
				.'<br>ID: '.$this->userID
				.'<br>realtime: '.$this->realtime
				.'<br>IP: '.$this->IP;


		if ( version_compare( JVERSION, '3.0', '<' ) == 0) {
		 	$mailer = JFactory::getMailer();

			$sender = array($this->mailfrom, $this->fromname);
			$mailer->setSender($sender);
			$mailer->addRecipient($this->adminEmail);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->isHTML(true);
			$mailer->send();
		} else {
		   	JUtility::sendMail( $this->mailfrom, $this->fromname, $this->adminEmail, $subject, $body, true);
		}
	}

	public function storeInDatabase(){
		$this->getData();

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$columns = array('userid', 'username', 'ip', 'realtime');
		$values = array( $this->userID, $db->quote($this->username), $db->quote($this->IP), $this->realtime);
		$query
		    ->insert($db->quoteName('#__userlogin_tracking'))
		    ->columns($db->quoteName($columns))
		    ->values(implode(',', $values));
		$db->setQuery($query);

		if ($db->query()){
			$query->select($db->quoteName('email'));
			    $query->from($db->quoteName('#__users'));
			    $query->where($db->quoteName('name') . ' LIKE '. $db->quote('Super User'));
			    $query->order($db->quoteName('email'));
			$db->setQuery($query);
			$data_sp = $db->loadObjectList();

			foreach ($data_sp as $i => $email) {
				$this->adminEmail[$i] = $email->email;
			}
	
			$this->sendMail();
			return true;
		} else {
			return false;
		}
	}

	public function getData(){

		$mainframe = JFactory::getApplication();
		$this->fromname = $mainframe->getCfg('fromname');
		$this->mailfrom = $mainframe->getCfg('mailfrom');
		
			$http_client_ip = $_SERVER['HTTP_CLIENT_IP'];
			$http_x_forwarded_for= $_SERVER['HTTP_X_FORWARDED_FOR'];
			$remote_addr = $_SERVER['REMOTE_ADDR'];
			
			if(!empty($http_client_ip)){
				$ip = $http_client_ip;
			}else if(!empty($http_x_forwarded_for)){
				$ip = $http_x_forwarded_for;
			}else{
				$ip = $remote_addr;
			}

			$this->IP = $ip;

			date_default_timezone_set('GMT');
			$time = strftime('%Y-%m-%d %H:%M:%S');
			$this->realtime = "'".$time."'";
	}

	public function onUserLogin($user, $options = array()){
		jimport('joomla.user.helper');
		$mainframe = JFactory::getApplication();
		$this->username = $user['username'];
		$this->userID = JUserHelper::getUserId($user['username']);
		
		$this->getCommandsParams();

		if(!$this->sendMail2SupperUser){
			if($this->isUserSuperUser()){
				return true;
			}
		}

		$this->storeInDatabase();

		return true;
	}


	public function getCommandsParams(){
		$this->sendMail = $this->params->get('send_mail') ? true : false;
		$this->sendMail2SupperUser = $this->params->get('send_mail_supper_user') ? true : false;
	}

	public function isUserSuperUser()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
       		$query
			    ->select($db->quoteName('id'))
			    ->from($db->quoteName('#__users'))
			    ->where($db->quoteName('name') . ' LIKE '. $db->quote('Super User'));
		    $db->setQuery($query);	
			     $data_sp = $db->loadObjectList();
		 	$spId = $data_sp[0]->id;

			if($this->userID === $spId){
				return true;
			}else{
				return false;
			}		  
	}  
}
