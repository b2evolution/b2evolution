<?php 
/**
 * backoffice page footer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
</div>

<p class="footer">
<strong><span style="color:#333333">b</span><span style="color:#ff9900">2</span><span style="color:#333333">e</span><span style="color:#554433">v</span><span style="color:#775522">o</span><span style="color:#996622">l</span><span style="color:#bb7722">u</span><span style="color:#cc8811">t</span><span style="color:#dd9911">i</span><span style="color:#ee9900">o</span><span style="color:#ff9900">n</span></strong> <?php echo $b2_version, ' '; ?>
-
<a href="http://b2evolution.net/about/license.html" class="nobr"><?php echo T_('GPL License') ?></a>
-
<span class="nobr">&copy;2001-2002 by <a href="http://cafelog.com/">Michel V</a></span>
-
<span class="nobr">&copy;2003-2004 by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a></span>
</p>

<!-- this is for the spellchecker -->
<form action="" name="SPELLDATA"><div>
<input name="formname" type="hidden" value="" />
<input name="messagebodyname" type="hidden" value="" />
<input name="subjectname" type="hidden" value="" />
<input name="companyID" type="hidden" value="" />
<input name="language" type="hidden" value="" />
<input name="opener" type="hidden" value="" />
<input name="formaction" type="hidden" value="" />
</div></form>

<?php debug_info(); ?>

</body>
</html>