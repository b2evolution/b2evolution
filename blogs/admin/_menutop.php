<?php
/**
 * This file displays the first part of the page menu (before the page title).
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title>b2evo :: <?php echo preg_replace( '/:$/', '', $admin_pagetitle ); ?></title>
	<link href="variation.css" rel="stylesheet" type="text/css" title="Variation" />
	<link href="desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
	<link href="legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
	<?php if( is_file( dirname(__FILE__).'/custom.css' ) ) { ?>
	<link href="custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />
	<?php } ?>
	<script type="text/javascript" src="styleswitcher.js"></script>
	<?php
	if( $mode == 'sidebar' )
	{ // Include CSS overrides for sidebar: ?>
		<link href="sidebar.css" rel="stylesheet" type="text/css" />
	<?php
	}

	$Debuglog->add( 'Admin_tab='.$admin_tab );

	if( $admin_tab == 'files'
			|| ($admin_tab == 'blogs' && $tab == 'perm') )
	{{{ // -- Inject javascript ----------------
		// gets initialized in _footer.php
		?>
		<script type="text/javascript">
		<!--
			<?php
			switch( $admin_tab )
			{
				case 'blogs': // {{{
					?>
					/**
					 * Toggles all checkboxes of the wide layout
					 *
					 * @param form the form
					 * @param integer the checkbox group id
					 * @param integer force set/unset
					 */
					function toggleall_wide( the_form, id, set )
					{
						if( typeof(set) != 'undefined' )
						{
							allchecked[id] = Boolean(set);
						}
						else
						{
							allchecked[id] = allchecked[id] ? false : true;
						}

						the_form.elements['blog_ismember_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_published_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_protected_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_private_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_draft_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_deprecated_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_delpost_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_comments_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_media_upload_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_media_browse_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_media_change_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_cats_'+String(id)].checked = allchecked[id];
						the_form.elements['blog_perm_properties_'+String(id)].checked = allchecked[id];
					}


					/**
					 * Switches UI layouts by applying CSS style.display
					 */
					function switch_layout( layout )
					{
						if( layout == 'debug' )
						{
							document.getElementById( 'userlist_default' ).style.display='block';
							document.getElementById( 'userlist_wide' ).style.display='block';
						}
						else if( layout == 'wide' )
						{
							document.getElementById( 'userlist_default' ).style.display='none';
							document.getElementById( 'userlist_wide' ).style.display='block';
							document.FormPerm.layout.value = 'wide';
						}
						else
						{
							document.getElementById( 'userlist_wide' ).style.display='none';
							document.getElementById( 'userlist_default' ).style.display='block';
							document.FormPerm.layout.value = 'default';
						}
					}


					/**
					 * Updates other UI layouts when "easy UI" changes
					 */
					function merge_from_easy( source, userid )
					{
						if( source.name.indexOf( 'blog_perm_easy_' ) != 0 )
						{
							return;
						}
						if( source.value == 'custom' )
						{ // don't change anything
							return;
						}

						// reset all checkboxes
						toggleall_wide( source.form, userid, 0 );

						switch( source.value )
						{
							case 'admin':
								source.form.elements['blog_perm_cats_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_properties_'+String(userid)].checked = 1;
							case 'editor':
								source.form.elements['blog_perm_published_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_protected_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_private_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_draft_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_deprecated_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_delpost_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_comments_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_media_browse_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_media_upload_'+String(userid)].checked = 1;
								source.form.elements['blog_perm_media_change_'+String(userid)].checked = 1;
							case 'member':
								source.form.elements['blog_ismember_'+String(userid)].click();
						}
					}


					function merge_from_wide( source, userid )
					{
						if( typeof(source.checked) != 'undefined' )
						{ // source is checkbox
							f = source.form;

							if( source.id.indexOf( idprefix+'_state_'+String(userid) ) == 0 )
							{ // state-checkbox
								if( !source.checked ){ toggleall_wide( f, userid, 0 ) }
								setcheckallspan(userid, source.checked);
							}
							else if( source.checked && !f.elements[idprefix+'_state_'+String(userid)].checked )
							{
								f.elements['checkallspan_state_'+String(userid)].click();
							}
						}
						else
						{
							f = source;
						}

						var toeasy = '';
						if( !f.elements['blog_ismember_'+String(userid)].checked )
						{
							toeasy = 'nomember';
						}
						else
						{
							var perms_editor = Number(f.elements['blog_perm_deprecated_'+String(userid)].checked)
															+Number(f.elements['blog_perm_draft_'+String(userid)].checked)
															+Number(f.elements['blog_perm_private_'+String(userid)].checked)
															+Number(f.elements['blog_perm_protected_'+String(userid)].checked)
															+Number(f.elements['blog_perm_published_'+String(userid)].checked)
															+Number(f.elements['blog_perm_delpost_'+String(userid)].checked)
															+Number(f.elements['blog_perm_comments_'+String(userid)].checked)
															+Number(f.elements['blog_perm_media_upload_'+String(userid)].checked)
															+Number(f.elements['blog_perm_media_browse_'+String(userid)].checked)
															+Number(f.elements['blog_perm_media_change_'+String(userid)].checked);

							var perms_admin = Number(f.elements['blog_perm_properties_'+String(userid)].checked)
															+Number(f.elements['blog_perm_cats_'+String(userid)].checked);

							if( perms_editor == 10 )
							{ // has full editor rights
								switch( perms_admin )
								{
									case 0: toeasy = 'editor'; break;
									case 1: toeasy = 'custom'; break;
									case 2: toeasy = 'admin'; break;
								}
							}
							else if( perms_editor == 0 )
							{
								if( perms_admin )
								{
									toeasy = 'custom';
								}
								else
								{
									toeasy = 'member';
								}
							}
							else
							{
								toeasy = 'custom';
							}
						}


						for( i = 0; i < f.elements['blog_perm_easy_'+String(userid)].length; i++ )
						{
							if( f.elements['blog_perm_easy_'+String(userid)][i].value == toeasy )
							{
								f.elements['blog_perm_easy_'+String(userid)][i].checked = 1;
								break;
							};
						}
					}

					<?php // }}}
				break;


				case 'files': // {{{
				/**
				 * Toggles status of a bunch of checkboxes in a form
				 *
				 * @param string the form name
				 * @param string the checkbox(es) element(s) name
				 */ ?>
				function toggleCheckboxes(the_form, the_elements)
				{
					if( allchecked[0] ) allchecked[0] = false;
					else allchecked[0] = true;

					var elems = document.forms[the_form].elements[the_elements];
					var elems_cnt = (typeof(elems.length) != 'undefined') ? elems.length : 0;
					if (elems_cnt)
					{
						for (var i = 0; i < elems_cnt; i++)
						{
							elems[i].checked = allchecked[0];
						} // end for
					}
					else
					{
						elems.checked = allchecked[0];
					}
					setcheckallspan(0);
				}
				<?php // }}}
				break;
			}

			// --- general functions ----------------
			/**
			 * replaces the text of the [nr]th checkall-html-ID
			 *
			 * @param integer number of the checkall "set"
			 * @param boolean force setting to true/false
			 */ ?>
			function setcheckallspan( nr, set )
			{
				if( typeof(allchecked[nr]) == 'undefined' || typeof(set) != 'undefined' )
				{ // init
					allchecked[nr] = set;
				}

				if( allchecked[nr] )
				{
					var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('uncheck all') ?>');
				}
				else
				{
					var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('check all') ?>');
				}

				if( document.getElementById( idprefix+'_'+String(nr) ) )
				{
					document.getElementById( idprefix+'_'+String(nr) ).replaceChild(replace, document.getElementById( idprefix+'_'+String(nr) ).firstChild);
				}
				//else alert('no element with id '+idprefix+'_'+String(nr));
			}

			<?php
			/**
			 * inits the checkall functionality.
			 *
			 * @param string the prefix of the IDs where the '(un)check all' text should be set
			 * @param boolean initial state of the text (if there is no checkbox with ID htmlid + '_state_' + nr)
			 */ ?>
			function initcheckall( htmlid, init )
			{
				// initialize array
				allchecked = Array();
				idprefix = typeof(htmlid) == 'undefined' ? 'checkallspan' : htmlid;

				for( lform = 0; lform < document.forms.length; lform++ )
				{
					for( lelem = 0; lelem < document.forms[lform].elements.length; lelem++ )
					{
						if( document.forms[lform].elements[lelem].id.indexOf( idprefix ) == 0 )
						{
							var index = document.forms[lform].elements[lelem].name.substring( idprefix.length+2, document.forms[lform].elements[lelem].name.length );
							if( document.getElementById( idprefix+'_state_'+String(index)) )
							{
								setcheckallspan( index, document.getElementById( idprefix+'_state_'+String(index)).checked );
							}
							else
							{
								setcheckallspan( index, init );
							}
						}
					}
				}
			}
			//-->
		</script>
		<?php
	}}}

	// CALL PLUGINS NOW:
	$Plugins->trigger_event( 'AdminEndHtmlHead', array() );
	?>
