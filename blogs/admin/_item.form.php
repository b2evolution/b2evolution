<?php
/**
 * This file implements the Post form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author fplanque: François PLANQUE
 * @author blueyed
 * @author gorgeb
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

if( isset($Blog) )
{
?>
<script type="text/javascript" language="javascript">
	<!--
	/*
	 * open_preview()
	 * fplanque: created
	 */
	function open_preview(form)
	{
		// Stupid thing: having a field called action !
		var saved_action =  form.attributes.getNamedItem('action').value;
		form.attributes.getNamedItem('action').value = '<?php $Blog->disp( 'dynurl', 'raw' ) ?>';
		form.target = 'b2evo_preview';
		preview_window = window.open( '', 'b2evo_preview' );
		preview_window.focus();
		// submit after target window is created.
		form.submit();
		form.attributes.getNamedItem('action').value = saved_action;
		form.target = '_self';
	}
	/*
	 * edit_reload()
	 * fplanque: created
	 */
	function edit_reload( form, blog )
	{
		form.attributes.getNamedItem('action').value = '<?php echo $pagenow ?>';
		form.blog.value = blog;
		// form.action.value = 'reload';
		// form.post_title.value = 'demo';
		// alert( form.action.value + ' ' + form.post_title.value );
		form.submit();
		return false;
	}
	// End -->
</script>
<?php
}
?>
<!-- ================================ START OF EDIT FORM ================================ -->

<form name="post" id="post" action="<?php echo $form_action ?>" target="_self" method="post">

