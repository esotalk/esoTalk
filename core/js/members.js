// Member List JavaScript

var ETMembers = {

init: function() {

	// Add a click handler to the "create member" button.
	$("#createMemberLink").click(function(e) {
		e.preventDefault();
		ETMembers.loadCreateMemberSheet();
	});

	var selected = $("#memberListOrderBy").find(".selected").removeClass("selected").find("a").prepend("<i class='icon-ok'></i>");
	$(".scrubberContent").prepend($("#memberListOrderBy").removeClass("tabs").popup({
		alignment: "right",
		content: T("Sort By") + " " + selected.text()+" <i class='icon-caret-down'></i>"
	}).find(".button").addClass("big").end());

	// Set up the letter scrubber.
	ETScrubber.body = $("#memberListBody");
	ETScrubber.scrubber = $("#members .scrubberContent");
	ETScrubber.items = $("#memberList");
	ETScrubber.count = parseInt(ET.countMembers);
	ETScrubber.perPage = parseInt(ET.membersPerPage);
	ETScrubber.moreText = T("Load more members");
	ETScrubber.startFrom = parseInt(ET.startFrom);

	// Set a callback that will load new member rows.
	ETScrubber.loadItemsCallback = function(position, success) {
		$.ETAjax({
			url: "members/index.ajax/"+ET.orderBy+"/"+position,
			data: {search: ET.searchString},
			success: success,
			global: false
		});
	};

	// Set a callback that will run whenever we scroll to a specific index. We need it to change the URL.
	ETScrubber.scrollToIndexCallback = function(index) {
		$.history.load("members/"+ET.orderBy+"/"+index, true);
	};

	// Initialize the scrubber.
	ETScrubber.init();

	// Add a tooltip to the online indicators.
	$("#memberList .online").tooltip({alignment: "left", className: "withArrow withArrowBottom", offset: [-9, 0]}).css("cursor", "pointer");


	// INITIALIZE THE GAMBITS.

	ETMembers.formInput = $("#memberSearch input[name=search]");

	// Hide the gambits area.
	$("#gambits").hide();

	// The gambits area should hide when the search input loses focus.
	$("#memberSearch input[name=search]").blur(function() {
		$("#gambits").fadeOut("fast");
	}).focus(function() {
		var input = $("#memberSearch input[name=search]");
		$("#gambits").addClass("popup").css({
			position: "absolute",
			top: input.offset().top + input.outerHeight() + 5,
			left: input.offset().left
		}).fadeIn("fast");
	});

	// However, prevent the search input from losing focus if a click takes place on the gambits popup.
	$("#gambits").mousedown(function(e) {
		e.preventDefault();
	});

	// Add click and double click handlers to all the gambits.
	$("#gambits a").click(function(e) {
		e.preventDefault();
		ETMembers.gambit(desanitize($(this).data("gambit")));
	}).dblclick(function(e) {
		e.preventDefault();
		ETMembers.formInput.val(desanitize($(this).data("gambit")));
		$("#memberSearch").submit();
	})

	// Prevent the search field from being unfocussed when a gambit is clicked.
	.bind("mousedown", function(e) {
		e.preventDefault();
	});

},

// Add (or take away) a gambit from the search input.
gambit: function(gambit) {

	// Get the initial length of the search text.
	var initialLength = $.trim(ETMembers.formInput.val()).length;

	// Make a regular expression to find any instances of the gambit already in there.
	var safe = gambit.replace(/([?^():\[\]])/g, "\\$1");
	var regexp = new RegExp("( ?\\+ *" + safe + " *$|^ *" + safe + " *\\+ ?| ?\\+ *" + safe + "|^ *" + safe + " *$)", "i");

	// If there is an instance, take it out.
	if (ETMembers.formInput.val().match(regexp)) ETMembers.formInput.val(ETMembers.formInput.val().replace(regexp, ""));

	// Otherwise, insert the gambit with a +, -, or ! before it.
	else {
		var insert = (initialLength ? " + " : "") + gambit;
		ETMembers.formInput.focus();
		ETMembers.formInput.val(ETMembers.formInput.val() + insert);

		// If there is an instance of "?" in the gambit, we want to select it so the user can type over it.
		var placeholderIndex, placeholder;
		if (insert.indexOf("?") != -1) {
			placeholderIndex = insert.indexOf("?");
			placeholder = "?";
		}
		if (placeholderIndex) {
			ETMembers.formInput.selectRange(initialLength + placeholderIndex, initialLength + placeholderIndex + placeholder.length);
		}
	}
},

// Open the "create member" sheet.
loadCreateMemberSheet: function(formData) {
	ETSheet.loadSheet("createMemberSheet", "members/create.view", function() {
		$(this).find("form").ajaxForm("submit", ETMembers.loadCreateMemberSheet);
	}, formData);
}

};

$(function() {
	ETMembers.init();
});
