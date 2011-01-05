<?php
/**
 * File to handle the maintaining of the static html files of this package
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package internal
 * @author blueyed: Daniel HAHLER
 */

/**
 * Load config
 */
require( dirname( __FILE__ ).'/../blogs/conf/_config.php' );

define( 'EVO_MAIN_INIT', true );

/**#@+
 * Load required functions
 */
require_once $inc_path.'_core/_class'.floor(PHP_VERSION).'.funcs.php';
require_once $inc_path.'_core/_misc.funcs.php';
load_class('_core/model/_log.class.php', 'Log');
load_funcs('_core/_param.funcs.php');
load_funcs('_core/ui/forms/_form.funcs.php');
/**#@-*/

$Debuglog = new Log();


$pofilepath = dirname(__FILE__).'/langfiles';

// ------------------- CONFIG ------------------------
// TODO: use other markers and do not use it for replacement tags like
//       {{{trans_current}}}, which only gets replaced by the current locale
define( 'TRANSTAG_OPEN', '{{{' );
define( 'TRANSTAG_CLOSE', '}}}' );
define( 'CHDIR_TO_BLOGS', '..' );
define( 'STATIC_POT', $pofilepath.'/static.pot' );
define( 'DEFAULT_TARGET', 'en-US' );
define( 'DEFAULT_CHARSET', 'iso-8859-1' );

param('highlight_untranslated', 'integer', 0 );
param('action', 'string', '' );


// look what translations we have
$pofiles = glob( $pofilepath.'/*.static.po' );
$targets[ DEFAULT_TARGET ] = '';

// add targets that use same message file
foreach( $locales as $key => $value )
{
	if( $value['enabled'] && substr($value['messages'], 0, 2 ) == substr( $locales[ DEFAULT_TARGET ]['messages'], 0, 2 ) )
	{
		$targets[ $key ] = '';
	}
}

// Discover targets from *.static.po files
foreach( $pofiles as $po )
{
	$target = basename( $po, '.static.po' );
	$targets[ $target ] = $po;

	/*
	// add targets that use same message file
	foreach( $locales as $key => $value ) if( $key != $target )
	{
		if( $value['enabled'] && $value['messages'] == $locales[ $target ]['messages'] )
		{
			$targets[ $key ] = $po;
		}
	}
	*/
}


if( !isset($argv) )
{ // html head
	?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>b2evo :: static page generation</title>
		<link href="../blogs/skins_adm/chicago/rsc/css/chicago.css" rel="stylesheet" type="text/css" />
	</head>
	<body style="background:white">
	<div class="pblock" style="width:800px; margin:10px auto">
    <div class="pan_left"><div class="pan_right"><div class="pan_top"><div class="pan_tl"><div class="pan">
    <div class="panelblock">
	<img src="<?php echo $adminskins_url ?>chicago/rsc/img/b2evolution-footer-logo-blue-bg.gif" />
    <br />
    
<?php
}

log_('<hr />');
log_('This script maintains the static html files of the b2evolution project.');
log_('written by <a href="http://thequod.de/contact">daniel hahler</a>, 2004');
log_('<hr />');

if( isset($argv) )
{ // commandline mode
	if( isset($argv[1]) && in_array($argv[1], array('extract', 'merge')) )
	{
		$action = $argv[1];
	}
	else
	{
		echo "
Usage: $argv[0] <extract|merge>
extract: extracts all translatable strings into ".STATIC_POT.".
merge: creates all static files for which there are .po files in the current directory.
\ntargets: ".implode(', ', array_keys( $targets ))."
";
		exit;
	}
}
elseif( $action == '' )
{
	htmlmenu();
}

function htmlmenu()
{
	global $targets, $highlight_untranslated;
	
	echo '
	<div class="fieldset_wrapper fieldset">
	  <div class="fieldset_title">
		<div class="fieldset_title_right">
		  <div class="fieldset_title_bg">Create static files from locales .po files</div>
		</div>
	  </div>
	  <form method="get" class="fform">
	    <input type="hidden" name="action" value="merge" />
		<fieldset class="fieldset">
		  <fieldset>
			<div class="label"><label>Highlight untranslated strings:</label></div>
			<div class="info">
			<input type="checkbox" value="1" name="highlight_untranslated" '.( ($highlight_untranslated) ? 'checked="checked"' : '' ).' />
			(targets: '.implode(', ', array_keys( $targets )).')
			</div>
		  </fieldset>
		  <fieldset><div class="input"><input type="submit" value="Create" class="search" /></div></fieldset>
		</fieldset>
	  </form>
	</div>
	
	<div class="fieldset_wrapper fieldset">
	  <div class="fieldset_title">
		<div class="fieldset_title_right">
		  <div class="fieldset_title_bg">Extract translatable strings into .POT file</div>
		</div>
	  </div>
	  <form method="get" class="fform">
	    <input type="hidden" name="action" value="extract" />
		<fieldset class="fieldset">
		  <fieldset>
		  <div class="label">Static POT file:</div>
		  <div class="info">'.str_replace( '\\', '/', STATIC_POT ).'</div>
		  </fieldset>
		  <fieldset><div class="input"><input type="submit" value="Extract" class="search" /></div></fieldset>
		</fieldset>
	  </form>
	</div>';
};


