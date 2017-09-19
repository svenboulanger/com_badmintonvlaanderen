<?php
defined('_JEXEC') or die('Restricted access');

class BadmintonViewGroup extends JViewLegacy
{
	protected $form = null;
	
	/**
	 * Check if the current user is the player
	 *
	 * @param int $id	The ID of the player
	 */
	protected function isUser($user, $id)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('1')
			->from($db->quoteName('#__badminton_players'))
			->where($db->quoteName('id') . '=' . $db->quote($id) . ' AND ' . $db->quoteName('user_id') . '=' . $db->quote($user->id));
		$db->setQuery("SELECT EXISTS($query)");
		return ($db->loadResult() > 0);
	}
	
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
		
		$this->canViewMember = $user->authorise('player.view', 'com_badminton') || $user->authorise('player.edit', 'com_badminton');
		$this->canViewMemberList = $user->authorise('player.viewlist', 'com_badminton') || $this->canViewMember;
		$this->params = $app->getParams('com_badminton');

		// Get form and item
		$this->form = $this->get('Form');
		$this->item = $this->get('Item');
		if ($user->authorise('group.email', 'com_badminton') ||
			($user->authorise('group.emailown', 'com_badminton') && $this->isUser($user, $this->item->ownerid)))
			$this->emailform = $this->get('EmailForm');
		else
			$this->emailform = null;
		
		// Get the players that were not contacted and then clear that information
		$nccontext = "com_badminton.edit.group.notcontacted";
		$this->notcontacted = $app->getUserState($nccontext, null);
		$app->setUserState($nccontext, null);

		// Get the member information
		if ($this->canViewMember) {
			$this->memberinfo = array('member.id' => 'memberid', 'member.home' => 'memberhome', 'member.mobile' => 'membermobile', 'member.email' => 'memberemail');
			$this->memberdisplay = array('HOME' => 'memberhome', 'MOBILE' => 'membermobile', 'EMAIL' => 'memberemail');
			$this->guardianinfo = array('guardian.id' => 'guardianid', 'guardian.home' => 'guardianhome', 'guardian.mobile' => 'guardianmobile', 'guardian.email' => 'guardianemail');
			$this->guardiandisplay = array('HOME' => 'guardianhome', 'MOBILE' => 'guardianmobile', 'EMAIL' => 'guardianemail');
			$this->guardian2info = array('guardian2.id' => 'guardian2id', 'guardian2.home' => 'guardian2home', 'guardian2.mobile' => 'guardian2mobile', 'guardian2.email' => 'guardian2email');
			$this->guardian2display = array('HOME' => 'guardian2home', 'MOBILE' => 'guardian2mobile', 'EMAIL' => 'guardian2email');
		}
		else
		{
			$this->memberinfo = array('member.id' => 'memberid');
			$this->memberdisplay = array();
			$this->guardianinfo = array();
			$this->guardiandisplay = array();
			$this->guardian2info = array();
			$this->guardian2display = array();
		}
		$this->members = $this->getMemberList();

		// Add styles
		$document = JFactory::getDocument();
		$document->addStyleSheet(JRoute::_('media/com_badminton/css/jquery-ui.min.css'));

		// Add scripts
		JHtml::_('jquery.framework');
		$document->addScript(JRoute::_('media/com_badminton/js/jquery-ui.min.js'));
		$document->addScriptDeclaration('window.onload=function(){jQuery(".datepicker").datepicker({dateFormat:"yy-mm-dd"});jQuery("#contentemail").hide();}');
		
		// Check for errors
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		$this->setDocument();

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

		// Add a script to ask for confirmation before deleting
		JHtml::_('jquery.framework');
		$document->addScript(JRoute::_('media/com_badminton/js/jquery-ui.min.js'));
		$document->addStyleSheet(JRoute::_('media/com_badminton/css/jquery-ui.min.css'));
		
		// Add declaration
		if ($this->canViewMember) {
			$script = array("function toggleCheck(id){",
				"jQuery('#member'+id).toggleClass('active');",
				"};");
			$document->addScriptDeclaration(implode($script));
		}
		$script = array("function toggleTab(id){",
			"jQuery('ul.nav li').removeClass('active');",
			"jQuery('[id^=content]').hide();",
			"jQuery('#tab'+id).addClass('active');",
			"jQuery('#content'+id).show();",
			"};");
		$document->addScriptDeclaration(implode($script));

		// Add some styling
		$style = array();
		$style[] = "span.checked{display:none;}";
		$style[] = ".active span.checked{display:inline;}";
		$document->addStyleDeclaration(implode($style));
	}
	
	/**
	 * Generate the HTML to show a group member
	 *
	 * @param	stdClass $member	The member to be shown
	 */
	protected function showMember($member)
	{
		if (!$this->canViewMemberList)
			return '';
		$html = array();
		$src = $this->params->get('avatar_path', '') . '/' . (!empty($member->avatar) ? $member->avatar : 'default.png');
		
		$active = '';
		if ($this->notcontacted != null)
		{
			if (!in_array($member->memberid, $this->notcontacted))
				$active = ' active';
		}

		$html[] = "<a href=\"javascript:void(0);\" class=\"media list-group-item$active\" id=\"member{$member->memberid}\" onclick=\"toggleCheck({$member->memberid});\" style=\"margin-top:0;\">";
		$html[] = "<div class=\"pull-left\"><img src=\"$src\" class=\"img-thumbnail\" style=\"max-width:128px;max-height:128px;\" /></div>";
		$html[] = "<h4 class=\"list-group-item-heading\">{$member->membername}<span class=\"checked\"> " . JText::_('COM_BADMINTON_GROUP_PLAYER_CONTACTED') . "</span></h4>";
		
		// Show player profile info for contact information
		if (count($this->memberdisplay) > 0)
		{
			
			$html[] = "<p class=\"list-group-item-text\">";
			$html[] = '<div class="memberinfo" id="memberinfo' . $member->memberid . '">';
			$tags = array();
			foreach ($this->memberdisplay as $key => $value)
				$tags[] = $this->smallinfo($key, $member->$value);
			$html[] = implode(' ', $tags);
			$html[] = '</div>';

			if (count($this->guardiandisplay) > 0 && !empty($member->guardianid)) {
				$html[] = '<div class="guardianinfo" id="guardianinfo' . $member->guardianid . '">';
				
				// Add name
				$tags = array();
				$tags[] = $this->smallinfo('GUARDIAN', $member->guardianname, array('class' => 'label-info'));
				foreach ($this->guardiandisplay as $key => $value)
					$tags[] = $this->smallinfo($key, $member->$value);
				$html[] = implode(' ', $tags);
				$html[] = '</div>';
			}
			
			if (count($this->guardian2display) > 0 && !empty($member->guardian2id)) {
				$html[] = '<div class="guardianinfo" id="guardian2info' . $member->guardianid . '">';
				
				// Add name
				$tags = array();
				$tags[] = $this->smallinfo('GUARDIAN', $member->guardian2name, array('class' => 'label-info'));
				foreach ($this->guardian2display as $key => $value)
					$tags[] = $this->smallinfo($key, $member->$value);
				$html[] = implode(' ', $tags);
				$html[] = '</div>';
			}
			$html[] = "</p>";
		}
		$html[] = "</a>";
		return implode($html);
	}
	
	/**
	 * Generate a navigation pill style item
	 */
	public function smallinfo($desc, $value, $attr = array('class' => 'label-default'))
	{
		// Do not display what doesn't exist
		if (empty($value))
			return '';

		if (!isset($attr['class']))
			$attr['class'] = 'label';
		else
			$attr['class'] .= ' label';

		$attributes = '';
		foreach ($attr as $k => $v)
			$attributes .= " $k=\"$v\"";
		$label = JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($desc));
		
		// Create an array
		return "<span$attributes>$label: $value</span>";
	}

	/**
	 * Get a list of members from a record
	 *
	 * @param mixed $record		The record containing the group information
	 * @return array			The members as an array of objects
	 */
	public function getMemberList()
	{
		// Init
		$db = JFactory::getDbo();
		$user = JFactory::getUser();
		$app = JFactory::getApplication();

		// All values that should be selected
		$selection = array('member.avatar');
		$selection[] = 'CONCAT_WS(" ",member.lastname,member.firstname) as membername';
		$selection[] = 'CONCAT_WS(" ",guardian.lastname,guardian.firstname) as guardianname';
		$selection[] = 'CONCAT_WS(" ",guardian2.lastname,guardian2.firstname) as guardian2name';
		foreach ($this->memberinfo as $key => $value)
			$selection[] = $db->quoteName($key, $value);
		foreach ($this->guardianinfo as $key => $value)
			$selection[] = $db->quoteName($key, $value);
		foreach ($this->guardian2info as $key => $value)
			$selection[] = $db->quoteName($key, $value);
		
		// Search the database for all members
		$query = $db->getQuery(true);
		$query->select($selection)
			->from($db->quoteName('#__badminton_group_members', 'm'))
			->leftJoin($db->quoteName('#__badminton_players', 'member') . ' ON ' . $db->quoteName('m.playerid') . '=' . $db->quoteName('member.id'))
			->leftJoin($db->quoteName('#__badminton_players', 'guardian') . ' ON ' . $db->quoteName('member.guardian') . '=' . $db->quoteName('guardian.id'))
			->leftJoin($db->quoteName('#__badminton_players', 'guardian2') . ' ON ' . $db->quoteName('member.guardian2') . '=' . $db->quoteName('guardian2.id'))
			->where($db->quoteName('m.groupid') . '=' . $db->quote($this->item->id));
		return $db->setQuery($query)->loadObjectList();
	}
}