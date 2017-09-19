<?php
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.framework');
?>
<script type="text/javascript">
function acceptRequest(fname, name, value, isOption) {
	if (typeof isOption === 'undefined')
		isOption = false;
	if (!isOption)
		jQuery('input[name=\'' + fname + '\']').val(value);
	else
	{
		var sel = jQuery('select[name=\'' + fname + '\']');
		sel.val(sel.find('option[value=\'' + value + '\']').val());
	}
	var inp = jQuery('input[name=\'jform[properties]\']');
	var properties = JSON.parse(inp.val());
	if (!('accepted' in properties))
		properties.accepted = {};
	delete properties.request[name];
	properties.accepted[name] = value;
	inp.val(JSON.stringify(properties));
	jQuery('div#' + name).hide();
}
function declineRequest(name, value) {
	var inp = jQuery('input[name=\'jform[properties]\']');
	var properties = JSON.parse(inp.val());
	if (!('denied' in properties))
		properties.denied = {};
	delete properties.request[name];
	properties.denied[name] = value;
	inp.val(JSON.stringify(properties));
	jQuery('div#' + name).hide();
}
</script>

<div class="well">
	<form class="form-horizontal" action="<?= JRoute::_('index.php?option=com_badminton&layout=edit&id=' . (int)$this->item->id); ?>"
		method="post" name="adminForm" id="adminForm" enctype="multipart/form-data">

		<fieldset>
			<legend><?= JText::_('COM_BADMINTON_PLAYER_DETAILS'); ?></legend>
				<!-- Show image -->
				<img class="img img-thumbnail center-block" style="height:256px" src="<?= JRoute::_($this->avatar); ?>" />
				<?php if (isset($this->request['avatar'])) : ?>
				<div class="form-group">
					<div class="col-lg-12 alert alert-warning" id="avatar"><?= $this->getAvatarRequest() ?></div>
				</div>
				<?php endif ?>
			
				<?php foreach ($this->form->getFieldset() as $field) : ?>
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
				
			<div class="col-lg-10 col-lg-offset-2">
				<a href="javascript:void(0);" class="btn btn-success" id="save" onclick="Joomla.submitbutton('player.save');"><?= JText::_('JSAVE'); ?></a>
				<a href="javascript:void(0);" class="btn btn-warning" id="cancel" onclick="Joomla.submitbutton('player.cancel');"><?= JText::_('JCANCEL'); ?></a>
			</div>
		</fieldset>
		
		<input type="hidden" name="task" value="player.edit" />
		<?= JHtml::_('form.token'); ?>
	</form>
</div>