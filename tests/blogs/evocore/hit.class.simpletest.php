<?php
/**
 * Tests for the {@link Hit} class.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

load_class( 'sessions/model/_hit.class.php', 'Hit' );

// Helper function
function extract_keyphrase( $url )
{
	$Hit = new Hit();
	$arr = $Hit->extract_params_from_referer( $url );
	return $arr['keyphrase'];
}

/**
 * @package tests
 */
class HitTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Hit class test' );
	}

	function test_extract_keyphrase()
	{
		global $evo_charset;

		$ref_latin1 = 'http://www.google.de/search?q=rhabarberw%E4he&ie=ISO-8859-1&oe=ISO-8859-1';
		$ref_utf8 = 'http://www.google.de/search?q=rhabarberw%C3%A4he&ie=utf-8&oe=utf-8';
		$ref_noie = 'http://www.google.de/search?q=rhabarberw%C3%A4he';

		// Referrer is latin1:
		$evo_charset = 'utf-8';
		$this->assertEqual( extract_keyphrase($ref_latin1), 'rhabarberwähe' );

		$evo_charset = 'iso-8859-1';
		$this->assertEqual( extract_keyphrase($ref_latin1), utf8_decode('rhabarberwähe') );

		// Referrer is utf8:
		$evo_charset = 'utf-8';
		$this->assertEqual( extract_keyphrase($ref_utf8), 'rhabarberwähe' );

		$evo_charset = 'iso-8859-1';
		$this->assertEqual( extract_keyphrase($ref_utf8), utf8_decode('rhabarberwähe') );

		// Referrer is "unknown":
		$evo_charset = 'utf-8';
		$this->assertEqual( extract_keyphrase($ref_noie), 'rhabarberwähe' );

		$evo_charset = 'iso-8859-1';
		$this->assertEqual( extract_keyphrase($ref_noie), utf8_decode('rhabarberwähe') );

		$ref = 'http://www.google.de/search?q=is+this+a+question%3F&btnG=Suche';
		$this->assertEqual( extract_keyphrase($ref), 'is this a question?' );

		$evo_charset = 'utf-8';
		$ref = 'http://images.google.de/imgres?imgurl=http://www.hahler.de/media/users/claudia/90506005.jpg&imgrefurl=http://claudia.hahler.de/rhabarberwahe_schweizer_rezept&usg=__4aU46UZlyKjUEhR1A8k2AYeCc3k=&h=480&w=640&sz=96&hl=de&start=7&tbnid=ojcNBFIQ8uAOwM:&tbnh=103&tbnw=137&prev=/images%3Fq%3Drhabarberw%25C3%25A4he%26gbv%3D1%26hl%3Dde%26sa%3DG';
		$this->assertEqual( extract_keyphrase($ref), 'rhabarberwähe' );

		// "q" param is encoded in latin1. This must be detected.
		$ref = 'http://suche.t-online.de/fast-cgi/tsc?mandant=toi&device=html&portallanguage=de&userlanguage=de&dia=adr&context=internet-tab&tpc=internet&ptl=std&classification=internet-tab_internet_std&start=0&num=10&type=all&lang=any&more=none&q=gut+gl%FCck';
		$this->assertEqual( extract_keyphrase($ref), 'gut glück' );

		// "q" param in utf-8.
		$ref = 'http://suche.t-online.de/?q=gut+gl%C3%BCck';
		$this->assertEqual( extract_keyphrase($ref), 'gut glück' );

		// "€" in utf-8
		$ref = 'http://suche.t-online.de/?q=%E2%82%AC';
		$this->assertEqual( extract_keyphrase($ref), '€' );

		// "€" in iso-8859-15
		$ref = 'http://suche.t-online.de/?q=%A4';
		$this->assertEqual( extract_keyphrase($ref), '€' );

		$ref = 'http://images.google.de/imgres?imgurl=http://www.hahler.de/media/dAny%2520und%2520dAni%2520am%2520Strand.jpg&imgrefurl=http://dany.hahler.de/lass_die_sonne_rein&usg=__j10Oj7RuDyfJFrwaWaEmzZ84vaE=&h=450&w=600&sz=66&hl=de&start=6&um=1&itbs=1&tbnid=l3WSDzMHaY9e8M:&tbnh=101&tbnw=135&prev=/images%3Fq%3Dstrand%2Bpag%26um%3D1%26hl%3Dde%26sa%3DN%26rlz%3D1B3GGGL_deDE334DE334%26tbs%3Disch:1';
		$this->assertEqual( extract_keyphrase($ref), 'strand pag' );

		// "text" param in utf-8 with "q" param as page number
		$ref = 'http://yandex.ru/?text=проверка+связи&p=4';
		$this->assertEqual( extract_keyphrase($ref), 'проверка связи' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new HitTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>