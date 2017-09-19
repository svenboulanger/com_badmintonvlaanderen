<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

class BadmintonViewMeeting extends JViewLegacy
{
	/*
	 * Display the view
	 */
	public function display($tpl = null)
	{
		// Authorise user
		$app = JFactory::getApplication();
		if (!JFactory::getUser()->authorise('badminton.meeting', 'com_badminton'))
		{
			$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			return false;
		}
		
		// Display errors
		if (count($errors = $this->get('Errors')))
		{
			$app->enqueueMessage(implode('<br />', $errors), 'error');
			return false;
		}

		// Add styles
		$document = JFactory::getDocument();
		$document->addStyleSheet(JRoute::_('media/com_badminton/css/player.css'));
		$document->addStyleSheet(JRoute::_('media/com_badminton/css/jquery-ui.min.css'));
		
		// Add scripts
		JHtml::_('jquery.framework');
		$document->addScript(JRoute::_('media/com_badminton/js/meeting.js'));
		$document->addScript(JRoute::_('media/com_badminton/js/player.js'));
		$document->addScript(JRoute::_('media/com_badminton/js/jquery-ui.min.js'));
		
		// Add the onload event
		$javascript = '';
		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration('window.onload = loaded');
		
		parent::display($tpl);
	}
	
	/*
	 * Output a line of match code
	 */
	public function outputMatch($type, $id, $rows) {
		echo "<tr>\r\n";
		
		// Game type
		if ($rows > 1)
			echo "<td rowspan = \"" . $rows . '" class=\"tetype\" id = "' . $id . 'type">' . $type . '</td>';
		elseif (strlen($type) > 0)
			echo "<td class = \"tetype\" id = \"" . $id . "type\">" . $type . "</td>";
		$sd = substr($id, 0, 1);
		$nr = substr($id, 1, 1);

		// Names
		echo "<td class = \"selectable\" onclick = \"clickSelectPlayer('" . $id . "h', 'home');\"><span id=\"" . $id . "hlastname\"></span> <span id=\"" . $id . "hfirstname\"></span></td>\r\n";
		echo "<td>";
		if ($sd == 's')
			echo "<span id=\"" . $id . "hsingles\"></span>";
		else if ($sd == 'd') {
			echo "<span class=\"tedoubles\" id=\"" . $id . "hdoubles\"></span>";
			echo "<span class=\"temixed\" id=\"" . $id . "hmixed\"></span>";
		}
		echo "<span style=\"display:none;\" id=\"" . $id . "hmemberid\"></span>";
		echo "</td>\r\n";
		echo "<td class = \"selectable\" onclick = \"clickSelectPlayer('" . $id . "v', 'visitor');\"><span id=\"" . $id . "vlastname\"></span> <span id=\"" . $id . "vfirstname\"></span></td>\r\n";
		echo "<td>";
		if ($sd == 's')
			echo "<span id=\"" . $id . "vsingles\"></span>";
		else if ($sd == 'd') {
			echo "<span class=\"tedoubles\" id=\"" . $id . "vdoubles\"></span>";
			echo "<span class=\"temixed\" id=\"" . $id . "vmixed\"></span>";
		}
		echo "<span style=\"display:none;\" id=\"" . $id . "vmemberid\"></span>";
		echo "</td>\r\n";
		
		// Scores
		if ($rows > 1)
		{
			echo "<td class = \"selectable\" width = \"30px\" rowspan = \"" . $rows . '" id = "' . $id . "hscore1\" onclick = \"clickSelectScore('" . $id . "hscore1')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" rowspan = \"" . $rows . '" id = "' . $id . "vscore1\" onclick = \"clickSelectScore('" . $id . "vscore1')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" rowspan = \"" . $rows . '" id = "' . $id . "hscore2\" onclick = \"clickSelectScore('" . $id . "hscore2')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" rowspan = \"" . $rows . '" id = "' . $id . "vscore2\" onclick = \"clickSelectScore('" . $id . "vscore2')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" rowspan = \"" . $rows . '" id = "' . $id . "hscore3\" onclick = \"clickSelectScore('" . $id . "hscore3')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" rowspan = \"" . $rows . '" id = "' . $id . "vscore3\" onclick = \"clickSelectScore('" . $id . "vscore3')\"></td>\r\n";
		}
		else if ($rows > 0)
		{
			echo "<td class = \"selectable\" width = \"30px\" id = \"" . $id . "hscore1\" onclick = \"clickSelectScore('" . $id . "hscore1')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" id = \"" . $id . "vscore1\" onclick = \"clickSelectScore('" . $id . "vscore1')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" id = \"" . $id . "hscore2\" onclick = \"clickSelectScore('" . $id . "hscore2')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" id = \"" . $id . "vscore2\" onclick = \"clickSelectScore('" . $id . "vscore2')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" id = \"" . $id . "hscore3\" onclick = \"clickSelectScore('" . $id . "hscore3')\"></td>\r\n";
			echo "<td class = \"selectable\" width = \"30px\" id = \"" . $id . "vscore3\" onclick = \"clickSelectScore('" . $id . "vscore3')\"></td>\r\n";
		}
	}
}