</head>


<body>
<?php

param( 'blog', 'integer', 0, true ); // We need this for the urls

if( empty($mode) )
{ // We're not running in an special mode (bookmarklet, sidebar...)
?>

<div id="header">
	<a href="http://b2evolution.net/" title="<?php echo T_("visit b2evolution's website") ?>"><img id="evologo" src="../img/b2evolution_minilogo2.png" alt="b2evolution"  title="<?php echo T_("visit b2evolution's website") ?>" width="185" height="40" /></a>

	<div id="headfunctions">
		<?php echo T_('Style:') ?>
		<a href="#" onclick="setActiveStyleSheet('Variation'); return false;" title="Variation (Default)">V</a>&middot;<a href="#" onclick="setActiveStyleSheet('Desert'); return false;" title="Desert">D</a>&middot;<a href="#" onclick="setActiveStyleSheet('Legacy'); return false;" title="Legacy">L</a><?php if( is_file( dirname(__FILE__).'/custom.css' ) ) { ?>&middot;<a href="#" onclick="setActiveStyleSheet('Custom'); return false;" title="Custom">C</a><?php } ?>
		&bull;
		<a href="<?php echo $htsrv_url ?>login.php?action=logout"><?php echo T_('Logout') ?></a>
		&bull;
		<a href="<?php echo $baseurl ?>"><?php echo T_('Exit to blogs') ?> <img src="img/close.gif" width="14" height="14" class="top" alt="" title="<?php echo T_('Exit to blogs') ?>" /></a><br />
	</div>

	<?php
	if( !$obhandler_debug )
	{ // don't display changing time when we want to test obhandler
	?>
	<div id="headinfo">
		b2evo v <strong><?php echo $app_version ?></strong>
		&middot; <?php echo T_('Blog time:') ?> <strong><?php echo date_i18n( locale_timefmt(), $localtimenow ) ?></strong>
		&middot; <?php echo T_('GMT:') ?> <strong><?php echo gmdate( locale_timefmt(), $servertimenow); ?></strong>
		&middot; <?php echo T_('Logged in as:'), ' <strong>', $user_login; ?></strong>
	</div>
	<?php } ?>

	<ul class="tabs">
	<?php
		$menu = array(
			'new' => array( 'text'=>T_('Write'),
											'href'=>'b2edit.php?blog='.$blog,
											'style'=>'font-weight: bold;' ),

			'edit' => array( 'text'=>T_('Edit'),
												'href'=>'b2browse.php?blog='.$blog,
												'style'=>'font-weight: bold;' ),

			'cats' => array( 'text'=>T_('Categories'),
												'href'=>'b2categories.php?blog='.$blog ),

			'blogs' => array( 'text'=>T_('Blogs'),
												'href'=>'blogs.php' ),

			'stats' => array( 'text'=>T_('Stats'),
												'perm_name'=>'stats',
												'perm_level'=>'view',
												'href'=>'b2stats.php' ),

			'antispam' => array( 'text'=>T_('Antispam'),
														'perm_name'=>'spamblacklist',
														'perm_level'=>'view',
														'href'=>'b2antispam.php' ),

			'templates' => array( 'text'=>T_('Templates'),
														'perm_name'=>'templates',
														'perm_level'=>'any',
														'href'=>'b2template.php' ),

			'users' => array( 'text'=>T_('Users'),
												'perm_name'=>'users',
												'perm_level'=>'view',
												'text_noperm'=>T_('User Profile'),	// displayed if perm not granted
												'href'=>'b2users.php' ),

			// TODO: check filemanager permission
			'files' => array( 'text'=>T_('Files'),
												'href'=>'files.php' ),

			'options' => array( 'text'=>T_('Settings'),
													'perm_name'=>'options',
													'perm_level'=>'view',
													'href'=>'b2options.php' ),

 			'tools' => array( 'text'=>T_('Tools'),
												'href'=>'tools.php' ),

			);

		foreach( $menu as $loop_tab => $loop_details )
		{
			$perm = true; // By default
			if( (!isset($loop_details['perm_name']))
				|| ($perm = $current_User->check_perm( $loop_details['perm_name'], $loop_details['perm_level'] ) )
				|| isset($loop_details['text_noperm']) )
			{	// If no permission requested or if perm granted or if we have an alt text, display tab:

				echo (($loop_tab == $admin_tab) ? '<li class="current">' : '<li>');

				echo '<a href="'.$loop_details['href'].'"';

				if( isset($loop_details['style']) ) echo ' style="'.$loop_details['style'].'"';

				echo '>';

				echo ($perm ? $loop_details['text'] : $loop_details['text_noperm'] );

				echo '</a></li>';
			}
		}
	?>

	</ul>
</div>

<?php
} // not in special mode
?>

<div id="TitleArea">
<h1><strong>:: <?php echo $admin_pagetitle; ?></strong>