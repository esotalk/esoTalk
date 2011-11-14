// Member JavaScript

var ETMember = {
	
activityPage: 1,

init: function() {

	// Add click handlers to all of the controls.
	$("#editPermissionsLink").click(function(e) {
		e.preventDefault();
		ETMember.showPermissionsSheet();
	});
	$("#suspendLink").click(function(e) {
		e.preventDefault();
		ETMember.showSuspendSheet();
	});
	$("#renameLink").click(function(e) {
		e.preventDefault();
		ETMember.showRenameSheet();
	});
	$("#deleteLink").click(function(e) {
		e.preventDefault();
		ETMember.showDeleteSheet();
	});

	// Make the controls list into a popup.
	$("#memberActions").before($("#memberControls").popup({alignment: "right"}));

	// Add a tooltip to the online indicator.
	$("#memberProfile .online").tooltip({alignment: "left", className: "withArrow withArrowBottom", offset: [-9, 0]}).css("cursor", "pointer");

	// Initialize the activity section.
	this.initActivity();

},

initActivity: function() {

	// Add a click handler to the "view more" button.
	$("#viewMoreActivity").click(function(e) {
		e.preventDefault();
		$.ETAjax({
			url: "member/activity.view/"+ET.memberId+"/"+(++ETMember.activityPage),
			success: function(data) {
				$("#viewMoreActivity").remove();
				$("#memberContent").append(data);
				ETMember.initActivity();
			},
			beforeSend: function() {
				createLoadingOverlay("memberPane", "memberContent");
			},
			complete: function() {
				hideLoadingOverlay("memberPane", false);
			}
		});
	});

	// Add tootips to the controls on each activity item, and add a click handler to delete controls.
	$(".activity .controls a").tooltip({alignment: "center"});
	$(".activity .controls a.control-delete").click(function(e) {
		e.preventDefault();
		if (!confirm(T("message.confirmDelete"))) return;
		var activity = $(this).parents(".activity").first();
		$.ETAjax({
			url: "member/deleteActivity.ajax/"+activity.attr("id").substr(1),
			success: function(data) {
				activity.fadeOut(function() { $(this).remove(); });
			},
			global: true
		})
	});

},

showPermissionsSheet: function() {
	ETSheet.loadSheet("permissionsSheet", "member/permissions.view/"+ET.memberId, function() {

		// If the selected account is administrator, hide the groups checkboxes.
		if ($("#permissionForm select").val() == "administrator") $("#permissionGroups").hide();

		// Add change handlers to the account select to show/hide the groups checkboxes.
		$("#permissionForm select").change(function() {
			$("#permissionGroups")[$(this).val() == "member" ? "slideDown" : "slideUp"]("fast");
		});

		// Add change handlers to the account/groups inputs to update the permission info area.
		$("#permissionForm input, #permissionForm select").change(function() {
			$.ETAjax({
				id: "permissionInfo",
				url: "member/permissions.ajax/"+ET.memberId,
				data: $("#permissionsSheet form").serialize(),
				type: "POST",
				success: function(data) {
					$("#permissionInfo").html(data.view);
				},
				beforeSend: function() {
					createLoadingOverlay("permissionInfo", "permissionInfo");
				},
				complete: function() {
					hideLoadingOverlay("permissionInfo", false);
				}
			})
		});

	});
},

showSuspendSheet: function() {
	ETSheet.loadSheet("suspendSheet", "member/suspend.view/"+ET.memberId);
},

showRenameSheet: function() {
	ETSheet.loadSheet("renameMemberSheet", "member/rename.view/"+ET.memberId);
},

showDeleteSheet: function() {
	ETSheet.loadSheet("deleteMemberSheet", "member/delete.view/"+ET.memberId);
}

};

$(function() {
	ETMember.init();
})