<?php
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.framework');

$now = new DateTime();

if (!$this->matchlist)
{
	echo JText::_('COM_BADMINTONVLAANDEREN_COMPETITIONLIST_NODATA');
	return;
} ?>

<?php if ($this->canTeamExchange) : ?>
<div class="col-sm-12 col-lg-12">
	<small class="pull-right"><?= JText::_('COM_BADMINTONVLAANDEREN_COMPETITIONLIST_RETRIEVED') ?></small>
	<small class="pull-left"><a href="<?= $this->matchlist->calendar ?>" target="_blank"><i class="fa fa-calendar"></i> iCal Download</a></small>
</div>

<script type="text/javascript">
function gotoTeamExchange(home, visitor, mdate, mtime)
{
	jQuery('input[name=home]').val(home);
	jQuery('input[name=visitor]').val(visitor);
	jQuery('input[name=date]').val(mdate);
	jQuery('input[name=time]').val(mtime);
	document.forms.teamexchange.submit();
}
</script>
<form action="<?= JRoute::_('index.php?option=com_badmintonvlaanderen&view=teamexchange') ?>" method="POST" name="teamexchange">
<?php endif ?>

<div class="col-md-12">
	<ul class="list-group">
		<?php foreach ($this->matchlist->matches as $match) : ?>
		<li class="list-group-item">
			<h4 class="badminton_title"><?= $match->home ?> vs <?= $match->visitors ?></h4>
			<div class="badminton_details">
				<?= $match->datetime->format('d/m/Y H:i'); ?><br />
				<a href="<?= $match->locationurl ?>"><?= $match->location ?></a>
			</div>
			<div class="badminton_score">
			<?php if ($match->datetime > $now && $this->canTeamExchange) : ?>
				<a href="javascript:void(0);" onclick="gotoTeamExchange('<?= str_replace("'", "\\'", $match->home) ?>', '<?= str_replace("'", "\\'", $match->visitors) ?>', '<?= $match->datetime->format('d/m/Y') ?>', '<?= $match->datetime->format('H:i'); ?>')"><i class="fa fa-file-text-o"></i></a>
			<?php elseif ($match->scores) : ?>
				<?= $match->scores[0] ?> - <?= $match->scores[1] ?>
			<?php else : ?>
				-
			<?php endif ?>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
</div>

<?php if ($this->canTeamExchange) : ?>
<input type="hidden" name="level" value="<?= $this->level ?>" />
<input type="hidden" name="type" value="<?= $this->type ?>" />
<input type="hidden" name="division" value="<?= $this->division ?>" />
<input type="hidden" name="series" value="<?= $this->series ?>" />
<input type="hidden" name="home" value="" />
<input type="hidden" name="visitor" value="" />
<input type="hidden" name="date" value="" />
<input type="hidden" name="time" value="" />
</form>
<?php endif ?>