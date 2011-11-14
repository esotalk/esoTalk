// Channels JavaScript

var ETChannels = {

// Initialize the channels page.
init: function() {

	// Add click handlers to the subscribe/unsubscribe buttons.
	$(".channelList .subscription .button").click(function(e) {
		e.preventDefault();
		ETChannels.toggleSubscription($(this).data("id"));
	});

},

// Toggle subscription to a channel.
toggleSubscription: function(channelId) {
	$.ETAjax({
		url: "channels/subscribe.ajax/"+channelId,
		global: true,
		success: function(data) {

			// Change the appearance of the subscription button, depending on whether or not the user is subscribed.
			$("#channel-"+channelId)
				.find(".subscription .button")
				.removeClass()
				.addClass("button")
				.addClass(data.unsubscribed ? "unsubscribed" : "subscribed")
				.html(data.unsubscribed ? T("Subscribe") : "<span class='icon-tick'></span> "+T("Subscribed"));
		}
	})
}

};

// Initialize when the page loads.
$(function() {
	ETChannels.init();
});