<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonEmailHelper
{
		/**
	 * Invite a player
	 *
	 * @param $table		The table with the player to be sent an invite to
	 * @param $sender		The sender for the email
	 * @param $email		The email template to be sent
	 *
	 * @return bool			True if the invitation mail has succeeded
	 */
	public static function invitePlayer($table) {
		
		// Initialize
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		$sender = array( 
			$config->get('mailfrom'),
			$config->get('fromname') 
		);
		$parameters = JComponentHelper::getParams('com_badminton');
		$body = $parameters->get('invitation');
		
		// Initialize plugin
		JPluginHelper::importPlugin('bamadi');
		$dispatcher = JEventDispatcher::getInstance();

		// Dispatch event before invitation
		$results = $dispatcher->trigger('onPlayerBeforeInvite', array($table));
		if (in_array(false, $results))
			return false;
		
		// Generate a unique string
		$verification = substr(md5(rand()), 0, 25);
		
		// Calculate the registration link
		$linkparams = array(
			'verification=' . urlencode($verification),
			'email=' . urlencode($table->email),
			'name=' . urlencode($table->firstname . ' ' . $table->lastname)
		);
		$link = 'index.php?option=com_users&view=registration&' . implode('&', $linkparams);
		$link = JUri::base() . $link;
		
		// Store the verification string in the database
		$table->bind(array("verification" => $verification));
		$table->store();
		
		// Send the email
		$mailer = JFactory::getMailer();
		$mailer->setSender($sender);
		$mailer->addRecipient($table->email);
		$mailer->setSubject('Registration BAMADI');
		$mailer->isHTML(true);
		
		// Setup the body
		$body = empty($body) ? '$link$' : $body;
		$mailbody = str_replace('$verification$', urlencode($verification), $body);
		$mailbody = str_replace('$firstname$', $table->firstname, $mailbody);
		$mailbody = str_replace('$lastname$', $table->lastname, $mailbody);
		$mailbody = str_replace('$link$', $link, $mailbody);
		$mailbody .= $parameters->get('email-signature', '');
		$mailer->setBody($mailbody);
		$send = $mailer->Send();
		
		// Check sending
		if ($send !== true)
		{
			$app->enqueueMessage(JText::sprintf('COM_BADMINTON_COULD_NOT_SEND_EMAIL', $table->email) . '<br />' . $mailbody, 'warning');
			return false;
		}
		
		// Dispatch event after invitation
		$dispatcher->trigger('onPlayerAfterInvite', array( $table ));
		
		// Email sent
		$app->enqueueMessage(JText::sprintf('COM_BADMINTON_PLAYER_INVITED', $table->firstname . ' ' . $table->lastname));
		return true;
	}

	/**
	 * Update a player
	 *
	 * @param $table		The table containing player information
	 * @param $sender		The sender for the email
	 * @param $accepted		The messages for accepted parameters
	 * @param $denied		The messages for denied parameters
	 * @param $parameters	The parameters
	 */
	public static function updatePlayer($table, $accepted, $denied) {
		
		// No need to notify
		if (count($accepted) == 0 && count($denied) == 0)
			return;
		
		// Initialize
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		$sender = array( 
			$config->get('mailfrom'),
			$config->get('fromname') 
		);
		$parameters = JComponentHelper::getParams('com_badminton');
		
		// Send an email to update of the changes
		if (!empty($table->email)) {

			// Send the email
			$mailer = JFactory::getMailer();
			$mailer->setSender($sender);
			$mailer->addRecipient($table->email);
			$mailer->setSubject('Update BAMADI');
			$mailer->isHTML(true);
			
			$body = JText::sprintf('COM_BADMINTON_PLAYER_EMAIL_CHANGE', $table->firstname . ' ' . $table->lastname);
			$body .= "<ul>";
			foreach ($accepted as $key => $value) {
				$name = strtolower(JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($key)));
				if ($key != 'avatar')
					$body .=  '<li>' . JText::sprintf('COM_BADMINTON_PLAYER_EMAIL_CHANGE_ACCEPTED', $name, $table->$key) . '</li>';
				else
				{
					$src = JUri::base() . $app->getParams('com_badminton')->get('avatar_path', '') . '/' . $table->$key;
					$body .= '<li>' . JText::sprintf('COM_BADMINTON_PLAYER_EMAIL_CHANGE_ACCEPTED', $name, "<img src=\"$src\" />" . '</li>');
				}
			}
			foreach ($denied as $key => $value) {
				$name = strtolower(JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($key)));
				if ($key != 'avatar')
					$body .= '<li>' . JText::sprintf('COM_BADMINTON_PLAYER_EMAIL_CHANGE_DENIED', $name, $value, $table->$key) . '</li>';
				else
				{
					$src = JUri::base() . $app->getParams('com_badminton')->get('avatar_path', '') . '/' . $table->$key;
					$body .= '<li>' . JText::sprintf('COM_BADMINTON_PLAYER_EMAIL_CHANGE_DENIED', $name, $value, "<img src=\"$src\" />") . '</li>';
				}
			}
			$body .= "</ul>";
			$body .= $parameters->get('email-signature', '');
			
			// Setup the body
			$mailer->setBody($body);
			$send = $mailer->Send();
			
			// Check sending
			if ($send !== true) {
				$app->enqueueMessage(JText::sprintf('COM_BADMINTON_COULD_NOT_SEND_EMAIL', $table->email) . '<br />' . $body, 'warning');
				return false;
			} else {
				$app->enqueueMessage(JText::sprintf('COM_BADMINTON_PLAYER_UPDATED', $table->firstname . ' ' . $table->lastname));
				return true;
			}
		}
		return false;
	}
	
	/*
	 * Send an email to member management
	 */
	public static function updateRegistration($table) {
		
		// Initialize
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		$sender = array( 
			$config->get('mailfrom'),
			$config->get('fromname') 
		);
		$parameters = JComponentHelper::getParams('com_badminton');
		$mailbody = $parameters->get('registration');

		// Create a list of users that need the email
		$groups = $parameters->get('changed-group', array());
		if (empty($groups))
			return false;
		$emails = array();
		foreach ($groups as $group) {
			$users = JAccess::getUsersByGroup($group);
			$db->setQuery("SELECT id,email FROM " . $db->quoteName('#__users') . " where id IN (" . implode(',', $users) . ");");
			$list = $db->loadAssocList();
			foreach ($list as $row)
				$emails[$row['id']] = $row['email'];
		}
		
		// Extract the remark
		$formdata = $app->input->get('jform', array(), 'array');
		$remark = isset($formdata['remark']) ? $formdata['remark'] : '';
		
		// Build the email to notify managers of changes
		$mailer = JFactory::getMailer();
		$mailer->setSender($sender);
		$mailer->addRecipient(array_values($emails));
		$mailer->setSubject('Update BAMADI');
		$mailer->isHTML(true);
		
		$src = JUri::base() . $app->getParams('com_badminton')->get('avatar_path', '') . '/' . $table->avatar;
		$mailbody = str_replace('$avatar$', $src, $mailbody);
		$mailbody = str_replace('$firstname$', $table->firstname, $mailbody);
		$mailbody = str_replace('$lastname$', $table->lastname, $mailbody);
		$mailbody = str_replace('$email$', $table->email, $mailbody);
		$mailbody = str_replace('$gender$', self::formatGender($table->gender), $mailbody);
		$mailbody = str_replace('$birthdate$', self::formatDate($table->birthdate), $mailbody);
		$mailbody = str_replace('$address1$', $table->address1, $mailbody);
		$mailbody = str_replace('$address2$', $table->address2, $mailbody);
		$mailbody = str_replace('$postalcode$', $table->postalcode, $mailbody);
		$mailbody = str_replace('$city$', $table->city, $mailbody);
		$mailbody = str_replace('$home$', $table->home, $mailbody);
		$mailbody = str_replace('$mobile$', $table->mobile, $mailbody);
		$mailbody = str_replace('$remark$', $remark, $mailbody);
		
		$mailbody .= $parameters->get('email-signature', '');

		// Setup the body
		$mailer->setBody($mailbody);
		$send = $mailer->Send();
		if ($send !== true) {
			$app->enqueueMessage(JText::_('COM_BADMINTON_REGISTRATIONNOTICE_NOT_SENT'), 'warning');
			return false;
		}
		
		// Succeeded
		$app->enqueueMessage(JText::_('COM_BADMINTON_REGISTRATIONNOTICE_SENT'));
		return true;
	}
	
	/*
	 * Format a gender
	 */
	protected static function formatGender($gender)
	{
		switch($gender) {
			case 1: return JText::_('COM_BADMINTON_PLAYER_GENDER_MALE');
			case 2: return JText::_('COM_BADMINTON_PLAYER_GENDER_FEMALE');
			default: return JText::_('COM_BADMINTON_PLAYER_GENDER_UNSPECIFIED');
		}
	}
	
	/*
	 * Format a date
	 */
	protected static function formatDate($date)
	{
		$d = new DateTime($date);
		return $d->format('Y-m-d');
	}
}