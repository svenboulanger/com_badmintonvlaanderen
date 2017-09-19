<?php
defined('_JEXEC') or die('Restricted access');

JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.framework');
?>

<h1><?= JText::_('COM_BADMINTON_MAIL_ALL'); ?></h1>

<form action="<?= JURI::current(); ?>" method="post" id="adminForm" name="adminForm">

	<div id="contentemail">
		<?php if ($this->form) :
		foreach ($this->form->getFieldset() as $field) { ?>
			<div class="col-lg-2"><?= $field->label; ?></div>
			<div class="col-lg-10"><?= $field->input; ?></div>
		<?php } endif ?>
		<div class="col-lg-12">
			<a href="javascript:void(0);" class="btn btn-primary" id="email" onclick="Joomla.submitbutton('mailall.send');"><?= JText::_('COM_BADMINTON_EMAIL_SEND'); ?></a>
		</div>
	</div>

	<input type="hidden" name="task" value="" />
	<input type="hidden" name="option" value="com_badminton" />
	<?= JHtml::_('form.token'); ?>
</form>
