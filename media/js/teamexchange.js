var BadmintonVlaanderen = {

	localPlayers: [],

	/**
	 * Select a player from the list and fill in the correct information
	 *
	 * @param group		The name of the group of player data
	 * @param options	Extra options for the popup window
	 */
	selectPlayerByElementId: function(group, options) 
	{

		// Get the waiting gif
		var selector = '.' + group;

		// Default options
		if (!options)
			options = { ranking: true };
		if (!options.ranking)
			options.ranking = true;

		// Create the select box
		BadmintonVlaanderen.createPlayerSelect({
			busy: BadmintonVlaanderenConfig.waitingIcon,
			search: {
				divClass: 'line',
				lblSearch: BadmintonVlaanderenConfig.bvlSearch
			},
			display: BadmintonVlaanderen.getPlayerDisplay,
			removeplayer: BadmintonVlaanderen.searchRemovePlayer,
			removeplayerClass: "btn btn-default",
			remove: function()
			{
				jQuery(selector).empty();
				if (jQuery.isFunction(BadmintonVlaanderenRules.check))
					BadmintonVlaanderenRules.check();
			},
			select: function(player) {

				// Reset the fields
				if (typeof player !== 'object') {
					jQuery(selector).empty();
					return;
				}
				
				if (!player.id)
				{
					alert(BadmintonVlaanderenConfig.lblNoID);
					player.singles = '';
					player.doubles = '';
					player.mixed = '';
					fillPlayerInfo(group, player);
					return;
				}
				
				// Find information if some is missing
				if (!player.singles || !player.doubles || !player.mixed) {
					jQuery(selector).html(BadmintonVlaanderenConfig.waitingIcon);

					if (options.ranking) {
						var req = {
							"ranking": player.id,
							"option": 'com_badmintonvlaanderen',
							"view": 'player',
							"format": 'json' };
						jQuery.get("index.php", req)
							.done(function(p) {

								// Fill in the right fields
								if (!p)
								{
									alert(BadmintonVlaanderenConfig.lblNoPlayer);
									return;
								}
								
								// Add properties and fill structures
								player.singles = p.rank.singles;
								player.doubles = p.rank.doubles;
								player.mixed = p.rank.mixed;
								BadmintonVlaanderen.fillPlayerInfo(group, player);
							});
					} else {
						
						// Fill information
						BadmintonVlaanderen.fillPlayerInfo(group, player)
					}
				} else
					BadmintonVlaanderen.fillPlayerInfo(group, player);
			}
		});
	},

	/**
	 * Fill player information into the correct fields depending on the class definitions
	 *
	 * @param group		The name of the group for which the player data should be used
	 * @param player	An object with the player data
	 */
	fillPlayerInfo: function(group, player) 
	{
		
		// Get the class selector
		group = '.' + group;
		
		// Individual data
		if (player.name)
			jQuery(group + '.name').html(player.name);
		if (player.id)
			jQuery(group + '.bvlid').html(player.id);
		if (player.singles)
			jQuery(group + '.singles').html(player.singles);
		if (player.doubles)
			jQuery(group + '.doubles').html(player.doubles);
		if (player.mixed)
			jQuery(group + '.mixed').html(player.mixed);
		
		// Specific data
		if (player.name && player.id)
			jQuery(group + '.full').html(player.name + ' (' + player.id + ')');
		
		// Check for rules
		if (jQuery.isFunction(BadmintonVlaanderenRules.check))
			BadmintonVlaanderenRules.check();
	},

	/**
	 * Event when the page has loaded
	 */
	loaded: function() 
	{
		
		// Date time picker
		jQuery(".datepicker").datepicker({ dateFormat: 'dd/mm/yy', firstDay: 1 });
	},

	/*
	 * Extract data from the page
	 */
	extractData: function()
	{
		// Get general teamexchange information
		data = {
			level: jQuery('input:radio[name=level]:checked').val(),
			type: jQuery('input:radio[name=type]:checked').val(),
			division: jQuery('input:text[name=division]').val(),
			series: jQuery('input:text[name=series]').val(),
			date: jQuery('input:text[name=date]').val(),
			start: jQuery('input:text[name=start]').val(),
			home: jQuery('input:text[name=home]').val(),
			visitors: jQuery('input:text[name=visitors]').val(),
			captain: jQuery('.captain.full').html()
		};
			
		// Players and match types
		data.matches = [];
		data.substitutes = [];
		var matches = [["m1", 2], ["m2", 2], ["m3", 2], ["m4", 2], ["m5", 1], ["m6", 1], ["m7", 1], ["m8", 1], ["i", 4]];
		for (var m = 0; m < matches.length; m++) {
			var key = matches[m][0];
			var selector = '.' + key;
			
			// Add the match
			var match = {};
			if (key !== 'i')
				match.matchtype = jQuery(selector + '.type').html();
			
			// Add player data
			var players = [];
			for (var p = 0; p < matches[m][1]; p++) {
				var psel = selector + "p" + (p + 1);
				players.push({
					name: jQuery(psel + ".name").html(),
					id: jQuery(psel + ".bvlid").html(),
					singles: jQuery(psel + ".singles").html(),
					doubles: jQuery(psel + ".doubles").html(),
					mixed: jQuery(psel + ".mixed").html()
				});
			}
			
			if (key !== 'i') {
				match.players = players;
				data.matches[m] = match;
			} else
				data.substitutes = players;
		}
		
		return data;
	},

	/**
	 * Gather team exchange data and download the PDF-version
	 */
	downloadTeamExchange: function()
	{
		var data = BadmintonVlaanderen.extractData();

		// Execute the form
		var tmpForm = jQuery('<form action="index.php?option=com_badmintonvlaanderen&view=teamexchange&format=pdf" method="POST" target="teamexchangePdf">')
			.css({ display: "none" });
		tmpForm
			.append("<input type='text' name='teamexchange' value='" + JSON.stringify(data) + "\' />")
			.appendTo(document.body)
			.submit();
		tmpForm.remove();
	},

	/**
	 * Set the current competition type
	 *
	 * @param type	The id of the type
	 *				0 = Mixed
	 *				1 = Men
	 *				2 = Women
	 */
	setCompetitionType: function(type)
	{
		// Update types
		var types = BadmintonVlaanderenConfig.types;
		for (var i = 0; i < types[type].length; i++)
			jQuery("." + types[type][i].id + '.type').html(types[type][i].label + types[type][i].index);
	},
	
	/**
	 * Create a div containing all necessary elements for selecting players
	 *
	 * @param options	The options of the popup
	 *
	 * @return Object	The div container
	 */
	createPlayerSelect: function(options)
	{
		
		// Create a division to close without doing anything
		jQuery("<div>")
			.attr("id", "playercancel")
			.css({
				zIndex: 511,
				position: "fixed",
				left: 0,
				top: 0,
				width: "100%",
				height:"100%",
				backgroundColor: "#CCCCCC",
				opacity: 0.4
			})
			.click(function(e) {
				
				// Run options.cancel if exists and stop if it returns false
				var options = jQuery("#playerselect").data("options");
				if (typeof(options) !== "undefined" && typeof(options.cancel) === "function")
					if (!options.cancel())
						return;
				
				// Remove the popup
				BadmintonVlaanderen.closePlayerSelect();
			})
			.appendTo(jQuery(document.body));
		
		// Create a div
		var playerselect = jQuery("<div>")
			.attr("id", "playerselect")
			.css({
				position: "fixed",
				zIndex: 512,
				top: "50%",
				left: "50%",
				width: 400,
				marginLeft: -205,
				marginTop: -205,
				padding: 10,
				backgroundColor: "white"
			});
		var form = jQuery('<form action = "javascript:void(0);">').appendTo(playerselect);

		// Add the search field
		form
			.append(jQuery('<div>')
				.addClass(options.search.divClass)
				.append(jQuery('<div>')
					.append(jQuery('<input type="text">')
						.addClass(options.search.textClass)
						.keydown(function (e) {
							if (event.which == 13) { // Pressing enter will also initiate searching
								e.preventDefault();
								BadmintonVlaanderen.searchPlayers();
							}
						})))
				.append(jQuery('<a href="javascript:void(0);">')
					.addClass('btn btn-default')
					.html(options.search.lblSearch)
					.click(function() { BadmintonVlaanderen.searchPlayers(); })))
			.append(jQuery('<div>')
				.attr("id", "playerlist")
				.addClass("list-group")
				.css({
					display: "inline-block",
					height: 360,
					margin: "auto",
					width: "100%",
					overflowY: "scroll",
					overflowX: "hidden"
				}))
			.append(jQuery('<a href="javascript:void(0);">')
				.addClass("btn btn-default")
				.html(BadmintonVlaanderenConfig.lblRemove)
				.click(function() {
					options.remove();
					BadmintonVlaanderen.closePlayerSelect();
				}));
			
		// Add to the document
		jQuery(document.body).append(playerselect);
		jQuery('#playerselect').data('options', options);
		
		// Add previously searched players
		for (var i = 0; i < BadmintonVlaanderen.localPlayers.length; i++) 
		{
			var p = BadmintonVlaanderen.localPlayers[i];
			if (typeof options.group !== 'undefined' && typeof p[i].group !== 'undefined') {

				// Only add the player if the group matches
				if (p.group === options.group)
					BadmintonVlaanderen.createPlayerItem(p, options.display(p));
			} else
				BadmintonVlaanderen.createPlayerItem(p, options.display(p)); // Add anyway
		}
		
		return playerselect;
	},

	/**
	 * Close any opened player select popups
	 */
	closePlayerSelect: function()
	{
		jQuery("#playerselect").remove();
		jQuery("#playercancel").remove();
	},

	/**
	 * Get the format in which a player should be displayed
	 *
	 * @param player	The player object that should be displayed
	 *
	 * @return String	The string of how to display
	 */
	getPlayerDisplay: function(player)
	{
		return player.name + ' ' + ' (' + player.id + ')';
	},

	/**
	 * Create a player inside the active player select
	 *
	 * @param player		The player object containing the player display data
	 *
	 * @return Object		The player div container
	 */
	createPlayerItem: function(player, display) {

		// Create a div
		var item = jQuery('<a href="javascript:void(0);">')
			.addClass("list-group-item");
		
		// Add content
		item.click(BadmintonVlaanderen.selectPlayerItem);
		item.append(jQuery('<span>').html(display));
		item.data("player", player);
		
		// Add the item
		item.appendTo(jQuery("#playerselect div#playerlist"));
		return item;
	},

	/**
	 * Select a player item
	 * This is the event that is called for a selected player item
	 *
	 * @param e 	The event data
	 */
	selectPlayerItem: function(e) {

		// Get the player object and options
		var player = jQuery(this).data("player");
		var options = jQuery("#playerselect").data("options");
		
		// Run the onSelect option
		if (options && options.select)
			options.select(player);

		if (typeof player === 'object') {
			
			// Update the group identifier if possible
			if (options && options.group)
				player.group = options.group;
			
			// Add it to the local players if it doesn't exist yet
			var found = 0;
			for (var i = 0; i < BadmintonVlaanderen.localPlayers.length; i++)
				if (BadmintonVlaanderen.localPlayers[i].id === player.id)
					found = 1;
				
			// If not found, then add to the list
			if (found == 0)
				BadmintonVlaanderen.localPlayers[BadmintonVlaanderen.localPlayers.length] = player;
		}
		
		// Close any instance of a popup
		BadmintonVlaanderen.closePlayerSelect();
	},

	/**
	 * Search players
	 *
	 * @param options		Search options
	 */
	searchPlayers: function()
	{
		
		// Find the search string
		var search = jQuery('#playerselect input[type="text"]').val();
		if (typeof(search) !== "string" || search === "")
			return;
		
		// Find options
		var options = jQuery('#playerselect').data("options");
		
		// Search the database
		var req = {
			"search": search,
			"option": 'com_badmintonvlaanderen',
			"view": 'player',
			"format": 'json' };
			
		// Clear the player list with the busy-placeholder
		var list = jQuery('#playerselect div#playerlist');
		list.html(options.busy);
		
		// Get the player list
		jQuery.get("index.php", req)
			.done(function(players) {

				// Add the results to the list
				var options = jQuery("#playerselect").data("options");
				jQuery('#playerselect div#playerlist').empty();
				
				// Parse the data and add it as items to the list
				if (players)
				{
					for (var i = 0; i < players.length; i++)
						BadmintonVlaanderen.createPlayerItem(players[i], options.display(players[i]));
				}
			});
	}
}