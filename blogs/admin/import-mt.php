<?php
/**
 * This script imports Movable Type entries into b2evolution.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package internal
 * @author blueyed - http://hahler.de/daniel
 *
 * TODO:
 *  - list of all posts, editable (overkill?)
 *  - assign comment_author_ID to comments if user exist?!
 *
 * CHANGES:
 *  0.2:
 *   - fixed comments/trackbacks (still not thoroughly tested) [thanks to chris]
 *   - new user password must be confirmed [thanks to chris]
 *   - we preselect the b2evo default renderers now (especially Auto-P)
 *   - fixed user mapping from select box
 *  0.1:
 *   - first release
 *
 * Credits go to the WordPress team (http://wordpress.org), where I got the basic import-mt.php script with
 * most of the core functions. Thank you!
 *
 * This script was developed and tested with b2evolution 0.9.0.4 (on Sourceforge CVS) and Movable Type 2.64 and 2.661.
 * It should work quite alright with b2evo 0.9 though.
 *
 * Feedback is very welcome (http://thequod.de/contact).
 */

// enter the relative path of the import.txt file containing the MT entries.
// If the file is called import.txt and it is /admin, then this line
// should be:
// define('MTEXPORT', 'import.txt');
define('MTEXPORT', '');


$output_debug_dump = 0;  // set to true to get a lot of <pre>'d var_dumps
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>b2evolution &rsaquo; Import from Movable Type</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link href="../admin/admin.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="header">
	<a href="http://b2evolution.net"><img id="evologo" src="../img/b2evolution_minilogo2.png" alt="b2evolution"  title="visit b2evolution's website" width="185" height="40" /></a>

	<div id="headinfo">
	<br /><h2>import Movable Type into b2evolution - v0.2</h2>
	</div>


<?php

if( !file_exists('../conf/_config.php') )
{
	die( "There doesn't seem to be a conf/_config.php file. You must install b2evolution before you import any entries."
	.'</div></div></body></html>' );
}
require( '../conf/_config.php' );
if( (!isset($config_is_done) || !$config_is_done) )
{
	die( 'b2evolution configuration is not done yet.'
	.'</div></div></body></html>' );
}


$use_obhandler = 0;  // no output buffering!
require( '../b2evocore/_main.php' );


param( 'tab', 'string', 'info' );
?>

<?php /*** tabs ***/ ?>
	<ul class="tabs">
	<?php
		foreach( array(
							'info' => 'Info',
							'exportfile' => 'Import export-file',
						) as $looptab => $disp )
		{
			echo ( $tab == $looptab ) ? '<li class="current">' : '<li>';
			echo '<a href="?tab='.$looptab.'">'.$disp.'</a></li>';
		}
	?>
	</ul>
</div>

<div class="panelbody">
<div class="panelblock">
<p class="center"><strong style="color:red">this is gamma state, don't use without backups!</strong></p>

<?php

