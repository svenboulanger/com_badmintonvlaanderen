<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');
require_once(JRoute::_("media/com_badminton/php/spreadsheet/SpreadsheetReader.php"));

class BadmintonViewMailAll extends JViewLegacy
{
	
	/**
	 * Display the players view
	 *
	 * @param $tpl		The name of the template
	 */
	public function display($tpl = null) {
		
		// Check access levels
		$app = JFactory::getApplication();
		$user = JFactory::getUser();

		// No access if the player cannot view a list
		if (!$user->authorise('player.mailall', 'com_badminton') && !$this->canView)
			return false;

		// Set document
		$this->setDocument();
		$this->form = $this->get('Form');

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
	}
	
	/*
	 * Create the html for a toolbar
	 *
	 * @return html	The html
	 */
	protected function createToolbar() {
		// Generate the HTML
		$html = array('<div class="row-fluid">');
		
		$html[] = JHtml::link("javascript:void(0);", '<i class="fa fa-envelope"></i> ' . JText::_('COM_BADMINTON_MAIL_ALL'), array(
			"onclick" => "Joomla.submitbutton('players.mailall');",
			"class" => "btn btn-success"
		));

		$html[] = '</div>';
		return implode(' ', $html);
	}
}