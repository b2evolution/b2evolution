<?php
/**
 * This file implements the right sidebar for the comment browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;
/**
 * @var Blog
 */
global $Collection, $Blog;

global $current_User;

global $CommentList;

$pp = $CommentList->param_prefix;

global ${$pp.'show_statuses'}, ${$pp.'expiry_statuses'}, ${$pp.'s'}, ${$pp.'sentence'}, ${$pp.'exact'};
global ${$pp.'rating_toshow'}, ${$pp.'rating_turn'}, ${$pp.'rating_limit'}, ${$pp.'url_match'}, ${$pp.'author_IDs'}, ${$pp.'authors_login'}, ${$pp.'author_url'}, ${$pp.'include_emptyurl'}, ${$pp.'author_IP'};
global $tab3;

$show_statuses = ${$pp.'show_statuses'};
$expiry_statuses = ${$pp.'expiry_statuses'};
$s = ${$pp.'s'};
$sentence = ${$pp.'sentence'};
$exact = ${$pp.'exact'};
$rating_toshow = ${$pp.'rating_toshow'};
$rating_turn = ${$pp.'rating_turn'};
$rating_limit = ${$pp.'rating_limit'};
$url_match = ${$pp.'url_match'};
$author_IDs = ${$pp.'author_IDs'};
$authors_login = ${$pp.'authors_login'};
$author_url = ${$pp.'author_url'};
$include_emptyurl = ${$pp.'include_emptyurl'};
$author_IP = ${$pp.'author_IP'};

load_funcs( 'skins/_skin.funcs.php' );

$Form = new Form( NULL, 'comment_filter_form', 'get', 'none' );

$Form->begin_form( 'evo_sidebar_filters' );

$Form->hidden_ctrl();
$Form->hidden( 'tab3', $tab3 );
$Form->hidden( 'blog', $Blog->ID );

echo '<div class="filter_buttons">';
	if( $CommentList->is_filtered() )
	{	// TODO: style this better:
		echo '<a href="?ctrl=comments&amp;blog='.$Blog->ID.'&amp;tab3='.$tab3.'&amp;filter=reset" class="btn btn-warning" style="margin-right: 5px">';
		echo get_icon( 'filter' ).' '.T_('Remove filters').'</a>';
	}

	$Form->submit( array( 'submit', T_('Apply filters'), 'btn-info' ) );
echo '</div>';

