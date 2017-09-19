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
function searchVbl(vblid) {

	// First set a searcher
	jQuery('#' + vblid)
		.html('<img src="<?= JRoute::_('media/com_badminton/images/waiting.gif') ?>" alt="VBL" />')
		.prop('onclick',null).off('click');

	// Search for the id
	var req = {
		id: vblid,
		option: 'com_badminton',
		view: 'player',
		format: 'json',
		all: 0 };
	jQuery.get("index.php", req)
		.done(function(p) {

			// Fill in the right fields
			if (!p) {
				alert("Something went wrong, could not load player");
				jQuery('#' + vblid).html('<i class="fa fa-times-circle"></i>');
				return;
			}

			// Add properties and fill structures
			jQuery('#' + vblid)
				.html('<?= JHtml::image(JRoute::_($this->vbl_img), 'VBL') ?>')
				.attr('href', '<?= $this->vbl_url ?>' + p.profile)
				.attr('target', '_blank');
		});
}
function clearForm() {
	jQuery('input[name^="filter"]').val('');
	jQuery('select[name^="filter"]').val('').change();
}
</script>

<h1><?= JText::_('COM_BADMINTON_MANAGER_PLAYERS') . " <span style=\"font-size:0.5em;vertical-align:middle;\">(" . $this->pagination->total . ")</span>"; ?></h1>

<?php $columnselect = $this->createColumnSelect();
if ($columnselect !== false): ?>
<div class="well well-sm text-center">
<?= $columnselect ?>
</div>
<?php endif; ?>

<form action="<?= JURI::current(); ?>" method="post" id="adminForm" name="adminForm">


	<div class="well well-sm">
		<legend><?= JText::_('COM_BADMINTON_PLAYERS_FILTER'); ?></legend>
		
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
	<?php if ($this->showgrouptool) : ?>
	<div class="well well-sm">
		<legend><?= JText::_('COM_BADMINTON_GROUPS_TOOL'); ?></legend>
		<?= $this->createGroupManager(); ?>
	</div>
	<?php endif; ?>

	<?= $this->createToolbar(); ?>
	
	<div class="pull-right">
		<?= $this->getLimitBox(); ?>
	</div>
	
	<table class="table table-striped table-hover">
		<thead>
			<?= $this->showTitle(); ?>
		</thead>
		
		<tfoot>
			<tr>
				<td colspan="<?= $this->fields ?>">
					<?= $this->pagination->getPagesLinks(); ?>
				</td>
			</tr>
		</tfoot>
		
		<tbody>
			<?php if (!empty($this->items))
				foreach ($this->items as $i => $row)
					echo $this->showPlayer($i, $row); ?>
		</tbody>
	</table>

	<div id="askdelete" style="display:none;overflow:auto;"><?= JText::_('COM_BADMINTON_PLAYERS_DELETE_ASK') ?></div>
	
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="list[order]" value="<?= $this->listOrder; ?>" />
	<input type="hidden" name="list[direction]" value="<?= $this->listDirection; ?>" />
	<?= JHtml::_('form.token'); ?>
</form>

<?php if ($this->canView): ?>
<div class="wrap btn-actions text-center" style="margin:40px auto">
	<a href="<?= JUri::base() ?>index.php?option=com_badminton&view=players&format=xlsx" target="playersDoc" class="btn btn-lg btn-primary">Download XLSX</a>
</div>
<iframe style="display:none;width:1px;height:1px;" name="playersDoc" ></iframe>
<?php endif ?>

<?php if (JFactory::getUser()->authorise('player.add', 'com_badminton')) { ?>
<!-- This form is for uploading a player list -->
<div class="well">
	<form name="upload" class="form-horizontal" method="post" enctype="multipart/form-data" action="<?= JRoute::_('index.php?option=com_badminton&view=playersupload') ?>">
		<fieldset>
			<legend><?= JText::_('COM_BADMINTON_PLAYERS_UPLOAD'); ?></legend>
			<div class="form-group">
				<label for="file_upload" class="hasTooltip required col-lg-2 control-label" title data-original-title="<strong><?= JText::_('COM_BADMINTON_PLAYERS_UPLOAD_SPREADSHEET'); ?></strong><br /><?= JText::_('COM_BADMINTON_PLAYERS_UPLOAD_SPREADSHEET_DESC'); ?>"><?= JText::_('COM_BADMINTON_PLAYERS_UPLOAD_SPREADSHEET'); ?></label>
				<div class="col-lg-10">
					<input type="file" class="form-control" name="file_upload" id="file_upload" />
				</div>
			</div>
			<div class="form-group">
				<label for="previewcount" class="hasTooltip col-lg-2 control-label" title data-original-title="<strong><?= JText::_('COM_BADMINTON_PLAYERS_UPLOAD_PREVIEW'); ?></strong><br /><?= JText::_('COM_BADMINTON_PLAYERS_UPLOAD_PREVIEW_DESC'); ?>"><?= JText::_('COM_BADMINTON_PLAYERS_UPLOAD_PREVIEW'); ?></label>
				<div class="col-lg-10">
					<input type="number" name="previewcount" value="5" />
				</div>
			</div>
			<input class="btn btn-primary" type="submit" />
		</fieldset>
	</form>
</div>
<?php } ?>
