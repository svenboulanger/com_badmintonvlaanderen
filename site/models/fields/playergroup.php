<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldPlayerGroup extends JFormField {
	
	protected $type = 'PlayerGroup';
	
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
		$groups = $this->getUserGroups();
		
		// Get all current user groups
		$user = JFactory::getUser();
		$usergroups = JUserHelper::getUserGroups($user->get('id'));
		
		// Only show the groups that are less than the current user
		foreach ($groups as $key => $value)
		{
			// Disable super user group
			if ($value->value != 8)
			{
				$tmp = new stdClass();
				$tmp->text = $value->text;
				$tmp->value = $value->value;
				$tmp->level = $value->level;
				$options[] = $tmp;
			}
		}
		return $options;
	}
	
	/*
	 * Gets the input
	 *
	 * @return html		The input html
	 */
	public function getInput() {
		
		$disabled     = $this->disabled ? ' disabled' : '';
		
		// Create an unordered list
		$html[] = "<ul class=\"list-group\">";
		
		// Go over all the options
		$options = $this->getOptions();
		$lastindent = 0;
		foreach ($options as $key => $option) {
			
			// Initialize some option attributes.
			if (isset($this->value) && !empty($this->value))
			{
				$value = !is_array($this->value) ? explode(',', $this->value) : $this->value;
				$checked = (in_array((string) $option->value, $value) ? ' checked' : '');
			} else {
				$value = array();
				$checked = '';
			}

			// Create indentation
			$style = "";
			if ($option->level > 0)
				$style = " style=\"padding-left:" . ($option->level * 5) . "%;\"";
			
			// Create a list item
			$html[] = '<li class="list-group-item" ' . $style . '><input type="checkbox" name="' . $this->name . '[]" value="' . $option->value . '" ' . $checked . $disabled . ' /> ' . $option->text . '</li>';
		}
		
		for ($i = 0; $i < $lastindent; $i++)
			$html[] = '</div>';

		$html[] = '</ul>';
		return implode($html);
	}
	
	/**
	 * Get a list of the user groups.
	 *
	 * @return	array
	 * @since	1.6
	 */
	protected function getUserGroups()
	{
		// Initialise variables.
		$db		= JFactory::getDBO();
		$query	= $db->getQuery(true)
			->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level, a.parent_id')
			->from('#__usergroups AS a')
			->leftJoin('`#__usergroups` AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id')
			->order('a.lft ASC');

		$db->setQuery($query);
		$options = $db->loadObjectList();

		return $options;
	}
}