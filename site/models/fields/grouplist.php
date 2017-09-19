<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldGroupList extends JFormFieldList {
	
	protected $type = 'GroupList';
	
	public $ownedgroups = false;
	
	/*
	 * Generate a label for the input
	 *
	 * @return html		The html for a label
	 */
	public function getLabel() {
		return parent::getLabel();
	}
	
	/*
	 * Gets all the options
	 *
	 * @return array	An array of option objects
	 */
	public function getOptions() {
		$options = array();
		$db = JFactory::getDbo();
		$user = JFactory::getUser();

		// Add a default option
		$tmp = new StdClass();
		$tmp->value = '';
		$tmp->text = JText::_('COM_BADMINTON_SEARCH_SELECT_GROUP');
		$options[] = $tmp;
		
		if (!$user->authorise('group.view', 'com_badminton'))
		{
			if ($user->authorise('group.editown', 'com_badminton')) // Can edit own groups, so can also view own groups
				$this->ownedgroups = true;
			else
				return $options;
		}
		
		// Create the query that will count the number of members
		$innerquery = $db->getQuery(true);
		$innerquery->select('COUNT(*)')
			->from($db->quoteName('#__badminton_group_members', 'm'))
			->where($db->quoteName('g.id') . '=' . $db->quoteName('m.groupid'));
			
		// Load all players from the database as options
		$query = $db->getQuery(true);
		$query->select('g.id,g.name,(' . $innerquery . ') as members')
			->from($db->quoteName('#__badminton_groups', 'g'))
			->order('name ASC');
		
		// Limit the query to the ones with ownership
		if ($this->ownedgroups)
		{
			// $query->select('p.user_id');
			$query->leftJoin($db->quoteName('#__badminton_players', 'p') . ' ON ' . $db->quoteName('p.id') . '=' . $db->quoteName('g.ownerid'));
			$query->where($db->quoteName('p.user_id') . '=' . $db->quote($user->id));
		}
			
		$list = $db->setQuery($query)->loadAssocList();
		foreach ($list as $i => $data)
		{
			$tmp = new stdClass();
			$tmp->value = $data['id'];
			$tmp->text = $data['name'];
			if (!empty($data['members']))
				$tmp->text .= ' (' . $data['members'] . ')';
			$options[] = $tmp;
		}
		return $options;
	}
}