<?php
switch($action) 
{
	case "post":
		/*
		 * -----------------------------------------
		 * NEW POST:
		 */
		$submitbutton_text = _('Blog this !');
		$form_action = "post";
		$form_extra = "";
		if( ! $use_pingback ) $form_pingback = '';
		if( ! $use_trackback ) $form_trackback = '';
		$colspan = 3;
		$post_date = date("Y-m-d H:i:s",(time() + ($time_difference * 3600)));
		break;



	case "edit":
		/*
		 * -----------------------------------------
		 * EDITING POST:
		 */
		$submitbutton_text = _('Edit this !');
		$form_action = "editpost";
		$form_extra = "\" />\n<input type=\"hidden\" name=\"post_ID\" value=\"$post";
		if( ! $use_pingback ) $form_pingback = '';
		if( ! $use_trackback ) $form_trackback = '';
		$colspan = 2;
		$post_date = $postdata['Date'];
		break;



	case "editcomment":
		/*
		 * -----------------------------------------
		 * EDITING COMMENT:
		 */
		$submitbutton_text = _('Edit this !');
		$form_action = "editedcomment";
		$form_extra = "\" />\n<input type=\"hidden\" name=\"comment_ID\" value=\"$comment\" />\n<input type=\"hidden\" name=\"comment_post_ID\" value=\"".$commentdata["comment_post_ID"];
		$form_pingback = '';
		$form_trackback = '';
		$colspan = 3;
		$post_date = $commentdata["comment_date"];
		break;
}

?>

<!-- ================================ START OF EDIT FORM ================================ -->

<form name="post" action="edit_actions.php" target="_self" method="post">

<?php echo $admin_2col_start; ?>

<div class="bPost">
	
	<input type="hidden" name="blog" value="<?php echo $blog ?>" />
	<input type="hidden" name="user_ID" value="<?php echo $user_ID ?>" />
	<input type="hidden" name="action" value="<?php echo $form_action.$form_extra ?>" />

	<!-- In case we send this to the blog for a preview : -->
	<input type="hidden" name="preview" value="1" />
	<input type="hidden" name="preview_userid" value="<?php echo $user_ID ?>" />
	<input type="hidden" name="preview_date" value="<?php echo $post_date; ?>" />
	
	<?php 
	
	if ($action != "editcomment") 
	{ // this is for everything but comment editing
	?>
	
	<label for="post_title"><strong><?php echo _('Title') ?>:</strong></label><input type="text" name="post_title" size="45" value="<?php echo $edited_post_title; ?>" id="post_title" tabindex="1" />
	
	<label for="post_lang"><strong><?php echo _('Language') ?>:</strong></label><select name="post_lang" id="post_lang" tabindex="2"><?php lang_options( $post_lang ) ?></select>
	<br />
	
	<label for="post_url"><strong><?php echo _('Link to url') ?>:</strong></label> <input type="text" name="post_url"  size="40" value="<?php echo $post_url; ?>" id="post_url" tabindex="3" /><br />
	
	<?php
	} 
	else 
	{ // this is for comment editing
		?>
	<label for="name"><strong><?php echo _('Name') ?>:</strong></label><input type="text" name="newcomment_author" size="20" value="<?php echo format_to_edit($commentdata["comment_author"]) ?>" id="name" tabindex="1" />
	
	<label for="email"><strong><?php echo _('Email') ?>:</strong></label><input type="text" name="newcomment_author_email" size="20" value="<?php echo format_to_edit($commentdata["comment_author_email"]) ?>" id="email" tabindex="2" />
	
	<label for="URL"><strong><?php echo _('URL') ?>:</strong></label><input type="text" name="newcomment_author_url" size="20" value="<?php echo format_to_edit($commentdata["comment_author_url"]) ?>" id="URL" tabindex="3" /><br />
	
	<?php
	}
	?>
	
	<div class="center">
	<?php if ($use_quicktags) include($b2inc.'/_quicktags.php'); ?>
	</div>
	<div style="width:100%"><img src="img/blank.gif" width="1" height="1" alt="" border="0" /><textarea rows="18" cols="40" class="large" name="content" wrap="virtual" id="content" tabindex="4"><?php echo $content ?></textarea></div>
	<input type="checkbox" class="checkbox" name="post_autobr" value="1" <?php
	if ($autobr) echo ' checked="checked"' ?> id="autobr" tabindex="6" /><label for="autobr"> <?php echo _('Auto-BR (converts line-breaks into &lt;br /&gt; tags)') ?></label><br />
	
	<?php
	if( $action != "editcomment")
	{ // this is for everything but comment editing
		if( $use_pingback )
		{
	?>
	<input type="checkbox" class="checkbox" name="post_pingback" value="1" id="pingback" tabindex="7" /><label for="pingback"> <?php echo _('Pingback the URLs in this post') ?></label><br />
	<?php
		}

		if( $use_trackback )
		{
	?>
	<label for="trackback"><?php echo _('<strong>Trackback</strong> URLs (separate multiple URLs with space)') ?>:</label><br /><input type="text" name="trackback_url" class="large" id="trackback" tabindex="8" />
	<?php 
		}
	}
	
	if($use_preview && ($action != 'editcomment') )  
	{ ?>
	<input type="button" value="Preview" onClick="open_preview(this.form);" class="search" tabindex="9" />
	<?php 
	}
	
	?>
	<input type="submit" value="<?php echo $submitbutton_text ?>" class="search" style="font-weight: bold;" tabindex="10" /> 
	
	<?php
	 if ($use_spellchecker) 
	{ // ------------------------------- SPELL CHECKER ---------------------------------- ?>
	<!--<input type = "button" value = "Spell Check" onclick="var f=document.forms[0]; doSpell( 'en', f.post_content, '<?php echo $spellchecker_url ?>/sproxy.cgi', true);" class="search" />-->
	<input type="button" value="<?php echo _('Spellcheck') ?>" onClick="DoSpell
	('post','content','');" class="search" tabindex="11" />
	<?php } ?>
	
	<?php if ( ($use_fileupload) && ($user_level >= $fileupload_minlevel) && ((ereg(" ".$user_login." ", $fileupload_allowedusers)) || (trim($fileupload_allowedusers)=="")) ) { ?>
	<input type="button" value="<?php echo _('Upload a file/image') ?>" onClick="launchupload();" class="search" tabindex="12"  />
	<?php }
	
	// if the level is 5+, allow user to edit the timestamp - not on 'new post' screen though
	// if (($user_level > 4) && ($action != "post"))
	if ($user_level > 4) 
	{
		$jj = mysql2date('d', $post_date);
		$mm = mysql2date('m', $post_date);
		$aa = mysql2date('Y', $post_date);
		$hh = mysql2date('H', $post_date);
		$mn = mysql2date('i', $post_date);
		$ss = mysql2date('s', $post_date);
		?>
		<br />
		<input type="checkbox" class="checkbox" name="edit_date" value="1" id="timestamp" tabindex="13" /><label for="timestamp"><?php echo _('Edit') ?>:</label>
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
			echo ">"._($month[$ii])."</option>\n";
		} ?>
	</select>
	<input type="text" name="aa" value="<?php echo $aa ?>" size="4" maxlength="5" tabindex="16" /> @
	<input type="text" name="hh" value="<?php echo $hh ?>" size="2" maxlength="2" tabindex="17" /> :
	<input type="text" name="mn" value="<?php echo $mn ?>" size="2" maxlength="2" tabindex="18" /> :
	<input type="text" name="ss" value="<?php echo $ss ?>" size="2" maxlength="2" tabindex="19" />
		<?php
	}
	?>
