<?php
/**
 * This is b2evolution's application config file.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */


$app_name = 'b2evolution';
$app_shortname = 'b2evo';
$app_version = '0.9.2-CVS';


$admin_path_seprator = ' :: ';
$app_admin_logo = '<a href="http://b2evolution.net/" title="'.T_("visit b2evolution's website").
									'"><img id="evologo" src="../img/b2evolution_minilogo2.png" alt="b2evolution" title="'.
									T_("visit b2evolution's website").'" width="185" height="40" /></a>';
$app_exit_links = '<a href="'.$htsrv_url.'login.php?action=logout">'.T_('Logout').'</a>
									&bull;
									<a href="'.$baseurl.'">'.T_('Exit to blogs').'
									<img src="img/close.gif" width="14" height="14" class="top" alt="" title="'
									.T_('Exit to blogs').'" /></a><br />';

$app_footer_text = '<a href="http://www.b2evolution.net"><strong><span style="color:#333">b</span><sub><span style="color:#f90;margin-top:2ex;">2</span></sub><span style="color:#333">e</span><span style="color:#543">v</span><span style="color:#752">o</span><span style="color:#962">l</span><span style="color:#b72">u</span><span style="color:#c81">t</span><span style="color:#d91">i</span><span style="color:#e90">o</span><span style="color:#f90">n</span></strong> '.$app_version.'</a>
		&ndash;
		<a href="http://b2evolution.net/about/license.html" class="nobr">'.T_('GPL License').'</a>
		&ndash;
		<span class="nobr">&copy;2001-2002 by <a href="http://cafelog.com/">Michel V</a> &amp; others</span>
		&ndash;
		<span class="nobr">&copy;2003-2005 by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> &amp; others.</span>';

?>