/**
 * output, respects commandline mode
 */
function log_( $string )
{
	global $argv;
	if( isset($argv) )
	{ // command line mode
		if( $string == '<hr />' )
			echo '------------------------------------------------------------------------------';
		else
		{ // remove tags
			$string = strip_tags($string);
			echo $string;
		}
		echo "\n";
	}
	else
	{
		echo $string."<br />\n";
	}
}


// HERE WE GO -------------------------------

if( $action )
{
	log_('<div class="panelinfo"><p><strong>action: '.$action.'</strong></p>'
				.( ( $highlight_untranslated && $action == 'merge' ) ? 'note: untranslated strings will get highlighted!' : '' )
				.'</div>'
			);
}

// change to /blogs folder
chdir( CHDIR_TO_BLOGS );
#pre_dump( getcwd(), 'cwd' );

// get the source files
$srcfiles = array();

foreach( array( '.', 'doc' ) as $dir )
{
	if( $fp = opendir($dir) )
	{
		while( ($file = readdir($fp) ) !== false )
		{
			if( $dir != '.' )
			{
				$file = $dir.'/'.$file;
			}
			if( is_file($file) && preg_match('/\.src\.html$/', $file))
			{
				$srcfiles[] = $file;
			}
		}
		closedir($fp);
	}
	else log( 'could not open directory '.$dir );
}

// echo '<hr />'; pre_dump( $srcfiles, 'source files' ); echo '<hr />';


