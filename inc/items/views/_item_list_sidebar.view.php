<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;
/**
 * @var Blog
 */
global $Blog;
/**
 * @var Plugins
 */
global $Plugins;
/**
 * @var ItemList
 */
global $ItemList;

$pp = $ItemList->param_prefix;

global $tab;
global ${$pp.'show_past'}, ${$pp.'show_future'}, ${$pp.'show_statuses'}, ${$pp.'s'}, ${$pp.'sentence'}, ${$pp.'exact'}, ${$pp.'author'}, ${$pp.'author_login'}, ${$pp.'assgn'}, ${$pp.'assgn_login'}, ${$pp.'status'};

$show_past = ${$pp.'show_past'};
$show_future = ${$pp.'show_future'};
$show_statuses = ${$pp.'show_statuses'};
$s = ${$pp.'s'};
$sentence = ${$pp.'sentence'};
$exact = ${$pp.'exact'};
$author = ${$pp.'author'};
$author_login = ${$pp.'author_login'};
$assgn = ${$pp.'assgn'};
$assgn_login = ${$pp.'assgn_login'};
$status = ${$pp.'status'};


load_funcs( 'skins/_skin.funcs.php' );

$Widget = new Widget();
$template = $AdminUI->get_template( 'side_item' );

$Widget->title = format_to_output( $Blog->get_maxlen_name( 22 ), 'htmlbody' );
echo $Widget->replace_vars( $template['block_start'] );

	// CALENDAR:
	// Call the Calendar plugin:
	$Plugins->call_by_code( 'evo_Calr', array( // Params follow:
			'block_start'     => '',
			'block_end'       => '',
			'title'           => '',        // No title.
			'link_type'       => 'context', // Preserve page context
			'itemlist_prefix' => $pp        // Prefix of the ItemList object
		) );

echo $template['block_end'];

