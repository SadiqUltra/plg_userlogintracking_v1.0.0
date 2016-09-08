<?php
/**
 * @package       Plugin User Login Traking for Joomla! 3.6
 * @author        A. S. M. Sadiqul Islam
 * @copyright (C) 2014- A. S. M. Sadiqul Islam
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * Class plgUserUserlogintracking
 */
class plgUserUserlogintracking extends JPlugin
{

	/**
	 * @var
	 */
	public $username;

	/**
	 * @var
	 */
	public $userID;

	/**
	 * @var
	 */
	public $IP;

	/**
	 * @var
	 */
	public $timestamp;

	/**
	 * @var
	 */
	public $fromname;

	/**
	 * @var
	 */
	public $mailfrom;

	/**
	 * @var
	 */
	public $adminEmail;

	/**
	 * @var
	 */
	public $sendMail;

	/**
	 * @var
	 */
	public $sendMail2SuperUser;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * @return bool
	 */
	public function sendMail()
	{
		if (!$this->sendMail)
		{
			return true;
		}
		$subject = 'User Login Tracking!';
		$body    = 'User Login Information:<br>'
			. 'Username: ' . $this->username
			. '<br>ID: ' . $this->userID
			. '<br>Timestamp: ' . $this->timestamp
			. '<br>IP: ' . $this->IP;


		if (version_compare(JVERSION, '3.0', '<') == 0)
		{
			$mailer = JFactory::getMailer();

			$sender = array($this->mailfrom, $this->fromname);
			$mailer->setSender($sender);
			$mailer->addRecipient($this->adminEmail);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
			$mailer->isHTML(true);
			$mailer->send();
		}
		else
		{
			JUtility::sendMail($this->mailfrom, $this->fromname, $this->adminEmail, $subject, $body, true);
		}
	}

	/**
	 * @return bool
	 */
	public function storeInDatabase()
	{
		$this->getData();

		$db      = JFactory::getDbo();
		$query   = $db->getQuery(true);
		$columns = array('userid', 'username', 'ip', 'timestamp');
		$values  = array($this->userID, $db->quote($this->username), $db->quote($this->IP), $this->timestamp);
		$query
			->insert($db->quoteName('#__userlogin_tracking'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));
		$db->setQuery($query);

		if ($db->query())
		{
			$query->select($db->quoteName('email'));
			$query->from($db->quoteName('#__users'));
			$query->where($db->quoteName('name') . ' LIKE ' . $db->quote('Super User'));
			$query->order($db->quoteName('email'));
			$db->setQuery($query);
			$data_sp = $db->loadObjectList();

			foreach ($data_sp as $i => $email)
			{
				$this->adminEmail[$i] = $email->email;
			}

			$this->sendMail();

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 *
	 */
	public function getData()
	{

		$mainframe      = JFactory::getApplication();
		$this->fromname = $mainframe->getCfg('fromname');
		$this->mailfrom = $mainframe->getCfg('mailfrom');

		$http_client_ip       = $_SERVER['HTTP_CLIENT_IP'];
		$http_x_forwarded_for = $_SERVER['HTTP_X_FORWARDED_FOR'];
		$remote_addr          = $_SERVER['REMOTE_ADDR'];

		if (!empty($http_client_ip))
		{
			$ip = $http_client_ip;
		}
		else if (!empty($http_x_forwarded_for))
		{
			$ip = $http_x_forwarded_for;
		}
		else
		{
			$ip = $remote_addr;
		}

		$this->IP = $ip;

		date_default_timezone_set('GMT');
		$time            = time();
		$this->timestamp = $time;
	}

	/**
	 * @param       $user
	 * @param array $options
	 *
	 * @return bool
	 */
	public function onUserLogin($user, $options = array())
	{
		jimport('joomla.user.helper');
		$mainframe      = JFactory::getApplication();
		$this->username = $user['username'];
		$this->userID   = JUserHelper::getUserId($user['username']);

		$this->getCommandsParams();

		if (!$this->sendMail2SuperUser)
		{
			if ($this->isUserSuperUser())
			{
				return true;
			}
		}

		$this->storeInDatabase();

		return true;
	}


	/**
	 *
	 */
	public function getCommandsParams()
	{
		$this->sendMail           = $this->params->get('send_mail', 1) ? true : false;
		$this->sendMail2SuperUser = $this->params->get('send_mail_supper_user') ? true : false;
	}

	/**
	 * @return bool
	 */
	public function isUserSuperUser()
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('name') . ' LIKE ' . $db->quote('Super User'));
		$db->setQuery($query);
		$data_sp = $db->loadObjectList();
		$spId    = $data_sp[0]->id;

		if ($this->userID === $spId)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}
