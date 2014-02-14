var AdminProfiles = {

	init: function() {

		// Make the field list sortable.
		$("#adminProfiles .list").sortable({
			axis: "y",
			update: function() {
				var ids = [];
				$("#adminProfiles .list li").each(function() {
					ids.push($(this).data("id"));
				});
				$.ETAjax({
					type: "POST",
					url: "admin/profiles/reorder.ajax",
					data: {ids: ids},
					globalLoading: true
				});
			}
		}).find("li").css("cursor", "move");

		$("#adminProfiles li .control-edit").click(function(e) {
			var id = $(this).parents("li").data("id");
			AdminProfiles.showEditSheet(id);
			e.preventDefault();
		});

		$("#addFieldButton").click(function(e) {
			AdminProfiles.showCreateSheet();
			e.preventDefault();
		});

		$("#adminProfiles li .control-delete").click(function(e) {
			return confirm(T("message.confirmDelete"));
		});

	},

	showEditSheet: function(fieldId, formData) {
		ETSheet.loadSheet("editFieldSheet", "admin/profiles/edit.view/"+fieldId, function() {
			$(this).find("form").ajaxForm("save", function(formData) {
				AdminProfiles.showEditSheet(fieldId, formData);
			});
		}, formData);
	},

	showCreateSheet: function(formData) {
		ETSheet.loadSheet("editFieldSheet", "admin/profiles/create.view/", function() {
			$(this).find("form").ajaxForm("save", AdminProfiles.showCreateSheet);
		}, formData);
	}

};
