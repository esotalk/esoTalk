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
		if ($(this).data("gambit").indexOf("?") != -1) {
			e.preventDefault();
			var placeholderIndex = $(this).data("gambit").indexOf("?");
			$("#memberSearch input[name=search]")
				.val($(this).data("gambit"))
				.selectRange(placeholderIndex, placeholderIndex + 1);
		}
	})

	// Prevent the search field from being unfocussed when a gambit is clicked.
	.bind("mousedown", function(e) {
		if ($(this).data("gambit").indexOf("?") != -1) {
			e.preventDefault();
		}
	});

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
