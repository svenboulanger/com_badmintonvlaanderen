<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');
require_once(JRoute::_("media/com_badminton/php/spreadsheet/SpreadsheetReader.php"));

class BadmintonViewPlayersupload extends JViewLegacy
{
	// Constants
	protected $vbl_url = 'http://badmintonvlaanderen.toernooi.nl/profile/overview.aspx?id=';
	protected $vbl_img = 'media/com_badminton/images/searchVbl.png';
	protected $valid_ext = array('xls', 'xlsx');
	protected $previewcount = 10;
	protected $action = false;
	protected $fields = array();
	protected $isfile = false;

	/**
	 * Display the players view
	 *
	 * @param $tpl		The name of the template
	 */
	public function display($tpl = null) {
		
		// Check access levels
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$input = $app->input;
		$this->canAdd = $user->authorise('player.add', 'com_badminton');

		// Get application data
		$context = "badminton.list.admin.playerupload";
		$this->targetfile = __DIR__ . '/uploaded/upload' . $user->id;
		$this->previewcount = $app->getUserStateFromRequest("$context.previewcount", "previewcount", 10, "INT");
		if ($input->get('save', '', 'string') === JText::_('JSAVE'))
			$this->action = 'save';
		else if ($input->get('cancel', '', 'string') === JText::_('JCANCEL'))
			$this->action = 'cancel';
		else
			$this->action = 'view';
		
		// Get the supported fields and names
		$this->fields = array('id', 'firstname', 'lastname', 'vblid', 'clubid', 'address1', 'address2', 'postalcode', 'city', 'home', 'mobile', 'email', 'birthdate', 'gender', 'competitive', 'paiddate', 'paidamount');
		$this->primary = array('id', 'firstname', 'lastname');
		$this->names = array();
		foreach ($this->fields as $field) {
			$name = JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($field));
			$this->names[$field] = $name;
		}
		
		// Check for uploaded files and set document options
		$this->checkUploads();
		$this->setDocument();
		
		// Load the preview
		$this->doAction();
		
		// Clean up if the action was save or cancel
		if ($this->action === 'save' || $this->action === 'cancel') {
			$this->cleanUp();
			
			// Redirect to members
			$app->redirect(JRoute::_('index.php?option=com_badminton&view=players', false));
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
		
		// Add some styling
		$style = ".fa-check-circle{color:#468847;}";
		$style .= ".fa-times-circle{color:#cc0000;}";
		$document->addStyleDeclaration($style);
	}
	
