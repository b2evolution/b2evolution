<?php
/**
 * MMS Server - injecting mms messages from a mobile phone into a blog
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * This file built upon code from original Peffisaur - 
 * Stefan Hellkvist - {@link http://hellkvist.org}
 *
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * @copyright (c)2004 by Hans Reinders - {@link http://hansreinders.com}
 *
 * @package htsrv
 * @todo MOVE THIS FILE TO /htsrv
 */
 
/**
 * Initalize:
 */
require_once( dirname(__FILE__).'/../evocore/_main.inc.php' );

param( 'login', 'string', '', true );
param( 'pass', 'string', '', true );
param( 'cat', 'integer', $default_category, true );

if( !user_pass_ok( $login, $pass, false ) || $_SERVER['CONTENT_TYPE'] != "application/vnd.wap.mms-message" || strlen( $HTTP_RAW_POST_DATA ) == 0 ) exit;

$userdata = get_userdatabylogin($login);
$current_User = & $UserCache->get_by_ID( $userdata['ID'] );
$post_category = $cat;
$blog = get_catblog($post_category); 

// Check permission:
$current_User->check_perm( 'blog_post_statuses', 'published', true, $blog );


define( "BCC", 			0x01 );
define( "CC", 			0x02 );
define( "CONTENT_LOCATION", 	0x03 );
define( "CONTENT_TYPE", 	0x04 );
define( "DATE", 		0x05 );
define( "DELIVERY_REPORT", 	0x06 );
define( "DELIVERY_TIME", 	0x07 );
define( "EXPIRY", 		0x08 );
define( "FROM", 		0x09 );
define( "MESSAGE_CLASS",	0x0A );
define( "MESSAGE_ID", 		0x0B );
define( "MESSAGE_TYPE", 	0x0C );
define( "MMS_VERSION", 		0x0D );
define( "MESSAGE_SIZE", 	0x0E );
define( "PRIORITY", 		0x0F );
define( "READ_REPLY", 		0x10 );
define( "REPORT_ALLOWED", 	0x11 );
define( "RESPONSE_STATUS", 	0x12 );
define( "RESPONSE_TEXT", 	0x13 );
define( "SENDER_VISIBILITY", 	0x14 );
define( "STATUS", 		0x15 );
define( "SUBJECT", 		0x16 );
define( "TO", 			0x17 );
define( "TRANSACTION_ID", 	0x18 );
define( "IMAGE_GIF", 		0x1D );
define( "IMAGE_JPEG", 		0x1E );
define( "IMAGE_PNG", 		0x20 );
define( "IMAGE_WBMP", 		0x21 );
define( "TEXT_PLAIN", 		0x03 );
define( "MULTIPART_MIXED", 	0x23 );
define( "MULTIPART_RELATED",  	0x33 );

$content_types = array( "*/*", "text/*", "text/html", "text/plain",
	 	 	"text/x-hdml", "text/x-ttml", "text/x-vCalendar",
			"text/x-vCard", "text/vnd.wap.wml", 
			"text/vnd.wap.wmlscript", "text/vnd.wap.wta-event",
			"multipart/*", "multipart/mixed", 
			"multipart/form-data", "multipart/byterantes",
			"multipart/alternative", "application/*",
			"application/java-vm", 
			"application/x-www-form-urlencoded",
			"application/x-hdmlc", "application/vnd.wap.wmlc",
			"application/vnd.wap.wmlscriptc", 
			"application/vnd.wap.wta-eventc", 
			"application/vnd.wap.uaprof", 
			"application/vnd.wap.wtls-ca-certificate",
			"application/vnd.wap.wtls-user-certificate",
			"application/x-x509-ca-cert", 
			"application/x-x509-user-cert", 
			"image/*", "image/gif", "image/jpeg", "image/tiff",
			"image/png", "image/vnd.wap.wbmp", 
			"application/vnd.wap.multipart.*", 
			"application/vnd.wap.multipart.mixed", 
			"application/vnd.wap.multipart.form-data", 
			"application/vnd.wap.multipart.byteranges", 
			"application/vnd.wap.multipart.alternative", 
			"application/xml", "text/xml", 
			"application/vnd.wap.wbxml", 
			"application/x-x968-cross-cert", 
			"application/x-x968-ca-cert", 
			"application/x-x968-user-cert", 
			"text/vnd.wap.si", 
			"application/vnd.wap.sic", 
			"text/vnd.wap.sl", 
			"application/vnd.wap.slc", 
			"text/vnd.wap.co", 
			"application/vnd.wap.coc", 
			"application/vnd.wap.multipart.related", 
			"application/vnd.wap.sia", 
			"text/vnd.wap.connectivity-xml", 
			"application/vnd.wap.connectivity-wbxml", 
			"application/pkcs7-mime", 
			"application/vnd.wap.hashed-certificate", 
			"application/vnd.wap.signed-certificate", 
			"application/vnd.wap.cert-response",
			"application/xhtml+xml",
			"application/wml+xml",
			"text/css", 
			"application/vnd.wap.mms-message", 
			"application/vnd.wap.rollover-certificate", 
			"application/vnd.wap.locc+wbxml", 
			"application/vnd.wap.loc+xml", 
			"application/vnd.syncml.dm+wbxml", 
			"application/vnd.syncml.dm+xml", 
			"application/vnd.syncml.notification", 
			"application/vnd.wap.xhtml+xml", 
			"application/vnd.wv.csp.cir", 
			"application/vnd.oma.dd+xml", 
			"application/vnd.oma.drm.message", 
			"application/vnd.oma.drm.content", 
			"application/vnd.oma.drm.rights+xml",
			"application/vnd.oma.drm.rights+wbxml" );

