<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Users Html Helper
 *
 * @since  1.6
 */
abstract class JHtmlGroups
{

	/**
	 * Get the sanitized value
	 *
	 * @param   mixed  $value  Value of the field
	 *
	 * @return  mixed  String/void
	 *
	 * @since   1.6
	 */
	public static function value($value)
	{
		if (is_string($value))
		{
			$value = trim($value);
		}

		if (empty($value))
		{
			return '---';
		}

		elseif (!is_array($value))
		{
			return htmlspecialchars($value);
		}
		
		return '';
	}
	
	/**
	 * Get the space symbol
	 *
	 * @param   mixed  $value  Value of the field
	 *
	 * @return  string
	 *
	 * @since   1.6
	 */
	public static function spacer($value)
	{
		return '';
	}

	/**
	 * Display a playerlist item
	 */
	public static function playerlist($value)
	{
		// Search the database for a player list
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('CONCAT_WS(" ",lastname,firstname)')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('id') . '=' . $db->quote($value));
		$result = $db->setQuery($query)->loadResult();
		return $result;
	}
}