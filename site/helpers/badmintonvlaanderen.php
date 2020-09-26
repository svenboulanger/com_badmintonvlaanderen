<?php
defined('_JEXEC') or die;
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.Bvlplayer
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

require_once(__DIR__ . '/phpQuery-onefile.php');
 
class BadmintonVlaanderen
{
	/**
	 * Base urls for searching on Badminton Vlaanderen
	 */
	protected $base_url = 'https://www.badmintonvlaanderen.be/';
	protected $location_url = 'https://www.badmintonvlaanderen.be/sport/';
	protected $location_entry = 'location.aspx';
	protected $find_url = 'https://www.badmintonvlaanderen.be/ranking/find.aspx?rid=334';
	protected $player_url = 'https://www.badmintonvlaanderen.be/ranking/';
	protected $player_entry = 'player.aspx';
	protected $profile_url = 'https://www.badmintonvlaanderen.be/profile/';
	protected $profile_entry = 'overview.aspx';
	
	/**
	 * Create a full URL to a location
	 */
	protected function getLocationUrl($url)
	{
		// Find the start of location.aspx
		$index = strpos($url, $this->location_entry);
		if ($index !== false)
			$url = $this->location_url . substr($url, $index);
		else
			return false;
		if (!filter_var($url, FILTER_VALIDATE_URL))
			return false;
		return $url;
	}
	
	/**
	 * Create a full URL to a player
	 *
	 * @param string $url		The url starting from 'player.aspx'
	 */
	protected function getPlayerUrl($url)
	{
		// Build the URL
		$index = strpos($url, $this->player_entry);
		if ($index !== false)
			$url = $this->player_url . substr($url, $index);
		else
			return false;
		if (!filter_var($url, FILTER_VALIDATE_URL))
			return false;
		return $url;
	}
	
	/**
	 * Create a full URL to a profile
	 *
	 * @param string $url		The url starting from 'overview.aspx'
	 */
	protected function getProfileUrl($url)
	{
		// Find the start of location.aspx
		$index = strpos($url, $this->profile_entry);
		if ($index !== false)
			$url = $this->profile_url . substr($url, $index);
		else
			return false;
		if (!filter_var($url, FILTER_VALIDATE_URL))
			return false;
		return $url;
	}
	
	/**
	 * Search the website by Badminton Vlaanderen for data
	 */
	public function search($search)
	{
		// Check access
		$user = JFactory::getUser();
		if (!$user->authorise('badmintonvlaanderen.extractbvl', 'com_badmintonvlaanderen'))
			throw new Exception(JText::_('COM_BADMINTONVLAANDEREN_NOBVLACCESS'));
		
		// Search for the keyword
		$curl = curl_init();
		$response = $this->enterSearch($curl, $search);
		if (!$response)
			throw new Exception('CURL error: ' . curl_error($curl));
		
		// Try parsing a player list
		$result = $this->extractPlayerList($curl, $response);
		if ($result)
			return $result;
		
		// Try parsing a player page
		$result = $this->extractPlayer($curl, $response);
		if ($result)
			return array($result);
		
		// Try parsing a profile page
		$result = $this->extractProfile($curl, $response);
		if ($result)
			return array($result);
		
		throw new Exception('Page not recognized');
	}
	
	/**
	 * Search for the ranking information of the player
	 *
	 * @param string $search		The search term
	 */
	public function ranking($search)
	{
		// Check access
		$user = JFactory::getUser();
		if (!$user->authorise('badmintonvlaanderen.extractbvl', 'com_badmintonvlaanderen'))
			throw new Exception(JText::_('COM_BADMINTONVLAANDEREN_NOBVLACCESS'));
		
		// Search
		$player = array();
		$curl = curl_init();
		$response = $this->enterSearch($curl, $search);
		if (!$response)
			throw new Exception(curl_error($curl));
		
		// Try parsing a player list and redirect to the player page if necessary
		$result = $this->extractPlayerList($curl, $response);
		if ($result !== false && count($result) > 0)
		{
			// Take 1 if the list has only one match
			if (count($result) == 1 && isset($result[0]['url']))
			{
				$player = array_merge($player, $result[0]);
				
				// go to this url
				curl_setopt($curl, CURLOPT_URL, $result[0]['url']);
				$response = curl_exec($curl);
			}
			else
				throw new Exception(JText::_('COM_BADMINTONVLAANDEREN_MULTIPLE_PLAYERS'));
		}
		
		// Try parsing a player page and redirect to the profile page if necessary
		$result = $this->extractPlayer($curl, $response);
		if ($result !== false)
		{
			// Redirect to the profile page
			if (isset($result['profile']))
			{
				$player = array_merge($player, $result);
				curl_setopt($curl, CURLOPT_URL, $result['profile']);
				$response = curl_exec($curl);
			}
			else
				throw new Exception(JText::_('COM_BADMINTONVLAANDEREN_NOPROFILE'));
		}
		else
			throw new Exception(JText::_('COM_BADMINTONVLAANDEREN_NOPROFILE1'));
		
		// Try parsing a profile page
		$result = $this->extractProfile($curl, $response);
		if ($result !== false)
		{
			$player = array_merge($player, $result);
			return $player;
		}
		else
			throw new Exception(JText::_('COM_BADMINTONVLAANDEREN_NOPROFILE'));
	}

