tinymce.PluginManager.add( 'b2evo_attachments', function( editor ) {

	// This plugin requires the postID parameter
	if( typeof editor.settings.postID === 'undefined' )
	{
		return;
	}

	if( typeof editor.settings.attachments === 'undefined' )
	{
		editor.settings.attachments = [];
	}


	/**
	 * Get attachment details give a specific link ID
	 *
	 * @param Integer link ID that identifies the attachment
	 * @return Mixed array if attachment exists, null otherwise
	 */
	function getAttachment( linkId )
	{
		var attachments = editor.getParam( 'attachments' );
		for( var i = 0; i < attachments.length; i++ )
		{
			if( attachments[i].link_id == linkId )
			{
				return attachments[i];
			}
		}

		return null;
	}


	/**
	 * Loads attachments from the server and populates the attachment array
	 *
	 */
	function loadAttachments()
	{
		var collection = editor.getParam( 'collection' );
		var postID = editor.getParam( 'postID' );
		var restUrl = editor.getParam( 'rest_url' );

		tinymce.util.XHR.send({
			url: restUrl + '?api_version=1&api_request=collections/' + collection + '/items/' + postID,
			success: function( text ) {
				var attachmentList = tinymce.util.JSON.parse( text ).attachments;
				editor.settings.attachments = [];
				tinymce.each( attachmentList, function( link) {
					editor.settings.attachments.push( link );
				});

				// Fire event to let the other plugins know that we have finished loading the attachments
				editor.fire( 'attachmentsLoaded' );
			}
		});
	}


	// Add listener to reload attachments when the attachments change, e.g., new attachments, deleted or position modified
	document.addEventListener( 'b2evoAttachmentsChanged', function( event ) {
		loadAttachments();
	} );


	// Load attachments on init
	loadAttachments();
} );