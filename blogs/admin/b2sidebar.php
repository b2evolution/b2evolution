<?php
/* <Sidebar> */

$mode = 'sidebar';

$standalone = 1;
require_once (dirname(__FILE__)."/b2header.php");

get_currentuserinfo();

// just your usual browser thing because we're still too far from standards
$is_gecko = preg_match("/Gecko/",$HTTP_USER_AGENT);
$is_winIE = ((preg_match("/MSIE/",$HTTP_USER_AGENT)) && (preg_match("/Win/",$HTTP_USER_AGENT)));
$is_macIE = ((preg_match("/MSIE/",$HTTP_USER_AGENT)) && (preg_match("/Mac/",$HTTP_USER_AGENT)));
$is_IE    = (($is_macIE) || ($is_winIE));

if ($user_level == 0)
die (_('Cheatin\' uh ?'));

$request = " SELECT * FROM $tablesettings ";
$result = mysql_query($request);
while($row = mysql_fetch_object($result)) {
	$time_difference=$row->time_difference;
	$autobr=$row->AutoBR;
}
?>
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title>b2evo &gt; sidebar</title>
	<link rel="stylesheet" href="b2.css" type="text/css">
<script type="text/javascript" language="javascript" src="<?php echo $spch_url; ?>"></script>
<style type="text/css">
<!--
body {
	background-image:  url(img/b2minilogo.png);
	background-repeat: no-repeat;
	background-position: 50px 90px;
	padding: 3px;
}
textarea,input,select {
	font-family: arial,helvetica,sans serif;
	font-size: 12px;
	background-color: transparent;
<?php if ($is_gecko || $is_macIE) { ?>
	background-image: url('img/bgbookmarklet.png');
<?php } elseif ($is_winIE) { ?>
	background-color: #dddddd;
	filter: alpha(opacity:80);
<?php } ?>
	border-width: 1px;
	border-color: #cccccc;
	border-style: solid;
	padding: 2px;
	margin: 1px;
}
<?php if (!$is_gecko) { ?>
.checkbox {
	background-color: #ffffff;
	border-width: 0px;
	padding: 0px;
	margin: 0px;
}
<?php } ?>
-->
</style>
</head>
<body>
<!--<table width="100%" cellpadding="0" cellspacing="0">
<td><img src="img/b2minilogo.png"></td>
</table>
-->
<form name="post" action="edit_actions.php" method="POST" accept-charset="<?php locale_charset() ?>">
<input type="hidden" name="action" value="post" />
<input type="hidden" name="user_ID" value="<?php echo $user_ID ?>" />
<input type="hidden" name="mode" value="sidebar" />

<input type="text" name="post_title" size="20" tabindex="1" style="width: 100%;" value="Title" onFocus="if (this.value=='Title') { this.value='';}" onBlur="if (this.value=='') {this.value='Title';}" />

<?php dropdown_categories(); ?>

<textarea rows="8" cols="12" style="width: 100%" name="content" tabindex="2" class="postform" wrap="virtual" onFocus="if (this.value=='Post') { this.value='';}" onBlur="if (this.value=='') {this.value='Post';}"><?php echo _('Post') ?></textarea>

<input type="checkbox" name="post_autobr" value="1" <?php if ($autobr) echo ' checked="checked"' ?> tabindex="4" class="checkbox" id="autobr" /><label for="autobr"> <?php echo _('Auto-BR') ?></label><br />

<?php if ($use_pingback) { ?>
<input type="checkbox" class="checkbox" name="post_pingback" value="1" checked="checked" tabindex="5" id="pingback" /><label for="pingback"> <?php echo _('Pingback') ?></label>
<?php } ?>

<input type="submit" name="submit" value="Blog this !" class="search" tabindex="3" /> 

<?php if ($use_spellchecker) { ?>
<br /><input type = "button" value = "<?php echo _('SpellCheck') ?>" onclick="var f=document.forms[0]; doSpell( 'en', f.post_content, '$spellchecker_url/sproxy.cgi', true);" class="search" tabindex="6" /><br />
<?php }

if ($use_trackback) { ?>
<br /><label for="trackback"><?php echo _('<strong>Trackback</strong> an URL:') ?></label><br /><input type="text" name="trackback_url" style="width: 100%" id="trackback" tabindex="7" />
<?php } ?>

<script language="JavaScript">
<!--
//				document.blog.post_content.focus();
//-->
</script>
</td>
</tr>
</table>
</div>

</form>

<!-- this is for the spellchecker -->
<form name="SPELLDATA"><div>
<input name="formname" type="hidden" value="">
<input name="messagebodyname" type="hidden" value="">
<input name="subjectname" type="hidden" value="">
<input name="companyID" type="hidden" value="">
<input name="language" type="hidden" value="">
<input name="opener" type="hidden" value="">
<input name="formaction" type="hidden" value="">
</div></form>

</body>
</html>