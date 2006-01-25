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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

param( 'layout', 'string', 'default' );  // table layout mode

/**
 * Display user list with permissions
 *
 * Displays table lines <tr>...</tr>
 *
 * @param string layout to use (currently 'wide' or 'default')
 * @param string the DB query to get the users
 * @return array the displayed user IDs
 */
function list_users( $layout, $query )
{
	global $DB;

	$displayed = array();


	foreach( $DB->get_results( $query, ARRAY_A ) as $lKey => $lrow )
	{ // Go through users:
		$displayed[] = $lrow['user_ID'];
		switch( $layout )
		{
			case 'wide':
				// Wide layout:
				$perm_post = isset($lrow['bloguser_perm_poststatuses'])
											? explode( ',', $lrow['bloguser_perm_poststatuses'] )
											: array();
				?>
				<tr<?php if( $lKey % 2 ) echo ' class="odd"'; ?>>
					<td><?php echo format_to_output( $lrow['user_login'], 'htmlbody' ); ?></td>
					<td class="center">
						<input id="checkallspan_state_<?php echo $lrow['user_ID'] ?>" type="checkbox" name="blog_ismember_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty( $lrow['bloguser_ismember'] ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to read protected posts') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_published_<?php echo $lrow['user_ID'] ?>"
							<?php if( in_array( 'published', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_protected_<?php echo $lrow['user_ID'] ?>"
							<?php if( in_array( 'protected', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to post into this blog with protected status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_private_<?php echo $lrow['user_ID'] ?>"
							<?php if( in_array( 'private', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to post into this blog with private status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_draft_<?php echo $lrow['user_ID'] ?>"
							<?php if( in_array( 'draft', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to post into this blog with draft status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_deprecated_<?php echo $lrow['user_ID'] ?>"
							<?php if( in_array( 'deprecated', $perm_post ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to post into this blog with deprecated status') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_delpost_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty($lrow['bloguser_perm_delpost']) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to delete posts in this blog') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_comments_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty( $lrow['bloguser_perm_comments'] ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to edit comments in this blog') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_cats_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty( $lrow['bloguser_perm_cats'] ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to edit categories for this blog') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_properties_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty( $lrow['bloguser_perm_properties'] ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_('Permission to edit blog properties') ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_media_upload_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty( $lrow['bloguser_perm_media_upload'] ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_("Permission to upload into blog's media folder") ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_media_browse_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty( $lrow['bloguser_perm_media_browse'] ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_("Permission to browse blog's media folder") ?>" />
					</td>
					<td class="center">
						<input type="checkbox" name="blog_perm_media_change_<?php echo $lrow['user_ID'] ?>"
							<?php if( !empty( $lrow['bloguser_perm_media_change'] ) ) { ?> checked="checked" <?php } ?>
							onclick="merge_from_wide( this, <?php echo $lrow['user_ID'] ?>);" class="checkbox"
							value="1" title="<?php echo T_("Permission to change the blog's media folder content") ?>" />
					</td>
					<td class="center">
						<a href="javascript:toggleall_wide(document.blogperm_checkchanges, <?php echo $lrow['user_ID'] ?>);merge_from_wide( document.blogperm_checkchanges, <?php echo $lrow['user_ID'] ?>); setcheckallspan(<?php echo $lrow['user_ID'] ?>);" title="<?php echo T_('(un)selects all checkboxes using Javascript') ?>">
							<span id="checkallspan_<?php echo $lrow['user_ID'] ?>"><?php echo T_('(un)check all')?></span>
						</a>
					</td>
				</tr>
				<?php
				break;


			case 'custom':
				// TODO: custom edit form.
				break;


			default:
				// Simple layout:
				?>
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
							<input type="radio" id="blog_perm_easy_<?php echo $lrow['user_ID'].'_'.$lkey ?>" name="blog_perm_easy_<?php echo $lrow['user_ID'] ?>" value="<?php echo $easy_group[0] ?>"<?php
							if( $easy_group[0] == $user_easy_group )
							{
								echo ' checked="checked"';
							}
							?> onclick="merge_from_easy( this, <?php echo $lrow['user_ID'] ?> )" class="radio" />
							<label for="blog_perm_easy_<?php echo $lrow['user_ID'].'_'.$lkey ?>"><?php echo $easy_group[1] ?></label>
							<?php
						}
						?>
					</td>
				</tr>
			<?php
		}
	}

	return $displayed;
}


$Form = & new Form( 'blogs.php', 'blogperm_checkchanges', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'perm' );
$Form->hidden( 'blog', $edited_Blog->ID );
$Form->hidden( 'layout', $layout );

$Form->begin_fieldset( T_('User permissions') );

// Display layout selector:
?>
<div style="float:right">
	<?php
	echo T_('Layout').': ';
	echo '[<a href="blogs.php?action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=default"
					onclick="switch_layout(\'default\'); return false;">Simple</a>] ';
	echo '[<a href="blogs.php?action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=wide"
					onclick="switch_layout(\'wide\'); return false;">Wide</a>] ';
	if( $debug )
	{
		echo '[<a href="blogs.php?action=edit&amp;tab=perm&amp;blog='.$edited_Blog->ID.'&amp;layout=all"
						onclick="switch_layout(\'all\'); return false;">Debug</a>] ';
	}
	?>
</div>

<?php
// Display wide layout:
?>

<div id="userlist_wide" class="clear" style="<?php
	echo 'display:'.( ($layout == 'wide' || $layout == 'all' ) ? 'block' : 'none' ) ?>">
	<table class="grouped" cellspacing="0">
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
			$members = list_users( 'wide',
								'SELECT user_ID, user_login, bloguser_perm_poststatuses, bloguser_ismember,
													bloguser_perm_comments, bloguser_perm_delpost, bloguser_perm_cats,
													bloguser_perm_properties, bloguser_perm_media_upload,
													bloguser_perm_media_browse, bloguser_perm_media_change
									 FROM T_users INNER JOIN T_coll_user_perms ON user_ID = bloguser_user_ID
									WHERE bloguser_blog_ID = '.$blog.'
									  AND bloguser_ismember <> 0
									ORDER BY user_login' );
			?>

			<tr class="group">
				<td colspan="15">
					<strong><?php echo T_('Non members') ?></strong>
				</td>
			</tr>

			<?php
			list_users( 'wide', 'SELECT user_ID, user_login
										FROM T_users'
									.( count( $members ) ? ' WHERE user_ID NOT IN ('.implode( ',', $members ) .') ' : '' )
									.' ORDER BY user_login' );
			?>
		</tbody>
	</table>
</div>

<?php
// Display simple layout:
?>
<div id="userlist_default" class="clear" style="<?php
	echo 'display:'.( ($layout == 'default' || $layout == 'all' ) ? 'block' : 'none' ) ?>">
	<table class="grouped">
		<tr class="group">
			<td colspan="2">
				<strong><?php echo T_('Members') ?></strong>
			</td>
		</tr>

		<?php

		$members = list_users( 'default',
							'SELECT user_ID, user_login, bloguser_perm_poststatuses, bloguser_ismember,
												bloguser_perm_comments, bloguser_perm_delpost, bloguser_perm_cats,
												bloguser_perm_properties, bloguser_perm_media_upload,
												bloguser_perm_media_browse, bloguser_perm_media_change
								 FROM T_users INNER JOIN T_coll_user_perms ON user_ID = bloguser_user_ID
								WHERE bloguser_blog_ID = '.$blog.'
								  AND bloguser_ismember <> 0
								ORDER BY user_login' );

		?>

		<tr class="group">
			<td colspan="2">
				<strong><?php echo T_('Non members') ?></strong>
			</td>
		</tr>

		<?php
		list_users( 'default', 'SELECT user_ID, user_login
									FROM T_users'
								.( count( $members ) ? ' WHERE user_ID NOT IN ('.implode( ',', $members ) .') ' : '' )
								.' ORDER BY user_login' );

		?>
	</table>
	<br />
</div>


<?php
$Form->end_fieldset();
// warning if a user withdraws own permission to edit the blog's properties
// TODO: simultaneously check group properties (we migth want to do this in PHP and not JS)
form_submit( ( $current_User->ID != 1 ) ); // ? 'onclick="if( document.blogperm_checkchanges.blog_perm_properties_'.$current_User->ID.'.checked == false) return( confirm(\''.TS_('Warning! You are about to remove your own permission to edit this blog!\nYou won\'t have access to its properties any longer if you do that!').'\') );"' : '' );

$Form->end_form();
?>