<div class="left_col">

	<input type="hidden" id="blog" name="blog" value="<?php echo $blog ?>" />
	<input type="hidden" id="action" name="action" value="<?php echo $next_action ?>" />
	<input type="hidden" name="mode" value="<?php echo $mode ?>" />
	<?php if( $action == 'edit' ) { ?>
		<input type="hidden" name="post_ID" value="<?php echo $post ?>" />
	<?php } ?>

	<!-- In case we send this to the blog for a preview : -->
	<input type="hidden" name="preview" value="1" />
	<input type="hidden" name="more" value="1" />
	<input type="hidden" name="preview_userid" value="<?php echo $user_ID ?>" />

	<fieldset>
		<legend><?php echo T_('Post contents') ?></legend>

	<span class="line">
	<label for="post_title"><strong><?php echo T_('Title') ?>:</strong></label>
	<input type="text" name="post_title" size="48" value="<?php echo format_to_output( $post_title, 'formvalue') ?>" id="post_title" tabindex="1" />
	</span>

	<span class="line">
	<label for="post_locale"><strong><?php echo T_('Language') ?>:</strong></label>
	<select name="post_locale" id="post_locale" tabindex="2"><?php locale_options( $post_locale ) ?></select>
	</span>

	<?php if( $use_post_url )
	{ ?>
		<span class="line">
		<label for="post_url"><strong><?php echo T_('Link to url') ?>:</strong></label>
		<input type="text" name="post_url" size="40" value="<?php echo format_to_output( $post_url, 'formvalue' ) ?>" id="post_url" tabindex="3" />
		</span>
		<?php
	}
	else
	{
		?>
		<input type="hidden" name="post_url" size="40" value="" id="post_url" />
		<?php
	}
	?>

	<div class="edit_toolbars">
	<?php // --------------------------- TOOLBARS ------------------------------------
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'DisplayToolbar', array( 'target_type' => 'Item' ) );
	?>
	</div>

	<?php // ---------------------------- TEXTAREA -------------------------------------
	// Note: the pixel images are here for an IE layout bug
	?>
	<div class="edit_area"><img src="img/blank.gif" width="1" height="1" alt="" /><textarea rows="16" cols="40" name="content" id="content" tabindex="4"><?php echo $content ?></textarea><img src="img/blank.gif" width="1" height="1" alt="" /></div>
	<script type="text/javascript" language="JavaScript">
		<!--
		// This is for toolbar plugins
		b2evoCanvas = document.getElementById('content');
		//-->
	</script>

	<div class="edit_actions">
	<?php // ------------------------------- ACTIONS ---------------------------------- ?>
		<input type="button" value="<?php echo T_('Preview') ?>" onclick="open_preview(this.form);"
		tabindex="9" />

	<input type="submit" value="<?php /* TRANS: the &nbsp; are just here to make the button larger. If your translation is a longer word, don't keep the &nbsp; */ echo T_('&nbsp; Save ! &nbsp;'); ?>" class="SaveButton" tabindex="10" />

	<?php
	// ---------- DELETE ----------
  if( $action == 'edit' )
	{	// Editing post
		// Display delete button if current user has the rights:
		$edited_Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', true );
	}

	if( $use_filemanager )
	{	// ------------------------------- UPLOAD ----------------------------------
		require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php' );
		$Fileman = new Filemanager( $current_User, 'files.php', 'user' );
		$Fileman->dispButtonUpload( T_('Files'), 'tabindex="12"' );
	}

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayEditorButton', array( 'target_type' => 'Item' ) );

	?>
	</div>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Advanced properties') ?></legend>

		<?php
		if( $current_User->check_perm( 'edit_timestamp' ) )
		{	// ------------------------------------ TIME STAMP -------------------------------------
			?>
			<div>
			<input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp"
				tabindex="13" />
			<label for="timestamp"><strong><?php echo T_('Edit timestamp') ?></strong>:</label>
			<span class="nobr">
			<input type="text" name="jj" value="<?php echo $jj ?>" size="2" maxlength="2" tabindex="14" />
			<select name="mm" tabindex="15">
			<?php
			for ($i = 1; $i < 13; $i = $i + 1)
			{
				echo "\t\t\t<option value=\"$i\"";
				if ($i == $mm)
				echo ' selected="selected"';
				if ($i < 10) {
					$ii = '0'.$i;
				} else {
					$ii = "$i";
				}
				echo ">";
				if( $mode == 'sidebar' )
					echo T_($month_abbrev[$ii]);
				else
					echo T_($month[$ii]);
				echo "</option>\n";
			}
			?>
		</select>
		<input type="text" name="aa" value="<?php echo $aa ?>" size="4" maxlength="5" tabindex="16" />
		</span>
		<span class="nobr">@
		<input type="text" name="hh" value="<?php echo $hh ?>" size="2" maxlength="2" tabindex="17" />:<input type="text" name="mn" value="<?php echo $mn ?>" size="2" maxlength="2" tabindex="18" />:<input type="text" name="ss" value="<?php echo $ss ?>" size="2" maxlength="2" tabindex="19" />
		</span></div>
		<?php
		}
		?>
		<div>
			<span class="line">
			<label for="post_urltitle"><strong><?php echo T_('URL Title') ?>:</strong></label>
			<input type="text" name="post_urltitle" id="post_urltitle" value="<?php echo format_to_output( $post_urltitle, 'formvalue' ); ?>" size="40" maxlength="50" tabindex="20" />
			<span class="notes"><?php echo T_('(to be used in permalinks)') ?></span>
			</span>
		</div>

	</fieldset>

	<?php
	if( isset( $Blog ) && ((get_bloginfo('allowpingbacks') || get_bloginfo('allowtrackbacks'))) )
	{
		?>
		<fieldset>
		<legend><?php echo T_('Additional actions') ?></legend>
		<?php
		if( get_bloginfo('allowpingbacks') )
		{ // --------------------------- PINGBACK --------------------------------------
		?>
		<div>
			<input type="checkbox" class="checkbox" name="post_pingback" value="1" id="post_pingback"
				<?php	if ($post_pingback) { echo ' checked="checked"'; } ?> />
			<label for="post_pingback"><strong><?php echo T_('Pingback') ?></strong> <span class="notes"><?php echo T_('(Send a pingback to all URLs in this post)') ?></span></label>
		</div>
		<?php
		}

		if( get_bloginfo('allowtrackbacks') )
		{	// --------------------------- TRACKBACK --------------------------------------
		?>
		<div>
			<label for="trackback_url"><strong><?php echo T_('Trackback URLs') ?>:</strong> <span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br /><input type="text" name="trackback_url" class="large" id="trackback_url" value="<?php echo format_to_output( $post_trackbacks, 'formvalue' ); ?>" />
		</div>
		<?php
		}
		?>
		</fieldset>
	<?php
	}
	?>

</div>