switch( $action )
{
	case 'extract':
		load_class('locales/_pofile.class.php', 'POTFile');
		$POTFile = new POTFile( STATIC_POT );

		foreach( $srcfiles as $srcfile )
		{
			log_( 'Extracting '.$srcfile.'..' );

			// get source file content
			$text = implode( '', file( $srcfile ) );
			// get all strings to translate
			preg_match_all('/'.TRANSTAG_OPEN.'(.*?)'.TRANSTAG_CLOSE.'/s', $text, $matches_msgids, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);
			// get all newlines (to assign source file line numbers to msgids later)
			preg_match_all('/\n/', $text, $matches_line, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE);

			$lm = 0;  // represents line numer - 1
			foreach( $matches_msgids[1] as $match )
			{
				while( isset($matches_line[0][$lm]) && ($match[1] > $matches_line[0][ $lm ][1]) )
				{ // assign line numbers
					$lm++;
				}

				// remove newlines, tabs and carriage returns:
				$msgid = preg_replace( '|[\n\t\r]+|', ' ', $match[0] );
				$POTFile->addmsgid( $msgid, $srcfile.':'.($lm + 1) );
				#log_(' ['.$srcfile.':'.($lm + 1).']<br />');
			}
		}

		// write POT file
		$POTFile->write();
	break;

	case 'merge':
		load_class('locales/_pofile.class.php', 'POFile');

		foreach( $targets as $target => $targetmessagefile )
		{ // loop targets/locales
			log_('<h2 style="margin-bottom:0">TARGET: '.$target.'</h2>');
			if( $targetmessagefile != '' )
			{ // only translate when not DEFAULT_TARGET
				log_( 'reading .po file: '.basename( $targetmessagefile ) );

				$POFile = new POFile( $targetmessagefile );
				$POFile->read();

				// get charset out of .PO file header
				if( preg_match( '/; charset=(.*?)\n/', $POFile->translate(''), $matches ) )
				{
					$charset = $matches[1];
					if( $charset == 'CHARSET' )
					{
						log_('Invalid charset "'.$charset.'". Will use default, '.DEFAULT_CHARSET);
						$charset = DEFAULT_CHARSET;
					}
					log_('Charset: '.$charset);
				}
				else
				{
					log_('<span style="color:red">WARNING: no charset found. Will use '.DEFAULT_CHARSET.'.</span>');
					$charset = DEFAULT_CHARSET;
				}
			}
			else
			{ // no $targetmessagefile, so we don't translate
				$charset = DEFAULT_CHARSET;
				log_( 'building default files');
				$POFile = new POFile('');
			}
			$replacesrc = $target != DEFAULT_TARGET ?
											'.'.$target.'.' :
											'.';


			foreach( $srcfiles as $srcfile )
			{ // loop through sourcefiles
				// the file to create
				$newfilename = str_replace('.src.', $replacesrc, $srcfile);

				log_( 'Merging '.$srcfile.' into '.$newfilename );
				$text = implode( '', file( $srcfile ) );

				$path_to_root = '';
				for($i = 1; $i < count(split('/', $srcfile)); $i++)
				{
					$path_to_root = '../'.$path_to_root;
				}


				// --- build "available translations" list -------------
				locale_activate( $target );  // activate locale to translate locale names

				// Sort the targets by their translated name
				$sortedTargets = $targets;
				ksort( $sortedTargets );

				$trans_available = "\n";
				foreach( $sortedTargets as $ttarget => $ttargetmessagefile )
				{ // the link to the static html file for that target message file
					$linkto = str_replace('.src.', ( $ttarget != DEFAULT_TARGET ) ? ".$ttarget." : '.', basename($srcfile) );

					$trans_available .=
					'<a href="'.$linkto.'">'
					// title="'.T_( $locales[$ttarget]['name'] ).'"
					.locale_flag($ttarget, 'h10px', 'flag', '', false, $path_to_root.'blogs/rsc/flags')
					.'</a>'."\n";
				}
				$trans_available .= "\n";

				$text = str_replace( TRANSTAG_OPEN.'trans_available'.TRANSTAG_CLOSE, $trans_available, $text );

				// Current lang:
				$text = str_replace( TRANSTAG_OPEN.'trans_current'.TRANSTAG_CLOSE, $target, $text );
				// T_( $locales[$target]['name'] )

				// standard replacements
				$search = array(
					// internal replacements
					TRANSTAG_OPEN.'trans_locale'.TRANSTAG_CLOSE,
					urlencode( TRANSTAG_OPEN.'trans_locale'.TRANSTAG_CLOSE ), // DW fix
					TRANSTAG_OPEN.'trans_charset'.TRANSTAG_CLOSE,
					'<html', // add note about generator
				);
				$replace = array(
					$target,
					$target,
					$charset,
					'<!-- This file was generated automatically by /gettext/staticfiles.php - Do not edit this file manually -->'."\n".'<html'
				);
				$text = str_replace( $search, $replace, $text);


				// emphasize links to start page (small-caps)
				#pre_dump('/index.([a-z]{2}-[A-Z]{2}(-.{1,14})?.)?html/', $newfilename );
				/*if( preg_match( '/index.([a-z]{2}-[A-Z]{2}(-.{1,14})?.)?html/', $newfilename ) )
				{ // start page is current
					$text = preg_replace( '/(<a .*?>)({{{Start page}}})(<\/a>)/s', '$1<span style="font-variant:small-caps">$2</span>$3', $text );
				}*/

				// emphasize current flag link (<strong>)
				$text = preg_replace( '/(<a[^>]+href="(..\/)?'.basename($newfilename).'(\?.*?)?"[^>]*><img .*?>)(.+?)(<\/a>)/s',
															'$1<strong>$4</strong>$5',
															$text);


				if( $targetmessagefile != '' )
				{ // translate everything
					$text = preg_replace( '/'.TRANSTAG_OPEN.'(.*?)'.TRANSTAG_CLOSE.'/es', '$POFile->translate( preg_replace( \'|[\n\t\r]+|\', \' \', stripslashes( \'$1\' ) ) )', $text );

					if( strpos( $text, TRANSTAG_OPEN ) !== false )
					{ // there are still tags.
						#pre_dump( $text, substr( $text, strpos( $text, TRANSTAG_OPEN ), 30 ) );
						log_('<span style="color:blue">WARNING: some strings have not been translated!</span>');
					}
				}


				// handle left TRANSTAGs
				if( $highlight_untranslated && $targetmessagefile != '' )
				{ // we want to highlight untranslated strings
					$text = str_replace( array(TRANSTAG_OPEN, TRANSTAG_CLOSE), array('<span style="color:red" title="not translated">', '</span>'), $text );
				}
				else
				{ // just remove tags
					$text = str_replace( array(TRANSTAG_OPEN, TRANSTAG_CLOSE), '', $text );
				}


				// replace links
				$text = preg_replace( '/\.src\.(html)/', $replacesrc."$1", $text );


				// remove DW tags
				$text = preg_replace( '/<!-- Instance(Begin|End|Param).*? -->/', '', $text );

				$fh = @fopen( $newfilename, 'w' );
				if( ! $fh )
				{
					log_( sprintf('<p class="error">Could not open %s for writing!</p>', $newfilename) );
				}
				else
				{
					fwrite( $fh, $text );
					fclose( $fh );
				}
			}
			log_('');
		}

	break;
}


if( !isset($argv) )
{
	if( !empty($action) )
		htmlmenu();
	echo '</div></div>
</div></div></div></div>
<div class="pan_bot"><div class="pan_bl"><div class="pan_br"></div></div></div>
</div></body></html>';
}

?>