	/**
	 * Load a preview of the currently stored spreadsheet file
	 *
	 * @return boolean
	 */
	protected function doAction() {
		
		// Create preview players
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		$this->preview = array();
		
		// Clean up if cancelled
		if ($this->action === 'cancel') {
			$this->cleanUp();
			return false;
		}

		// Retrieve the file
		$files = glob($this->targetfile . '.*');
		if (count($files) !== 1)
			return false;
		$target = $files[0];
		$this->isfile = true;
		
		// Initialize
		$primaryheaders = array();
		foreach ($this->primary as $value)
			$primaryheaders[] = strtolower($this->names[$value]);
			
		// Calculate the supported header values
		$header = array();
		foreach ($this->fields as $key)
			$header[strtolower($this->names[$key])] = $key;
		$columns = array();
		
		// Load the reader
		try
		{
			// Initialize
			$this->uploaderrors = array();
			$this->uploadwarnings = array();
			$nophone = array();
			$invalid = 0;
			$valid = 0;
			$added = 0;
			
			// Reader
			$reader = new SpreadsheetReader($target);
			
			// Read the whole file to produce errors and warnings
			foreach ($reader as $row) {
				
				// Convert to strings
				$sRow = array();
				foreach ($row as $value) {
					if ($value instanceof DateTime)
						$sRow[] = $this->formatDate($value);
					else
						$sRow[] = strtolower((string)$value);
				}
				
				// First find a header
				$found = array_intersect($primaryheaders, $sRow);
				if (count($found) == count($this->primary)) {
					
					// Read the columns
					foreach ($sRow as $index => $value) {
						
						// Find the associated name
						if (isset($header[$value]))
							$columns[$header[$value]] = $index;
					}
				}
				else if (count($columns) > 0)
				{
				
					// Conditions for a row to be recognized: id is numeric and primary fields have all values
					$isValidRow = true;
					foreach ($this->primary as $key) {
						$index = $columns[$key];
						if (empty($row[$index]))
						{
							$isValidRow = false;
							break;
						}
					}
					if (!$isValidRow || !is_numeric($row[$columns['id']]))
						continue;
					
					// Create the player object
					$player = array();
					foreach ($columns as $key => $index) {
						if (isset($row[$index]))
							$player[$key] = $row[$index];
					}
					
					if ($this->validatePlayer($player))
					{
						// Add to the preview
						if (count($this->preview) < $this->previewcount)
							$this->preview[] = $player;
						$valid++;
						
						// Add if action is save
						if ($this->action === 'save') {
							
							// Try to save it in the database if it doesn't already exist
							if ($this->addPlayer($player))
								$added++;
						}
					}
					else
						$invalid++;
				}
			}
			
			// Give a message that no headers were found
			if (count($columns) == 0) {
				$app->enqueueMessage(JText::sprintf('COM_BADMINTON_PLAYERS_UPLOAD_NOHEADERS', implode(', ', $primaryheaders)));
			}
		}
		catch (Exception $ex)
		{
			$this->uploaderrors[] = $ex->getMessage();
		}
		
		$app = JFactory::getApplication();
		if (count($this->uploaderrors) > 0)
			$app->enqueueMessage(implode('<br />', $this->uploaderrors), 'error');
		if (count($this->uploadwarnings) > 0)
			$app->enqueueMessage(implode('<br />', $this->uploadwarnings), 'warning');
		
		// Show some quick statistics
		$app->enqueueMessage(JText::sprintf('COM_BADMINTON_PLAYERS_UPLOAD_FOUND', $valid + $invalid, $invalid));
		if ($added > 0)
			$app->enqueueMessage(JText::plural('COM_BADMINTON_PLAYERS_ADDED', $added));
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
		try
		{
		
			// Handle DateTime input
			if ($input instanceof DateTime)
			{
				if ($input->format($db->getDateFormat()) === $db->getNullDate())
					return $default;
				else
					return $input->format($fmt);
			}
		
			// Handle JDate input
			else if ($input instanceof JDate)
			{
				if ($input->format($db->getDateFormat()) === $db->getNullDate())
					return $default;
				else
					return $input->format($fmt);
			}
		
			// Handle string input
			else
			{
				$dt = JFactory::getDate((string)$input);
				if ($dt->format($db->getDateFormat()) === $db->getNullDate())
					return $default;
				else
					return $dt->format($fmt);
			}
		}
		catch (Exception $ex)
		{
			JFactory::getApplication()->enqueueMessage($ex->getMessage(), 'error');
		}
		return $default;
	}
	
	/**
	 * Validate player data
	 *
	 * @param	array	$player		An associative array containing the player data
	 * @param	array	&$this->uploaderrors	An array containing all errors
	 * @param	array	&$this->uploadwarnings	An array containing all warnings
	 *
	 * @return	boolean
	 */
	protected function validatePlayer($player) {
		
		// Pattern with accents, etc.
		$pattern = '/^[\p{L}\d\.\/ -]+$/u';
		
		// Check empty ID
		
		// Check the format of standard text fields
		$fields = array('firstname', 'lastname', 'vblid', 'clubid',
			'address1', 'address2', 'city');
		foreach ($fields as $field) {
			if (!empty($player[$field]) && !preg_match($pattern, $player[$field])) {
				$this->uploaderrors[] = JText::sprintf('COM_BADMINTON_PLAYERS_UPLOAD_TEXT', JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($field)), $player['id']) . ' (' . $player[$field] . ')';
				return false;
			}
		}
			
