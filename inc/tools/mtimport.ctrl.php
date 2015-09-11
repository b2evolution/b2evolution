<?php
/**
 * This file implements importing of Movable Type entries into b2evolution.
 *
 * {@internal
 * TODO:
 *  - Wrap this by an abstract import class!
 *  - list of all posts, editable (overkill?)
 *  - assign comment_author_user_ID to comments if user exist?! }}
 *
 *
 * This script was developed and tested with b2evolution 0.9.0.4 (on Sourceforge CVS)
 * and Movable Type 2.64 and 2.661.
 * It should work quite alright with b2evo 0.9 and later though.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Credits go to the WordPress team (@link http://wordpress.org), where I got the basic
 * import-mt.php script with most of the core functions. Thank you!
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

/**
 * @const IMPORT_SRC_DIR directory where to be imported files get searched for.
 */
define('IMPORT_SRC_DIR', $basepath);

/**
 * Enter the relative path of the import.txt file containing the MT entries.
 * If the file is called import.txt and it is in /admin, then this line should be:
 * <code>
 * define('MTEXPORT', 'import.txt');
 * </code>
 *
 * You only need this to force a specific file instead of using a dropdown list
 * (UI selection)
 */
define('MTEXPORT', '');

/**
 * Set to true to get a lot of var_dumps, wrapped in pre tags
 */
$output_debug_dump = 0;


// ----------- don't change below if you don't know what you do ------------------------
// TODO: Make this AdminUI compliant, or better: make an MT import plugin..

load_funcs('files/model/_file.funcs.php');
load_class( 'items/model/_item.class.php', 'Item' );

if( function_exists( 'set_magic_quotes_runtime' ) )
{
	@set_magic_quotes_runtime( 0 );  // be clear on this
}
else
{
	ini_set( 'magic_quotes_runtime', 0 );
}

// TODO: $io_charset !!
$head = <<<EOB
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>b2evolution &rsaquo; Import from Movable Type</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link href="{$adminskins_url}legacy/rsc/css/variation.css" rel="stylesheet" type="text/css" title="Variation" />
	<link href="{$adminskins_url}legacy/rsc/css/desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
	<link href="{$adminskins_url}legacy/rsc/css/legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
EOB;
if( is_file( $adminskins_path.'legacy/rsc/css/custom.css' ) )
{
	$head .= '<link href="'.$adminskins_url.'legacy/rsc/css/custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />';
}
$head .= <<<EOB
<script type="text/javascript" src="{$rsc_url}js/styleswitcher.js?v=2"></script>
</head>
<body>
<div id="header">
	<div id="headinfo">
		<span style="font-size:150%; font-weight:bold">Movable Type to b2evolution importer</span>
		[<a href="{$dispatcher}?ctrl=tools">Back to b2evolution</a>]
	</div>
EOB;

$conf_file = $conf_path.'_config.php';
if( !file_exists( $conf_file ) )
{
	dieerror( "There doesn't seem to be a conf/_config.php file. You must install b2evolution before you can import any entries.", $head );
}
require( $conf_file );
if( ! isset($config_is_done) || ! $config_is_done )
{
	$error_message = '';
	require( $inc_path.'_conf_error_page.php' );

	dieerror( 'b2evolution configuration is not done yet.', $head );
}


// TODO: this should use no output buffering (probably to display page content during import, which may take long)!


// Check if user is logged in and is in group #1 (admins)
if( !is_logged_in( false ) || $current_User->grp_ID != 1 )
{	// login failed
	debug_die( 'You must login with an administrator (group #1) account.' );
}

echo $head;

param( 'exportedfile', 'string', '' );
param( 'import_mode', 'string', 'normal' );

/*** mode-tabs ***/ ?>
<ul class="tabs"><?php
	foreach( array( 'easy', 'normal', 'expert' ) as $tab )
	{
		echo ( $tab == $import_mode ) ? '<li class="current">' : '<li>';
		echo '<a href="'.$dispatcher.'?ctrl=mtimport&amp;import_mode='.$tab.( !empty($exportedfile) ? '&amp;exportedfile='.$exportedfile : '' ).'">'.ucwords($tab).'</a></li>';
	}
?></ul>
</div>

