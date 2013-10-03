$._ETAjax = $.ETAjax;
$.ETAjax = function(options) {
	var result = $._ETAjax(options);
	result.fail(function(jqXHR, textStatus, errorThrown) {
		var error = $("<div/>").html(jqXHR.responseText).text();
		ETMessages.showMessage("<strong>"+errorThrown+": "+textStatus+"<br></strong>"+error, {className: "info dismissable", id: "ajaxDisconnectedDebug"});
	});
}

// Re-add all these methods, because we lost them when we re-assigned $.ETAjax :(
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