</div>

<!-- ================================== END OF EDIT FORM =================================== -->

<?php
echo $admin_2col_nextcol;

if( $action != 'editcomment' ) 
{ // ------------------------------- POST STATUS ---------------------------------- ?>
	<div class="bSideItem">

	<fieldset title="Status">
		<legend><?php echo _('Status') ?></legend>

		<label><input type="radio" name="post_status" value="draft" class="checkbox" <?php if( $post_status == 'draft' ) echo 'checked="checked"'; ?> tabindex="20"><?php echo _('Draft (Not published!)') ?></label><br />
		<label><input type="radio" name="post_status" value="published" class="checkbox" <?php if( $post_status == 'published' ) echo 'checked="checked"'; ?> tabindex="20"><?php echo _('Published') ?></label><br />

	</fieldset>
	
	<fieldset title="Categories" class="extracats">
	<legend><?php echo _('Categories') ?></legend>

	<div class="extracats">

	<span class="notes"><?php echo _('Select main category in target blog and optionnaly check addtionnal categories') ?>:</span>

<?php 
	// ----------------------------  CATEGORIES ------------------------------
	$default_main_cat = 0;
	$tabindex = 22;

	// ----------------- START RECURSIVE CAT LIST ----------------
	cat_query();	// make sure the caches are loaded
	function cat_select_before_first( $parent_cat_ID, $level )
	{	// callback to start sublist
		echo "\n<ul>\n";
	}
	
	function cat_select_before_each( $cat_ID, $level )
	{	// callback to display sublist element
		global $i_blog, $blog, $cat, $postdata, $extracats, $default_main_cat, $action, $tabindex;
		$this_cat = get_the_category_by_ID( $cat_ID );

		// Checkbox:
		echo '<li><input type="checkbox" name="extracats[]" class="checkbox" title="', _('Select as an additionnal category') , '" value="',$cat_ID,'" tabindex="', $tabindex++,'"';
		if (($cat_ID == $postdata["Category"]) or (in_array($cat_ID,$extracats)))
			echo ' checked="checked"';
		echo '>';

		// Radio for main cat:
		if( $i_blog->blog_ID == $blog )
		{
			if( ($default_main_cat == 0) && ($action == 'post') )
			{	// Assign default cat for new post
				$default_main_cat = $cat_ID;
			}
			echo '<input type="radio" name="post_category" class="checkbox" title="', _('Select as MAIN category'), '" value="',$cat_ID,'" tabindex="21"';
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
	foreach( $cache_blogs as $i_blog )
	{ // run recursively through the cats
		echo "<h4>".$i_blog->blog_name."</h4>\n";
		cat_children( $cache_categories, $i_blog->blog_ID, NULL, 'cat_select_before_first', 
									'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
	}
	// ----------------- END RECURSIVE CAT LIST ----------------
	?>
	</div>
	</fieldset>
	</div>
<?php
}

if ($action == "editcomment") 
{
?>
	<div class="bSideItem">
		<h3><?php echo _('Comment info') ?></h3>
		<p><strong><?php echo _('Type') ?>:</strong> <?php echo $commentdata["comment_type"]; ?></p>
		<p><strong><?php echo _('Status') ?>:</strong> <?php echo $commentdata["comment_status"]; ?></p>
		<p><strong><?php echo _('IP address') ?>:</strong> <?php echo $commentdata["comment_author_IP"]; ?></p>
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