<div style="padding-top:1em;clear:both;">
<?php
	// check existence of export-file
	if( empty($exportedfile) )
	{
		if( '' != MTEXPORT && !file_exists(MTEXPORT) )
		{
			?>
			<div class="error"><p>
			The MT export file you defined in MTEXPORT at top of the script does not seem to exist.
			Please check the path you've given for MTEXPORT or choose a file below.
			</p></div>
			<?php
		}
		elseif( '' != MTEXPORT )
		{
			$exportedfile = MTEXPORT;
		}
		if( empty($exportedfile) )
		{ // no valid MTEXPORT defined
			chooseexportfile();
			echo '</div></div></body></html>';
			exit(0);
		}
	}
	else
	{
		if( !file_exists($exportedfile) )
		{
			chooseexportfile();
			dieerror("The MT export file [$exportedfile] you've chosen does not seem to exist. Please check path/permission.");
		}
	}

	// get the params
	param( 'simulate', 'integer', 0 );
	param( 'default_password', 'string', 'changeme' );
	param( 'default_password2', 'string', 'changeme' );
	param( 'post_locale', 'string', $Settings->get( 'default_locale' ) );

	if( $default_password != $default_password2 )
	{
		dieerror( 'The two passwords for new users are not identical.' );
	}

	param( 'default_userlevel', 'integer', 1 );
	if( $default_userlevel > 10 ) $default_userlevel = 10;
	param( 'default_usergroup', 'integer', $Settings->get('newusers_grp_ID') );
	param( 'default_convert_breaks', 'integer', 1 );
	param( 'convert_html_tags', 'integer', 0 );

	param( 'action', 'string', '' );


	// global arrays
	$categories_countprim = array();  // counts posts in primary categories

	// load caches
	blog_load_cache();

	$i_user = -1;

	if( empty($action) )
	{
		param( 'import_mode', 'string', 'normal', true );
		import_data_extract_authors_cats();

		?>
		<div class="panelinfo">
		<p>We are about to import <?php
			echo '['.$exportedfile.'].';
			if( '' == MTEXPORT )
			{
				?> [<a href="<?php echo $dispatcher ?>?ctrl=mtimport&amp;import_mode=<?php echo $import_mode ?>">choose another export-file</a>]<?php
			} ?></p>

		<p>This file contains <?php echo count( $posts ) ?> post(s) from <?php echo count( $authors ) ?> author(s) in <?php echo count( $categories ) ?> category(ies).</p>

		<p>We'll import into b2evolution's database &quot;<?php echo $db_config['name'] ?>&quot;.</p>
		</div>
		<div class="panelinfo">
			<p>Before importing, you should check the URLs of any &lt;img&gt; tags you may have in <?php echo $exportedfile ?>. Will these URLs still be valid after the migration? If not, we advise you do a search and replace on <?php echo $exportedfile ?> before continuing.</p>

			<p>Preferred location for inline images is the blog media folder.</p>

			<p>You can also handle the images later, but it might be easier now :)</p>
		</div>

		<div class="panelinfo">
			<p>The importer is smart enough not to import duplicates, so you can run this procedure multiple times without worrying if &#8212; for whatever reason &#8212; it doesn't finish (script timeout for example).</p>
		</div>


		<div class="panelblock">
		<form class="fform" action="<?php echo $dispatcher ?>" method="post">
			<input type="hidden" name="ctrl" value="mtimport" />
			<input type="hidden" name="action" value="import" />
		<?php
		if( !empty($exportedfile) )
		{
			?><input type="hidden" name="exportedfile" value="<?php echo format_to_output( $exportedfile, 'formvalue' ) ?>" />
			<?php
		}

		?>

		<?php
		switch( $import_mode )
		{
			case 'easy':
				?>
				<h2>Easy Import:</h2>
				<ul>
				<li>MT users with no matching b2evolution user login will be automatically created.</li>
				<li>MT categories with no matching b2evolution category name will be automatically created (in the default blog selected below.)</li>
				<?php
				if( isset($categories_countprim['[no category assigned]']) )
				{ ?>
				<li>Entries without categories (<?php echo $categories_countprim['[no category assigned]'] ?>) will be imported to category '[no category assigned]' in the default blog.</li>
				<?php
				}
				echo '</ul>';
				break;
			case 'normal':
				?>
				<h2>Normal Import:</h2>
				<ul>
				<li>MT users can be mapped to existing b2evo users, mapped to new users (provide login) or ignored.</li>
				<li>Categories can be mapped to existing b2evo categories, mapped to new categories (provide location + name) or ignored.</li>
				</ul>
				<?php
				break;
			case 'expert':
				?>
				<h2>Expert Import:</h2>
				<p>This gives you as much power as we can provide. It's like normal mode, but lets you map categories to a whole set of b2evo categories (one main category and as many extra categories as you like). You can run the importer multiple times to use different sets of b2evo categories for different sets of MT categories.</p>
				<?php
				break;

		}

		?>

		<?php if( $import_mode != 'expert' ) { ?>
		<fieldset>
			<legend>Default blog</legend>
			<fieldset>
				<div class="label"><?php echo ( $import_mode == 'easy' ) ? 'Create categories in blog' : 'Use as default blog for categories' ?>:</div>
				<div class="input">
					<select name="default_blog">
					<?php
					$BlogCache = & get_BlogCache();
					echo $BlogCache->get_option_list( 2 );  // use first non-all blog as default
					?>
					</select>
				</div>
			</fieldset>
		</fieldset>
		<?php } ?>

		<?php if( $import_mode != 'easy' )	{ ?>
		<fieldset><legend>Author mapping</legend>
			<?php
				$evousers = $DB->get_results('SELECT * FROM T_users ORDER BY user_ID');
				foreach ($authors as $author)
				{
					++$i_user;
					?>
					<fieldset>
					<div class="label"><label><?php echo $author ?></label></div>
					<div class="input">
						<select name="user_select[]">
							<option value="#CREATENEW#" selected="selected"> Create new: </option>
							<option value="#IGNORE#"> Ignore! </option>
							<?php
							foreach( $evousers as $user )
							{
								?><option value="<?php echo $user->user_ID ?>"<?php if( utf8_strtolower($author) == utf8_strtolower( $user->user_login ) ) echo ' selected="selected"';
								echo '>'.format_to_output(utf8_strtolower($user->user_login), 'formvalue').'</option>';
							}
						?></select>
						<input type="text" value="<?php echo format_to_output($author, 'formvalue') ?>" name="user_name[]" maxlength="30" class="input" />
						<span class="notes">(login for new user)</span>
					</div>
					</fieldset>
					<?php
				}
			?>
		</fieldset>
		<?php } ?>


		<fieldset><legend>New user defaults</legend>
			<?php
			form_text( 'default_password', $default_password, 20, 'Password for new users', 'this will be the password for users created during migration (default is "changeme")', 30 , '', 'password' );
			form_text( 'default_password2', $default_password, 20, 'Confirm password', 'please confirm the password', 30 , '', 'password' );
			$GroupCache = & get_GroupCache();
			form_select_object( 'default_usergroup', $Settings->get('newusers_grp_ID'), $GroupCache, T_('User group') );
			$field_note = '[0 - 10]';
			form_text( 'default_userlevel', $Settings->get('newusers_level'), 2, T_('Level'), $field_note, 2 );
			?>
		</fieldset>


		<?php if( $mode != 'easy' ){ ?>
		<fieldset><legend>Category mapping</legend>
		<?php
		$i_cat = 0;
		foreach( $categories as $cat )
		{
			?>
			<fieldset>
			<div class="label">
				<label><?php echo format_to_output($cat, 'htmlbody') ?></label>
				<br /><span class="notes" style="font-weight:normal">used <?php echo @(int)$categories_countprim[$cat] ?> times as primary category</span>
			</div>
			<div class="input"><select name="catmap_select[]">
				<?php
				if( $import_mode == 'expert' )
					echo '<option value="#DEFAULTSET#">Map to default categories set (see below)</option>';
					else echo '<option value="#DEFAULTBLOG#">Create in default blog:</option>'; ?>
				<?php cats_optionslist( $cat ) ?>
				<option value="#IGNORE#">Ignore entries with this primary cat</option>
			</select>
			<input type="text" name="catmap_name[]" value="<?php echo format_to_output( $cat, 'formvalue' ) ?>" size="30" />
			</div>
			</fieldset>
		<?php
			$i_cat++;
		} ?>
		<?php
		if( $import_mode == 'expert' )
		{
			fieldset_cats();
		}
		?>
		</fieldset>
		<?php } ?>


		<fieldset><legend>Post/Entry defaults</legend>
			<?php
			form_checkbox( 'default_convert_breaks', $default_convert_breaks, 'Convert-Breaks default', 'will be used for posts with empty CONVERT BREAKS or "__default__"' );
			form_select( 'post_locale', $Settings->get('default_locale'), 'locale_options', T_('Default locale'), 'Locale for posts.' );
			form_checkbox( 'convert_html_tags', $convert_html_tags, 'Convert ugly HTML', 'this will lowercase all html tags and add a XHTML compliant closing tag to &lt;br&gt;, &lt;img&gt;, &lt;hr&gt; (you\'ll get notes)' );

			if( $import_mode != 'easy' )
			{ // we'll use 'default' when importing
				// Autoselect a blog from where to get renderer settings
				$autoselect_blog = autoselect_blog( 'blog_post_statuses', 'edit' );
				$BlogCache = & get_BlogCache();
				$setting_Blog = & $BlogCache->get_by_ID( $autoselect_blog );
				?>
				<div class="label">Renderers:</div>
				<div class="input"><?php renderer_list( $setting_Blog ) ?></div>
			<?php } ?>
		</fieldset>

		<?php /*<fieldset style="padding-left:1ex"><legend>&lt;img&gt;-URL mapping</legend>
			<a id="imgurls"><p class="notes">This lets you map found image urls (their basename) to another basename.

			// TODO: refer to Blog media folder/url and ensure that it's enabled..

			You probably want to put the images that you had on your MT installation into b2evo's media folder.<br />
			So you would use <strong><?php echo "TODO" ?></strong> for replacement.<br />

			You can leave this empty, of course and nothing will be replaced, but then you'll have probably broken images.</p></a>
			<?php
			preg_match_all( '#<img .*?src="([^"]*)/.*?"#is', $importdata, $matches );

			foreach( $matches[1] as $imgurl )
			{
				if( !isset($imgurlscount[ $imgurl ]) )
					$imgurlscount[ $imgurl ] = 1;
				else $imgurlscount[ $imgurl ]++;
			}

			asort( $imgurlscount );
			$imgurlscount = array_reverse( $imgurlscount );

			param( 'singleimgurls', 'integer', 0 );
			$i = 0;
			foreach( $imgurlscount as $imgurl => $counter ) if( $counter > 1 || $singleimgurls )
			{
				?><input type="hidden" name="url_search[<?php echo $i ?>]" value="<?php echo format_to_output( $imgurl, 'formvalue' ) ?>" />
				<strong><?php echo $imgurl ?></strong>:<br />
				<div class="input"><input style="clear:left" type="text" name="url_replace[]" size="50" /></div>
				<span class="notes" style="font-weight:normal"> (used <?php echo $counter ?> times)</span>
				<br />
				<?php
				$i++;
			}

			echo '<p class="center"><a id="imgurls" href="<?php echo $dispatcher ?>?ctrl=mtimport&amp;tab=import&amp;singleimgurls='.( $singleimgurls ? '0' : '1' );
			if( !empty($exportedfile) ) echo '&amp;exportedfile='.$exportedfile;
			echo '">'.( $singleimgurls ? 'hide img urls only used once' : 'show also img urls only used once').'</a></p>';

		?>
		</fieldset>
		*/ ?>

		<fieldset><legend>other settings</legend>
			<?php
			form_checkbox( 'simulate', $simulate, 'Simulate: do not import really', 'Use this to test importing, without really changing the target database.' );
		?>
		</fieldset>
		<p>Please note:</p>
		<ul>
			<li>b2evolution does not support excerpts yet.
			So, we will import them in front of the body with "[teaserbreak]" tags,
			but only if there is no extended body for the post. In that case we'll use the extended body appended with the [teaserbreak] tag to the body - excerpts are lost then (but you'll get a note about it).
			</li>
		</ul>

		<fieldset class="submit">
			<div class="input">
				<input type="hidden" name="import_mode" value="<?php echo $import_mode ?>" />
				<input class="search" type="submit" value=" Import! " />
			</div>
		</fieldset>

		</div>

		</form>

		<?php
	}


	/*************
		IMPORT
	*************/
	elseif( $action == 'import' )
	{
		$Timer->resume('import_main');
		?>
		<div class="panelinfo">
		<h4>Importing from [<?php echo $exportedfile ?>]..<?php if( $simulate ) echo ' (simulating)' ?></h4>

		<?php
		set_max_execution_time(900);

		// counters
		$count_postscreated = 0;
		$count_userscreated = 0;
		$count_commentscreated = 0;
		$count_trackbackscreated = 0;

		// get POSTed data
		param( 'import_mode', 'string', true );

		if( $import_mode != 'expert' )
		{
			param( 'default_blog', 'integer', true );
		}

		import_data_extract_authors_cats();

		{{{ // map categories
		/**
		 * associative array that maps MT cats to b2evo.
		 * key is the MT category name.
		 * values:
		 * holds type and value:
		 *  types:
		 *   - 'blogid': blog_id, new name
		 *   - 'catid': cat_id
		 *   - 'defaultset': -
		 *   - 'ignore': -
		 */
		$catsmapped = array();

		$i_cat = -1;
		// category mapping
		if( !isset($_POST['catmap_select']) )
		{ // no category mapping
			foreach( $categories as $cat )
			{
				$catsmapped[ $cat ] = array('blogid', $default_blog, $cat );
			}
		}
		else foreach( $_POST['catmap_select'] as $cat )
		{
			$i_cat++;
			if( $cat == '#IGNORE#' )
			{
				$catsmapped[ $categories[$i_cat] ] = array( 'ignore' );
			}
			elseif( $cat == '#DEFAULTSET#' )
			{
				if( !isset( $default_post_category ) )
				{ // get the default category set
					if( isset($_POST['post_category']) )
					{
						$default_post_category = (int)$_POST['post_category'];
					}
					else
					{
						dieerror( 'You have chosen to map at least one category to the default category set, but you have not selected a main category for this set!<br />Please go back and correct that..' );
					}
					$default_post_extracats = array();
					if( isset( $_POST['post_extracats'] ) )
					{ // get extra cats
						foreach( $_POST['post_extracats'] as $tcat )
						{
							$default_post_extracats[] = (int)$tcat;
						}
					}
				}
				$catsmapped[ $categories[$i_cat] ] = array( 'defaultset' );
			}
			elseif( preg_match( '/^\d+$/', $cat, $match ) )
			{ // we map to a b2evo cat
				$catsmapped[ $categories[$i_cat] ] = array('catid', (int)$cat);
			}
			elseif( $cat == '#DEFAULTBLOG#'
							|| preg_match( '/^#NEW#(\d+)$/', $cat, $match ) )
			{ // we want a new category
				$blog_id = ($cat == '#DEFAULTBLOG#') ? $default_blog : $match[1];
				// remember the name to create it when posts get inserted
				// fp>dh: please use param() instead of $_POST[] (everywhere)
				$catsmapped[ $categories[$i_cat] ] = array( 'blogid', $blog_id, remove_magic_quotes( $_POST['catmap_name'][$i_cat]) );
			}
			else
			{
				dieerror('This should never happen @catmapping. Please report it! (cat='.$cat.' / ');
			}

		}

		foreach( $catsmapped as $mtcat => $values ) if( $values[0] == 'blogid' )
		{
			echo 'Category <span style="color:#09c">'.$values[2].'</span> (for blog #'.$values[1].') ';
			// check if it already exists
			$cat_ID = $DB->get_var("SELECT cat_ID FROM T_categories
															WHERE cat_blog_ID = {$values[1]}
															AND cat_name = ".$DB->quote( $values[2] ));
			if( !$cat_ID )
			{
				echo 'will be created with first post.<br />';
			}
			else
			{
				echo 'already exists.<br />';
				$catsmapped[ $mtcat ] = array('catid', (int)$cat_ID); // map to existing category
			}

		}

		debug_dump( $catsmapped, 'catsmapped' );
		}}}



		// get renderers
		if( $import_mode != 'easy' )
		{
			$default_renderers = array();
			if( !isset($_POST['renderers']) )
			{ // all unchecked
				$default_renderers = array();
			}
			else $default_renderers = $_POST['renderers'];

			// the special Auto-P renderer
			param( 'autop', 'string', true );
			if( $autop === '1' )
			{ // use always
				$default_renderers[] = 'b2WPAutP';
			}
		}
		else
		{
			global $Plugins;
			// Autoselect a blog to validate renderer list
			$autoselect_blog = autoselect_blog( 'blog_post_statuses', 'edit' );
			$BlogCache = & get_BlogCache();
			$setting_Blog = & $BlogCache->get_by_ID( $autoselect_blog );

			$renderer_params = isset( $setting_Blog ) ? array( 'Blog' => & $setting_Blog, 'setting_name' => 'coll_apply_rendering' ) : array();
			$default_renderers = $Plugins->validate_renderer_list( array('default'), $renderer_params );
			$autop = 1;
		}


		/*
		// get image s&r
		$urlsearch = array();
		$urlreplace = array();
		$i = 0;
		foreach( $_POST['url_replace'] as $replace )
		{
			if( !empty($replace) )
			{
				$urlsearch[] = remove_magic_quotes($_POST['url_search'][$i]);
				$urlreplace[] = remove_magic_quotes( $replace );
			}
			$i++;
		}
		*/

		// get users
		$i_user = 0;
		if( !isset($_POST['user_select']) )
		{
			foreach( $authors as $author )
			{
				$usersmapped[ $author ] = array('createnew', $author );
			}

		}
		else foreach( $_POST['user_select'] as $select )
		{
			$mtauthor = $authors[ $i_user ];

			if( $select == '#IGNORE#' )
			{
				$usersmapped[ $mtauthor ] = array( 'ignore' );
			}
			elseif( $select == '#CREATENEW#' )
			{
				$usersmapped[ $mtauthor ] = array( 'createnew', remove_magic_quotes( $_POST['user_name'][$i_user] ) );
			}
			elseif( preg_match( '#\d+#', $select, $match ) )
			{
				$usersmapped[ $mtauthor ] = array( 'b2evo', $select );
			}
			else
			{
				?><p class="error">Unknown user mapping. This should never ever happen. Please report it.</p><?php
			}
			$i_user++;
		}
		debug_dump( $usersmapped, 'usersmapped' );


		if( $simulate )
		{
			$simulate_cat_id = $DB->get_var( 'SELECT MAX( cat_ID )+1 FROM T_categories' );
		}

		$i = -1;
		echo "\n<ol>";
		foreach ($posts as $post)
		{
			++$i;

			// Defaults:
			$post_catids = array();
			$post_renderers = $default_renderers;
			$post_status = 'published';

			// strip the post's last '--------'
			// "MT export files use 8 dashes to delimit entires (not 5, which delimit entry's sections)."
			$post = preg_replace("|--------\n+$|s", '', $post);

			// first line is author of post
			$post_author = trim( substr( $post, 0, strpos( $post, "\n", 1 ) ) );
			$post = preg_replace( '/^.*\n/', '', $post );
			$message = "\n<li>Post from ".format_to_output( $post_author, 'entityencoded' ).' <ul>';

			// Take the pings out first
			preg_match("|(-----\n\nPING:.*)|s", $post, $pings);
			$post = preg_replace("|(-----\n\nPING:.*)|s", '', $post);

			// Then take the comments out
			preg_match("|(-----\nCOMMENT:.*)|s", $post, $comments);
			$post = preg_replace("|(-----\nCOMMENT:.*)|s", '', $post);

			// We ignore the keywords
			$post = preg_replace("|(-----\nKEYWORDS:.*)|s", '', $post);

			// We want the excerpt - it's put with more and noteaser tag into main body, only if we have no extended body!
			preg_match("|-----\nEXCERPT:(.*)|s", $post, $excerpt);
			$excerpt = trim($excerpt[1]);
			$post = preg_replace("|(-----\nEXCERPT:.*)|s", '', $post);

			// We're going to put extended body into main body with a more tag
			preg_match("|-----\nEXTENDED BODY:(.*)|s", $post, $extended);
			$extended = trim($extended[1]);
			$post = preg_replace("|(-----\nEXTENDED BODY:.*)|s", '', $post);

			// Now for the main body
			preg_match("|-----\nBODY:(.*)|s", $post, $body);
			$body = trim($body[1]);
			if( empty($extended) )
			{ // no extended body, so we can use the excerpt
				if( empty($excerpt) )
					$post_content = $body;
				else $post_content = $excerpt."\n[teaserbreak]\n".$body;
			}
			else
			{ // we'll use body and extended body
				if( !empty($excerpt) )
				{
					$message .=	'<li><span style="color:red">Excerpt discarded because of existing extended body:</span>
					<blockquote>'.htmlspecialchars($excerpt).'</blockquote></li>';
				}
				$post_content = $body."\n[teaserbreak]\n".$extended;
			}

			$post = preg_replace("|(-----\nBODY:.*)|s", '', $post);


			// Grab the metadata from what's left
			$metadata = explode("\n", $post);

			$post_categories = array();
			foreach ($metadata as $line) if( !empty($line) )
			{
				debug_dump($line);

				if( !preg_match("/^(.*?):(.*)/", $line, $token) )
				{
					$message .= "<li class=\"notes\">Unknown meta-data: [$line] (ignoring)</li>";
					continue;
				}
				$key = trim( $token[1] );
				$value = trim( $token[2] );

				// Now we decide what it is and what to do with it
				switch($key)
				{
					case 'TITLE':
						$message .= '<li>title: '.strip_tags($value).'</li>';
						$post_title = $value;
						break;
					case 'STATUS':
						if( strtolower($value) == 'publish' )
							$post_status = 'published';
						elseif( strtolower($value) == 'draft' )
							$post_status = 'draft';
						else
						{
							$message .= '<li>Unknown post status ['.$value.'], using "draft".</li>';
							$post_status = 'draft';
						}
						break;
					case 'ALLOW COMMENTS':
						$post_allow_comments = $value;
						switch( $post_allow_comments ) {
							case 0: $comment_status = 'disabled'; break;
							case 1: $comment_status = 'open'; break;
							case 2: $comment_status = 'closed'; break;
							default:
								$message .= '<li>Unknown comment status ['.$value.'], using "closed".</li>';
								$comment_status = 'closed';
						}
						break;
					case 'CONVERT BREAKS':
						if( $value == '__default__' || empty($value) )
						{
							$post_convert_breaks = $default_convert_breaks;
						}
						elseif( $value == 'textile_2'	&& array_search( 'b2DATxtl', $post_renderers ) === false )
						{ // add the textile 2 renderer to the post's renderers
							$post_renderers[] = 'b2DATxtl';
							$post_convert_breaks = 1;  // TODO: check if this makes sense!
						}
						elseif( preg_match('/\d+/', $value) )
						{
							$post_convert_breaks = (int)( $value > 0 );
						}
						else
						{
							$message .= '<li>Unknown CONVERT BREAKS value, using default ('.$default_convert_breaks.')..</li>';
							$post_convert_breaks = $default_convert_breaks;
						}

						if( $autop == 'depends' && $post_convert_breaks && array_search( 'b2WPAutP', $post_renderers ) === false  )
						{ // add the Auto-P renderer
							$post_renderers[] = 'b2WPAutP';
						}

						break;
					case 'ALLOW PINGS':
						if( $value == 1)
						{
							$post_allow_pings = 'open';
						}
						else
						{
							$post_allow_pings = 'closed';
						}
						break;
					case 'PRIMARY CATEGORY':
					case 'CATEGORY':
						if( !empty($value) && !isset($post_categories[$value]) )
						{
							if( $catsmapped[ $value ][0] == 'defaultset' )
							{ // we add default set
								$post_categories[$value] = $default_post_extracats;
								array_unshift( $post_categories[$value], 'catid', $default_post_category );
							}
							else $post_categories[$value] = $catsmapped[ $value ];
						}
						break;
					case 'DATE':
						$post_date = strtotime( $value );
						$post_date = date('Y-m-d H:i:s', $post_date);
						break;
					default:
						$message .= "\n<li>Unknown key [$key] in metadata:\nvalue: $value\n</li>";
						break;
				}
			} // End foreach (metadata)

			$dontimport = 0;


			if( empty($post_categories) )
			{ // no category metadata found!

				if( $catsmapped[ '[no category assigned]' ][0] == 'defaultset' )
				{ // we must convert default set
					$post_categories['[no category assigned]'] = $default_post_extracats;
					array_unshift( $post_categories['[no category assigned]'], 'catid', $default_post_category );
				}
				else $post_categories[ '[no category assigned]' ] = $catsmapped[ '[no category assigned]' ];

			}

			// Let's check to see if it's in already
			if( $post_ID = $DB->get_var( "SELECT post_ID
																			FROM T_items__item
																		 WHERE post_title = ".$DB->quote($post_title)."
																		   AND post_datestart = '$post_date'"))
			{
				$message .= '<li style="color:blue">Post already imported.</li>';
			}
			else
			{ // insert post

				// check&map author
				switch( $usersmapped[ $post_author ][0] )
				{
					case 'ignore':
						$message .= '<li style="color:blue">User ignored!</li>';
						echo $message.'</ul>';
						continue;  // next post

					case 'b2evo':
						$item_Author = & $UserCache->get_by_login( $usersmapped[ $post_author ][1] );
						break;

					case 'createnew':
						// check if the user already exists
						$UserCache = & get_UserCache();
						$item_Author = & $UserCache->get_by_login( $usersmapped[ $post_author ][1] );

						if( ! $item_Author )
						{
							$item_Author = new User();
							$item_Author->set('login', utf8_strtolower($usersmapped[ $post_author ][1]));
							$item_Author->set('nickname', $usersmapped[ $post_author ][1]);
							$item_Author->set('pass', md5( $default_password ));
							$item_Author->set('level', $default_userlevel);
							$item_Author->set('email', '');
							$GroupCache = & get_GroupCache();
							$item_Author_Group = & $GroupCache->get_by_ID( $default_usergroup );
							$item_Author->set_Group( $item_Author_Group );

							if( !$simulate )
							{
								$item_Author->dbinsert();
							}

							// This is a bad hack, because add() would need an ID (which we don't have when simulating)
							$UserCache->cache_login[ $item_Author->login ] = & $item_Author;

							$message .= '<li style="color:orange">user '.$item_Author->login.' created</li>';
							$count_userscreated++;
						}
						break;
					default:
						$message .= '<li style="color:red">unknown type in checkauthor ('.$usersmapped[ $author ][0].'). This should never ever happen. Post ignored. Please report it.</li>';
						echo $message.'</ul>';
						continue;  // next post
				}


				debug_dump( $post_categories, 'cats to check' );

				// Check categories
				$i_cat = -1;
				$message_ignored = '';
				foreach( $post_categories as $catname => $checkcat )
				{
					$i_cat++;
					switch( $checkcat[0] )
					{
						case 'catid': // existing b2evo catids
							array_shift($checkcat);
							while( $cat_id = array_shift($checkcat) )
								$post_catids[] = $cat_id; // get all catids
							continue;

						case 'ignore': // category is ignored
							if( $i_cat == 0 )
							{ // main category ignored, don't import post
								$dontimport = 1;
								$message_ignored .= '<li>Main Category &quot;'.$catname.'&quot; ignored! - no import</li>';
								break;
							}
							else
							{ // ignored category in extracats, remove it there
								$message_ignored .= '<li>Extra category '.$catname.' ignored.</li>';
								unset( $post_categories[ $catname ] );
							}
							break;

						case 'blogid': // category has to be created
							// create it and remember ID
							if( $simulate )
							{
								$cat_id = ++$simulate_cat_id;
							}
							else
							{
								$cat_id = cat_create( $checkcat[2], 'NULL', $checkcat[1] );
							}
							$catsmapped[ $catname ] = array( 'catid', $cat_id ); // use ID from now on.
							$post_catids[] = $cat_id;
							$message .= '<li style="color:orange">category '.$checkcat[2].' [ID '.$cat_id.'] created</li>';
							break;

						default:
							$message .= '<li style="color:red">This should never ever happen @check_cats. Please report it! (checkcat[0]: '.$checkcat[0].')</li>';

					}
				}
				if( !empty($message_ignored) )
					$message .= '<li style="color:blue">Categories ignored: <ul>'.$message_ignored.'</ul></li>';

				debug_dump( $dontimport, 'dontimport' );
				if( $dontimport )
				{ // see var name :)
					echo $message;
					continue;  // next post
				}

				if( $convert_html_tags )
				{
					$old_content = $post_content;
					// convert tags to lowercase
					$post_content = stripslashes( preg_replace( "~(</?)(\w+)([^>]*>)~e", "'\\1'.strtolower('\\2').'\\3'", $post_content ) );

					// close br, hr and img tags
					$post_content = preg_replace( array('~<(br)>~', '~<(hr\s?.*?)>~', '~<(img\s.*?)>~'), '<\\1 />', $post_content );


					// add quotes for href tags that don't have them
					$post_content = preg_replace( '~href=([^"\'][^\s>"\']+)["\']?~', 'href="$1"', $post_content );

					if( $post_content != $old_content )
					{
						$message .= '<li><p style="color:darkblue;border:1px dashed orange;">'.htmlspecialchars($old_content).'</p>
						html-converted to: <p style="color:darkblue;border:1px dashed orange;">'.htmlspecialchars($post_content).'</p></li>';
					}
				}

				/*if( count($urlreplace) )
				{
					$old_content = $post_content;
					foreach( $urlreplace as $search => $replace )
					{
						$post_content = str_replace( $urlsearch, $urlreplace, $post_content );
					}
					if( $post_content != $old_content )
					{
						echo '<p style="color:darkblue;border:1px dashed orange;">'.htmlspecialchars($old_content).'</p>
						converted img-links to: <p style="color:darkblue;border:1px dashed orange;">'.htmlspecialchars($post_content).'</p>';
					}
				}*/

				debug_dump( $post_catids, 'post_extracats' );
				$post_category = array_shift($post_catids);
				debug_dump( $post_category, 'post_category' );
				debug_dump( $post_categories, 'post_categories' );
				debug_dump( $post_author, 'post_author' );
				debug_dump( isset($item_Author->ID) ? $item_Author->ID : 'NULL (simulating)', 'item_Author->ID' );

				if( !$simulate )
				{
					$edited_Item = new Item();
					$edited_Item->set_creator_User($item_Author);
					$edited_Item->set('title', $post_title);
					$edited_Item->set('content', $post_content);
					$edited_Item->set('datestart', $post_date);
					$edited_Item->set('main_cat_ID', $post_category);
					$edited_Item->set('extra_cat_IDs', $post_catids);
					$edited_Item->set('status', $post_status);
					$edited_Item->set('locale', $post_locale);
					$edited_Item->set('notifications_status', 'finished');
					$edited_Item->set('comment_status', $comment_status);
					$edited_Item->set_renderers($post_renderers);
					$edited_Item->dbinsert();
					$post_ID = $edited_Item->ID;
				}

				$message .= '<li><span style="color:green">Imported successfully</span><ul><li>main category: <span style="color:#09c">'.get_catname( $post_category ).'</span></li>';
				if( count($post_catids) )
					$message .= '<li>extra categories: <span style="color:#09c">'.preg_replace( '/(\d+)/e', "get_catname('\\1')", implode( ', ', $post_catids ) ).'</span></li>';
				$message .= '</ul></li>';
				$count_postscreated++;

			}
			echo $message.'</ul>';


			if( count($comments) )
			{ // comments
				$message = '';

				$comments = explode("-----\nCOMMENT:", $comments[0]);
				foreach ($comments as $comment)
				{
					$comment = trim($comment);
					if( empty($comment) ) continue;

					$comment_author = ripline( 'AUTHOR:', $comment );
					$comment_email = ripline( 'EMAIL:', $comment );
					$comment_ip = ripline( 'IP:', $comment );
					$comment_url = ripline( 'URL:', $comment );
					$comment_date = date('Y-m-d H:i:s', strtotime( ripline( 'DATE:', $comment )));

					$comment_content = preg_replace("/\n*-----$/", '', $comment);

					// Check if it's already in there
					if( !$DB->get_row("SELECT * FROM T_comments WHERE comment_date = '$comment_date' AND comment_content = ".$DB->quote( $comment_content )) )
					{
						if( !$simulate )
						{
							$DB->query( "INSERT INTO T_comments( comment_item_ID, comment_type, comment_author_user_ID, comment_author,
																										comment_author_email, comment_author_url, comment_author_IP,
																										comment_date, comment_content, comment_renderers )
												VALUES( $post_ID, 'comment', NULL, ".$DB->quote($comment_author).",
																".$DB->quote($comment_email).",	".$DB->quote($comment_url).",
																".$DB->quote($comment_ip).", '$comment_date', ".$DB->quote($comment_content).", 'default' )" );
						}

						$message .= '<li>Comment from '.$comment_author.' added.</li>';
						$count_commentscreated++;
					}
				}
				if( !empty($message) )
				{
					echo '<ul>'.$message.'</ul>';
				}

			}

			// Finally the pings
			// fix the double newline on the first one
			if( count($pings) )
			{
				$message = '';
				$pings[0] = str_replace("-----\n\n", "-----\n", $pings[0]);
				$pings = explode("-----\nPING:", $pings[0]);
				foreach( $pings as $ping )
				{
					$ping = trim($ping);
					if( empty($ping) ) continue;

					$comment_author = ripline( 'BLOG NAME:', $ping );
					$comment_email = '';
					$comment_ip = ripline( 'IP:', $ping );
					$comment_url = ripline( 'URL:', $ping );
					$comment_date = date('Y-m-d H:i:s', strtotime( ripline( 'DATE:', $ping )));
					$ping_title = ripline( 'TITLE:', $ping );

					$comment_content = preg_replace("/\n*-----$/", '', $ping);

					$comment_content = "<strong>$ping_title</strong><br />$comment_content";

					// Check if it's already there
					if (!$DB->get_row("SELECT * FROM T_comments WHERE comment_date = '$comment_date' AND comment_type = 'trackback' AND comment_content = ".$DB->quote($comment_content)))
					{
						if( !$simulate )
						{
							$DB->query( "INSERT INTO T_comments
								(comment_item_ID, comment_type, comment_author, comment_author_email, comment_author_url,
								comment_author_IP, comment_date, comment_content, comment_renderers )
								VALUES
								($post_ID, 'trackback', ".$DB->quote($comment_author).", ".$DB->quote($comment_email).", ".$DB->quote($comment_url).",
								".$DB->quote($comment_ip).", ".$DB->quote($comment_date).", ".$DB->quote($comment_content).", 'default' )" );
						}
						$message .= '<li>Trackback from '.$comment_url.' added.</li>';
						$count_trackbackscreated++;
					}
				}
				echo $message;
			}

			echo "</li>\n";
			evo_flush();
		}
		?>
		</ol>
		<h4>All done.<?php if( $simulate ) echo ' (simulated - no real import!)' ?></h4>
		<ul>
			<li><?php echo $count_postscreated ?> post(s) imported.</li>
			<li><?php echo $count_userscreated ?> user(s) created.</li>
			<li><?php echo $count_commentscreated ?> comment(s) imported.</li>
			<li><?php echo $count_trackbackscreated ?> trackback(s) imported.</li>
			<li>in <?php echo $Timer->get_duration('import_main') ?> seconds.</li>
		</ul>
		<?php
		if( $simulate )
		{
			echo '
			<form action="'.$dispatcher.'" method="post">
			<input type="hidden" name="ctrl" value="mtimport" />
			<p>
			<strong>This was only simulated..</strong>
			';
			foreach( $_POST as $key => $value )
			{
				if( $key != 'simulate' )
				{
					if( is_array( $value ) )
					{
						foreach( $value as $key2 => $value2 )
						{
							echo '<input type="hidden" name="'.$key.'['.$key2.']" value="'.format_to_output( $value2, 'formvalue' ).'" />';
						}
					}
					else
					{
						echo '<input type="hidden" name="'.$key.'" value="'.format_to_output( $value, 'formvalue' ).'" />';
					}
				}
			}
			echo '<input type="submit" value="Do it for real now!" /></p></form>'."\n";
		}
		?>
		<p>
			<a href="<?php echo $baseurl ?>">Have fun in your blogs</a> or <a href="<?php echo $admin_url ?>">go to admin</a> (it's fun there, too)
		</p>
		<?php
		if( $count_userscreated )
		{
			echo '<p class="note">Please note that the new users being created are not member of any blog yet. You\'ll have to setup this in the <a href="'.$admin_url.'?ctrl=dashboard">blogs admin</a>.</p>';
		}
		?>
		</div>
	<?php
	}

?>
<div class="clear">
<?php if( $output_debug_dump ) $DB->dump_queries() ?>
</div>
</div>
</body>
</html>
<?php

/* ------ FUNCTIONS ------ */

/**
 * @todo fp> this needs to be deprecated
 * @todo fp> get rid of the $cache_blogs crap and use $BlogCache only
 */
function blog_load_cache()
{
	global $DB, $cache_blogs;
	if( empty($cache_blogs) )
	{
		$BlogCache = & get_BlogCache();
		$cache_blogs = array();

		foreach( $DB->get_results( "SELECT * FROM T_blogs ORDER BY blog_ID", OBJECT, 'blog_load_cache()' ) as $this_blog )
		{
			$cache_blogs[$this_blog->blog_ID] = $this_blog;

			// Add it to BlogCache, so it does not need to load it also again:
			// NOTE: dh> it may be bad to instantiate all objects, but there's no shadow_cache for rows..
			$BlogCache->instantiate($this_blog);
			//echo 'just cached:'.$cache_blogs[$this_blog->blog_ID]->blog_name.'('.$this_blog->blog_ID.')<br />';
		}
		$BlogCache->all_loaded = true;
	}
}


/**
 * Get name for a given cat ID.
 *
 * @return string Cat name in case of success, false on failure.
 */
function get_catname($cat_ID)
{
	$ChapterCache = & get_ChapterCache();
	$Chapter = & $ChapterCache->get_by_ID($cat_ID);
	return $Chapter->name;
}


function fieldset_cats()
{
	global $cache_blogs;

	// ----------------- START RECURSIVE CAT LIST ----------------
	$ChapterCache = & get_ChapterCache();
	// Load all chapters recursively at once
	$ChapterCache->reveal_children( NULL, true );

	?>
	<fieldset title="default categories set" style="background-color:#fafafa; border:1px solid #ccc; padding: 1em; display:inline; float:right; white-space:nowrap;">
		<legend>Default categories set (only needed if you want to map categories to this)</legend>
		<p class="extracatnote">
		<?php
			if( count( $ChapterCache->cache ) )
			{
				echo T_('Select main category in target blog and optionally check additional categories').':';
			}
			else
			{
				echo 'No categories in your blogs..';
			}
		?>
		</p>

		<?php
		// ----------------------------  CATEGORIES ------------------------------
		$default_main_cat = 0;
		$blog = 1;

		function import_cat_select_before_first( $level )
		{	// callback to start sublist
			return "\n<ul>\n";
		}

		function import_cat_select_before_each( $Chapter, $level )
		{	// callback to display sublist element
			global $current_blog_ID, $blog, $cat, $postdata, $default_main_cat, $action, $tabindex;

			$r = '<li>';

			if( get_allow_cross_posting() >= 1 )
			{ // We allow cross posting, display checkbox:
				$r .= '<input type="checkbox" name="post_extracats[]" class="checkbox" title="'.T_('Select as an additionnal category').'" value="'.$Chapter->ID.'"';
				$r .= ' />';
			}

			// Radio for main cat:
			if( $current_blog_ID == $blog )
			{
				if( ($default_main_cat == 0) && ($action == 'post') )
				{	// Assign default cat for new post
					$default_main_cat = $Chapter->ID;
				}
				$r .= ' <input type="radio" name="post_category" class="checkbox" title="'.T_('Select as MAIN category').'" value="'.$Chapter->ID.'"';
				if( ($Chapter->ID == $postdata["Category"]) || ($Chapter->ID == $default_main_cat))
					$r .= ' checked="checked"';
				$r .= ' />';
			}
			$r .= ' '.htmlspecialchars(get_catname($Chapter->ID));

			// End of element
			$r .= "</li>\n";

			return $r;
		}

		function import_cat_select_after_last( $level )
		{	// callback to end sublist
			return "</ul>\n";
		}

		$callbacks = array(
			'line'         => 'import_cat_select_before_each',
			'before_level' => 'import_cat_select_before_first',
			'after_level'  => 'import_cat_select_after_last'
		);

		// go through all blogs with cats:
		foreach( $cache_blogs as $i_blog )
		{ // run recursively through the cats
			$current_blog_ID = $i_blog->blog_ID;
			if( ! blog_has_cats( $current_blog_ID ) ) continue;
			#if( ! $current_User->check_perm( 'blog_post_statuses', 'any', false, $current_blog_ID ) ) continue;
			echo "<h4>".$i_blog->blog_name."</h4>\n";
			echo $ChapterCache->recurse( $callbacks, $current_blog_ID, NULL, 0, 0, array( 'sorted' => true) );
		}
		// ----------------- END RECURSIVE CAT LIST ----------------
		?>
	</fieldset>
<?php
}


/*
	-- Category options list --
*/
function cats_optionslist( $forcat )
{
	global $cache_blogs, $cache_optionslist, $cat_name_id_associations;

	if( !isset( $cat_name_id_associations ) )
	{ // Create a map fro the category name-id associations to populate during optionlist initialization
		$cat_name_id_associations = array();
	}

	if( !isset($cache_optionslist) )
	{
		$ChapterCache = & get_ChapterCache();
		$ChapterCache->reveal_children( NULL, true );
		$callbacks = array( 'line' => 'proces_cat_line' );

		$cache_optionslist = '';
		foreach( $cache_blogs as $i_blog )
		{
			$cache_optionslist .= '<option value="#NEW#'.$i_blog->blog_ID.'">[-- create in blog '.$i_blog->blog_shortname.' --]:</option>';
			$cache_optionslist .= $ChapterCache->recurse( $callbacks, $i_blog->blog_ID, NULL, 0, 0, array( 'sorted' => true) );
		}
	}

	$cat_id = isset( $cat_name_id_associations[$forcat] ) ? $cat_name_id_associations[$forcat] : false;

	if( $cat_id )
	{
		echo str_replace( '<option value="'.$cat_id.'">', '<option value="'.$cat_id.'" selected="selected">', $cache_optionslist );
	}
	else
	{
		echo $cache_optionslist;
	}
}


function proces_cat_line( $Chapter, $level )
{
	global $cat_name_id_associations;

	// Set name-ID association to be able to select them easily in the select list
	$cat_name_id_associations[$Chapter->get( 'name' )] = $Chapter->ID;

	$r .= '<option value="'.$Chapter->ID.'">';

	for( $i = 0; $i <= $level; $i++ )
	{ // Add one '-' for each level
		$r .= '-';
	}

	$r .= '&gt; '.format_to_output( $Chapter->get( 'name' ), 'entityencoded' ).'</option>';
	return $r;
}


/**
 * extracts unique authors and cats from posts array
 */
function import_data_extract_authors_cats()
{
	global $authors, $categories, $posts;
	global $exportedfile;
	global $categories_countprim;
	global $importdata;
	global $import_mode;
	global $dispatcher;

	$fp = fopen( $exportedfile, 'rb');
//slamp_080609_begin: to avoid warning when importing file with 0 bytes of data
//	$buffer = fread($fp, filesize( $exportedfile ));
	$buffer = '';
	$length = filesize($exportedfile);
	if($length)
	{
  		$buffer = fread($fp, $length);
	}
//slamp_080609_end
	fclose($fp);
	if( !preg_match( '/^[-\s]*AUTHOR: /', $buffer ) )
	{
		dieerror("The file [$exportedfile] does not seem to be a MT exported file.. ".'[<a href="'.$dispatcher.'?ctrl=mtimport&amp;import_mode='.$import_mode.'">choose another export-file</a>]');
	}

	$importdata = preg_replace( "/\r?\n|\r/", "\n", $buffer );
	$posts = preg_split( '/(^|--------\n)(AUTHOR: |$)/', $importdata );

	$authors = array(); $tempauthors = array();
	$categories = array(); $tempcategories = array();

	foreach ($posts as $nr => $post)
	{
		if ('' != trim($post))
		{
			// first line is author of post
			$tempauthors[] = trim( substr( $post, 0, strpos( $post, "\n", 1 ) ) );

			$oldcatcount = count( $tempcategories );

			if( preg_match_all( "/^(PRIMARY )?CATEGORY: (.*)/m", $post, $matches ) )
			{
				for( $i = 1; $i < count( $matches[2] ); $i++ )
				{
					$cat = trim( $matches[2][$i] );
					if( !empty( $cat ) ) $tempcategories[] = $cat;
				}

				// main category last (-> counter)
				if( !empty($matches[2][0]) ) $tempcategories[] = $matches[2][0];
			}

			if( $oldcatcount == count( $tempcategories ) )
			{
				$tempcategories[] = '[no category assigned]';
			}

			// remember how many times used as primary category
			@$categories_countprim[ $tempcategories[ count( $tempcategories )-1 ] ]++;
		}
		else
		{
			unset( $posts[ $nr ] );
		}
	}

	// we need to find unique values of author names, while preserving the order, so this function emulates the unique_value(); php function, without the sorting.
	$authors[0] = array_shift($tempauthors);
	$y = count($tempauthors) + 1;
	for ($x = 1; $x < $y; $x++)
	{
		$next = array_shift($tempauthors);
		if( !(in_array($next,$authors)) ) $authors[] = $next;
	}
	$categories[0] = array_shift( $tempcategories );
	$y = count($tempcategories) + 1;
	for ($x = 1; $x < $y; $x++)
	{
		$next = array_shift($tempcategories);
		if( !(in_array($next, $categories)) ) $categories[] = $next;
	}
}


/**
 * Outputs a list of available renderers (not necessarily installed).
 */
function renderer_list( & $Blog )
{
	global $renderers;

	$admin_Plugins = & get_Plugins_admin(); // use Plugins_admin, because a plugin might be disabled
	$admin_Plugins->discover();

	$renderers = array('default');
	$admin_Plugins->restart();	 // make sure iterator is at start position
	while( $loop_RendererPlugin = & $admin_Plugins->get_next() )
	{ // Go through whole list of renders
		// echo ' ',$loop_RendererPlugin->code;
		if( empty($loop_RendererPlugin->code) )
		{ // No unique code!
			continue;
		}

		$apply_rendering = $loop_RendererPlugin->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( $apply_rendering == 'stealth'
			|| $apply_rendering == 'never' )
		{	// This is not an option.
			continue;
		}
		elseif( $loop_RendererPlugin->code == 'b2WPAutP' )
		{ // special Auto-P plugin
			?>
			<fieldset>
				<label for="textile" title="<?php echo format_to_output($loop_RendererPlugin->short_desc, 'formvalue'); ?>"><strong><?php echo format_to_output($loop_RendererPlugin->name) ?>:</strong></label>
				<div style="margin-left:2ex" />
				<input type="radio" name="autop" value="1" class="checkbox" checked="checked" /> yes (always)<br>
				<input type="radio" name="autop" value="0" class="checkbox" /> no (never)<br>
				<input type="radio" name="autop" value="depends" class="checkbox" /> depends on CONVERT BREAKS
				<span class="notes"> ..that means it will apply if convert breaks results to true (set to either 1, textile_2 or __DEFAULT__ (and &quot;Convert-breaks default&quot; checked above)</span>

				</div>
			</fieldset>
			<?php
			continue;
		}
		?>
		<div>
			<input type="checkbox" class="checkbox" name="renderers[]"
				value="<?php echo $loop_RendererPlugin->code ?>" id="<?php echo $loop_RendererPlugin->code ?>"
				<?php
				switch( $apply_rendering )
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
			title="<?php echo format_to_output( $loop_RendererPlugin->short_desc, 'formvalue' ) ?>" />
		<label for="<?php echo $loop_RendererPlugin->code ?>" title="<?php echo format_to_output($loop_RendererPlugin->short_desc, 'formvalue'); ?>"><strong><?php echo format_to_output($loop_RendererPlugin->name); ?></strong></label>
	</div>
	<?php
	}
}


/**
 * Die with a message.
 *
 * @param string the message (wrapped in div and p tag of class error)
 * @param string optional head
 */
function dieerror( $message, $before = '' )
{
	if( !empty($before) )
		echo $before;

	die( '<div class="error"><p class="error">'.$message.'</p></div>
	</div></body></html>' );
}


function debug_dump( $var, $title = '' )
{
	global $output_debug_dump;

	if( $output_debug_dump )
	{
		pre_dump( $var, $title );
	}
}


function chooseexportfile()
{
	global $exportedfile, $import_mode, $dispatcher;

	// Go through directory:
	$this_dir = dir( IMPORT_SRC_DIR );
	$r = '';
	while( $this_file = $this_dir->read() )
	{
		if( preg_match( '/^.+\.txt$/i', $this_file ) )
		{
			$r .= '<option value="'.format_to_output( $this_file, 'formvalue' ).'"';
			if( $exportedfile == $this_file ) $r .= ' selected="selected"';
			$r .= '>'.format_to_output( $this_file, 'entityencoded' ).'</option>';
		}
	}

	if( $r )
	{
		?>
		<form action="<?php echo $dispatcher ?>" class="center">
			<p>First, choose a file to import (.TXT files from the b2evolution base directory):</p>
			<select name="exportedfile" onChange="submit()">
				<?php echo $r ?>
			</select>
			<input type="hidden" name="import_mode" value="<?php echo $import_mode ?>" />
			<input type="hidden" name="ctrl" value="mtimport" />
			<input type="submit" value="Next step..." class="search" />
		</form>
		<?php
	}
	else
	{ // no file found
		?>
		<div class="error">
		<p class="center">No .TXT file found. Nothing to import...</p>
		<p class="center">Please copy your Movable Type .TXT export file into <?php echo rel_path_to_base(IMPORT_SRC_DIR); ?>.</p>
		</div>
		<?php
	}
}


function ripline( $prefix, &$haystack )
{
	if( preg_match( '|^'.$prefix.'(.*)|m', $haystack, $match ) )
	{
		$haystack = preg_replace('|^'.$prefix.".*\n?|m", '', $haystack );
		return trim( $match[1] );
	}
	else return false;
}


function tidypostdata( $string )
{
	return str_replace( array('&quot;', '&#039;', '&lt;', '&gt;'), array('"', "'", '<', '>'), remove_magic_quotes( $string ) );
}

?>