<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $current_User, $Session, $admin_url, $status_list;

?>
<script type="text/javascript">

var modifieds = new Array();

function isDefined( variable )
{
	return (typeof(variable) != 'undefined');
}

//Fade in background color
function fadeIn( selector, color )
{
	if( jQuery( selector ).length == 0 )
	{
		return;
	}
	if( jQuery( selector ).get(0).tagName == 'TR' )
	{ // Fix selector, <tr> cannot have a css property background-color
		selector = selector + ' td';
	}
	var bg_color = jQuery( selector ).css( 'backgroundColor' );
	jQuery( selector ).animate( { backgroundColor: color }, 200 );
	return bg_color;
}

function fadeInStatus( selector, status )
{
	switch( status )
	{
		case 'published':
			return fadeIn( selector, '#99EE44' );
		case 'community':
			return fadeIn( selector, '#2E8BB9' );
		case 'protected':
			return fadeIn( selector, '#FF9C2A' );
		case 'review':
			return fadeIn( selector, '#CC0099' );
		case 'deprecated':
			return fadeIn( selector, '#656565' );
		case 'deleted':
			return fadeIn( selector, '#fcc' );
		case 'spam':
			return fadeIn( selector, '#ffc9c9' );
		case 'notsure':
			return fadeIn( selector, '#bbbbbb' );
		case 'ok':
			return fadeIn( selector, '#bcffb5' );
	}
}

function delete_comment_url( comment_id )
{
	var selector = '#commenturl_' + comment_id;
	fadeIn( selector, '#fcc' );

	jQuery.ajax({
		type: 'POST',
		url: '<?php echo get_samedomain_htsrv_url(); ?>async.php',
		data: 'blogid=' + <?php echo $Blog->ID ?> + '&commentid=' + comment_id + '&action=delete_comment_url' + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
		success: function(result) { jQuery('#' + divid).remove(); }
	});
}

function show_modifieds()
{
	for(var id in modifieds)
	{
		fadeInStatus( '#' + id, modifieds[id] );
	}
}

// Set comments status
function setCommentStatus( id, status, redirect_to )
{
	var divid = 'comment_' + id;
	var selector = '[id=' + divid + ']';

	if( typeof( modifieds[divid] ) != 'undefined' )
	{ // This comment is in process now, Wait the ending of previous action
		return;
	}
	modifieds[divid] = status;

	var color = fadeInStatus( selector, status );
	var statuses = get_show_statuses();
	var expiry_status = get_expiry_status();
	var currentpage = get_current_page();
	var item_id = get_itemid();
	var limit = get_limit();

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>async.php',
	data:
		{ 'blogid': <?php echo '\''.$Blog->ID.'\''; ?>,
			'commentid': id,
			'status': status,
			'limit': limit,
			'action': 'set_comment_status',
			<?php echo is_admin_page() ? "'is_backoffice': 1,\n" : ''; ?>
			'moderation': 'commentlist',
			'statuses': statuses,
			'expiry_status': expiry_status,
			'itemid': item_id,
			'currentpage': currentpage,
			'redirect_to': redirect_to,
			'crumb_comment': <?php echo '\''.get_crumb('comment').'\''; ?>,
		},
	success: function(result)
		{
			delete modifieds[divid];
			fadeIn( selector, color );
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
	var selector = '#comment_' + id;
	var color = fadeInStatus( selector, vote );

	var highlight_class = '';
	switch(vote)
	{
		case 'spam':
			highlight_class = '<?php echo button_class( 'button_red' ); ?>';
			break;
		case 'ok':
			highlight_class = '<?php echo button_class( 'button_green' ); ?>';
			break;
	}

	if( highlight_class != '' )
	{
		jQuery( '#vote_'+type+'_'+id ).find( '<?php echo button_class( 'button', true ); ?>' ).addClass( highlight_class );
	}

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data:
		{ 'blogid': <?php echo '\''.$Blog->ID.'\''; ?>,
			'commentid': id,
			'type': type,
			'vote': vote,
			'action': 'set_comment_vote',
			<?php echo is_admin_page() ? "'is_backoffice': 1,\n" : ''; ?>
			'crumb_comment': '<?php echo get_crumb('comment'); ?>',
		},
	success: function(result)
		{
			fadeIn( selector, color );
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
		var divid = 'comment_' + commentIds[id];
		if( jQuery('#'+divid) != null )
		{
			fadeIn(divid, '#fcc');
			modifieds[divid] = 'deleted';
		}
	}

	var statuses = get_show_statuses();
	var expiry_status = get_expiry_status();
	var item_id = get_itemid();
	var currentpage = get_current_page();
	var limit = get_limit();

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>async.php',
	data: 'action=get_opentrash_link&blog=<?php echo $Blog->ID; ?>&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
	success: function(result)
		{
			jQuery('#recycle_bin').replaceWith( ajax_debug_clear( result ) );
		}
	});

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>async.php',
	data:
		{ 'blogid': '<?php echo $Blog->ID; ?>',
			'commentIds': commentIds,
			'action': 'delete_comments',
			<?php echo is_admin_page() ? "'is_backoffice': 1,\n" : ''; ?>
			'itemid': item_id,
			'statuses': statuses,
			'expiry_status': expiry_status,
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

<?php
// Initialize JavaScript to build and open window
echo_modalwindow_js();
?>

// Ban comment url
function ban_url( authorurl )
{
	<?php global $rsc_url; ?>
	openModalWindow( '<img src="<?php echo $rsc_url; ?>img/ajax-loader2.gif" alt="<?php echo T_('Loading...'); ?>" title="<?php echo T_('Loading...'); ?>" style="display:block;margin:auto;position:absolute;top:0;bottom:0;left:0;right:0;" />', '90%', false, '', false );
	jQuery.ajax({
		type: 'POST',
		url: '<?php echo $admin_url; ?>',
		data: 'ctrl=antispam&action=ban&display_mode=js&mode=iframe&request=checkban&keyword=' + authorurl +
			  '&' + <?php echo '\''.url_crumb('antispam').'\''; ?>,
		success: function(result)
		{
			openModalWindow( result, '90%', false, '', false );
		}
	});
}

// Refresh comments on dashboard after ban url -> delete comment
function refreshAfterBan( deleted_ids )
{
	var comment_ids = String(deleted_ids).split(',');
	for( var i=0;i<comment_ids.length; ++i )
	{
		fadeIn( '#comment_' + comment_ids[i], '#fcc' );
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

function get_expiry_status()
{
	var expiry_status = 'active';
	if( jQuery('#show_expiry_all') && jQuery('#show_expiry_all').attr('checked') )
	{
		expiry_status = 'all';
	}

	return expiry_status;
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
	var expiry_status = get_expiry_status();

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
		url: '<?php echo get_samedomain_htsrv_url(); ?>async.php',
		data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_item_comments&itemid=' + item_id + '&statuses=' + statuses + '&currentpage=' + currentpage + '&expiry_status=' + expiry_status,
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