<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonViewPlayer extends JViewLegacy
{
	protected $form = null;
	
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
		$params = $app->getParams('com_badminton');

		// Get form and item
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		
		// Get request variables
		if (!empty($this->item->properties))
		{
			$properties = json_decode($this->item->properties, true);
			if (isset($properties['request']))
				$this->request = $properties['request'];
		}
		
		// Get the avatar configuration
		$this->avatar = $params->get('avatar_path', '');
		$item = $this->item->get('avatar');
		if (empty($item))
			$item = "default.png";
		$this->avatar = JRoute::_(str_replace("//", "/", $params->get('avatar_path') . '/' . $item));
		if (isset($this->request['avatar']))
		{
			$this->original_avatar = $this->avatar;
			$this->avatar = JRoute::_(str_replace("//", "/", $params->get('avatar_path') . '/' . $this->request['avatar']));
		}

		// Add styles
		$document = JFactory::getDocument();
		$document->addStyleSheet(JRoute::_('media/com_badminton/css/jquery-ui.min.css'));

		// Add scripts
		JHtml::_('jquery.framework');
		$document->addScript(JRoute::_('media/com_badminton/js/jquery-ui.min.js'));
		$document->addScriptDeclaration('window.onload=function(){jQuery(".datepicker").datepicker({dateFormat:"yy-mm-dd",showButtonPanel:true});}');
		
		// Check for errors
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		// Display the template
		parent::display($tpl);
	}
	
	/*
	 * Find out if a request for change has been made for this field
	 *
	 * @param JFormField $field		The field
	 *
	 * @return bool		True if a request has been made, false else
	 */
	protected function hasRequest($field)
	{
		$name = $field->getAttribute('name');
		return isset($this->request[$name]);
	}
	
	/*
	 * Get the HTML for a request handling
	 *
	 * @param JFormField $field		The field
	 *
	 * @return html		The html
	 */
	protected function getRequest($field)
	{
		// Get field parameters
		$name = $field->getAttribute('name');
		$fname = $field->__get('name');
		$type = $field->getAttribute('type');
		$isOption = $type === 'list' ? 'true' : 'false';
		$req = $this->request[$name];
		
		// Build HTML output
		$html = "<span>" . JText::sprintf('COM_BADMINTON_PLAYER_REQUEST_VALUE', strtolower(JText::_('COM_BADMINTON_PLAYER_' . strtoupper($name))), $req) . '</span> ';
		$html .= "<a href=\"javascript:void(0);\" onclick=\"acceptRequest('$fname','$name','$req',$isOption)\" class=\"btn btn-primary\" id=\"accept$name\"><i class=\"fa fa-check-circle\"></i> " . JText::_('COM_BADMINTON_PLAYER_REQUEST_ACCEPT') . "</a> ";
		$html .= "<a href=\"javascript:void(0);\" onclick=\"declineRequest('$name','$req',$isOption)\" class=\"btn btn-default\" id=\"decline$name\"><i class=\"fa fa-times-circle\"></i> " . JText::_('COM_BADMINTON_PLAYER_REQUEST_DECLINE') . "</a>";
		return $html;
	}
	
	/*
	 * Get the HTML for requesting a new avatar
	 *
	 * @return html		The html
	 */
	protected function getAvatarRequest()
	{
		$req = $this->request['avatar'];
		$html = "<span>" . JText::sprintf('COM_BADMINTON_PLAYER_REQUEST_AVATAR') . '</span> ';
		$html .= "<a href=\"javascript:void(0);\" onclick=\"acceptRequest('jform[main][avatar]','avatar','$req',false)\" class=\"btn btn-primary\" id=\"acceptavatar\"><i class=\"fa fa-check-circle\"></i>" . JText::_('COM_BADMINTON_PLAYER_REQUEST_ACCEPT') . "</a> ";
		$html .= "<a href=\"javascript:void(0);\" onclick=\"declineRequest('avatar','$req',false)\" class=\"btn btn-default\" id=\"declineavatar\"><i class\"fa fa-times-circle\"></i>" . JText::_('COM_BADMINTON_PLAYER_REQUEST_DECLINE') . "</a>";
		$html .= "<h3>" . JText::_('COM_BADMINTON_PLAYER_REQUEST_ORIGINAL_AVATAR') . '</h2>';
		$html .= "<img src=\"" . $this->original_avatar . "\" alt=\"New avatar\" />";
		return $html;
	}
}