$typeToExtension = array( IMAGE_GIF =>		".gif",
			  IMAGE_JPEG =>         ".jpg", 	
			  IMAGE_PNG =>          ".png",
			  IMAGE_WBMP =>         ".wbmp",
			  TEXT_PLAIN =>		".txt",
			  "application/smil" => ".smil",
			  "audio/amr" =>   	".amr",
			  "x-music/x-midi" =>	".mid",
			  "audio/midi" =>	".mid",
			  "audio/x-wav" =>      ".wav",
			  "video/mpeg" =>	".mpg",
			  "video/avi" =>	".avi",
			  "video/mpeg4" =>	".mp4",
			  "application/zip" =>	".zip",
			  "text/x-imelody" => 	".imy" );

function calcSize( $md )
{
	$size = 0;
	$parts = $md->parts;
	for ( $i = 0; $i < sizeof( $parts ); $i++ )
	{
		$p = $parts[$i];
		$size = $size+$p->dataLen;
	}
	return $size;
}

function contentTypeToString( $contentType )
{
	global $content_types;
	if ( is_string( $contentType ) ) return $contentType;
	return $content_types[$contentType];
}

function extractText( $md )
{
	$parts = $md->parts;
	for ( $i = 0; $i < sizeof( $parts ); $i++ )
	{
		$p=$parts[$i];
		if ( $p->contentType==TEXT_PLAIN )
		{
			$text = toString( $p->data );
			$text = textDecode( $text );
			$text = strip_tags( $text );
			return $text;
		}
	}
	return "";
}


function getExtension( $contentType )
{
	global $typeToExtension;
	if ( array_key_exists( $contentType, $typeToExtension ) ) return $typeToExtension[$contentType];
	return ".bin";
}



function textDecode( $text )
{
	if ( ord( $text{0} ) == 0xff && ord( $text{1} ) == 0xfe )
	{
		for ( $i = 2; $i < strlen( $text ); $i += 2 ) $res .= $text{$i};
		return $res;
        }
	return $text;
}


function toString( $data )
{
	for ( $i = 0; $i < sizeof( $data ); $i++ ) $res .= chr( $data[$i] );
	return $res;
}

function writeBackSendConf( $md ) {
	$reply[0] = 0x8c; // X-Mms-Message-Type
	$reply[1] = 0x81; // = m-send-conf
	$reply[2] = 0x98; // X-Mms-Transaction-ID
	for ( $i = 3; $i < strlen( $md->transactionId )+3; $i++ ) $reply[$i] = ord( $md->transactionId{$i-3} );
	$reply[$i++]=0; // Terminate string
	$reply[$i++]=0x8D; // X-Mms-Version
	$reply[$i++]=0x90; // = 1.0
	$reply[$i++]=0x92; // X-Mms-Response-Status
	$reply[$i++]=128;  // = OK
	header("Content-Type: application/vnd.wap.mms-message");
	for ( $j = 0; $j < $i; $j++ ) print( chr( $reply[$j] ) );
}

/**
 * @package htsrv
 */
class MMSDecoder {
	var $data;
	var $curp;
	var $messageType;
	var $transactionId;
	var $mmsVersion;
	var $date;
	var $from;
	var $to;
	var $cc;
	var $bcc;
	var $subject;
	var $messageClass;
	var $priority;
	var $senderVisibility;
	var $deliveryReport;
	var $readReply;
	var $contentType;
	var $bodyStartsAt;
	var $expiryDate;
	var $expiryDeltaSeconds;
	var $status;
	var $nparts;
	var $parts;

