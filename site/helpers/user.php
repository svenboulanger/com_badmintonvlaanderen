<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonUserHelper
{
		/**
	 * Update user parameters from the player parameters
	 *
	 * @param	int	$userId		The user ID
	 */
	public static function updateUserFromPlayer($userId, $groups = array()) {

		// Make sure the user stays in/out the superuser group
		$groups = array_diff($groups, array(8));
		$usergroups = JUserHelper::getUserGroups($userId);
		if (in_array(8, $usergroups))
			$groups[] = 8;
		
		// Get the player from the database
		$db = JFactory::getDbo();
		$db->setQuery($db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('user_id') . '=' . $db->quote($userId)));
		$player = $db->loadAssoc();
		
		// No player found linked to this ID, just skip
		if ($player === null)
			return;
		
		// Find the configuration parameters for com_badminton
		$parameters = JComponentHelper::getParams('com_badminton');
		$group = $parameters->get('competitivegroup', 0);
		
		// If the user is party of any group, then set competitive to true
		if ($groups !== false) {
			if ($group > 0)
			{
				$key = array_search($group, $groups);
				
				// Match the groups with competitivity
				if ($player['competitive'] > 0) {
					if ($key === false)
						$groups[] = $group;
				} else {
					if ($key !== false)
						unset($groups[$key]);
				}
			}
			
			// Set the user groups back
			// JUserHelper::setUserGroups($userId, $groups);
		}
		
		// Calculate the name
		$user = JFactory::getUser($userId);
		$user->name = $player['firstname'] . ' ' . $player['lastname'];
		$user->email = $player['email'];
		$user->block = $player['allowed'] > 0 ? 0 : 1;
		$user->save();
	}
	
	/**
	 * Update player data from the user data
	 *
	 * @param	int	$userId		The user ID
	 */
	public static function updatePlayerFromUser($userId) {
		$db = JFactory::getDbo();
		
		// Find the configuration parameters for com_badminton
		$parameters = JComponentHelper::getParams('com_badminton');
		$group = $parameters->get('competitivegroup');
		$values = array();
		
		// Get the user
		$user = JFactory::getUser($userId);
		$values[] = $db->quoteName('email') . '=' . $db->quote($user->email);
		$values[] = $db->quoteName('allowed') . '=' . $db->quote($user->block > 0 ? 0 : 1);
		
		// Get the user groups
		$groups = JUserHelper::getUserGroups($userId);
		if (in_array($group, $groups))
			$values[] = $db->quoteName('competitive') . '=1';
		else
			$values[] = $db->quoteName('competitive') . '=0';
		
		// Update the database
		$db = JFactory::getDbo();
		$db->setQuery($db->getQuery(true)
			->update($db->quoteName('#__badminton_players'))
			->set($values)
			->where($db->quoteName('user_id') . '=' . $db->quote($userId)));
		$db->execute();
	}

	/*
	 * Get the user associated with a player
	 * Returns false if the player is not associated with any player
	 *
	 * @param int $playerid		The ID of the player
	 *
	 * @return mixed			False if no user is linked, the ID of the user else
	 */
	public static function getUserId($playerid) {
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('user_id'))
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('id') . '=' . $db->quote($playerid));
		$db->setQuery($query);
		$user_id = $db->loadResult();
		if (!is_numeric($user_id) || $user_id <= 0)
			return false;
		return $user_id;
	}
	
	/*
	 * Check for invalid data
	 * - Identical CLUB ID is not allowed
	 * - Identical VBL ID is not allowed
	 * - Identical EMAIL is not allowed
	 *
	 * @param array $data		The player data
	 * @return bool				True if a valid player, false otherwise
	 */
	public static function isValidPlayer($data) {
		$db = JFactory::getDbo();
		
		// Find for duplicate values
		$check = array();
		$docheck = false;
		if (!empty($data['id']))
			$check[] = 'NOT ' . $db->quoteName('id') . '=' . $db->quote($data['id']);
		if (!empty($data['administration']['vblid']))
		{
			$check[] = $db->quoteName('vblid') . '=' . $db->quote($data['administration']['vblid']);
			$docheck = true;
		}
		if (!empty($data['administration']['clubid']))
		{
			$check[] = $db->quoteName('clubid') . '=' . $db->quote($data['administration']['clubid']);
			$docheck = true;
		}
		if (!empty($data['main']['email']))
		{
			$check[] = 'LOWER(' . $db->quoteName('email') . ')=' . $db->quote(strtolower($data['main']['email']));
			$docheck = true;
		}
		if ($docheck === true) {
			$query = $db->getQuery(true)->select('COUNT(*)')->from($db->quoteName('#__badminton_players'))->where($check);
			$db->setQuery($query);
			if ($db->loadResult() > 0) {
				$this->setError(JText::sprintf('COM_BADMINTON_PLAYERS_UPLOAD_DUPLICATE', $data['firstname'] . ' ' . $data['lastname']));
				return false;
			}
		}
		return true;
	}
}