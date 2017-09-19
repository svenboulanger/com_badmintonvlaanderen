<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

// Load the pdf library
require_once(JRoute::_("media/com_badminton/php/xlsxwriter.class.php"));

class BadmintonViewPlayers extends JViewLegacy
{
	/*
	 * Generate the display
	 *
	 * @param	string	$tpl		The template
	 *
	 */
	public function display($tpl = null) {

		// Check access levels
		$app = JFactory::getApplication();
		$filename = $this->generateXlsx();
		
		$model = $this->getModel();
		$query = $model->getCurrentQuery();
		
		// Return the file
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($filename));
		readFile($filename);

		// Remove the file
		unlink($filename);
	}
	
	/*
	 * Generate an XLSX file
	 *
	 * return	string		The filename of the generated file
	 */
	protected function generateXlsx() {
		
		// Generate the filename
		$filename = 'members.xlsx';
		if (file_exists($filename))
			unlink($filename);
		
		// Initialize
		$writer = new XLSXWriter();
		$db = JFactory::getDbo();
		$keys = array('id' => 'integer', 'firstname' => 'string', 'lastname' => 'string', 'vblid' => 'string', 'clubid' => 'string',
			'address1' => 'string', 'address2' => 'string', 'postalcode' => 'integer', 'city' => 'string', 'home' => 'string', 'mobile' => 'string', 'email' => 'string',
			'birthdate' => 'date', 'gender' => 'string', 'competitive' => 'integer', 'added' => 'date', 'paiddate' => 'date', 'paidamount' => 'euro', 'paidyear' => 'integer');
		
		// Calculate the header values
		$header = array();
		foreach ($keys as $key => $format) {
			$name = JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($key));
			$header[$name] = $format;
		}
		
		// Load all players from the list
		$writer->writeSheetHeader('Members', $header);
		$db = JFactory::getDbo();
		$model = $this->getModel();
		/* $db->setQuery($db->getQuery(true)
			->select(array_keys($keys))->from($db->quoteName('#__badminton_players'))); */
		$db->setQuery($model->getCurrentQuery());
		$it = $db->getIterator();
		while ($it->valid()) {
			$object = $it->current();
			$values = array();
			
			// Change values if necessary
			foreach ($keys as $key => $format) {
				
				// Change dates
				if ($format == 'date')
					$values[] = $this->formatDate($object->$key);
				else if ($format == 'euro')
					$values[] = $object->$key == 0 ? '' : $object->$key;

				// Change based on key
				else if ($key == 'gender')
				{
					if ($object->$key == 1)
						$values[] = JText::_('COM_BADMINTON_PLAYER_GENDER_MALE');
					else if ($object->$key == 2)
						$values[] = JText::_('COM_BADMINTON_PLAYER_GENDER_FEMALE');
					else
						$values[] = JText::_('COM_BADMINTON_PLAYER_GENDER_UNSPECIFIED');
				}
				else if ($key == 'paidyear' || $key == 'postalcode')
				{
					if ($object->$key == 0)
						$values[] = '';
					else
						$values[] = $object->$key;
				}
				else
					$values[] = $object->$key;
			}

			$writer->writeSheetRow('Members', $values);
			$it->next();
		};
		
		// Create a new sheet to be able to read it back
		$writer->writeSheet(array(), 'Sheet1');
		
		// Write to file
		$writer->writeToFile($filename);
		return $filename;
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
	protected function formatDate($input, $default = '', $fmt = "Y-m-d") {
		
		if ($input == null)
			return;

		// If a string, check for null date
		if (is_string($input)) {
			$db = JFactory::getDbo();
			if ($input === $db->getNullDate())
				return $default;
			$date = new JDate($input);
			return $date->format($fmt);
		}
		return $input->format($fmt);
	}
}