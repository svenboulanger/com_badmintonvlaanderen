<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonModelMailall extends JModelLegacy
{
	/**
	 * Get the form for a group
	 *
	 * @param 	$data
	 * @param	$loadData
	 *
	 * @return JForm	The form used to the data
	 */
	public function getForm($data = array(), $loadData = true) {

		// Load form/email.xml
		$form = new JForm('Email');
		if (!$form->loadFile(JPATH_COMPONENT_SITE . '/models/forms/email.xml'))
			$form = null;
		$context = "$this->option.mail.all";
		if (empty($form)) {
			JFactory::getApplication()->enqueueMessage('No form');
			return false;
		}
		
		// Disable fields depending on access control
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
		
		foreach ($form->getFieldset() as $field)
			$form->setFieldAttribute($field->name, 'name', 'jform[' . $field->name . ']');

		// Find default values from posted requests if available
		$data = $app->getUserState("$context.emaildata", array());
		$form->bind($data);

		return $form;
	}
}