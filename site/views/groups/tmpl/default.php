<?php
defined('_JEXEC') or die('Restricted access');

JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.framework');
?>

<script type="text/javascript">
function orderBy(order, dir, task) {
	document.getElementsByName('list[order]')[0].value = order;
	document.getElementsByName('list[direction]')[0].value = dir;
	document.getElementsByName('task').value = task;
	document.forms.namedItem('adminForm').submit();
}
function clearForm() {
	jQuery('input[name^="filter"]').val('').change();
}
</script>

<h1><?= JText::_('COM_BADMINTON_MANAGER_GROUPS'); ?></h1>

<?php $columnselect = $this->createColumnSelect();
if ($columnselect !== false): ?>
<div class="well well-sm text-center">
<?= $columnselect ?>
</div>
<?php endif; ?>

<form action="<?= JURI::current(); ?>" method="post" id="adminForm" name="adminForm">


	<div class="well well-sm">
		<legend><?= JText::_('COM_BADMINTON_GROUPS_FILTER'); ?></legend>
		
		<?php foreach ($this->filterForm->getFieldset() as $field):
			if (!$field->hidden): ?>
			<?= !empty($field->label) || $field->type === 'Spacer' ? $field->label : '' ?>
			<?= $field->input ?>
		<?php
			endif;
		endforeach;

		// Add a clear all button
		$attr = array('class' => 'btn btn-default', 'onclick' => 'clearForm()');
		echo ' ' . JHtml::link('javascript:void(0);', JText::_('COM_BADMINTON_SEARCH_CLEARALL'), $attr); ?>
	</div>

	<?= $this->createToolbar(); ?>
	
	<table class="table table-striped table-hover">
		<thead>
			<?= $this->showTitle(); ?>
		</thead>
		
		<tfoot>
			<tr>
				<td colspan="<?= $this->fields ?>"><?= $this->pagination->getListFooter(); ?></td>
			</tr>
		</tfoot>
		
		<tbody>
			<?php if (!empty($this->items))
				foreach ($this->items as $i => $row)
					echo $this->showGroup($i, $row); ?>
		</tbody>
	</table>

	<div id="askdelete" style="display:none;overflow:auto;"><?= JText::_('COM_BADMINTON_GROUPS_DELETE_ASK') ?></div>
	
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="list[order]" value="<?= $this->listOrder; ?>" />
	<input type="hidden" name="list[direction]" value="<?= $this->listDirection; ?>" />
	<input type="hidden" name="list[limit]" value="20" />
	<input type="hidden" name="jform[competitive]" value="0">
	<?= JHtml::_('form.token'); ?>
</form>