// Scrubber JavaScript

// A scrubber is a list of "sections" that allow you to quickly navigate through a large collection of items.
// By default, a scrubber is used in the conversation view (the timeline scrubber) and on the member list (as
// a letter scrubber.)

var ETScrubber = {

// These variables refer to various elements of the page.
header: null,
body: null,
scrubber: null,
items: null,

// Callback functions.
loadItemsCallback: null,
scrollToIndexCallback: null,

// Information about the content of the scrubber.
count: 0,
startFrom: 0,
perPage: 0,
moreText: "Load more",

// An array of positions within the scrubber that have been loaded.
loadedItems: [],

// Initialize the scrubber.
init: function() {

	// Go through the currently displaying item range and add the positions to the loadedItems array.
	var count = Math.min(this.startFrom + this.perPage, this.count);
	for (var i = this.startFrom; i < count; i++)
		this.loadedItems.push(i);

	// Make the header and the scrubber's position fixed when we scroll down the page.
	// Get the normal top position of the header and of the scrubber. If the scrollTop is greater than
	// this, we know we'll need to make it fixed.
	this.header = $("#hdr");
	var headerTop = this.header.offset().top;
	var headerWidth = this.header.width();
	var scrubberTop = this.scrubber.length && (this.scrubber.offset().top - this.header.outerHeight() - 20);

	// Whenever the user scrolls within the window...
	$(window).scroll(function() {
		var y = $(this).scrollTop();

		// Now we need to work out where we are in the content and highlight the appropriate
		// index in the scrubber. Go through each of the items on the page...
		$("> li", ETScrubber.items).each(function() {
			var item = $(this);

			// If we've scrolled past this item, continue in the loop.
			if (y > item.offset().top + item.outerHeight() - ETScrubber.header.outerHeight()) return true;
			else if (item.data("index")) {

				// This must be the first item within our viewport. Get the index of it and highlight
				// that index in the scrubber, then break out of the loop.
				$(".scrubber li").removeClass("selected");
				var index = item.data("index");
				$(".scrubber-"+index, ETScrubber.scrubber).addClass("selected").parents("li").addClass("selected");
				return false;

			}
		});

		// Work out if the "next page" block is visible in the viewport. If it is, automatically load
		// new items, starting from the last item position that we have loaded already.
		var newer = $(".scrubberNext", ETScrubber.body);
		if (newer.length && y + $(window).height() > newer.offset().top && !newer.hasClass("loading") && !ET.disableFixedPositions) {
			newer.find("a").click();
		}
	}).scroll();

	// Alright, so, all the scrolling event stuff is done! Now we need to make the "next/previous page" and
	// "load more" blocks clickable.
	$(ETScrubber.body).on("click", ".scrubberMore a", function(e) {
		e.preventDefault();
		$(this).parent().addClass("loading");
		var moreItem = $(this).parent();

		var backwards, // Whether or not to load items that are at the start or the end of this "more" block.
			position; // The position to load items from.

		// If this is the "previous page" block...
		if (moreItem.is(".scrubberPrevious")) {
			backwards = true;
			position = Math.min.apply(Math, ETScrubber.loadedItems) - ETScrubber.perPage;
		}

		// If this is the "next page" block...
		else if (moreItem.is(".scrubberNext")) {
			backwards = false;
			position = Math.max.apply(Math, ETScrubber.loadedItems) + 1;
		}

		// If this is a "load more" block...
		else {
			backwards = moreItem.offset().top - $(document).scrollTop() < 250;
			position = backwards ? $(this).parent().data("positionEnd") - ETScrubber.perPage + 1 : $(this).parent().data("positionStart");
		}

		ETScrubber.loadItemsCallback(position, function(data) {

			// If we are loading items that are above where we are, save the scroll position relative
			// to the first post after the "more" block.
			if (backwards) {
				var firstItem = moreItem.next();
				var scrollOffset = firstItem.offset().top - $(document).scrollTop();
			}

			var items = ETScrubber.addItems(data.startFrom, data.view, moreItem);

			// Restore the scroll position.
			if (backwards)
				$.scrollTo(firstItem.offset().top - scrollOffset);

			return items;

		});


	});

	// Finally, we need to make the indexes in the scrubber clickable.
	$(".scrubber a", ETScrubber.scrubber).click(function(e) {
		e.preventDefault();

		// Get the index of that this element represents.
		var index = $(this).parent().data("index");
		if (index == "last") index = Infinity;
		else if (index == "first") index = 0;

		// Now let's scroll to the first item with the same index. If one wasn't found, we might need
		// to make an AJAX request to load some more items.
		var found = ETScrubber.scrollToIndex(index);

		if (!found) {

			// 1. Work out where this index will be in the context of the items we currently have
			// rendered. We need to find the "more" block that the index will be in, and the item
			// before that "more" block if there is one.
			var moreItem = null,
				prevPost = null;
			$("li", ETScrubber.items).each(function() {
				if ($(this).is(".scrubberMore")) moreItem = $(this);
				else {
					var item = $(this).first();

					// If this item is past the index we're looking for, break out of the loop.
					if (item.data("index") > index) return false;

					moreItem = null;
					prevPost = $(this);
				}
			});

			// 2. If a "more" block wasn't found, and no previous items were found, then scroll right up to the top.
			if (!moreItem && !prevPost)
				ETScrubber.scrollTo(0);

			// 3. If a "more" block wasn't found, and a previous item was found, scroll to the previous item.
			else if (!moreItem && prevPost && index != Infinity)
				ETScrubber.scrollTo(prevPost.offset().top);

			// 4. If a "more" block WAS found, scroll to it, and load the items.
			else if (moreItem) {
				ETScrubber.scrollTo(moreItem.offset().top);
				moreItem.addClass("loading");
				ETScrubber.loadItemsCallback(index, function(data) {

					// If we're scrolling down to the very bottom, save the scroll position relative to the
					// bottom of the items area. Stop the current scroll animation and jump to its end first.
					if (index == Infinity) {
						$('html,body').stop(true, true);
						var scrollOffset = ETScrubber.items.offset().top + ETScrubber.items.outerHeight() - $(document).scrollTop();
					}

					var items = ETScrubber.addItems(data.startFrom, data.view, moreItem);

					// Restore the scroll position, or scroll to the index which we should now have items for.
					if (index == Infinity) $.scrollTo(ETScrubber.items.offset().top + ETScrubber.items.outerHeight() - scrollOffset);
					else ETScrubber.scrollToIndex(index);

					return items;

				}, true);
			}

		}
	});

},

// Scroll to a specific position, applying an animation and taking the fixed header into account.
scrollTo: function(position) {
	$.scrollTo(Math.max(0, position - ETScrubber.header.outerHeight() - 20), "slow");
},

// Scroll to the item on or before an index combination.
scrollToIndex: function(index) {

	var post = null,
		found = false,
		item;

	// Go through each of the items and find one on or before the supplied index to scroll to.
	$("li", ETScrubber.items).each(function() {
		item = $(this);

		// If this item matches the index we want to scroll to, then we've found it!
		if (item.data("index") == index) {
			found = true;
			return false;
		}

		// If this item is after the index we want to scroll to, break out of the loop.
		if (item.data("index") > index)
			return false;
	});

	// Scroll to it.
	if (item) ETScrubber.scrollTo(index == 0 ? 0 : $(item).offset().top);

	if (typeof ETScrubber.scrollToIndexCallback == "function") ETScrubber.scrollToIndexCallback(index);

	return found;
},

// Add a collection of items, returned by an AJAX request, to the page.
addItems: function(startFrom, items, moreItem, animate) {

	startFrom = parseInt(startFrom);
	moreItem.removeClass("loading");

	// Get all of the <li>s in the item HTML provided.
	var view = $(items);
	view = view.filter("li");

	// Now we're going to loop through the range of items (startFrom -> startFrom + itemsPerPage) and make
	// a nice array of item objects, making sure we only add items that we don't already have. This means that
	// if we already have items 1-10 and 15-25, and we load items 11-20, this array will only contain 11-14.
	var items = [],
		newStartFrom = startFrom;
	for (var i = 0; i < ETScrubber.perPage; i++) {
		if (startFrom + i >= ETScrubber.count) break;
		if (ETScrubber.loadedItems.indexOf(startFrom + i) != -1) {
			if (items.length) break;
			newStartFrom = startFrom + i + 1;
			continue;
		}
		items.push(view[i]);
	}
	startFrom = newStartFrom;

	// Now that we have an array of items, convert it to a jQuery collection.
	items = $(items);

	// If we already have a "Just now" time marker anywhere in our posts, remove any "Just now" time markers
	// from these new posts.
	if ($("div.timeMarker[data-now]", ETScrubber.body).length) {
		items.find("div.timeMarker[data-now]").remove();
	}

	// Add the items to the page before/after/replacing the "more" block, depending on the type of "more" block.
	if (moreItem.is(".scrubberPrevious"))
		moreItem.after(items);
	else if (moreItem.is(".scrubberNext"))
		moreItem.before(items);
	else if (items.length)
		moreItem.replaceWith(items);

	// Create a "more" block item which we can use below.
	var scrubberMore = $("<li class='scrubberMore'><a href='#'>"+ETScrubber.moreText+"</a></li>");

	// If we don't have the item immediately before the first item we just loaded (ie. there's a gap),
	// we need to put a "more" block there.
	if (ETScrubber.loadedItems.indexOf(startFrom - 1) == -1 && items.first().prev().is("li:not(.scrubberMore)")) {
		scrubberMore = scrubberMore.clone();
		items.first().before(scrubberMore);

		// Work out the range of items that this "more" block covers. We know where it ends, so loop backwards
		// from there and find the start.
		for (var i = startFrom - 1; i > 0; i--) {
			if (ETScrubber.loadedItems.indexOf(i) != -1) break;
		}
		scrubberMore.data("positionStart", i + 1);
		scrubberMore.data("positionEnd", startFrom - 1);
	}

	// If we don't have the item immediately AFTER the LAST item that we just loaded (ie. there's a gap), we
	// need to put a "more" block there.
	if (ETScrubber.loadedItems.indexOf(startFrom + items.length) == -1 && items.last().next().is("li:not(.scrubberMore)")) {
		scrubberMore = scrubberMore.clone();
		items.last().after(scrubberMore);

		// Work out the range of items that this "more" block covers. We know where it starts, so loop forwards
		// from there and find the end.
		for (var i = startFrom + items.length + 1; i < ETScrubber.count; i++) {
			if (ETScrubber.loadedItems.indexOf(i) != -1) break;
		}
		scrubberMore.data("positionStart", startFrom + items.length);
		scrubberMore.data("positionEnd", i - 1);
	}

	if (animate) items.hide().fadeIn("slow");

	// Update the loadedItems index with the new item positions we have loaded.
	for (var i = startFrom; i < startFrom + items.length; i++) {
		if (ETScrubber.loadedItems.indexOf(i) == -1)
			ETScrubber.loadedItems.push(i);
	}

	// If we have the very first item in the collection, remove the "older" block.
	if (Math.min.apply(Math, ETScrubber.loadedItems) <= 0)
		$(".scrubberPrevious").remove();

	// If we have the very last item in the collection, remove the "newer" block.
	if (Math.max.apply(Math, ETScrubber.loadedItems) >= ETScrubber.count - 1)
		$(".scrubberNext").remove();

	return items;
}

};
