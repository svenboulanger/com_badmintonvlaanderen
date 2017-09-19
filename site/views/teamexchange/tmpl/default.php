<?php
defined('_JEXEC') or die;
?>

<div class="page-header">
	<h1><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE'); ?></h1>
</div>
<h2><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_MEETING'); ?></h2>
<p>
	<table class="table table-striped">
		<tr><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_LEVEL_LEAGUE'); ?></td><td><input type="radio" name="level" value="league"<?= $this->mlevel == 0 ? ' checked' : '' ?>></td></tr>
		<tr><td><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_LEVEL_YOUTH'); ?></td><td><input type="radio" name="level" value="youth"<?= $this->mlevel == 1 ? ' checked' : '' ?>></td></tr>
		<tr><td><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_LEVEL_ADULTS'); ?></td><td><input type="radio" name="level" value="adults" <?= $this->mlevel == 2 ? ' checked' : '' ?>></td></tr>
	</table><br />
	<table class="table table-striped">
		<tr><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TYPE_MIXED'); ?></td><td><input type="radio" name="type" value="mixed"<?= $this->mtype == 0 ? ' checked' : '' ?> onclick="BadmintonVlaanderen.setCompetitionType(0);"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TYPE_MEN'); ?></td><td><input type="radio" name="type" value="men"<?= $this->mtype == 1 ? ' checked' : '' ?> onclick="BadmintonVlaanderen.setCompetitionType(1);"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TYPE_WOMEN'); ?></td><td><input type="radio" name="type" value="women"<?= $this->mtype == 2 ? ' checked' : '' ?> onclick="BadmintonVlaanderen.setCompetitionType(2);"></td></tr>
	</table><br />
	<table class="table table-striped">
		<tr><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_DIVISON'); ?></td><td><input type="text" class="form-control" name="division" value="<?= $this->division ?>" /></td></tr>
		<tr><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_SERIES'); ?></td><td><input type="text" class="form-control" name="series" value="<?= $this->series ?>" /></td></tr>
		<tr><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_DATE'); ?></td><td><input type="text" name="date" class="form-control datepicker" value="<?= $this->mdate ?>" /></td></tr>
		<tr><td><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_START'); ?></td><td><input type="text" name="start" value="<?= $this->mtime ?>" /></td></tr>
		<tr><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_HOME'); ?></td><td><input type="text" name="home" value="<?= $this->home ?>" /></td></tr>
		<tr><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_VISITORS'); ?></td><td><input type="text" name="visitors" value="<?= $this->visitor ?>" /></td></tr>
	</table>
	<table class="table table-striped table-hover">
		<tr class="selectable" onclick="BadmintonVlaanderen.selectPlayerByElementId('captain',{ranking:false})"><td width="200px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_TEAM_CAPTAIN'); ?></td><td class="captain full"></td></tr>
	</table>
</p>

<h2><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_SETUP'); ?></h2>
<table class="table table-hover table-bordered">
	<thead>
		<tr>
			<th class="type" width="50px"></td>
			<th class="name"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_NAME'); ?></td>
			<th class="memberid" width="100px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_MEMBERID'); ?></td>
			<th class="singles" width="50px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_SINGLES'); ?></td>
			<th class="doubles" width="50px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_DOUBLES'); ?></td>
			<th class="mixed" width="50px"><?= JText::_('COM_BADMINTONVLAANDEREN_TEAMEXCHANGE_MIXED'); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php
			for ($i = 0; $i < 9; $i++)
				$this->outputPlayerRow($i);
		?>
	</tbody>
</table>

<div class="wrap btn-actions text-center" style="margin:40px auto">
	<a href="javascript:void(0);" class="btn btn-lg btn-primary" onclick="BadmintonVlaanderen.downloadTeamExchange()">Download PDF</a>
</div>
<iframe style="display:none;width:1px;height:1px;" name="teamexchangePdf"></iframe>