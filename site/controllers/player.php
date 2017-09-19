<?php
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT . '/helpers/email.php');

class BadmintonControllerPlayer extends JControllerForm
{
	
	/**
	 * Check if the current user can add a player
	 *
	 * @return Boolean		True if the user can add
	 */
	protected function allowAdd($data = array()) {
		return JFactory::getUser()->authorise('player.add', 'com_badminton');
	}
	
	/**
	 * Check if the current user can edit a player
	 *
	 * @return Boolean		True if the user can add
	 */
	protected function allowEdit($data = array(), $key = 'id') {
		$user = JFactory::getUser();
		$canEdit = $user->authorise('player.edit', 'com_badminton');
		$canPayment = $user->authorise('player.payment', 'com_badminton');

		return $canEdit || $canPayment;
	}
	
	/**
	 * Check if the current user can register a player
	 *
	 * @return Boolean		True if the user can register
	 */
	protected function allowRegister($data = array(), $key = 'id') {
		$user = JFactory::getUser();
		$canAdd = $user->authorise('player.add', 'com_badminton');
		$canRegister = $user->authorise('player.register', 'com_badminton');
		
		return $canAdd || $canRegister;
	}
	
	/**
	 * Check if the current user can view a player
	 *
	 * @return Boolean		True if the user can add
	 */
	protected function allowView($data = array(), $key = 'id') {
		return JFactory::getUser()->authorise('player.view', 'com_badminton');
	}

	/**
	 * Method to view a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   12.2
	 */
	public function view($key = null, $urlVar = null) {
		
		$app   = JFactory::getApplication();
		$model = $this->getModel();
		$table = $model->getTable();
		$cid   = $this->input->post->get('cid', array(), 'array');
		$context = "$this->option.edit.$this->context";
		
		// Determine the name of the primary key for the data.
		if (empty($key))
			$key = $table->getKeyName();

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
			$urlVar = $key;
		
		// Get the previous record id (if any) and the current record id.
		$recordId = (int) (count($cid) ? $cid[0] : $this->input->getInt($urlVar));
		$checkin = property_exists($table, 'checked_out');
		
		// Attempt to check-out the new record for editing and redirect.
		if ($checkin && !$model->checkout($recordId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_list
					. $this->getRedirectToListAppend(), false
				)
			);

			return false;
		}
		else
		{
			// Check-out succeeded, push the new record id into the session.
			$app->setUserState($context . '.data', null);

			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);

			return true;
		}
	}

	/**
	 * Method to register a record
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   12.2
	 */
	public function register($key = null, $urlVar = null) {
		
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		$app   = JFactory::getApplication();
		$model = $this->getModel();
		$table = $model->getTable();
		$data  = $this->input->post->get('jform', array(), 'array');
		$checkin = property_exists($table, $table->getColumnAlias('checked_out'));
		$context = "$this->option.edit.$this->context";

		// Determine the name of the primary key for the data.
		if (empty($key))
			$key = $table->getKeyName();

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
			$urlVar = $key;

		// Access check.
		if (!$this->allowRegister($data, $key))
		{
			$this->setError(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=register'
				)
			);

			return false;
		}

		// Validate the posted data.
		// Sometimes the form needs some posted data, such as for plugins and modules.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');
			return false;
		}

		// Test whether the data is valid.
		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof Exception)
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				else
					$app->enqueueMessage($errors[$i], 'warning');
			}

			// Save the data in the session.
			$app->setUserState($context . '.data', $data);

			// Redirect back to the edit screen.
			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=register'
				)
			);

			return false;
		}

		// Attempt to save the data.
		if (!$model->register($validData))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $validData);

			// Redirect back to the edit screen.
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=register'
				)
			);

			return false;
		}
		else
		{
			$this->setMessage(JText::_('JLIB_APPLICATION_SUBMIT_SAVE_SUCCESS'));
			
			// Check-out succeeded, push the new record id into the session.
			$app->setUserState($context . '.data', null);

			// Redirect to register page
			$this->setRedirect(
				JRoute::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=register'
				)
			);
		}

		// Invoke the postSave method to allow for the child class to access the model.
		$this->postSaveHook($model, $validData);
		return true;
	}
}