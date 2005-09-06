<?php
/**
 * This file implements the riight sidebar for the post browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

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

	$Form = & new Form( $pagenow, 'searchform', 'get', 'none' );

	$Form->begin_form( '' );

		$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

		echo '<h3>'.T_('Search').'</h3>';

		$Form->hidden( 'tab', $tab );
		$Form->hidden( 'blog', $blog );

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


		/*
		 * Authors:
		 */
		echo '<fieldset>';
		echo '<legend>'.T_('Authors').'</legend>';
		// Load current blog members into cache:
		$UserCache->load_blogmembers( $blog );
		if( count($UserCache->cache) )
		{
			echo '<ul>';
			foreach( $UserCache->cache as $loop_Obj )
			{
				echo '<li><input type="radio" name="author" value="'.$loop_Obj->ID.'" class="radio"';
				if( $loop_Obj->ID == $author ) echo ' checked="checked"';
				echo '> <a href="'.regenerate_url( 'author', 'author='.$loop_Obj->ID, $pagenow ).'">';
				$loop_Obj->prefered_name();
				echo '</a></li>';
			}
			echo '</ul>';
		}
		echo '</fieldset>';


		$Form->submit( array( 'submit', T_('Search'), 'search' ) );
		$Form->button( array( 'button', '', T_('Full Reset'), 'search', 'document.location.href=\''.$pagenow.'?blog='.$blog.'\';' ) );

	$Form->end_form();

echo '</div>';

/*
 * $Log$
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