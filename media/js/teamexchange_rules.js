/*
 * Deze functies controleren op regels voor competitie bij Badminton Vlaanderen
 * Ref. C320 - 	Competitiereglement Vlaamse en Provinciale Competitiereglement
 *				Gemengd - Heren - Dames
 *				Uitgave 2014
 */

var BadmintonVlaanderenRules = {
	 
	// Ref Art. 51.2
	rankscores: { "A": 20, "B1": 10, "B2": 6, "C1": 4, "C2": 2, "D": 1, "": 0 },

	// Show an error
	showError: function(selector, msg)
	{
		if (selector.constructor === Array)
		{
			for (var k in selector)
			{
				jQuery(selector[k])
					.addClass('teamexchange_error')
					.attr('title', msg)
					.tooltip();
			}
		}
		else
		{
			jQuery(selector)
				.addClass('teamexchange_error')
				.attr('title', msg)
				.tooltip();
		}
	},

	// Controleer alle regels
	check: function()
	{
		// Remove all previous tooltips
		jQuery('.teamexchange_error')
			.tooltip('destroy')
			.removeAttr("title")
			.removeClass('teamexchange_error');

		// Extract the data
		var data = BadmintonVlaanderen.extractData();
		var matches;
		switch (data.type)
		{
			case "mixed": matches = BadmintonVlaanderenConfig.types[0]; break;
			case "men": matches = BadmintonVlaanderenConfig.types[1]; break;
			case "women": matches = BadmintonVlaanderenConfig.types[2]; break;
			default: return;
		}

		// Check completeness of information
		for (var m = 0; m < matches.length; m++) {
			for (var p = 0; p < matches[m].rows; p++) {
				var player;
				if (matches[m].id == 'i')
					player = data.substitutes[p];
				else
					player = data.matches[m].players[p];
				var score = 0;
				if (player.name) score = score + 1;
				if (player.id) score = score + 1;
				if (player.singles) score = score + 1;
				if (player.doubles) score = score + 1;
				if (player.mixed) score = score + 1;
				if (score > 0 && score < 5)
				{
					BadmintonVlaanderenRules.showError('.' + matches[m].id + "p" + (p + 1), BadmintonVlaanderenConfig.lblArt_52_6);
				}
			}
		}
		
		// Make a list of played games and check for maximum played games
		var matchcount = {};
		for (var m = 0; m < matches.length; m++) {
			var matchplayers = [];
			for (var p = 0; p < matches[m].rows; p++) {
				var player;
				if (matches[m].id == "i")
					player = data.substitutes[p];
				else
					player = data.matches[m].players[p];
				if (!player.id)
					continue;
				
				// Get id's
				if (!(matchcount[player.id]))
					matchcount[player.id] = { S: [], D: [], X: [], I: [] };
				var selector = "." + matches[m].id + "p" + (p + 1);
				
				// Find out if the match players already contains this player
				if (matchplayers[player.id] !== undefined)
				{
					BadmintonVlaanderenRules.showError(selector, BadmintonVlaanderenConfig.lblDuplicate);
					BadmintonVlaanderenRules.showError(matchplayers[player.id], BadmintonVlaanderenConfig.lblDuplicate);
				}
				matchplayers[player.id] = selector;
				console.log(matchplayers);
				
				// Add count
				if (matches[m].id == "i")
					matchcount[player.id].I.push(selector);
				else
				{
					switch (data.type)
					{
						case "mixed":
							if (m < 2)
								matchcount[player.id].D.push(selector);
							else if (m < 4)
								matchcount[player.id].X.push(selector);
							else
								matchcount[player.id].S.push(selector);
							break;
						case "men":
							if (m < 4)
								matchcount[player.id].D.push(selector);
							else
								matchcount[player.id].S.push(selector);
							break;
						case "women":
							if (m < 4)
								matchcount[player.id].D.push(selector);
							else
								matchcount[player.id].S.push(selector);
							break;
					}
				}
			}
		}
		for (var id in matchcount)
		{
			var mc = matchcount[id];
			switch (data.type)
			{
				case "mixed":
					if (mc.S.length > 1)
						BadmintonVlaanderenRules.showError(mc.S, BadmintonVlaanderenConfig.lblArt_52_7);
					if (mc.D.length > 1)
						BadmintonVlaanderenRules.showError(mc.D, BadmintonVlaanderenConfig.lblArt_52_7);
					if (mc.X.length > 1)
						BadmintonVlaanderenRules.showError(mc.X, BadmintonVlaanderenConfig.lblArt_52_7);
					break;
				case "men":
					if (matchcount[id].S.length > 1)
						BadmintonVlaanderenRules.showError(mc.S, BadmintonVlaanderenConfig.lblArt_52_7);
					if (matchcount[id].D.length > 2)
						BadmintonVlaanderenRules.showError(mc.D, BadmintonVlaanderenConfig.lblArt_52_7);
					break;
				case "women":
					if (matchcount[id].S.length > 1)
						BadmintonVlaanderenRules.showError(mc.S, BadmintonVlaanderenConfig.lblArt_52_7);
					if (matchcount[id].D.length > 2)
						BadmintonVlaanderenRules.showError(mc.D, BadmintonVlaanderenConfig.lblArt_52_7);
					break;
			}
		}

		/*
		 * Artikel 52.8:	De spelers dienen in het enkelspel in volgorde van hun klassement als titularis opgesteld te worden, waarbij
		 *					de speler met het hoogste klassement het eerste enkelspel speelt. Wanneer spelers hetzelfde klassement
		 *					hebben, mogen zij in willekeurige volgorde als titularis opgesteld worden.
		 * Artikel 52.9:	In de heren- en damescompetitie moeten er 4 paren (minimaal 2 verschillende qua samenstelling) gevormd
		 *					worden voor de dubbelwedstrijden, waarbij eenzelfde heer/dame nooit in meer dan 2 paren kan aantreden,
		 *					noch als titularis, noch als invaller. Uitzondering hierop vormt artikel 57 lid 1.
		 * Artikel 52.10:	Elk paar krijgt naargelang het klassement van beide spelers een index volgens de voorwaarden van
		 *					artikel 51.
		 * Artikel 52.11	De dubbelparen dienen in volgorde van hun index te worden opgesteld. Paren met dezelfde index mogen
		 * 					onderling willekeurig worden opgesteld.
		 */
		// Calculate the indices
	}
}