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

	if( $admin_tab == 'files'	|| ($admin_tab == 'blogs' && $tab == 'perm') )
	{{{ // -- Inject javascript ----------------
		// gets initialized in _footer.php
		?>
		<script type="text/javascript">
		<!--
			<?php
			switch( $admin_tab )
			{
				case 'blogs':
				?>
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
				<?php
				break;

				case 'files':
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
				<?php
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
		b2evo v <strong><?php echo $b2_version ?></strong>
		&middot; <?php echo T_('Blog time:') ?> <strong><?php echo date_i18n( locale_timefmt(), $localtimenow ) ?></strong>
		&middot; <?php echo T_('GMT:') ?> <strong><?php echo gmdate( locale_timefmt(), $servertimenow); ?></strong>
		&middot; <?php echo T_('Logged in as:'), ' <strong>', $user_login; ?></strong>
	</div>
	<?php } ?>

	<ul class="tabs">
	<?php
		if( $admin_tab == 'new' )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2edit.php?blog=', $blog, '" style="font-weight: bold;">', T_('Write'), '</a></li>';

		if( $admin_tab == 'edit'  )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2browse.php?blog=', $blog, '" style="font-weight: bold;">', T_('Edit'), '</a></li>';

		if( $admin_tab == 'cats' )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="b2categories.php?blog=', $blog, '" >', T_('Categories'), '</a></li>';

		if( $admin_tab == 'blogs' )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="blogs.php" >', T_('Blogs'), '</a></li>';

		if( $current_User->check_perm( 'stats', 'view' ) )
		{
			if( $admin_tab == 'stats' )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2stats.php" >', T_('Stats'), '</a></li>';
		}

		if( $current_User->check_perm( 'spamblacklist', 'view' ) )
		{
			if( $admin_tab == 'antispam' )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2antispam.php" >', T_('Antispam'), '</a></li>';
		}

		if( $current_User->check_perm( 'templates', 'any' ) )
		{
			if( $admin_tab == 'templates' )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2template.php">', T_('Templates'), '</a></li>';
		}

		if( $admin_tab == 'users' )
			echo '<li class="current">';
		else
			echo '<li>';

		if( $current_User->check_perm( 'users', 'view' ) )
		{
			echo '<a href="b2users.php" >', T_('Users'), '</a></li>';
		}
		else
		{
			echo '<a href="b2users.php" >', T_('User Profile'), '</a></li>';
		}

		if( $current_User->level >= 10 ) // TODO: check filemanager permission
		{
			if( $admin_tab == 'files' )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="files.php">', T_('Files'), '</a></li>';
		}

		if( $current_User->check_perm( 'options', 'view' ) )
		{
			if( $admin_tab == 'options' )
				echo '<li class="current">';
			else
				echo '<li>';
			echo '<a href="b2options.php" >', T_('Settings'), '</a></li>';
		}

		if( $admin_tab == 'tools' )
			echo '<li class="current">';
		else
			echo '<li>';
		echo '<a href="tools.php" >', T_('Tools'), '</a></li>';

	?>

	</ul>
</div>

<?php
} // not in special mode
?>

<div id="TitleArea">
<h1><strong>:: <?php echo $admin_pagetitle; ?></strong>