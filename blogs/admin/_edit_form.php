<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
?>
<script type="text/javascript" language="javascript">
	<!--
<?php 
if ($use_spellchecker) 
{ // --------------------------- SPELL CHECKER -------------------------------
	?>
	function DoSpell(formname, subject, body)
	{
		document.SPELLDATA.formname.value=formname
		document.SPELLDATA.subjectname.value=subject
		document.SPELLDATA.messagebodyname.value=body
		document.SPELLDATA.companyID.value="custom\\http://cafelog.com"
		document.SPELLDATA.language.value=1033
		document.SPELLDATA.opener.value="<?php echo $admin_url ?>/sproxy.php"
		document.SPELLDATA.formaction.value="http://www.spellchecker.com/spell/startspelling.asp "
		window.open("<?php echo $admin_url ?>/b2spell.php","Spell","toolbar=no,directories=no,location=yes,resizable=yes,width=620,height=400,top=100,left=100")
	}
<?php
}

 // --------------------------- PREVIEW -------------------------------
?>
	/*
	 * open_preview()
	 * fplanque: created
	 */
	function open_preview(form) 
	{
		// Stupid thing: having a field called action !
		var saved_action =  form.attributes.getNamedItem('action').value;
		form.attributes.getNamedItem('action').value = '<?php bloginfo('dynurl') ?>';
		form.target = 'b2evo_preview';
		form.submit();
		preview_window = window.open( '', 'b2evo_preview' );
		preview_window.focus();
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

	function launchupload() 
	{
		window.open ("b2upload.php", "b2upload", "width=380,height=360,location=0,menubar=0,resizable=1,scrollbars=yes,status=1,toolbar=0");
	}
	// End -->
</script>

<!-- ================================ START OF EDIT FORM ================================ -->

<form name="post" id="post" action="edit_actions.php" target="_self" method="post">

<?php echo $admin_2col_start;  ?>

<div class="bPost">
	
	<input type="hidden" id="blog" name="blog" value="<?php echo $blog ?>" />
	<input type="hidden" id="action" name="action" value="<?php echo $form_action ?>" />
	<input type="hidden" name="mode" value="<?php echo $mode ?>" />
	<?php if( $action == 'edit' ) { ?>
		<input type="hidden" name="post_ID" value="<?php echo $post ?>" />
	<?php } ?>

	<!-- In case we send this to the blog for a preview : -->
	<input type="hidden" name="preview" value="1" />
	<input type="hidden" name="preview_userid" value="<?php echo $user_ID ?>" />
	
	<?php 
	
	if ($action != 'editcomment') 
	{ // ------------------------------ POST HEADER -----------------------
	?>
	
		<span class="line">
		<label for="post_title"><strong><?php echo T_('Title') ?>:</strong></label>
		<input type="text" name="post_title" size="45" value="<?php echo $edited_post_title; ?>" id="post_title" tabindex="1" />
		</span>
		
		<span class="line">
		<label for="post_lang"><strong><?php echo T_('Language') ?>:</strong></label>
		<select name="post_lang" id="post_lang" tabindex="2"><?php lang_options( $post_lang ) ?></select>
		</span>
		
		<?php if( $use_post_url ) { ?>
		<span class="line">
		<label for="post_url"><strong><?php echo T_('Link to url') ?>:</strong></label>
		<input type="text" name="post_url"  size="40" value="<?php echo $post_url; ?>" id="post_url" tabindex="3" />
		</span>
		<?php } else { ?>
		<input type="hidden" name="post_url"  size="40" value="" id="post_url" />
		<?php
		}
	} 
	else 
	{ // ------------------------------ COMMENT HEADER -----------------------
		?>
		<input type="hidden" name="comment_ID" value="<?php echo $comment ?>" />
	
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
	?>
	
	<div class="center">
	<?php // --------------------------- QUICK TAGS ------------------------------------
		require( dirname(__FILE__).'/_quicktags.php'); 
	?>
	</div>
	
	<?php // ---------------------------- TEXTAREA ------------------------------------- ?>
	<div style="width:100%"><img src="img/blank.gif" width="1" height="1" alt="" border="0" /><textarea rows="18" cols="40" class="large" name="content" wrap="virtual" id="content" tabindex="4"><?php echo $content ?></textarea></div>


	<?php // --------------------------- AUTOBR -------------------------------------- ?>
	<input type="checkbox" class="checkbox" name="post_autobr" value="1" <?php
	if ($autobr) echo ' checked="checked"' ?> id="autobr" tabindex="6" /><label for="autobr"><strong><?php echo T_('Auto-BR') ?></strong> <span class="notes"><?php echo T_('(converts line-breaks into &lt;br /&gt; tags)') ?></span></label><br />
	
	<?php	
	if($use_preview && ($action != 'editcomment') )  
	{ ?>
	<input type="button" value="<?php echo T_('Preview') ?>" onClick="open_preview(this.form);" class="search" tabindex="9" />
	<?php 
	}
	
	?>
	<input type="submit" value="<?php echo ($action == 'post') ? T_('Blog this !') : T_('Edit this !'); ?>" class="search" style="font-weight: bold;" tabindex="10" /> 
	
	
	<?php if ($use_spellchecker) 
	{ // ------------------------------- SPELL CHECKER ---------------------------------- ?>
		<input type="button" value="<?php echo T_('Spellcheck') ?>" onClick="DoSpell
	('post','content','');" class="search" tabindex="11" />
	<?php } 
	
	if ( ($use_fileupload) && ($user_level >= $fileupload_minlevel) && ((ereg(" ".$user_login." ", $fileupload_allowedusers)) || (trim($fileupload_allowedusers)=="")) ) { ?>
	<input type="button" value="<?php echo T_('Upload a file/image') ?>" onClick="launchupload();" class="search" tabindex="12"  />
	<?php } ?>
	
	<fieldset>
		<legend><?php echo T_('Advanced properties') ?></legend>

		<?php
		if ($user_level > 4) 
		{	// ------------------------------------ TIME STAMP -------------------------------------
			?>
			<div><input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp" tabindex="13" <?php if( $edit_date ) echo 'checked="checked"' ?> /><label for="timestamp"><strong><?php echo T_('Edit timestamp') ?></strong>:</label>
			<span class="nobr">
			<input type="text" name="jj" value="<?php echo $jj ?>" size="2" maxlength="2" tabindex="14" />
			<select name="mm" tabindex="15">
			<?php 
			for ($i=1; $i < 13; $i=$i+1) 
			{
				echo "\t\t\t<option value=\"$i\"";
				if ($i == $mm)
				echo ' selected="selected"';
				if ($i < 10) {
					$ii = "0".$i;
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

		if( $action != "editcomment")
		{ // this is for everything but comment editing
			if( get_bloginfo('allowpingbacks') )
			{ // --------------------------- PINGBACK --------------------------------------
		?>
		<div><input type="checkbox" class="checkbox" name="post_pingback" value="1" id="post_pingback" <?php
		if ($post_pingback) echo ' checked="checked"' ?> tabindex="20" /><label for="post_pingback"><strong><?php echo T_('Pingback') ?></strong> <span class="notes"><?php echo T_('(Send a pingback to all URLs in this post)') ?></span></label></div>	
		<?php
			}
	
			if( get_bloginfo('allowtrackbacks') )
			{	// --------------------------- TRACKBACK --------------------------------------
		?>
		<div><label for="trackback"><strong><?php echo T_('Trackback URLs') ?>:</strong> <span class="notes"><?php echo T_('(Separate by space)') ?></span></label><br /><input type="text" name="trackback_url" class="large" id="trackback_url" tabindex="21" value="<?php echo format_to_edit( $post_trackbacks ); ?>" /></div>
		<?php 
			}
		}
		?>
	</fieldset>
</div>

<!-- ================================== END OF EDIT FORM =================================== -->

<?php
echo $admin_2col_nextcol;

if( $action != 'editcomment' ) 
{ // ------------------------------- POST STATUS ---------------------------------- ?>
	<div class="bSideItem2">

	<fieldset title="Status">
		<legend><?php echo T_('Status') ?></legend>

		<?php 
		if( $current_User->check_perm( 'blog_post_statuses', 'published', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will be publicly published') ?>"><input type="radio" name="post_status" value="published" class="checkbox" <?php if( $post_status == 'published' ) echo 'checked="checked"'; ?>><?php echo T_('Published (Public)') ?></label><br />
		<?php 
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'protected', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will be published but visible only by logged-in team members') ?>"><input type="radio" name="post_status" value="protected" class="checkbox" <?php if( $post_status == 'protected' ) echo 'checked="checked"'; ?>><?php echo T_('Protected (Members only)') ?></label><br />
		<?php 
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'private', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will be published but visible only by yourself') ?>"><input type="radio" name="post_status" value="private" class="checkbox" <?php if( $post_status == 'private' ) echo 'checked="checked"'; ?>><?php echo T_('Private (You only)') ?></label><br />
		<?php 
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'draft', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will appear only in the backoffice') ?>"><input type="radio" name="post_status" value="draft" class="checkbox" <?php if( $post_status == 'draft' ) echo 'checked="checked"'; ?>><?php echo T_('Draft (Not published!)') ?></label><br />
		<?php 
		}
		if( $current_User->check_perm( 'blog_post_statuses', 'deprecated', false, $blog ) )
		{
		?>
		<label title="<?php echo T_('The post will appear only in the backoffice') ?>"><input type="radio" name="post_status" value="deprecated" class="checkbox" <?php if( $post_status == 'deprecated' ) echo 'checked="checked"'; ?>><?php echo T_('Deprecated (Not published!)') ?></label><br />
		<?php 
		}
		?>

	</fieldset>
	

	
	<fieldset title="Categories" class="extracats">
		<legend><?php echo T_('Categories') ?></legend>
	
		<div class="extracats">
	
		<p class="extracatnote"><?php echo T_('Select main category in target blog and optionnaly check addtionnal categories') ?>:</p>
	
	<?php 
		// ----------------------------  CATEGORIES ------------------------------
		$default_main_cat = 0;

		// ----------------- START RECURSIVE CAT LIST ----------------
		cat_query();	// make sure the caches are loaded
		function cat_select_before_first( $parent_cat_ID, $level )
		{	// callback to start sublist
			echo "\n<ul>\n";
		}
		
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
				echo '>';
			}
			
			// Radio for main cat:
			if( $current_blog_ID == $blog )
			{
				if( ($default_main_cat == 0) && ($action == 'post') )
				{	// Assign default cat for new post
					$default_main_cat = $cat_ID;
				}
				echo '<input type="radio" name="post_category" class="checkbox" title="', T_('Select as MAIN category'), '" value="',$cat_ID,'"';
				if( ($cat_ID == $postdata["Category"]) || ($cat_ID == $default_main_cat))
					echo ' checked="checked"';
				echo '>';
			}		
			echo $this_cat['cat_name'];
		}
		function cat_select_after_each( $cat_ID, $level )
		{	// callback after each sublist element
			echo "</li>\n";
		}
		function cat_select_after_last( $parent_cat_ID, $level )
		{	// callback to end sublist
			echo "</ul>\n";
		}
	
		if( $allow_cross_posting == 2 )
		{	// If BLOG cross posting enabled, go through all blogs with cats:
			foreach( $cache_blogs as $i_blog )
			{ // run recursively through the cats
				$current_blog_ID = $i_blog->blog_ID;
				if( ! blog_has_cats( $current_blog_ID ) ) continue;
				echo "<h4>".$i_blog->blog_name."</h4>\n";
				cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first', 
											'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
			}
			?>
			<p class="notes"><?php echo T_('Note: Cross posting among multiple blogs is enabled.') ?></p>
			<?php
		}
		else
		{	// BLOG Cross posting is disabled. Current blog only:
			$current_blog_ID = $blog;
			cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first', 
										'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
			?>
			<p class="notes"><?php
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

	<fieldset title="Status">
		<legend><?php echo T_('Comments') ?></legend>

		<label title="<?php echo T_('Visitors can leave comments on this post.') ?>"><input type="radio" name="post_comments" value="open" class="checkbox" <?php if( $post_comments == 'open' ) echo 'checked="checked"'; ?>><?php echo T_('Open') ?></label><br />

		<label title="<?php echo T_('Visitors can NOT leave comments on this post.') ?>"><input type="radio" name="post_comments" value="closed" class="checkbox" <?php if( $post_comments == 'closed' ) echo 'checked="checked"'; ?>><?php echo T_('Closed') ?></label><br />

	</fieldset>

	</div>
<?php
}

if ($action == "editcomment") 
{
?>
	<div class="bSideItem">
		<h3><?php echo T_('Comment info') ?></h3>
		<p><strong><?php echo T_('Type') ?>:</strong> <?php echo $commentdata["comment_type"]; ?></p>
		<p><strong><?php echo T_('Status') ?>:</strong> <?php echo $commentdata["comment_status"]; ?></p>
		<p><strong><?php echo T_('IP address') ?>:</strong> <?php echo $commentdata["comment_author_IP"]; ?></p>
	</div>
<?php
}
/* elseif ($action == "edit") 
{
// 		<p><strong>Pings:</strong> <?php echo in_array( 'pingsdone', $postdata["Flags"] ) ? 'Done':'Not done yet'; 
}*/

echo $admin_2col_end;

?>

</form>
<!-- ================================== END OF EDIT FORM ================================== -->
