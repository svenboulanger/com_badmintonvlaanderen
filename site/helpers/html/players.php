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
abstract class JHtmlPlayers
{
	
	/**
	 * Generate the HTML for an avatar
	 *
	 * @param	string	$value	The name of the avatar image2wbmp
	 *
	 * @return	mixed	string/void
	 */
	public static function avatar($value)
	{
		return "<img src=\"$value\" class=\"img img-thumbnail\" style=\"height:256px\" />";
	}

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
	 * Get the player groups
	 *
	 * @param	mixed	$value	Value of the field
	 *
	 * @return string
	 */
	public static function playergroups($value)
	{
		if (!is_array($value))
			return '---';
		
		$html = array();
		
		// Get the group names
		$groups = JHtmlPlayers::getUserGroups();
		foreach ($groups as $k => $v)
		{
			if (in_array($v->value, $value)) {
				$html[] = "<span class=\"label label-primary\">{$v->text}</span>";
			}
		}
		return implode(' ', $html);
	}
	
	/**
	 * Get a player name
	 *
	 * @param mixed $value		The ID of the player
	 *
	 * @return string
	 */
	public static function playerlist($value)
	{
		if (!is_numeric($value))
			return '---';

		// Get the player name
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('CONCAT_WS(" ",lastname,firstname)')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('id') . '=' . $db->quote($value));
		$name = $db->setQuery($query)->loadResult();
		if ($name)
			return "<span class=\"label label-default\">$name</span>";
		else
			return '---';
	}
	
	/*
	 * Get the player allowed or not
	 *
	 * @param	mixed	$value	Value of the field
	 *
	 * @return string
	 */
	public static function allowed($value)
	{
		if ($value == 1)
			return '<i class="fa fa-check-circle"></i>';
		else
			return '<i class="fa fa-times-circle"></i>';
	}
	
	/*
	 * Show competitive option
	 *
	 * @param	mixed	$value	The value of the field
	 *
	 * @return	string
	 */
	public static function competitive($value)
	{
		if ($value == 1)
			return '<span class="label label-primary">' . JText::_('COM_BADMINTON_SEARCH_COMPETITIVE') . '</span>';
		else
			return '<span class="label label-primary">' . JText::_('COM_BADMINTON_SEARCH_RECREATIVE') . '</span>';
	}
	
	/**
	 * Show gender
	 *
	 * @return string
	 */
	public static function gender($value)
	{
		if ($value == 0)
			return '<span class="label label-primary">' . JText::_('COM_BADMINTON_PLAYER_GENDER_UNSPECIFIED') . '</span>';
		else if ($value == 1)
			return '<span class="label label-primary">' . JText::_('COM_BADMINTON_PLAYER_GENDER_MALE') . '</span>';
		else
			return '<span class="label label-primary">' . JText::_('COM_BADMINTON_PLAYER_GENDER_FEMALE') . '</span>';
	}
	
	/**
	 * Get a list of the user groups.
	 *
	 * @return	array
	 * @since	1.6
	 */
	protected static function getUserGroups()
	{
		// Initialise variables.
		$db		= JFactory::getDBO();
		$query	= $db->getQuery(true)
			->select('a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level, a.parent_id')
			->from('#__usergroups AS a')
			->leftJoin('`#__usergroups` AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->group('a.id')
			->order('a.lft ASC');

		$db->setQuery($query);
		$options = $db->loadObjectList();

		return $options;
	}
}