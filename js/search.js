// Search (conversation list) JavaScript.

var ETSearch = {

// The current search details.
currentSearch: "",
currentChannels: [],

// References to search form elements.
form: null,
formInput: null,
formReset: null,

updateInterval: null,

// Initialize the search page.
init: function() {

	// Set the current channel and search query.
	if (ET.currentChannels) ETSearch.currentChannels = ET.currentChannels;
	if (ET.currentSearch) ETSearch.currentSearch = ET.currentSearch;


	// INITIALIZE THE SEARCH FORM.

	// Get the search form elements.
	ETSearch.form = $("#search");
	ETSearch.formInput = $("#search .text");
	ETSearch.formReset = $("#search .control-reset");

	new ETAutoCompletePopup(ETSearch.formInput, "author:");
	new ETAutoCompletePopup(ETSearch.formInput, "contributor:");

	// Add an onclick handler to the search button to perform a search.
	ETSearch.form.submit(function(e) {
		ETSearch.search(ETSearch.formInput.val());
		e.preventDefault();
	});

	// Add a key press handler to clear the search input when escape is pressed.
	ETSearch.formInput.keydown(function(e) {
		if (e.which != 27) return;

		// If the value isn't empty, clear it and focus on the input.
		if (ETSearch.formInput.val() != "") {
			ETSearch.search("");
			ETSearch.formInput.focus();
		}
		// If it is already empty, unfocus from the input.
		else ETSearch.formInput.blur();
		e.preventDefault();
	})

	// Add a key press handler to make the 'x' button visible if text has been entered or have previously been entered.
	.keyup(function(e) {
		ETSearch.formReset.css("visibility", (ETSearch.formInput.val() != "" || ETSearch.currentSearch != "") ? "" : "hidden");
	})

	// Add a handler to show the gambits section when the search input is active.
	.focus(function() {
		$("#gambits").addClass("popup withArrow withArrowTop withArrowTopRight").css({
			position: "absolute",
			top: $("#search").offset().top + $("#search").outerHeight() + 5,
			left: $("#search").offset().left + $("#search").outerWidth() - $("#gambits").outerWidth()
		}).fadeIn("fast");
	});

	// If the search input is blank, hide the reset 'x' button.
	if (!ETSearch.currentSearch) ETSearch.formReset.css("visibility", "hidden");

	// Add a click handler to the reset 'x' button.
	ETSearch.formReset.click(function(e) {
		ETSearch.search("");
		ETSearch.formInput.focus();
		e.preventDefault();
	});


	// INITIALIZE THE GAMBITS.

	// Hide the gambits area.
	$("#gambits").hide();

	// The gambits area should hide when the search input loses focus.
	ETSearch.formInput.blur(function() {
		$("#gambits").fadeOut("fast");
	});
	// However, prevent the search input from losing focus if a click takes place on the gambits popup.
	$("#gambits").mousedown(function(e) {
		e.preventDefault();
	});

	// Add click and double click handlers to all the gambits.
	$("#gambits a").click(function(e) {
		e.preventDefault();
		ETSearch.gambit(desanitize($(this).html()), e.shiftKey);
		ETSearch.formInput.keyup();
	}).dblclick(function(e) {
		e.preventDefault();
		ETSearch.search((e.shiftKey ? "!" : "") + "#" + desanitize($(this).html()));
		ETSearch.formInput.blur().keyup();
	})

	// Prevent the search field from being unfocussed when a gambit is clicked.
	.bind("mousedown", function(e) {
		e.preventDefault();
	});


	// INITIALIZE THE REST OF THE PAGE.

	// Run a callback that will update search results every so often.
	ETSearch.updateInterval = new ETIntervalCallback(ETSearch.update, ET.searchUpdateInterval);

	// Add tooltips to the channels, and give them click handlers.
	$("#channels a:not(.channel-list)").tooltip({alignment: "left", delay: 250, offset: [0, 0], className: "withArrow withArrowBottom"});
	$("#channels a.channel-list").tooltip();

	// When the hash in the URL changes, update the search interface.
	$(document).bind("statechange", function(event, hash) {

		// We'll get the hash in the format "conversations/channel-slug?search=whatever".
		var parts = hash.split("?");
		var channelParts = parts[0].split("/");
		if (!channelParts[1]) channelParts[1] = "all";
		if (!parts[1]) parts[1] = "";
		var newChannel = decodeURIComponent(channelParts[1]);
		var newSearch = decodeURIComponent(parts[1].replace("search=", ""));
		var oldChannel = ETSearch.getCurrentChannelSlugs().join("+");

		// If either the search or the channel has changed, update accordingly.
        if (ETSearch.currentSearch != newSearch || oldChannel != newChannel) {
			if (oldChannel != newChannel) ETSearch.changeChannel(newChannel);
			else ETSearch.search(newSearch);
		}
	});

	// Save the scroll position and the conversation ID whenever a conversation link is clicked on.
	$("#conversations a").live("click", function() {
		$.cookie("scrollTop", $(document).scrollTop(), {path: "/"});
		$.cookie("cid", ETSearch.getConversationIdForElement(this), {path: "/"});
	});

	// When the page loads, scroll to a position saved in a cookie (if any), and highlight the conversation that was just visited.
	setTimeout(function() {
		var scrollTop = $.cookie("scrollTop"),
			cid = $.cookie("cid");
		if (scrollTop) $.scrollTo(scrollTop);
		if (cid) $("#c" + cid).addClass("justVisited").delay(1000).removeClass("justVisited", 2000);
		$.cookie("scrollTop", null, {path: "/"});
		$.cookie("cid", null, {path: "/"});
	}, 1);

	// Add click handlers to the unread indicators.
	$("#conversations .unreadIndicator").live("click", function(e) {
		e.preventDefault();
		ETSearch.markAsRead(ETSearch.getConversationIdForElement(this));
	});

	// Add click handlers to the channels.
	$("#conversations .channel").live("click", function(e) {
		ETSearch.changeChannel($(this).data("channel"));
		e.preventDefault();
	});

	// Initialize the search results.
	ETSearch.initSearchResults();

	// Add a click handler to the mark all as read button.
	$("#conversationsFooter .markAllAsRead").live("click", function(e) {
		e.preventDefault();
		ETSearch.currentSearch = "";
		ETSearch.changeChannel("all", false, true);
	});

	// Add a click handler to the view more button.
	$("#conversationsFooter .viewMore a").live("click", function(e) {
		e.preventDefault();
		ETSearch.search((ETSearch.currentSearch ? ETSearch.currentSearch + " + " : "") + T("gambit.more results"));
	});

	// Add click handlers to the channels.
	$("#channels a:not(.channel-list)").live("click", function(e) {
		if (e.metaKey || e.ctrlKey) return;
		e.preventDefault();
		ETSearch.changeChannel($(this).data("channel"), e.shiftKey);
	});
},

// Given an element within a conversation row, get the ID of its parent conversation.
getConversationIdForElement: function(elm) {
	elm = $(elm);
	var id = elm.is("li") ? elm.attr("id") : elm.parents("li").attr("id");
	return id ? id.substr(1) : null;
},

// Initialize the search results.
initSearchResults: function() {

	// Make all "private" labels show a list of members allowed when they are moused over.
	ETMembersAllowedTooltip.init($("#conversations .label-private"), function(elm) {return ETSearch.getConversationIdForElement(elm)});
	ETMembersAllowedTooltip.showDelay = 500;
	$("#conversations .label-private").css("cursor", "pointer");

},

// Mark a single conversation as read, hiding its unread indicator.
markAsRead: function(conversationId) {
	$.ETAjax({
		url: "conversation/markAsRead.json/" + conversationId,
		global: true,
		success: function(data) {
			var row = $("#c" + conversationId);
			$(row).removeClass("unread");
			$(".unreadIndicator", row).remove();
			var link = $(".jumpToUnread", row);
			link.removeClass("jumpToUnread")
				.addClass("jumpToLast")
				.html(T("Jump to last"))
				.attr("href", link.attr("href").replace("/unread", "/last"));
		}
	});
},

// Change the channel.
changeChannel: function(channel, addChannel, markAllAsRead) {

	// Hide the tooltip and unselect all channels in the list.
	$.hideToolTip();
	$("#channels li:not(.pathItem)").removeClass("selected").find("a").removeClass("channel");

	// Find the channel ID that corresponds to the provided slug.
	var newChannel = null;
	for (var i in ET.channels) {
		if (ET.channels[i] == channel) {
			newChannel = i;
			break;
		}
	}

	// If we're adding this channel to the selection...
	if (addChannel) {

		// If we're not in "multi-select mode" (where the first channel is blank), make it so we are.
		if (ETSearch.currentChannels[0] != "") ETSearch.currentChannels = [""];

		// If this channel is already selected, we want to remove it from the selection.
		var k = ETSearch.currentChannels.indexOf(newChannel);
		if (k != -1) ETSearch.currentChannels.splice(k, 1);

		// Otherwise, add it.
		else ETSearch.currentChannels.push(newChannel);

	}

	// If we found a channel ID, change the selected channels to just this one.
	else if (newChannel) ETSearch.currentChannels = [newChannel];

	// Otherwise, we can assume "all channels" was clicked, in which case we clear the selected channels.
	else ETSearch.currentChannels = [];

	// If one or more channels are selected, highlight them in the channel breadcrumb area.
	if (ETSearch.currentChannels.length) {
		for (var i in ETSearch.currentChannels) {
			$("#channels .channel-"+ETSearch.currentChannels[i]).parent().addClass("selected").not(".pathItem").find("a").addClass("channel");
		}
	}

	// Perform the search.
	ETSearch.search(ETSearch.currentSearch, markAllAsRead);
},

// Get a list of slugs of the currently selected channels.
getCurrentChannelSlugs: function() {
	var slugs = [];
	if (ETSearch.currentChannels.length) {
		for (var i in ETSearch.currentChannels) {
			if (ET.channels[ETSearch.currentChannels[i]]) slugs.push(encodeURIComponent(ET.channels[ETSearch.currentChannels[i]]));
			else slugs.push("");
		}
	}
	else slugs = ["all"];

	return slugs;
},

// Perform a search.
search: function(query, markAllAsRead) {

	// Hide the gambits popup.
	$("#gambits").fadeOut("fast");

	// Set the current search and the form input value.
	ETSearch.currentSearch = ETSearch.formInput.val(query).val();

	// Get the channel slugs and join them together so we can put them in a URL.
	var channelString = ETSearch.getCurrentChannelSlugs().join("+");

	// Create a history entry so we can use the back button even though we're making an AJAX request.
	$.history.load("conversations/"+channelString+(query ? "?search="+encodeURIComponent(query) : ""), true);

	// Clear the results update timeout.
	ETSearch.updateInterval.reset();

	// Make the request.
	$.ETAjax({
		id: "search",
		url: "conversations/"+(markAllAsRead ? "markAllAsRead.ajax" : "index.ajax")+"/"+channelString,
		type: "post",
		global: false,
		data: {search: query},
		success: function(data) {

			// If messages were returned, don't update the results.
			if (data.messages) return;

			// Display the new results.
			$("#conversations").html(data.view);

			// Update the channels and re-initialize everything.
			ETSearch.updateChannels(data.channels);
			ETSearch.initSearchResults();
			ETMessages.hideMessage("search");

		},
		beforeSend: function() {
			createLoadingOverlay("conversations", "conversations");
		},
		complete: function() {
			hideLoadingOverlay("conversations", false);
		}
	});
},

// Update the channel breadcrumb area, animating the old channels to their new positions.
updateChannels: function(newChannels) {

	// Save the positional coordinates of all <a> tags.
	var positions = {};
	$("#channels a").each(function() {
		var classes = $(this).prop("className").split(" ");
		for (var i in classes) {
			if (classes[i].indexOf("channel-") != -1) {
				positions[classes[i]] = $(this).offset().left;
				return;
			}
		}
	});

	// Remove all of the channels (short of the channel list icon,) and add the new ones.
	$("#channels li:not(:first-child)").remove();
	$("#channels").append(newChannels);

	// Restore the old positional coordinates for all of the <a> tags, and then animate them to their new positions.
	$("#channels a").each(function() {

		// Read the channel's className to find the old position of the same channel.
		var classes = $(this).prop("className").split(" ");
		for (var i in classes) {

			// If this class is a "channel-x" class and we have a position saved for it...
			if (typeof classes[i] == "string" && classes[i].indexOf("channel-") != -1 && positions[classes[i]]) {
				var newPos = $(this).offset().left;
				$(this).css("position", "relative").css("left", -newPos + positions[classes[i]]).animate({left: 0}, "fast");
				return;
			}
		}

		// If we didn't find any matching positions, animate it moving in from the left and fading in.
		$(this).css("position", "relative").css("left", -100).css("opacity", 0).animate({left: 0, opacity: 1}, "fast");
	});
},

// Update the current search results with new post counts, last post times, etc.
update: function() {

	// Construct a list of conversation IDs for which to get updated details.
	var conversationIds = "";
	var count = Math.min($("#conversations li").length, 20);
	$("#conversations li").each(function(i, row) {
		if (i > count) return false;
		conversationIds += ETSearch.getConversationIdForElement(row) + ",";
	});

	// Get the channel slugs and join them together so we can put them in a URL.
	var channelString = ETSearch.getCurrentChannelSlugs().join("+");

	// Make an ajax request.
	$.ETAjax({
		url: "conversations/update.ajax/"+channelString+"/"+encodeURIComponent(ETSearch.currentSearch),
		type: "post",
		global: false,
		data: {conversationIds: conversationIds},
		success: function(data) {
			if (!data.conversations) return;

			// For each of the conversation rows returned, replace them in the results table.
			for (var i in data.conversations) {
				if (!$("#c"+i).length) continue;
				$("#c"+i).replaceWith(data.conversations[i]);
			}
			ETSearch.initSearchResults();
		}
	});
},

// Show new activity - an alias for reperforming the current search.
showNewActivity: function() {
	ETSearch.search(ETSearch.currentSearch);
	ETMessages.hideMessage("newSearchResults");
},

// Add (or take away) a gambit from the search input.
gambit: function(gambit, negative) {

	// Prepend a hashtag to the gambit.
	gambit = "#"+gambit;

	// Get the initial length of the search text.
	var initialLength = $.trim(ETSearch.formInput.val()).length;

	// Make a regular expression to find any instances of the gambit already in there.
	var safe = gambit.replace(/([?^():\[\]])/g, "\\$1");
	var regexp = new RegExp(negative
		? "( ?(- *|!)" + safe + " *$|^ *!" + safe + " *\\+ ?| ?(- *|!)" + safe + "|^ *!" + safe + " *$)"
		: "( ?\\+ *" + safe + " *$|^ *" + safe + " *\\+ ?| ?\\+ *" + safe + "|^ *" + safe + " *$)"
	, "i");

	// If there is an instance, take it out.
	if (ETSearch.formInput.val().match(regexp)) ETSearch.formInput.val(ETSearch.formInput.val().replace(regexp, ""));

	// Otherwise, insert the gambit with a +, -, or ! before it.
	else {
		var insert = (initialLength ? (negative ? " - " : " + ") : (negative ? "!" : "")) + gambit;
		ETSearch.formInput.focus();
		ETSearch.formInput.val(ETSearch.formInput.val() + insert);

		// If there is an instance of "?" or ":member" or ">10" in the gambit, we want to select it so the user can type over it.
		var placeholderIndex, placeholder;
		if (insert.indexOf("?") != -1) {
			placeholderIndex = insert.indexOf("?");
			placeholder = "?";
		} else if (insert.indexOf(">10") != -1) {
				placeholderIndex = insert.indexOf(">10");
				placeholder = ">10";
		} else if (insert.indexOf(":" + T("gambit.member")) != -1) {
			placeholderIndex = insert.indexOf(":" + T("gambit.member")) + 1;
			placeholder = T("gambit.member");
		}
		if (placeholderIndex) {
			ETSearch.formInput.selectRange(initialLength + placeholderIndex, initialLength + placeholderIndex + placeholder.length);
		}
	}
}

};

$(function() {
	ETSearch.init();
});