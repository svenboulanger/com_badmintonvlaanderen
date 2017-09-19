<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');
require_once(JRoute::_("media/com_badminton/php/spreadsheet/SpreadsheetReader.php"));

class BadmintonViewGroups extends JViewLegacy
{
	// Constants
	protected $vbl_url = 'http://badmintonvlaanderen.toernooi.nl/profile/overview.aspx?id=';
	protected $vbl_img = 'media/com_badminton/images/searchVbl.png';
	protected $playerid = null;
	
	/**
	 * Display the players view
	 *
	 * @param $tpl		The name of the template
	 */
	public function display($tpl = null) {
		
		// Check access levels
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$this->canViewAll = $user->authorise('group.view', 'com_badminton') || $user->authorise('group.edit', 'com_badminton');
		$this->canViewOwn = $user->authorise('group.editown', 'com_badminton');
		$this->canAdd = $user->authorise('group.add', 'com_badminton') || $user->authorise('group.addown', 'com_badminton');
		$this->canEdit = $user->authorise('group.edit', 'com_badminton');
		$this->canEditOwn = $user->authorise('group.editown', 'com_badminton');
		$this->canDelete = $user->authorise('group.delete', 'com_badminton') || $user->authorise('group.deleteown', 'com_badminton');
		$this->playerid = $this->getPlayerId($user);
		
		if (!$this->canViewOwn && !$this->canViewAll)
			return false;

		// Get application data
		$context = "badminton.list.admin.group";
		
		// Set document
		$this->setDocument();
		
		// Get data from the model
		$input = JFactory::getApplication()->input;
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->listOrder = $this->state->get('list.order', 'name');
		$this->listDirection = $this->state->get('list.direction', 'asc');
		$this->listLimit = $this->state->get('list.limit');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Fetch column data
		$input = $app->input;
		$this->columns = array('name' => 1, 'members' => 1, 'owner' => 1);
		
		// Override if new values are here
		$this->columns = array_replace($this->columns, $app->getUserState("$context.groups.list", array()));
		foreach ($this->columns as $k => $v)
			$this->columns[$k] = $app->input->get($k, $this->columns[$k], 'INT');
		$app->setUserState("$context.groups.list", $this->columns);
		
		// Display errors
		if (count($errors = $this->get('Errors'))) {
			$app->enqueueMessage(implode('<br />', $errors), 'error');
			return false;
		}
		
		// Set the toolbar
		parent::display($tpl);
	}
	
	/**
	 * Set the title of the document
	 */
	protected function setDocument() {
		
		// Initialize
		$user = JFactory::getUser();
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_BADMINTON_ADMINISTRATION'));
		
		// Add a script to ask for confirmation before deleting
		JHtml::_('jquery.framework');
		$document->addScript(JRoute::_('media/com_badminton/js/jquery-ui.min.js'));
		$document->addStyleSheet(JRoute::_('media/com_badminton/css/jquery-ui.min.css'));
		
		// Add declaration
		if ($this->canDelete) {
			$script = array(
				"jQuery(document).ready(function(){",
					"jQuery('#askdelete').dialog({",
						"autoOpen: false,",
						"width:400,",
						"buttons:[",
							"{text:\"" . JText::_('COM_BADMINTON_ITEMS_DELETE_OK') . "\",click:function(){Joomla.submitbutton('groups.delete');}},",
							"{text:\"" . JText::_('COM_BADMINTON_ITEMS_DELETE_CANCEL') . "\",click:function(){jQuery(\"#askdelete\").dialog(\"close\");}}",
						"]}",
					");});");
			$script[] = "function askDelete(){jQuery('#askdelete').dialog(\"open\");};";
			$document->addScriptDeclaration(implode($script));
		}

		// Add some styling
		$style = array();
		$style[] = ".fa-check-circle{color:#468847;}";
		$style[] = ".fa-times-circle{color:#cc0000;}";
		$style[] = ".fa-exclamation-triangle{color:#ff8800;}";
		$style[] = ".minimum{white-space:nowrap;width:1%;}";
		$document->addStyleDeclaration(implode($style));
	}
	
