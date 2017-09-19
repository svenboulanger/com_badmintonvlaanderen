<?php
defined('_JEXEC') or die('Restricted access'); ?>

<h2><?= JText::_('COM_BADMINTON_VIEW_MEETING_TITLE'); ?></h2>
<p>
	<table class="setupproperties">
		<tr><td width = "200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_NATIONAL'); ?></td><td><input type = "radio" name="level" value = "national"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_LEAGUE'); ?></td><td><input type = "radio" name = "level" value = "league"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_PROVINCE'); ?></td><td><input type = "radio" name = "level" value = "regional" checked></td></tr>
	</table><br />
	<table class="setupproperties">
		<tr><td width = "200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_MIXED'); ?></td><td><input type = "radio" name = "type" value = "mixed" onclick = "setCompetitionType(0);"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_MEN'); ?></td><td><input type = "radio" name = "type" value = "men" checked onclick = "setCompetitionType(1);"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_WOMEN'); ?></td><td><input type = "radio" name = "type" value = "women" onclick = "setCompetitionType(2);"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_TYPE_YOUTH'); ?></td><td><input type = "radio" name = "type" value = "youth" onclick = "setCompetitionType(3);"></td></tr>
	</table><br />
	<table class="setupproperties">
		<tr><td width = "200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_DIVISON'); ?></td><td><input type = "text" name = "division" /></td></tr>
		<tr><td width = "200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_SERIES'); ?></td><td><input type = "text" name = "series" /></td></tr>
	</table><br />
	<table class="setupproperties">
		<tr><td width = "200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_DATE'); ?></td><td><input type = "text" name = "date" class = "datepicker" /></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_START'); ?></td><td><input type = "text" name = "start" /></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_END'); ?></td><td><input type = "text" name = "end" /></td></tr>
	</table><br />
	<table class="setupproperties">
		<tr><td width = "200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_HOME'); ?></td><td><input type = "text" name = "home" /></td></tr>
		<tr><td width = "200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_VISITORS'); ?></td><td><input type = "text" name = "visitors" /></td></tr>
	</table>
	<table class="setupproperties">
		<tr><td width="200px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_SHUTTLE'); ?></td><td><input type = "text" name = "shuttle" /></td></tr>
	</table>
</p>

<h2><?= JText::_('COM_BADMINTON_VIEW_MEETING_GAMES'); ?></h2>
<p>
	<table class = "setup">
		<tr>
			<th class = "wttype" width = "30px"></td>
			<th class = "wtname" width = "150px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_NAME'); ?></td>
			<th class = "wtrank" width = "30px"></td>
			<th class = "wbname" width = "150px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_NAME'); ?></td>
			<th class = "wbrank" width = "25px"></td>
			<th colspan = "2" class = "wscore1" width = "60px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_GAME'); ?> 1</td>
			<th colspan = "2" class = "wscore2" width = "60px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_GAME'); ?> 2</td>
			<th colspan = "2" class = "wscore3" width = "60px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_GAME'); ?> 3</td>
		</tr>
		<?php
			$this->outputMatch("HD1", "d1", 2);
			$this->outputMatch("", "d1b", 0);
			$this->outputMatch("HD2", "d2", 2);
			$this->outputMatch("", "d2b", 0);
			$this->outputMatch("HD3", "d3", 2);
			$this->outputMatch("", "d3b", 0);
			$this->outputMatch("HD4", "d4", 2);
			$this->outputMatch("", "d4b", 0);
			$this->outputMatch("HE1", "s1", 1);
			$this->outputMatch("HE2", "s2", 1);
			$this->outputMatch("HE3", "s3", 1);
			$this->outputMatch("HE4", "s4", 1);
		?>
	</table>
	
	<a href="javascript:void(0);" class="btn btn-primary" onclick = "downloadMeeting()">Download PDF</a>
</p>

<h2><?= JText::_('COM_BADMINTON_VIEW_MEETING_STATISTICS'); ?></h2>
<p>
	<table class = "statistics">
		<tr><th width = "300px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_STATISTIC'); ?></th><th width = "100px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_STATISTICS_HOME'); ?></th><th width = "100px"><?= JText::_('COM_BADMINTON_VIEW_MEETING_STATISTICS_VISITORS'); ?></th></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_STATISTICS_GAMES'); ?></td><td id = "hgames"></td><td id = "vgames"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_STATISTICS_MATCHES'); ?></td><td id = "hmatches"></td><td id = "vmatches"></td></tr>
		<tr><td><?= JText::_('COM_BADMINTON_VIEW_MEETING_STATISTICS_POINTS'); ?></td><td id = "hscores"></td><td id = "vscores"></td></tr>
	</table>
</p>
<iframe style = "display:none;width:1px;height:1px;" name = "meetingPdf" />