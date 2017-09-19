<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonVlaanderenController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		return parent::display($cachable, $urlparams);
	}
	
	/**
	 * Procedure for when a user has no access
	 */
	protected function noAccess()
	{
		$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
		$this->setRedirect(JRoute::_('index.php', false));
		return false;
	}
}