<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonControllerGroup extends JControllerForm
{
	/**
	 * Check if a user is also the owner
	 *
	 * @param int $userid	The user ID
	 * @param int $ownerid	The group owner ID
	 */
	protected function isOwner($userid, $ownerid)
	{
		// No 0 id's allowed
		if (empty($userid) || empty($ownerid))
			return false;
		
		// Search the database
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('1')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('user_id') . '=' . $db->quote($userid)
				. ' AND ' . $db->quoteName('id') . '=' . $db->quote($ownerid));
		$db->setQuery('SELECT EXISTS(' . $query . ')');
		return ($db->loadResult() > 0);
	}
	
	/**
	 * Check if a user is the owner of a group by ID
	 *
	 * @param int $userid	The ID of the user
	 * @param int $groupid	The ID of the group
	 */
	protected function isGroupOwner($userid, $groupid)
	{
		$db = JFactory::getDbo();

		// No 0's allowed
		if (empty($userid) || empty($groupid))
			return false;
		
		// Search the database
		$query = $db->getQuery(true);
		$query->select('1')
			->from($db->quoteName('#__badminton_players', 'p'))
			->leftJoin($db->quoteName('#__badminton_groups', 'g') . ' ON ' . $db->quoteName('p.id') . '=' . $db->quoteName('g.ownerid'))
			->where($db->quoteName('g.id') . '=' . $db->quote($groupid) . ' AND ' . $db->quoteName('p.user_id') . '=' . $db->quote($userid));
		$db->setQuery("SELECT EXISTS($query)");
		return ($db->loadResult() > 0);
	}
	
	/**
	 * Check if the current user can add a group
	 *
	 * @return Boolean		True if the user can add
	 */
	protected function allowAdd($data = array()) {
		$user = JFactory::getUser();
		if ($user->authorise('group.add', 'com_badminton'))
			return true;
		if ($user->authorise('group.addown', 'com_badminton'))
			return $this->isOwner($user->id, $data['ownerid']);
		return false;
	}
	
	/**
	 * Check if the current user can edit a group
	 *
	 * @param array $data	The array containing at least the ID of the group
	 * @return Boolean		True if the user can add
	 */
	protected function allowEdit($data = array(), $key = 'id') {
		$user = JFactory::getUser();
		if ($user->authorise('group.edit', 'com_badminton'))
			return true;
		if ($user->authorise('group.editown', 'com_badminton'))
			return $this->isGroupOwner($user->id, $data[$key]);
		return $canEdit;
	}
	
	/**
	 * Check if the current user can view a group
	 *
	 * @return Boolean		True if the user can add
	 */
	protected function allowView($data = array(), $key = 'id') {
		$user = JFactory::getUser();
		if ($user->authorise('group.view', 'com_badminton')) // Can view anything
			return true;
		if ($user->authorise('group.edit', 'com_badminton')) // Can edit anything (so can view as well)
			return true;
		if ($user->authorise('group.editown', 'com_badminton')) // Can edit own stuff
			return $this->isOwner($user->id, $data['ownerid']);
		return false;
	}
	
	/**
	 * Allowed to send email?
	 *
	 * @return Boolean		True if the user can send emails to this group
	 */
	protected function allowEmail($data = array(), $key = 'id') {
		$user = JFactory::getUser();
		if ($user->authorise('group.email', 'com_badminton'))
			return true;
		if ($user->authorise('group.emailown', 'com_badminton'))
			return $this->isOwner($user->id, $data['ownerid']);
		return false;
	}

	/**
	 * Method to view a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   12.2
	 */
	public function view($key = null, $urlVar = null) {

		$app   = JFactory::getApplication();
		$model = $this->getModel();
		$table = $model->getTable();
		$cid   = $this->input->post->get('cid', array(), 'array');
		$context = "$this->option.edit.$this->context";
		
		// Determine the name of the primary key for the data.
		if (empty($key))
			$key = $table->getKeyName();

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
			$urlVar = $key;
		
		// Get the previous record id (if any) and the current record id.
		$recordId = (int) (count($cid) ? $cid[0] : $this->input->getInt($urlVar));
		$checkin = property_exists($table, 'checked_out');

		// Attempt to check-out the new record for editing and redirect.
		if ($checkin && !$model->checkout($recordId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);

			return false;
		}
		else
		{
			// Check-out succeeded, push the new record id into the session.
			$app->setUserState($context . '.data', null);
			
			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);

			return true;
		}
	}

	/**
	 * Send an email to all users in the group
	 */
	public function sendemail($key = null, $urlVar = null) {

		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
	
		// Initialize
		$app   = JFactory::getApplication();
		$user  = JFactory::getUser();
		$model = $this->getModel();
		$table = $model->getTable();
		$data  = $this->input->post->get('jform', array(), 'array');
		$config = JFactory::getConfig();
		$context = "$this->option.mail.all";
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

		// Determine the name of the primary key for the data.
		if (empty($key))
			$key = $table->getKeyName();
		
		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
			$urlVar = $key;

		// test email body
		$emailsubject = $data['emailsubject'];
		$emailbody = $data['emailbody'];

		// Get the previous record id (if any) and the current record id.
		$recordId = $this->input->getInt($urlVar);
		
		// Check if we're allowed to send emails
		if (!$this->allowEmail(array('id' => $recordId)))
		{
			$app->enqueueMessage(JText::_('COM_BADMINTON_EMAIL_NO_PERMISSION'), 'error');
			
			// redirect back to the group item
			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);
			return false;
		}

		// Get the email list
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select(array(
			'CONCAT_WS(" ",p.lastname,p.firstname) as name',
			$db->quoteName('p.id', 'id'),
			$db->quoteName('p.email', 'email1'),
			$db->quoteName('g.email', 'email2'),
			$db->quoteName('g2.email', 'email3')))
			->from($db->quoteName('#__badminton_players', 'p'))
			->leftJoin($db->quoteName('#__badminton_players', 'g') . ' ON ' . $db->quoteName('p.guardian') . '=' . $db->quoteName('g.id'))
			->leftJoin($db->quoteName('#__badminton_players', 'g2') . ' ON ' . $db->quoteName('p.guardian2') . '=' . $db->quoteName('g2.id'))
			->leftJoin($db->quoteName('#__badminton_group_members', 'm') . ' ON ' . $db->quoteName('m.playerid') . '=' . $db->quoteName('p.id'))
			->where($db->quoteName('m.groupid') . '=' . $db->quote($recordId));
		$results = $db->setQuery($query)->loadObjectList();
		$emailfields = array('email1', 'email2', 'email3');
		$emails = array();
		$contacted = array();
		$notcontact = array();
		foreach ($results as $i => $em)
		{
			$cemails = array();
			foreach ($emailfields as $field)
			{
				if (!empty($em->$field))
					$cemails[] = $em->$field;
			}
			if (count($cemails) > 0)
				$contacted[] = $em->name . ' (' . implode(', ', $cemails) . ')';
			else
				$notcontacted[$em->id] = $em->name;
			$emails = array_merge($emails, $cemails);
		}
		
		// Make a list of unique
		$emails = array_unique($emails);
		
		if (count($emails) == 0)
		{
			// No emails sent
			$app->enqueueMessage(JText::_('COM_BADMINTON_EMAIL_NOEMAILS'), 'error');
			
			// Save the email data
			$app->setUserState("$context.emaildata", array('emailbody' => $emailbody, 'emailsubject' => $emailsubject));
		}
		else
		{
			// Send the emails
			$mailer = JFactory::getMailer();
			$mailer->addRecipient($emails);
			$mailer->isHtml();
			$mailer->setSubject($emailsubject);
			$mailer->setBody($emailbody);
			$mailer->addReplyTo($user->email);
			$mailer->setSender($sender);
			if (!empty($attachment_loc) && !empty($attachment_name))
				$mailer->addAttachment($attachment_loc, $attachment_name);
			$sent = $mailer->Send();
			if ($sent !== true)
			{
				$app->enqueueMessage($sent->__toString(), 'error');
				
				// Save the email data
				$app->setUserState("$context.emaildata", array('emailbody' => $emailbody, 'emailsubject' => $emailsubject));
			}
			else
			{
				// Show sent email list
				$app->enqueueMessage(JText::sprintf('COM_BADMINTON_GROUP_EMAIL_SENT', count($emails)));
				if (count($notcontacted) > 0) {
					$app->enqueueMessage(JText::sprintf('COM_BADMINTON_GROUP_EMAIL_NOTCONTACTED', implode(', ', $notcontacted)), 'warning');
					
					// Save this notcontacted data for possible further usage
					$app->setUserState("$context.notcontacted", array_keys($notcontacted));
				}
				
				// Clear email data
				$app->setUserState("$context.emaildata", null);
			}
		}
		
		// redirect back to the group item
		$this->setRedirect(
			JRoute::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_item
				. $this->getRedirectToItemAppend($recordId, $urlVar), false
			)
		);
			
		return true;
	}
}