<div class="right_col">

	<fieldset>
		<legend><?php echo T_('Status') ?></legend>

		<?php
		if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will be publicly published') ?>"><input type="radio" name="post_status" value="published" class="checkbox" <?php if( $post_status == 'published' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Published (Public)') ?></label><br />
		<?php
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will be published but visible only by logged-in blog members') ?>"><input type="radio" name="post_status" value="protected" class="checkbox" <?php if( $post_status == 'protected' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Protected (Members only)') ?></label><br />
		<?php
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'private', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will be published but visible only by yourself') ?>"><input type="radio" name="post_status" value="private" class="checkbox" <?php if( $post_status == 'private' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Private (You only)') ?></label><br />
		<?php
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will appear only in the backoffice') ?>"><input type="radio" name="post_status" value="draft" class="checkbox" <?php if( $post_status == 'draft' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Draft (Not published!)') ?></label><br />
		<?php
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'deprecated', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will appear only in the backoffice') ?>"><input type="radio" name="post_status" value="deprecated" class="checkbox" <?php if( $post_status == 'deprecated' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Deprecated (Not published!)') ?></label><br />
		<?php
		}
		?>

	</fieldset>



	<fieldset class="extracats">
		<legend><?php echo T_('Categories') ?></legend>

		<div class="extracats">

		<p class="extracatnote"><?php echo T_('Select main category in target blog and optionally check additional categories') ?>:</p>

	<?php
		// ----------------------------  CATEGORIES ------------------------------
		$default_main_cat = 0;

		// ----------------- START RECURSIVE CAT LIST ----------------
		cat_query( false );	// make sure the caches are loaded
		/**
		 * callback to start sublist
		 */
		function cat_select_before_first( $parent_cat_ID, $level )
		{	// callback to start sublist
			echo "\n<ul>\n";
		}

		/**
		 * callback to display sublist element
		 */
		function cat_select_before_each( $cat_ID, $level )
		{	// callback to display sublist element
			global $current_blog_ID, $blog, $cat, $postdata, $post_extracats, $default_main_cat, $action, $tabindex, $allow_cross_posting;
			$this_cat = get_the_category_by_ID( $cat_ID );
			echo '<li>';

			if( $allow_cross_posting )
			{ // We allow cross posting, display checkbox:
				echo'<input type="checkbox" name="post_extracats[]" class="checkbox" title="', T_('Select as an additionnal category') , '" value="',$cat_ID,'"';
				if (($cat_ID == $postdata["Category"]) or (in_array( $cat_ID, $post_extracats )))
					echo ' checked="checked"';
				echo ' />';
			}

			// Radio for main cat:
			if( ($current_blog_ID == $blog) || ($allow_cross_posting > 2) )
			{ // This is current blog or we allow moving posts accross blogs
				if( ($default_main_cat == 0) && ($action == 'post') && ($current_blog_ID == $blog) )
				{	// Assign default cat for new post
					$default_main_cat = $cat_ID;
				}
				echo ' <input type="radio" name="post_category" class="checkbox" title="', T_('Select as MAIN category'), '" value="',$cat_ID,'"';
				if( ($cat_ID == $postdata["Category"]) || ($cat_ID == $default_main_cat))
					echo ' checked="checked"';
				echo ' />';
			}
			echo ' '.$this_cat['cat_name'];
		}
		/**
		 * callback after each sublist element
		 */
		function cat_select_after_each( $cat_ID, $level )
		{	// callback after each sublist element
			echo "</li>\n";
		}
		/**
		 * callback to end sublist
		 */
		function cat_select_after_last( $parent_cat_ID, $level )
		{	// callback to end sublist
			echo "</ul>\n";
		}

		if( $allow_cross_posting >= 2 )
		{	// If BLOG cross posting enabled, go through all blogs with cats:
			foreach( $cache_blogs as $i_blog )
			{ // run recursively through the cats
				$current_blog_ID = $i_blog->blog_ID;
				if( ! blog_has_cats( $current_blog_ID ) ) continue;
				if( ! $current_User->check_perm( 'blog_post_statuses', 'any', false, $current_blog_ID ) ) continue;
				echo "<h4>".$i_blog->blog_name."</h4>\n";
				cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
											'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
			}

      if( $allow_cross_posting >= 3 )
      {
        echo '<p class="extracatnote">'.T_('Note: Moving posts across blogs is enabled. Use with caution.').'</p> ';
      }
      echo '<p class="extracatnote">'.T_('Note: Cross posting among multiple blogs is enabled.').'</p>';
		}
		else
		{	// BLOG Cross posting is disabled. Current blog only:
			$current_blog_ID = $blog;
			cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
										'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
			?>
			<p class="extracatnote"><?php
			if( $allow_cross_posting )
				echo T_('Note: Cross posting among multiple blogs is currently disabled.');
			else
				echo T_('Note: Cross posting among multiple categories is currently disabled.');
			?></p>
			<?php
		}
		// ----------------- END RECURSIVE CAT LIST ----------------
		?>
		</div>
	</fieldset>
	<?php
		if( isset($Blog) && ($Blog->allowcomments == 'post_by_post') ) 
		{
	?>
	<fieldset>
		<legend><?php echo T_('Comments') ?></legend>

		<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comments" value="open" class="checkbox" <?php if( $post_comments == 'open' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Open') ?></label><br />

		<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comments" value="closed" class="checkbox" <?php if( $post_comments == 'closed' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Closed') ?></label><br />

		<label title="<?php echo T_('Visitors cannot see nor leave comments on this post.') ?>"><input type="radio" name="post_comments" value="disabled" class="checkbox" <?php if( $post_comments == 'disabled' ) echo 'checked="checked"'; ?> />
		<?php echo T_('Disabled') ?></label><br />

	</fieldset>
	<?php
		}
	?>
	<fieldset>
		<legend><?php echo T_('Renderers') ?></legend>
		<?php
		$Plugins->restart(); // make sure iterator is at start position
		$atLeastOneRenderer = false;
		while( $loop_RendererPlugin = $Plugins->get_next() )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code;
			if( $loop_RendererPlugin->apply_when == 'stealth'
				|| $loop_RendererPlugin->apply_when == 'never' )
			{ // This is not an option.
				continue;
			}
			$atLeastOneRenderer = true;
			?>
			<div>
				<input type="checkbox" class="checkbox" name="renderers[]"
					value="<?php $loop_RendererPlugin->code() ?>" id="<?php $loop_RendererPlugin->code() ?>"
					<?php
					switch( $loop_RendererPlugin->apply_when )
					{
						case 'always':
							// echo 'FORCED';
							echo ' checked="checked"';
							echo ' disabled="disabled"';
							break;

						case 'opt-out':
							if( in_array( $loop_RendererPlugin->code, $renderers ) // Option is activated
								|| in_array( 'default', $renderers ) ) // OR we're asking for default renderer set
							{
								// echo 'OPT';
								echo ' checked="checked"';
							}
							// else echo 'NO';
							break;

						case 'opt-in':
							if( in_array( $loop_RendererPlugin->code, $renderers ) ) // Option is activated
							{
								// echo 'OPT';
								echo ' checked="checked"';
							}
							// else echo 'NO';
							break;

						case 'lazy':
							// cannot select
							if( in_array( $loop_RendererPlugin->code, $renderers ) ) // Option is activated
							{
								// echo 'OPT';
								echo ' checked="checked"';
							}
							echo ' disabled="disabled"';
							break;
					}
				?>
				title="<?php	$loop_RendererPlugin->short_desc(); ?>" />
			<label for="<?php $loop_RendererPlugin->code() ?>" title="<?php	$loop_RendererPlugin->short_desc(); ?>"><?php echo $loop_RendererPlugin->name(); ?></label>
		</div>
		<?php
		}
		if( !$atLeastOneRenderer )
		{
			echo T_('No renderer plugins are installed.');
		}

		?>
	</fieldset>

<?php

/* if ($action == "edit")
{
// 		<p><strong>Pings:</strong> <?php echo in_array( 'pingsdone', $postdata["Flags"] ) ? 'Done':'Not done yet';
}*/

?>

</div>

<div class="clear"></div>

</form>
<!-- ================================== END OF EDIT FORM ================================== -->

<?php
/*
 * $Log$
 * Revision 1.2  2004/12/15 20:50:31  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.1  2004/12/14 20:27:11  fplanque
 * splited post/comment edit forms
 *
 * Revision : 1.64
	Date : 2004/10/6 9:36:55
	Author : 'gorgeb'
	State : 'Exp'
	Lines : +7 -2
	Description :
	Added allowcomments, a per blog setting taking three values : always, post_by_post, never.
 */
?>