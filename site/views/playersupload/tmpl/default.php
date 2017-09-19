<?php
defined('_JEXEC') or die('Restricted access');

JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.framework');
?>

<?php if (JFactory::getUser()->authorise('player.add', 'com_badminton')) { ?>
<!-- This form is for uploading a member spreadsheet -->
<div class="well">
	<form name="upload" class="form-horizontal" method="post" enctype="multipart/form-data">
		<fieldset>
			<legend>Upload</legend>
			<div class="form-group">
				<label for="file_upload" class="hasTooltip required col-lg-2 control-label" title data-original-title="<strong>Spreadsheet</strong><br />The spreadsheet containing member data">Members</label>
				<div class="col-lg-10">
					<input type="file" class="form-control" name="file_upload" id="file_upload" />
				</div>
			</div>
			<div class="form-group">
				<label for="previewcount" class="hasTooltip col-lg-2 control-label" title data-original-title="<strong>Preview count</strong><br />The amount of example records that are shown here">Preview Count</label>
				<div class="col-lg-10">
					<input type="number" name="previewcount" value="<?= $this->previewcount ?>" onchange="this.form.submit()" />
				</div>
			</div>
			<input class="btn btn-primary" type="submit" />
		</fieldset>
	</form>
	<?php if ($this->isfile) { ?>
	<br />
	<form name="update" class="form-horizontal" method="post" enctype="multipart/form-data">
		<fieldset>
			<legend>Manage</legend>
			<input class="btn btn-primary" type="submit" name="save" value="<?= JText::_('JSAVE') ?>" />
			<input class="btn btn-default" type="submit" name="cancel" value="<?= JText::_('JCANCEL') ?>" />
		</fieldset>
	</form>
	<?php } ?>
</div>
<?php } ?>

<?php 
if (count($this->preview) > 0) {
	foreach ($this->preview as $player) { ?>
<table class="table">
	<thead>
		<tr>
			<th colspan="2"><?= $player['lastname'] . ' ' . $player['firstname'] ?></th>
		</tr>
	</thead>
	
	<tbody>
	<?php foreach ($player as $key => $value) { ?>
	<tr>
		<td style="width:20%"><?= JText::_('COM_BADMINTON_PLAYERS_' . strtoupper($key)) ?></td>
		<td><?= $value instanceof DateTime ? $value->format('Y-m-d') : $value ?></td>
	</tr>
	<?php } ?>
	</tbody>
</table>
<?php }
}
else { ?>
<div class="col-lg-12 col-sm-12">
	<div class="panel panel-warning">
		<div class="panel-heading">
			<h3 class="panel-title">Opgelet</h3>
		</div>
		<div class="panel-body">
			Voor je een door deze website geëxporteerde spreadsheet kan uploaden, moet je hem eerst nog eens heropslaan met Excel. Indien je dit niet gedaan hebt, kan het zijn dat er fouten optreden, of incorrecte informatie wordt gevonden.
		</div>
	</div>

	<h2>Gebruik</h2>
	De volgende regels gelden voor het lezen van een spreadsheet document:<br />
	<ol class="list-group">
		<li class="list-group-item">Er moet een rij bestaan met minstens de volgende velden. Deze rij wordt gebruikt om te weten in welke rij welke informatie staat.<br />
			<ul><?php foreach ($this->primary as $field) { echo "<li>" . $this->names[$field] . "</li>"; };?></ul></li>
		<li class="list-group-item">Andere kolommen die kunnen gelezen worden worden hier gegeven. Kolommen die niet in deze rij staan worden genegeerd.<br />
			<ul><?php foreach ($this->names as $name) { echo "<li>$name</li>"; } ?></ul>
		</li>
		<li class="list-group-item">Indien kolominformatie is gevonden zoals hierboven, wordt er voor de rijen er na beslist of het gaat om een speler of niet. Dit gebeurt met de volgende regels.<br />
			<ul>
				<li>De kolom ID moet een getal bevatten</li>
				<li>Alle primaire velden (<?php $names = array(); foreach ($this->primary as $field) { $names[] = $this->names[$field]; }; echo implode(', ', $names); ?>) moeten ingevuld zijn</li>
				<li>De informatie moet correct zijn. Dit betekent, datums zijn correct, ongeldige tekens mogen niet aanwezig zijn, emails zijn geldige emailadressen, enz. Een melding wordt gegeven indien een veld incorrect is.</li>
			</ul>
		</li>
		<li class="list-group-item">Na het uploaden kan je een aantal voorbeelden bekijken. Als je accepteert, dan worden de gevonden spelers in de database geladen. Hierbij wordt echter eerst hetvolgende gecontroleerd:
			<ul>
				<li>Er mag geen clublid zijn met hetzelfde email-adres (indien opgegeven)</li>
				<li>Er mag geen clublid zijn met hetzelfde VBL identificatienummer (indien opgegeven)</li>
				<li>Er mag geen clublid zijn met hetzelfde club identificatienummer (indien opgegeven)</li>
			</ul>
			Nadien wordt een melding gegeven indien spelers niet konden toegevoegd worden. U kan dit alsnog aanpassen en opnieuw uploaden.
		</li>
	</ol>
</div>
<?php } ?>