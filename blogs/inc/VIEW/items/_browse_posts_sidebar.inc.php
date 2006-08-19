<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
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
/**
 * @var DataObjectCache
 */
global $ItemStatusCache;

global $tab, $show_past, $show_future, $show_status, $s, $sentence, $exact, $author, $assgn, $status;


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

	if( $Blog->get( 'notes' ) )
	{
		echo '<h3>'.T_('Notes').'</h3>';
		$Blog->disp( 'notes', 'htmlbody' );
	}

echo '</div>';

echo '<div class="browse_side_item">';

	$Form = & new Form( NULL, 'resetform', 'get', 'none' );

	$Form->begin_form( '' );

		$Form->hidden_ctrl();
		$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

		echo '<h3>'.T_('Filters').'</h3>';

		// TODO: style this better...
		echo '<p><a href="?ctrl=browse&amp;blog='.$Blog->ID.'&amp;filter=reset">'.T_('Reset all filters!').'</a></p>';

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

		<input type="checkbox" name="show_status[]" value="published" id="sh_published" class="checkbox" <?php if( in_array( "published", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_published"><?php echo T_('Published') ?> <span class="notes">(<?php echo T_('Public') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="protected" id="sh_protected" class="checkbox" <?php if( in_array( "protected", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_protected"><?php echo T_('Protected') ?> <span class="notes">(<?php echo T_('Members only') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="private" id="sh_private" class="checkbox" <?php if( in_array( "private", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_private"><?php echo T_('Private') ?> <span class="notes">(<?php echo T_('You only') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="draft" id="sh_draft" class="checkbox" <?php if( in_array( "draft", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_draft"><?php echo T_('Draft') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

		<input type="checkbox" name="show_status[]" value="deprecated" id="sh_deprecated" class="checkbox" <?php if( in_array( "deprecated", $show_status ) ) echo 'checked="checked" '?> />
		<label for="sh_deprecated"><?php echo T_('Deprecated') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

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
		<span class="line">
			<input type="radio" name="sentence" value="AND" id="sentAND" class="radio" <?php if( $sentence=='AND' ) echo 'checked="checked" '?> />
			<label for="sentAND"><?php echo T_('AND') ?></label>
		</span>
		<span class="line">
			<input type="radio" name="sentence" value="OR" id="sentOR" class="radio" <?php if( $sentence=='OR' ) echo 'checked="checked" '?> />
			<label for="sentOR"><?php echo T_('OR') ?></label>
		</span>
		<span class="line">
			<input type="radio" name="sentence" value="sentence" id="sentence" class="radio" <?php if( $sentence=='sentence' ) echo 'checked="checked" '?> />
			<label for="sentence"><?php echo T_('Entire phrase') ?></label>
		</span>
		<span class="line">
			<input type="checkbox" name="exact" value="1" id="exact" class="checkbox" <?php if( $exact ) echo 'checked="checked" '?> />
			<label for="exact"><?php echo T_('Exact match') ?></label>
		</span>

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



		// CATEGORIES:
		// Call the Categories plugin:
		$Plugins->call_by_code( 'evo_Cats', array( // Parameters follow:
				'block_start'=>'<fieldset>',
				'block_end'=>"</fieldset>\n",
				'title'=>'<legend>'.T_('Categories')."</legend>\n",
				'collist_start'=>'<ul>',
				'collist_end'=>"</ul>\n",
				'coll_start'=>'<li><strong>',
				'coll_end'=>"</strong></li>\n",
				'link_type'=>'context', 							// Preserve page context
				'form'=>true,                         // add form fields (radio buttons)
			)	);


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
		echo '<a href="?ctrl=browse&amp;blog='.$Blog->ID.'&amp;filter=reset" class="ActionButton">'.T_('Reset all filters!').'</a>';

	$Form->end_form();

echo '</div>';

/*
 * $Log$
 * Revision 1.5  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.4  2006/07/04 17:32:30  fplanque
 * no message
 *
 * Revision 1.3  2006/06/13 21:49:15  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.2.2.1  2006/06/13 18:27:51  fplanque
 * fixes
 *
 * Revision 1.2  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:12:18  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.24  2006/01/10 20:55:40  fplanque
 * bugfix
 *
 * Revision 1.23  2006/01/04 20:34:51  fplanque
 * allow filtering on extra statuses
 *
 * Revision 1.22  2006/01/04 19:07:48  fplanque
 * allow filtering on assignees
 *
 * Revision 1.21  2006/01/04 15:02:10  fplanque
 * better filtering design
 *
 * Revision 1.20  2005/12/22 13:41:00  fplanque
 * Added clean filter_reset feature
 *
 * Revision 1.19  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.18  2005/12/05 18:17:19  fplanque
 * Added new browsing features for the Tracker Use Case.
 *
 * Revision 1.17  2005/11/23 02:22:41  blueyed
 * Closing input (valid xhtml)
 *
 * Revision 1.16  2005/09/29 15:07:29  fplanque
 * spelling
 *
 * Revision 1.15  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.14  2005/08/31 19:06:41  fplanque
 * minor
 *
 * Revision 1.13  2005/08/26 17:52:02  fplanque
 * abstraction
 *
 * Revision 1.12  2005/08/26 16:15:08  fplanque
 * made the whole calendar contextual (wow am I happy about this functionality! :)
 *
 * Revision 1.11  2005/08/25 19:02:10  fplanque
 * categories plugin phase 2
 *
 * Revision 1.10  2005/08/25 17:45:19  fplanque
 * started categories plugin
 *
 * Revision 1.9  2005/08/24 18:43:09  fplanque
 * Removed public stats to prevent spamfests.
 * Added context browsing to Archives plugin.
 *
 * Revision 1.8  2005/08/22 18:42:25  fplanque
 * minor
 *
 * Revision 1.7  2005/08/18 17:49:51  fplanque
 * New search options
 *
 * Revision 1.6  2005/08/17 21:01:34  fplanque
 * Selection of multiple authors with (-) option.
 * Selection of multiple categories with (-) and (*) options.
 *
 * Revision 1.5  2005/08/17 18:23:47  fplanque
 * minor changes
 *
 * Revision 1.4  2005/08/03 21:05:01  fplanque
 * cosmetic cleanup
 *
 * Revision 1.3  2005/08/02 18:15:59  fplanque
 * cosmetic enhancements
 *
 * Revision 1.2  2005/05/09 19:06:53  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.1  2005/03/14 19:54:42  fplanque
 * no message
 *
 */
?>