	function isSeparator( $ch )
	{
		return $ch == 40 || $ch == 41 || $ch == 60 || $ch == 62 || $ch == 64 || $ch == 44 || $ch == 58 || $ch == 59 || $ch == 92 || $ch == 47 || $ch == 123 || $ch == 125 || $ch == 91 || $ch == 93 || $ch == 63 || $ch == 61 || $ch == 32 || $ch == 11;
	}

	function MMSDecoder( $data )
	{
		$datalen = strlen( $data );
		for ( $i = 0; $i < $datalen; $i++ ) $this->data[$i] = ord( $data{$i} );
		$this->curp = 0;
	}

	function parse()
	{
		while ( MMSDecoder::parseHeader() );
		$this->bodyStartsAt = $this->curp;
		if ( $this->contentType == MULTIPART_MIXED || $this->contentType == MULTIPART_RELATED ) MMSDecoder::parseBody();
	}

	function parseApplicationHeader()
	{
		$res = MMSDecoder::parseToken( $token );
		if ($res) $res = MMSDecoder::parseTextString( $appspec );
		return $res;
	}

	function parseBcc()
	{
		MMSDecoder::parseEncodedString( $this->bcc );
	}

	function parseBody()
	{
		MMSDecoder::parseUintvar( $this->nparts );
		for ($i=0; $i < $this->nparts; $i++ ) MMSDecoder::parsePart($i);
	}

	function parseCc() {
		MMSDecoder::parseEncodedString( $this->cc );
	}

	function parseConstrainedEncoding( &$encoding )
	{
		$res = MMSDecoder::parseShortInteger( $encoding );
		if (!$res) $res = MMSDecoder::parseExtensionMedia( $encoding );
		return $res;
	}

	function parseConstrainedMedia( &$contentType )
	{
		return MMSDecoder::parseConstrainedEncoding( $contentType );
	}

	function parseContentGeneralForm( &$encoding )
	{
		$res = MMSDecoder::parseValueLength( $length );
		$tmp = $this->curp;
		if ( !$res ) return 0;
		$res = MMSDecoder::parseMediaType( $encoding );
		$this->curp = $tmp+$length;
		return $res;
	}

	function parseContentType( &$contentType )
	{
		$typeFound = MMSDecoder::parseConstrainedMedia( $contentType );
		if ( !$typeFound )
		{
			MMSDecoder::parseContentGeneralForm( $contentType );
			$typeFound = 1;
		}
		return $typeFound;
	}

	function parseDate( &$date )
	{
		MMSDecoder::parseLongInteger( $date );
	}

	function parseDeliveryReport()
	{
		$this->deliveryReport = $this->data[$this->curp++];
	}

	function parseDeltaSeconds( &$deltaSecs )
	{
		MMSDecoder::parseDate( $deltaSecs );
	}

	function parseEncodedString(&$encstring)
	{
		$isencoded = MMSDecoder::parseValueLength( $length );
		if ( $isencoded ) $this->curp++;
		MMSDecoder::parseTextString( $encstring );
	}

	function parseExpiry()
	{
		MMSDecoder::parseValueLength( $length );
		switch ( $this->data[$this->curp] )
		{
			case 128:
				$this->curp++; 
				MMSDecoder::parseDate( $this->expiryDate ); 
				break;
			case 129:
				$this->curp++; 
				MMSDecoder::parseDeltaSeconds( $this->expiryDeltaSeconds );
				break;
			default:
		}
	}

	function parseExtensionMedia( &$encoding )
	{
		$ch = $this->data[$this->curp];
		if ( $ch<32 || $ch == 127 ) return 0;
		$res = MMSDecoder::parseTextString( $encoding );
		return $res;
	}

	function parseFrom()
	{
		MMSDecoder::parseValueLength( $length );
		if ( $this->data[$this->curp] == 128 )
		{
			$this->curp++;
			MMSDecoder::parseEncodedString( $this->from );
		}
		else
		{
			$this->from = "Anonymous";
			$this->curp++;
		}
	}

	function parseHeader()
	{
		$res = MMSDecoder::parseMMSHeader();
		if (!$res) $res = MMSDecoder::parseApplicationHeader();
		return $res;
	}

	function parseInteger( &$integer )
	{
		$res = MMSDecoder::parseShortInteger( $integer );
		if (!$res) $res = MMSDecoder::parseLongInteger( $integer );
		return $res;
	}

	function parseLongInteger( &$longInt )
	{
		if ( !MMSDecoder::parseShortLength( $length ) ) return 0;
		return MMSDecoder::parseMultiOctetInteger( $longInt,$length );
	}

