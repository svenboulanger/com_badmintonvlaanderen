<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');
require_once(JRoute::_("media/com_badminton/php/spreadsheet/SpreadsheetReader.php"));

class BadmintonViewPlayers extends JViewLegacy
{
	// Constants
	protected $vbl_url = 'http://badmintonvlaanderen.toernooi.nl/profile/overview.aspx?id=';
	protected $vbl_img = 'media/com_badminton/images/searchVbl.png';
	
	/**
	 * Display the players view
	 *
	 * @param $tpl		The name of the template
	 */
	public function display($tpl = null) {
		
		// Check access levels
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$this->canAdd = $user->authorise('player.add', 'com_badminton');
		$this->canEdit = $user->authorise('player.edit', 'com_badminton') || $user->authorise('player.payment', 'com_badminton');
		$this->canView = $user->authorise('player.view', 'com_badminton') || $user->authorise('player.edit', 'com_badminton');
		$this->canDelete = $user->authorise('player.delete', 'com_badminton');
		$this->canInvite = $user->authorise('player.invite', 'com_badminton');
		$this->canAllow = $user->authorise('player.allow', 'com_badminton');
		$this->canPayment = $user->authorise('player.payment', 'com_badminton') || $user->authorise('player.viewpayment', 'com_badminton');
		$this->canGroupEdit = $user->authorise('group.edit', 'com_badminton');
		$this->canGroupEditOwn = $user->authorise('group.editown', 'com_badminton');
		$this->canGroupView = $user->authorise('group.view', 'com_badminton') || $this->canGroupEdit || $this->canGroupEditOwn;
		
		// No access if the player cannot view a list
		if (!$user->authorise('player.viewlist', 'com_badminton') && !$this->canView)
			return false;

		// Get application data
		$context = "badminton.list.admin.player";
		
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
		
		// Can the user see the group tools?
		$this->showgrouptool = $this->canGroupEdit || $this->canGroupEditOwn;
		$this->cgrouptool = $app->getUserStateFromRequest("$context.players.grouptool", "gid", 0, 'int');

		// Fetch column data
		$input = $app->input;
		if ($this->canView)
			$this->columns = array('name' => 1, 'vbl' => 1, 'vblid' => 0, 'clubid' => 0, 'street' => 0, 'postalcode' => 0, 'city' => 0, 'email' => 0, 'home' => 0, 'mobile' => 0, 'birthdate' => 0, 'gender' => 0, 'competitive' => 0, 'added' => 0, 'paiddate' => 0, 'paidamount' => 1, 'paidyear' => 1, 'allowed' => 1);
		else
			$this->columns = array('name' => 1, 'vbl' => 1, 'vblid' => 0);

		// Override if new values are here
		$this->stored = array_replace($this->columns, $app->getUserState("$context.players.list", array()));
		foreach ($this->columns as $k => $v)
			$this->columns[$k] = $app->input->get($k, $this->stored[$k], 'INT');
		$app->setUserState("$context.players.list", $this->columns);
		
		// Remove columns that should not be viewed
		if (!$this->canAllow) {
			unset($this->columns['allowed']);
			$this->filterForm->removeField('allowed', 'filter');
		}
		if (!$this->canPayment)
			unset($this->columns['paiddate'], $this->columns['paidamount'], $this->columns['paidyear']);
		if (!$this->canView)
			$this->filterForm->removeField('registered', 'filter');
		// if (!$this->canGroupView)
			// $this->filterForm->removeField('group', 'filter');

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
							"{text:\"" . JText::_('COM_BADMINTON_ITEMS_DELETE_OK') . "\",click:function(){Joomla.submitbutton('players.delete');}},",
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
		$style[] = "#listlimit_chzn{float:right;margin:20px 0;}";
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
			$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-plus-circle"></i> ' . JText::_('COM_BADMINTON_MANAGER_PLAYERS_NEW'), array(
				"onclick" => "Joomla.submitbutton('player.add');",
				"class" => "btn btn-success"
			));
			
		// Display edit button
		if ($this->canEdit)
			$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-pencil"></i> ' . JText::_('COM_BADMINTON_MANAGER_PLAYERS_EDIT'), array(
				"onclick" => "Joomla.submitbutton('player.edit')",
				"class" => "btn btn-warning",
			));
		
		// Remove player
		if ($this->canDelete)
			$html[] = JHtml::link("javascript:void(0);", JText::_('COM_BADMINTON_MANAGER_PLAYERS_DELETE'), array(
				"onclick" => "askDelete()",
				"class" => "btn btn-danger"
			));
			
		// Send invitation email
		if ($this->canInvite)
			$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-envelope"></i> ' . JText::_('COM_BADMINTON_MANAGER_PLAYERS_INVITE'), array(
				"onclick" => "Joomla.submitbutton('players.invite')",
				"class" => "btn btn-default"
			));
			
		// Block and allow
		if ($this->canAllow) {
			$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-check-circle"></i> ' . JText::_('COM_BADMINTON_MANAGER_PLAYERS_ALLOW'), array(
				"onclick" => "Joomla.submitbutton('players.allow')",
				"class" => "btn btn-default"));
			$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-times-circle"></i> ' . JText::_('COM_BADMINTON_MANAGER_PLAYERS_BLOCK'), array(
				"onclick" => "Joomla.submitbutton('players.block')",
				"class" => "btn btn-default"));
		}

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
			$html[] = JHtml::link($link, JText::_("COM_BADMINTON_PLAYERS_" . strtoupper($name)), $attr);
		}
		return implode(' ', $html);
	}
	
	/**
	 * Create the tools necessary to manage group members
	 */
	protected function createGroupManager() {
		$html = array();
		$formfield = new JFormFieldGroupList();
		if (!$this->showgrouptool)
			return '';
		if (!$this->canGroupEdit)
			$formfield->ownedgroups = true;
		$formfield->name = "gid";
		$formfield->value = $this->cgrouptool;
		
		// Create the tools for adding and removing from groups
		$html[] = $formfield->input;
		$html[] = "<a class=\"btn btn-success\" href=\"javascript:void(0);\" onclick=\"Joomla.submitbutton('players.groupIn');\"><i class='fa fa-plus-circle'></i> " . JText::_('COM_BADMINTON_MANAGER_GROUPMEMBER_ADD') . "</a>";
		$html[] = "<a class=\"btn btn-danger\" href=\"javascript:void(0);\" onclick=\"Joomla.submitbutton('players.ungroupFrom');\"><i class='fa fa-minus-circle'></i> " . JText::_('COM_BADMINTON_MANAGER_GROUPMEMBER_REMOVE') . "</a>";
		
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
		if ($this->canView || $this->showgrouptool)
			$html[] = '<th width="30px">' . JHtml::_('grid.checkall') . '</th>';
		
		// Create an ordering symbol
		if ($this->listDirection == 'asc')
			$style = '<i class="fa fa-arrow-up"></i>';
		else if ($this->listDirection == 'desc')
			$style = '<i class="fa fa-arrow-down"></i>';
		else
			$style = '';

		// Add columns
		$html[] = $this->addCell('name', $this->gridsort('COM_BADMINTON_PLAYERS_NAME', 'name', $this->listDirection, $this->listOrder),  false, 'th');
		$html[] = $this->addCell('vbl', JText::_('COM_BADMINTON_PLAYERS_VBL'), true, 'th', "50px");
		$html[] = $this->addCell('vblid', $this->gridsort('COM_BADMINTON_PLAYERS_VBLID', 'vblid', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('clubid', $this->gridsort('COM_BADMINTON_PLAYERS_CLUBID', 'clubid', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('street', $this->gridsort('COM_BADMINTON_PLAYERS_STREET', 'street', $this->listDirection, $this->listOrder), false, 'th');
		$html[] = $this->addCell('postalcode', $this->gridsort('COM_BADMINTON_PLAYERS_POSTALCODE', 'postalcode', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('city', $this->gridsort('COM_BADMINTON_PLAYERS_CITY', 'city', $this->listDirection, $this->listOrder), false, 'th');
		$html[] = $this->addCell('email', $this->gridsort('COM_BADMINTON_PLAYERS_EMAIL', 'email', $this->listDirection, $this->listOrder), false, 'th');
		$html[] = $this->addCell('home', JText::_('COM_BADMINTON_PLAYERS_HOME'), true, 'th');
		$html[] = $this->addCell('mobile', JText::_('COM_BADMINTON_PLAYERS_MOBILE'), true, 'th');
		$html[] = $this->addCell('birthdate', $this->gridsort('COM_BADMINTON_PLAYERS_BIRTHDATE', 'birthdate', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('gender', $this->gridsort('COM_BADMINTON_PLAYERS_GENDER', 'gender', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('competitive', $this->gridsort('COM_BADMINTON_PLAYERS_COMPETITIVE', 'competitive', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('added', $this->gridsort('COM_BADMINTON_PLAYERS_ADDED', 'added', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('paiddate', $this->gridsort('COM_BADMINTON_PLAYERS_PAIDDATE', 'paiddate', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('paidamount', $this->gridsort('COM_BADMINTON_PLAYERS_PAIDAMOUNT', 'paidamount', $this->listDirection, $this->listOrder), true, 'th');
		$html[] = $this->addCell('paidyear', $this->gridsort('COM_BADMINTON_PLAYERS_PAIDYEAR', 'paidyear', $this->listDirection, $this->listOrder), true, 'th'); 
		$html[] = $this->addCell('allowed', $this->gridsort('COM_BADMINTON_PLAYERS_ALLOWED', 'allowed', $this->listDirection, $this->listOrder), true, 'th');

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
	protected function showPlayer($i, $row) {

		// Generate a row
		$html = array("<tr>");
		
		// Add offset and checkbox
		$html[] = "<td>" . $this->pagination->getRowOffset($i) . "</td>";
		if ($this->canView || $this->showgrouptool)
			$html[] = "<td>" . JHtml::_('grid.id', $i, $row->id) . "</td>";
		
		// name
		$task = $this->canEdit ? 'edit' : 'view';
		$link = JRoute::_("index.php?option=com_badminton&task=player.$task&id={$row->id}");
		$usericon = $row->user_id > 0 ? ' <i class="fa fa-user"></i>' : '';
		if (!empty($row->properties))
		{
			$properties = json_decode($row->properties, true);
			if (isset($properties['request']) && count($properties['request']) > 0)
				$usericon = ' <i class="fa fa-pencil-square-o"></i>';
		}
		$attr = array('title' => JText::_('COM_BADMINTON_MANAGER_PLAYERS_' . strtoupper($task) . '_DESC'));
		if ($this->canView)
			$html[] = $this->addCell('name', JHtml::link($link, $row->lastname . ' ' . $row->firstname . $usericon, $attr));
		else
			$html[] = $this->addCell('name', $row->lastname . ' ' . $row->firstname . $usericon);
		
		// vbl
		if (!empty($row->vbl_urlid))
			$vbl = JHtml::link($this->vbl_url . $row->vbl_urlid, JHtml::image($this->vbl_img, 'VBL'), array('target' => '_blank'));
		else if (!empty($row->vblid))
			$vbl = JHtml::link("javascript:void(0);", '<i class="fa fa-search"></i>', array("onclick" => "searchVbl('{$row->vblid}')", "id" => "{$row->vblid}"));
		else
			$vbl = "<i class=\"fa fa-exclamation-triangle\"></i>";
		$html[] = $this->addCell('vbl', $vbl, true);
		
		// Others
		$html[] = $this->addCell('vblid', $row->vblid, true);
		$html[] = $this->addCell('clubid', $row->clubid, true);
		$html[] = $this->addCell('street', $row->address1 . (empty($row->address2) ? '' : '<br />' . $row->address2));
		$html[] = $this->addCell('postalcode', $row->postalcode, true);
		$html[] = $this->addCell('city', $row->city);
		$html[] = $this->addCell('email', $row->email);
		$html[] = $this->addCell('home', $row->home, true);
		$html[] = $this->addCell('mobile', $row->mobile, true);
		$html[] = $this->addCell('birthdate', $this->formatDate($row->birthdate), true);
		$html[] = $this->addCell('gender', $this->formatGender($row->gender), true);
		$html[] = $this->addCell('competitive', $this->toggleSwitch($i, array(
			array('action' => 'players.recreative', 'tooltip' => 'Make recreative'),
			array('action' => 'players.competitive', 'tooltip' => 'Make competitive')
			), $row->competitive), true);
		$html[] = $this->addCell('added', $this->formatDate($row->added, '?'), true);
		$html[] = $this->addCell('paiddate', $this->formatDate($row->paiddate, '<i class="fa fa-times-circle"></i>'), true);
		$html[] = $this->addCell('paidamount', $row->paidamount > 0 ? $row->paidamount : '', true);
		$html[] = $this->addCell('paidyear', $row->paidyear > 0 ? $row->paidyear : '', true);
		$html[] = $this->addCell('allowed', $this->toggleSwitch($i, array(
			array('action' => 'players.block', 'tooltip' => 'COM_BADMINTON_MANAGER_PLAYER_BLOCK'),
			array('action' => 'players.allow', 'tooltip' => 'COM_BADMINTON_MANAGER_PLAYER_ALLOW')
			), $row->allowed), true);

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
	
	/**
	 * Format a gender to a string
	 */
	protected function formatGender($gender)
	{
		if ($gender == 1)
			return JText::_('COM_BADMINTON_PLAYER_GENDER_MALE');
		else if ($gender == 2)
			return JText::_('COM_BADMINTON_PLAYER_GENDER_FEMALE');
		else
			return JText::_('COM_BADMINTON_PLAYER_GENDER_UNSPECIFIED');
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
	
	public function getListFooter()
	{
		$pages = $this->pagination->getPaginationPages();
		$links = $this->pagination->getPagesLinks();
		print_r($links);
		
		$html = array();
		return implode('', $html);
	}
	
	/**
	 * Creates a dropdown box for selecting how many records to show per page.
	 *
	 * @return  string  The HTML for the limit # input box.
	 *
	 * @since   1.5
	 */
	public function getLimitBox()
	{
		$limits = array();

		// Make the option list.
		for ($i = 5; $i <= 30; $i += 5)
		{
			$limits[] = JHtml::_('select.option', "$i");
		}

		$limits[] = JHtml::_('select.option', '50', JText::_('J50'));
		$limits[] = JHtml::_('select.option', '100', JText::_('J100'));
		$limits[] = JHtml::_('select.option', '0', JText::_('JALL'));

		$viewall = $this->pagination->get('viewall');
		$selected = $viewall ? 0 : $this->pagination->limit;

		$html = JHtml::_(
			'select.genericlist',
			$limits,
			'list[limit]',
			'class="inputbox input-mini" size="1" onchange="this.form.submit()"',
			'value',
			'text',
			$selected
		);

		return $html;
	}
}