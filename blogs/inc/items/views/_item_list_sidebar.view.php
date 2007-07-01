<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @var Blog
 */
global $Blog;
/**
 * @var Plugins
 */
global $Plugins;

global $tab, $show_past, $show_future, $show_statuses, $s, $sentence, $exact, $author, $assgn, $status;

load_funcs( 'skins/_skin.funcs.php' );

echo '<div class="browse_side_item">';

	echo '<h2>'.$Blog->dget( 'name', 'htmlbody' ).'</h2>';

	// CALENDAR:
	// Call the Calendar plugin:
	$Plugins->call_by_code( 'evo_Calr', array(	// Params follow:
			'block_start'=>'',
			'block_end'=>'',
			'title'=>'',								// No title.
			'link_type'=>'context', 		// Preserve page context
		) );

	/*
	// TODO: a specific field for the backoffice, at the bottom of the page
	// would be used for moderation rules.
	if( $Blog->get( 'notes' ) )
	{
		echo '<h3>'.T_('Notes').'</h3>';
		$Blog->disp( 'notes', 'htmlbody' );
	}
	*/

echo '</div>';

echo '<div class="browse_side_item">';

	$Form = & new Form( NULL, 'resetform', 'get', 'none' );

	$Form->begin_form( '' );

		$Form->hidden_ctrl();
		$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

		echo '<h3>'.T_('Filters').'</h3>';

		// TODO: style this better...
		echo '<p><a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset">'.T_('Reset all filters!').'</a></p>';

		$Form->hidden( 'tab', $tab );
		$Form->hidden( 'blog', $Blog->ID );

		$Form->begin_fieldset( T_('Posts to show') );
		?>
		<div>

		<input type="checkbox" name="show_past" value="1" id="ts_min" class="checkbox" <?php if( $show_past ) echo 'checked="checked" '?> />
		<label for="ts_min"><?php echo T_('Past') ?></label><br />

		<input type="checkbox" name="show_future" value="1" id="ts_max" class="checkbox" <?php if( $show_future ) echo 'checked="checked" '?> />
		<label for="ts_max"><?php echo T_('Future') ?></label>

		</div>

		<div>

		<input type="checkbox" name="show_statuses[]" value="published" id="sh_published" class="checkbox" <?php if( in_array( "published", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_published"><?php echo T_('Published') ?> <span class="notes">(<?php echo T_('Public') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="protected" id="sh_protected" class="checkbox" <?php if( in_array( "protected", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_protected"><?php echo T_('Protected') ?> <span class="notes">(<?php echo T_('Members only') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="private" id="sh_private" class="checkbox" <?php if( in_array( "private", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_private"><?php echo T_('Private') ?> <span class="notes">(<?php echo T_('You only') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="draft" id="sh_draft" class="checkbox" <?php if( in_array( "draft", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_draft"><?php echo T_('Draft') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="deprecated" id="sh_deprecated" class="checkbox" <?php if( in_array( "deprecated", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_deprecated"><?php echo T_('Deprecated') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

		<input type="checkbox" name="show_statuses[]" value="redirected" id="sh_redirected" class="checkbox" <?php if( in_array( "redirected", $show_statuses ) ) echo 'checked="checked" '?> />
		<label for="sh_deprecated"><?php echo T_('Redirected') ?></label><br />

	 	</div>

		<?php
		$Form->end_fieldset();


		$Form->begin_fieldset( T_('Title / Text contains'), array( 'class'=>'Text' ) );

		echo $Form->inputstart;
		?>
		<div><input type="text" name="s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField" /></div>
		<?php
		echo $Form->inputend;
		// echo T_('Words').' : ';
		?>
		<div class="tile">
			<input type="radio" name="sentence" value="AND" id="sentAND" class="radio" <?php if( $sentence=='AND' ) echo 'checked="checked" '?> />
			<label for="sentAND"><?php echo T_('AND') ?></label>
		</div>
		<div class="tile">
			<input type="radio" name="sentence" value="OR" id="sentOR" class="radio" <?php if( $sentence=='OR' ) echo 'checked="checked" '?> />
			<label for="sentOR"><?php echo T_('OR') ?></label>
		</div>
		<div class="tile">
			<input type="radio" name="sentence" value="sentence" id="sentence" class="radio" <?php if( $sentence=='sentence' ) echo 'checked="checked" '?> />
			<label for="sentence"><?php echo T_('Entire phrase') ?></label>
		</div>
		<div class="tile">
			<input type="checkbox" name="exact" value="1" id="exact" class="checkbox" <?php if( $exact ) echo 'checked="checked" '?> />
			<label for="exact"><?php echo T_('Exact match') ?></label>
		</div>

		<?php
		$Form->end_fieldset();


		/*
		 * Assignees:
 		 * TODO: allow multiple selection
		 */
		echo '<fieldset>';
		echo '<legend>'.T_('Assignees').'</legend>';
		// Load current blog members into cache:
		$UserCache = & get_Cache( 'UserCache' );
		$UserCache->load_blogmembers( $Blog->ID );
		if( count($UserCache->cache) )
		{
			echo '<ul>';

			echo '<li><input type="radio" name="assgn" value="-" class="radio"';
			if( '-' == $assgn ) echo ' checked="checked"';
			echo ' /> <a href="'.regenerate_url( 'assgn', 'assgn=-' ).'">'.T_('Not assigned').'</a></li>';

			foreach( $UserCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="assgn" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $assgn ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'assgn', 'assgn='.$loop_Obj->ID ).'">';
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
				echo '<li><input type="radio" name="author" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $author ) echo ' checked="checked"';
				echo ' /> <a href="'.regenerate_url( 'author', 'author='.$loop_Obj->ID ).'">';
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
		$ItemStatusCache = & get_Cache( 'ItemStatusCache' );
		$ItemStatusCache->load_all(); // TODO: load for current blog only
		if( count($ItemStatusCache->cache) )
		{	// We have satuses:
			echo '<fieldset>';
			echo '<legend>'.T_('Statuses').'</legend>';
			echo '<ul>';

			echo '<li><input type="radio" name="status" value="-" class="radio"';
			if( '-' == $status ) echo ' checked="checked"';
			echo ' /> <a href="'.regenerate_url( 'status', 'status=-' ).'">'.T_('Without status').'</a></li>';

			foreach( $ItemStatusCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="status" value="'.$loop_Obj->ID.'" class="radio"';
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
				'use_form' => 'embedded',
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

		// TODO: style this better:
		echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset" class="ActionButton">'.T_('Reset all filters!').'</a>';

	$Form->end_form();

echo '</div>';

/*
 * $Log$
 * Revision 1.2  2007/07/01 03:55:04  fplanque
 * category plugin replaced by widget
 *
 * Revision 1.1  2007/06/25 11:00:30  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.14  2007/06/11 01:55:02  fplanque
 * minor
 *
 * Revision 1.13  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.12  2007/03/11 23:57:06  fplanque
 * item editing: allow setting to 'redirected' status
 *
 * Revision 1.11  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.10  2006/11/19 03:50:29  fplanque
 * cleaned up CSS
 *
 * Revision 1.8  2006/11/16 23:48:56  blueyed
 * Use div.line instead of span.line as element wrapper for XHTML validity
 */
?>