// COMMENTS TO SHOW:
if( $tab3 != 'meta' && !$CommentList->is_trashfilter() )
{ // These filters only for normal comments:
	$fold_statuses = ( $CommentList->default_filters['statuses'] == $CommentList->filters['statuses'] );
	$fold_expiry = ( $CommentList->default_filters['expiry_statuses'] == $CommentList->filters['expiry_statuses'] );
	$fold_comments_to_show = $fold_statuses && $fold_expiry;
	$Form->begin_fieldset( T_('Comments to show'), array( 'id' => 'comment_filter_comment_to_show', 'fold' => true, 'default_fold' => $fold_comments_to_show ) );

	$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_comment!' ), array( 'redirected' ) );
	$statuses = get_visibility_statuses( 'notes-array', $exclude_statuses );
	foreach( $statuses as $status_key => $status_name )
	{ // show statuses
		?>
		<input type="checkbox" name="<?php echo $pp ?>show_statuses[]" value="<?php echo $status_key; ?>" id="sh_<?php echo $status_key; ?>" class="checkbox" <?php if( in_array( $status_key, $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_<?php echo $status_key; ?>" title="<?php echo substr( $status_name[1], 1, strlen( $status_name[1] ) - 2 ); ?>"><?php echo $status_name[0] ?></label><br />
		<?php
	}
	?>

	<div style="margin-top:5px">
	<input type="checkbox" name="<?php echo $pp ?>expiry_statuses[]" value="active" id="show_active" class="checkbox" <?php if( in_array( "active", $expiry_statuses ) ) echo 'checked="checked" '?> />
	<label for="show_active"><?php echo T_('Show active') ?> </label><br />
	<input type="checkbox" name="<?php echo $pp ?>expiry_statuses[]" value="expired" id="show_expired" class="checkbox" <?php if( in_array( "expired", $expiry_statuses ) ) echo 'checked="checked" '?> />
	<label for="show_expired"><?php echo T_('Show expired') ?> </label>
	</div>

	<?php
	$Form->end_fieldset();
}
elseif( $CommentList->is_trashfilter() )
{
	foreach( $show_statuses as $show_status )
	{
		$Form->hidden( $pp.'show_statuses[]', $show_status );
	}
}

// KEYWORDS:
$fold_keywords = ( $CommentList->default_filters['keywords'] == $CommentList->filters['keywords'] );
$Form->begin_fieldset( T_('Keywords'), array( 'id' => 'comment_filter_keywords', 'fold' => true, 'default_fold' => $fold_keywords ) );
?>
<div class="tile"><input type="text" name="<?php echo $pp ?>s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField form-control" /></div>
<div class="tile">
	<input type="radio" name="<?php echo $pp ?>sentence" value="AND" id="sentAND" class="radio" <?php if( $sentence=='AND' ) echo 'checked="checked" '?> />
	<label for="sentAND"><?php echo T_('AND') ?></label>
</div>
<div class="tile">
	<input type="radio" name="<?php echo $pp ?>sentence" value="OR" id="sentOR" class="radio" <?php if( $sentence=='OR' ) echo 'checked="checked" '?> />
	<label for="sentOR"><?php echo T_('OR') ?></label>
</div>
<div class="tile">
	<input type="radio" name="<?php echo $pp ?>sentence" value="sentence" id="sentence" class="radio" <?php if( $sentence=='sentence' ) echo 'checked="checked" '?> />
	<label for="sentence"><?php echo T_('Entire phrase') ?></label>
</div>
<div class="tile">
	<input type="checkbox" name="<?php echo $pp ?>exact" value="1" id="exact" class="checkbox" <?php if( $exact ) echo 'checked="checked" '?> />
	<label for="exact"><?php echo T_('Exact match') ?></label>
</div>

<?php
$Form->end_fieldset();

// RATING:
if( $tab3 != 'meta' )
{ // These filters only for normal comments:
	$fold_rating_toshow = ( $CommentList->default_filters['rating_toshow'] == $CommentList->filters['rating_toshow'] );
	$fold_rating_turn   = ( $CommentList->default_filters['rating_turn'] == $CommentList->filters['rating_turn'] );
	$fold_rating_limit  = ( $CommentList->default_filters['rating_limit'] == $CommentList->filters['rating_limit'] );
	$fold_rating = $fold_rating_toshow && $fold_rating_turn && $fold_rating_limit;
	$Form->begin_fieldset( T_('Rating'), array( 'id' => 'comment_filter_rating', 'fold' => true, 'default_fold' => $fold_rating ) );
	?>
	<div class="rating">
		<input type="checkbox" name="<?php echo $pp ?>rating_toshow[]" value="norating" id="rating_ts_norating" class="checkbox" <?php if( isset( $rating_toshow ) && in_array( "norating", $rating_toshow ) ) echo 'checked="checked" '?> />
		<label for="rating_ts_norating"><?php echo T_('No rating') ?> </label><br />

		<input type="checkbox" name="<?php echo $pp ?>rating_toshow[]" value="haverating" id="rating_ts_haverating" class="checkbox" <?php if( isset( $rating_toshow ) && in_array( "haverating", $rating_toshow ) ) echo 'checked="checked" '?> />
		<label for="rating_ts_haverating"><?php echo T_('Have rating') ?> </label><br />
	</div>
	<div class="rating">
		<input type="radio" name="<?php echo $pp ?>rating_turn" value="above" id="rating_above" class="radio" <?php if( $rating_turn=='above' ) echo 'checked="checked" '?> />
		<label for="rating_above"><?php echo T_('Above') ?></label>

		<input type="radio" name="<?php echo $pp ?>rating_turn" value="below" id="rating_below" class="radio" <?php if( $rating_turn=='below' ) echo 'checked="checked" '?> />
		<label for="rating_below"><?php echo T_('Below') ?></label><br />

		<input type="radio" name="<?php echo $pp ?>rating_turn" value="exact" id="rating_exact" class="radio" <?php if( $rating_turn=='exact' ) echo 'checked="checked" '?> />
		<label for="rating_norating"><?php echo T_('Exact') ?></label>
	</div>
	<div class="rating">
		<?php
		echo T_('Poor');

		for( $i=1; $i<=5; $i++ )
		{
			echo '<input type="radio" name="'.$pp.'rating_limit" value="'.$i.'" class="radio"';
			if( $rating_limit == $i )
			{
				echo ' checked="checked"';
			}
			echo ' />';
		}

		echo T_('Excellent');
		?>
	</div>

	<?php
	$Form->end_fieldset();

	// AUTHOR:
	// Load only first 21 users to know when we should display an input box instead of full users list:
	$first_users_SQL = new SQL( 'Get users count for filter comments list' );
	$first_users_SQL->SELECT( 'user_ID' );
	$first_users_SQL->FROM( 'T_users' );
	$first_users_SQL->ORDER_BY( 'user_login' );
	$first_users_SQL->LIMIT( '21' );
	$first_users = $DB->get_col( $first_users_SQL );
	$user_count = count( $first_users );
	/*
		* Authors:
		* TODO: allow multiple selection
		*/
	$fold_author_IDs = ( $CommentList->default_filters['author_IDs'] == $CommentList->filters['author_IDs'] ) || ( $CommentList->filters['author_IDs'] == 0 );
	$fold_authors_login = ( $CommentList->default_filters['authors_login'] == $CommentList->filters['authors_login'] );
	$fold_authors = $fold_author_IDs && $fold_authors_login;
	$Form->begin_fieldset( T_('Author'), array( 'id' => 'comment_filter_author', 'fold' => true, 'default_fold' => $fold_authors ) );
	if( $user_count )
	{
		if( $user_count > 20 )
		{	// Display an input box to enter user login:
			echo '<label for="'.$pp.'authors_login">'.T_('User').':</label> <input type="text" class="form-control middle autocomplete_login" value="'.format_to_output( $authors_login, 'formvalue' ).'" name="'.$pp.'authors_login" id="'.$pp.'authors_login" />';
		}
		else
		{	// Display a list of users:
			echo '<ul>'
				.'<li>'
					.'<input type="radio" name="'.$pp.'author_IDs" value="0" class="radio"'.( empty( $author_IDs ) ? ' checked="checked"' : '' ).' /> '
					.'<a href="'.regenerate_url( $pp.'author_IDs', $pp.'author_IDs=0' ).'">'.T_('Any').'</a>'
				.'</li>';
			$UserCache = & get_UserCache();
			$UserCache->load_list( $first_users );
			foreach( $first_users as $user_ID )
			{
				$loop_User = & $UserCache->get_by_ID( $user_ID );
				echo '<li>'
					.'<input type="radio" name="'.$pp.'author_IDs" value="'.$loop_User->ID.'" class="radio"'.( $loop_User->ID == $author_IDs ? ' checked="checked"' : '' ).' /> '
					.'<a href="'.regenerate_url( $pp.'author_IDs', $pp.'author_IDs='.$loop_User->ID ).'" rel="bubbletip_user_'.$loop_User->ID.'">'
						.$loop_User->get_colored_login( array( 'login_text' => 'name' ) )
					.'</a>'
				.'</li>';
			}
			echo '</ul>';
		}
	}
	$Form->end_fieldset();

	// AUTHOR URL:
	$fold_author_url = ( $CommentList->default_filters['author_url'] == $CommentList->filters['author_url'] );
	$Form->begin_fieldset( T_('Author URL'), array( 'id' => 'comment_filter_author_url', 'fold' => true, 'default_fold' => $fold_author_url ) );
	?>
	<div class="tile"><input type="text" name="<?php echo $pp ?>author_url" size="20" value="<?php echo htmlspecialchars($author_url) ?>" class="SearchField form-control" /></div>
	<div class="tile">
		<input type="radio" name="<?php echo $pp ?>url_match" value="=" id="with_url" class="radio" <?php if( $url_match=='=' ) echo 'checked="checked" '?> />
		<label for="with_url"><?php echo T_('With this') ?></label>

		<input type="radio" name="<?php echo $pp ?>url_match" value="!=" id="without_url" class="radio" <?php if( $url_match=='!=' ) echo 'checked="checked" '?> />
		<label for="without_url"><?php echo T_('Without this') ?></label>
	</div>
	<div class="tile">
		<input type="checkbox" name="<?php echo $pp ?>include_emptyurl" value="true" id="without_any_url" class="checkbox" <?php if( $include_emptyurl ) echo 'checked="checked" '?> />
		<label for="without_any_url"><?php echo T_('Include comments with no url') ?> <span class="notes">(<?php echo T_('Works only when url filter is set') ?>)</span></label><br />
	</div>

	<?php
	$Form->end_fieldset();

	// IP:
	$fold_ip_address = ( $CommentList->default_filters['author_IP'] == $CommentList->filters['author_IP'] );
	$Form->begin_fieldset( T_('IP'), array( 'id' => 'comment_filter_ip_address', 'fold' => true, 'default_fold' => $fold_ip_address ) );
	?>
	<?php echo T_('IP') ?> <input type="text" name="<?php echo $pp ?>author_IP" value="<?php echo htmlspecialchars($author_IP) ?>" class="SearchField form-control" />
	<span class="note"><?php
		// We use sprintf to avoid problems with a single % sign in transifex
		echo sprintf( T_('use \'%%\' for partial matches') );
	?></span>
	<?php

	$Form->end_fieldset();
}

$Form->end_form();

// Enable JS for fieldset folding:
echo_fieldset_folding_js();
?>