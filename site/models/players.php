<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonModelPlayers extends JModelList
{
	protected $players;
	
	/**
	 * Constructor
	 *
	 * @param	$config		Configuration data
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'name', 'vbl', 'vblid', 'clubid', 'street', 'postalcode', 'city',
				'email', 'home', 'mobile', 'birthdate', 'competitive', 'allowed', 'gender', 'group'
			);
		}
		parent::__construct($config);
	}
	
	/**
	 * Get the correct table
	 *
	 * @param	$name		The name of the table
	 * @param	$prefix		The prefix of the table
	 * @param	$options	Additional options
	 *
	 * @return JTable		The associated table
	 */
	public function getTable($name = 'Player', $prefix = 'BadmintonTable', $options = array()) {
		return JTable::getInstance($name, $prefix, $options);
	}
	
	/**
	 * Get the currently active query
	 *
	 * @return	JDatabaseQuery
	 */
	public function getCurrentQuery() {
		return $this->getListQuery();
	}
	
	/**
	 * Get a list by query
	 *
	 * @return JQuery	The query object that can select from the database
	 */
	protected function getListQuery() {
		
		// Get database query to build up the list
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('*')
			->from($db->quoteName('#__badminton_players', 'p'));

		// Filter like / search
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			
			$searchfields = array('lastname', 'firstname');
			if ($user->authorise('player.view', 'com_badminton'))
				$searchfields = array_merge($searchfields, array('email', 'vblid', 'clubid', 'postalcode', 'city', 'email'));
			
			$innerQuery = $db->getQuery(true);
			$innerQuery
				->select('*,CONCAT_WS(" ",' . implode(',', $searchfields) . ') as ' . $db->quoteName('search'))
				->from($db->quoteName('#__badminton_players'));
			$query = $db->getQuery(true);
			$query
				->select('*')
				->from($innerQuery, 'base');
			$possibilities = explode(' ', $search);
			$where = array();
			foreach ($possibilities as $key => $value)
				if (!empty($value))
					$where[] = $db->quoteName('search') . ' LIKE ' . $db->quote("%$value%");
			$query->where(implode(' AND ', $where));
		}
		
		// Filter competitive players
		$competitive = $this->getState('filter.competitive');
		if ($competitive != null)
			$query->where('competitive=' . $db->quote($competitive ? 1 : 0));
		
		// Filter registered
		$registered = $this->getState('filter.registered');
		if ($registered == 1)
			$query->where('user_id=0&&(verification IS NULL || verification="")');
		else if ($registered == 2)
			$query->where('user_id=0&&verification IS NOT NULL&&verification!=""');
		else if ($registered == 3)
			$query->where('user_id>0');
		else if ($registered == 4)
			$query->where('properties LIKE ' . $db->quote("%\"request\":{%") . ' AND properties NOT LIKE ' . $db->quote("%\"request\":{}%"));
		
		// Filter gender
		$gender = $this->getState('filter.gender');
		if ($gender != null)
			$query->where('gender=' . $db->quote($gender));
		
		// Filter allowed state
		if ($user->authorise('player.allow', 'com_badminton')) {
			$allowed = $this->getState('filter.allowed');
			if ($allowed != null)
				$query->where('allowed=' . $db->quote($allowed ? 1 : 0));
		}
		else
			$query->where('allowed=1');
		
		// Filter groups
		$group = $this->getState('filter.group');
		if ($group != null)
		{
			$query->leftJoin($db->quoteName('#__badminton_group_members', 'gm') . ' ON ' . $db->quoteName('gm.playerid') . '=' . $db->quoteName('p.id'));
			$query->where($db->quoteName('gm.groupid') . '=' . $db->quote($group));
		}

		// Add the list ordering clause
		$orderCol = $this->state->get('list.order', 'name');
		$orderDirn = $this->state->get('list.direction', 'asc');
		if ($orderCol == 'name')
			$orderCol = "CONCAT(" . $db->quoteName('lastname') . "," . $db->quoteName('firstname') . ")";
		else if ($orderCol == 'street')
			$orderCol = "CONCAT(" . $db->quoteName('address1') . "," . $db->quoteName('address2') . ")";
		else
			$orderCol = $db->escape($orderCol);
		$query->order($orderCol . ' ' . $db->escape($orderDirn));

		return $query;
	}
}