$._ETAjax = $.ETAjax;
$.ETAjax = function(options) {
	var result = $._ETAjax(options);
	result.fail(function(jqXHR, textStatus, errorThrown) {
		var error = $("<div/>").html(jqXHR.responseText).text();
		ETMessages.showMessage("<strong>"+errorThrown+": "+textStatus+"<br></strong>"+error, {className: "info dismissable", id: "ajaxDisconnectedDebug"});
	});
}