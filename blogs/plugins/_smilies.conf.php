<?php
/**
 * This file hold the configuration for the Image Smilies plugins for b2evolution.
 * These settings apply to both the renderer and the toolbar Smilies plugin.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */

global $img_url;

# the directory where your smilies are (no trailing slash)
$this->smilies_path = $img_url.'/img/smilies';

# here's the conversion table, you can modify it if you know what you're doing
# smilies will be displayed in their order of appearance
$this->smilies = array(
										'~`'				=> 'qm_open.gif',
										'\'~'				=> 'qm_close.gif',
										'=>'				=> 'icon_arrow.gif',
								//	':!:'				=> 'icon_exclaim.gif',
								//	':?:'				=> 'icon_question.gif',
										':idea:'		=> 'icon_idea.gif',
										':)'				=> 'icon_smile.gif',
										':D'				=> 'icon_biggrin.gif',
								//	':DD'				=> 'icon_lol.gif',
								//	':]'				=> 'icon_cheeze.gif',
										':p'				=> 'icon_razz.gif',
										'B)'				=> 'icon_cool.gif',
										';)'				=> 'icon_wink.gif',
										':>'				=> 'icon_twisted.gif',
								//	':o'				=> 'icon_surprised.gif',
								//	'8|'				=> 'icon_eek.gif',
								//	'>:-['			=> 'icon_evil.gif',
										':roll:'		=> 'icon_rolleyes.gif',
										':oops:'		=> 'icon_redface.gif',
										':|'				=> 'icon_neutral.gif',
										':-/'				=> 'icon_confused.gif',
										':('				=> 'icon_sad.gif',
										'>:('				=> 'icon_mad.gif',
										':\'('			=> 'icon_cry.gif',
										'|-|'				=> 'icon_wth.gif',
										':>>'				=> 'icon_mrgreen.gif',
								//	':)'				=> 'graysmile.gif',
								//	':yes:'			=> 'grayyes.gif',
										';D'				=> 'graysmilewinkgrin.gif',
								//	':b'				=> 'grayrazz.gif',
										':P'				=> 'graybigrazz.gif',
										':))'				=> 'graylaugh.gif',
										'88|'				=> 'graybigeek.gif',
								//	')-o'				=> 'grayembarrassed.gif',
										':.'				=> 'grayshy.gif',
								//	'U-('				=> 'grayuhoh.gif',
								//	':('				=> 'graysad.gif',
								//	':**:'			=> 'graysigh.gif', 			// alternative: graysighw.gif
								//	':??:'			=> 'grayconfused.gif',  // alternative: grayconfusedw.gif
								//	':no:'			=> 'grayno.gif',
								//	':`('				=> 'graycry.gif',
								//	'>:-('			=> 'graymad.gif',
								//	':##'				=> 'grayupset.gif',			// alternative: grayupsetw.gif
										'XX('				=> 'graydead.gif',
								//	':zz:'			=> 'graysleep.gif', 		// alternative: graysleepw.gif
								//	':yawn:'		=> 'icon_yawn.gif',
								//	':wave:'		=> 'icon_wave.gif',
										':lalala:'	=> 'icon_lalala.gif',
										':crazy:'		=> 'icon_crazy.gif',
								//	'>:XX'			=> 'icon_censored.gif',
									);

// echo 'Smilies: ', count( $this->smilies );

?>
