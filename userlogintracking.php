<?php
/**
 * @package       Plugin User Login Traking for Joomla! 3.6
 * @author        A. S. M. Sadiqul Islam
 * @copyright (C) 2014- A. S. M. Sadiqul Islam
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/

defined('_JEXEC') or die('Restricted access');

/**
 * Class plgUserUserlogintracking
 */
class plgUserUserlogintracking extends JPlugin
{
	/**
	 * Database Object. Automagically assigned by parent constructor
	 *
	 * @var    JDatabaseDriver
	 * @since  2.0
	 */
	protected $db;

	/**
	 * Application. Automagically assigned by parent constructor
	 *
	 * @var    JApplication
	 * @since  2.0
	 */
	protected $app;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  2.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Acting after a user is successfully logged in.
	 *
	 * @param   array $options Array holding options
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function onUserAfterLogin($options)
	{
		if (!$this->params->get('track_superuser') && $options['user']->get('isRoot'))
		{
			return;
		}

		$jinput = $this->app->input;

		$data              = array();
		$data['timestamp'] = JFactory::getDate()->getTimestamp();
		$data['userid']    = $options['user']->id;
		$data['username']  = $options['user']->username;
		$data['ip']        = $jinput->server->getString('HTTP_CLIENT_IP');

		if (!$data['ip'])
		{
			$data['ip'] = $jinput->server->getString('HTTP_X_FORWARDED_FOR');
		}

		if (!$data['ip'])
		{
			$data['ip'] = $jinput->server->getString('REMOTE_ADDR');
		}

		if ($this->storeInDatabase($data))
		{
			$this->sendMail($data);
		}

		return;
	}

	/**
	 * Stores the login data into the database
	 *
	 * @param   array $data Array holding data to be stored.
	 *
	 * @return bool
	 * @since   1.0
	 */
	public function storeInDatabase($data)
	{
		$query   = $this->db->getQuery(true);
		$columns = array('userid', 'username', 'ip', 'timestamp');
		$values  = $this->db->quote(array($data['userid'], $data['username'], $data['ip'], $data['timestamp']));
		$query
			->insert($this->db->quoteName('#__userlogin_tracking'))
			->columns($this->db->quoteName($columns))
			->values(implode(',', $values));

		$this->db->setQuery($query);

		try
		{
			$this->db->execute();
		}
		catch (Exception $e)
		{
			throw($e);
			// Do nothing
			return false;
		}

		return true;
	}

	/**
	 * Sends the Email
	 *
	 * @param   array $data Array holding data to be emailed.
	 *
	 * @return void
	 *
	 * @since   2.0
	 */
	public function sendMail($data)
	{
		if (!$this->params->get('send_mail') || !$this->params->get('usergroup'))
		{
			return;
		}

		// Get recipients in the group
		$recipients = JAccess::getUsersByGroup($this->params->get('usergroup'));

		if ($recipients)
		{
			return;
		}

		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName(array('email, name')))
			->from('#__users')
			->where($this->db->quoteName('sendEmail') . ' = 1')
			->where($this->db->quoteName('id') . ' IN (' . implode(',', $recipients) . ')');
		$this->db->setQuery($query);

		$recipients = $this->db->loadObjectList();

		if ($recipients)
		{
			return;
		}

		$subject = JText::_('PLG_USER_USERLOGINTRACKING_MAIL_SUBJECT');
		$body    = JText::sprintf('PLG_USER_USERLOGINTRACKING_MAIL_SUBJECT',
			$data['user']->username,
			$data['user']->user->id,
			$data['timestamp'],
			$data['ip']
		);

		$mailer = JFactory::getMailer();

		foreach ($recipients as $recipient)
		{
			$mailer->addRecipient($recipient->email, $recipient->name);
		}

		$mailer->setSender(array($this->app->get('mailfrom'), $this->app->get('fromname')));
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->isHtml(true);
		$mailer->Send();

		return;
	}
}
