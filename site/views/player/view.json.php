<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_badminton
 */
defined('_JEXEC') or die('Restricted access');

JLoader::register('BadmintonVlaanderen', JPATH_ROOT . '/components/com_badmintonvlaanderen/helpers/badmintonvlaanderen.php');

class BadmintonVlaanderenViewPlayer extends JViewLegacy
{	
	/**
	 * Extract data from the Badminton Vlaanderen website
	 *
	 * @param $tpl		The template name
	 */
	public function display($tpl = null)
	{
		// Check access levels
		$app = JFactory::getApplication();
		$input = $app->input;
		$search = $input->get('search', false, 'string');
		$ranking = $input->get('ranking', false, 'string');
		
		// $bvl = new BadmintonVlaanderen;
		$bvl = new BadmintonVlaanderen();
		
		// Search
		try
		{
			if ($search)
				echo json_encode($bvl->search($search));
			if ($ranking)
				echo json_encode($bvl->ranking($ranking));
		}
		catch (Exception $ex)
		{
			echo json_encode(array(
				'error' => $ex->getMessage()
			));
		}
	}
}