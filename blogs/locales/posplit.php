<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

include( dirname(__FILE__).'/../conf/_config.php' );
include( dirname(__FILE__).'/../b2evocore/_functions.php' );

if( ! $allow_po_extraction )
	die( 'PO extraction is currently not allowed.' );

param( 'locale', 'string', true );

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $locales[$locale]['charset'] ?>" />
	<title>Localization Status Report for b2evolution</title>
	<link rel="stylesheet" type="text/css" href="report.css">
</head>
<body>
<?php


$filename = $locale.'/LC_MESSAGES/messages.po';

echo '<h1>Splitting ', $filename, '</h1>';

// Get PO file for that locale:
$lines = file( dirname(__FILE__).'/'.$filename);
$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file
$all = 0;
$fuzzy=0;
$untranslated=0;
$translated=0;
$status='-';
$matches = array();
$sources = array();
$loc_vars = array();
$trans = array();
foreach ($lines as $line) 
{
	// echo 'LINE:', $line, '<br />';
	if(trim($line) == '' )	
	{	// Blank line, go back to base status:
		if( $status == 't' )
		{	// ** End of a translation **:
			if( $msgstr == '' )
			{
				$untranslated++;
				// echo 'untranslated: ', $msgid, '<br />';
			}
			else
			{
				$translated++;
				
				// Inspect where the string is used
				$sources = array_unique( $sources );
				// echo '<p>sources: ', implode( ', ', $sources ), '</p>';
				foreach( $sources as $source )
				{
					if( !isset( $loc_vars[$source]  ) ) $loc_vars[$source] = 1;
					else $loc_vars[$source] ++;
				}

				// Save the string
				$trans[] = "\n\t'".str_replace( "'", "\'", str_replace( '\"', '"', $msgid ))."' => '".str_replace( "'", "\'", str_replace( '\"', '"', $msgstr ))."',";

			}
		}
		$status = '-';
		$msgid = '';
		$msgstr = '';
		$sources = array();
	}
	elseif( ($status=='-') && preg_match( '#^msgid "(.*)"#', $line, $matches)) 
	{	// Encountered an original text
		$status = 'o';
		$msgid = $matches[1];
		// echo 'original: "', $msgid, '"<br />';
		$all++;
	}
	elseif( ($status=='o') && preg_match( '#^msgstr "(.*)"#', $line, $matches)) 
	{	// Encountered a translated text
		$status = 't';
		$msgstr = $matches[1];
		// echo 'translated: "', $msgstr, '"<br />';
	}
	elseif( preg_match( '#^"(.*)"#', $line, $matches)) 
	{	// Encountered a followup line
		if ($status=='o') 
			$msgid .= $matches[1];
		elseif ($status=='t')
			$msgstr .= $matches[1];
	}
	elseif( ($status=='-') && preg_match( '@^#:(.*)@', $line, $matches)) 
	{	// Encountered a source code location comment
		// echo $matches[0],'<br />';
		$sourcefiles = preg_replace( '@\\\\@', '/', $matches[1] );
		// $c = preg_match_all( '@ ../../../([^:]*):@', $sourcefiles, $matches);
		$c = preg_match_all( '@ ../../../([^/:]*)@', $sourcefiles, $matches);
		for( $i = 0; $i < $c; $i++ )
		{
			$sources[] = $matches[1][$i];
		}
		// echo '<br />';
	}
	elseif(strpos($line,'#, fuzzy') === 0) 
		$fuzzy++;
}


ksort( $loc_vars );
foreach( $loc_vars as $source => $c )
{
	echo $source, ' = ', $c, '<br />';
}

echo '<hr />';

$outfile = $locale.'/_global.php';
$fp = fopen( $outfile, 'w+' );
fwrite( $fp, "<?php\n" );
fwrite( $fp, "/*\n" );
fwrite( $fp, " * Global lang file\n" );
fwrite( $fp, " * This file was generated automatically from messages.po\n" );
fwrite( $fp, " */\n" );
fwrite( $fp, "\n\$trans['$locale'] = array(" );
echo '<pre>';
foreach( $trans as $line )
{
	echo htmlspecialchars( $line );
	fwrite( $fp, $line );
}
echo '</pre>';
fwrite( $fp, "\n);\n?>" );
fclose( $fp );

?>


</body>
</html>