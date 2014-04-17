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
		content: "Sort By "+selected.text()+" <i class='icon-caret-down'></i>"
	}).find(".button").addClass("big").end());

	// Set up the letter scrubber.
	ETScrubber.body = $("#memberListBody");
	ETScrubber.scrubber = $("#members .scrubberContent");
	ETScrubber.items = $("#memberList");
	ETScrubber.count = ET.countMembers;
	ETScrubber.perPage = ET.membersPerPage;
	ETScrubber.moreText = T("Load more members");
	ETScrubber.startFrom = ET.startFrom;

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
