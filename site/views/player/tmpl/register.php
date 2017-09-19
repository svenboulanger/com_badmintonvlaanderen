<?php
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.framework');
?>
<div class="well">
	<form class="form-horizontal" action="<?= JRoute::_('index.php?option=com_badminton&layout=register') ?>"
		method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

		<fieldset>
			<legend><?= JText::_('COM_BADMINTON_PLAYER_REGISTRATION'); ?></legend>
				<!-- Show image -->
				<img class="img img-thumbnail center-block" style="height:256px" src="<?= JRoute::_($this->avatar); ?>" />
				
				<!-- Show basic information -->
				<?php foreach ($this->form->getGroup('main') as $field) : ?>
					<?php if (!$field->hidden) : ?>
						<?php if ($field->getAttribute('type') == 'spacer') : ?>
							<div class="form-group">
								<div class="col-lg-12"><?= $field->label; ?></div>
							</div>
						<?php else : ?>
							<div class="form-group">
								<div class="col-lg-2"><?= $field->label; ?></div>
								<div class="col-lg-10"><?= $field->input; ?></div>
								<?php if ($this->hasRequest($field)) : ?>
									<div class="col-lg-12 alert alert-warning" id="<?= $field->getAttribute('name') ?>"><?= $this->getRequest($field); ?></div>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php else : ?>
					<?= $field->input ?>
					<?php endif; ?>
				<?php endforeach; ?>
				
			<div class="form-group">
				<div class="col-lg-2"><label for="jform_remark" class="hasPopover" title data-content="<?= JText::_('COM_BADMINTON_PLAYER_REMARK_DESC'); ?>" data-original-title="<?= JText::_('COM_BADMINTON_PLAYER_REMARK'); ?>"><?= JText::_('COM_BADMINTON_PLAYER_REMARK'); ?></label></div>
				<div class="col-lg-10"><textarea name="jform[remark]" id="jform_remark" class="form-control" rows="3" id="textArea"></textarea></div>
			</div>
				
			<div class="col-lg-10 col-lg-offset-2">
				<a href="javascript:void(0);" class="btn btn-success" id="register" onclick="Joomla.submitbutton('player.register');"><?= JText::_('JSAVE'); ?></a>
				<a href="javascript:void(0);" class="btn btn-warning" id="cancel" onclick="Joomla.submitbutton('player.cancel');"><?= JText::_('JCANCEL'); ?></a>
			</div>
		</fieldset>
		
		<input type="hidden" name="task" value="player.register" />
		<?= JHtml::_('form.token'); ?>
	</form>
</div>