// Select a player from a list
function clickSelectPlayer(elementid, target) {
	selectPlayer({
		select: function(player) {
			
			if (typeof(player) === "undefined") {
				// Fill in the right fields
				jQuery("#" + elementid + "name").empty();
				jQuery("#" + elementid + "name").removeData("player");
				jQuery("#" + elementid + "rank").empty();
				return;
			}
			
			// Get the information of this player
			if (!player.singles || !player.doubles || !player.mixed) {
				jQuery("#" + elementid + "lastname").html('<img src = "media/com_badminton/images/waiting.gif" />');
				// jQuery("#" + elementid + "firstname").html('<img src = "media/com_badminton/images/waiting.gif" />');
				// jQuery("#" + elementid + "memberid").html('<img src = "media/com_badminton/images/waiting.gif" />');
				jQuery("#" + elementid + "singles").html('<img src = "media/com_badminton/images/waiting.gif" />');
				jQuery("#" + elementid + "doubles").html('<img src = "media/com_badminton/images/waiting.gif" />');
				jQuery("#" + elementid + "mixed").html('<img src = "media/com_badminton/images/waiting.gif" />');
				
				var options = {
					id: player.memberId,
					option: 'com_badminton',
					view: 'player',
					format: 'json' };
				jQuery.get("index.php", {
					id: player.memberId,
					option: 'com_badminton',
					view: 'player',
					format: 'json'
				}).done(function(p) {
					
					// Fill in the right fields
					if (!p) {
						alert("Er ging iets verkeerd met het opzoeken");
						return;
					}
					
					// Add properties and fill structures
					player.singles = p.singles;
					player.doubles = p.doubles;
					player.mixed = p.mixed;
					fillPlayerInfo(elementid, player);
				});
			}
			else
				fillPlayerInfo(elementid, player);
		},
		waitPlaceholder: jQuery('<span>')
			.css({ display: "block", width: "100%", textAlign: "center", padding: "10px", border: "none" })
			.html('<img src="media/com_badminton/images/waiting.gif" />'),
		target: target
	});
}

// Select a score from a number pad
function clickSelectScore(elementid) {
	selectNumber({
		select: function(number) {
			if (typeof(number) === "undefined") {
				jQuery("#" + elementid).empty();
			}
			else {
				jQuery("#" + elementid).html(number.toString());
				
				// This would be the losing number, so get the elementid of the other number
				var otherid;
				var character = elementid.substring(elementid.length - 7, elementid.length - 6);
				if (character == "h")
					otherid = elementid.substring(0, elementid.length - 7) + "v" + elementid.substring(elementid.length - 6);
				else if (character == "v")
					otherid = elementid.substring(0, elementid.length - 7) + "h" + elementid.substring(elementid.length - 6);
				
				if (typeof(otherid) !== "undefined") {
					if (number < 21)
						jQuery("#" + otherid).html('21');
					else if (number < 29)
						jQuery("#" + otherid).html((number + 2).toString());
					else if (number == 29)
						jQuery("#" + otherid).html('30');
				}
			}
			
			// Update statistics
			updateStatistics();
		},
		cancel: function() {
			updateStatistics();
		}
	});
}

// Fill information of a player in the correct fields
function fillPlayerInfo(elementid, player) {
	jQuery("#" + elementid + "firstname").html(player.firstName);
	jQuery("#" + elementid + "lastname").html(player.lastName);
	jQuery("#" + elementid + "memberid").html(player.memberId);
	jQuery("#" + elementid + "singles").html(player.singles);
	jQuery("#" + elementid + "doubles").html(player.doubles);
	jQuery("#" + elementid + "mixed").html(player.mixed);
}

// Load
function loaded() {
	
	// Date time picker
	jQuery(".datepicker").datepicker({ dateFormat: 'dd/mm/yy', firstDay: 1 });
	
	// Select the current competition type
	setCompetitionType(1);
	
	// Initialize the statistics
	updateStatistics();
}

// Set the competition
function setCompetitionType(type) {
	var types = new Array();
	switch (type) {
		case 0: // Mixed
			jQuery(".tedoubles").hide();
			jQuery(".temixed").show();
			types = ['HD1', 'DD1', 'GD1', 'GD2', 'HE1', 'HE2', 'DE1', 'DE2'];
			break;
			
		case 1: // Men
			jQuery(".tedoubles").show();
			jQuery(".temixed").hide();
			types = ['HD1', 'HD2', 'HD3', 'HD4', 'HE1', 'HE2', 'HE3', 'HE4'];
			break;
			
		case 2: // Women
			jQuery(".tedoubles").show();
			jQuery(".temixed").hide();
			types = ['DD1', 'DD2', 'DD3', 'DD4', 'DE1', 'DE2', 'DE3', 'DE4'];
			break;
	}
	
	// Set
	var ids = ['d1', 'd2', 'd3', 'd4', 's1', 's2', 's3', 's4'];
	for (var i = 0; i < ids.length; i++)
		jQuery('#' + ids[i] + 'type').html(types[i]);
}

