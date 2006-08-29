<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{$title}</title>
	<link rel="stylesheet" type="text/css" href="{$subdir}media/style.css" />
	<link rel="stylesheet" type="text/css" href="{$subdir}media/rsc/css/evonet2.css" />
	<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
</head>
<body>


<div id="body_header">	
	<!-- Start of page header -->
	<div class="shade">

		<h1 id="header_logo"><a href="http://b2evolution.net/" title="b2evolution: Home"><img src="{$subdir}media/rsc/img/b2evolution_logo_transp.gif" alt="b2evolution" width="472" height="90" border="0" /></a></h1>
		<h2 id="tagline">Multilingual multiuser multiblog engine</h2>
	
		<table class="main_menu" cellspacing="0">
			<tr>
				<td class=""><a href="http://b2evolution.net/index.php" title="b2evolution main page">Home</a></td>
				<td class=""><a href="http://b2evolution.net/index.html" title="Read all about b2evolution">About</a></td>
				<td class=""><a href="http://demo.b2evolution.net/" title="See for yourself with the online demo">Demo</a></td>
				<td class=""><a href="http://b2evolution.net/downloads/index.html" title="Get your own b2evolution, it's free!">Download</a></td>
				<td class=""><a href="http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php" title="Get a first class host for your blog">Hosting</a></td>
				<td class=""><a href="http://b2evolution.net/downloads/extend.html" title="Get more with skins, plugins and language packs">Extend</a></td>
				<td class=""><a href="http://manual.b2evolution.net/" title="Get more documentation, howtos and tutorials from the manual">Docs</a></td>
				<td class=""><a href="http://forums.b2evolution.net/" title="Get answers to your questions in our forums">Support</a></td>
			</tr>
		</table>
	
	</div>
	
<!--	
	<div class="sub_menu">
		<a class="current" href="index.php">Main</a> &middot; 
		<a class="" href="news.php">News</a> &middot; 
		<a class="" href="dev/donations.php">Donations</a> &middot; 
		<a class="" href="about/userblogs.php">User blogs</a> &middot; 
		<a class="" href="dev/authors.html">Team</a> 
	</div>
-->
	
	<!-- End of page header -->
</div>


<div id="main-wrapper">
<div id="main-outer">
<div id="float-wrapper">

<div class="header-menu">
	<small>{$maintitle}</small>
  		  [ <a href="{$subdir}classtrees_{$package}.html" class="menu">class tree: {$package}</a> ]
		  [ <a href="{$subdir}elementindex_{$package}.html" class="menu">index: {$package}</a> ]
		  [ <a href="{$subdir}elementindex.html" class="menu">all elements</a> ]
</div>


<!--
<div class="header-packages">
	<span class="header-top-right">{$package}</span>
	{if $subpackage}
		&mdash; <span class="header-top-right-subpackage">{$subpackage}</span>
	{/if}
</div>
-->


<div class="content">
{if !$hasel}{assign var="hasel" value=false}{/if}
{if $eltype == 'class' && $is_interface}{assign var="eltype" value="interface"}{/if}
{if $hasel}
<h1>{$eltype|capitalize}: {$class_name}</h1>

<p>Source Location: {$source_location}</p>
{/if}
