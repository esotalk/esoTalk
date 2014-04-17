$(function() {

	ETConversation.toggleBookmarked = function() {
		$("#control-bookmark span").html(T($("#control-bookmark span").html() == T("Bookmark") ? "Unbookmark" : "Bookmark"));
		$.ETAjax({
			url: "conversation/bookmark.ajax/" + ETConversation.id,
			success: function(data) {
				$("#conversationHeader .labels").html(data.labels);
			}
		});
	};

	$("#control-bookmark").click(function(e) {
		e.preventDefault();
		ETConversation.toggleBookmarked();
	});

});	
