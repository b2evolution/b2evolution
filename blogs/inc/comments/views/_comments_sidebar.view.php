<?php
/**
 * This file implements the right sidebar for the comment browsing screen.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
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

global $current_User;

global $CommentList;

global $show_statuses, $s, $sentence, $exact;
global $rating_toshow, $rating_turn, $rating_limit, $url_match, $author_url, $include_emptyurl;
global $tab3;

load_funcs( 'skins/_skin.funcs.php' );

$Widget = new Widget();
$template = $AdminUI->get_template( 'side_item' );

$Widget->title = T_('Filters');

echo $Widget->replace_vars( $template['block_start'] );

$Form = new Form( NULL, 'comment_filter_form', 'get', 'none' );

$Form->begin_form( '' );

	$Form->hidden_ctrl();
	$Form->hidden( 'tab3', $tab3 );
	$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

	echo '<fieldset>';
	echo '<legend>'.T_('Comments to show').'</legend>';

	?>
	<div>

	<input type="checkbox" name="show_statuses[]" value="published" id="sh_published" class="checkbox" 
		<?php if( in_array( "published", $show_statuses ) ) echo 'checked="checked" '; ?>
	/>
	<label for="sh_published"><?php echo T_('Published') ?> <span class="notes">(<?php echo T_('Public') ?>)</span></label><br />

	<input type="checkbox" name="show_statuses[]" value="draft" id="sh_draft" class="checkbox" 
		<?php if( in_array( "draft", $show_statuses ) ) echo 'checked="checked" '; ?>
	/>
	<label for="sh_draft"><?php echo T_('Draft') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

	<input type="checkbox" name="show_statuses[]" value="deprecated" id="sh_deprecated" class="checkbox" 
		<?php if( in_array( "deprecated", $show_statuses ) ) echo 'checked="checked" '; ?>
	/>
	<label for="sh_deprecated"><?php echo T_('Deprecated') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

	<?php
	if( $current_User->check_perm( 'blogs', 'editall', false ) )
	{
		echo '<input type="checkbox" name="show_statuses[]" value="trash" id="sh_trash" class="checkbox" ';
		if( in_array( "trash", $show_statuses ) ) 
			echo 'checked="checked" ';
		echo '/>';
		echo '	<label for="sh_trash">'.T_('Trash').' <span class="notes">('.T_('Deleted!').')</span></label><br />';
	}
	?>

	</div>

	<?php
	echo '</fieldset>';
	
	echo '<fieldset>';
	echo '<legend>'.T_('Title / Text contains').'</legend>';

	echo $Form->inputstart;
	?>
	<div><input type="text" name="s" size="20" value="<?php echo htmlspecialchars($s) ?>" class="SearchField" /></div>
	<?php
	echo $Form->inputend;
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
	echo '</fieldset>';

	echo '<fieldset>';
	echo '<legend>'.T_('Rating').'</legend>';

	?>
	<div class="rating">
		<input type="checkbox" name="rating_toshow[]" value="norating" id="rating_ts_norating" class="checkbox" <?php if( isset( $rating_toshow ) && in_array( "norating", $rating_toshow ) ) echo 'checked="checked" '?> />
		<label for="rating_ts_norating"><?php echo T_('No rating') ?> </label><br />
		
		<input type="checkbox" name="rating_toshow[]" value="haverating" id="rating_ts_haverating" class="checkbox" <?php if( isset( $rating_toshow ) && in_array( "haverating", $rating_toshow ) ) echo 'checked="checked" '?> />
		<label for="rating_ts_haverating"><?php echo T_('Have rating') ?> </label><br />
	</div>
	<div class="rating">
		<input type="radio" name="rating_turn" value="above" id="rating_above" class="radio" <?php if( $rating_turn=='above' ) echo 'checked="checked" '?> />
		<label for="rating_above"><?php echo T_('Above') ?></label>

		<input type="radio" name="rating_turn" value="below" id="rating_below" class="radio" <?php if( $rating_turn=='below' ) echo 'checked="checked" '?> />
		<label for="rating_below"><?php echo T_('Below') ?></label><br />

		<input type="radio" name="rating_turn" value="exact" id="rating_exact" class="radio" <?php if( $rating_turn=='exact' ) echo 'checked="checked" '?> />
		<label for="rating_norating"><?php echo T_('Exact') ?></label>
	</div>
	<div class="rating">
		<?php
		echo T_('Poor');

		for( $i=1; $i<=5; $i++ )
		{
			echo '<input type="radio" name="rating_limit" value="'.$i.'" class="radio"';
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
	echo '</fieldset>';

	echo '<fieldset>';
	echo '<legend>'.T_('Author URL').'</legend>';

	echo $Form->inputstart;
	?>
	<div><input type="text" name="author_url" size="20" value="<?php echo htmlspecialchars($author_url) ?>" class="SearchField" /></div>
	<?php
	echo $Form->inputend;
	?>
	<div>
		<input type="radio" name="url_match" value="=" id="with_url" class="radio" <?php if( $url_match=='=' ) echo 'checked="checked" '?> />
		<label for="with_url"><?php echo T_('With this') ?></label>

		<input type="radio" name="url_match" value="!=" id="without_url" class="radio" <?php if( $url_match=='!=' ) echo 'checked="checked" '?> />
		<label for="without_url"><?php echo T_('Without this') ?></label>
	</div>
	<div>
		<input type="checkbox" name="include_emptyurl" value="true" id="without_any_url" class="checkbox" <?php if( $include_emptyurl ) echo 'checked="checked" '?> />
		<label for="without_any_url"><?php echo T_('Include comments with no url') ?> <span class="notes">(<?php echo T_('Works only when url filter is set') ?>)</span></label><br />
	</div>

	<?php
	echo '</fieldset>';

	$Form->submit( array( 'submit', T_('Search'), 'search' ) );

$Form->end_form();

echo $template['block_end'];


/*
 * $Log$
 * Revision 1.9  2011/02/20 23:37:06  fplanque
 * minor/doc
 *
 * Revision 1.8  2011/02/14 14:13:24  efy-asimo
 * Comments trash status
 *
 * Revision 1.7  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 */
?>