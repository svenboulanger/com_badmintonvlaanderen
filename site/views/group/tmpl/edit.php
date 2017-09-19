<?php
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.framework');
?>

<form class="form-horizontal" action="<?= JRoute::_('index.php?option=com_badminton&layout=edit&id=' . (int)$this->item->id); ?>"
		method="post" name="adminForm" enctype="multipart/form-data" id="adminForm">
	<div class="well">
		<fieldset>
			<legend><?= JText::_('COM_BADMINTON_GROUP_DETAILS'); ?></legend>
				<?php
				foreach ($this->form->getFieldset() as $field) {
					if (!$field->hidden) { ?>
				<div class="form-group">
					<div class="col-lg-2"><?= $field->label; ?></div>
					<div class="col-lg-10"><?= $field->input; ?></div>
				</div>
				<?php
					} else { ?>
					<?= $field->input ?>
				<?php 
					} 
				} ?>
				
			<div class="col-lg-10 col-lg-offset-2">
				<a href="javascript:void(0);" class="btn btn-success" id="save" onclick="Joomla.submitbutton('group.save');"><?= JText::_('JSAVE'); ?></a>
				<a href="javascript:void(0);" class="btn btn-warning" id="cancel" onclick="Joomla.submitbutton('group.cancel');"><?= JText::_('JCANCEL'); ?></a>
			</div>
		</fieldset>
		
		<input type="hidden" name="task" value="group.edit" />
		<?= JHtml::_('form.token'); ?>
	</div>
	
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
