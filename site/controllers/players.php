<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonControllerPlayers extends JControllerAdmin
{
	// Get the model
	public function getModel($name = 'Player', $prefix = 'BadmintonModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}
	
	/**
	 * Allows a player.
	 *
	 * @return  void
	 *
	 */
	public function allow() {
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request
		$app = JFactory::getApplication();
		$cid = $app->input->get('cid', array(), 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		} else {
			
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Allow players
			if ($model->allow($cid)) {
				$this->setMessage(JText::plural($this->text_prefix . '_N_PLAYERS_ALLOWED', count($cid)));
			} else {
				$this->setMessage($model->getError(), 'error');
			}
		}
		
		// Redirect to list
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
	
	/**
	 * Blocks a player.
	 *
	 * @return  void
	 *
	 */
	public function block() {
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request
		$app = JFactory::getApplication();
		$cid = $app->input->get('cid', array(), 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		} else {
			
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Block players
			if ($model->block($cid)) {
				$this->setMessage(JText::plural($this->text_prefix . '_N_PLAYERS_BLOCKED', count($cid)));
			} else {
				$this->setMessage($model->getError(), 'error');
			}
		}
		
		// Redirect to list
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
	
	/*
	 * Send email to the list of users with random verification string
	 */
	public function invite() {
			
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request
		$app = JFactory::getApplication();
		$cid = $app->input->get('cid', array(), 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		} else {
			
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Block players
			if ($model->invite($cid)) {
				$this->setMessage(JText::plural($this->text_prefix . '_N_PLAYERS_INVITED', count($cid)));
			} else {
				$this->setMessage($model->getError(), 'error');
			}
		}
		
		// Redirect to list
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/*
	 * Make a player competitive
	 */
	public function competitive() {
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request
		$app = JFactory::getApplication();
		$cid = $app->input->get('cid', array(), 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		} else {
			
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Block players
			if ($model->competitive($cid)) {
				$this->setMessage(JText::plural($this->text_prefix . '_N_PLAYERS_MADE_COMPETITIVE', count($cid)));
			} else {
				$this->setMessage($model->getError(), 'error');
			}
		}
		
		// Redirect to list
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/*
	 * Make a player recreative
	 */
	public function recreative() {
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request
		$app = JFactory::getApplication();
		$cid = $app->input->get('cid', array(), 'array');
		
		if (!is_array($cid) || count($cid) < 1) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		} else {
			
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Block players
			if ($model->recreative($cid)) {
				$this->setMessage(JText::plural($this->text_prefix . '_N_PLAYERS_MADE_RECREATIVE', count($cid)));
			} else {
				$this->setMessage($model->getError(), 'error');
			}
		}
		
		// Redirect to list
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}

	/**
	 * Add players to a group
	 */
	public function groupIn() {
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		$context = "badminton.list.admin.player";
		
		// Get items to remove from the request
		$app = JFactory::getApplication();
		$cid = $app->input->get('cid', array(), 'array');
		$gid = $app->getUserStateFromRequest("$context.players.grouptool", "gid", 0, 'int');
		
		if (!is_array($cid) || count($cid) < 1) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		} else if ($gid == 0) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_GROUP_SELECTED'), 'error');
		} else {
			
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Block players
			if ($model->groupIn($cid, $gid)) {
				$this->setMessage(JText::plural($this->text_prefix . '_N_PLAYERS_GROUPED', count($cid)));
			} else {
				$this->setMessage($model->getError(), 'error');
			}
		}
		
		// Redirect to list
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
	
	/**
	 * Ungroup players
	 */
	public function ungroupFrom() {
		
		// Check for request forgeries
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));
		
		// Get items to remove from the request
		$app = JFactory::getApplication();
		$cid = $app->input->get('cid', array(), 'array');
		$gid = $app->getUserStateFromRequest("$context.players.grouptool", "gid", 0, 'int');
		
		if (!is_array($cid) || count($cid) < 1) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_ITEM_SELECTED'), 'error');
		} else if ($gid == 0) {
			$app->enqueueMessage(JText::_($this->text_prefix . '_NO_GROUP_SELECTED'), 'error');
		} else {
			
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			jimport('joomla.utilities.arrayhelper');
			JArrayHelper::toInteger($cid);
			
			// Block players
			if ($model->ungroupFrom($cid, $gid)) {
				$this->setMessage(JText::plural($this->text_prefix . '_N_PLAYERS_UNGROUPED', count($cid)));
			} else {
				$this->setMessage($model->getError(), 'error');
			}
		}
		
		// Redirect to list
		$this->setRedirect(JRoute::_('index.php?option=' . $this->option . '&view=' . $this->view_list, false));
	}
}