		// Check the content of numeric fields
		$fields = array('vblid', 'postalcode', 'competitive', 'paidamount');
		foreach ($fields as $field) {
			if (!empty($player[$field]) && !is_numeric($player[$field])) {
				$this->uploaderrors[] = JText::sprintf('COM_BADMINTON_PLAYERS_UPLOAD_NUMBER', JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($field)), $player['id']) . ' (' . $player[$field] . ')';
				return false;
			}
		}
		
		// Check the content of the gender field
		$fields = array('gender');
		$options = array('gender' => array(
			JText::_('COM_BADMINTON_PLAYER_GENDER_UNSPECIFIED'),
			JText::_('COM_BADMINTON_PLAYER_GENDER_MALE'),
			JText::_('COM_BADMINTON_PLAYER_GENDER_FEMALE')
			));
		foreach ($fields as $field) {
			if (!in_array($player[$field], $options[$field])) {
				$this->uploaderrors[] = $field;
				$this->uploaderrors[] = JText::sprintf(
				'COM_BADMINTON_PLAYERS_UPLOAD_GENDER', JText::_(
				'COM_BADMINTON_PLAYERS_' . strtoupper($field)), $player['id']) . ' (' . $player[$field] . ')';
				return false;
			}
		}
		
		// Check the content of date fields
		$fields = array('birthdate', 'paiddate');
		foreach ($fields as $field) {
		}
			
		// Check the content of email fields
		$fields = array('email');
		foreach ($fields as $field) {
			if (!empty($player[$field]) && !filter_var($player[$field], FILTER_VALIDATE_EMAIL)) {
				$this->uploaderrors[] = JText::sprintf('COM_BADMINTON_PLAYERS_UPLOAD_EMAIL', JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($field)), $player['id']) . ' (' . $player[$field] . ')';
				return false;
			}
		}
		
		// Check the content of phone fields
		$fields = array('home', 'mobile');
		foreach ($fields as $field) {
		}
		
		// Passes all the tests
		return true;
	}
	
	/**
	 * Add a player to the database
	 *
	 * @param	array	$player		An associative array containing the player data
	 * @param	array	&$this->uploaderrors	An array containing all errors
	 * @param	array	&$this->uploadwarnings	An array containing all warnings
	 *
	 * @return	boolean
	 */
	protected function addPlayer($player) {
		$db = JFactory::getDbo();
		
		// Build duplicate check
		$conditions = array();
		if (!empty($player['vblid']))
			$conditions[] = $db->quoteName('vblid') . '=' . $db->quote($player['vblid']);
		if (!empty($player['clubid']))
			$conditions[] = $db->quoteName('clubid') . '=' . $db->quote($player['clubid']);
		if (!empty($player['email']))
			$conditions[] = 'LOWER(' . $db->quoteName('email') . ')=' . $db->quote(strtolower($player['email']));
		
		if (count($conditions) > 0) {
			$db->setQuery('SELECT EXISTS(' .
				$db->getQuery(true)
				->select('1')
				->from($db->quoteName('#__badminton_players'))
				->where($conditions, 'OR') . ')');
			$count = $db->loadResult();
			if ($count > 0) {
				$this->uploadwarnings[] = JText::sprintf('COM_BADMINTON_PLAYERS_UPLOAD_DUPLICATE', $player['firstname'] . ' ' . $player['lastname'] . ' (' . $player['id'] . ')');
				return false;
			}
		}
		
		// Store the new player
		$columns = array();
		$values = array();
		foreach ($player as $key => $value) {
			if ($key === 'id')
				continue;

			$columns[] = $key;
			if ($value instanceof DateTime)
				$values[] = $value->format($db->getDateFormat());
			else if ($key === 'gender')
			{
				if ($value === JText::_('COM_BADMINTON_PLAYER_GENDER_MALE'))
					$values[] = 1;
				else if ($value === JText::_('COM_BADMINTON_PLAYER_GENDER_FEMALE'))
					$values[] = 2;
				else
					$values[] = 0;
			}
			else
				$values[] = $value;
		}
		
		// Make sure the player is initially allowed
		$columns[] = 'allowed';
		$values[] = '1';
		
		$db->setQuery($db->getQuery(true)
			->insert('#__badminton_players')
			->columns($db->quoteName($columns))
			->values(implode(',', $db->quote($values))));
		$response = $db->execute();
		return !($response === false);
	}
	
	/*
	 * Check for any uploaded files
	 *
	 */
	protected function checkUploads() {
		
		// Initialize
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$file = $app->input->files->get('file_upload');
		
		try
		{
			// If the user can't add, delete the file again
			if (!$this->canAdd)
				throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
			
			// Load results in the session
			if (!empty($file['name']))
			{
				// Check for a valid extension
				jimport('joomla.filesystem.file');
				$ext = strtolower(JFile::getExt($file['name']));
				if (!in_array($ext, $this->valid_ext))
					throw new Exception(JText::_('COM_BADMINTON_PLAYERS_INVALID_SPREADSHEET'));
			
				// Remove any files
				$this->cleanUp();
			
				// Move the file to a location that we can work with
				$target = $this->targetfile . '.' . $ext;
				move_uploaded_file($file['tmp_name'], $target);
			}
		}
		catch (Exception $ex)
		{
			$app->enqueueMessage($ex->getMessage(), 'error');
			return false;
		}
	}
	
	/**
	 * Clean up all files for this user
	 */
	protected function cleanUp() {
		
		// Clean up all files
		$files = glob($this->targetfile . '.*');
		if ($files)
			foreach ($files as $file)
				unlink($file);
	}
}