<?php
/**
 * This file implements the UI view for editing a post/comment.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
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

<!-- ================================ START OF EDIT FORM ================================ -->

<form name="post" id="post" action="edit_actions.php" target="_self" method="post">

<div class="left_col">

	<input type="hidden" id="blog" name="blog" value="<?php echo $blog ?>" />
	<input type="hidden" id="action" name="action" value="<?php echo $form_action ?>" />
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

	<?php

	if ($action != 'editcomment')
	{ // ------------------------------ POST HEADER -----------------------
		$target_type = 'Item';
		?>

		<span class="line">
		<label for="post_title"><strong><?php echo T_('Title') ?>:</strong></label>
		<input type="text" name="post_title" size="48" value="<?php echo format_to_output( $post_title, 'htmlattr') ?>" id="post_title" tabindex="1" />
		</span>

		<span class="line">
		<label for="post_locale"><strong><?php echo T_('Language') ?>:</strong></label>
		<select name="post_locale" id="post_locale" tabindex="2"><?php locale_options( $post_locale ) ?></select>
		</span>

		<?php if( $use_post_url ) { ?>
		<span class="line">
		<label for="post_url"><strong><?php echo T_('Link to url') ?>:</strong></label>
		<input type="text" name="post_url" size="40" value="<?php echo format_to_output( $post_url, 'htmlattr' ) ?>" id="post_url" tabindex="3" />
		</span>
		<?php } else { ?>
		<input type="hidden" name="post_url" size="40" value="" id="post_url" />
		<?php
		}
	}
	else
	{	// -------------------------- COMMENT HEADER -----------------------
		$target_type = 'Comment';
		?>
		<input type="hidden" name="comment_ID" value="<?php echo $comment ?>" />

		<?php
		if( $edited_Comment->author_User === NULL )
		{ // This is not a member comment
			?>
			<span class="line">
			<label for="name"><strong><?php echo T_('Name') ?>:</strong></label><input type="text" name="newcomment_author" size="20" value="<?php echo format_to_edit($commentdata['comment_author']) ?>" id="name" tabindex="1" />
			</span>

			<span class="line">
			<label for="email"><strong><?php echo T_('Email') ?>:</strong></label><input type="text" name="newcomment_author_email" size="20" value="<?php echo format_to_edit($commentdata['comment_author_email']) ?>" id="email" tabindex="2" />
			</span>

			<span class="line">
			<label for="URL"><strong><?php echo T_('URL') ?>:</strong></label><input type="text" name="newcomment_author_url" size="20" value="<?php echo format_to_edit($commentdata['comment_author_url']) ?>" id="URL" tabindex="3" />
			</span>
		<?php
		}
	}
	?>

	<div class="edit_toolbars">
	<?php // --------------------------- TOOLBARS ------------------------------------
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'DisplayToolbar', array( 'target_type' => $target_type ) );
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
	<?php
	if($use_preview && ($action != 'editcomment') )
	{ // ------------------------------- PREVIEW ---------------------------------- ?>
		<input type="button" value="<?php echo T_('Preview') ?>" onclick="open_preview(this.form);"
		tabindex="9" />
	<?php
	}

	// ------------------------------- SAVE ---------------------------------- ?>
	<input type="submit" value="<?php /* TRANS: the &nbsp; are just here to make the button larger. If your translation is a longer word, don't keep the &nbsp; */ echo T_('&nbsp; Save ! &nbsp;'); ?>" class="SaveButton" tabindex="10" />

	<?php
	// ---------- DELETE ----------
  if( $action == 'edit' )
	{	// Editing post
		// Display delete button if current user has the rights:
		$edited_Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', true );
	}
	elseif( $action == 'editcomment' )
	{	// Editing comment
		// Display delete button if user has permission to:
		$edited_Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton', true );
	}


	// ------------------------------- UPLOAD ----------------------------------
	require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_filemanager.class.php' );
	$Fileman = new Filemanager( $current_User, 'files.php', 'user' );
	$Fileman->dispButtonUpload( '#', 'tabindex="12"' );


	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'DisplayEditorButton', array( 'target_type' => $target_type ) );

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
				tabindex="13" <?php if( $edit_date ) echo 'checked="checked"' ?> />
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

		if( $action != 'editcomment' )
		{ // this is for everything but comment editing
		?>
		<div>
			<span class="line">
			<label for="post_urltitle"><strong><?php echo T_('URL Title') ?>:</strong></label>
			<input type="text" name="post_urltitle" id="post_urltitle" value="<?php echo format_to_output( $post_urltitle, 'htmlattr' ); ?>" size="40" maxlength="50" tabindex="20" />
			<span class="notes"><?php echo T_('(to be used in permalinks)') ?></span>
			</span>
		</div>

		<?php
		}

		// --------------------------- AUTOBR -------------------------------------- 	?>
		<input type="checkbox" class="checkbox" name="post_autobr" value="1" <?php
		if( $post_autobr ) echo ' checked="checked"' ?> id="autobr" tabindex="6" /><label for="autobr">
		<strong><?php echo T_('Auto-BR') ?></strong> <span class="notes"><?php echo T_('This option is deprecated, you should avoid using it.') ?></span></label><br />

	</fieldset>

	<?php
	if( ($action != 'editcomment') && (get_bloginfo('allowpingbacks') || get_bloginfo('allowtrackbacks')) )
	{ // this is for everything but comment editing
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
			<label for="trackback_url"><strong><?php echo T_('Trackback URLs') ?>:</strong> <span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br /><input type="text" name="trackback_url" class="large" id="trackback_url" value="<?php echo format_to_output( $post_trackbacks, 'htmlattr' ); ?>" />
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

<?php

if( $action != 'editcomment' )
{ // ------------------------------- POST STATUS ---------------------------------- ?>
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
		cat_query();	// make sure the caches are loaded
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
		if( $Blog->allowcomments == 'post_by_post' )
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
		$Plugins->restart();	 // make sure iterator is at start position
		while( $loop_RendererPlugin = $Plugins->get_next() )
		{ // Go through whole list of renders
			// echo ' ',$loop_RendererPlugin->code;
			if( $loop_RendererPlugin->apply_when == 'stealth'
				|| $loop_RendererPlugin->apply_when == 'never' )
			{	// This is not an option.
				continue;
			}
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
		?>
	</fieldset>

<?php
}

if ($action == "editcomment")
{
?>
	<fieldset>
		<legend><?php echo T_('Comment info') ?></legend>
		<p><strong><?php echo T_('Author') ?>:</strong> <?php echo $edited_Comment->author() ?></p>
		<p><strong><?php echo T_('Type') ?>:</strong> <?php echo $commentdata["comment_type"]; ?></p>
		<p><strong><?php echo T_('Status') ?>:</strong> <?php echo $commentdata["comment_status"]; ?></p>
		<p><strong><?php echo T_('IP address') ?>:</strong> <?php echo $commentdata["comment_author_IP"]; ?></p>

<?php
}
/* elseif ($action == "edit")
{
// 		<p><strong>Pings:</strong> <?php echo in_array( 'pingsdone', $postdata["Flags"] ) ? 'Done':'Not done yet';
}*/

?>

</div>

<div class="clear"></div>

</form>
<!-- ================================== END OF EDIT FORM ================================== -->