	function parseMediaType( &$encoding )
	{
		$res = MMSDecoder::parseWellKnownMedia( $encoding );
		if (!$res) $res	= MMSDecoder::parseExtensionMedia( $encoding );
		return $res;
	}

	function parseMessageClass()
	{
		if ($this->data[$this->curp]<128 || $this->data[$this->curp]>131) die( "parseMessageClass not fully implemented" );
		$this->messageClass = $this->data[$this->curp++];
	}

	function parseMessageType()
	{
		if (!($this->data[$this->curp] & 0x80)) return 0;
		$this->messageType = $this->data[$this->curp];
		$this->curp++;
		return 1;
	}

	function parseMMSHeader()
	{
		if ( !MMSDecoder::parseShortInteger( $mmsFieldName ) ) return 0;
		switch ($mmsFieldName)
		{
			case BCC:
				MMSDecoder::parseBcc();
				break;
			case CC:
				MMSDecoder::parseCc();
				break;
			case CONTENT_LOCATION:
				MMSDecoder::parseContentLocation();
				break;
			case CONTENT_TYPE:
				MMSDecoder::parseContentType( $this->contentType );
				break;
			case DATE:
				MMSDecoder::parseDate( $this->date );
				break;
			case DELIVERY_REPORT:
				MMSDecoder::parseDeliveryReport();
				break;
			case DELIVERY_TIME:
				MMSDecoder::parseDeliveryTime();
				break;
			case EXPIRY:
				MMSDecoder::parseExpiry();
				break;
			case FROM:
				MMSDecoder::parseFrom();
				break;
			case MESSAGE_CLASS:
				MMSDecoder::parseMessageClass();
				break;
			case MESSAGE_ID:
				MMSDecoder::parseMessageId();
				break;
			case MESSAGE_TYPE:
				MMSDecoder::parseMessageType();
				break;
			case MMS_VERSION:
				MMSDecoder::parseMmsVersion();
				break;
			case MESSAGE_SIZE:
				MMSDecoder::parseMessageSize();
				break;
			case PRIORITY:
				MMSDecoder::parsePriority();
				break;
			case READ_REPLY:
				MMSDecoder::parseReadReply();
				break;
			case REPORT_ALLOWED:
				MMSDecoder::parseReportAllowed();
				break;
			case RESPONSE_STATUS:
				MMSDecoder::parseResponseStatus();
				break;
			case SENDER_VISIBILITY:
				MMSDecoder::parseSenderVisibility();
				break;
			case STATUS:
				MMSDecoder::parseStatus();
				break;
			case SUBJECT:
				MMSDecoder::parseSubject();
				break;
			case TO:
				MMSDecoder::parseTo();
				break;
			case TRANSACTION_ID:
				MMSDecoder::parseTransactionId();
				break;
			default:
				break;
		}
		return 1;
	}

	function parseMmsVersion()
	{
		MMSDecoder::parseShortInteger( $this->mmsVersion );
	}

	function parseMultiOctetInteger( &$moint, $noctets )
	{
		$moint=0;
		for ( $i = 0; $i < $noctets; $i++ )
		{
			$moint = $moint << 8;
			$moint |= $this->data[$this->curp];
			$this->curp++;
		}
		return 1;
	}

	function parsePart( $i )
	{
		$part = new Part;
		MMSDecoder::parseUintvar( $headersLen );
		MMSDecoder::parseUintvar( $dataLen );
		$part->dataLen = $dataLen;
		$tmp = $this->curp;
		MMSDecoder::parseContentType( $part->contentType );
		$this->curp = $tmp+$headersLen;
		for ( $j = 0; $j < $dataLen; $j++ )
		{
			$part->data[$j] = $this->data[$this->curp];
			$this->curp++;
		}
		$this->parts[$i]=$part;
	}

	function parsePriority()
	{
		$this->priority = $this->data[$this->curp++];
	}

	function parseReadReply()
	{
		$this->readReply = $this->data[$this->curp++];
	}	

	function parseSenderVisibility()
	{
		$this->senderVisibility = $this->data[$this->curp++];
	}

	function parseShortInteger(&$shortInt)
	{
		if ( !( $this->data[$this->curp] & 0x80 ) ) return 0;
		$shortInt = $this->data[$this->curp] & 0x7f;
		$this->curp++;
		return 1;
	}

	function parseShortLength( &$shortLength )
	{
		$shortLength = $this->data[$this->curp];
		if ($shortLength>30) return 0;
		$this->curp++;
		return 1;
	}

	function parseStatus()
	{
		$this->status = $this->data[$this->curp++];
	}

