$(function() {
	$('#conversationPosts').on('click', '.ignoredInfo a.ignoredShow', function(e) {
		e.preventDefault();
		$(this).parents('.post').removeClass('ignored');
		$(this).parent().remove();
	});
});
