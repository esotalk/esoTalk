// Create an auto-complete popup to show a list of members matching text behind the cursor.
// field: the field to monitor for input.
// character: a character to look for that indicates a member name (eg. @)
// clickHandler: a function to be called in a member is selected from the popup.
function ETAutoCompletePopup(field, character, clickHandler) {

	var ac = this;

	// Initialize ALL THE THINGS.
	this.field = field;
	this.character = character;
	this.active = false;
	this.items = 0;
	this.index = 0;
	this.cache = [];
	this.searches = [];
	this.value = "";
	this.clickHandler = clickHandler;

	// If no click handler was specified, use our own default one. The default click handler will replace
	// everything from the cursor until the character denoting the beginning of this "token" with the member's
	// name.
	if (!this.clickHandler) this.clickHandler = function(member) {
		var selection = ac.field.getSelection();
		var value = ac.field.val();
		var nameStart = 0;

		// If nothing is selected in the field...
		if (selection.length == 0) {

			// From the selection position, go back up to 20 characters, searching for the start character.
			for (var i = selection.start; i > selection.start - 20; i--) {
				if (i != selection.start && (value.substr(i, 1) == "]")) break;
				if (value.substr(i, ac.character.length) == ac.character) {
					nameStart = i + ac.character.length;
					break;
				}
			}

			// If we found the position of the character, replace it all with the member's name.
			if (nameStart) {
				ac.field.val(value.substring(0, nameStart) + member["name"] + " " + value.substr(selection.start));
				var p = nameStart + member["name"].length + 1;
				ac.field.selectRange(p, p);
			}
		}
	};

	// Now, construct the auto complete popup for this field.
	this.popup = $("#autoCompletePopup-"+field.attr("id"));
	if (!this.popup.length) this.popup = $("<div id='autoCompletePopup-"+field.attr("id")+"'/>");
	this.popup.bind("mouseup", function(e) { return false; }).addClass("popup").addClass("autoCompletePopup").hide();

	// Append it to the body, and hide it when the document is clicked.
	this.popup.appendTo("body");
	$(document).mouseup(function(e) { ac.hide(); });

	// Add a keydown handler to the field. This will be used to navigate the popup menu (down/up/enter/escape).
	this.field.attr("autocomplete", "off").keydown(function(e) {
		if (ac.active) {
			switch (e.which) {
				case 40: // Down
					ac.updateIndex(ac.index + 1);
					e.preventDefault();
					break;
				case 38: // Up
					ac.updateIndex(ac.index - 1);
					e.preventDefault();
					break;
				case 13: case 9: // Enter/Tab
					ac.popup.find("li").eq(ac.index).click();
					e.preventDefault();
					break;
				case 27: // Escape
					ac.hide();
					e.stopPropagation();
					e.preventDefault();
					break;
			}
		}
	});

	// Add a keyup handler to the field. This will be used to fetch new content based on the field's value.
	this.field.keyup(function(e) {
		switch (e.which) {

			case 27: // Escape
				if (ac.active) e.stopPropagation();
				break;

			// Up, down, enter, tab, escape, left, right.
			case 9: case 13: case 27: case 40: case 38: case 37: case 39: break;

			default:

				// If we have a character to search for, backtrack from where the cursor is until we find it,
				// and use that content.
				if (ac.character) {
					var selection = $(this).getSelection();
					var value = $(this).val();
					var nameStart = 0;
					if (selection.length == 0) {
						for (var i = selection.start; i > selection.start - 20; i--) {
							if (i != selection.start && value.substr(i, 1) == "]") break;
							if (value.substr(i, ac.character.length) == ac.character) {
								nameStart = i + ac.character.length;
								break;
							}
						}

						if (nameStart) {
							var name = value.substring(nameStart, selection.start);
							ac.fetchNewContent(name);
						}
					}
				}

				// Otherwise, just use the whole field's content.
				else ac.fetchNewContent($(this).val());
				break;
		}
	});

	// This function updates the items in the popup menu to show only those who match the current value of the
	// field.
	this.update = function() {
		if (ac.value) {

			// Convert spaces to non-breaking spaces.
			var value = ac.value.replace(/ /g, "\xA0");

			// Sanitize the current value of the field for use in a regular expression.
			value = value.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
			var regexp = new RegExp("(" + value + ")", "i");
			var results = [];

			// For all of the members in the member cache, test the regular expression and pull out matching results.
			for (var i in ac.cache) {
				if (regexp.test(ac.cache[i].name)) {
					results.push(ac.cache[i]);
				}
			}

			// Clear the popup menu.
			ac.popup.html("<ul class='popupMenu'></ul>");

			// If there are results, show them.
			if (results.length) {

				// Sort the results alphabetically by name.
				results = results.sort(function(a, b) {
					return a.name == b.name ? 0 : (a.name < b.name ? -1 : 1);
				});

				// Get the first 5 results.
				results = results.slice(0, 5);

				// Add each of the results to the popup.
				var item;
				for (var i in results) {

					// Highlight the matching part of the name.
					var name = $("<div/>").text(results[i].name).html();
					name = name.replace(regexp, "<strong>$1</strong>");

					// Create an <li> for the result and add some event handlers.
					item = $("<li><a href='#'><i>"+results[i].avatar+"</i> "+name+"</a></li>").data("position", i).data("member", results[i]).mouseover(function() {
						ac.updateIndex($(this).data("position"));
					}).click(function(e) {
						e.preventDefault();
						ac.clickHandler($(this).data("member"));
						ac.stop();
					});

					// Append it to the popup menu.
					ac.popup.find("ul").append(item);
				}

				ac.items = results.length;
				ac.active = true;
				ac.show();
				ac.updateIndex(ac.index);

			}

			// If there are no results, hide the popup.
			else ac.hide();

		} else ac.hide();
	}

	this.timeout = null;

	// This function fetches a list of members that match the specified value with AJAX.
	this.fetchNewContent = function(value) {

		// If we've not already search for this value, and it's greater than 2 characters, proceed!
		if (value && value != ac.value && ac.searches.indexOf(value) == -1 && value.length > 2) {
			clearTimeout(ac.timeout);

			// Set a timeout to make an AJAX request for a list of members.
			ac.timeout = setTimeout(function() {
				$.ETAjax({
					id: "autoComplete",
					url: "members/autocomplete.ajax/"+encodeURIComponent(value),
					global: false,
					success: function(data) {

						// Add the results to the cache.
						results: for (var i in data.results) {
							for (var j in ac.cache) {
								if (ac.cache[j].type == data.results[i].type && ac.cache[j].memberId == data.results[i].memberId) continue results;
							}
							ac.cache.push(data.results[i]);
						}

						// Update the popup now that we've updated the cache.
						ac.searches.push(value);
						ac.update();
					}
				});
			}, 250);
		}
		ac.value = value;
		ac.update();
	}

	// Show the popup
	this.show = function() {
		ac.popup.show().css({position: "absolute", zIndex: 9999});

		// If we have a character that denotes the start of the token, we want to position the popup just below
		// it. This is super-hard with a textarea. We have to create a dummy div and set its contents to whatever
		// the textarea's is, up until the character. Then we can add a span at the end and get its position.
		if (ac.character) {
			var selection = ac.field.getSelection();
			var value = ac.field.val().substr(0, selection.start - ac.value.length);
			var testSubject = $('<div/>')
				.css({
					position: 'absolute',
					top: ac.field.offset().top,
					left: ac.field.offset().left,
					width: ac.field.width(),
					height: ac.field.height(),
					fontSize: ac.field.css('fontSize'),
					fontFamily: ac.field.css('fontFamily'),
					fontWeight: ac.field.css('fontWeight'),
					paddingTop: ac.field.css('paddingTop'),
					paddingLeft: ac.field.css('paddingLeft'),
					paddingRight: ac.field.css('paddingRight'),
					paddingBottom: ac.field.css('paddingBottom'),
					letterSpacing: ac.field.css('letterSpacing'),
					lineHeight: ac.field.css('lineHeight')
				})
				.html(value.replace(/[\n\r]/g, "<br/>"))
				.appendTo("body")
				.append("<span style='position:absolute'>&nbsp;</span>");

			// Get the position of our dummy span and set the popup to the same position.
			var offset = testSubject.find("span").offset();
			ac.popup.css({left: offset.left, top: offset.top + testSubject.find("span").height()});
			testSubject.remove();
		}

		// If we don't have a character, we can just position the popup directly beneath the field.
		else ac.popup.css({left: ac.field.offset().left, top: ac.field.offset().top + ac.field.outerHeight() - 1, width: ac.field.outerWidth()});
		ac.active = true;
	}

	// Hide the popup.
	this.hide = function() {
		ac.popup.hide();
		ac.active = false;
	}

	// Stop any active AJAX requests to fetch members and hide the popup.
	this.stop = function() {
		ac.hide();
		clearTimeout(ac.timeout);
		$.ETAjax.abort("autoComplete");
	}

	// Update the selected index of the popup.
	this.updateIndex = function(index)
	{
		ac.index = index;

		// Make sure the index is valid.
		if (ac.index < 0) ac.index = ac.items - 1;
		else if (ac.index >= ac.items) ac.index = 0;

		ac.popup.find("li").removeClass("selected").eq(ac.index).addClass("selected");
	}

};