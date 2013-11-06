<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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
global ${$pp.'show_past'}, ${$pp.'show_future'}, ${$pp.'show_statuses'}, ${$pp.'s'}, ${$pp.'sentence'}, ${$pp.'exact'}, ${$pp.'author'}, ${$pp.'assgn'}, ${$pp.'status'};

$show_past = ${$pp.'show_past'};
$show_future = ${$pp.'show_future'};
$show_statuses = ${$pp.'show_statuses'};
$s = ${$pp.'s'};
$sentence = ${$pp.'sentence'};
$exact = ${$pp.'exact'};
$author = ${$pp.'author'};
$assgn = ${$pp.'assgn'};
$status = ${$pp.'status'};


load_funcs( 'skins/_skin.funcs.php' );

$Widget = new Widget();
$template = $AdminUI->get_template( 'side_item' );

$Widget->title = format_to_output( $Blog->get_maxlen_name( 22 ), 'htmlbody' );
echo $Widget->replace_vars( $template['block_start'] );

	// CALENDAR:
	// Call the Calendar plugin:
	$Plugins->call_by_code( 'evo_Calr', array(	// Params follow:
			'block_start'=>'',
			'block_end'=>'',
			'title'=>'',								// No title.
			'link_type'=>'context', 		// Preserve page context
		) );

echo $template['block_end'];

$Widget = new Widget();
$Widget->title = T_('Filters');
if( $ItemList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 4, 4 );
}
echo $Widget->replace_vars( $template['block_start'] );

	$Form = new Form( NULL, 'resetform', 'get', 'none' );

	$Form->begin_form( '' );

		$Form->hidden_ctrl();
		$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

		$Form->hidden( 'tab', $tab );
		$Form->hidden( 'blog', $Blog->ID );

		echo '<fieldset>';
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

		echo $Form->inputstart;
		?>
		<div><input type="text" name="<?php echo $pp ?>s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField" /></div>
		<?php
		echo $Form->inputend;
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


		/*
		 * Assignees:
 		 * TODO: allow multiple selection
		 */
		echo '<fieldset>';
		echo '<legend>'.T_('Assignees').'</legend>';
		// Load current blog members into cache:
		$UserCache = & get_UserCache();
		$UserCache->load_blogmembers( $Blog->ID );
		if( count($UserCache->cache) )
		{
			echo '<ul>';

			echo '<li><input type="radio" name="'.$pp.'assgn" value="-" class="radio"';
			if( '-' == $assgn ) echo ' checked="checked"';
			echo ' /> <a href="'.regenerate_url( 'assgn', 'assgn=-' ).'">'.T_('Not assigned').'</a></li>';

			foreach( $UserCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="'.$pp.'assgn" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $assgn ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'assgn', 'assgn='.$loop_Obj->ID ).'" class="'.$loop_Obj->get_gender_class().'" rel="bubbletip_user_'.$loop_Obj->ID.'">';
				$loop_Obj->preferred_name();
				echo '</a></li>';
			}
			echo '</ul>';
		}
		echo '</fieldset>';


		/*
		 * Authors:
		 * TODO: allow multiple selection
		 */
		echo '<fieldset>';
		echo '<legend>'.T_('Authors').'</legend>';
		// Load current blog members into cache:
		$UserCache->load_blogmembers( $Blog->ID );
		if( count($UserCache->cache) )
		{
			echo '<ul>';
			foreach( $UserCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="'.$pp.'author" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $author ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'author', 'author='.$loop_Obj->ID ).'" class="'.$loop_Obj->get_gender_class().'" rel="bubbletip_user_'.$loop_Obj->ID.'">';
				$loop_Obj->preferred_name();
				echo '</a></li>';
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
			echo ' /> <a href="'.regenerate_url( 'status', 'status=-' ).'">'.T_('Without status').'</a></li>';

			foreach( $ItemStatusCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="'.$pp.'status" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $status ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'status', 'status='.$loop_Obj->ID ).'">';
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
				'block_start' => '<fieldset>',
				'block_end' => '</fieldset>',
				'block_title_start' => '<legend>',
				'block_title_end' => '</legend>',
				'link_type' => 'context',
				'display_checkboxes' => 1,
				'show_locked' => true,
			) );
		// ---------------------------------- END OF CATEGORY LIST ---------------------------------


		// ARCHIVES:
		// Call the Archives plugin:
		$Plugins->call_by_code( 'evo_Arch', array( // Parameters follow:
				'block_start'=>'<fieldset>',
				'block_end'=>"</fieldset>\n",
				'title'=>'<legend>'.T_('Archives')."</legend>\n",
				'link_type'=>'context', 							// Preserve page context
				'form'=>true,                         // add form fields (radio buttons)
				'limit'=>'',                          // No limit
				'more_link'=>'',                      // No more link
			)	);


		$Form->submit( array( 'submit', T_('Search'), 'search' ) );

		if( $ItemList->is_filtered() )
		{
			// TODO: style this better:
			echo '&nbsp; <a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset">'.T_('Reset all filters!').'</a>';
		}

	$Form->end_form();

echo $template['block_end'];

/*
 * $Log$
 * Revision 1.26  2013/11/06 08:04:24  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>