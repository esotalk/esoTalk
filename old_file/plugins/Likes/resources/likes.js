$(function() {
	
	$(".likes .showMore").live("click", function(e) {
		e.preventDefault();
		ETSheet.loadSheet("onlineSheet", "conversation/liked.view/"+$(this).parents(".post").data("id"));
	});

	$(".likes .like-button").live("click", function(e) {
		e.preventDefault();
		var area = $(this).parents(".likes");
		area.find(".like-button").html(area.hasClass("liked") ? "Like" : "Unlike");
		
		$.ETAjax({
			url: "conversation/"+(area.hasClass("liked") ? "unlike" : "like")+".json/"+area.parents(".post").data("id"),
			success: function(data) {
				area.find(".like-members").html(data.names);
				area.toggleClass("liked");
			}
		})
	});

});