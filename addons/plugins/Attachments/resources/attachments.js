$(function() {

	function initUploadArea(postId) {

		var $post = $("#"+postId);
		var $attachments = $post.find('.attachments-edit ul');

		var uploader = new qq.FineUploaderBasic({
			debug: true,
			button: $post.find('.attachments-button')[0],
			request: {
				endpoint: ET.webPath+'/?p=attachment/upload',
				params: {
					postId: postId == "reply" ? (ETConversation.id ? "c"+ETConversation.id : "c0") : postId
				}
			},
			callbacks: {
				onSubmit: function(id, fileName) {
					$attachments.append('<li id="file-'+postId+'-' + id + '"></li>');
				},
				onUpload: function(id, fileName) {
					$('#file-'+postId+'-' + id).addClass('attachment-uploading')
						.html('Initializing “' + fileName + '”...');
				},
				onProgress: function(id, fileName, loaded, total) {
					if (loaded < total) {
						progress = Math.round(loaded / total * 100) + '% of ' + Math.round(total / 1024) + ' kB';
						$('#file-'+postId+'-' + id).html('Uploading “' + fileName + '” ' +progress);
					} else {
						$('#file-'+postId+'-' + id).html('Saving “' + fileName + '”...');
					}
				},
				onComplete: function(id, fileName, responseJSON) {
					if (responseJSON.success) {
						$('#file-'+postId+'-' + id).removeClass('attachment-uploading')
							.html('<a href="#" class="control-delete" title="Delete" data-id="'+id+'"><i class="icon-remove"></i></a> <strong>' + fileName + '</strong>');
					} else {
						$('#file-'+postId+'-' + id).remove();
						ETMessages.showMessage('Error uploading "'+fileName+'": '+responseJSON.error, {className: "warning dismissable", id: "attachmentUploadError"});
					}
				}
			}
		});

		var dragAndDropModule = new qq.DragAndDrop({
			dropZoneElements: [$post.find(".dropZone")[0]],
			classes: {
				dropActive: "dropZoneActive"
			},
			hideDropZonesBeforeEnter: true,
			callbacks: {
				processingDroppedFiles: function() {
					//TODO: display some sort of a "processing" or spinner graphic
				},
				processingDroppedFilesComplete: function(files) {
					//TODO: hide spinner/processing graphic

					uploader.addFiles(files); //this submits the dropped files to Fine Uploader
				}
			}
		});

		$post.on("click", ".attachments-edit .control-delete", function(e) {
			e.preventDefault();
			$.ETAjax({
				url: "attachment/remove/"+$(this).data("id")
			});
			$(this).parent().remove();
		});

	}

	initUploadArea("reply");

	ETConversation._updateEditPost = ETConversation.updateEditPost;
	ETConversation.updateEditPost = function(postId, html) {
		ETConversation._updateEditPost(postId, html);
		initUploadArea("p"+postId);
	};

	ETConversation._resetReply = ETConversation.resetReply;
	ETConversation.resetReply = function() {
		ETConversation._resetReply();
		$("#reply .attachments ul").html("");
	};

});