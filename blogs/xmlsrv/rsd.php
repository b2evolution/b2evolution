<?php
/**
 * RSD Really Simple Discoverability
 *
 * @see http://archipelago.phrasewise.com/rsd
 * @see http://en.wikipedia.org/wiki/Really_Simple_Discovery
 * @see http://cyber.law.harvard.edu/blogs/gems/tech/rsd.html
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @todo fp> do we want to restrict API definitions to a specific blog with blogID="" ?
 *
 * @package xmlsrv
 *
 * @version $Id$
 */
header('Content-type: text/xml; charset=UTF-8', true);

require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

param( 'blog', 'integer', NULL );

echo '<?xml version="1.0" encoding="UTF-8"?'.'>';
?>
<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd">
	<service>
	  <engineName>b2evolution</engineName>
	  <engineLink>http://b2evolution.net/</engineLink>
	  <homePageLink><?php echo $baseurl; ?></homePageLink>
	  <apis>
	    <api name="MetaWeblog" preferred="true" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php"<?php
	    	if( !empty($blog) ) { echo ' blogID="'.$blog.'"'; }
	    	?>>
				<settings>
					<docs>http://manual.b2evolution.net/MetaWeblog_API</docs>
				</settings>
			</api>
	    <api name="MovableType" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php"<?php
	    	if( !empty($blog) ) { echo ' blogID="'.$blog.'"'; }
	    	?>>
				<settings>
					<docs>http://manual.b2evolution.net/MovableType_API</docs>
				</settings>
			</api>
	    <api name="Blogger" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php"<?php
	    	if( !empty($blog) ) { echo ' blogID="'.$blog.'"'; }
	    	?>>
				<settings>
					<docs>http://manual.b2evolution.net/Blogger_API</docs>
				</settings>
			</api>
	    <api name="b2" preferred="false" apiLink="<?php echo $xmlsrv_url; ?>xmlrpc.php"<?php
	    	if( !empty($blog) ) { echo ' blogID="'.$blog.'"'; }
	    	?>>
				<settings>
					<docs>http://manual.b2evolution.net/B2_API</docs>
				</settings>
			</api>
	  </apis>
	</service>
</rsd>
<?php

/*
 * $Log$
 * Revision 1.2  2008/01/13 03:10:22  fplanque
 * Enhanced RSD
 *
 * Revision 1.1  2008/01/12 22:51:11  fplanque
 * RSD support
 *
 */
?>
