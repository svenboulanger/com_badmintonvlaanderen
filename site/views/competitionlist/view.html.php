<?php
defined('_JEXEC') or die('Restricted access');

JLoader::register('BadmintonVlaanderen', JPATH_ROOT . '\components\com_badmintonvlaanderen\helpers\badmintonvlaanderen.php');

class BadmintonVlaanderenViewCompetitionList extends JViewLegacy
{
	protected $form = null;
	
	protected $matches = null;
	protected $title = '';
	protected $teamname = '';

	/*
	 * Setup the display
	 *
	 * @param $tpl	The default template
	 */
	public function display($tpl = null)
	{
		// Initialize
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$params = $app->getParams();
		
		// Initialize
		$this->title = $params->get('title');
		$url = $params->get('competitionlisturl', '');
		$this->level = $params->get('competitionlevel', 2);
		$this->type = $params->get('competitiontype', 1);
		$this->division = $params->get('competitiondivision', '');
		$this->series = $params->get('competitionseries', '');
		$this->showtitle = $params->get('showtitle', 1);
		$this->canTeamExchange = $authorised = $user->authorise('badmintonvlaanderen.teamexchange', 'com_badmintonvlaanderen') || count($user->getAuthorisedCategories('com_badmintonvlaanderen', 'badmintonvlaanderen.teamexchange'));

		// Check for errors
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		$this->setDocument();
		
		// Extract data
		$bvl = new BadmintonVlaanderen();
		$this->matchlist = $bvl->extractCompetitionData($url);

		// Display the template
		parent::display($tpl);
	}
	
	/**
	 * Set document
	 */
	protected function setDocument()
	{
		// Initialize
		$user = JFactory::getUser();
		$document = JFactory::getDocument();
		
		if (!empty($this->title))
			$document->setTitle($this->title);

		// Add a script to ask for confirmation before deleting
		JHtml::_('jquery.framework');
		// $document->addScript(JRoute::_('media/com_badmintonvlaanderen/js/jquery-ui.min.js'));
		// $document->addStyleSheet(JRoute::_('media/com_badmintonvlaanderen/css/jquery-ui.min.css'));
		$document->addStyleSheet(JRoute::_('media/com_badmintonvlaanderen/css/badminton.css'));
	}
}