<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonControllerMailall extends JControllerLegacy
{
	protected $option = "com_badminton";
	protected $context = "mailall";

	protected function canEmail()
	{
		$user = JFactory::getUser();
		if (!$user->authorise('players.mailall', 'com_badminton'))
			return false;
		return true;
	}

	/**
	 * Send an email to all users in the group
	 */
	public function send() {

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		// Initialize
		$app   = JFactory::getApplication();
		$user  = JFactory::getUser();
		$model = $this->getModel();
		$data  = $this->input->post->get('jform', array(), 'array');
		$config = JFactory::getConfig();
		$context = "$this->option.edit.$this->context";
		$task = $this->getTask();
		$sender = array(
			$config->get('mailfrom'),
			$user->name
		);
		$files = JRequest::getVar('jform', array(), 'files', 'array');
		if (isset($files['name']['attachment']))
			$attachment_name = $files['name']['attachment'];
		if (isset($files['tmp_name']['attachment']))
			$attachment_loc = $files['tmp_name']['attachment'];
		$emailsubject = $data['emailsubject'];
		$emailbody = $data['emailbody'];

		// Check if we're allowed to send emails
		if (!$this->canEmail())
		{
			$app->enqueueMessage(JText::_('COM_BADMINTON_EMAIL_NO_PERMISSION'), 'error');
			$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=mailall'));
		}

		// Get the email list
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array(
			$db->quoteName('firstname'),
			$db->quoteName('lastname'),
			$db->quoteName('email')))
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('email') . ' LIKE "%@%.%"');
		$results = $db->setQuery($query)->loadObjectList();
		
		// Send the email to everyone
		$messages = array();
		$errors = array();
		foreach ($results as $i => $em)
		{
			$mailer = JFactory::getMailer();
			$mailer->addRecipient($em->email);
			$mailer->isHtml();
			$mailer->setSubject($emailsubject);
			$mailer->addReplyTo($user->email);
			$mailer->setSender($sender);
			if (!empty($attachment_loc) && !empty($attachment_name))
				$mailer->addAttachment($attachment_loc, $attachment_name);
			
			// Make the body
			$body = str_replace("{firstname}", $em->firstname, $emailbody);
			$body = str_replace("{lastname}", $em->lastname, $body);
			$mailer->setBody($body);
			
			$sent = $mailer->Send();
			if ($sent !== true)
			{
				$errors[] = $sent->__toString();
				$errors[] = JText::sprintf("COM_BADMINTON_EMAIL_FAILED", $em->email);
				
				// Save the email data to try again
				$app->setUserState("$context.emaildata", array('emailbody' => $emailbody, 'emailsubject' => $emailsubject));
			}
			else
			{
				$messages[] = JText::sprintf("COM_BADMINTON_EMAIL_SUCCEEDED", $em->email);
			}
		}
		
		// Output messages
		if (count($messages) > 0)
			$app->enqueueMessage(implode("<br />", $messages));
		if (count($errors) > 0)
			$app->enqueueMessage(implode("<br />", $errors), 'error');
			
		// Redirect back to the email
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=mailall'));
	}
}