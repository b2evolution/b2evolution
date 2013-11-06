<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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
	var bg_color = jQuery('#' + id).css( 'backgroundColor' );
	jQuery('#' + id).animate({ backgroundColor: color }, 200);
	return bg_color;
}

function fadeInStatus( id, status )
{
	switch(status)
	{
		
		case 'published':
			return fadeIn( id, '#99EE44' );
		case 'community':
			return fadeIn( id, '#2E8BB9' );
		case 'protected':
			return fadeIn( id, '#FF9C2A' );
		case 'review':
			return fadeIn( id, '#CC0099' );
		case 'deprecated':
			return fadeIn( id, '#656565' );
		case 'deleted':
			return fadeIn( id, '#fcc' );
		case 'spam':
			return fadeIn( id, '#ffc9c9' );
		case 'notsure':
			return fadeIn( id, '#bbbbbb' );
		case 'ok':
			return fadeIn( id, '#bcffb5' );
	}
}

function delete_comment_url( comment_id )
{
	var divid = 'commenturl_' + comment_id;
	fadeIn(divid, '#fcc');

	jQuery.ajax({
		type: 'POST',
		url: '<?php echo $htsrv_url; ?>async.php',
		data: 'blogid=' + <?php echo $Blog->ID ?> + '&commentid=' + comment_id + '&action=delete_comment_url' + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
		success: function(result) { jQuery('#' + divid).remove(); }
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
	var limit = get_limit();

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>async.php',
	data:
		{ 'blogid': <?php echo '\''.$Blog->ID.'\''; ?>,
			'commentid': id,
			'status': status,
			'limit': limit,
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
			jQuery( '#comments_container' ).html( ajax_debug_clear( result ) );
			jQuery( '.vote_spam' ).show();
			show_modifieds();
		}
	});
}

// Display voting tool when JS is enable
jQuery( 'document' ).ready( function() { jQuery( '.vote_spam' ).show(); } );
// Set comments vote
function setCommentVote( id, type, vote )
{
	var color = fadeInStatus( 'c' + id, vote );

	var highlight_class = '';
	switch(vote)
	{
		case 'spam':
			highlight_class = 'roundbutton_red';
			break;
		case 'ok':
			highlight_class = 'roundbutton_green';
			break;
	}

	if( highlight_class != '' )
	{
		jQuery( '#vote_'+type+'_'+id ).find( 'a.roundbutton, span.roundbutton' ).addClass( highlight_class );
	}

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>anon_async.php',
	data:
		{ 'blogid': <?php echo '\''.$Blog->ID.'\''; ?>,
			'commentid': id,
			'type': type,
			'vote': vote,
			'action': 'set_comment_vote',
			'crumb_comment': <?php echo '\''.get_crumb('comment').'\''; ?>,
		},
	success: function(result)
		{
			fadeIn( 'c' + id, color );
			jQuery('#vote_'+type+'_'+id).after( ajax_debug_clear( result ) );
			jQuery('#vote_'+type+'_'+id).remove();
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
		if( jQuery('#'+divid) != null )
		{
			fadeIn(divid, '#fcc');
			modifieds[divid] = 'deleted';
		}
	}

	var statuses = get_show_statuses();
	var item_id = get_itemid();
	var currentpage = get_current_page();
	var limit = get_limit();

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>async.php',
	data: 'action=get_opentrash_link&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
	success: function(result)
		{
			jQuery('#recycle_bin').replaceWith( ajax_debug_clear( result ) );
		}
	});

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo $htsrv_url; ?>async.php',
	data: 
		{ 'blogid': '<?php echo $Blog->ID; ?>',
			'commentIds': commentIds,
			'action': 'delete_comments',
			'itemid': item_id,
			'statuses': statuses,
			'currentpage': currentpage,
			'limit': limit,
			'crumb_comment': '<?php echo get_crumb('comment'); ?>',
		},
	success: function(result)
		{
			jQuery( '#' + divid ).effect( 'transfer', { to: jQuery( '#recycle_bin' ) }, 700, function() {
				delete modifieds[divid];
				jQuery( '#comments_container' ).html( ajax_debug_clear( result ) );
				jQuery( '.vote_spam' ).show();
				show_modifieds();
			});
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
	<?php global $rsc_url; ?>
	antispamSettings( '<img src="<?php echo $rsc_url; ?>img/ajax-loader2.gif" alt="<?php echo T_('Loading...'); ?>" title="<?php echo T_('Loading...'); ?>" style="display:block;margin:auto;position:absolute;top:0;bottom:0;left:0;right:0;" />' );
	jQuery.ajax({
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

	jQuery.ajax({
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
		fadeIn(divid, '#fcc');
	}
	var item_id = get_itemid();
	refresh_item_comments( item_id );
}

function startRefreshComments( item_id, currentpage )
{
	jQuery('#comments_container').fadeTo( 'slow', 0.1, function() {
		refresh_item_comments( item_id, currentpage );
	} );
}

function endRefreshComments( result )
{
	jQuery('#comments_container').html(result);
	jQuery('#comments_container').fadeTo( "slow", 1 );
}

function get_current_page()
{
	if( ( isDefined( jQuery('#currentpage') ) ) && isDefined( jQuery('#currentpage').attr('value') ) )
	{
		return jQuery('#currentpage').attr('value');
	}
	return 1;
}

function get_limit()
{
	var limit = jQuery( 'select[name$=_per_page]' );
	if( ( isDefined( limit ) ) )
	{
		return limit.val();
	}
	return 0;
}

function get_show_statuses()
{
	if( jQuery('#only_draft') && jQuery('#only_draft').attr('checked') )
	{
		return '(draft)';
	}
	else if( jQuery('#only_published') && jQuery('#only_published').attr('checked') )
	{
		return '(published)';
	}

	return '(published,community,protected,private,review,draft,deprecated)';
}

function get_itemid()
{
	var item_id = jQuery('#comments_container').attr('value');
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
	jQuery.ajax({
		type: 'POST',
		url: '<?php echo $htsrv_url; ?>async.php',
		data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_item_comments&itemid=' + item_id + '&statuses=' + statuses + '&currentpage=' + currentpage,
		success: function(result)
		{
			endRefreshComments( ajax_debug_clear( result ) );
			show_modifieds();
		}
	});
}

</script>
<?php
//end
?>