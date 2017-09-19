<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

class BadmintonVlaanderenViewTeamExchange extends JViewLegacy
{
	/*
	 * Display the view
	 */
	public function display($tpl = null)
	{

		// Check access levels
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$input = $app->input;
		
		// Check
		$authorised = $user->authorise('badmintonvlaanderen.teamexchange', 'com_badmintonvlaanderen') || count($user->getAuthorisedCategories('com_badmintonvlaanderen', 'badmintonvlaanderen.teamexchange'));
		if (!$authorised)
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);
			return false;
		}
		
		// Get the defaults from GET or POST inputs
		$this->home = $input->get('home', '', 'string');
		$this->visitor = $input->get('visitor', '', 'string');
		$this->mlevel = $input->get('level', 2, 'int');
		$this->mtype = $input->get('type', 1, 'int');
		$this->mdate = $input->get('date', '', 'string');
		$this->mtime = $input->get('time', '', 'string');
		$this->division = $input->get('division', '', 'string');
		$this->series = $input->get('series', '', 'string');
		$this->getHeaders();
		
		// Display errors
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode('<br />', $errors), 'error');
			return false;
		}

		// Set the document
		$this->setDocument();
		parent::display($tpl);
	}
	
	/**
	 * Get the headers
	 */
	protected function getHeaders()
	{
		$rows = array(2, 2, 2, 2, 1, 1, 1, 1, 4);
		$ids = array("m1", "m2", "m3", "m4", "m5", "m6" ,"m7", "m8", "i");
		$types = array(
			array('MD', 'WD', 'XD', 'XD', 'MS', 'MS', 'WS', 'WS', 'SUB'),
			array('MD', 'MD', 'MD', 'MD', 'MS', 'MS', 'MS', 'MS', 'SUB'),
			array('WD', 'WD', 'WD', 'WD', 'WS', 'WS', 'WS', 'WS', 'SUB')
		);
		$indices = array(
			array('', '', '1', '2', '1', '2', '1', '2', ''),
			array('1', '2', '3', '4', '1', '2', '3', '4', ''),
			array('1', '2', '3', '4', '1', '2', '3', '4', '')
		);
		
		// Create the header information
		$this->headerInfo = array();
		for ($i = 0; $i < 3; $i++)
		{
			$this->headerInfo[$i] = array();
			for ($k = 0; $k < 9; $k++)
			{
				$this->headerInfo[$i][] = (object) array(
					'rows' => $rows[$k],
					'id' => $ids[$k],
					'index' => $indices[$i][$k],
					'label' => JText::_('COM_BADMINTONVLAANDEREN_TYPE_' . $types[$i][$k])
				);
			}
		}
	}
	
	/**
	 * Set the title of the document
	 */
	protected function setDocument() {
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE'));
		
		// Add scripts
		JHtml::_('bootstrap.framework');

		
		// Add the onload event
		$document->addScriptDeclaration('window.onload = BadmintonVlaanderen.loaded;');
		
		// Add some variables necessary from PHP
		$document->addScriptDeclaration('
			var BadmintonVlaanderenConfig = {
				waitingIcon: \'<div class="text-center">' . JHtml::image(JRoute::_('media/com_badmintonvlaanderen/images/spinner.gif'), 'Searching...') . '</div>\',
				bvlActive: \'' . JHtml::image(JRoute::_(JURI::root() . 'media/com_badmintonvlaanderen/images/bvl_active.png'), 'BVL') . '\',
				bvlIdle: \'' . JHtml::image(JRoute::_(JURI::root() . 'media/com_badmintonvlaanderen/images/bvl_idle.png'), '-') . '\',
				bvlSearch: \'' . JHtml::image(JRoute::_(JURI::root() . 'media/com_badmintonvlaanderen/images/bvl_search.png'), 'Search') . '\',
				lblRemove: \'' . JText::_('COM_BADMINTONVLAANDEREN_REMOVE') . '\',
				lblNoID: \'' . JText::_('COM_BADMINTONVLAANDEREN_NOMEMBERID') . '\',
				lblNoPlayer: \'' . JText::_('COM_BADMINTONVLAANDEREN_NOPLAYER') . '\',
				types: ' . json_encode($this->headerInfo) . ',
				lblDuplicate: \'' . addslashes(JText::_('COM_BADMINTONVLAANDEREN_CHECK_DUPLICATE')) . '\',
				lblArt_52_6: \'' . addslashes(JText::_('COM_BADMINTONVLAANDEREN_CHECK_ART52_6')) . '\',
				lblArt_52_7: \'' . addslashes(JText::_('COM_BADMINTONVLAANDEREN_CHECK_ART52_7')) . '\'
			};');
			
		$document->addScript('media/com_badmintonvlaanderen/js/teamexchange.js');
		$document->addScript('media/com_badmintonvlaanderen/js/teamexchange_rules.js');
		$document->addScript('media/com_badmintonvlaanderen/js/jquery-ui.min.js');
		
		// Add some styling
		$style[] = ".table td{height:37px;padding:2px;}";
		$style[] = ".fa-check-circle{color:#468847;}";
		$style[] = ".fa-times-circle{color:#cc0000;}";
		$style[] = ".selectable{cursor:pointer;}";
		$style[] = ".line{display:table; width:100%; margin:2px;}";
		$style[] = ".line div{display:table-cell;vertical-align:middle;padding:2px;}";
		$style[] = ".line div input{width:100%;}";
		$style[] = ".line a{display:table-cell;}";
		$style[] = "table thead tr th{text-align:center;}";
		$style[] = "table tbody tr td.type{vertical-align:middle;background-color:white;}";
		$style[] = ".vblid,.singles,.doubles,.mixed{text-align:center;}";
		$style[] = ".teamexchange_error{color:red;}";
		$document->addStyleDeclaration(implode($style));
		$document->addStyleSheet('media/com_badmintonvlaanderen/css/jquery-ui.min.css');
	}
	
	/**
	 * Output HTML for a player row
	 *
	 * @param $index	The index of the match to show
	 * @param $id		The id that is given to the match
	 * @param $rows		The number of players in the match
	 */
	function outputPlayerRow($index)
	{
		$info = $this->headerInfo[$this->mtype][$index];
		
		// Start first row
		$class = $info->id . "p1";
		echo "<tr class=\"selectable\" onclick=\"BadmintonVlaanderen.selectPlayerByElementId('$class')\">";
		
		// Show match type
		if ($info->rows > 1)
			echo "<td rowspan=\"{$info->rows}\" class=\"{$info->id} type\">{$info->label}</td>";
		else
			echo "<td class = \"{$info->id} type\">{$info->label}</td>";
			
		// Add players
		for ($i = 1; $i <= $info->rows; $i++) {
			$class = $info->id . "p" . $i;
			
			// Show other players
			if ($i > 1)
				echo "<tr class=\"selectable\" onclick=\"BadmintonVlaanderen.selectPlayerByElementId('$class')\">";
			
			// Display data
			echo("<td class=\"$class name\"></td>");
			echo("<td class=\"$class bvlid\"></td>");
			echo("<td class=\"$class singles\"></td>");
			echo("<td class=\"$class doubles\"></td>");
			echo("<td class=\"$class mixed\"></td>");

			// End row
			echo "</tr>";
		}
	}
}