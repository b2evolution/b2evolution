</div>

<?php
if ($debug=="1") 
{
	printf( _('<p>%d queries - %01.3f seconds</p>'), $querycount, timer_stop() );
}
?>

<p class="footer"><strong><span style="color:#333333">b</span><span style="color:#ff9900">2</span><span style="color:#333333">e</span><span style="color:#554433">v</span><span style="color:#775522">o</span><span style="color:#996622">l</span><span style="color:#bb7722">u</span><span style="color:#cc8811">t</span><span style="color:#dd9911">i</span><span style="color:#ee9900">o</span><span style="color:#ff9900">n</span></strong> <?php echo $b2_version ?> - <a href="http://b2evolution.net/about/license.html"><?php echo _('GPL License') ?></a> - &copy; 2001-2002 by <a href="http://cafelog.com/">Michel V</a> - &copy; 2003 by <a href="http://www.fplanque.net/2003/b2evolution/">Fran&ccedil;ois PLANQUE</a></p>

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