<?php
/**
 * This file implements the UI view (+more :/) for the blogs permission management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * @todo move user rights queries to object (fplanque)
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

param( 'layout', 'string', 'default' );  // table layout mode

/**
 *
 * @param string the DB query to get the users
 * @return array the displayed user IDs
 */
function list_users( $layout, $query )
{{{
	global $DB;

	$displayed = array();


	foreach( $DB->get_results( $query, ARRAY_A ) as $lKey => $lrow )
	{ // Go through users:
		$displayed[] = $lrow['ID'];
		switch( $layout )
		{
			case 'wide':
				$perm_post = isset($lrow['bloguser_perm_poststatuses'])
											? explode( ',', $lrow['bloguser_perm_poststatuses'] )
											: array();
				?>
				<tr<?php if( $lKey % 2 ) echo ' class="odd"'; ?>>
					<td><?php echo format_to_output( $lrow['user_login'], 'htmlbody' ); ?></td>
					<td class="center">
						<input id="checkallspan_state_<?php echo $lrow['ID'] ?>" type="checkbox" name="blog_ismember_<?php echo $lrow['ID'] ?>"
							<?php if( isset( $lrow['bloguser_ismember'] ) && $lrow['bloguser_ismember'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to read protected posts') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_published_<?php echo $lrow['ID'] ?>"
							<?php if( in_array( 'published', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_protected_<?php echo $lrow['ID'] ?>"
							<?php if( in_array( 'protected', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to post into this blog with protected status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_private_<?php echo $lrow['ID'] ?>"
							<?php if( in_array( 'private', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_draft_<?php echo $lrow['ID'] ?>"
							<?php if( in_array( 'draft', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to post into this blog with draft status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_deprecated_<?php echo $lrow['ID'] ?>"
							<?php if( in_array( 'deprecated', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to post into this blog with deprecated status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_delpost_<?php echo $lrow['ID'] ?>"
							<?php if( isset($lrow['bloguser_perm_delpost']) && $lrow['bloguser_perm_delpost'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to delete posts in this blog') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_comments_<?php echo $lrow['ID'] ?>"
							<?php if( isset( $lrow['bloguser_perm_comments'] ) && $lrow['bloguser_perm_comments'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to edit comments in this blog') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_cats_<?php echo $lrow['ID'] ?>"
							<?php if( isset( $lrow['bloguser_perm_cats'] ) && $lrow['bloguser_perm_cats'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to edit categories for this blog') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_properties_<?php echo $lrow['ID'] ?>"
							<?php if( isset( $lrow['bloguser_perm_properties'] ) && $lrow['bloguser_perm_properties'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_('Permission to edit blog properties') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_media_upload_<?php echo $lrow['ID'] ?>"
							<?php if( isset( $lrow['bloguser_perm_media_upload'] ) && $lrow['bloguser_perm_media_upload'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_("Permission to upload into blog's media folder") ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_media_browse_<?php echo $lrow['ID'] ?>"
							<?php if( isset( $lrow['bloguser_perm_media_browse'] ) && $lrow['bloguser_perm_media_browse'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_("Permission to browse blog's media folder") ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_media_change_<?php echo $lrow['ID'] ?>"
							<?php if( isset( $lrow['bloguser_perm_media_change'] ) && $lrow['bloguser_perm_media_change'] != 0  ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['ID'] ?>);"
							value="1" title="<?php echo T_("Permission to change the blog's media folder content") ?>" />
					</td>
					<td class="center">
						<a href="javascript:toggleall_wide(document.FormPerm, <?php echo $lrow['ID'] ?>);merge_from_wide( document.FormPerm, <?php echo $lrow['ID'] ?>); setcheckallspan(<?php echo $lrow['ID'] ?>);" title="<?php echo T_('(un)selects all checkboxes using Javascript') ?>">
							<span id="checkallspan_<?php echo $lrow['ID'] ?>"><?php echo T_('(un)check all')?></span>
						</a>
					</td>
				</tr>
				<?php
			break;


			case 'custom':
				// TODO: custom edit form.
			break;


			default: ?>
				<tr<?php if( $lKey % 2 ) echo ' class="odd"'; ?>>
					<td><?php echo format_to_output( $lrow['user_login'], 'htmlbody' ); ?></td>
					<td>
						<?php
						$user_easy_group = blogperms_get_easy( $lrow );
						foreach( array(
													array( 'nomember', T_('Not Member') ),
													array( 'member', T_('Member') ),
													array( 'editor', T_('Editor') ),
													array( 'admin',  T_('Admin') ),
													array( 'custom',  T_('Custom') )
												) as $lkey => $easy_group )
						{
							?>
							<input type="radio" id="blog_perm_easy_<?php echo $lrow['ID'].'_'.$lkey ?>" name="blog_perm_easy_<?php echo $lrow['ID'] ?>" value="<?php echo $easy_group[0] ?>"<?php
							if( $easy_group[0] == $user_easy_group )
							{
								echo ' checked="checked"';
							}
							?> onclick="merge_from_easy( this, <?php echo $lrow['ID'] ?> )" />
							<label for="blog_perm_easy_<?php echo $lrow['ID'].'_'.$lkey ?>"><?php echo $easy_group[1] ?></label>
							<?php
						}
						?>
					</td>
				</tr>
			<?php
		}
	}

	return $displayed;
}}}


$Form = & new Form( 'blogs.php', 'FormPerm' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'perm' );
$Form->hidden( 'blog', $edited_Blog->ID );
$Form->hidden( 'layout', $layout );

$Form->fieldset( T_('User permissions') );
?>
<div style="float:right">
	<?php echo T_('Layout').':';

	foreach( array( 'default' => T_('Default'), 'wide' => T_('wide'), 'all' => 'Complete (JS-debug)' ) as $lkey => $lname )
	{
		echo '<a href="?action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout='.$lkey.'"'
					.' onclick="switch_layout(\''.$lkey.'\'); return false;">['.$lname.']</a>';
	}

	?>
</div>


<div id="userlist_wide" class="clear" style="<?php
	echo 'display:'.( ($layout == 'wide' || $layout == 'all' ) ? 'block' : 'none' ) ?>">
	<table class="grouped">
		<thead>
			<tr>
				<th rowspan="2"><?php /* TRANS: table header for user list */ echo T_('Login ') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Is<br />member') ?></th>
				<th colspan="5" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Can post/edit with following statuses:') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Delete<br />posts') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Edit<br />comts') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Edit<br />cats') ?></th>
				<th rowspan="2" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Edit<br />blog') ?></th>
				<th colspan="3" class="checkright"><?php /* TRANS: SHORT table header on TWO lines */ echo T_('Media directory') ?></th>
				<th rowspan="2" class="checkright">&nbsp;</th>
			</tr>
			<tr>
				<th class="checkright"><?php echo T_('Published') ?></th>
				<th class="checkright"><?php echo T_('Protected') ?></th>
				<th class="checkright"><?php echo T_('Private') ?></th>
				<th class="checkright"><?php echo T_('Draft') ?></th>
				<th class="checkright"><?php echo T_('Deprecated') ?></th>
				<th class="checkright"><?php echo T_('Upload') ?></th>
				<th class="checkright"><?php echo T_('Read') ?></th>
				<th class="checkright"><?php echo T_('Write') ?></th>
			</tr>
		</thead>

		<tbody>
			<tr class="group">
				<td colspan="15">
					<strong><?php echo T_('Members') ?></strong>
				</td>
			</tr>

			<?php
			$members = list_users( 'wide', 'SELECT ID, user_login, bloguser_perm_poststatuses, bloguser_ismember,
													bloguser_perm_comments, bloguser_perm_delpost, bloguser_perm_cats,
													bloguser_perm_properties, bloguser_perm_media_upload,
													bloguser_perm_media_browse, bloguser_perm_media_change
									FROM T_users INNER JOIN T_blogusers
													ON ID = bloguser_user_ID
									WHERE bloguser_blog_ID = '.$blog.'
									ORDER BY user_login' );
			?>

			<tr class="group">
				<td colspan="15">
					<strong><?php echo T_('Non members') ?></strong>
				</td>
			</tr>

			<?php
			list_users( 'wide', 'SELECT ID, user_login
										FROM T_users'
									.( count( $members ) ? ' WHERE ID NOT IN ('.implode( ',', $members ) .') ' : '' )
									.' ORDER BY user_login' );
			?>
		</tbody>
	</table>
</div>


<div id="userlist_default" class="clear" style="<?php
	echo 'display:'.( ($layout == 'default' || $layout == 'all' ) ? 'block' : 'none' ) ?>">
	<table class="grouped">
		<tr class="group">
			<td colspan="2">
				<strong><?php echo T_('Members') ?></strong>
			</td>
		</tr>

		<?php

		$members = list_users( 'default', 'SELECT ID, user_login, bloguser_perm_poststatuses, bloguser_ismember,
												bloguser_perm_comments, bloguser_perm_delpost, bloguser_perm_cats,
												bloguser_perm_properties, bloguser_perm_media_upload,
												bloguser_perm_media_browse, bloguser_perm_media_change
								FROM T_users INNER JOIN T_blogusers
												ON ID = bloguser_user_ID
								WHERE bloguser_blog_ID = '.$blog.'
								ORDER BY user_login' );

		?>

		<tr class="group">
			<td colspan="2">
				<strong><?php echo T_('Non members') ?></strong>
			</td>
		</tr>

		<?php
		list_users( 'default', 'SELECT ID, user_login
									FROM T_users'
								.( count( $members ) ? ' WHERE ID NOT IN ('.implode( ',', $members ) .') ' : '' )
								.' ORDER BY user_login' );

		?>
	</table>
	<br />
</div>


<?php
$Form->fieldset_end();
// warning if a user withdraws own permission to edit the blog's properties
form_submit( ( $current_User->ID != 1 ) ? 'onclick="if( document.FormPerm.blog_perm_properties_'.$current_User->ID.'.checked == false) return( confirm(\''. /* TRANS: Warning this is a javascript string */ T_('Warning! You are about to remove your own permission to edit this blog!\nYou won\\\'t have access to its properties any longer if you do that!').'\') );"' : '' )
?>


</form>