	/**
	 * Go to Badminton Vlaanderen and enter a search term
	 *
	 * @param string $search		The search term
	 */
	protected function enterSearch($curl, $search)
	{
		// Setup CURL
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $this->find_url);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
		// Get the initial page
		$response = curl_exec($curl);
		libxml_use_internal_errors(true);
		
		// Filter out wrong responses
		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200)
			return false;
		
		// Parse the HTML dom
		$dom = new DOMDocument;
		$dom->loadHTML($response);
		
		// Find the inputs
		$inputs = $dom->getElementsByTagName('input');
		$postfields = array();
		foreach ($inputs as $input)
		{
			// Get attributes
			$name = $input->getAttribute('name');
			$type = $input->getAttribute('type');
			$value = $input->getAttribute('value');
			
			if ($type == 'text')
				$postfields[$name] = $search;
			else
				$postfields[$name] = $value;
		}
		
		// Post the request
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
		$response = curl_exec($curl);
		$myurl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		
		// Check for invalid responses
		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200)
			return false;
		
		// Return the response
		return $response;
	}
	
	/**
	 * Extract a list of players
	 */
	protected function extractPlayerList($curl, $response)
	{
		// Check the url if we can read it
		$myurl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		if (substr($myurl, 0, strlen($this->find_url)) != $this->find_url)
			return false;
		
		$dom = phpQuery::newDocumentHTML($response);
		
		// Extract list of players
		$result = array();
		$rows = pq('table.ruler tbody tr');
		foreach ($rows as $row)
		{
			$columns = pq($row)->find('td');
			
			// First element contains the link with the name and link
			$link = pq($columns->get(0))->find('a');
			$name = $link->text();
			$url = $this->getPlayerUrl($link->attr('href'));
			$id = pq($columns->get(1))->text();
			$result[] = array('name' => $name, 'id' => $id, 'url' => $url);
		}

		return $result;
	}
	
	/**
	 * Extract player data
	 */
	protected function extractPlayer($curl, $response)
	{
		// Check the url if we can read it
		$myurl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		if (substr($myurl, 0, strlen($this->player_url)) != $this->player_url)
			return false;
		
		$dom = phpQuery::newDocumentHTML($response);
		
		// Finding the name and id of the player is quite dirty...
		$player = array();
		$caption = pq('table.ruler caption')->text();
		if (preg_match('/Ranking van ([^\)]+) \((\d+)\)/', $caption, $matches))
		{
			$player['name'] = $matches[1];
			$player['id'] = $matches[2];
		}
		
		// Find the profile link
		$profileurls = pq('a.icon.profile');
		if ($profileurls->length() > 0)
			$player['profile'] = $this->base_url . substr($profileurls->attr('href'), 1);
		
		// Return the result
		return $player;
	}
	
	/**
	 * Extract profile page data
	 */
	protected function extractProfile($curl, $response)
	{
		// Check the url if we can read it
		$myurl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
		$refurl = $this->profile_url . $this->profile_entry;
		if (substr($myurl, 0, strlen($refurl)) != $refurl)
			return false;
		
		$dom = phpQuery::newDocumentHTML($response);
		
		// Find the name
		$name = pq("div.title h3")->attr("title");
		$ranks = pq("span.playerlevel");
		$singles = $ranks->eq(0)->text();
		$doubles = $ranks->eq(1)->text();
		$mixed = $ranks->eq(2)->text();
		
		// Return the result
		return array(
			'name' => $name,
			'rank' => array(
				'singles' => $singles,
				'doubles' => $doubles,
				'mixed' => $mixed
			)
		);
	}

	/**
	 * Extract competition data
	 *
	 * @param string $url		The URL containing the data
	 */
	public function extractCompetitionData($url)
	{
		$curl = curl_init();
		
		// Setup CURL
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		
		// Request the HTML
		$response = curl_exec($curl);
		if (!$response)
			throw new Exception(curl_error($curl));
		
		// Parse the response
		$dom = phpQuery::newDocumentHTML($response);
		$table = pq('.teammatch-table');
		
		$result = new stdClass();
		$result->calendar = $this->base_url . $table->find('a.icon-calendar.caption-icalendar')->attr('href');
		
		// Extract all matches
		$result->matches = array();
		$matches = $table->find('tbody tr');
		foreach ($matches as $match)
		{
			$m = pq($match);
			
			// Get the date/time
			$datetime = explode(' ', $m->find('td.plannedtime')->text());
			$datetime = DateTime::createFromFormat('d/m/Y H:i', $datetime[1] . ' ' . $datetime[2]);
			
			// Get the team names
			$teamnames = $m->find('.teamname');
			$home = $teamnames->eq(0)->text();
			$visitors = $teamnames->eq(1)->text();
			
			// Get the score
			$scores = explode("-", $m->find('span.score')->text());
			if (!$scores || count($scores) != 2)
				$scores = false;
			else
				$scores = array_map('intval', $scores);
			
			// Extract the location
			$l = $m->find('td')->eq(11)->find('a');
			$location = $l->text();
			$locationurl = $this->getLocationUrl($l->attr('href'));
			
			$result->matches[] = (object) array(
				'datetime' => $datetime,
				'home' => $home,
				'visitors' => $visitors,
				'scores' => $scores,
				'location' => $location,
				'locationurl' => $locationurl
			);
		}
		
		return $result;
	}
}