$Widget = new Widget();
$Widget->title = T_('Filters');
if( $ItemList->is_filtered() )
{ // List is filtered, offer option to reset filters:
	$Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 4, 4, array( 'class' => 'action_icon btn btn-warning btn-sm' ) );
}
echo $Widget->replace_vars( $template['block_start'] );

	$Form = new Form( NULL, 'resetform', 'get', 'none' );

	$Form->begin_form( '' );

		$Form->hidden_ctrl();
		$Form->button_input( array(
				'tag'   => 'button',
				'value' => get_icon( 'filter' ).' './* TRANS: Verb */ T_('Filter'),
				'class' => 'search btn-info pull-right',
			) );

		$Form->hidden( 'tab', $tab );
		$Form->hidden( 'blog', $Blog->ID );

		echo '<fieldset class="clearfix">';
		echo '<legend>'.T_('Posts to show').'</legend>';
		?>
		<div>

		<input type="checkbox" name="<?php echo $pp ?>show_past" value="1" id="ts_min" class="checkbox" <?php if( $show_past ) echo 'checked="checked" '?> />
		<label for="ts_min"><?php echo T_('Past') ?></label><br />

		<input type="checkbox" name="<?php echo $pp ?>show_future" value="1" id="ts_max" class="checkbox" <?php if( $show_future ) echo 'checked="checked" '?> />
		<label for="ts_max"><?php echo T_('Future') ?></label>

		</div>

		<div>

		<?php
		// Get those statuses that current User can't view in this blog, and don't display those as filters
		$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_post!' ), array( 'trash' ) );
		$statuses = get_visibility_statuses( 'notes-array', $exclude_statuses );
		foreach( $statuses as $status_key => $status_name )
		{ // show statuses
			?>
			<input type="checkbox" name="<?php echo $pp ?>show_statuses[]" value="<?php echo $status_key; ?>" id="sh_<?php echo $status_key; ?>" class="checkbox" <?php if( in_array( $status_key, $show_statuses ) ) echo 'checked="checked" '?> />
			<label for="sh_<?php echo $status_key; ?>" title="<?php echo substr( $status_name[1], 1, strlen( $status_name[1] ) - 2 ); ?>"><?php echo $status_name[0] ?></label><br />
			<?php
		}
		?>

		</div>

		<?php
		echo '</fieldset>';


		echo '<fieldset>';
		echo '<legend>'.T_('Title / Text contains').'</legend>';

		?>
		<div class="tile"><input type="text" name="<?php echo $pp ?>s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField form-control" /></div>
		<?php
		// echo T_('Words').' : ';
		?>
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
		echo '</fieldset>';


		// Load current blog members into cache:
		$UserCache = & get_UserCache();
		// Load only first 21 users to know when we should display an input box instead of full users list
		$UserCache->load_blogmembers( $Blog->ID, 21, false );
		$user_count = count( $UserCache->cache );

		if( $Blog->get_setting( 'use_workflow' ) )
		{ // Display only if workflow is enabled

			/*
			 * Assignees:
			 * TODO: allow multiple selection
			 */
			echo '<fieldset>';
			echo '<legend>'.T_('Assignees').'</legend>';
			if( $user_count )
			{
				echo '<ul>';

				echo '<li><input type="radio" name="'.$pp.'assgn" value="0" class="radio"';
				if( empty( $assgn ) ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( $pp.'assgn', $pp.'assgn=0' ).'">'.T_('Any').'</a></li>';

				echo '<li><input type="radio" name="'.$pp.'assgn" value="-" class="radio"';
				if( '-' == $assgn ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( $pp.'assgn', $pp.'assgn=-' ).'">'.T_('Not assigned').'</a></li>';

				if( $user_count > 20 )
				{ // Display an input box to enter user login
					echo '<li>';
					echo T_('User').': <input type="text" class="form_text_input autocomplete_login" value="'.$assgn_login.'" name="'.$pp.'assgn_login" id="'.$pp.'assgn_login" />';
					echo '</li>';
				}
				else
				{ // Display a list of users
					foreach( $UserCache->cache as $loop_User )
					{
						echo '<li><input type="radio" name="'.$pp.'assgn" value="'.$loop_User->ID.'" class="radio"';
						if( $loop_User->ID == $assgn ) echo ' checked="checked"';
						echo ' /> <a href="'.regenerate_url( $pp.'assgn', $pp.'assgn='.$loop_User->ID ).'" rel="bubbletip_user_'.$loop_User->ID.'">';
						echo $loop_User->get_colored_login( array( 'login_text' => 'name' ) );
						echo '</a></li>';
					}
				}
				echo '</ul>';
			}
			echo '</fieldset>';
			?>
			<script type="text/javascript">
			jQuery( '#<?php echo $pp; ?>assgn_login' ).focus( function()
			{
				jQuery( 'input[name=<?php echo $pp; ?>assgn]' ).removeAttr( 'checked' );
			} );
			jQuery( 'input[name=<?php echo $pp; ?>assgn]' ).click( function()
			{
				jQuery( '#<?php echo $pp; ?>assgn_login' ).val( '' );
			} );
			</script>
			<?php
		}

		// Load only first 21 users to know when we should display an input box instead of full users list
		$UserCache->load_blogmembers( $Blog->ID, 21 );
		$user_count = count( $UserCache->cache );

		/*
		 * Authors:
		 * TODO: allow multiple selection
		 */
		echo '<fieldset>';
		echo '<legend>'.T_('Authors').'</legend>';
		if( $user_count )
		{
			echo '<ul>';

			if( $user_count > 20 )
			{ // Display an input box to enter user login	
				echo '<li>';
				echo T_('User').': <input type="text" class="form_text_input autocomplete_login" value="'.$author_login.'" name="'.$pp.'author_login" id="'.$pp.'author_login" />';
				echo '</li>';
			}
			else
			{ // Display a list of users
				echo '<li><input type="radio" name="'.$pp.'author" value="0" class="radio"';
				if( empty( $author ) ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( $pp.'author', $pp.'author=0' ).'">'.T_('Any').'</a></li>';

				foreach( $UserCache->cache as $loop_User )
				{
					echo '<li><input type="radio" name="'.$pp.'author" value="'.$loop_User->ID.'" class="radio"';
					if( $loop_User->ID == $author ) echo ' checked="checked"';
					echo ' /> <a href="'.regenerate_url( $pp.'author', $pp.'author='.$loop_User->ID ).'" rel="bubbletip_user_'.$loop_User->ID.'">';
					echo $loop_User->get_colored_login( array( 'login_text' => 'name' ) );
					echo '</a></li>';
				}
			}
			echo '</ul>';
		}
		echo '</fieldset>';


		/*
		 * Statuses
		 * TODO: allow multiple selection
		 */
		$ItemStatusCache = & get_ItemStatusCache();
		$ItemStatusCache->load_all(); // TODO: load for current blog only
		if( count($ItemStatusCache->cache) )
		{	// We have satuses:
			echo '<fieldset>';
			echo '<legend>'.T_('Statuses').'</legend>';
			echo '<ul>';

			echo '<li><input type="radio" name="'.$pp.'status" value="-" class="radio"';
			if( '-' == $status ) echo ' checked="checked"';
			echo ' /> <a href="'.regenerate_url( $pp.'status', $pp.'status=-' ).'">'.T_('Without status').'</a></li>';

			foreach( $ItemStatusCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="'.$pp.'status" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $status ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( $pp.'status', $pp.'status='.$loop_Obj->ID ).'">';
				$loop_Obj->disp('name');
				echo '</a></li>';
			}
			echo '</ul>';
			echo '</fieldset>';
		}

		// --------------------------------- START OF CATEGORY LIST --------------------------------
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'coll_category_list',
				// Optional display params
				'block_start' => '<fieldset class="widget_core_coll_category_list">',
				'block_end' => '</fieldset>',
				'block_title_start' => '<legend>',
				'block_title_end' => '</legend>',
				'link_type' => 'context',
				'display_checkboxes' => 1,
				'show_locked' => true,
				'max_colls' => 15,
			) );
		// ---------------------------------- END OF CATEGORY LIST ---------------------------------


		// ARCHIVES:
		// Call the Archives plugin:
		$Plugins->call_by_code( 'evo_Arch', array( // Parameters follow:
				'block_start'     => '<fieldset>',
				'block_end'       => "</fieldset>\n",
				'title'           => '<legend>'.T_('Archives')."</legend>\n",
				'link_type'       => 'context', // Preserve page context
				'form'            => true,      // add form fields (radio buttons)
				'limit'           => '',        // No limit
				'more_link'       => '',        // No more link
				'itemlist_prefix' => $pp,       // Prefix of the ItemList object
			) );

		echo '<br />';
		$Form->button_input( array(
				'tag'   => 'button',
				'value' => get_icon( 'filter' ).' './* TRANS: Verb */ T_('Filter'),
				'class' => 'search btn-info',
			) );

		if( $ItemList->is_filtered() )
		{
			// TODO: style this better:
			echo '&nbsp; <a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset" class="btn btn-warning">'.get_icon( 'filter' ).' '.T_('Reset all filters!').'</a>';
		}

	$Form->end_form();

echo $template['block_end'];

?>