// Update statistics
function updateStatistics() {
	var scoreid = ['d1', 'd2', 'd3', 'd4', 's1', 's2', 's3', 's4'];
	var hgames = 0, vgames = 0, hmatches = 0, vmatches = 0, hscores = 0, vscores = 0;
	for (var i = 0; i < scoreid.length; i++) {
		var hg = 0, vg = 0;
		for (var m = 1; m <= 3; m++) {
			var h = parseInt(jQuery('#' + scoreid[i] + 'hscore' + m).html());
			var v = parseInt(jQuery('#' + scoreid[i] + 'vscore' + m).html());
			
			// Scores accumuleren
			if (!isNaN(h))
				hscores += h;
			if (!isNaN(v))
				vscores += v;
			var w = gameWon(h, v);
			
			// Games accumuleren
			hg += (w == 1 ? 1 : 0);
			vg += (w == 2 ? 1 : 0);
			hgames += (w == 1 ? 1 : 0);
			vgames += (w == 2 ? 1 : 0);
		}
		if (hg > vg && hg >= 2)
			hmatches++;
		if (vg > hg && vg >= 2)
			vmatches++;
	}
	
	// Update
	jQuery("#hgames").html(hgames.toString());
	jQuery("#vgames").html(vgames.toString());
	jQuery("#hscores").html(hscores.toString());
	jQuery("#vscores").html(vscores.toString());
	jQuery("#hmatches").html(hmatches.toString());
	jQuery("#vmatches").html(vmatches.toString());
}

// Winner of a game
function gameWon(hscore, vscore) {
	if (isNaN(hscore) || isNaN(vscore))
		return 0;
	if (hscore > vscore) {
		if (hscore == 30 || (hscore >= 21 && hscore - vscore > 1))
			return 1;
	}
	if (vscore > hscore) {
		if (vscore == 30 || (vscore >= 21 && vscore - hscore > 1))
			return 2;
	}
	return 0;
}

// Download the PUF
function downloadMeeting() {

	// Get general TE information
	data = {
		level: jQuery('input:radio[name=level]:checked').val(),
		type: jQuery('input:radio[name=type]:checked').val(),
		division: jQuery('input:text[name=division]').val(),
		series: jQuery('input:text[name=series]').val(),
		date: jQuery('input:text[name=date]').val(),
		start: jQuery('input:text[name=start]').val(),
		end: jQuery('input:text[name=end]').val(),
		home: jQuery('input:text[name=home]').val(),
		visitors: jQuery('input:text[name=visitors]').val()
	};
	var tmpForm = jQuery('<form action="index.php?option=com_badminton&view=meeting&format=pdf" method="POST" target="meetingPdf">')
		.css({ display: "none" });
		
	// Players and match types
	data.matches = [];
	var ids = ["d1", "d1b", "d2", "d2b", "d3", "d3b", "d4", "d4b", "s1", "s2", "s3", "s4"];
	var ts = ["d1", "d2", "d3", "d4", "s1", "s2", "s3", "s4"];
	var nplayers = [2, 2, 2, 2, 1, 1, 1, 1];
	var p = 0;
	for (var i = 0; i < ts.length; i++) {
		
		// Add type
		data.matches[i] = {
			matchtype: jQuery('td#' + ts[i] + 'type').html(),
		};

		// Add players
		data.matches[i].home = {};
		data.matches[i].visitor = {};
		for (var j = 1; j <= nplayers[i]; j++) {
			var hplayer = {
				firstname: jQuery('#' + ids[p] + 'hfirstname').html(),
				lastname: jQuery('#' + ids[p] + 'hlastname').html(),
				memberid: jQuery('#' + ids[p] + 'hmemberid').html()
			};
			var vplayer = {
				firstname: jQuery('#' + ids[p] + 'vfirstname').html(),
				lastname: jQuery('#' + ids[p] + 'vlastname').html(),
				memberid: jQuery('#' + ids[p] + 'vmemberid').html()
			};
			
			// Add ranks
			var rankid = [['hsingles', 'hdoubles', 'hmixed'],
							['vsingles', 'vdoubles', 'vmixed']];
			for (var k = 0; k < rankid.length; k++)
			{
				var rank = jQuery('#' + ids[p] + rankid[0][k]);
				if (rank.length > 0 && rank.css('display') !== 'none')
					hplayer.rank = rank.html();
				rank = jQuery('#' + ids[p] + rankid[1][k]);
				if (rank.length > 0 && rank.css('display') !== 'none')
					vplayer.rank = rank.html();
			}
			
			// Add players to the home and visitor list
			data.matches[i].home['player' + j] = hplayer;
			data.matches[i].visitor['player' + j] = vplayer;
			p = p + 1;
		}
		
		// Add scores
		data.matches[i].scores = [];
		for (var j = 0; j < 3; j++) {
			data.matches[i].scores[j] = [
				jQuery('td#' + ts[i] + 'hscore' + (j+1)).html(),
				jQuery('td#' + ts[i] + 'vscore' + (j+1)).html() ];
		}
	}
	
	// Execute the form
	tmpForm
		.append("<input type='text' name='meeting' value='" + JSON.stringify(data) + "\' />")
		.appendTo(document.body)
		.submit();
	tmpForm.remove();
}

// Add a player information from the webpage to a form
function appendGame(id, form, playerid) {
	appendValue(form, id + "type", jQuery("#" + id + "type").html());
	appendPlayer(form, id + "h");
	appendPlayer(form, id + "v");
	if (id.substring(0, 1) == "d") {
		appendPlayer(form, id + "bh");
		appendPlayer(form, id + "bv");
	}
	
	// Add scores
	for (var i = 1; i <= 3; i++) {
		var score = jQuery("#" + id + "hscore" + i).html();
		if (score.length > 0)
			appendValue(form, id + "hscore" + i, score);
		score = jQuery("#" + id + "vscore" + i).html();
		if (score.length > 0)
			appendValue(form, id + "vscore" + i, score);
	}
}
function appendPlayer(form, id) {
	var player = jQuery('#' + id + 'name').data("player");
	if (typeof(player) !== "undefined") {
		appendValue(form, id + 'name', player.lastName + " " + player.firstName);
		appendValue(form, id + 'id', player.memberId);
		appendValue(form, id + 'rank', jQuery("#" + id + "rank").html());
	}
}
function appendValue(form, name, value) {
	form.append(jQuery('<input type="text" name="' + name + '" value="' + value + '" />'));
}