	function parseSubject()
	{
		MMSDecoder::parseEncodedString( $this->subject );
	}

	function parseTextString( &$textString )
	{
		if ( $this->data[$this->curp] == 127 ) $this->curp++;
		while ( $this->data[$this->curp] )
		{
			$textString .= chr( $this->data[$this->curp] );
			$this->curp++;
		}
		$this->curp++;
		return 1;
	}

	function parseTo() {
		MMSDecoder::parseEncodedString( $this->to );
	}

	function parseToken(&$token) {
		if ( $this->data[$this->curp] <= 31 || MMSDecoder::isSeparator( $this->data[$this->curp] ) ) return 0;
		while ( $this->data[$this->curp] > 31 && !MMSDecoder::isSeparator($this->data[$this->curp] ) )
		{
			$token .= chr( $this->data[$this->curp] );
			$this->curp++;
		}
		return 1;
	}

	function parseTransactionId()
	{	
		MMSDecoder::parseTextString( $this->transactionId );
	}

	function parseUintvar( &$uintvar ) {
		$uintvar = 0;
		while ( $this->data[$this->curp] & 0x80 )
		{
			$uintvar=$uintvar << 7;
			$uintvar |= $this->data[$this->curp] & 0x7f;
			$this->curp++;
		}
		$uintvar = $uintvar << 7;
		$uintvar |= $this->data[$this->curp] & 0x7f;
		$this->curp++;
	}

	function parseValueLength( &$length ) {
		$lengthFound=MMSDecoder::parseShortLength( $length );
		if ( !$lengthFound ) {
			if ( $this->data[$this->curp] == 31)
			{
				$this->curp++;
				MMSDecoder::parseUintvar( $length );
				return 1;
			}
		}
		return $lengthFound;
	}

	function parseWellKnownMedia( &$encoding ) {
		return MMSDecoder::parseInteger( $encoding );
	}

}

/**
 * @package htsrv
 */
class Part
{

	var $contentType;
	var $dataLen;
	var $data;

	function writeToFile( $fileName )
	{
		$fp = fopen( $fileName, "wb" );
		for ( $i = 0; $i < $this->dataLen; $i++ ) fwrite( $fp, chr( $this->data[$i] ), 1 );
		fclose($fp);
	}

}


$md = new MMSDecoder( $HTTP_RAW_POST_DATA );
$md->parse();

// $from_ip = $_SERVER['REMOTE_ADDR'];
// $recipient = $md->to;

$post_title = $md->subject;
$text = extractText( $md );

if ( strlen( $post_title ) == 0 )
{
	if ( strlen( $text ) > 0) $post_title = substr( $text, 0, 12 ) . "...";
	else $post_title = date( 'H:i', $localtimenow );
}

$parts = $md->parts;
$content = $text . '<br />';

for ( $i = 0; $i < sizeof( $parts ); $i++ )
{
	$part = $parts[$i];
	$ext = getExtension( $part->contentType );
	$size = $part->dataLen;
	$type = contentTypeToString( $part->contentType );
	if ( $ext != '.smil' )
	{
		$filename = 'mms' . mktime() . $ext;
		$part->writeToFile ( $fileupload_realpath.'/'.$filename );
		
		$content .= '<img src="'.$fileupload_url.'/'.$filename.'"';
		if( $img_dimensions = getimagesize( $fileupload_realpath.'/'.$filename ) )
		{ // add 'width="xx" height="xx"'
			$content .= ' '.$img_dimensions[3];
		}
		$content .= ' alt="" /><br />';
	}
}
// $sizeofparts = calcSize( $md ) / 1024;

$post_title = format_to_post( trim( $post_title ), 0, 0 );
$content = format_to_post( trim( $content ), $Settings->get('AutoBR'), 0 );

$post_date = date('Y-m-d H:i:s', $localtimenow);

$post_ID = bpost_create( $current_User->ID, $post_title, $content, $post_date, $post_category, array(), 'published', $current_User->locale, '', $Settings->get('AutoBR'), true );

if ( isset( $sleep_after_edit ) && $sleep_after_edit > 0 ) 
{
	sleep( $sleep_after_edit );
}

writeBackSendConf( $md );

/* Pinging turned off for now because of causing invalid server response
$blogparams = get_blogparams_by_ID( $blog );
pingback( true, $content, $post_title, '', $post_ID, $blogparams, false );
pingb2evonet( $blogparams, $post_ID, $post_title, false );
pingWeblogs( $blogparams, false );
pingBlogs( $blogparams );
pingTechnorati($blogparams);
*/

exit;

?>