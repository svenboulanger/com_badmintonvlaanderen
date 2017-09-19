<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonModelGroups extends JModelList
{
	protected $groups;
	
	/**
	 * Constructor
	 *
	 * @param	$config		Configuration data
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'name'
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
	public function getTable($name = 'Group', $prefix = 'BadmintonTable', $options = array()) {
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
			->select(implode(', ', array('g.*',
				'(SELECT count(*) from ' . $db->quoteName('#__badminton_group_members', 'm') . ' where m.groupid=g.id) as members',
				'CONCAT_WS(" ",u.lastname,u.firstname) as ' . $db->quoteName('owner'))))
			->leftJoin($db->quoteName('#__badminton_players', 'u') . ' ON g.ownerid=u.id')
			->from($db->quoteName('#__badminton_groups', 'g'));

		// Filter like / search
		$search = $this->getState('filter.search');
		if (!empty($search)) {
			$possibilities = explode(' ', $search);
			$where = array();
			foreach ($possibilities as $key => $value)
				if (!empty($value))
					$where[] = $db->quoteName('name') . ' LIKE ' . $db->quote("%$value%");
			$query->where(implode(' AND ', $where));
		}
		
		// Add the list ordering clause
		$orderCol = $this->state->get('list.order', 'name');
		$orderDirn = $this->state->get('list.direction', 'asc');
		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
		
		// Display the query
		// $app->enqueueMessage($query);
		
		return $query;
	}
}