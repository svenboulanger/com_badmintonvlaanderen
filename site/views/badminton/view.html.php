<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

class BadmintonViewBadminton extends JViewLegacy
{
	// Display the Badminton view
	public function display($tpl = null)
	{
		// Display errors
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		parent::display($tpl);
	}
}