/**
 * jQuery.placeholder - Placeholder plugin for input fields
 * Written by Blair Mitchelmore (blair DOT mitchelmore AT gmail DOT com)
 * Licensed under the WTFPL (http://sam.zoy.org/wtfpl/).
 * Date: 2008/10/14
 *
 * @author Blair Mitchelmore
 * @version 1.0.1
 *
 **/
new function($) {
    $.fn.placeholder = function(value) {  
	      var className = "placeholder";
        return this.filter(":input").each(function() {
			$(this).data("placeholderValue", value);
			$(this).data("isPlaceholding", false);
        }).focus(function() {
	if ($(this).data("isPlaceholding")) $(this).unplacehold().val("");
}).change(function() {
	if ($(this).val() && $(this).data("isPlaceholding")) $(this).unplacehold();
}).blur(function() {
            if ($.trim($(this).val()) === "")
                $(this).addClass(className).val($(this).data("placeholderValue")).data("isPlaceholding", true).trigger("update");
        }).each(function(index, elem) {
			$(this).val("");
			$(this).blur();
                new function(e) {
                    $(e.form).submit(function() {
                        if ($(this).data("isPlaceholding")) 
			                $(this).removeClass(className).val("").data("isPlaceholding", false).trigger("update");
                        return true;
                    });
                }(elem);
        });
    };
	$.fn.unplacehold = function() {
		var className = "placeholder";
		if (this.data("isPlaceholding")) 
            this.removeClass(className).data("isPlaceholding", false).val("").trigger("update");
			return this;
	}
}(jQuery);