	/*
	 * Create the html for a toolbar
	 *
	 * @return html	The html
	 */
	protected function createToolbar() {
		// Generate the HTML
		$html = array('<div class="row-fluid">');
		
		// Display add button
		if ($this->canAdd)
			$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-plus-circle"></i> ' . JText::_('COM_BADMINTON_MANAGER_GROUPS_NEW'), array(
				"onclick" => "Joomla.submitbutton('group.add');",
				"class" => "btn btn-success"
			));
			
		// Display edit button
		if ($this->canEdit || $this->canEditOwn)
			$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-pencil"></i> ' . JText::_('COM_BADMINTON_MANAGER_GROUPS_EDIT'), array(
				"onclick" => "Joomla.submitbutton('group.edit')",
				"class" => "btn btn-warning",
			));
		
		// Remove player
		if ($this->canDelete)
			$html[] = JHtml::link("javascript:void(0);", JText::_('COM_BADMINTON_MANAGER_GROUPS_DELETE'), array(
				"onclick" => "askDelete()",
				"class" => "btn btn-danger"
			));

		$html[] = '</div>';
		return implode(' ', $html);
	}
	
	/*
	 * Create the html for a selection of columns
	 *
	 * @return	html	The html
	 */
	protected function createColumnSelect() {
		$html = array();
		foreach ($this->columns as $name => $visible)
		{
			// Calculate the style for the button
			$attr = array("class" => "label " . ($visible ? "label-primary" : "label-default"));
			$link = "index.php?option=com_badminton&view=players&$name=" . ($visible ? '0' : '1');
			$html[] = JHtml::link($link, JText::_("COM_BADMINTON_GROUPS_" . strtoupper($name)), $attr);
		}
		return implode(' ', $html);
	}
	
	/**
	 * Show a title for a player list
	 *
	 * @return string		The html for a row
	 */
	protected function showTitle() {
		
		// Get user and ordering data
		$this->fields = 2;

		// Create a row
		$html = array();
		$html[] = "<tr>";
		$html[] = '<th width="20px">' . JText::_('COM_BADMINTON_NUM') . '</th>';
		$html[] = '<th width="30px">' . JHtml::_('grid.checkall') . '</th>';
		
		// Create an ordering symbol
		if ($this->listDirection == 'asc')
			$style = '<i class="fa fa-arrow-up"></i>';
		else if ($this->listDirection == 'desc')
			$style = '<i class="fa fa-arrow-down"></i>';
		else
			$style = '';

		// Add columns
		$html[] = $this->addCell('name', $this->gridsort('COM_BADMINTON_GROUPS_NAME', 'name', $this->listDirection, $this->listOrder),  false, 'th');
		$html[] = $this->addCell('members', $this->gridsort('COM_BADMINTON_GROUPS_MEMBERS', 'members', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('owner', $this->gridsort('COM_BADMINTON_GROUPS_OWNER', 'owner', $this->listDirection, $this->listOrder), false, 'th');
		
		$html[] = '</tr>';
		return implode($html);
	}
	
	/**
	 * Method to sort a column in a grid - Taken from Joomla 3.x and changed
	 *
	 * @param   string  $title          The link title
	 * @param   string  $order          The order field for the column
	 * @param   string  $direction      The current direction
	 * @param   string  $selected       The selected ordering
	 * @param   string  $task           An optional task override
	 * @param   string  $new_direction  An optional direction for the new column
	 * @param   string  $tip            An optional text shown as tooltip title instead of $title
	 *
	 * @return  string
	 *
	 * @since   1.5
	 */
	protected function gridsort($title, $order, $direction = 'asc', $selected = '', $task = null, $new_direction = 'asc', $tip = '') {
		
		// Init
		JHtml::_('bootstrap.tooltip');
		$direction = strtolower($direction);
		$icon = array('asc' => 'sort-asc', 'desc' => 'sort-desc');
		$index = (int) ($direction == 'desc');
		if ($order == $selected) {
			$arrow = ' <i class="fa fa-' . $icon[$direction] . '"></i>';
			$direction = ($direction == 'desc') ? 'asc' : 'desc';
		}
		else {
			$arrow = '';
			$direction = $new_direction;
		}
		$attr = array(
			'onclick' => "orderBy('$order','$direction','$task');return false;",
			'class' => 'hasTooltip',
			'title' => JHtml::tooltipText(!empty($tip) ? $tip : $title, 'JGLOBAL_CLICK_TO_SORT_THIS_COLUMN'));
		$html = JHtml::link('#', JText::_($title) . $arrow, $attr);
		return $html;
	}
	
	/**
	 * Show a row for a player
	 *
	 * @param	$i		The index
	 * @param	$row	The data for the row
	 *
	 * @return string		The html for a row
	 */
	protected function showGroup($i, $row) {

		if (!$this->canViewAll)
		{
			if (!$this->canViewOwn)
				return '';
			else if ($row->ownerid != $this->playerid)
				return '';
		}
	
		// Generate a row
		$html = array("<tr>");
		
		// Add offset and checkbox
		$html[] = "<td>" . $this->pagination->getRowOffset($i) . "</td>";
		$html[] = "<td>" . JHtml::_('grid.id', $i, $row->id) . "</td>";
		
		// name
		$task = 'view';
		if ($this->canEdit)
			$task = 'edit';
		if ($this->canEditOwn && ($row->ownerid === $this->playerid))
			$task = 'edit';
		$link = JRoute::_("index.php?option=com_badminton&task=group.$task&id={$row->id}");
		$attr = array('title' => JText::_('COM_BADMINTON_MANAGER_GROUP_EDIT'));
		$html[] = $this->addCell('name', JHtml::link($link, $row->name, $attr));

		// Others
		$html[] = $this->addCell('members', $row->members, true);
		$html[] = $this->addCell('owner', $row->owner, false);

		$html[] = "</tr>";
		return implode($html);
	}
	
	/*
	 * Add a new cell to a table
	 * @param 	$colname	The name of the column that needs to be added if possible
	 * @param	$content	The displayed content
	 * @param	$width		The width of the cell
	 * @param	$center		True if the contents need to be centered, default false
	 * @param	$tag		The tag (usually td or th), default td
	 *
	 * @return	html		The html for the cell
	 */ 
	protected function addCell($colname, $content, $center = false, $tag = 'td') {
		
		if (!isset($this->columns[$colname]))
			return '';
		if ($this->columns[$colname] == 0)
			return '';
		
		// Styling
		$html = "<$tag";
		$classes = array();
		$styles = array();

		// Centering and minimal width
		if ($center) {
			$classes[] = "text-center";
			$classes[] = "minimum";
		}
		
		// Count fields
		if ($tag === 'th' && $this->columns[$colname] > 0)
		{
			// Count the number of visible fields
			$this->fields++;
		}

		// Combine
		if (count($classes) > 0)
			$html .= " class=\"" . implode(' ', $classes) . "\"";
		if (count($styles) > 0)
			$html .= " style=\"" . implode('', $styles) . "\"";
		
		// Add content
		$html .= ">$content</$tag>";
		return $html;
	}
	
	/*
	 * Format a date from different possible inputs
	 *
	 * @param	$input		mixed		The input
	 * @param	$default	mixed		The default if not recognized
	 * @param	$fmt		string		The format for the date when recognized
	 * 
	 * @return	string					The formatted date
	 */
	protected function formatDate($input, $default = "", $fmt = "Y-m-d") {
		$db = JFactory::getDbo();
		$date = null;
		
		// Handle DateTime input
		if ($input instanceof DateTime)
		{
			if ($input->format('Y-m-d H:i:s') == $db->getNullDate())
				return '';
			else
				$date = $input->setTime(12, 0)->format($fmt);
		}
		
		// Handle JDate input
		else if ($input instanceof JDate)
		{
			if ($input->format('Y-m-d H:i:s') == $db->getNullDate())
				return '';
			else
				$date = $input->format($fmt);
		}
		
		// Handle string input
		if (is_string($input))
		{
			if ($input !== $db->getNullDate())
			{
				$dt = JFactory::getDate($input);
				if ($dt)
					$date = $dt->format($fmt);
			}
		}
		
		// Handle unrecognized
		if ($date == null)
			return $default;
		
		// Return date
		return $date;
	}
	
	/*
	 * Display a switch
	 *
	 * @param	int		$row	The row that is displayed
	 * @param	array	$states	The different states with information
	 * @cstate	int		$cstate	The current state
	 *
	 * @return	string
	 */
	protected function toggleSwitch($row, $states, $cstate) {
		// Find the next state
		$nextstate = ($cstate + 1) % count($states);
		
		// Add some default options
		if (!isset($states[0]['display']))
			$states[0]['display'] = '<i class="fa fa-times-circle"></i>';
		if (!isset($states[1]['display']))
			$states[1]['display'] = '<i class="fa fa-check-circle"></i>';
		
		// Get display information
		$display = $states[$cstate]['display'];
		$action = $states[$nextstate]['action'];
		$tip = JText::_($states[$nextstate]['tooltip']);
		$attr = array('class' => 'hasTooltip', 'onclick' => "return listItemTask('cb$row','$action')", 'data-original-title' => $tip);
		
		// Generate the HTML
		$html = JHtml::link('#', $display, $attr);
		return $html;
	}
	
	/**
	 * Load the player id
	 */
	protected function getPlayerId($user)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query
			->select('id')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('user_id') . '=' . $db->quote($user->id));
		return $db->setQuery($query)->loadResult();
	}
}