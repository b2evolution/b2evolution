<?php
/**
 * This file implements theright sidebar for the comment browsing screen.
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

global $CommentList;

global $show_statuses, $s, $sentence, $exact;

load_funcs( 'skins/_skin.funcs.php' );

$Widget = new Widget();
$template = $AdminUI->get_template( 'side_item' );

$Widget->title = T_('Filters');

echo $Widget->replace_vars( $template['block_start'] );

$Form = new Form( NULL, 'comment_filter_form', 'get', 'none' );

$Form->begin_form( '' );

	$Form->hidden_ctrl();
	$Form->submit( array( 'submit', T_('Search'), 'search', '', 'float:right' ) );

	echo '<fieldset>';
	echo '<legend>'.T_('Comments to show').'</legend>';

	?>
	<div>

	<input type="checkbox" name="show_statuses[]" value="published" id="sh_published" class="checkbox" <?php if( in_array( "published", $show_statuses ) ) echo 'checked="checked" '?> />
	<label for="sh_published"><?php echo T_('Published') ?> <span class="notes">(<?php echo T_('Public') ?>)</span></label><br />
	
	<input type="checkbox" name="show_statuses[]" value="draft" id="sh_draft" class="checkbox" <?php if( in_array( "draft", $show_statuses ) ) echo 'checked="checked" '?> />
	<label for="sh_draft"><?php echo T_('Draft') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

	<input type="checkbox" name="show_statuses[]" value="deprecated" id="sh_deprecated" class="checkbox" <?php if( in_array( "deprecated", $show_statuses ) ) echo 'checked="checked" '?> />
	<label for="sh_deprecated"><?php echo T_('Deprecated') ?> <span class="notes">(<?php echo T_('Not published!') ?>)</span></label><br />

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
	echo '</fieldset>';

$Form->end_form();

echo $template['block_end'];

?>