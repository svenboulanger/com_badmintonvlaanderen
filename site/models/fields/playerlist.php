<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldPlayerList extends JFormField {
	
	protected $type = 'PlayerList';

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
		
		// Load all players from the database as options
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id, CONCAT_WS(" ",lastname, firstname) as name')
			->from($db->quoteName('#__badminton_players'))
			->order('name ASC');
		$list = $db->setQuery($query)->loadAssocList();
		foreach ($list as $i => $data)
			$options[$i] = array('id' => $data['id'], 'name' => $data['name']);
		
		// Add default option
		$nooption = $this->getAttribute('nooption', null);
		if ($nooption == null)
			$nooption = 'COM_BADMINTON_PLAYER_NONE';
		$options[0] = array('id' => 0, 'name' => JText::_($nooption));
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
		$html[] = '<select id="' . $this->id . '" name="' . $this->name . "\"$disabled>";

		// Go over all the options
		$options = $this->getOptions();
		foreach ($options as $i => $option)
		{
			$key = $option['id'];
			$name = $option['name'];
			$selected = '';
			if (isset($this->value) && ($this->value == $key))
				$selected = ' selected="selected"';
			$html[] = "<option value=\"$key\"$selected>$name</option>";
		}
		$html[] = '</select>';
		return implode($html);
	}
}