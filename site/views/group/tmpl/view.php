<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
JLoader::register('JHtmlGroups', JPATH_COMPONENT . '/helpers/html/groups.php');
JHtml::register('groups.spacer', array('JHtmlGroups', 'spacer'));
JHtml::register('groups.playerlist', array('JHtmlGroups', 'playerlist'));

?>

<?php $fields = $this->form->getFieldset(); ?>
<?php if (count($fields)) : ?>
<fieldset id="users-profile-custom">
	<legend><?= JText::_('COM_BADMINTON_GROUP_DETAILS'); ?></legend>
	<dl class="dl-horizontal">
	<?php foreach ($fields as $field):
		if (!$field->hidden) :?>
		<dt><?= $field->title; ?></dt>
		<dd>
			<?php if (JHtml::isRegistered('groups.' . $field->id)):?>
				<?php echo JHtml::_('groups.' . $field->id, $field->value);?>
			<?php elseif (JHtml::isRegistered('groups.' . $field->fieldname)):?>
				<?php echo JHtml::_('groups.' . $field->fieldname, $field->value);?>
			<?php elseif (JHtml::isRegistered('groups.' . $field->type)):?>
				<?php echo JHtml::_('groups.' . $field->type, $field->value);?>
			<?php else:?>
				<?php echo JHtml::_('groups.value', $field->value);?>
			<?php endif;?>
		</dd>
		<?php endif;?>
	<?php endforeach;?>
	</dl>
</fieldset>
<?php endif;?>

<form class="form-horizontal" action="<?= JRoute::_('index.php?option=com_badminton&layout=edit&id=' . (int)$this->item->id); ?>"
		method="post" name="adminForm" enctype="multipart/form-data" id="adminForm">
	<div class="col-lg-12 row">
		<ul class="nav nav-tabs" style="margin-bottom: 15px;">
			<li class="active" id="tabmembers"><a href="javascript:void(0);" onclick="toggleTab('members')"><?= JText::_('COM_BADMINTON_GROUP_MEMBERS'); ?></a></li>
			<?php if ($this->emailform) : ?>
			<li id="tabemail"><a href="javascript:void(0);" onclick="toggleTab('email')"><?= JText::_('COM_BADMINTON_GROUP_EMAIL'); ?></a></li>
			<?php endif ?>
		</ul>

		<div id="contentmembers">
			<div class="list-group"><?php
				foreach ($this->members as $member)
					echo $this->showMember($member);
			?></div>
		</div>
		<div id="contentemail">
			<?php if ($this->emailform) :
			foreach ($this->emailform->getFieldset() as $field) { ?>
				<div class="col-lg-2"><?= $field->label; ?></div>
				<div class="col-lg-10"><?= $field->input; ?></div>
			<?php } endif ?>
			<div class="col-lg-12">
				<a href="javascript:void(0);" class="btn btn-primary" id="email" onclick="Joomla.submitbutton('group.sendemail');"><?= JText::_('COM_BADMINTON_EMAIL_SEND'); ?></a>
			</div>
		</div>
	</div>
</form>