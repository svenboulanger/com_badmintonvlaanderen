<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

// Load the pdf library
require_once(JRoute::_("media/com_badminton/php/fpdf.php"));

class BadmintonViewEvents extends JViewLegacy
{
	// Generate the PDF
	public function display($tpl = null) {
		
		// Check access levels
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$this->viewlevels = array_unique($user->getAuthorisedViewLevels());
		
		// Get the input
		$ids = array();
		$req = $app->input->get('id', '', 'array');
		foreach ($req as $i)
			$ids[] = intval($i);
		$ids = array_unique($ids);

		$filename = 'bamadievent.ics';
		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $filename);

		$eol = "\r\n";
		echo 'BEGIN:VCALENDAR' . $eol;
		echo 'VERSION:2.0' . $eol;
		echo 'PRODID:-//BAMADI VZW//Bamadi iCalendar events//NL' . $eol;
		
		// Get all events
		$events = $this->getEvents($ids);
		foreach ($events as $event)
			$this->writeEvent($event, $eol);
		
		echo 'END:VCALENDAR';
	}

	/**
	 * Write an iCal event to the output
	 *
	 * @param $event		The event associacted array
	 */
	private function writeEvent($event, $eol = "\r\n") {
		
		// Get all unique dates
		$pdates = unserialize($event['period']);
		$sdates = unserialize($event['dates']);
		$dates = array_unique(array_merge($pdates, $sdates));
		
		// Calculate the duration of each event
		$start = DateTime::createFromFormat('Y-m-d H:i:s', $event['startdate']);
		$end = DateTime::createFromFormat('Y-m-d H:i:s', $event['enddate']);
		$hours = $end->format('H') - $start->format('H');
		$minutes = $end->format('i') - $start->format('i');
		$seconds = $end->format('s') - $start->format('s');
		if ($seconds < 0) {
			$seconds += 60;
			$minutes--;
		}
		if ($minutes < 0) {
			$minutes += 60;
			$hours--;
		}
		if ($hours < 0)
			return;
		$interval = new DateInterval("PT{$hours}H{$minutes}M{$seconds}S");

		// Write the iCal event header
		foreach ($dates as $i => $date) {
			
			$dt = DateTime::createFromFormat('Y-m-d H:i', $date);
			$dt->setTimezone(new DateTimeZone('UTC'));
			$summary = preg_replace('/([\,;])/','\\\$1', $event['title']);
			$address = preg_replace('/([\,;])/','\\\$1', $event['address']);
			$desc = preg_replace('/([\,;])/','\\\$1', $event['shortdesc']);
			
			echo 'BEGIN:VEVENT' . $eol;
			echo 'SUMMARY:' . $summary . $eol;
			echo 'DESCRIPTION:' . $desc . $eol;
			echo 'UID:' . md5('bamadi_ev' . $event['id'] . 'dt' . ($i++)) . '@bamadi.be' . $eol;
			echo 'LOCATION:' . $address . $eol;
			echo 'DTSTART:' . $dt->format('Ymd\THis\Z') . $eol;
			$dt->add($interval);
			echo 'DTEND:' . $dt->format('Ymd\THis\Z') . $eol;
			echo 'END:VEVENT' . $eol;
		}
	}
	
	/**
	 * Extract events from the database to be put in the iCalendar file
	 *
	 * @param $pks		The id's to be gotten
	 *
	 * @return Array	An array of objects
	 */
	private function getEvents($ids) {

		// Initialize
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__icagenda_events'))
			->where('id IN(' . implode(',', $ids) . ') AND access IN(' . implode(',', $this->viewlevels) . ')');
		
		// Return the list of events
		return $db->setQuery($query)->loadAssocList();
	}
}