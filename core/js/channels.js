// Channels JavaScript

var ETChannels = {

// Initialize the channels page.
init: function() {

	// Make the controls into popups.
	$(".channelList li").each(function() {
		var item = $(this).find(".controls").first().popup({alignment: "right"});
		$(this).find(".channelControls").append(item);
	});

}

};

// Initialize when the page loads.
$(function() {
	ETChannels.init();
});