$(function() {

	$('#fine-uploader').fineUploader({
          request: {
            endpoint: ET.webPath+'/conversation/upload'
          },
          text: {
	        uploadButton: '<img src="'+ET.webPath+'/plugins/Attachments/resources/attachment.png"> Attach a file'
	      },
	      failedUploadTextDisplay: {
	        mode: 'custom',
	        maxChars: 40,
	        responseProperty: 'error',
	        enableTooltip: true
	      },
	      deleteFile: {
	        enabled: true,
	        forceConfirm: true
	    }
    });

	ETConversation._updateEditPost = ETConversation.updateEditPost;
    ETConversation.updateEditPost = function(postId, html) {
    	ETConversation._updateEditPost(postId, html);

    	var post = $("#p" + postId);
    	$('#fine-uploader', post).fineUploader({
	          request: {
	            endpoint: ET.webPath+'/conversation/upload'
	          },
	          text: {
		        uploadButton: '<img src="'+ET.webPath+'/plugins/Attachments/resources/attachment.png"> Attach a file'
		      },
		      failedUploadTextDisplay: {
		        mode: 'custom',
		        maxChars: 40,
		        responseProperty: 'error',
		        enableTooltip: true
		      },
		      deleteFile: {
		        enabled: true,
		        forceConfirm: true
		    }
	    });
    }

});