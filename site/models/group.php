<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonModelGroup extends JModelAdmin
{
	public $emailform;

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
	 * Check if a user is owner of the group
	 *
	 * @param int $userid		The user id
	 * @param int $groupid		The group id
	 */
	protected function isOwnerOfGroup($userid, $groupid)
	{
		$db = JFactory::getDbo();
		
		// No 0's allowed
		if (empty($userid) || empty($groupid))
			return false;
		
		// Search the database
		$query = $db->getQuery(true);
		$db->select('1')
			->from($db->quoteName('#__badminton_players', 'p'))
			->leftJoin($db->quoteName('#__badminton_groups', 'g') . ' ON ' . $db->quoteName('p.id') . '=' . $db->quoteName('g.ownerid'))
			->where($db->quoteName('g.id') . '=' . $db->quote($groupid) . ' AND ' . $db->quoteName('p.user_id') . '=' . $db->quote($userid));
		$db->setQuery("SELECT EXISTS($query)");
		return ($db->loadResult() > 0);
	}

	/**
	 * Method to get a table object
	 *
	 * @param $type		The name of the table
	 * @param $prefix	The prefix of the name of the table
	 * @param $config	Configuration data
	 *
	 * @return JTable	The table
	 */
	public function getTable($type = 'Group', $prefix = 'BadmintonTable', $config = array()) {
		
		// Return an instance of the group table
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Get the form for a group
	 *
	 * @param 	$data
	 * @param	$loadData
	 *
	 * @return JForm	The form used to the data
	 */
	public function getForm($data = array(), $loadData = true) {

		// Load form/group.xml
		$form = $this->loadForm('com_badminton.group', 'group', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			JFactory::getApplication()->enqueueMessage('No form');
			return false;
		}
		
		// Add field paths
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$form->addFieldPath(JPATH_COMPONENT . '/models/fields');

		// Disable fields depending on access control
		$user = JFactory::getUser();
		$app = JFactory::getApplication();

		// If no global rights, then disable the owner field
		if (!$user->authorise('group.edit', 'com_badminton') && !$user->authorise('group.add', 'com_badminton'))
			$form->setFieldAttribute('ownerid', 'disabled', 'true');
		return $form;
	}
	
	/**
	 * Get the form for emails
	 */
	public function getEmailForm($data = array(), $loadData = true) {
		
		// Load form/email.xml
		$form = $this->loadForm('com_badminton.email', 'email', array('control' => 'jform', 'load_data' => $loadData));
		$context = "$this->option.edit.group";
		if (empty($form)) {
			JFactory::getApplication()->enqueueMessage('No form');
			return false;
		}
		
		// Disable fields depending on access control
		$user = JFactory::getUser();
		$app = JFactory::getApplication();

		// Find default values from posted requests if available
		$data = $app->getUserState("$context.emaildata", array());
		$form->bind($data);

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form
	 *
	 * @return mixed	The data to be used in the form
	 */
	public function loadFormData() {
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		
		// Get the data from the user state or else load it from the database
		$data = $app->getUserState("$this->option.edit.group.data", array());
		if (empty($data))
			$data = $this->getItem();
		return $data;
	}

	/**
	 * Save a group to the database
	 *
	 * @param array $data	The data to be saved
	 *
	 * @return boolean		True if succeeded, false if error
	 */
	public function save($data) {
		
		$db = JFactory::getDbo();
		
		// If there is no $id assigned (meaning it is a new group) then use the current player as the owner
		if (empty($data['id']))
		{
			$user = JFactory::getUser();
			$query = $db->getQuery(true);
			$query->select('id')
				->from($db->quoteName('#__badminton_players'))
				->where($db->quoteName('user_id') . '=' . $db->quote($user->id));
			$db->setQuery($query);
			$playerid = $db->loadResult();
			if ($playerid > 0)
				$data['ownerid'] = $playerid;
		}
		
		return parent::save($data);
	}
	
	/**
	 * Method to delete one or more records.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   12.2
	 */
	public function delete(&$pks) {
		$dispatcher = JEventDispatcher::getInstance();
		$pks = (array) $pks;
		$table = $this->getTable();

		// Include the plugins for the delete events.
		JPluginHelper::importPlugin($this->events_map['delete']);

		// Iterate the items to delete each one.
		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if ($this->canDelete($table))
				{
					$context = $this->option . '.' . $this->name;

					// Trigger the before delete event.
					$result = $dispatcher->trigger($this->event_before_delete, array($context, $table));

					if (in_array(false, $result, true))
					{
						$this->setError($table->getError());
						return false;
					}
					
					// First delete entries still connected to this group
					$db = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->delete($db->quoteName('#__badminton_group_members'))
						->where($db->quoteName('groupid') . '=' . $db->quote($pk));
					$db->setQuery($query)->execute();

					if (!$table->delete($pk))
					{
						$this->setError($table->getError());
						return false;
					}
					else
					{
						// Remove the user if possible
						$userId = isset($table->user_id) ? $table->user_id : 0;
						if ($userId > 0) {
							$user = JFactory::getUser($userId);
							if (!$user->delete())
							{
								$this->setError($user->getError());
								return false;
							}
						}
					}

					// Trigger the after event.
					$dispatcher->trigger($this->event_after_delete, array($context, $table));
				}
				else
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$error = $this->getError();

					if ($error)
					{
						JLog::add($error, JLog::WARNING, 'jerror');
						return false;
					}
					else
					{
						JLog::add(JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), JLog::WARNING, 'jerror');
						return false;
					}
				}
			}
			else
			{
				$this->setError($table->getError());
				return false;
			}
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
	 */
	protected function canDelete($record) {
		$user = JFactory::getUser();
		$ownerid = isset($record->ownerid) ? $record->ownerid : 0;
		if ($user->authorise('group.delete', 'com_badminton'))
			return true;
		if ($user->authorise('group.deleteown', 'com_badminton'))
			return $this->isOwner($user->id, $ownerid);
		return false;
	}
	
	/**
	 * Method to test whether a record can be edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
	 */
	protected function canEditState($record) {
		$user = JFactory::getUser();
		$owner = isset($record->ownerid) ? $record->ownerid : 0;
		if ($user->authorise('group.edit', 'com_badminton'))
			return true;
		if ($user->authorise('group.editown', 'com_badminton'))
			return $this->isOwner($user->id, $ownerid);
		return false;
	}

}