// Conversation JavaScript

var ETConversation = {

// Conversation details.
id: 0,
title: "",
channel: "",
slug: "",
startFrom: 0,
searchString: null,
postCount: 0,

updateInterval: null,
editingReply: false, // Are we typing a reply?
editingPosts: 0, // Number of posts being edited.

// Initialize:
init: function() {

	// If we're viewing an existing conversation...
	if (ET.conversation) {

		// Get the details.
		this.id = ET.conversation.conversationId;
		this.postCount = ET.conversation.countPosts;
		this.startFrom = ET.conversation.startFrom;
		this.channel = ET.conversation.channel;
		this.slug = ET.conversation.slug;
		this.searchString = ET.conversation.searchString;

		// Make the controls into a popup button.
		if ($("#conversationControls").length)
			$("#conversation .search").after($("#conversationControls").popup({
				alignment: "right",
				content: "<i class='icon-cog'></i> <span class='text'>"+T("Controls")+"</span> <i class='icon-caret-down'></i>"
			}).find(".button").addClass("big").end());

		// Set up the timeline scrubber.
		ETScrubber.body = $("#conversation");
		ETScrubber.scrubber = $("#conversation .scrubberContent");
		ETScrubber.items = $("#conversationPosts");
		ETScrubber.count = this.postCount;
		ETScrubber.perPage = ET.postsPerPage;
		ETScrubber.moreText = T("Load more posts");
		ETScrubber.startFrom = this.startFrom;

		// Set a callback that will load new post data.
		ETScrubber.loadItemsCallback = function(position, success, index) {
			if (position == Infinity) position = "999999"; // Kind of hackish? Meh...

			// If this "position" is an index in the timeline (eg. 201004), split it into year/month for the request.
			if (index) {
				position = (""+position).substr(0, 4)+"/"+(""+position).substr(4, 2);
			}

			// Make the request for post data.
			$.ETAjax({
				url: "conversation/index.ajax/"+ETConversation.id+"/"+position,
				data: {search: ETConversation.searchString},
				success: function(data) {
					var items = success(data);
					ETConversation.initPost(items);
					ETConversation.redisplayAvatars();
				},
				global: false
			});
		}

		// Set a callback that will run whenever we scroll to a specific index. We need it to change the URL.
		ETScrubber.scrollToIndexCallback = function(index) {
			var position;
			if (index == Infinity) position = "last";
			else position = (""+index).substr(0, 4)+"/"+(""+index).substr(4, 2);
			$.history.load(ETConversation.slug+"/"+position, true);
		}

		// Initialize the scrubber.
		ETScrubber.init();

		// When the "add a reply" button in the sidebar is clicked, we trigger a click on the "now" item in
		// the scrubber, and also on the reply textarea.
		$("#jumpToReply").click(function(e) {
			e.preventDefault();
			$(".scrubber-now a").click();
			setTimeout(function() {
				$("#reply textarea").click();
			}, 1);
		});

		// Start the automatic reload timeout.
		this.updateInterval = new ETIntervalCallback(this.update, ET.conversation.updateInterval);

		// Store the conversation title and add a click handler to edit it.
		this.title = $("#conversationTitle a").text() || $("#conversationTitle").text();
		$("#conversationTitle a").live("click", function(e) {
			e.preventDefault();
			ETConversation.editTitle();
		});

		// Add click handlers to the various conversation controls.
		$("#control-sticky").click(function(e) {
			e.preventDefault();
			ETConversation.toggleSticky();
		});
		$("#control-lock").click(function(e) {
			e.preventDefault();
			ETConversation.toggleLock();
		});
		$("#control-ignore").click(function(e) {
			e.preventDefault();
			ETConversation.toggleIgnore();
		});
		$("#control-delete").click(function(e) {
			// Pause all auto-updaters so that we don't try to update when we regain focus
			// on the window from the confirm popup.
			ETIntervalCallback.pause();

			if (!ETConversation.confirmDelete()) {
				e.preventDefault();
				ETIntervalCallback.resume();
			}
		});

		// Add tooltips to labels.
		$("#conversationHeader .label").tooltip();

		// Initialize the posts.
		this.initPosts();

		// If there's a post ID in the URL hash (eg. p1234), highlight that post, and scroll to it.
		var hash = window.location.hash.replace("#", "");
		if (hash.substr(0, 1) == "p" && $("#"+hash).length) {
			ETConversation.highlightPost($("#"+hash));
			setTimeout(function(){
				ETConversation.scrollTo($("#"+hash).offset().top - 10);
			}, 100);
		}

		// If we scroll below the header, pop the conversation title up in the forum header.
		$(window).scroll(function() {
			var y = $(this).scrollTop();
			var title = $("#forumTitle");
			if (y > $("#hdr").height()) {
				if (!title.data("old")) {
					title.data("old", title.html()).html("<a href='#'></a>").find("a").text(ETConversation.title);
					title.find("a").click(function(e) {
						e.preventDefault();
						$.scrollTo(0, "fast");
					});
				}
			} else if (title.data("old")) {
				title.html(title.data("old")).data("old", null);
			}
		});

	}

	// If we're starting a new conversation...
	else {

		// Auto-grow the title input.
		$("#conversationTitle input").autoGrowInput({
		    comfortZone: 30,
		    minWidth: 300,
		    maxWidth: 500
		}).trigger("update");

		$("#membersAllowedSheet").parents("form").remove();

		// Show the "change channel" sheet straight away!
		ETConversation.changeChannel();
	}

	// Add click handlers to change the channel and members allowed.
	$("#control-changeChannel").click(function(e) {
		e.preventDefault();
		ETConversation.changeChannel();
	});
	$("#control-changeMembersAllowed").click(function(e) {
		e.preventDefault();
		ETConversation.changeMembersAllowed();
	});

	// Make sure channels always have tooltips.
	$(".channels a").tooltip({alignment: "left", delay: 250, className: "withArrow withArrowBottom"});

	// Set up a members allowed popup if there's a "x others" link in the list.
	ETMembersAllowedTooltip.init($("#conversationPrivacy .allowedList .showMore"), function() {return ETConversation.id;}, true);

	// If there's a reply box, initilize it.
	if ($("#reply").length) ETConversation.initReply();

	// Add an onbeforeunload handler (to warn the user if they have an unsaved post/draft).
	$(window).bind("beforeunload.conversation", ETConversation.beforeUnload);
},


// Scroll to a specific position, applying an animation and taking the fixed conversation header into account.
scrollTo: function(position) {
	ETScrubber.scrollTo(position);
},

// On page exit, display a confirmation message if the user is editing posts or hasn't saved their reply.
beforeUnload: function onbeforeunload() {
	if (ETConversation.editingPosts > 0) return T("message.confirmLeave");
	else if (ETConversation.editingReply) return T("message.confirmDiscardReply");
},



//***** REPLY AREA

replyShowing: false,

// Initialize the reply section: disable/enable buttons, add click events, etc.
initReply: function() {

	var textarea = $("#reply textarea");
	ETConversation.editingReply = false;

	// Auto resize our reply textareas
	textarea.TextAreaExpander(200, 700);

	// Disable the "post reply" button if there's not a draft. Disable the save draft button regardless.
	if (!textarea.val()) $("#reply .postReply").disable();
	$("#reply .saveDraft").disable();

	// Add event handlers on the textarea to enable/disable buttons.
	textarea.keyup(function(e) {
		if (e.ctrlKey) return;
		$("#reply .postReply, #reply .saveDraft")[$(this).val() ? "enable" : "disable"]();
		ETConversation.editingReply = $(this).val() ? true : false;
	});

	if (ET.mentions) new ETAutoCompletePopup($("#reply textarea"), "@");

	// Add click events to the buttons.
	$("#reply .saveDraft").click(function(e){ ETConversation.saveDraft(); e.preventDefault(); });
	$("#reply .discardDraft").click(function(e){ ETConversation.discardDraft(); e.preventDefault(); });
	$("#reply .postReply").click(function(e){
		if (ETConversation.id) ETConversation.addReply();
		else ETConversation.startConversation();
		e.preventDefault();
	});

	// If the user can actually reply, condense the box so it expands when clicked on.
	if (!$("#reply").hasClass("logInToReply") && ETConversation.id) {

		// Make the reply box a placeholder.
		$("#reply").addClass("replyPlaceholder");

		$("#reply").click(function(e) {
			if (!ETConversation.replyShowing) {

				$(this).trigger("change");

				// Save the scroll position and then focus on the textarea.
				var scrollTop = $(document).scrollTop();
				$("#reply textarea").focus();
				$.scrollTo(scrollTop);

				// Scroll to the bottom of the reply area.
				$.scrollTo("#reply", "slow");
			}
			e.stopPropagation();
		});

		$("#reply").change(function(e) {
			if (!ETConversation.replyShowing) {
				ETConversation.replyShowing = true;
				$("#reply").removeClass("replyPlaceholder");

				// Put the cursor at the end of the textarea.
				var pos = textarea.val().length;
				textarea.selectRange(pos, pos);
			}
		});

		// If there's something in the reply textarea, show it.
		if ($("#reply textarea").val()) $("#reply").trigger("change");

		$(document).click(function(e) { ETConversation.hideReply(); });

	}

	$("#reply .controls a").tooltip();
	$("#reply [name=discardDraft]").tooltip();

	// Register the Ctrl+Enter shortcut.
	textarea.keydown(function(e) {
		if (e.ctrlKey && e.which == 13 && !$("#reply .postReply").prop("disabled")) {
			$("#reply .postReply").click();
			e.preventDefault();
		}
	});
},

// Condense the reply box back into a placeholder.
hideReply: function() {
	if (!ETConversation.replyShowing || $("#reply textarea").val()) return;

	// Save the scroll top and height.
	var scrollTop = $(document).scrollTop();
	var oldHeight = $("#reply .postContent").height();

	// Condense it back into a placeholder.
	ETConversation.replyShowing = false;
	$("#reply").addClass("replyPlaceholder");

	// Animate the change and scroll back to where we were before.
	var newHeight = $("#reply .postContent").height();
	$("#reply .postContent").height(oldHeight).animate({height: newHeight}, "fast", function() {
		$(this).height("");
	});
	$.scrollTo(scrollTop);

	ETConversation.editingReply = false;
},

resetReply: function() {
	$("#reply textarea").val("");
	ETConversation.togglePreview("reply", false);
	ETConversation.hideReply();
},

// Add a reply.
addReply: function() {
	var content = $("#reply textarea").val();

	// Disable the reply/draft buttons.
	$("#reply .postReply, #reply .saveDraft").disable();

	// Make the ajax request.
	$.ETAjax({
		url: "conversation/reply.ajax/"+ETConversation.id,
		type: "post",
		data: {conversationId: ETConversation.id, content: content},
		success: function(data) {

			// If there are messages, enable the reply/draft buttons and don't continue.
			if (!data.postId) {
				$("#reply .postReply, #reply .saveDraft").enable();
				return;
			}

			// Hide messages which may have been previously triggered.
			ETMessages.hideMessage("waitToReply");
			ETMessages.hideMessage("emptyPost");

			// Hide the draft label, clear the textarea, and initialize the reply area again.
			$("#conversationHeader .labels .label-draft").remove();
			ETConversation.resetReply();

			ETConversation.postCount++;

			// Create a dud "more" block and then add the new post to it.
			var moreItem = $("<li></li>").appendTo("#conversationPosts");
			ETScrubber.count = ETConversation.postCount;
			var items = ETScrubber.addItems(ETConversation.postCount - 1, data.view, moreItem, true);
			ETConversation.redisplayAvatars();
			ETConversation.initPost(items);

			// Star the conversation if the user has the "star on reply" option on.
			if (data.starOnReply) {
				toggleStarState(ETConversation.id, true);
			}

			// Reset the post-checking timeout.
			ETConversation.updateInterval.reset(ET.conversationUpdateIntervalStart);

		},
		beforeSend: function() {
			createLoadingOverlay("reply", "reply");
		},
		complete: function() {
			hideLoadingOverlay("reply", false);
		}
	});
},

// Start a conversation.
startConversation: function(draft) {

	// Prepare the conversation data.
	var title = $("#conversationTitle input").val();
	var content = $("#reply textarea").val();
	var channel = $("#conversationHeader .channels :radio:checked").val();

	// Disable the post reply and save draft buttons.
	$("#reply .postReply, #reply .saveDraft").disable();

	// Make the ajax request.
	var data = {title: title, content: content, channel: channel};
	if (draft) data.saveDraft = "1";
	$.ETAjax({
		url: "conversation/start.ajax",
		type: "post",
		data: data,
		beforeSend: function() {
			createLoadingOverlay("reply", "reply");
			ETConversation.editingReply = false;
		},
		complete: function() {
			hideLoadingOverlay("reply", false);
			$("#reply .postReply, #reply .saveDraft").enable();
		},
		success: function(data) {
			// If there are messages, enable the reply/draft buttons and don't continue.
			if (data.messages) {
				if (data.messages.title) $("#conversationTitle input").focus();
				if (data.messages.content) $("#reply textarea").focus();
				return;
			}
		}
	});
},

// Save a draft.
saveDraft: function() {

	// If this is a new conversation, just use the startConversation function
	if (!ETConversation.id) {
		ETConversation.startConversation(true);
		return;
	}
	// Make the ajax request.
	$.ETAjax({
		url: "conversation/reply.ajax/"+ETConversation.id,
		type: "post",
		data: {saveDraft: true, content: $("#reply textarea").val()},
		beforeSend: function() {
			createLoadingOverlay("reply", "reply");
		},
		complete: function() {
			hideLoadingOverlay("reply", false);
		},
		success: function(data) {
			if (data.messages) return;
			ETMessages.hideMessage("emptyPost");

			// Show the draft label, disable the save draft button, and enable the discard draft button.
			$("#conversationHeader .labels").html(data.labels);
			$("#reply .saveDraft").disable();
			ETConversation.editingReply = false;
		}
	});
},

// Discard a draft.
discardDraft: function() {

	// If there are no posts in the conversation (ie. it's a draft conversation), delete the conversation.
	if (this.postCount == 0) {
		if ($("#control-delete").length && ETConversation.confirmDelete()) window.location = $("#control-delete").attr("href");
		else if (confirm(T("message.confirmDiscardReply"))) window.location = $("#forumTitle a").attr("href");
		$(window).unbind("beforeunload.conversation");
		return;
	}

	// Confirm this action!
	else {
		if (!confirm(T("message.confirmDiscardReply"))) return;
	}

	// Make the ajax request.
	$.ETAjax({
		url: "conversation/reply.ajax/" + ETConversation.id,
		type: "post",
		data: {discardDraft: true},
		beforeSend: function() {
			createLoadingOverlay("reply", "reply");
		},
		complete: function() {
			hideLoadingOverlay("reply", false);
		},
		success: function(data) {

			// Hide the draft label and collapse the reply area.
			$("#conversationHeader .labels").html(data.labels);
			ETConversation.resetReply();

		}
	});
},


//***** POSTS

// Get new posts at the end of the conversation by comparing our post count with the server's.
update: function() {

	// Don't do this if we're searching, or if we haven't loaded the end of the conversation.
	if (ETConversation.searchString || ETScrubber.loadedItems.indexOf(ETConversation.postCount - 1) == -1) return;

	// Make the request for post data.
	$.ETAjax({
		url: "conversation/index.ajax/"+ETConversation.id+"/"+ETConversation.postCount,
		success: function(data) {

			// If there are new posts, add them.
			if (ETConversation.postCount < data.countPosts) {
				ETConversation.postCount = data.countPosts;

				// Create a dud "more" block and then add the new post to it.
				var moreItem = $("<li></li>").appendTo("#conversationPosts");
				ETScrubber.count = ETConversation.postCount;
				ETScrubber.addItems(data.startFrom, data.view, moreItem, true);

				var interval = ET.conversationUpdateIntervalStart;

			}

			// Otherwise, multiply the update interval by our config setting.
			else var interval = Math.min(ET.conversationUpdateIntervalLimit, ETConversation.updateInterval.interval * ET.conversationUpdateIntervalMultiplier);

			ETConversation.updateInterval.reset(interval);

		},
		global: false
	});

},

// Initialize the posts.
initPosts: function() {

	// Add tooltips to post controls.
	$("#conversationPosts .controls a").tooltip({alignment: "center"});
	$("#conversationPosts h3 a").tooltip({alignment: "left", className: "withArrow withArrowBottom"});
	$("#conversationPosts .time").tooltip({alignment: "left", className: "withArrow withArrowBottom"});
	$("#conversationPosts .online").tooltip({alignment: "left", offset: [-9, 0], className: "withArrow withArrowBottom"}).css("cursor", "pointer");

	// Add click handlers to the post controls.
	$("#conversationPosts .controls .control-edit").live("click", function(e) {
		var postId = $(this).parents(".post").data("id");
		ETConversation.editPost(postId);
		e.preventDefault();
	});

	$("#conversationPosts .controls .control-delete").live("click", function(e) {
		var postId = $(this).parents(".post").data("id");
		ETConversation.deletePost(postId);
		e.preventDefault();
	});

	$("#conversationPosts .controls .control-restore").live("click", function(e) {
		var postId = $(this).parents(".post").data("id");
		ETConversation.restorePost(postId);
		e.preventDefault();
	});

	$("#conversationPosts .post:not(.edit) .controls .control-quote").live("click", function(e) {
		var postId = $(this).parents(".post").data("id");
		ETConversation.quotePost(postId, e.shiftKey);
		e.preventDefault();
	});

	// Add a click handler to any "post links" to scroll back up to the right post, if it's loaded.
	$("#conversationPosts .postBody a[rel=post]").live("click", function(e) {
		var id = $(this).data("id");

		$("#conversationPosts .post").each(function() {
			if ($(this).data("id") == id) {
				ETConversation.scrollTo($(this).offset().top - 10);
				ETConversation.highlightPost($("#p"+id));
				e.preventDefault();
				return false;
			}
		});
	});

	ETConversation.initPost($("#conversationPosts .post"));
},

initPost: function(post) {
	ETConversation.collapseQuotes(post);
},

// Collapse quotes and add expand buttons.
collapseQuotes: function(items) {
	$(".postBody blockquote:not(.collapsed)", items)
		.addClass("collapsed")
		.each(function() {
			if ($(this)[0].scrollHeight <= $(this).innerHeight() + 20) {
				$(this).removeClass("collapsed");
				return;
			}

			var link = $("<a href='#' class='expand'><i class='icon-ellipsis-horizontal'></i></a>");
			link.click(function(e) {
				e.preventDefault();
				$(this).parents("blockquote").removeClass("collapsed");
				$(this).remove();
			});
			$(this).append(link);
		});
},

// Highlight a post.
highlightPost: function(post) {
	$("#conversationPosts .post.highlight").removeClass("highlight");
	$(post).addClass("highlight");
	setTimeout(function() {
		$(post).removeClass("highlight");
	}, 2000);
},

// Hide consecutive avatars from the same member.
redisplayAvatars: function() {

	// Loop through the avatars in the posts area and compare each one's src with the one before it.
	// If they're the same, hide it.
	var prevId = null;
	$("#conversationPosts > li").each(function() {
		if (prevId == $(this).find("div.post").data("memberid"))
			$(this).find("div.avatar").hide();
		else
			$(this).find("div.avatar").show();

		prevId = $(this).find("div.post").data("memberid");

	});

},

// Delete a post.
deletePost: function(postId) {

	$.hideToolTip();

	// Make the ajax request.
	$.ETAjax({
		url: "conversation/deletePost.ajax/" + postId,
		beforeSend: function() {
			createLoadingOverlay("p" + postId, "p" + postId);
		},
		complete: function() {
			hideLoadingOverlay("p" + postId, true);
		},
		success: function(data) {
			if (data.messages) return;
			$("#p"+postId).replaceWith(data.view);
			ETConversation.redisplayAvatars();
		}
	});
},

// Restore a post.
restorePost: function(postId) {

	$.hideToolTip();

	// Make the ajax request.
	$.ETAjax({
		url: "conversation/restorePost.ajax/" + postId,
		beforeSend: function() {
			createLoadingOverlay("p" + postId, "p" + postId);
		},
		complete: function() {
			hideLoadingOverlay("p" + postId, true);
		},
		success: function(data) {
			if (data.messages) return;
			$("#p"+postId).replaceWith(data.view);
			ETConversation.redisplayAvatars();
			ETConversation.collapseQuotes($("#p"+postId));

		}
	});
},

// Edit a post - make the post area into a textarea.
editPost: function(postId) {

	$.hideToolTip();
	var post = $("#p" + postId);

	$.ETAjax({
		url: "conversation/editPost.ajax/" + postId,
		type: "get",
		beforeSend: function() {
			createLoadingOverlay("p" + postId, "p" + postId);
		},
		complete: function() {
			hideLoadingOverlay("p" + postId, true);
		},
		success: function(data) {
			if (data.messages || data.modalMessage) return;
			ETConversation.updateEditPost(postId, data.view);
		}
	});
},

updateEditPost: function(postId, html) {
	var post = $("#p" + postId);

	ETConversation.editingPosts++;

	var startHeight = $(".postContent", post).height();

	// Replace the post HTML with the new stuff we just got.
	post.replaceWith($(html).find(".post"));
	var newPost = $("#p" + postId);
	var textarea = $("textarea", newPost);

	// Save the old post HTML for later.
	newPost.data("oldPost", post);

	// Set up the text area.
	var len = textarea.val().length;
	textarea.TextAreaExpander(200, 700).focus().selectRange(len, len);
	new ETAutoCompletePopup(textarea, "@");

	// Add click handlers to the cancel/submit buttons.
	$(".cancel", newPost).click(function(e) {
		e.preventDefault();
		ETConversation.cancelEditPost(postId);
	});
	$(".submit", newPost).click(function(e) {
		e.preventDefault();
		ETConversation.saveEditPost(postId, textarea.val());
	});

	// Animate the post's height.
	var newHeight = $(".postContent", newPost).height();
	$(".postContent", newPost).height(startHeight).animate({height: newHeight}, "fast", function() {
		$(this).height("");
	});

	ETConversation.redisplayAvatars();

	// Scroll to the bottom of the edit area if necessary.
	var scrollTo = newPost.offset().top + newHeight - $(window).height() + 10;
	if ($(document).scrollTop() < scrollTo) $.scrollTo(scrollTo, "slow");

	// Regsiter the Ctrl+Enter and Escape shortcuts on the post's textarea.
	textarea.keydown(function(e) {
		if (e.ctrlKey && e.which == 13) {
			ETConversation.saveEditPost(postId, this.value);
			e.preventDefault();
		}
		if (e.which == 27) {
			ETConversation.cancelEditPost(postId);
			e.preventDefault();
		}
	});
},

// Save an edited post to the database.
saveEditPost: function(postId, content) {

	// Disable the buttons.
	var post = $("#p" + postId);
	$(".button", post).disable();

	// Make the ajax request.
	$.ETAjax({
		url: "conversation/editPost.ajax/" + postId,
		type: "post",
		data: {content: content, save: true},
		beforeSend: function() {
			createLoadingOverlay("p" + postId, "p" + postId);
		},
		complete: function() {
			hideLoadingOverlay("p" + postId, true);
			$(".button", post).enable();
		},
		success: function(data) {
			if (data.messages) return;

			var startHeight = $(".postContent", post).height();

			// Replace the post HTML with the new post we just got.
			post.replaceWith(data.view);
			var newPost = $("#p" + postId);

			// Animate the post's height.
			var newHeight = $(".postContent", newPost).height();
			$(".postContent", newPost).height(startHeight).animate({height: newHeight}, "fast", function() {
				$(this).height("");
			});

			ETConversation.editingPosts--;
			ETConversation.redisplayAvatars();
			ETConversation.initPost(newPost);
		}
	});
},

// Cancel editing a post.
cancelEditPost: function(postId) {
	ETConversation.editingPosts--;
	var post = $("#p" + postId);

	var scrollTop = $(document).scrollTop();

	// Change the post control and body HTML back to what it was before.
	var startHeight = $(".postContent", post).height();
	post.replaceWith(post.data("oldPost"));
	var newPost = $("#p" + postId);

	// Animate the post's height.
	var newHeight = $(".postContent", newPost).height();
	$(".postContent", newPost).height(startHeight).animate({height: newHeight}, "fast", function() {
		$(this).height("");
	});

	ETConversation.initPost(newPost);

	$.scrollTo(scrollTop);
},

// Quote a post.
quotePost: function(postId, multi) {
	var selection = ""+$.getSelection();
	$.ETAjax({
		url: "conversation/quotePost.json/" + postId,
		success: function(data) {
			var top = $(document).scrollTop();
			ETConversation.quote("reply", selection ? selection : data.content, data.postId + ":" + data.member, null, true);

			// If we're "multi" quoting (i.e. shift is being held down), keep our scroll position static.
			// Otherwise, scroll down to the reply area.
			if (!multi) {
				$("#jumpToReply").click();
			} else {
				$("#reply").change();
				$.scrollTo(top);
			}
		},
		global: true
	});
},


//***** MEMBERS ALLOWED

initMembersAllowed: function() {

	// Show or hide the "Click on a member's name to remove them" text depending on if the conversation is private or not.
	$("#addMemberForm .help").toggle($("#membersAllowedSheet .allowedList .name").length ? true : false);

	var ac = new ETAutoCompletePopup($("#addMemberForm input[name=member]"), false, function(member) {
		ETConversation.addMember(member.name);
	});

	// Wrap a form around the add member form so that the submit event works properly.
	$("#addMemberForm").wrap("<form>");
	$("#addMemberForm").parent().submit(function(e) {
		ETConversation.addMember($("#addMemberForm input[name=member]").val());
		ac.stop();
		e.preventDefault();
	});

	// Add click handlers to each of the names, to remove them.
	$("#membersAllowedSheet .allowedList .name a").die("click").live("click", function(e) {
		e.preventDefault();
		ETConversation.removeMember($(this).data("type"), $(this).data("id"));
	});

	// Focus on the name input.
	$("#addMemberForm input[name=member]").focus();

},

// Open up the "chang members allowed" sheet.
changeMembersAllowed: function() {
	ETSheet.loadSheet("membersAllowedSheet", "conversation/membersAllowed.ajax/"+ETConversation.id, function() {
		ETConversation.initMembersAllowed();
	});
},

// Add a member to the conversation.
addMember: function(name) {
	if (!name) return;

	$.ETAjax({
		id: "addMember",
		url: "conversation/addMember.ajax/" + ETConversation.id,
		type: "post",
		data: {member: name},
		success: function(data) {

			// If there was an error, select the contents of the name input.
			if (data.messages) $("#addMemberForm input[name=member]").select();

			// Otherwise, show the help text, clear the name input, and update the members allowed summary,
			// list, and labels.
			else {
				$("#addMemberForm .help").show();
				$("#addMemberForm input[name=member]").val("");
				$("#conversationPrivacy .allowedList").html(data.allowedSummary);
				$("#membersAllowedSheet .allowedList").html(data.allowedList);
				$("#conversationHeader .labels").html(data.labels);
				ETMembersAllowedTooltip.init($("#conversationPrivacy .allowedList .showMore"), function() {return ETConversation.id;}, true);
			}
		}
	});
},

// Remove a member from the members allowed list.
removeMember: function(type, id) {

	var data = {};
	data[type] = id;
	$.ETAjax({
		id: "addMember",
		url: "conversation/removeMember.ajax/" + ETConversation.id,
		type: "post",
		data: data,
		success: function(data) {

			// Update the members allowed summary, list, and labels.
			$("#conversationPrivacy .allowedList").html(data.allowedSummary);
			$("#membersAllowedSheet .allowedList").html(data.allowedList);
			$("#addMemberForm .help").toggle($("#membersAllowedSheet .allowedList .name").length ? true : false);
			$("#conversationHeader .labels").html(data.labels);
			ETMembersAllowedTooltip.init($("#conversationPrivacy .allowedList .showMore"), function() {return ETConversation.id;}, true);

		}
	});

},


//***** CHANNELS

// Open the change channel sheet.
changeChannel: function() {
	ETSheet.loadSheet("changeChannelSheet", "conversation/changeChannel.view/"+ETConversation.id, function() {

		// Hide the radio buttons, and set up a handler for when they're changed.
		$("#changeChannelSheet .channelList input").hide().click(function() {
			$("#changeChannelSheet form").submit();
		});

		$("#changeChannelSheet .buttons").hide();

		// Add tooltips to channels that cannot be changed to.
		$("#changeChannelSheet .channelList li").tooltip({alignment: "left", offset: [20, 43], className: "hoverable"});

		// Add a submit event to the form.
		$("#changeChannelSheet form").submit(function(e) {
			e.preventDefault();
			var channelId = $(this).find("input:checked").val();
			$.ETAjax({
				url: "conversation/save.json/" + ETConversation.id,
				type: "post",
				data: {channel: channelId},
						success: function(data) {
					if (data.messages) return;

					// Update the members allowed summary.
					$("#conversationPrivacy .allowedList").html(data.allowedSummary);
					ETMembersAllowedTooltip.init($("#conversationPrivacy .allowedList .showMore"), function() {return ETConversation.id;}, true);

					// Replace the conversation's channel with the new one.
					ETConversation.channel = channelId;
					$("#conversationHeader .channels").replaceWith(data.channelPath);

					ETSheet.hideSheet("changeChannelSheet");
				}
			})
		});
	});
},


//***** CONVERSATION PROPERTIES

// Edit the title of the conversation.
editTitle: function() {
	if (!$("#conversationTitle").hasClass("editing")) {

		// Replace the title tag with an input.
		var title = $("#conversationTitle a").text().trim();
		$("#conversationTitle").html("<input type='text' class='text' maxlength='100'/>").addClass("editing");
		$("#conversationTitle input").val(title).autoGrowInput({
		    comfortZone: 30,
		    minWidth: 250,
		    maxWidth: 500
		}).trigger("update");

		// Add a key press and blur handler to the field.
		$("#conversationTitle input").select().blur(function() { ETConversation.saveTitle(); }).keydown(function(e) {
			if (e.which == 13) ETConversation.saveTitle(); // Enter
			if (e.which == 27) ETConversation.saveTitle(true); // Escape
		});
	}
},

// Save the conversation title.
saveTitle: function(cancel) {
	if ($("#conversationTitle").hasClass("editing")) {

		// Return the conversation title input back to normal.
		var title = $("#conversationTitle input").val();
		if (!title || cancel) title = ETConversation.title;
		var sanitized = $('<div/>').text(title).html();
		$("#conversationTitle").html("<a href='#'>"+sanitized+"</a>").removeClass("editing");

		// If we're cancelling, that's all we need to do.
		if (cancel || ETConversation.title == title) return;

		// Otherwise, update the document title with the new title.
		$(document).attr("title", $(document).attr("title").replace(ETConversation.title, title));
		ETConversation.title = title;

		// And save it.
		$.ETAjax({
			url: "conversation/save.json/" + ETConversation.id,
			type: "post",
			data: {title: title},
			global: true
		});
	}
},

// Toggle sticky.
toggleSticky: function() {
	$("#control-sticky span").html(T($("#control-sticky span").html() == T("Sticky") ? "Unsticky" : "Sticky"));
	$.ETAjax({
		url: "conversation/sticky.ajax/" + ETConversation.id,
		success: function(data) {
			$("#conversationHeader .labels").html(data.labels);
		}
	});
},

// Toggle lock.
toggleLock: function() {
	$("#control-lock span").html(T($("#control-lock span").html() == T("Lock") ? "Unlock" : "Lock"));
	$.ETAjax({
		url: "conversation/lock.ajax/" + ETConversation.id,
		success: function(data) {
			$("#conversationHeader .labels").html(data.labels);
		}
	});
},

// Toggle lock.
toggleIgnore: function() {
	$("#control-ignore span").html(T($("#control-ignore span").html() == T("Ignore conversation") ? "Unignore conversation" : "Ignore conversation"));
	$.ETAjax({
		url: "conversation/ignore.ajax/" + ETConversation.id,
		success: function(data) {
			$("#conversationHeader .labels").html(data.labels);
		}
	});
},

// Confirm deletion of the conversation.
confirmDelete: function() {
	return confirm(T("message.confirmDelete"));
},


//***** POST FORMATTING

// Add a quote to a textarea.
quote: function(id, quote, name, postId, insert) {
	var argument = postId || name ? (postId ? postId + ":" : "") + (name ? name : "Name") : "";
	var startTag = "[quote" + (argument ? "=" + argument : "") + "]" + (quote ? quote : "");
	var endTag = "[/quote]";

	// If we're inserting the quote, add it to the end of the textarea.
	if (insert) ETConversation.insertText($("#" + id + " textarea"), startTag + endTag + "\n");

	// Otherwise, wrap currently selected text with the quote.
	else ETConversation.wrapText($("#" + id + " textarea"), startTag, endTag);
},

// Add text to the reply area at the very end, and move the cursor to the very end.
insertText: function(textarea, text) {
	textarea = $(textarea);
	textarea.focus();
	textarea.val(textarea.val() + text);
	textarea.focus();

	// Trigger the textarea's keyup to emulate typing.
	textarea.trigger("keyup");
},

// Add text to the reply area, with the options of wrapping it around a selection and selecting a part of it when it's inserted.
wrapText: function(textarea, tagStart, tagEnd, selectArgument, defaultArgumentValue) {

	textarea = $(textarea);

	// Save the scroll position of the textarea.
	var scrollTop = textarea.scrollTop();

	// Work out what text is currently selected.
	var selectionInfo = textarea.getSelection();
	if (textarea.val().substring(selectionInfo.start, selectionInfo.start + 1).match(/ /)) selectionInfo.start++;
	if (textarea.val().substring(selectionInfo.end - 1, selectionInfo.end).match(/ /)) selectionInfo.end--;
	var selection = textarea.val().substring(selectionInfo.start, selectionInfo.end);

	// Work out the text to insert over the selection.
	selection = selection ? selection : (defaultArgumentValue ? defaultArgumentValue : "");
	var text = tagStart + selection + (typeof tagEnd != "undefined" ? tagEnd : tagStart);

	// Replace the textarea's value.
	textarea.val(textarea.val().substr(0, selectionInfo.start) + text + textarea.val().substr(selectionInfo.end));

	// Scroll back down and refocus on the textarea.
	textarea.scrollTo(scrollTop);
	textarea.focus();

	// If a selectArgument was passed, work out where it is and select it. Otherwise, select the text that was selected
	// before this function was called.
	if (selectArgument) {
		var newStart = selectionInfo.start + tagStart.indexOf(selectArgument);
		var newEnd = newStart + selectArgument.length;
	} else {
		var newStart = selectionInfo.start + tagStart.length;
		var newEnd = newStart + selection.length;
	}
	textarea.selectRange(newStart, newEnd);

	// Trigger the textarea's keyup to emulate typing.
	textarea.trigger("keyup");
},

// Toggle preview on an editing area.
togglePreview: function(id, preview) {

	// If the preview box is checked...
	if (preview) {

		// Hide the formatting buttons.
		$("#" + id + " .formattingButtons").hide();
		$("#" + id + "-preview").html("");

		// Hide the attachments block.
		$(".attachments-edit").hide();

		// Get the formatted post and show it.
		$.ETAjax({
			url: "conversation/preview.ajax",
			type: "post",
			data: {content: $("#" + id + " textarea").val()},
				success: function(data) {

				// Keep the minimum height.
				$("#" + id + "-preview").css("min-height", $("#" + id + "-textarea").innerHeight());

				// Hide the textarea, and show the preview.
				$("#" + id + " textarea").hide();
				$("#" + id + "-preview").show()
				$("#" + id + "-preview").html(data.content);
			}
		});
	}

	// The preview box isn't checked...
	else {
		// Show the formatting buttons and the textarea; hide the preview area.
		$("#" + id + " .formattingButtons").show();
		$("#" + id + " textarea").show();
		$("#" + id + "-preview").hide();

		// Show the attachments block.
		$(".attachments-edit").show();
	}
}

};

$(function() {
	ETConversation.init();
});
