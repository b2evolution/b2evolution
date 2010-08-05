<?php

global $Blog, $htsrv_url, $current_User, $Session, $admin_url, $status_list;

?>
<script type="text/javascript">

function isDefined( variable )
{
	return (typeof(variable) != 'undefined');
}

//Fade in background color
function fadeIn( id, color )
{
	jQuery('#' + id).animate({ backgroundColor: color }, 200);
}

function delete_comment_url( comment_id )
{
	var divid = 'commenturl_' + comment_id;
	fadeIn(divid, '#EE0000');

	$.ajax({
		type: 'POST',
		url: '<?php echo $htsrv_url; ?>async.php',
		data: 'blogid=' + <?php echo $Blog->ID ?> + '&commentid=' + comment_id + '&action=delete_comment_url' + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
		success: function(result) { $('#' + divid).remove(); }
	});
}

// Set comments status
function setCommentStatus( id, status, redirect_to )
{
	var divid = 'c' + id;
	switch(status)
	{
		case 'published':
			fadeIn(divid, '#339900');
			break;
		case 'deprecated':
			fadeIn(divid, '#656565');
			break;
	};

	$.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>async.php',
	data:
		{ 'blogid': <?php echo '\''.$Blog->ID.'\''; ?>, 
			'commentid': id,
			'status': status,
			'action': 'set_comment_status',
			'moderation': 'commentlist',
			'redirect_to': redirect_to,
			'crumb_comment': <?php echo '\''.get_crumb('comment').'\''; ?>,
		},
	success: function(result)
		{
			$('#comment_' + id).html(result);
		}
	});
}

//Delete comment
function deleteComment( commentIds )
{
	if( ! (commentIds instanceof Array) )
	{
		commentIds = [commentIds];
	}
	for(var id in commentIds)
	{
		var divid = 'c' + commentIds[id];
		if( $('#'+divid) != null )
		{
			fadeIn(divid, '#EE0000');
		}
	}
	var item_id = $('#comments_container').attr('value');
	if( ! isDefined( item_id) )
	{
		item_id = -1;
	}

	$.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>async.php',
	data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentIds=' + commentIds + '&action=delete_comments&itemid=' + item_id + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
	success: function(result)
		{
			$('#comments_container').html(result);
		}
	});
}

//This is called when we get the response from the server:
function antispamSettings( the_html )
{
	// add placeholder for antispam settings form:
	jQuery( 'body' ).append( '<div id="screen_mask" onclick="closeAntispamSettings()"></div><div id="overlay_page"></div>' );
	var evobar_height = jQuery( '#evo_toolbar' ).height();
	jQuery( '#screen_mask' ).css({ top: evobar_height });
	jQuery( '#screen_mask' ).fadeTo(1,0.5).fadeIn(200);
	jQuery( '#overlay_page' ).html( the_html ).addClass( 'overlay_page_active' );
	AttachServerRequest( 'antispam_ban' ); // send form via hidden iframe
	jQuery( '#close_button' ).bind( 'click', closeAntispamSettings );
	jQuery( '.SaveButton' ).bind( 'click', refresh_overlay );
}

// This is called to close the antispam ban overlay page
function closeAntispamSettings()
{
	jQuery( '#overlay_page' ).hide();
	jQuery( '.action_messages').remove();
	jQuery( '#server_messages' ).insertBefore( '.first_payload_block' );
	jQuery( '#overlay_page' ).remove();
	jQuery( '#screen_mask' ).remove();
	return false;
}

// Ban comment url
function ban_url( authorurl )
{
	$.ajax({
		type: 'POST',
		url: '<?php echo $admin_url; ?>',
		data: 'ctrl=antispam&action=ban&display_mode=js&mode=iframe&request=checkban&keyword=' + authorurl + 
			  '&' + <?php echo '\''.url_crumb('antispam').'\''; ?>,
		success: function(result)
		{
			antispamSettings( result );
		}
	});
}

// Refresh overlay page after Check&ban button click
function refresh_overlay()
{
	var parameters = jQuery( '#antispam_add' ).serialize();

	$.ajax({
		type: 'POST',
		url: '<?php echo $admin_url; ?>',
		data: 'action=ban&display_mode=js&mode=iframe&request=checkban&' + parameters,
		success: function(result)
		{
			antispamSettings( result );
		}
	});
	return false;
}

// Refresh comments on dashboard after ban url -> delete comment
function refreshAfterBan( deleted_ids )
{
	var comment_ids = String(deleted_ids).split(',');
	for( var i=0;i<comment_ids.length; ++i )
	{
		var divid = 'c' + comment_ids[i];
		fadeIn(divid, '#EE0000');
	}
	var item_id = $('#comments_container').attr('value');
	refresh_item_comments( item_id );
}

function startRefreshComments( item_id )
{
	$('#comments_container').fadeTo( 'slow', 0.1, function() {
		refresh_item_comments( item_id );
		$('#comments_container').fadeTo( "slow", 1 );
	} );
}

function refresh_item_comments( item_id )
{
	var statuses;

	if( $('#only_draft') && $('#only_draft').attr('checked') )
	{
		statuses = '(draft)';
	}
	else
	{
		statuses = '(draft,published,deprecated)';
	}
	if( ! isDefined( item_id) )
	{ // show all comments
		item_id = -1;
	}
	$.ajax({
		type: 'POST',
		url: '<?php echo $htsrv_url; ?>async.php',
		data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_item_comments&itemid=' + item_id + '&statuses=' + statuses,
		success: function(result)
		{
			$('#comments_container').html(result);
		}
	});
}

</script>
<?php
//end 
?>