<?php

global $Blog, $htsrv_url, $current_User, $Session, $admin_url, $status_list;

?>
<script type="text/javascript">

var modifieds = new Array();

function isDefined( variable )
{
	return (typeof(variable) != 'undefined');
}

//Fade in background color
function fadeIn( id, color )
{
	jQuery('#' + id).animate({ backgroundColor: color }, 200);
}

function fadeInStatus( id, status )
{
	switch(status)
	{
		case 'published':
			fadeIn(id, '#339900');
			break;
		case 'deprecated':
			fadeIn(id, '#656565');
			break;
		case 'deleted':
			fadeIn(id, '#EE0000');
			break;
	};
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

function show_modifieds()
{
	for(var id in modifieds)
	{
		fadeInStatus( id, modifieds[id] );
	}
}

// Set comments status
function setCommentStatus( id, status, redirect_to )
{
	var divid = 'c' + id;
	fadeInStatus( divid, status );
	modifieds[divid] = status;

	var statuses = get_show_statuses();
	var currentpage = get_current_page();
	var item_id = get_itemid();

	$.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>async.php',
	data:
		{ 'blogid': <?php echo '\''.$Blog->ID.'\''; ?>, 
			'commentid': id,
			'status': status,
			'action': 'set_comment_status',
			'moderation': 'commentlist',
			'statuses': statuses,
			'itemid': item_id,
			'currentpage': currentpage,
			'redirect_to': redirect_to,
			'crumb_comment': <?php echo '\''.get_crumb('comment').'\''; ?>,
		},
	success: function(result)
		{
			delete modifieds[divid];
			$('#comments_container').html(result);
			show_modifieds();
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
			modifieds[divid] = 'deleted';
		}
	}

	var statuses = get_show_statuses();
	var item_id = get_itemid();
	var currentpage = get_current_page();

	$.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>async.php',
	data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentIds=' + commentIds + '&action=delete_comments&itemid=' + item_id + '&statuses=' + statuses + '&currentpage=' + currentpage + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
	success: function(result)
		{
			delete modifieds[divid];
			$('#comments_container').html(result);
			show_modifieds();
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

	// Close antispam popup if Escape key is pressed:
	var keycode_esc = 27;
	jQuery(document).keyup(function(e)
	{
		if( e.keyCode == keycode_esc )
		{
			closeAntispamSettings();
		}
	});
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
	var item_id = get_itemid();
	refresh_item_comments( item_id );
}

function startRefreshComments( item_id, currentpage )
{
	$('#comments_container').fadeTo( 'slow', 0.1, function() {
		refresh_item_comments( item_id, currentpage );
	} );
}

function endRefreshComments( result )
{
	$('#comments_container').html(result);
	$('#comments_container').fadeTo( "slow", 1 );
}

function get_current_page()
{
	if( ( isDefined( $('#currentpage') ) ) && isDefined( $('#currentpage').attr('value') ) )
	{
		return $('#currentpage').attr('value');
	}
	return 1;
}

function get_show_statuses()
{
	if( $('#only_draft') && $('#only_draft').attr('checked') )
	{
		return '(draft)';
	}
	else if( $('#only_published') && $('#only_published').attr('checked') )
	{
		return '(published)';
	}

	return '(draft,published,deprecated)';
}

function get_itemid()
{
	var item_id = $('#comments_container').attr('value');
	if( ! isDefined( item_id) )
	{
		item_id = -1;
	}
	return item_id;
}

function refresh_item_comments( item_id, currentpage )
{
	var statuses = get_show_statuses();

	if( ! isDefined( currentpage ) )
	{
		currentpage = get_current_page();
	}
	if( ! isDefined( item_id) )
	{ // show all comments
		item_id = -1;
	}
	$.ajax({
		type: 'POST',
		url: '<?php echo $htsrv_url; ?>async.php',
		data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_item_comments&itemid=' + item_id + '&statuses=' + statuses + '&currentpage=' + currentpage,
		success: function(result)
		{
			endRefreshComments( result );
			show_modifieds();
		}
	});
}

</script>
<?php
//end 
?>