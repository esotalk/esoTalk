// Global JavaScript


// Change the default jQuery easing method.
/*
 * jQuery Easing v1.3 - http://gsgd.co.uk/sandbox/jquery/easing/
 *
 * Uses the built in easing capabilities added In jQuery 1.1
 * to offer multiple easing options
 *
 * TERMS OF USE - jQuery Easing
 *
 * Open source under the BSD License.
 *
 * Copyright © 2008 George McGinley Smith
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list
 * of conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 *
 * Neither the name of the author nor the names of contributors may be used to endorse
 * or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *  COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 *  EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 *  GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

// t: current time, b: begInnIng value, c: change In value, d: duration
jQuery.easing['jswing'] = jQuery.easing['swing'];

jQuery.extend( jQuery.easing,
{
	def: 'easeOutQuad',
	swing: function (x, t, b, c, d) {
		return jQuery.easing[jQuery.easing.def](x, t, b, c, d);
	},
	easeOutQuad: function (x, t, b, c, d) {
		return -c *(t/=d)*(t-2) + b;
	}
});



// Implement Array.indexOf in IE.
if (!Array.indexOf){
	Array.prototype.indexOf = function(obj) {
		for (var i = 0; i < this.length; i++) {
			if (this[i]==obj) {
				return i;
			}
		}
		return -1;
	}
}


// Translate a string, using definitions found in ET.language. addJSLanguage() must be called on
// a controller to make a definition available in ET.language.
function T(string)
{
	return typeof ET.language[string] == "undefined" ? string : ET.language[string];
}

// Desanitize an HTML string, converting HTML entities back into their ASCII equivalent.
function desanitize(value)
{
	return value.replace(/\u00a0|&nbsp;/gi, " ").replace(/&gt;/gi, ">").replace(/&lt;/gi, "<").replace(/&amp;/gi, "&");
}

// An array of "loading overlays". Loading overlays can be used to cover up a certain area when new content
// is loading.
var loadingOverlays = {};

// Create a loading overlay. id should be a unique identifier so the loading overlay can be hidden with the
// same id. The loading overlay will be sized and positions to cover the element specified by coverElementWithId.
function createLoadingOverlay(id, coverElementWithId)
{
	if (!loadingOverlays[id]) loadingOverlays[id] = 0;
	loadingOverlays[id]++;

	// Create a new loading overlay element if one doesn't already exist.
	if (!$("#loadingOverlay-" + id).length)
		$("<div/>", {id: "loadingOverlay-" + id}).addClass("loadingOverlay").appendTo($("body")).hide();
	var elm = $("#" + coverElementWithId);

	// Style and position it.
	$("#loadingOverlay-" + id).css({
		opacity: 0.6,
		position: "absolute",
		top: elm.offset().top,
		left: elm.offset().left,
		width: elm.outerWidth(),
		height: elm.outerHeight()
	}).show();
}

// Hide a loading overlay.
function hideLoadingOverlay(id, fade)
{
	loadingOverlays[id]--;
	if (loadingOverlays[id] <= 0) $("#loadingOverlay-" + id)[fade ? "fadeOut" : "remove"]();
}





//***** AJAX Functionality

// esoTalk custom AJAX plugin. This automatically handles messages, disconnection, and modal message sheets.
$.ETAjax = function(options) {

	// If this request has an ID, abort any other requests with the same ID.
	if (options.id) $.ETAjax.abort(options.id);

	// Set up the error handler. If we get an error, inform the user of the "disconnection".
	var handlerError = function(XMLHttpRequestObject, textStatus, errorThrown) {
		if (!errorThrown || errorThrown == "abort") return;

		$.ETAjax.disconnected = true;

		// Save this request's information so that it can be tried again if the user clicks "try again".
		if (!$.ETAjax.disconnectedRequest) $.ETAjax.disconnectedRequest = options;

		// Show a disconnection message.
		ETMessages.showMessage(T("message.ajaxDisconnected"), {className: "warning dismissable", id: "ajaxDisconnected"});
	};

	// Set up the success handler!
	var handlerSuccess = function(result, textStatus, XMLHttpRequestObject) {

		// If the ajax system is disconnected but this request was successful, reconnect.
		if ($.ETAjax.disconnected) {
			$.ETAjax.disconnected = false;
			ETMessages.hideMessage("ajaxDisconnected");
		}

		// If the result is empty, don't continue. ???
		// if (!result) return;

		// If there's a URL to redirect to, redirect there.
		if (typeof result.redirect != "undefined") {
			$(window).unbind("beforeunload.ajax");
			window.location = result.redirect;
			return;
		}

		// Did we get any messages? Show them via the messages system.
		if (result.messages) {
			for (var i in result.messages) {
				if (typeof result.messages[i] != "object") continue;
				ETMessages.showMessage(result.messages[i]["message"], result.messages[i]);
			}
		}

		// Set the request's messages variable to false if there were no messages - makes it easy for handlers to test whether there were messages or not.
		if (typeof result.messages == "undefined" || result.messages.length < 1) result.messages = false;

		// But hold on... if the server returned a modal message, then show that, and mark the request as NOT successful.
		if (result.modalMessage) {
			ETSheet.showSheet("messageSheet", result.view, function() {
				$("#messageSheet .buttons a").click(function(e) {
					ETSheet.hideSheet("messageSheet");
					e.preventDefault();
				});
			});
		}

		// If there's a success handler for this request, call it.
		if (options.success) options.success.apply(window, arguments);
	};

	// Clone the request's options and make a few of our own changes.
	var newOptions = $.extend(true, {data: {}}, options);
	newOptions.error = handlerError;
	newOptions.success = handlerSuccess;
	if (ET.userId) newOptions.data.userId = ET.userId;
	if (ET.token) newOptions.data.token = ET.token;

	// Prepend the full path to this forum to the URL.
	newOptions.url = ET.webPath + "/?p=" + options.url;

	var result = $.ajax(newOptions);
	if (options.id) $.ETAjax.requests[options.id] = result;

	return result;
};

$.extend($.ETAjax, {

// Resume normal activity after recovering from a disconnection: clear messages and repeat the request that failed.
resumeAfterDisconnection: function() {
	ETMessages.hideMessage("ajaxDisconnected");
	$.ETAjax(this.disconnectedRequest);
	this.disconnectedRequest = false;
},

requests: [],
disconnected: false,
disconnectedRequest: null,

// Abort a request with the specified ID.
abort: function(id) {
	if ($.ETAjax.requests[id]) $.ETAjax.requests[id].abort();
}

});


// Set up global AJAX defaults and handlers.
$(function() {

	// Append a "loading" div to the page.
	$("<div id='loading'>"+T("Loading...")+"</div>").appendTo("body").hide();

	// Set up global ajax event handlers to show/hide the loading box and configure an onbeforeunload event.
	$(document).bind("ajaxStart", function(){
		$("#loading").show();
		$(window).bind("beforeunload.ajax", function() {
			return T("message.ajaxRequestPending");
		});
	 })
	 .bind("ajaxStop", function(){
		$("#loading").fadeOut("fast");
		$(window).unbind("beforeunload.ajax");
	 });

	// Set the default AJAX request settings.
	$.ajaxSetup({timeout: 10000});

	// iOS Safari doesn't update position:fixed elements when the keyboard is up.
	// So, whenever we focus on an input or textarea, change the header's position to absolute,
	// and revert it when we lose focus.
	var iOS = (navigator.userAgent.match(/(iPad|iPhone|iPod)/g) ? true : false);
	if (iOS) {
		$("input, textarea").live('focus', function(){
			$("#hdr").css({position:'absolute'});
		});
		$("input, textarea").live('blur', function(){
			$("#hdr").css({position:''});
		});
		// Also hide tooltips.
		$.fn.tooltip = function() { return this; };
	}

});

// Plugin to easily set up a form to submit via AJAX.
// button: the button that should be "clicked" when this form is submitted.
// callback: the function to call which will make the AJAX request.
$.fn.ajaxForm = function(button, callback) {

	$(this).submit(function(e) {
		e.preventDefault();
		var fields = $(this).serializeArray();
		fields.push({name: button, value: true});
		callback(fields);
	});

};



//***** MESSAGES SYSTEM

// The messages system makes it easy to show and hide messages in the lower left-hand corner of the page.
var ETMessages = {

// An array of currently-showing messages.
messages: {},
index: 0,

// Initialize the messages which are already in the container when the page loads.
init: function() {

	// Gather all the messages and their information.
	var messages = [];
	$("#messages .message").each(function() {
		messages.push([$(this).html(), $(this).attr("class")]);
	});

	// Clear the messages container, and redisplay the messages usings showMessage().
	$("#messages").html("");
	if (messages.length) {
		for (var i in messages) {
			if (typeof messages[i] != "object") continue;
			ETMessages.showMessage(messages[i][0], messages[i][1]);
		}
	}
},

// Show a message.
showMessage: function(text, options) {

	// If the options is a string, use it as a class name.
	if (typeof options == "string") options = {className: options};
	if (!options) options = {};
	options.message = text;

	// If this message has an ID, hide any other message with the same ID.
	if (options.id) ETMessages.hideMessage(options.id, true);

	// Construct the message as a div, and prepend it to the messages container.
	var message = $("<div class='messageWrapper'><div class='message "+options.className+"'>"+options.message+"</div></div>");
	$("#messages").prepend(message);

	// Hide it and fade it in.
	message.hide().fadeIn("fast");

	// Work out a unique ID for this message. If one wasn't provided, use a numeric index.
	var key = options.id || ++ETMessages.index;

	// Store the message information.
	ETMessages.messages[key] = [message, options];

	// If the message is dismissable, add an 'x' control to close it.
	if (!message.find(".message").hasClass("undismissable")) {
		var close = $("<a href='#' class='control-delete dismiss'><i class='icon-remove'></i></a>").click(function(e) {
			e.preventDefault();
			ETMessages.hideMessage(key);
		});
		message.find(".message").prepend(close);
	}

	// If the message is to auto-dismiss, set a timeout.
	if (message.find(".message").hasClass("autoDismiss")) {
		message.bind("mouseenter", function() {
			message.data("hold", true);
		}).bind("mouseleave", function() {
			message.data("hold", false);
		});
		ETMessages.messages[key][2] = setTimeout(function() {
			if (!message.data("hold")) ETMessages.hideMessage(key);
			else message.bind("mouseleave", function() {
				ETMessages.hideMessage(key);
			});
		}, 20 * 1000);
	}

},

// Hide a message.
hideMessage: function(key, noAnimate) {

	// If this message doesn't exist, we can't do anything.
	if (!ETMessages.messages[key]) return;

	// Fade out the message, or just hide it if we don't want animation.
	ETMessages.messages[key][0].fadeOut(noAnimate ? 0 : "fast", function() {
		$(this).remove();
	});

	// Clear the message's hide timeout so we don't hide a future message with the same key.
	clearTimeout(ETMessages.messages[key][2]);

	// Remove the message from storage.
	delete ETMessages.messages[key];

}

};

$(function() {
	ETMessages.init();
});



//***** SHEETS SYSTEM

// The sheet system allows sheets (aka lightboxes) to be easily displayed on the page.
var ETSheet = {

// A stack of all currently active sheets.
sheetStack: [],

// Show a sheet.
showSheet: function(id, content, callback) {

	var content = $(content);

	// If a sheet in the stack with this ID exists, remove it.
	var i = ETSheet.sheetStack.indexOf(id);
	if (i != -1) ETSheet.hideSheet(ETSheet.sheetStack[i]);

	// Append the sheet html to the body, add a close button to it.
	$("body").append(content);
	var sheet = $("#" + content.attr("id"));
	sheet.prepend("<a href='javascript:ETSheet.hideSheet(\"" + id + "\")' class='control-delete close'><i class='icon-remove'></i></a>");

	// Add an overlay div to dim the rest of the content. Clicking on it will hide all open sheets.
	if (!ETSheet.sheetStack.length)
		$("<div class='sheetOverlay'/>")
			.appendTo("body")
			.click(function() {
				for (var i in ETSheet.sheetStack) {
					ETSheet.hideSheet(ETSheet.sheetStack[i]);
				}
			});

	// Hide the sheet that's currently on top of the stack, and push our new one on to the top.
	$("#" + ETSheet.sheetStack[ETSheet.sheetStack.length - 1]).hide();
	ETSheet.sheetStack.push(id);

	// Position the page wrapper so that the browser scrollbars will no longer affect it. The browser scrollbars will become connected to the sheet content.
	$("#wrapper").addClass("sheetActive").css({position: "fixed", top: -$(document).scrollTop(), width: "100%"});
	$("#hdr").css("top", 0);

	// Position the sheet.
	sheet.addClass("active").css({position: "absolute", left: "50%", marginLeft: -sheet.width() / 2});

	// Any buttons named "cancel" will cause the sheet to close when clicked!
	$("input[name=cancel]", sheet).click(function(e) {
		e.preventDefault();
		ETSheet.hideSheet(id);
	});

	// Monitor the scroll event to apply a shadow to the sheet body.
	var top = $('h3', sheet);
	var bottom = $('.buttons', sheet);
	$('.sheetBody', sheet).scroll(function() {
		top.toggleClass('scrollShadowTop', $(this).scrollTop() > 0);
		bottom.toggleClass('scrollShadowBottom', $(this).scrollTop() + $(this).height() < $(this)[0].scrollHeight);
	}).scroll();

	// Focus on the first errorous input, or otherwise just the first input.
	var inputs = $("input, select, textarea", sheet).not(":hidden");
	inputs.first().focus();
	inputs.filter(".error").first().select();

	// Add a key event so that pressing escape hides the sheet.
	$("body").bind("keyup.sheets", function(e) {
		if (e.which == 27) ETSheet.hideSheet(ETSheet.sheetStack[ETSheet.sheetStack.length - 1]);
	});

	if (callback && typeof callback == "function") callback.apply(sheet);

},

// Hide a sheet.
hideSheet: function(id, callback) {

	// Find the sheet's index in the stack.
	var i = ETSheet.sheetStack.indexOf(id);
	if (i == -1) return;

	// Remove the sheet from the stack.
	ETSheet.sheetStack.splice(i, 1);

	// Run the callback function before we destroy the sheet.
	if (callback && typeof callback == "function") callback();
	$("#" + id).remove();

	// Re-show the sheet that's now on top of the stack (if there is one).
	if (ETSheet.sheetStack.length) $("#" + ETSheet.sheetStack[ETSheet.sheetStack.length - 1]).show();

	// If there are no sheets left on the stack, hide the overlay, put the wrapper position back to normal, and unbind the body "escape" key.
	else {
		var scrollTop = -parseInt($("#wrapper").css("top"));
		$("#wrapper").removeClass("sheetActive").css({position: "", top: 0, width: "auto"});
		$("#hdr").css("top", "");
		$.scrollTo(scrollTop);

		$(".sheetOverlay").remove();
		$("body").unbind("keyup.sheets");
	}
},

// Quickly load a view via an AJAX request and display it as a sheet.
loadSheet: function(id, url, callback, data) {
	$.ETAjax({
		id: id,
		url: url,
		data: data,
		type: data && data.length ? "POST" : "GET",
		global: true,
		success: function(data) {
			if (data.modalMessage) {
				ETSheet.hideSheet(id);
				return;
			}
			ETSheet.showSheet(id, data.view || data, callback);
		}
	})
}

};


//***** POPUPS SYSTEM

// The popups system allows popups and popup menus to easily be created and shown/hidden.
var ETPopup = {

// An array of currently-active popups.
popups: {},

// Show a popup.
showPopup: function(id, button, options) {

	// If this popup is already being shown, hide it.
	if (ETPopup.popups[id]) {
		ETPopup.hidePopup(id);
		return false;
	}

	// Hide all other popups.
	ETPopup.hideAllPopups();

	// Get the popup element if it exists, or create it if it doesn't.
	var popup = $("#"+id);
	if (!popup.length) popup = $("<div id='"+id+"'></div>").appendTo(button.parent()).hide();
	popup.addClass("popup");

	// Position the button's parent element (which should be a popupWrapper div) relatively so the popup within
	// it can be positioned accordingly.
	if (button.parent().css("position") != "absolute") button.parent().css("position", "relative");
	button.parent().addClass("active");

	// Make the popup button active.
	if (options.callbackOpen && typeof options.callbackOpen == "function") options.callbackOpen.call(popup);

	// Show and position the popup.
	popup
		.css({position: "absolute", top: button.outerHeight(true) - 1 - parseInt(button.css("marginBottom")) + (options.offset ? options.offset[1] || 0 : 0)})
		.css(options.alignment, 0)
		.show()
		.addClass(options.alignment)
		.data("options", options);
	this.popups[id] = popup;

	// Make sure the popup is within the document bounds.
	if (popup.offset().left < 0) popup.css(options.alignment, popup.offset().left);

	// Add a click handler to the body to hide the popup (as long as it's not a click on the popup button.)
	$(document).unbind("mouseup.popup").bind("mouseup.popup", function(e) {
		if ($(e.target).get(0) != button.get(0) && !button.has(e.target).length) ETPopup.hidePopup(id);
	});

	// However, we don't want this acting when the popup itself is clicked on.
	popup.bind("mouseup", function(e) { return false; });

},

// Hide a popup.
hidePopup: function(id) {
	var popup = ETPopup.popups[id];
	if (popup) {
		popup.hide().removeClass("popup");
		popup.parent().removeClass("active");
		if (typeof popup.data("options").callbackClose == "function") popup.data("options").callbackClose.call(popup);
		ETPopup.popups[id] = false;
	}
},

// Hide all popups.
hideAllPopups: function() {
	for (var i in ETPopup.popups) {
		ETPopup.hidePopup(i);
	}
}

};

// To finish off popup functionality, this jQuery plugin will convert an element into a popup, returning
// a button element which can be added somewhere on the page.
$.fn.popup = function(options) {

	options = options || {};
	options.content = options.content || "<i class='icon-cog'></i> <i class='icon-caret-down'></i>";

	// Get the element to use as the popup contents.
	var popup = $(this).first();
	if (!popup.length) return;

	// Construct the popup wrapper and button.
	var wrapper = $("<div class='popupWrapper " + options.class + "'></div>");
	var button = $("<a href='#' class='popupButton button' id='"+popup.attr("id")+"-button'>"+options.content+"</a>");
	wrapper.append(button).append(popup);

	// Remove whatever class is on the popup contents and make it into a popup menu.
	popup.removeClass().hide().addClass("popupMenu");

	// Add a click handler to the popup button to show the popup.
	button.click(function(e) {
		$.hideToolTip();
		ETPopup.showPopup("popup-"+popup.attr("id"), button, {
			alignment: options.alignment || "left",
			callbackOpen: function() {
				$(this).append(popup.show());
				popup.find("a").click(function() { ETPopup.hidePopup("popup-"+popup.attr("id")); });
			}
		});
		e.preventDefault();
	});

	return wrapper;

};



//***** TOOLTIPS

$.fn.tooltip = function(options) {

	// If we're doing a tooltip on a distinct element, bind the handlers. But if we're using a selector, use live so they always apply.
	var func = this.selector ? "live" : "bind";

	return this.unbind("mouseenter.tooltip").die("mouseenter.tooltip")[func]("mouseenter.tooltip", function() {

		var elm = $(this);
		options = options || {};
		$.hideToolTip();
		if ($.tooltipParent) clearTimeout($.tooltipParent.data("hideTimeout"));

		// Store the title attribute.
		if (!elm.attr("title")) return;
		elm.data("title", elm.attr("title"));
		elm.attr("title", "");

		var handler = function() {

			// Set up the tooltip container. There should only be one in existance globally.
			var tooltip = $("#tooltip");
			if (!tooltip.length) tooltip = $("<div id='tooltip'></div>").appendTo("body").css({position: "absolute"})
			.bind("mouseenter", function() {
				clearTimeout($.tooltipParent.data("hideTimeout"));
			}).bind("mouseleave", function() {
				$.hideToolTip();
			});
			tooltip.removeClass().addClass("tooltip").hide().data("parent", elm);
			if (options.className) tooltip.addClass(options.className);

			// Set the tooltip value.
			tooltip.html(elm.data("title"));

			// Work out the right position...
			var left = elm.offset().left, top = elm.offset().top - tooltip.outerHeight() - 3;
			switch (options.alignment) {
				case "left": break;
				case "right": left += elm.outerWidth() - tooltip.outerWidth(); break;
				default: left += elm.outerWidth() / 2 - tooltip.outerWidth() / 2;
			}
			left += options.offset ? options.offset[0] || 0 : 0;
			left = Math.min(left, $("body").width() - tooltip.outerWidth());
			left = Math.max(left, 0);
			top += options.offset ? options.offset[1] || 0 : 0;

			top = Math.max($(document).scrollTop(), top);

			// ...and position it!
			tooltip.css({left: left, top: top});

			// Stop a fade out animation and show the tooltip.
			tooltip.stop(true, false).css({display: "block", opacity: 1}).show();

		};

		// Either show it straight away, or delay before we show it.
		if (options.delay) $(this).data("timeout", setTimeout(handler, options.delay));
		else handler();

		$.tooltipParent = $(this);

	})

	// Bind a mouseleave handler to hide the tooltip.
	.unbind("mouseleave.tooltip").die("mouseleave.tooltip")[func]("mouseleave.tooltip", function() {

		// If the tooltip is hoverable, don't hide it instantly. Give it a chance to run the mouseenter event.
		$("#tooltip").hasClass("hoverable")
			? $.tooltipParent.data("hideTimeout", setTimeout($.hideToolTip, 1))
			: $.hideToolTip();
	});

};

$.fn.removeTooltip = function() {
	$.hideToolTip();
	return this.unbind("mouseenter.tooltip").die("mouseenter.tooltip").unbind("mouseleave.tooltip").die("mouseleave.tooltip")
};

// The element which the tooltip belongs to.
$.tooltipParent = false;

// Hide the tooltip: restore the parent's title attribute and fade out the tooltip.
$.hideToolTip = function() {
	$("#tooltip").fadeOut(100);
	var elm = $.tooltipParent;
	if (elm) {
		elm.attr("title", elm.data("title"));
		clearTimeout(elm.data("timeout"));
		$("#tooltip").data("parent", null);
	}
};


//***** MEMBERS ALLOWED TOOLTIP

// The members allowed tooltip will load a list of members who are allowed in a conversation and display it in
// a popup.
var ETMembersAllowedTooltip = {
	showDelay: 250,
	hideDelay: 250,
	showTimer: null,
	hideTimer: null,
	showing: false,

	tooltip: null,

	// Set up the members allowed tooltip to be activated on certain elements.
	init: function(elm, conversationIdCallback, cutFirst3) {

		// First, construct it (or get it if it already exists)...
		ETMembersAllowedTooltip.tooltip = $("#membersAllowedTooltip").length
			? $("#membersAllowedTooltip").hide()
			: $("<div class='popup withArrow withArrowTop allowedList action' id='membersAllowedTooltip'>Loading...</div>").appendTo("body").hide();

		// Bind event handlers to the element.
		elm.unbind("mouseover").unbind("mouseout").bind("mouseover", function() {

			// Prevent the tooltip from being hidden now that the mouse is over the activation element.
			if (ETMembersAllowedTooltip.hideTimer) clearTimeout(ETMembersAllowedTooltip.hideTimer);

			// If we're already showing the members allowed tooltip for this element, we don't need to show it again.
			if (ETMembersAllowedTooltip.showing == this) return;
			ETMembersAllowedTooltip.showing = this;

			var self = this;
			ETMembersAllowedTooltip.tooltip.html("Loading...").hide();

			// Start a timer, which when finished, will load the members allowed list and show it.
			ETMembersAllowedTooltip.showTimer = setTimeout(function() {

				// Position and show the tooltip.
				ETMembersAllowedTooltip.tooltip.css({position: "absolute", top: $(self).offset().top + $(self).height() + 5, left: $(self).offset().left}).show();

				// Load the members allowed.
				$.ETAjax({
					url: "conversation/membersAllowedList.view/" + conversationIdCallback($(self)),
					dataType: "text",
					global: false,
					success: function(data) {

						// Show the tooltip.
						ETMembersAllowedTooltip.tooltip.html(data);

						// Cut off the first 3 names if necessary.
						if (cutFirst3) $(".name", ETMembersAllowedTooltip.tooltip).slice(0, 3).remove();

					}
				});
			}, ETMembersAllowedTooltip.showDelay);
		}).bind("mouseout", ETMembersAllowedTooltip.mouseOutHandler);

		// Bind event handlers to the tooltip itself.
		ETMembersAllowedTooltip.tooltip.unbind("mouseover").unbind("mouseout").bind("mouseover", function() {
			if (ETMembersAllowedTooltip.hideTimer) clearTimeout(ETMembersAllowedTooltip.hideTimer);
		}).bind("mouseout", ETMembersAllowedTooltip.mouseOutHandler);
	},

	// An event handler for when the mouse leaves the activation element or tooltip.
	mouseOutHandler: function() {
		if (ETMembersAllowedTooltip.showTimer) clearTimeout(ETMembersAllowedTooltip.showTimer);
		ETMembersAllowedTooltip.hideTimer = setTimeout(function() {
			ETMembersAllowedTooltip.tooltip.fadeOut("fast");
			ETMembersAllowedTooltip.showing = false;
		}, ETMembersAllowedTooltip.hideDelay);
	}
};



//***** GLOBAL PAGE STUFF

$(function() {

	$("#backButton").tooltip({alignment: "left", offset: [20, 23]});

	// Initialize page history.
	$.history.init();

	// Add click handlers to some links.
	$(".link-forgot").live("click", function(e) {
		e.preventDefault();
		showForgotSheet();
	});

	$(".link-membersOnline").live("click", function(e) {
		e.preventDefault();
		showOnlineSheet();
	});

	// Add click handlers to stars.
	$(".starButton").live("click", function(e) {
		toggleStar($(this).data("id"));
		e.preventDefault();
	});

});

// Show the join sheet.
function showJoinSheet(formData)
{
	ETSheet.loadSheet("joinSheet", "user/join.view", function() {
		$(this).find("form").ajaxForm("submit", showJoinSheet);
	}, formData);
}

// Show the login sheet.
function showLoginSheet(formData)
{
	ETSheet.loadSheet("loginSheet", "user/login.ajax&return="+encodeURIComponent(window.location), function() {
		$(this).find("form").ajaxForm("submit", showLoginSheet);
	}, formData);
}

// Show the "forgot password" sheet.
function showForgotSheet(formData)
{
	ETSheet.loadSheet("forgotSheet", "user/forgot.ajax", function() {
		$(this).find("form").ajaxForm("submit", showForgotSheet);
	}, formData);
}

// Show the "members online" sheet.
function showOnlineSheet()
{
	ETSheet.loadSheet("onlineSheet", "members/online.view");
}

// Toggle the state of a star.
function toggleStar(conversationId)
{
	$.ETAjax({url: "conversation/star.json/"+conversationId});
	var star = $(".starButton[data-id="+conversationId+"] .star");
	var on = !star.hasClass("icon-star");
	toggleStarState(conversationId, on);
}

function toggleStarState(conversationId, on)
{
	var star = $(".starButton[data-id="+conversationId+"] .star");
	var text = star.parent().find("span:not(.star)");
	star.toggleClass("icon-star", on).toggleClass("icon-star-empty", !on);
	text.html(T(on ? "Following" : "Follow"));
	$("#c"+conversationId).toggleClass("starred", on);
}



//***** INTERVAL CALLBACK

// This class allows you to set up a callback to be run at a certain interval (in seconds). If the window
// loses focus, the callback will run when the window regains focus or when the timer runs out (whichever
// comes last.)
function ETIntervalCallback(callback, interval)
{
	var ic = this;
	ic.hold = false;
	ic.timeout = null;
	ic.interval = interval;
	ic.callback = callback;

	// Set a timeout to call the callback, or if we're "holding", stop holding so that the callback will be
	// run when the window regains focus.
	ic.setTimeout = function() {
		clearTimeout(ic.timeout);
		if (ic.interval <= 0) return;
		ic.timeout = setTimeout(function() {
			if (!ic.hold) ic.runCallback();
			else ic.hold = false;
		}, ic.interval * 1000);
	};

	// Run the callback, resetting the timeout and the hold flag.
	ic.runCallback = function() {
		ic.callback();
		ic.setTimeout();
		ic.hold = false;
	};

	// Reset the interval (start the timer from the beginning.)
	ic.reset = function(interval) {
		if (interval > 0) ic.interval = interval;
		ic.setTimeout();
	}

	// When the window gains focus, if we're "holding", stop holding. Otherwise, run the callback.
	$(window).focus(function(e) {
		if (e.target != window) return;
		if (ic.hold) ic.hold = false;
		else ic.runCallback();
	})

	// When the window loses focus, start "holding".
	.blur(function(e) {
		if (e.target != window) return;
		ic.hold = true;
	});

	// Set the initial timeout.
	ic.setTimeout();
}



//***** NOTIFICATIONS

var ETNotifications = {

// Whether or not the contents of the notifications popup needs to be reloaded.
reloadNotifications: true,

// Initialize the notifications popup.
init: function() {

	// Wrap the notifications button in a popup wrapper and make it show a popup when clicked.
	$("#notifications").wrap("<div class='popupWrapper'/>").click(function(e) {
		var that = this;

		ETPopup.showPopup("notificationsPopup", $(this), {
			alignment: "right",
			callbackOpen: function() {

				// If we need to reload the notification popup content, clear its contents now.
				if (ETNotifications.reloadNotifications)
					$(this).html("<h3>"+T("Notifications")+"</h3><div class='loading'></div>");

				// Regardless of whether we need to reload it, we still reload it. Make an AJAX request.
				$.ETAjax({
					url: "settings/notifications.view/1",
					global: false,
					success: function(data) {

						// Put the new contents into the notifications popup.
						$("#notificationsPopup div").html(data).removeClass("loading");

						// We no longer need to reload the notifications.
						ETNotifications.reloadNotifications = false;

						// Mark the notifications as read.
						$(that).removeClass("new").html("0");
						ETNotifications.updateTitle(0);

					}
				})

			}
		});
		e.preventDefault();

	});

	// Update the page title with the number of unread notifications.
	ETNotifications.updateTitle($("#notifications").html());

	// Set up an interval callback to check for notifications every so often.
	new ETIntervalCallback(ETNotifications.checkNotifications, ET.notificationCheckInterval);
},

// Check for notifications, updating the count shown on the notifications button and displaying new notifications
// as messages.
checkNotifications: function()
{
	// If the user isn't logged in, we can't do this.
	if (!ET.userId) return;

	$.ETAjax({
		url: "settings/notificationCheck.ajax",
		global: false,
		success: function(data) {

			// New notification messages are handled like normal messages!

			// Set the contents of the notifications button.
			$("#notifications").toggleClass("new", data.count > 0).html(data.count);
			ETNotifications.updateTitle(data.count);

			// If there are new notifications, mark the notifications popup as dirty.
			if (data.count > 0) ETNotifications.reloadNotifications = true;
		}
	})
},

// Update the document title to prepend a (#) where # is the number of unread notifications.
updateTitle: function(number) {
	var re = /\(\d+\)/;
	var count = number > 0 ? "("+number+") " : "";
	if (document.title.search(re) != -1) document.title = document.title.replace(re, count);
	else document.title = count + document.title;
}

};

$(function() {
	ETNotifications.init();
});


//***** COLOR PICKER

// Turn a normal text input into a color picker, and run a callback when the color is changed.
function colorPicker(id, callback) {

	// Create the color picker container.
	var picker = $("<div id='"+id+"-colorPicker'></div>").appendTo("body").addClass("popup").hide();

	// When the input is focussed upon, show the color picker.
	$("#"+id+" input").focus(function() {
		picker.css({position: "absolute", top: $(this).offset().top - picker.outerHeight(), left: $(this).offset().left}).show();
	})

	// When focus is lost, hide the color picker.
	.blur(function() {
		picker.hide();
	})

	// When a value is typed in the field, update the color.
	.keyup(function() {
		farb.setColor($(this).val());
	})

	// Add a color swatch before the input.
	.before("<span class='colorSwatch'></span>");

	// Create a handler function for when the color is changed to update the input and swatch, and call
	// the custom callback function.
	var handler = function(color) {
		if (typeof callback == "function") callback(color, picker);
		$("#"+id+" input").val(color.toUpperCase());
		$("#"+id+" .colorSwatch").css("backgroundColor", color);
		$("#"+id+" .reset").toggle(!!color);
	}

	// Set up a farbtastic instance inside the picker we've created.
	var farb = $.farbtastic(picker, function(color) {
		handler(color);
	}).setColor($("#"+id+" input").val());

	// When the "reset" link is clicked, reset the color.
	$("#"+id+" .reset").click(function(e) {
		e.preventDefault();
		handler("");
	}).toggle(!!$("#"+id+" input").val());

}
