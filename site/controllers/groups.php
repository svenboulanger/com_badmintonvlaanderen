<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonControllerGroups extends JControllerAdmin
{
	// Get the model
	public function getModel($name = 'Group', $prefix = 'BadmintonModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
}