switch( $tab ) {

	case 'info': ?>
		<div class="panelinfo">
			<p>Thanks for thinking about switching to b2evolution. We're sure you'll enjoy it.
			<br />So, on with the show..
			</p>
		</div>
		<div class="panelinfo">
			<p>This script imports your Movable Type entries into b2evolution.</p>
			<p>You need to edit the file [<code>import-mt.php</code>] and change one line so we know where to
			find your MT export file. To make this easy put the import (exported MT) file into the <code>admin</code>
			directory. Look for the line that says:</p>
			<p><code>define('MTEXPORT', '');</code></p>
			<p>and change it to</p>
			<p><code>define('MTEXPORT', 'import.txt');</code></p>
			<p>You have to do this manually for security reasons.</p>

			<p>Please note:
				<ul>
					<li>the importer is smart enough not to import duplicates, so you can run this multiple times without worry
					if &#8212; for whatever reason &#8212; it doesn't finish (script timeout).</li>

				</ul>
			</p>

		</div>

		<?php
		break;

	case 'exportfile':
		if( '' != MTEXPORT && !file_exists(MTEXPORT) )
		{
			dieerror("The MT export file you specified does not seem to exist. Please check the path you've given for defined MTEXPORT.");
		}
		elseif( '' == MTEXPORT )
		{
			dieerror('You must edit the MTEXPORT line as described on the <a href="import-mt.php">previous page</a> to continue.');
		}

		// get the params
		param( 'default_password', 'string', 'changeme' );
		param( 'default_password2', 'string', 'changeme' );

		if( $default_password != $default_password2 )
		{
			dieerror( 'The two passwords for new users are not identical.' );
		}

		param( 'default_userlevel', 'integer', 1 );
		param( 'default_usergroup', 'integer', $Settings->get('newusers_grp_ID') );
		param( 'default_convert_breaks', 'integer', 1 );

		param( 'action', 'string', '' );


		cat_load_cache();  // load categories cache

		set_magic_quotes_runtime( 0 );
		$i_user = -1;

		if( empty($action) )
		{
			$datalines = file(MTEXPORT); // Read the file into an array
			$importdata = implode('', $datalines); // squish it
			$importdata = preg_replace("/(\r\n|\n|\r)/", "\n", $importdata);	// make platform-independent newlines
			$posts = explode('--------', $importdata);


			import_data_extract_authors_cats();


			?>
			<form class="fform" action="?tab=exportfile&amp;action=import" method="post">

			<div class="bSideBar">

			<div class="bSideItem" style="background:#FFC">
			We have <?php echo count( $posts ) ?> post(s) from <?php echo count( $authors ) ?> author(s) in
			<?php echo count( $categories ) ?> categories.
			<br />
			We'll import into DB <?php echo DB_NAME ?>.
			</div>

			<div class="bSideItem2">
				<?php fieldset_cats() ?>
			</div>
			</div>

			<div class="bPosts">
			<div class="bPost">
			<fieldset><legend>Author mapping</legend>
				<div class="panelinfo">
					The authors of the MovableType posts are <em>emphasized</em>.
					For each MT author you can select a b2evo user (in the dropdown), enter a new login (new users created that
					way will get the default settings below) or ignore his/hers posts/entries.
				</div>
				<?php
					// build <select>-template for available users
					$evousers = $DB->get_results("SELECT * FROM $tableusers ORDER BY ID");

					$user_selecttemplate = '<select name="user_select[]">
						<option value="#NONE#"> - Select b2evo user.. - </option>';
					if( $evousers )
					{
						$i = -1;
						foreach($evousers as $user) {
							$user_selecttemplate .= '<option value="'.$user->ID.'">'.format_to_output($user->user_login, 'formvalue').'</option>';
						}
					}
					$user_selecttemplate .= '</select>';

					foreach ($authors as $author) {
					++$i_user;
					?>
					<fieldset>
					<div class="label"><label><em><?php echo $author ?></em></label></div>
					<div class="input">
						<?php
						printf( $user_selecttemplate."\n" )

						?>
						<input type="text" value="<?php echo format_to_output($author, 'formvalue') ?>" name="user_name[]" maxlength="30" class="input" />

						<span class="notes">(create new)</span>
						<span title="don't import posts of that user"><input type="checkbox" value="<?php echo $i_user ?>" name="user_ignore[]" /> ignore</span>
					</div>
					</fieldset>
					<?php
					}
					?>
				</fieldset>
				<fieldset><legend>Category mapping</legend>

				<div class="panelinfo">
				You can either map every MT category to an existing b2evo category (dropdown), create it as root category in an existing blog
				(dropdown and text input field for the name) or insert the posts of that category into the default categories set, which you can select in the categories
				list to the right.
				</div>
				<?php
				foreach( $categories as $cat )
				{
					?>
					<fieldset>
					<div class="label">
						<label><?php echo format_to_output($cat, 'htmlbody') ?></label>
					</div>
					<div class="input"><select name="catmap_select[]">
						<option value="#DEFAULTSET#">Create in default categories set (see right)</option>
						<?php cats_optionslist( $cat ) ?>
					</select>
					<input type="text" name="catmap_newname[]" value="<?php echo format_to_output( $cat, 'formvalue' ) ?>" size="20" />
					</div>
					</fieldset>
				<?php
				} ?>
				</fieldset>
				<fieldset><legend>New user defaults</legend>
					<?php
					form_text( 'default_password', $default_password, 30, 'Password for new users', 'this will be the password for users created during migration (default "changeme")', 30 , '', 'password' );
					form_text( 'default_password2', $default_password, 30, 'Confirm password', 'please confirm the password', 30 , '', 'password' );
					$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://b2evolution.net/man/user_levels.html"' );
					form_text( 'default_userlevel', 1, 2, T_('Level'), $field_note, 2 );
					form_select_object( 'default_usergroup', $Settings->get('newusers_grp_ID'), $GroupCache, T_('User group') );
					?>
				</fieldset>

				<fieldset><legend>Post/Entry defaults</legend>
					<?php
					form_checkbox( 'default_convert_breaks', $default_convert_breaks, 'Convert-Breaks default', 'will be used for posts with CONVERT BREAKS = __default__' );
					form_select( 'default_locale', $Settings->get('default_locale'), 'locale_options', T_('Default locale'), 'Locale for posts.' );

					?>
					<div class="label">Renderers</div>
					<div class="input"><?php renderer_list() ?></div>
				</fieldset>
			<p>Please note:</p>
			<ul>
				<li>Excerpts are put in front of body with "<?php echo htmlspecialchars('<!--more-->< !--noteaser-->') ?>" tags,
				but only if there is no extended body. Because then we'll use the extended text appended with the more tag in the body.</li>
			</ul>
			</div>

			<div class="input">
				<input class="search" type="submit" value=" Import! " />
				<input class="search" type="reset" value="reset form" />
			</div>
			</div>

			</form>

			<?php
			break;
		}

		/*************
			IMPORT
		*************/
		elseif( $action == 'import' )
		{
			?><div class="panelinfo"><?php
			if( function_exists( 'set_time_limit' ) )
			{
				set_time_limit( 900 ); // 15 minutes ought to be enough for everybody *g
			}

			$atleastoneusercreated = false;

			// get POSTed data
			$catmap_select = array();
			$catmap_newname = array();
			$default_renderers = array();  // no renderers by default

			foreach( $_POST['catmap_select'] as $cat )
			{

				$catmap_select[] = stripslashes(str_replace( array('&quot;', '&#039;', '&lt;', '&gt;'), array('"', "'", '<', '>'), $cat ));
			}
			foreach( $_POST['catmap_newname'] as $cat )
			{
				$catmap_newname[] = stripslashes(str_replace( array('&quot;', '&#039;', '&lt;', '&gt;'), array('"', "'", '<', '>'), $cat ));
			}

			if( isset( $_POST['renderers'] ) ) foreach( $_POST['renderers'] as $renderer )
			{
				$default_renderers[] = $renderer;
			}

			import_data_extract_authors_cats();

			debug_dump( $catmap_select, 'catmap_select' );
			debug_dump( $catmap_newname, 'catmap_newname' );
			debug_dump( $categories, 'categories' );

			// category mapping
			if( in_array( '#DEFAULTSET#', $catmap_select ) )
			{
				if( isset($_POST['post_category']) )
				{
					$default_post_category = $_POST['post_category'];
				}
				else
				{
					dieerror( 'You have chosen to map at least one category to default category set, but you have not selected a main category! Please go back and correct that..' );
				}
				$default_post_extracats = array();
				if( isset( $_POST['post_extracats'] ) )
				{ // get extra cats
					foreach( $_POST['post_extracats'] as $cat )
					{
						$default_post_extracats[] = $cat;
					}
				}
			}


			// build the $catsmapped array
			?><p><?php
			foreach( $categories as $nr => $cat )
			{
				debug_dump( $cat, $nr );
				if( preg_match( '/^#NEW#(\d+)$/', $catmap_select[$nr], $match ) )
				{ // we want a new category
					echo 'New category <span style="color:#09c">'.$catmap_newname[$nr].'</span>.. ';
					// check if it already exists
					$cat_ID = $DB->get_var("SELECT cat_ID FROM $tablecategories
										WHERE cat_blog_ID = {$match[1]} AND cat_name = '".$DB->escape($catmap_newname[$nr])."'");
					if( !$cat_ID )
					{
						$cat_ID = cat_create( $catmap_newname[$nr],	'NULL', $match[1] );
						echo 'created.<br />';
					}
					else
					{
						echo 'already exists!<br />';
					}
					$catsmapped[ $cat ] = $cat_ID;  // map select to cat
				}
				elseif( $catmap_select[$nr] == '#DEFAULTSET#' )
				{ // we use the default set
					$catsmapped[ $cat ] = array( $default_post_category, $default_post_extracats ); // extra cats as array
				}
				elseif( preg_match('/\d+/', $catmap_select[$nr]) )
				{ // simple integer, this is a b2evo category
					$catsmapped[ $cat ] = $catmap_select[$nr];
				}
				else
				{
					?><p class="error">Unknown category mapping. This should never ever happen. Please report it.</p><?php
				}
			}
			echo '</p>';


			$newauthornames = array();
			$mt_authors_input = array();
			$mt_authors_select = array();
			$mt_authors = array();
			$mt_authors_ignore = array();

			// ignored users
			if( isset($_POST['user_ignore']) )
				$mt_authors_ignore = $_POST['user_ignore'];

			foreach( $_POST['user_name'] as $line )
			{
				$newname = trim(stripslashes($line));
				$newname = str_replace( array('&quot;', '&#039;', '&lt;', '&gt;'), array('"', "'", '<', '>'), $newname );
				if( empty($newname) )
				{ // passing author names from step 1 to step 2 is accomplished by using POST. left_blank denotes an empty entry in the form.
					$newname = 'left_blank';
				}
				array_push($mt_authors_input, "$newname"); // $mt_authors_input is the array with the form entered names
			}

			foreach( $_POST['user_select'] as $user )
			{
				$selected = trim( stripslashes($user) );
				$selected = str_replace( array('&quot;', '&#039;', '&lt;', '&gt;'), array('"', "'", '<', '>'), $selected );

				array_push($mt_authors_select, "$selected");
			}
			$count = count( $mt_authors_input );
			for( $i = 0; $i < $count; $i++ )
			{
				if( $mt_authors_select[$i] != '#NONE#')
				{ //if no name was selected from the select menu, use the name entered in the form
					$mt_authors_select[$i] = $DB->get_var("SELECT user_login FROM $tableusers WHERE ID = $mt_authors_select[$i]");
					if( empty($mt_authors_select[$i]) )
					{ // the selected user ID could not be retrieved from DB (probably deleted after the select dropdown has been created)
						array_push($newauthornames,"$mt_authors_input[$i]");
					}
					array_push($newauthornames, "$mt_authors_select[$i]");
				}
				else
				{
					array_push($newauthornames,"$mt_authors_input[$i]");
				}
			}

			/**
			 * function to check the authorname and do the mapping
			 */
			function checkauthor($author)
			{
				global $DB, $tableusers, $mt_authors, $newauthornames, $i_user;
				global $default_password, $default_userlevel, $default_usergroup;
				global $mt_authors_ignore;
				global $GroupCache;
				global $atleastoneusercreated;

				$md5pass = md5( $default_password );
				if( !(in_array($author, $mt_authors)) )
				{ // a new mt author name is found
					++$i_user;
					$mt_authors[$i_user] = $author; //add that new mt author name to an array

					if( in_array( $i_user, $mt_authors_ignore ) )
					{
						?><span style="color:blue">User ignored!</span><?php
						return -1;
					}

					// check if the new author name defined by the user is a pre-existing b2evo user
					$user_id = $DB->get_var("SELECT ID FROM $tableusers WHERE user_login = '".$DB->escape($newauthornames[$i_user])."'");
					if( !$user_id )
					{
						if( $newauthornames[$i_user] == 'left_blank' )
						{ // check if the user does not want to change the authorname
							$newauthornames[$i_user] = $author; //now we have a name, in the place of left_blank.
							/*$DB->query("INSERT INTO $tableusers (user_level, user_login, user_pass, user_nickname) VALUES ('1', '".$DB->escape($author)."', '$md5pass', '".$DB->escape($author)."')"); // if user does not want to change, insert the authorname $author
							$user_id = $DB->get_var("SELECT ID FROM $tableusers WHERE user_login = '$author'");*/
						}

						$new_user = new User();
						$new_user->set('login', $newauthornames[$i_user]);
						$new_user->set('nickname', $newauthornames[$i_user]);
						$new_user->set('pass', $md5pass);
						$new_user->set('level', $default_userlevel);
						$new_user_Group = $GroupCache->get_by_ID( $default_usergroup );
						$new_user->setGroup( $new_user_Group );

						$new_user->dbinsert();
						$user_id = $new_user->ID;

						?><span class="notes"> [user <?php echo $author ?> created!] </span><?php
						$atleastoneusercreated = true;
					}
					else return $user_id; // return pre-existing b2evo username if it exists
				}
				else
				{
					// find the array key for $author in the $mt_authors array
					$key = array_search($author, $mt_authors);

					if( in_array( $key, $mt_authors_ignore ) )
					{
						?><span style="color:blue">User ignored!</span><?php
						return -1;
					}

					//use that key to get the value of the author's name from $newauthornames
					$user_id = $DB->get_var("SELECT ID FROM $tableusers WHERE user_login = '".$DB->escape($newauthornames[$key])."'");
				}
				return $user_id;
			}


			$i = -1;
			echo '<ol>';
			foreach ($posts as $post) if ('' != trim($post)) {
				++$i;
				unset($post_extracats);
				echo '<li>Processing post ';

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
				if( '' != $excerpt ) $excerpt = $excerpt."\n<!--more--><!--noteaser-->\n";
				$post = preg_replace("|(-----\nEXCERPT:.*)|s", '', $post);

				// We're going to put extended body into main body with a more tag
				preg_match("|-----\nEXTENDED BODY:(.*)|s", $post, $extended);
				$extended = trim($extended[1]);
				if ('' != $extended) $extended = "\n<!--more-->\n$extended";
				$post = preg_replace("|(-----\nEXTENDED BODY:.*)|s", '', $post);

				// Now for the main body
				preg_match("|-----\nBODY:(.*)|s", $post, $body);
				$body = trim($body[1]);
				if( empty($extended) )
				{ // no extended body, so we can use the excerpt
					$post_content = $body . $extended;
				}
				else
				{ // we'll use body and extended body
					$post_content = $body . $extended;
				}

				$post = preg_replace("|(-----\nBODY:.*)|s", '', $post);


				// Grab the metadata from what's left
				$metadata = explode("\n", $post);

				$post_extracats = array();
				foreach ($metadata as $line) if( !empty($line) ) {
					debug_dump($line);

					$post_locale = $default_locale;

					preg_match("/^(.*?):(.*)/", $line, $token);
					$key = trim($token[1]);
					$value = trim($token[2]);

					// Now we decide what it is and what to do with it
					switch($key)
					{
						case 'AUTHOR':
							$post_author = $value;
							echo 'from '.format_to_output( $value, 'entityencoded' ).' ';
							break;
						case 'TITLE':
							echo '<i>'.$value.'</i>... ';
							$post_title = addslashes($value);
							$post_name = $post_title;
							break;
						case 'STATUS':
							if( $value == 'Publish' )
							{
								$post_status = 'published';
							}
							else $post_status = $value;
							if( empty($post_status) ) $post_status = 'publish';
							break;
						case 'ALLOW COMMENTS':
							$post_allow_comments = $value;
							switch( $post_allow_comments ) {
								case 0: $comment_status = 'disabled'; break;
								case 1: $comment_status = 'open'; break;
								case 2: $comment_status = 'closed'; break;
								default:
									echo '<p class="error">Unknown comment status ['.$value.'], using "closed".';
									$comment_status = 'closed';
							}
							break;
						case 'CONVERT BREAKS':
							if( $value == '__default__' )
								$post_convert_breaks = $default_convert_breaks;
							else $post_convert_breaks = $value;
							break;
						case 'ALLOW PINGS':
							$post_allow_pings = trim($metadata[2][0]);
							if ($post_allow_pings == 1) {
								$post_allow_pings = 'open';
							} else {
								$post_allow_pings = 'closed';
							}
							break;
						case 'PRIMARY CATEGORY':
							if( empty($value) ) $post_category = $catsmapped[ '[no category assigned]' ];
							else $post_category = $catsmapped[ $value ];

							// category mapping
							if( is_array( $post_category ) )
							{ // we have a category+extracats here
								$post_extracats = $post_category[1]; // transfer extracats
								$post_category = $post_category[0];  // set primary category only
							}
							break;
						case 'CATEGORY':
							if( !empty($value) )
							{
								if( is_array( $catsmapped[ $value ] ) )
								{ // we have a category+extracats here
									$post_extracats[] = $catsmapped[ $value ][0];
									foreach( $catsmapped[ $value ][1] as $cat )
									{
										$post_extracats[] = $cat;
									}
								}
								else $post_extracats[] = $catsmapped[ $value ];
							}
							break;
						case 'DATE':
							$post_date = strtotime($value);
							$post_date = date('Y-m-d H:i:s', $post_date);
							break;
						default:
							echo "\n<p class=\"notes\">Unknown key [$key]: $value";
							break;
					}
				} // End foreach (metadata)

				debug_dump($post_category, 'post_category');
				debug_dump($post_extracats, 'post_extracats');

				// Let's check to see if it's in already
				if ($DB->get_var("SELECT ID FROM $tableposts WHERE post_title = '$post_title' AND post_issue_date = '$post_date'")) {
					echo 'Post already imported.';
				}
				else
				{ // insert post
					$post_author = checkauthor($post_author);//just so that if a post already exists, new users are not created by checkauthor

					if( $post_author == -1 )
						continue;  // user ignored

					$post_id =
						bpost_create( $post_author, $post_title, $post_content,	$post_date, $post_category, $post_extracats,
													$post_status,	$post_locale,	'' /* $post_trackbacks */, $post_convert_breaks, true /* $pingsdone */,
													'' /* $post_urltitle */, '' /* $post_url */, $comment_status, $default_renderers );

					echo ' Post imported successfully...';


					if( count($comments) )
					{ // comments
						$comments = explode("-----\nCOMMENT:", $comments[0]);
						foreach ($comments as $comment)	if( '' != trim($comment) )
						{
							// Author
							preg_match("|AUTHOR:(.*)|", $comment, $comment_author);
							$comment_author = addslashes(trim($comment_author[1]));
							$comment = preg_replace('|(\n?AUTHOR:.*)|', '', $comment);

							preg_match("|EMAIL:(.*)|", $comment, $comment_email);
							$comment_email = addslashes(trim($comment_email[1]));
							$comment = preg_replace('|(\n?EMAIL:.*)|', '', $comment);

							preg_match("|IP:(.*)|", $comment, $comment_ip);
							$comment_ip = trim($comment_ip[1]);
							$comment = preg_replace('|(\n?IP:.*)|', '', $comment);

							preg_match("|URL:(.*)|", $comment, $comment_url);
							$comment_url = addslashes(trim($comment_url[1]));
							$comment = preg_replace('|(\n?URL:.*)|', '', $comment);

							preg_match("|DATE:(.*)|", $comment, $comment_date);
							$comment_date = trim($comment_date[1]);
							$comment_date = date('Y-m-d H:i:s', strtotime($comment_date));
							$comment = preg_replace('|(\n?DATE:.*)|', '', $comment);

							$comment_content = addslashes(trim($comment));
							$comment_content = str_replace('-----', '', $comment_content);

							// Check if it's already there
							if( !$DB->get_row("SELECT * FROM $tablecomments WHERE comment_date = '$comment_date' AND comment_content = '$comment_content'") )
							{
								$DB->query( "INSERT INTO $tablecomments( comment_post_ID, comment_type, comment_author_ID, comment_author,
																											comment_author_email, comment_author_url, comment_author_IP,
																											comment_date, comment_content)
													VALUES( $post_ID, 'comment', 'NULL', ".$DB->quote($comment_author).",
																	".$DB->quote($comment_email).",	".$DB->quote($comment_url).",
																	'".$DB->escape($comment_ip)."', '$comment_date', '".$DB->escape($comment)."' )" );
								$DB->query( $query );

								echo ' Comment added.';
							}
						}
					}

					// Finally the pings
					// fix the double newline on the first one
					if( count($pings) )
					{
						$pings[0] = str_replace("-----\n\n", "-----\n", $pings[0]);
						$pings = explode("-----\nPING:", $pings[0]);
						foreach ($pings as $ping) if ('' != trim($ping))
						{
							// 'Author'
							preg_match("|BLOG NAME:(.*)|", $ping, $comment_author);
							$comment_author = addslashes(trim($comment_author[1]));
							$ping = preg_replace('|(\n?BLOG NAME:.*)|', '', $ping);

							$comment_email = '';

							preg_match("|IP:(.*)|", $ping, $comment_ip);
							$comment_ip = trim($comment_ip[1]);
							$ping = preg_replace('|(\n?IP:.*)|', '', $ping);

							preg_match("|URL:(.*)|", $ping, $comment_url);
							$comment_url = addslashes(trim($comment_url[1]));
							$ping = preg_replace('|(\n?URL:.*)|', '', $ping);

							preg_match("|DATE:(.*)|", $ping, $comment_date);
							$comment_date = trim($comment_date[1]);
							$comment_date = date('Y-m-d H:i:s', strtotime($comment_date));
							$ping = preg_replace('|(\n?DATE:.*)|', '', $ping);

							preg_match("|TITLE:(.*)|", $ping, $ping_title);
							$ping_title = addslashes(trim($ping_title[1]));
							$ping = preg_replace('|(\n?TITLE:.*)|', '', $ping);

							$comment_content = addslashes(trim($ping));
							$comment_content = str_replace('-----', '', $comment_content);

							$comment_content = "<trackback /><strong>$ping_title</strong>\n$comment_content";

							// Check if it's already there
							if (!$DB->get_row("SELECT * FROM $tablecomments WHERE comment_date = '$comment_date' AND comment_content = '$comment_content'"))
							{
								$DB->query("INSERT INTO $tablecomments
								(comment_post_ID, comment_type, comment_author, comment_author_email, comment_author_url,
								comment_author_IP, comment_date, comment_content )
								VALUES
								($post_id, 'trackback', ".$DB->quote($comment_author).", ".$DB->quote($comment_email).", ".$DB->quote($comment_url).",
								".$DB->quote($comment_ip).", ".$DB->quote($comment_date).", ".$DB->quote($comment_content).", '1')");
								echo ' Trackback added.';
							}
						}
					}
				}
				echo "</li>";
				flush();
			}
			?>
			</ol>
			<h4>All done.</h4>
			<a href="<?php echo $admin_dirout ?>">Have fun in your blogs</a> or <a href="<?php echo $admin_url ?>">go to admin</a> (it's fun there, too)</h3>
			<?php
			if(	!$atleastoneusercreated )
			{
				echo '<p>Please note that the new users being created are not member of any blog yet. You\'ll have to setup this in the <a href="'.$admin_url.'/b2blogs.php">blogs admin</a>.</p>';
			}
			?>
			</div>
		<?php
		}
		break;

} // switch $tab

?>
<div class="panelinfo">
	<p>
		Feel free to <a href="http://thequod.de/contact">contact me</a> in case of suggestions, bugs and lack of clarity.
		Of course, you're also welcome to <a href="https://sourceforge.net/donate/index.php?user_id=663176">donate to me</a> or <a href="http://sourceforge.net/donate/index.php?group_id=85535">the b2evolution project</a>.. :)
	</p>
</div>
<div class="clear">
<?php if( $output_debug_dump ) $DB->dump_queries() ?>
</div>
</div>
</div>
</body>
</html>




<?php
function fieldset_cats()
{
	global $cache_blogs, $cache_categories;
	?>
	<fieldset title="default categories set" class="extracats">
		<legend><?php echo T_('Categories') ?></legend>

		<div>
		<p class="extracatnote"><?php echo T_('Select main category in target blog and optionally check additional categories') ?>:</p>
		<p class="extracatnote">You only need this if you want to map a MT category to a default categories set.</p>

		<?php
		// ----------------------------  CATEGORIES ------------------------------
		$default_main_cat = 0;
		$blog = 1;

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
				echo ' />';
			}

			// Radio for main cat:
			if( $current_blog_ID == $blog )
			{
				if( ($default_main_cat == 0) && ($action == 'post') )
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
		function cat_select_after_each( $cat_ID, $level )
		{	// callback after each sublist element
			echo "</li>\n";
		}
		function cat_select_after_last( $parent_cat_ID, $level )
		{	// callback to end sublist
			echo "</ul>\n";
		}

		// go through all blogs with cats:
		foreach( $cache_blogs as $i_blog )
		{ // run recursively through the cats
			$current_blog_ID = $i_blog->blog_ID;
			if( ! blog_has_cats( $current_blog_ID ) ) continue;
			#if( ! $current_User->check_perm( 'blog_post_statuses', 'any', false, $current_blog_ID ) ) continue;
			echo "<h4>".$i_blog->blog_name."</h4>\n";
			cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
										'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
		}
		// ----------------- END RECURSIVE CAT LIST ----------------
		?>
		</div>
	</fieldset>
<?php
}


/** Category option list **/
	/** internal callback functions **/
	function cat_options_before_first( $cat_ID, $level )
	{
	}
	function cat_options_after_last( $cat_ID, $level )
	{
	}

	function cat_options_before_each( $cat_ID, $level )
	{
		global $cache_categories, $forcat;
		echo '<option value="'.$cat_ID.'"';
		if( $cache_categories[ $cat_ID ]['cat_name'] == $forcat ) echo ' selected';
		echo '>';

		for( $i = 0; $i < $level; $i++ )
		{
			echo '-';
		}

		echo ' '.format_to_output( $cache_categories[ $cat_ID ]['cat_name'], 'htmlbody' ).'</option>';
	}
	function cat_options_after_each( $cat_ID, $level )
	{
	}

function cats_optionslist( $forcat )
{
	global $cache_categories, $cache_blogs;

	foreach( $cache_blogs as $i_blog )
	{
		echo '<option value="#NEW#'.$i_blog->blog_ID.'">[-- create in blog '.$i_blog->blog_shortname.' --]</option>';
		cat_children( $cache_categories, $i_blog->blog_ID, NULL, 'cat_options_before_first',
									'cat_options_before_each', 'cat_options_after_each', 'cat_options_after_last', 1 );
	}
}


/**
 * extracts unique authors and cats from posts array
 */
function import_data_extract_authors_cats()
{
	global $authors, $categories, $posts;

	$datalines = file(MTEXPORT); // Read the file into an array
	$importdata = implode('', $datalines); // squish it
	$importdata = preg_replace("/(\r\n|\n|\r)/", "\n", $importdata);

	$posts = explode("--------", $importdata);

	$authors = array(); $categories = array();
	$tempauthors = array(); $tempcategories = array();

	$tempcategories = array();
	foreach ($posts as $post)
	{
		if ('' != trim($post))
		{
			preg_match_all( '/(PRIMARY )?CATEGORY: (.*?)\n/', $post, $matches );
			foreach( $matches[2] as $cat )
			{
				$cat = trim($cat);
				if( empty($cat) ) $cat = '[no category assigned]';
				$tempcategories[] = $cat;
			}

			preg_match("|AUTHOR:(.*)|", $post, $thematch);
			array_push($tempauthors, trim($thematch[1])); //store the extracted author names in a temporary array
		}
	}

	// we need to find unique values of author names, while preserving the order, so this function emulates the unique_value(); php function, without the sorting.
	$authors[0] = array_shift($tempauthors);
	$y = count($tempauthors) + 1;
	for ($x = 1; $x < $y; $x++) {
		$next = array_shift($tempauthors);
		if( !(in_array($next,$authors)) ) array_push($authors, "$next");
	}
	$categories[0] = array_shift( $tempcategories );
	$y = count($tempcategories) + 1;
	for ($x = 1; $x < $y; $x++) {
		$next = array_shift($tempcategories);
		if( !(in_array($next, $categories)) ) array_push($categories, "$next");
	}
}


// outputs renderer list
function renderer_list()
{
	global $Renderer, $renderers;

	$renderers = array('default');
	$Renderer->restart();	 // make sure iterator is at start position
	while( $loop_RendererPlugin = $Renderer->get_next() )
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
		<label for="<?php $loop_RendererPlugin->code() ?>" title="<?php	$loop_RendererPlugin->short_desc(); ?>"><strong><?php echo $loop_RendererPlugin->name(); ?></strong></label>
	</div>
	<?php
	}
}


function dieerror( $message )
{
	die( '<div class="error"><p>'.$message.'</p></div>
	</div></body></html>' );
}

function debug_dump( $var, $title = '')
{
	global $output_debug_dump;

	if( $output_debug_dump )
	{
		pre_dump( $var, $title );
	}
}

?>
