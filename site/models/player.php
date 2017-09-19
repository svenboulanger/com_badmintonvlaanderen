<?php
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT . '/helpers/email.php');
require_once(JPATH_COMPONENT . '/helpers/user.php');
require_once(JPATH_COMPONENT . '/helpers/avatar.php');

class BadmintonModelPlayer extends JModelAdmin
{
	// Local variables
	protected $validImageTypes = array(
		"jpg" => "image/jpeg",
		"png" => "image/png",
		"gif" => "image/gif"
	);
	protected $uploaded = null;

	// The form field groups
	protected $form_fields = array('id', 'properties');
	protected $form_field_groups = array(
		'main' => array('avatar_image', 'firstname', 'lastname', 'birthdate', 'gender',
			'address1', 'address2', 'postalcode', 'city', 'home', 'mobile', 'email'),
		'administration' => array('vblid', 'clubid', 'allowed', 'competitive',
			'guardian', 'guardian2'),
		'userdata' => array('playergroups'),
		'payment' => array('paiddate', 'paidamount', 'paidyear')
	);
	
	/**
	 * Method to get a table object
	 *
	 * @param $type		The name of the table
	 * @param $prefix	The prefix of the name of the table
	 * @param $config	Configuration data
	 *
	 * @return JTable	The table
	 */
	public function getTable($type = 'Player', $prefix = 'BadmintonTable', $config = array()) {
		
		// Return an instance of the player table
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Get the form for a player
	 *
	 * @param 	$data
	 * @param	$loadData
	 *
	 * @return JForm	The form used to the data
	 */
	public function getForm($data = array(), $loadData = true) {

		// Initialize
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
	
		// Load form/player.xml
		$form = $this->loadForm('com_badminton.player', 'player', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) {
			$app->enqueueMessage('No form', 'error');
			return false;
		}

		// Add field paths for custom fields
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$form->addFieldPath(JPATH_COMPONENT . '/models/fields');

		// Allow/Block players
		if (!$user->authorise('player.allow', 'com_badminton'))
			$form->setFieldAttribute('allowed', 'disabled', 'true', 'main');
		
		// User groups
		if (!$user->authorise('player.editgroup', 'com_badminton'))
			$form->removeGroup('userdata');
		else {
			$id = $app->input->get('id', 0, 'INT');
			if ($id <= 0)
				$form->removeGroup('userdata');
			elseif (!BadmintonUserHelper::getUserId($id))
				$form->removeGroup('userdata');
		}
		
		// Payment data
		if (!$user->authorise('player.payment', 'com_badminton')) {
			if (!$user->authorise('player.viewpayment', 'com_badminton'))
				$form->removeGroup('payment');
			else {
				foreach ($form->getGroup('payment') as $field)
					$form->setFieldAttribute($field->getAttribute('name'), 'disabled', 'true', 'payment');
			}
		}

		return $form;
	}
		
	/**
	 * Save a player to the database
	 *
	 * @param array $data	The data to be saved
	 *
	 * @return boolean		True if succeeded, false if error
	 */
	public function save($data) {

		// Initialize
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		JPluginHelper::importPlugin('badminton');
		$dispatcher = JEventDispatcher::getInstance();

		// Check for a valid player
		if (BadmintonUserHelper::isValidPlayer($data) == false)
			return false;

		// Find accepted or denied changes
		$accepted = array();
		$denied = array();
		if (!empty($data['properties'])) {
			$properties = json_decode($data['properties'], true);
			if (isset($properties['accepted']))
				$accepted = $properties['accepted'];
			if (isset($properties['denied']))
				$denied = $properties['denied'];
			unset($properties['accepted'], $properties['denied']);
			$data['properties'] = json_encode($properties, true);
		}

		// Update some values if necessary
		if (!empty($data['id'])) {
			if (isset($player['vblid']))
				$data['vbl_urlid'] = '';
		} else {
			// Initially allow new players
			if (!isset($data['allowed']))
				$data['allowed'] = true;
		}

		// Set avatar if accepted
		if (isset($accepted['avatar']))
			$data['avatar'] = $accepted['avatar'];
		
		// Dispatch an event
		$results = $dispatcher->trigger('onPlayerBeforeSave', array( &$data ));
		if (in_array(false, $results))
			return false;
		
		// Check for uploaded avatars
		if ($this->uploaded === null) {
			$result = BadmintonAvatarHelper::checkAvatarUpload($this->validImageTypes);
			if ($result !== false && $result['success'] === false) {
				$app->enqueueMessage($result['error'], 'error');
				unset($data['avatar']);
			} elseif (!empty($result['avatar'])) {
				$data['avatar'] = $result['avatar'];
				$this->uploaded = true; // Make sure we can only upload the file once
			}
		}

		// Save the data
		$success = parent::save($data);
		if ($success == true)
		{
			// Find the ID of the saved player
			$id = $this->getState($this->getName() . '.id', 0);
			$isNew = $this->getState($this->getName() . '.new', false);
			$table = $this->getTable();
			if ($id > 0 && $table->load($id))
			{
				// Automatically invite new players
				if ($isNew && $table->allowed > 0) {
					if ($this->canInvite($table))
						BadmintonEmailHelper::invitePlayer($table);
				}

				// Get the user groups from the passed data if any
				if ($table->user_id > 0)
				{
					// Make user database consistent with the player
					BadmintonUserHelper::updateUserFromPlayer($table->user_id, JArrayHelper::getValue($data, 'playergroups', array(), 'ARRAY'));
					BadmintonEmailHelper::updatePlayer($table, $accepted, $denied);
				}
				
				$dispatcher->trigger('onPlayerAfterSave', array( $data ));
			}
		}
		return $success;
	}
	
	/**
	 * Register a player in the database
	 * 
	 * @param array $data	The data to be saved
	 *
	 * @return boolean		True if succeeded, false if error
	 */
	public function register($data) {
		
		// Initialize
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		$table = $this->getTable();
		$parameters = JComponentHelper::getParams('com_badminton');
		JPluginHelper::importPlugin('badminton');
		$dispatcher = JEventDispatcher::getInstance();
		
		// Check for a valid player
		if (BadmintonUserHelper::isValidPlayer($data) == false)
			return false;

		// Initially allow new players
		if (!isset($data['allowed']))
			$data['allowed'] = true;
		
		// Dispatch an event
		$results = $dispatcher->trigger('onPlayerBeforeSave', array( &$data ));
		if (in_array(false, $results))
			return false;
		
		// Check for uploaded avatars
		if ($this->uploaded === null) {
			$result = BadmintonAvatarHelper::checkAvatarUpload($this->validImageTypes);
			if (!$result['success']) {
				$app->enqueueMessage($result['error'], 'error');
				unset($data['avatar']);
			} else {
				$data['avatar'] = $result['avatar'];
				$this->uploaded = true; // Make sure we can only upload the file once
			}
		}

		// Save the data
		$success = parent::save($data);
		if ($success == true)
		{
			// Find the ID of the saved player
			$id = $this->getState($this->getName() . '.id', 0);
			if ($id > 0 && $table->load($id))
			{
				// Send the secretary an email for the registration
				BadmintonEmailHelper::updateRegistration($table);
				
				// Send an email to the registered player
				/* if ($this->canInvite($table))
					BadmintonEmailHelper::invitePlayer($table);
				else
					$app->enqueueMessage($this->getError(), 'warning'); */
				$dispatcher->trigger('onPlayerAfterSave', array( $data ));
			}
		}
		return $success;
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
						
						// Remove entries from the group
						$db = JFactory::getDbo();
						$query = $db->getQuery(true);
						$query->delete($db->quoteName('#__badminton_group_members'))
							->where($db->quoteName('playerid') . '=' . $db->quote($table->id));
						$db->setQuery($query)->execute();
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
	 * Method to get the data that should be injected in the form
	 *
	 * @return mixed	The data to be used in the form
	 */
	public function loadFormData() {

		// Get the data from the user state or else load it from the database
		$app = JFactory::getApplication();
		$data = $app->getUserState("$this->option.edit.player.data", array());
		if (empty($data))
			$data = $this->getItem();
		else
			return $data;

		// Format birthdate
		if (isset($data->birthdate))
			$data->birthdate = (new DateTime($data->birthdate))->format('Y-m-d');
		if (isset($data->paiddate))
			$data->paiddate = (new DateTime($data->paiddate))->format('Y-m-d');
		
		// Load the user id
		if (isset($data->user_id))
		{
			$user_id = $data->get('user_id');
			if ($user_id > 0) {
				$group = JUserHelper::getUserGroups($user_id);
				$data->playergroups = $group;
			}
		}

		// Build the actual structure
		$item = new stdClass();
		foreach ($this->form_field_groups as $group => $fields) {
			foreach ($fields as $field) {
				if (isset($data->$field))
					$item->$group[$field] = $data->$field;
			}
		}
		foreach ($this->form_fields as $field) {
			if (isset($data->$field))
				$item->$field = $data->$field;
		}
		return $item; 
	}
	
	/*
	 * Decide whether a player can be allowed or not
	 *
	 * @param	mixed $record	A record with player data that will be allowed
	 *
	 * @return boolean			True if the player can be allowed
	 */
	protected function canAllow($record) {
		$user = JFactory::getUser();
		return $user->authorise('player.allow', $this->option);
	}
	
	/*
	 * Decide whether a player can be blocked or not
	 *
	 * @param	mixed $record	A record with player data that will be blocked
	 *
	 * @return boolean			True if the player can be allowed
	 */
	protected function canBlock($record) {
		$user = JFactory::getUser();
		$userId = isset($record->user_id) ? $record->user_id : 0;
		
		// If a user is linked, then restrict blocking if that user is an administrator or the user calling it
		if ($userId > 0) {
			
			// Get the user from the Joomla user list
			$target = JFactory::getUser($userId);
			
			// Never block super users
			if ($target->authorise('core.admin')) {
				$this->setError(JText::_('COM_BADMINTON_NO_BLOCK_ADMIN'));
				return false;
			}
			
			// Never block yourself
			else if ($target->id == $user->id) {
				$this->setError(JText::_('COM_BADMINTON_NO_BLOCK_SELF'));
				return false;
			}
		}
		return $user->authorise('player.allow', $this->option);
	}
	
	/*
	 * Decide whether a player can be made competitive or not
	 *
	 * @param	mixed $record	A record with the player data that will be made competitive
	 * 
	 * @return boolean			True if the player can be made competitive
	 */
	protected function canMakeCompetitive($record) {
		return true;
	}
	
	/*
	 * Decide whether a player can be made recreative or not
	 *
	 * @param	mixed $record	A record with the player data that will be made recreative
	 * 
	 * @return boolean			True if the player can be made recreative
	 */
	protected function canMakeRecreative($record) {
		return true;
	}
	
	/*
	 * Decide whether a player can be invited for registration or not
	 *
	 * @param	mixed	$record		A record with player data
	 *
	 * @return boolean		True if the player can be invited
	 */
	protected function canInvite($record) {
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		
		// Check if the user is allowed
		if (isset($record->allowed) && $record->allowed == 0)
			return false;
		
		// Check if has invite rights
		if (!$user->authorise('player.invite', $this->option))
			return false;
		
		// Throw error if no email is given or email is invalid
		if (!isset($record->email) || !filter_var($record->email, FILTER_VALIDATE_EMAIL))
		{
			$this->setError(JText::sprintf('COM_BADMINTON_INVALID_EMAIL', $record->firstname . ' ' . $record->lastname));
			return false;
		}
		
		// Check if a user is already associated with this account
		if (isset($record->user_id) && $record->user_id > 0)
		{
			$this->setError(JText::sprintf('COM_BADMINTON_PLAYER_ALREADY_REGISTERED', $record->firstname . ' ' . $record->lastname));
			return false;
		}
		
		// Passed
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
		$userId = isset($record->user_id) ? $record->user_id : 0;
		
		// If a user is linked, then restrict blocking if that user is an administrator or the user calling it
		if ($userId > 0) {
			
			// Get the user from the Joomla user list
			$target = JFactory::getUser($userId);
			
			// Never block super users
			if ($target->authorise('core.admin')) {
				$this->setError(JText::_('COM_BADMINTON_NO_DELETE_ADMIN'));
				return false;
			}
			
			// Never block yourself
			else if ($target->id == $user->id) {
				$this->setError(JText::_('COM_BADMINTON_NO_DELETE_SELF'));
				return false;
			}
		}
		return $user->authorise('player.delete', 'com_badminton');
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
		return $user->authorise('player.edit', 'com_badminton') || $user->authorise('player.payment', 'com_badminton');
	}
	
	/**
	 * Method to check if a user can edit a record
	 *
	 */
	protected function canChangeGroup($group) {
		$user = JFactory::getUser();
		
		if ($user->authorise('group.edit', 'com_badminton'))
			return true;
		
		// Check if it is the own group
		if ($user->authorise('group.editown', 'com_badminton'))
		{
			// Find out if the user has ownership
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('1')
				->from($db->quoteName('#__badminton_players'))
				->where($db->quoteName('id') . '=' . $db->quote($group->ownerid)
					. ' AND '
					. $db->quoteName('user_id') . '=' . $db->quote($user->id));
			$result = $db->setQuery('SELECT EXISTS(' . $query . ')')->loadResult();
			if ($result)
				return true;
		}
		return false;
	}

	/**
	 * Method to allow one or more players.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function allow(&$pkg) {
		
		// Initialize
		$table = $this->getTable();
		$app = JFactory::getApplication();
		$parameters = JComponentHelper::getParams('com_badminton');
		$config = JFactory::getConfig();
		$sender = array( 
			$config->get('mailfrom'),
			$config->get('fromname') 
		);
		JPluginHelper::importPlugin('badminton');
		$dispatcher = JEventDispatcher::getInstance();

		// Iterate over all identifiers
		foreach ($pkg as $i => $pk) {
			
			// Load the player
			if ($table->load($pk)) {
				if ($this->canAllow($table)) {
				
					// Dispatch event before allowing the player
					$results = $dispatcher->trigger('onPlayerBeforeAllow', array( $table ));
					if (in_array(false, $results))
					{
						unset($pkg[$i]);
						return false;
					}
					
					// Allow the real user as well
					if ($table->user_id > 0) {
						$user = JFactory::getUser($table->user_id);
						$user->block = 0;
						$user->save();
					}
					
					// Store local allowed variable
					$table->bind(array("allowed" => 1));
					$table->store();
					
					// If the user is not invited yet, invite him
					if ($table->user_id == 0)
					{
						// Not yet a user associated with it
						if (!$table->verification)
						{
							if ($this->canInvite($table))
								BadmintonEmailHelper::invitePlayer($table, $sender, $parameters);
						}
					}
					
					// Dispatch event after allowing the player
					$dispatcher->trigger('onPlayerAfterAllow', array( $table ));
				} else {
					
					// Remove id from list (unsuccessful)
					unset($pkg[$i]);
					$error = $this->getError();
					if ($error) {
						$app->enqueueMessage($error, 'warning');
						return false;
					}
				}
			}
		}
		
		// Clear the components cache
		$this->cleanCache();
		return true;
	}
	
	/**
	 * Method to allow one or more players.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function block(&$pkg) {
		
		// Initialize
		$table = $this->getTable();
		$app = JFactory::getApplication();
		JPluginHelper::importPlugin('badminton');
		$dispatcher = JEventDispatcher::getInstance();

		// Iterate over all identifiers
		foreach ($pkg as $i => $pk) {
			
			// Load the player
			if ($table->load($pk)) {
				if ($this->canBlock($table)) {
				
					// Dispatch event before blocking the player
					$results = $dispatcher->trigger('onPlayerBeforeBlock', array( $table ));
					if (in_array(false, $results))
					{
						unset($pkg[$i]);
						return false;
					}
					
					// Block the user as well if it exists
					if ($table->user_id > 0) {
						$user = JFactory::getUser($table->user_id);
						$user->block = 1;
						$user->save();
					}
					
					// Block player
					$table->bind(array("allowed" => 0));
					$table->store();
					
					$dispatcher->trigger('onPlayerAfterBlock', array( $table ));
				} else {

					// Remove id from list (unsuccessful)
					unset($pkg[$i]);
					$error = $this->getError();
					if ($error) {
						$app->enqueueMessage($error, 'warning');
						return false;
					}
				}
			}
		}
		
		// Clear the components cache
		$this->cleanCache();
		return true;
	}
	
	/**
	 * Method to allow one or more players.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 */
	public function invite(&$pkg) {
		
		// Initialize
		$table = $this->getTable();
		$app = JFactory::getApplication();
		$config = JFactory::getConfig();
		$sender = array( 
			$config->get('mailfrom'),
			$config->get('fromname') 
		);
		
		// Retrieve the body of the email
		$parameters = JComponentHelper::getParams('com_badminton');
		
		// Iterate over all identifiers
		foreach ($pkg as $i => $pk) {
			
			// Load the player
			if ($table->load($pk)) {
				
				if ($this->canInvite($table))
					BadmintonEmailHelper::invitePlayer($table, $sender, $parameters);
			}
		}
		
		// Clear the components cache
		$this->cleanCache();
		return true;
	}

	/**
	 * Method to make on or more players competitive
	 *
	 * @param	array &$pkg	An array of record primary keys
	 *
	 * @return	boolean	True if successful, false if an error occurs
	 */
	public function competitive(&$pkg) {
		
		// Initialize
		$table = $this->getTable();
		$app = JFactory::getApplication();

		// Iterate over all identifiers
		foreach ($pkg as $i => $pk) {
			
			// Load the player
			if ($table->load($pk)) {
				if ($this->canMakeCompetitive($table)) {
					
					// Block player
					$table->bind(array("competitive" => 1));
					$table->store();
				} else {
					
					// Remove id from list (unsuccessful)
					unset($pkg[$i]);
					$error = $this->getError();
					if ($error) {
						$app->enqueueMessage($error, 'warning');
						return false;
					}
				}
			}
		}
		
		// Clear the components cache
		$this->cleanCache();
		return true;
	}
	
	/**
	 * Method to make on or more players competitive
	 *
	 * @param	array &$pkg	An array of record primary keys
	 *
	 * @return	boolean	True if successful, false if an error occurs
	 */
	public function recreative(&$pkg) {
		
		// Initialize
		$table = $this->getTable();
		$app = JFactory::getApplication();

		// Iterate over all identifiers
		foreach ($pkg as $i => $pk) {
			
			// Load the player
			if ($table->load($pk)) {
				if ($this->canMakeRecreative($table)) {
					
					// Block player
					$table->bind(array("competitive" => 0));
					$table->store();
				} else {
					
					// Remove id from list (unsuccessful)
					unset($pkg[$i]);
					$error = $this->getError();
					if ($error) {
						$app->enqueueMessage($error, 'warning');
						return false;
					}
				}
			}
		}
		
		// Clear the components cache
		$this->cleanCache();
		return true;
	}

	/**
	 * Group players in a group
	 *
	 * @param	array &$pkg	An array of record primary keys
	 * @param	int $gid	The group id
	 */
	public function groupIn(&$pkg, $gid) {
		// Initialize
		$table = $this->getTable('Group');
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		JPluginHelper::importPlugin('badminton');
		$dispatcher = JEventDispatcher::getInstance();
		
		// Load the group from the table
		if ($table->load($gid))
		{
			if ($this->canChangeGroup($table))
			{
				// Update the table
				foreach ($pkg as $i => $pk)
				{
					$results = $dispatcher->trigger('onPlayerBeforeGroup', array($table, $i));
					if (in_array(false, $results))
					{
						unset($pkg[$i]);
					}
					else
					{
						// First find out if the player is already assigned to this group (no duplicates allowed)
						$query = $db->getQuery(true);
						$query->select('1')
							->from($db->quoteName('#__badminton_group_members'))
							->where($db->quoteName('playerid') . '=' . $db->quote($pk) . ' AND ' . $db->quoteName('groupid') . '=' . $db->quote($gid));
						$exists = $db->setQuery('SELECT EXISTS(' . $query . ')')->loadResult();
						if (!$exists)
						{
							$columns = array('playerid', 'groupid');
							$values = array($db->quote($pk), $db->quote($gid));
							
							$query = $db->getQuery(true);
							$query->insert($db->quoteName('#__badminton_group_members'))
								->columns(implode(',', $columns))
								->values(implode(',', $values));
							$db->setQuery($query)->execute();
							
							$dispatcher->trigger('onPlayerAfterGroup', array($table, $i));
						}
						else
						{
							// Failed
							$warning = JText::_('COM_BADMINTON_PLAYERS_MEMBER_EXISTS');
							$app->enqueueMessage($warning, 'warning');
							unset($pkg[$i]);
						}
					}
				}
			}
			else
			{
				// Remove id from list (unsuccessful)
				foreach ($pkg as $i => $pk)
					unset($pkg[$i]);
				$error = $this->getError();
				if ($error) {
					$app->enqueueMessage($error, 'warning');
					return false;
				}
			}
		}
		
		// Clear the components cache
		$this->cleanCache();
		return true;
	}
	
	/**
	 * Group players in a group
	 *
	 * @param	array &$pkg	An array of record primary keys
	 * @param	int $gid	The group id
	 */
	public function ungroupFrom(&$pkg, $gid) {
		// Initialize
		$table = $this->getTable('Group');
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		JPluginHelper::importPlugin('badminton');
		$dispatcher = JEventDispatcher::getInstance();
		
		// Load the group from the table
		if ($table->load($gid))
		{
			if ($this->canChangeGroup($table))
			{
				// Update the table
				foreach ($pkg as $i => $pk)
				{
					$results = $dispatcher->trigger('onPlayerBeforeUngroup', array($table, $i));
					if (in_array(false, $results))
					{
						unset($pkg[$i]);
					}
					else
					{
						$condition = array(
							$db->quoteName('playerid') . '=' . $db->quote($pk),
							$db->quoteName('groupid') . '=' . $db->quote($gid));
						
						// First find out if the player is already assigned to this group (no duplicates allowed)
						$query = $db->getQuery(true);
						$query->select('1')
							->from($db->quoteName('#__badminton_group_members'))
							->where($db->quoteName('playerid') . '=' . $db->quote($pk) . ' AND ' . $db->quoteName('groupid') . '=' . $db->quote($gid));
						$exists = $db->setQuery('SELECT EXISTS(' . $query . ')')->loadResult();
						if ($exists)
						{

							$query = $db->getQuery(true);
							$query->delete($db->quoteName('#__badminton_group_members'))->where($condition);
							$db->setQuery($query)->execute();
							
							$dispatcher->trigger('onPlayerAfterUngroup', array($table, $i));
						}
						else
						{
							// Failed
							$warning = JText::_('COM_BADMINTON_PLAYERS_MEMBER_NOTEXISTS');
							$app->enqueueMessage($warning, 'warning');
							unset($pkg[$i]);
						}
					}
				}
			}
			else
			{
				// Remove id from list (unsuccessful)
				foreach ($pkg as $i => $pk)
					unset($pkg[$i]);
				$error = $this->getError();
				if ($error) {
					$app->enqueueMessage($error, 'warning');
					return false;
				}
			}
		}
		
		// Clear the components cache
		$this->cleanCache();
		return true;
	}
}