<?php
/**
 * Displays blog properties form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<form action="b2blogs.php" class="fform" method="post" name="FormPerm">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="perm" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<fieldset>
		<legend><?php echo T_('User permissions') ?></legend>
		<table class="grouped" cellspacing="0">
			<tr>
				<th rowspan="2"><?php /* TRANS: table header for user list */ echo T_('Login ') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Is<br />member') ?></th>
				<th colspan="5" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Can post/edit with following statuses:') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Delete<br />posts') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Edit<br />comts') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Edit<br />cats') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Edit<br />blog') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Upload') ?></th>
				<th rowspan="2" class="checkright">&nbsp;</th>
			</tr>
			<tr>
				<th class="checkright"><?php echo T_('Published') ?></th>
				<th class="checkright"><?php echo T_('Protected') ?></th>
				<th class="checkright"><?php echo T_('Private') ?></th>
				<th class="checkright"><?php echo T_('Draft') ?></th>
				<th class="checkright"><?php echo T_('Deprecated') ?></th>
			</tr>
			<tr class="group">
				<td colspan="13">
					<strong><?php echo T_('Members') ?></strong>
				</td>
			</tr>
			<?php
				$query = "SELECT ID, user_login, bloguser_perm_poststatuses, bloguser_ismember,
													bloguser_perm_comments, bloguser_perm_delpost, bloguser_perm_cats,
													bloguser_perm_properties, bloguser_perm_upload
									FROM $tableusers INNER JOIN $tableblogusers
													ON ID = bloguser_user_ID
									WHERE bloguser_blog_ID = $blog
									ORDER BY user_login";
				$rows = $DB->get_results( $query, ARRAY_A );
				$members = array();
				$i = 0;  // incemental counter (for "check all" span IDs)
				if( count($rows) ) foreach( $rows as $loop_row )
				{	// Go through users:
					$members[] = $loop_row['ID'];
					$perm_post = explode( ',', $loop_row['bloguser_perm_poststatuses'] );
					?>
					<tr <?php if( $i%2 == 1) echo 'class="odd"'; ?>>
						<td><?php echo format_to_output( $loop_row['user_login'], 'htmlbody' ); ?></td>
						<td class="center">
							<input id="checkallspan_state_<?php echo $i ?>" type="checkbox" name="blog_ismember_<?php echo $loop_row['ID'] ?>"
								checked="checked" onclick="setcheckallspan(<?php echo $i ?>, this.checked)"
								value="1" title="<?php echo T_('Permission to read protected posts') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_published_<?php echo $loop_row['ID'] ?>"
								<?php if( in_array( 'published', $perm_post ) ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_protected_<?php echo $loop_row['ID'] ?>"
								<?php if( in_array( 'protected', $perm_post ) ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to post into this blog with protected status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_private_<?php echo $loop_row['ID'] ?>"
								<?php if( in_array( 'private', $perm_post ) ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_draft_<?php echo $loop_row['ID'] ?>"
								<?php if( in_array( 'draft', $perm_post ) ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to post into this blog with draft status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_deprecated_<?php echo $loop_row['ID'] ?>"
								<?php if( in_array( 'deprecated', $perm_post ) ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to post into this blog with deprecated status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_delpost_<?php echo $loop_row['ID'] ?>"
								<?php if( $loop_row['bloguser_perm_delpost'] != 0  ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to delete posts in this blog') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_comments_<?php echo $loop_row['ID'] ?>"
								<?php if( $loop_row['bloguser_perm_comments'] != 0  ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to edit comments in this blog') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_cats_<?php echo $loop_row['ID'] ?>"
								<?php if( $loop_row['bloguser_perm_cats'] != 0  ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to edit categories for this blog') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_properties_<?php echo $loop_row['ID'] ?>"
								<?php if( $loop_row['bloguser_perm_properties'] != 0  ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_('Permission to edit blog properties') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_upload_<?php echo $loop_row['ID'] ?>"
								<?php if( $loop_row['bloguser_perm_upload'] != 0  ) { ?>
								checked="checked"
								<?php } ?>
								value="1" title="<?php echo T_("Permission to upload into blog's media folder") ?>" />
						</td>
						<td class="center">
							<a href="#" onclick="toggleall(document.FormPerm, <?php echo $loop_row['ID'].', '.$i ?>);" title="<?php echo T_('(un)selects all checkboxes using Javascript') ?>">
								<span id="checkallspan_<?php echo $i ?>"><?php echo T_('(un)check all')?></span>
							</a>
						</td>
					</tr>
					<?php
					$i++;
				}
				?>
			<tr class="group">
				<td colspan="13">
					<strong><?php echo T_('Non members') ?></strong>
				</td>
			</tr>
				<?php
				$query = "SELECT ID, user_login
									FROM $tableusers ";
				if( count( $members ) )
				{
					$query .= "WHERE ID NOT IN (".implode( ',', $members ) .") ";
				}
				$query .= "ORDER BY user_login";
				$rows = $DB->get_results( $query, ARRAY_A );
				if( count($rows) ) foreach( $rows as $loop_row )
				{	// Go through users:
					?>
					<tr <?php if( $i%2 == 1) echo 'class="odd"'; ?>>
						<td><?php echo format_to_output( $loop_row['user_login'], 'htmlbody' ); ?></td>
						<td class="center">
							<input id="checkallspan_state_<?php echo $i ?>" type="checkbox" name="blog_ismember_<?php echo $loop_row['ID'] ?>"
								onclick="setcheckallspan(<?php echo $i ?>, this.checked)"
								value="1" title="<?php echo T_('Permission to read protected posts') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_published_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_protected_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to post into this blog with protected status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_private_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_draft_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to post into this blog with draft status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_deprecated_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to post into this blog with deprecated status') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_delpost_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to delete posts in this blog') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_comments_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to edit comments in this blog') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_cats_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to edit categories for this blog') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_properties_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_('Permission to edit blog properties') ?>" />
						</td>
						<td class="center">
							<input type="checkbox" name="blog_perm_upload_<?php echo $loop_row['ID'] ?>"
								value="1" title="<?php echo T_("Permission to upload into blog's media folder") ?>" />
						</td>
						<td class="center">
							<a href="#" onclick="toggleall(document.FormPerm, <?php echo $loop_row['ID'].', '.$i ?>);">
								<span id="checkallspan_<?php echo $i ?>"><?php echo T_('(un)check all')?></span>
							</a>
						</td>
					</tr>
					<?php
					$i++;
				}
			?>
		</table>
		<br />
	</fieldset>

	<?php 
		// warning if a user withdraws own permission to edit the blog's properties
		form_submit( ( $current_User->ID != 1 ) ? 'onclick="if( document.FormPerm.blog_perm_properties_'.$current_User->ID.'.checked == false) return( confirm(\''. /* TRANS: Warning this is a javascript string */ T_('Warning! You are about to remove your own permission to edit this blog!\nYou won\\\'t have access to its properties any longer if you do that!').'\') );"' : '' )
	?>

</form>
