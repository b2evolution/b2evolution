<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file is based on code from the GALLERY project.
 */
include( dirname(__FILE__).'/../conf/b2evo_config.php' );
include( dirname(__FILE__).'/../b2evocore/_vars.php' );
include( dirname(__FILE__).'/../b2evocore/_functions.php' );

$handle=opendir(dirname(__FILE__));
$i = 0;
$report = array();
while ($file = readdir($handle)) 
{	// For each file in locales directtory:
	// echo $file, '<br />';
  if(preg_match('#([a-z]){2,2}_([A-Z]){2,2}#', $file, $matches)) 
	{	// If it matches a locale sub directory
		// echo '<hr />';
		$locale=$matches[0];
		$i++;
		// Get PO file for that locale:
		$lines = file("./$file/LC_MESSAGES/messages.po");
		$lines[] = '';	// Adds a blank line at the end in order to ensure complete handling of the file
		$all = 0;
		$fuzzy = 0;
		$this_fuzzy = false;
		$untranslated=0;
		$translated=0;
		$status='-';
		$matches = array();
		foreach ($lines as $line) 
		{
			// echo 'LINE:', $line, '<br />';
			if(trim($line) == '' )	
			{	// Blank line, go back to base status:
				if( $status == 't' )
				{	// ** End of a translation ** :
					if( $msgstr == '' )
					{
						$untranslated++;
						// echo 'untranslated: ', $msgid, '<br />';
					}
					else
					{
						$translated++;
					}
					if( $msgid == '' && $this_fuzzy )
					{	// It's OK if first line is fuzzy
						$fuzzy--;
					}
					$msgid = '';
					$msgstr = '';
					$this_fuzzy = false;
				}
				$status = '-';
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
			elseif(strpos($line,'#, fuzzy') === 0) 
			{
				$this_fuzzy = true;
				$fuzzy++;
			}
		}
		// $all=$translated+$fuzzy+$untranslated;
		$percent_done=round(($translated-$fuzzy/2)/$all*100,2);
		$rpd=round($percent_done,0);
		// $report[$locale]=array ($percent_done,$translated,$fuzzy,$untranslated);
		if($rpd <50) {
			$color=dechex(255-$rpd*2). "0000";
		} else {
			$color="00" . dechex(55+$rpd*2). "00";
		}
		if (strlen($color) <6) $color="0". $color;
		$report[$locale]=array ($color, $percent_done,$translated,$fuzzy,$untranslated,$all);
	}
}
closedir($handle);

function my_usort_function ($a, $b) 
{
	if ($a[1] > $b[1]) { return -1; }
	if ($a[1] < $b[1]) { return 1; }
	return 0;
}

uasort($report, 'my_usort_function');

?>

<html>
<head>
	<title>Localization Status Report for b2evolution</title>
	<link rel="stylesheet" type="text/css" href="report.css">
</head>
<body>
<h1>Localization Status Report for b2evolution</h1>
<table align="center" border="0" cellspacing="0" cellpadding="0">
<tr>
	<th>Locale</th>
	<th>Language</th>
	<th>Charset</th>
	<th>Status</th>
	<th valign="bottom" style="width: 30px;">T<br/>r<br/>a<br/>n<br/>s<br/>l<br/>a<br/>t<br/>e<br/>d</th>
	<th valign="bottom" style="width: 30px;">F<br/>u<br/>z<br/>z<br/>y</th>
	<th valign="bottom" style="width: 30px;">U<br/>n<br/>t<br/>r<br/>a<br/>n<br/>s<br/>l<br/>a<br/>t<br/>e<br/>d</th>
	<th valign="bottom" style="width: 30px;">T<br/>o<br/>t<br/>a<br/>l</th>
<?php
	if( $allow_po_extraction  )
		echo '<th>Extract<br />strings<br />for<br />b2evo</th>';


	echo '</tr>';

$i=0;
foreach ($report as $key => $value) {
$i++;
if ($i%2==0) {
	$color="#dedede";
	$nr=1;
} else {
	$color="#CECECE";
	$nr=2;
}
		echo "\n<tr>";
		$lang = substr( $key, 0, 2 );
		
		echo "\n\t<td style=\"background-color:$color\">". $key, "</td>";
		echo "\n\t<td style=\"background-color:$color\">". $languages[$lang] . "</td>";
		echo "\n\t<td style=\"background-color:$color\">". $locales[$key]['charset'] . "</td>";
		echo "\n\t<td style=\"background-color:#". $value[0] . "\">". $value[1] ."% done</td>";
		echo "\n\t<td class=\"translated$nr\">". $value[2] ."</td>";
		echo "\n\t<td class=\"fuzzy$nr\">". $value [3] . "</td>";
		echo "\n\t<td class=\"untranslated$nr\">". $value[4] ."</td>";
		echo "\n\t<td style=\"background-color:$color\">". $value[5] ."</td>";
		if( $allow_po_extraction  )
			echo "\n\t<td style=\"background-color:$color\">", '[<a href="posplit.php?locale='.$key.'">Extract</a>]</td>';
		echo "\t</tr>";
}
?>
</table>

<?php
	// echo 'test: ', T_('New comment on your post #%d "%s"', $default_locale);
?>
</body>
</html>