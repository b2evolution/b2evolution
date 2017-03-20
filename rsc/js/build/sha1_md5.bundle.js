/* ------------------------- sha1.js ------------------------- */
/*
 * A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined
 * in FIPS 180-1
 * Version 2.2-alpha Copyright Paul Johnston 2000 - 2002.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 */

/*
 * Configurable variables. You may need to tweak these to be compatible with
 * the server-side, but the defaults work in most cases.
 */
var hexcase = 0;  /* hex output format. 0 - lowercase; 1 - uppercase        */
var b64pad  = ""; /* base-64 pad character. "=" for strict RFC compliance   */

/*
 * These are the functions you'll usually want to call
 * They take string arguments and return either hex or base-64 encoded strings
 */
function hex_sha1(s)    { return rstr2hex(rstr_sha1(str2rstr_utf8(s))); }
function b64_sha1(s)    { return rstr2b64(rstr_sha1(str2rstr_utf8(s))); }
function any_sha1(s, e) { return rstr2any_sha1(rstr_sha1(str2rstr_utf8(s)), e); }
function hex_hmac_sha1(k, d)
  { return rstr2hex(rstr_hmac_sha1(str2rstr_utf8(k), str2rstr_utf8(d))); }
function b64_hmac_sha1(k, d)
  { return rstr2b64(rstr_hmac_sha1(str2rstr_utf8(k), str2rstr_utf8(d))); }
function any_hmac_sha1(k, d, e)
  { return rstr2any_sha1(rstr_hmac_sha1(str2rstr_utf8(k), str2rstr_utf8(d)), e); }

/*
 * Perform a simple self-test to see if the VM is working
 */
function sha1_vm_test()
{
  return hex_sha1("abc") == "a9993e364706816aba3e25717850c26c9cd0d89d";
}

/*
 * Calculate the SHA1 of a raw string
 */
function rstr_sha1(s)
{
  return binb2rstr(binb_sha1(rstr2binb(s), s.length * 8));
}

/*
 * Calculate the HMAC-SHA1 of a key and some data (raw strings)
 */
function rstr_hmac_sha1(key, data)
{
  var bkey = rstr2binb(key);
  if(bkey.length > 16) bkey = binb_sha1(bkey, key.length * 8);

  var ipad = Array(16), opad = Array(16);
  for(var i = 0; i < 16; i++)
  {
    ipad[i] = bkey[i] ^ 0x36363636;
    opad[i] = bkey[i] ^ 0x5C5C5C5C;
  }

  var hash = binb_sha1(ipad.concat(rstr2binb(data)), 512 + data.length * 8);
  return binb2rstr(binb_sha1(opad.concat(hash), 512 + 160));
}

/*
 * Convert a raw string to a hex string
 */
function rstr2hex(input)
{
  var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
  var output = "";
  var x;
  for(var i = 0; i < input.length; i++)
  {
    x = input.charCodeAt(i);
    output += hex_tab.charAt((x >>> 4) & 0x0F)
           +  hex_tab.charAt( x        & 0x0F);
  }
  return output;
}

/*
 * Convert a raw string to a base-64 string
 */
function rstr2b64(input)
{
  var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
  var output = "";
  var len = input.length;
  for(var i = 0; i < len; i += 3)
  {
    var triplet = (input.charCodeAt(i) << 16)
                | (i + 1 < len ? input.charCodeAt(i+1) << 8 : 0)
                | (i + 2 < len ? input.charCodeAt(i+2)      : 0);
    for(var j = 0; j < 4; j++)
    {
      if(i * 8 + j * 6 > input.length * 8) output += b64pad;
      else output += tab.charAt((triplet >>> 6*(3-j)) & 0x3F);
    }
  }
  return output;
}

/*
 * Convert a raw string to an arbitrary string encoding
 */
function rstr2any_sha1(input, encoding)
{
  var divisor = encoding.length;
  var remainders = Array();
  var i, q, x, quotient;

  /* Convert to an array of 16-bit big-endian values, forming the dividend */
  var dividend = Array(Math.ceil(input.length / 2));
  for(i = 0; i < dividend.length; i++)
  {
    dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
  }

  /*
   * Repeatedly perform a long division. The binary array forms the dividend,
   * the length of the encoding is the divisor. Once computed, the quotient
   * forms the dividend for the next step. We stop when the dividend is zero.
   * All remainders are stored for later use.
   */
  while(dividend.length > 0)
  {
    quotient = Array();
    x = 0;
    for(i = 0; i < dividend.length; i++)
    {
      x = (x << 16) + dividend[i];
      q = Math.floor(x / divisor);
      x -= q * divisor;
      if(quotient.length > 0 || q > 0)
        quotient[quotient.length] = q;
    }
    remainders[remainders.length] = x;
    dividend = quotient;
  }

  /* Convert the remainders to the output string */
  var output = "";
  for(i = remainders.length - 1; i >= 0; i--)
    output += encoding.charAt(remainders[i]);

  /* Append leading zero equivalents */
  var full_length = Math.ceil(input.length * 8 /
                                    (Math.log(encoding.length) / Math.log(2)))
  for(i = output.length; i < full_length; i++)
    output = encoding[0] + output;

  return output;
}

/*
 * Encode a string as utf-8.
 * For efficiency, this assumes the input is valid utf-16.
 */
function str2rstr_utf8(input)
{
  var output = "";
  var i = -1;
  var x, y;

  while(++i < input.length)
  {
    /* Decode utf-16 surrogate pairs */
    x = input.charCodeAt(i);
    y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
    if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF)
    {
      x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
      i++;
    }

    /* Encode output as utf-8 */
    if(x <= 0x7F)
      output += String.fromCharCode(x);
    else if(x <= 0x7FF)
      output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F),
                                    0x80 | ( x         & 0x3F));
    else if(x <= 0xFFFF)
      output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F),
                                    0x80 | ((x >>> 6 ) & 0x3F),
                                    0x80 | ( x         & 0x3F));
    else if(x <= 0x1FFFFF)
      output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07),
                                    0x80 | ((x >>> 12) & 0x3F),
                                    0x80 | ((x >>> 6 ) & 0x3F),
                                    0x80 | ( x         & 0x3F));
  }
  return output;
}

/*
 * Encode a string as utf-16
 */
function str2rstr_utf16le(input)
{
  var output = "";
  for(var i = 0; i < input.length; i++)
    output += String.fromCharCode( input.charCodeAt(i)        & 0xFF,
                                  (input.charCodeAt(i) >>> 8) & 0xFF);
  return output;
}

function str2rstr_utf16be(input)
{
  var output = "";
  for(var i = 0; i < input.length; i++)
    output += String.fromCharCode((input.charCodeAt(i) >>> 8) & 0xFF,
                                   input.charCodeAt(i)        & 0xFF);
  return output;
}

/*
 * Convert a raw string to an array of big-endian words
 * Characters >255 have their high-byte silently ignored.
 */
function rstr2binb(input)
{
  var output = Array(input.length >> 2);
  for(var i = 0; i < output.length; i++)
    output[i] = 0;
  for(var i = 0; i < input.length * 8; i += 8)
    output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (24 - i % 32);
  return output;
}

/*
 * Convert an array of little-endian words to a string
 */
function binb2rstr(input)
{
  var output = "";
  for(var i = 0; i < input.length * 32; i += 8)
    output += String.fromCharCode((input[i>>5] >>> (24 - i % 32)) & 0xFF);
  return output;
}

/*
 * Calculate the SHA-1 of an array of big-endian words, and a bit length
 */
function binb_sha1(x, len)
{
  /* append padding */
  x[len >> 5] |= 0x80 << (24 - len % 32);
  x[((len + 64 >> 9) << 4) + 15] = len;

  var w = Array(80);
  var a =  1732584193;
  var b = -271733879;
  var c = -1732584194;
  var d =  271733878;
  var e = -1009589776;

  for(var i = 0; i < x.length; i += 16)
  {
    var olda = a;
    var oldb = b;
    var oldc = c;
    var oldd = d;
    var olde = e;

    for(var j = 0; j < 80; j++)
    {
      if(j < 16) w[j] = x[i + j];
      else w[j] = bit_rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1);
      var t = safe_add(safe_add(bit_rol(a, 5), sha1_ft(j, b, c, d)),
                       safe_add(safe_add(e, w[j]), sha1_kt(j)));
      e = d;
      d = c;
      c = bit_rol(b, 30);
      b = a;
      a = t;
    }

    a = safe_add(a, olda);
    b = safe_add(b, oldb);
    c = safe_add(c, oldc);
    d = safe_add(d, oldd);
    e = safe_add(e, olde);
  }
  return Array(a, b, c, d, e);

}

/*
 * Perform the appropriate triplet combination function for the current
 * iteration
 */
function sha1_ft(t, b, c, d)
{
  if(t < 20) return (b & c) | ((~b) & d);
  if(t < 40) return b ^ c ^ d;
  if(t < 60) return (b & c) | (b & d) | (c & d);
  return b ^ c ^ d;
}

/*
 * Determine the appropriate additive constant for the current iteration
 */
function sha1_kt(t)
{
  return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 :
         (t < 60) ? -1894007588 : -899497514;
}

/*
 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
 * to work around bugs in some JS interpreters.
 */
function safe_add(x, y)
{
  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}

/*
 * Bitwise rotate a 32-bit number to the left.
 */
function bit_rol(num, cnt)
{
  return (num << cnt) | (num >>> (32 - cnt));
}


/* ------------------------- md5.js ------------------------- */
/*
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.2-alpha Copyright (C) Paul Johnston 1999 - 2005
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */

/*
 * These are the functions you'll usually want to call
 * They take string arguments and return either hex or base-64 encoded strings
 */
function hex_md5(s)    { return rstr2hex(rstr_md5(str2rstr_utf8(s))); }
function b64_md5(s)    { return rstr2b64(rstr_md5(str2rstr_utf8(s))); }
function any_md5(s, e) { return rstr2any_md5(rstr_md5(str2rstr_utf8(s)), e); }
function hex_hmac_md5(k, d)
  { return rstr2hex(rstr_hmac_md5(str2rstr_utf8(k), str2rstr_utf8(d))); }
function b64_hmac_md5(k, d)
  { return rstr2b64(rstr_hmac_md5(str2rstr_utf8(k), str2rstr_utf8(d))); }
function any_hmac_md5(k, d, e)
  { return rstr2any_md5(rstr_hmac_md5(str2rstr_utf8(k), str2rstr_utf8(d)), e); }

/*
 * Perform a simple self-test to see if the VM is working
 */
function md5_vm_test()
{
  return hex_md5("abc") == "900150983cd24fb0d6963f7d28e17f72";
}

/*
 * Calculate the MD5 of a raw string
 */
function rstr_md5(s)
{
  return binl2rstr(binl_md5(rstr2binl(s), s.length * 8));
}

/*
 * Calculate the HMAC-MD5, of a key and some data (raw strings)
 */
function rstr_hmac_md5(key, data)
{
  var bkey = rstr2binl(key);
  if(bkey.length > 16) bkey = binl_md5(bkey, key.length * 8);

  var ipad = Array(16), opad = Array(16);
  for(var i = 0; i < 16; i++)
  {
    ipad[i] = bkey[i] ^ 0x36363636;
    opad[i] = bkey[i] ^ 0x5C5C5C5C;
  }

  var hash = binl_md5(ipad.concat(rstr2binl(data)), 512 + data.length * 8);
  return binl2rstr(binl_md5(opad.concat(hash), 512 + 128));
}

/*
 * Convert a raw string to an arbitrary string encoding
 */
function rstr2any_md5(input, encoding)
{
  var divisor = encoding.length;
  var i, j, q, x, quotient;

  /* Convert to an array of 16-bit big-endian values, forming the dividend */
  var dividend = Array(Math.ceil(input.length / 2));
  for(i = 0; i < dividend.length; i++)
  {
    dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
  }

  /*
   * Repeatedly perform a long division. The binary array forms the dividend,
   * the length of the encoding is the divisor. Once computed, the quotient
   * forms the dividend for the next step. All remainders are stored for later
   * use.
   */
  var full_length = Math.ceil(input.length * 8 /
                                    (Math.log(encoding.length) / Math.log(2)));
  var remainders = Array(full_length);
  for(j = 0; j < full_length; j++)
  {
    quotient = Array();
    x = 0;
    for(i = 0; i < dividend.length; i++)
    {
      x = (x << 16) + dividend[i];
      q = Math.floor(x / divisor);
      x -= q * divisor;
      if(quotient.length > 0 || q > 0)
        quotient[quotient.length] = q;
    }
    remainders[j] = x;
    dividend = quotient;
  }

  /* Convert the remainders to the output string */
  var output = "";
  for(i = remainders.length - 1; i >= 0; i--)
    output += encoding.charAt(remainders[i]);

  return output;
}

/*
 * Convert a raw string to an array of little-endian words
 * Characters >255 have their high-byte silently ignored.
 */
function rstr2binl(input)
{
  var output = Array(input.length >> 2);
  for(var i = 0; i < output.length; i++)
    output[i] = 0;
  for(var i = 0; i < input.length * 8; i += 8)
    output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (i%32);
  return output;
}

/*
 * Convert an array of little-endian words to a string
 */
function binl2rstr(input)
{
  var output = "";
  for(var i = 0; i < input.length * 32; i += 8)
    output += String.fromCharCode((input[i>>5] >>> (i % 32)) & 0xFF);
  return output;
}

/*
 * Calculate the MD5 of an array of little-endian words, and a bit length.
 */
function binl_md5(x, len)
{
  /* append padding */
  x[len >> 5] |= 0x80 << ((len) % 32);
  x[(((len + 64) >>> 9) << 4) + 14] = len;

  var a =  1732584193;
  var b = -271733879;
  var c = -1732584194;
  var d =  271733878;

  for(var i = 0; i < x.length; i += 16)
  {
    var olda = a;
    var oldb = b;
    var oldc = c;
    var oldd = d;

    a = md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
    d = md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
    c = md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
    b = md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
    a = md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
    d = md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
    c = md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
    b = md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
    a = md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
    d = md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
    c = md5_ff(c, d, a, b, x[i+10], 17, -42063);
    b = md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
    a = md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
    d = md5_ff(d, a, b, c, x[i+13], 12, -40341101);
    c = md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
    b = md5_ff(b, c, d, a, x[i+15], 22,  1236535329);

    a = md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
    d = md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
    c = md5_gg(c, d, a, b, x[i+11], 14,  643717713);
    b = md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
    a = md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
    d = md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
    c = md5_gg(c, d, a, b, x[i+15], 14, -660478335);
    b = md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
    a = md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
    d = md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
    c = md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
    b = md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
    a = md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
    d = md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
    c = md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
    b = md5_gg(b, c, d, a, x[i+12], 20, -1926607734);

    a = md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
    d = md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
    c = md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
    b = md5_hh(b, c, d, a, x[i+14], 23, -35309556);
    a = md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
    d = md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
    c = md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
    b = md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
    a = md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
    d = md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
    c = md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
    b = md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
    a = md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
    d = md5_hh(d, a, b, c, x[i+12], 11, -421815835);
    c = md5_hh(c, d, a, b, x[i+15], 16,  530742520);
    b = md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);

    a = md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
    d = md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
    c = md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
    b = md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
    a = md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
    d = md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
    c = md5_ii(c, d, a, b, x[i+10], 15, -1051523);
    b = md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
    a = md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
    d = md5_ii(d, a, b, c, x[i+15], 10, -30611744);
    c = md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
    b = md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
    a = md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
    d = md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
    c = md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
    b = md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);

    a = safe_add(a, olda);
    b = safe_add(b, oldb);
    c = safe_add(c, oldc);
    d = safe_add(d, oldd);
  }
  return Array(a, b, c, d);
}

/*
 * These functions implement the four basic operations the algorithm uses.
 */
function md5_cmn(q, a, b, x, s, t)
{
  return safe_add(bit_rol(safe_add(safe_add(a, q), safe_add(x, t)), s),b);
}
function md5_ff(a, b, c, d, x, s, t)
{
  return md5_cmn((b & c) | ((~b) & d), a, b, x, s, t);
}
function md5_gg(a, b, c, d, x, s, t)
{
  return md5_cmn((b & d) | (c & (~d)), a, b, x, s, t);
}
function md5_hh(a, b, c, d, x, s, t)
{
  return md5_cmn(b ^ c ^ d, a, b, x, s, t);
}
function md5_ii(a, b, c, d, x, s, t)
{
  return md5_cmn(c ^ (b | (~d)), a, b, x, s, t);
}


/* jshint node: true, browser: true, nonstandard: true, eqeqeq: true, eqnull: true */

(function(factory) {
    if (typeof exports === 'object') {
        // CommonJS
        factory(exports, require('crypto'));
    } else {
        // Browser/Worker globals
        /* global self */
        factory(self.TwinBcrypt = {}, self.crypto || self.msCrypto);
    }
}(function (exports, crypto) {
    "use strict";

    var isFirefox = typeof InstallTrigger !== 'undefined';
    var useAsm = isFirefox;
    var randomBytes;

    if (crypto) {
        // Nodejs crypto random number generator
        randomBytes = crypto.randomBytes;
        
        // Cryptographic-quality random number generator for newer browsers.
        if (crypto.getRandomValues) {
            randomBytes = function(numBytes) {
                var array = new Uint8Array(numBytes);
                return crypto.getRandomValues(array);
            };
        }
    }

    // utf-8 conversion for browsers and Node.
    function string2utf8Bytes(s) {
        var utf8 = unescape(encodeURIComponent(s)),
            len = utf8.length,
            bytes = new Array(len);
        for (var i = 0; i < len; i++) {
            bytes[i] = utf8.charCodeAt(i);
        }
        return bytes;
    }

    function string2rawBytes(s) {
        var len = s.length,
            bytes = new Array(len);
        for (var i = 0; i < len; i++) {
            bytes[i] = s.charCodeAt(i);
        }
        return bytes;
    }

    var BCRYPT_SALT_LEN = 16;
    var GENSALT_DEFAULT_LOG2_ROUNDS = 10;
    var BLOWFISH_NUM_ROUNDS = 16;

    var P = [0x243f6a88, 0x85a308d3, 0x13198a2e, 0x03707344, 0xa4093822,
            0x299f31d0, 0x082efa98, 0xec4e6c89, 0x452821e6, 0x38d01377,
            0xbe5466cf, 0x34e90c6c, 0xc0ac29b7, 0xc97c50dd, 0x3f84d5b5,
            0xb5470917, 0x9216d5d9, 0x8979fb1b];
    var S = [0xd1310ba6, 0x98dfb5ac, 0x2ffd72db, 0xd01adfb7, 0xb8e1afed,
            0x6a267e96, 0xba7c9045, 0xf12c7f99, 0x24a19947, 0xb3916cf7,
            0x0801f2e2, 0x858efc16, 0x636920d8, 0x71574e69, 0xa458fea3,
            0xf4933d7e, 0x0d95748f, 0x728eb658, 0x718bcd58, 0x82154aee,
            0x7b54a41d, 0xc25a59b5, 0x9c30d539, 0x2af26013, 0xc5d1b023,
            0x286085f0, 0xca417918, 0xb8db38ef, 0x8e79dcb0, 0x603a180e,
            0x6c9e0e8b, 0xb01e8a3e, 0xd71577c1, 0xbd314b27, 0x78af2fda,
            0x55605c60, 0xe65525f3, 0xaa55ab94, 0x57489862, 0x63e81440,
            0x55ca396a, 0x2aab10b6, 0xb4cc5c34, 0x1141e8ce, 0xa15486af,
            0x7c72e993, 0xb3ee1411, 0x636fbc2a, 0x2ba9c55d, 0x741831f6,
            0xce5c3e16, 0x9b87931e, 0xafd6ba33, 0x6c24cf5c, 0x7a325381,
            0x28958677, 0x3b8f4898, 0x6b4bb9af, 0xc4bfe81b, 0x66282193,
            0x61d809cc, 0xfb21a991, 0x487cac60, 0x5dec8032, 0xef845d5d,
            0xe98575b1, 0xdc262302, 0xeb651b88, 0x23893e81, 0xd396acc5,
            0x0f6d6ff3, 0x83f44239, 0x2e0b4482, 0xa4842004, 0x69c8f04a,
            0x9e1f9b5e, 0x21c66842, 0xf6e96c9a, 0x670c9c61, 0xabd388f0,
            0x6a51a0d2, 0xd8542f68, 0x960fa728, 0xab5133a3, 0x6eef0b6c,
            0x137a3be4, 0xba3bf050, 0x7efb2a98, 0xa1f1651d, 0x39af0176,
            0x66ca593e, 0x82430e88, 0x8cee8619, 0x456f9fb4, 0x7d84a5c3,
            0x3b8b5ebe, 0xe06f75d8, 0x85c12073, 0x401a449f, 0x56c16aa6,
            0x4ed3aa62, 0x363f7706, 0x1bfedf72, 0x429b023d, 0x37d0d724,
            0xd00a1248, 0xdb0fead3, 0x49f1c09b, 0x075372c9, 0x80991b7b,
            0x25d479d8, 0xf6e8def7, 0xe3fe501a, 0xb6794c3b, 0x976ce0bd,
            0x04c006ba, 0xc1a94fb6, 0x409f60c4, 0x5e5c9ec2, 0x196a2463,
            0x68fb6faf, 0x3e6c53b5, 0x1339b2eb, 0x3b52ec6f, 0x6dfc511f,
            0x9b30952c, 0xcc814544, 0xaf5ebd09, 0xbee3d004, 0xde334afd,
            0x660f2807, 0x192e4bb3, 0xc0cba857, 0x45c8740f, 0xd20b5f39,
            0xb9d3fbdb, 0x5579c0bd, 0x1a60320a, 0xd6a100c6, 0x402c7279,
            0x679f25fe, 0xfb1fa3cc, 0x8ea5e9f8, 0xdb3222f8, 0x3c7516df,
            0xfd616b15, 0x2f501ec8, 0xad0552ab, 0x323db5fa, 0xfd238760,
            0x53317b48, 0x3e00df82, 0x9e5c57bb, 0xca6f8ca0, 0x1a87562e,
            0xdf1769db, 0xd542a8f6, 0x287effc3, 0xac6732c6, 0x8c4f5573,
            0x695b27b0, 0xbbca58c8, 0xe1ffa35d, 0xb8f011a0, 0x10fa3d98,
            0xfd2183b8, 0x4afcb56c, 0x2dd1d35b, 0x9a53e479, 0xb6f84565,
            0xd28e49bc, 0x4bfb9790, 0xe1ddf2da, 0xa4cb7e33, 0x62fb1341,
            0xcee4c6e8, 0xef20cada, 0x36774c01, 0xd07e9efe, 0x2bf11fb4,
            0x95dbda4d, 0xae909198, 0xeaad8e71, 0x6b93d5a0, 0xd08ed1d0,
            0xafc725e0, 0x8e3c5b2f, 0x8e7594b7, 0x8ff6e2fb, 0xf2122b64,
            0x8888b812, 0x900df01c, 0x4fad5ea0, 0x688fc31c, 0xd1cff191,
            0xb3a8c1ad, 0x2f2f2218, 0xbe0e1777, 0xea752dfe, 0x8b021fa1,
            0xe5a0cc0f, 0xb56f74e8, 0x18acf3d6, 0xce89e299, 0xb4a84fe0,
            0xfd13e0b7, 0x7cc43b81, 0xd2ada8d9, 0x165fa266, 0x80957705,
            0x93cc7314, 0x211a1477, 0xe6ad2065, 0x77b5fa86, 0xc75442f5,
            0xfb9d35cf, 0xebcdaf0c, 0x7b3e89a0, 0xd6411bd3, 0xae1e7e49,
            0x00250e2d, 0x2071b35e, 0x226800bb, 0x57b8e0af, 0x2464369b,
            0xf009b91e, 0x5563911d, 0x59dfa6aa, 0x78c14389, 0xd95a537f,
            0x207d5ba2, 0x02e5b9c5, 0x83260376, 0x6295cfa9, 0x11c81968,
            0x4e734a41, 0xb3472dca, 0x7b14a94a, 0x1b510052, 0x9a532915,
            0xd60f573f, 0xbc9bc6e4, 0x2b60a476, 0x81e67400, 0x08ba6fb5,
            0x571be91f, 0xf296ec6b, 0x2a0dd915, 0xb6636521, 0xe7b9f9b6,
            0xff34052e, 0xc5855664, 0x53b02d5d, 0xa99f8fa1, 0x08ba4799,
            0x6e85076a, 0x4b7a70e9, 0xb5b32944, 0xdb75092e, 0xc4192623,
            0xad6ea6b0, 0x49a7df7d, 0x9cee60b8, 0x8fedb266, 0xecaa8c71,
            0x699a17ff, 0x5664526c, 0xc2b19ee1, 0x193602a5, 0x75094c29,
            0xa0591340, 0xe4183a3e, 0x3f54989a, 0x5b429d65, 0x6b8fe4d6,
            0x99f73fd6, 0xa1d29c07, 0xefe830f5, 0x4d2d38e6, 0xf0255dc1,
            0x4cdd2086, 0x8470eb26, 0x6382e9c6, 0x021ecc5e, 0x09686b3f,
            0x3ebaefc9, 0x3c971814, 0x6b6a70a1, 0x687f3584, 0x52a0e286,
            0xb79c5305, 0xaa500737, 0x3e07841c, 0x7fdeae5c, 0x8e7d44ec,
            0x5716f2b8, 0xb03ada37, 0xf0500c0d, 0xf01c1f04, 0x0200b3ff,
            0xae0cf51a, 0x3cb574b2, 0x25837a58, 0xdc0921bd, 0xd19113f9,
            0x7ca92ff6, 0x94324773, 0x22f54701, 0x3ae5e581, 0x37c2dadc,
            0xc8b57634, 0x9af3dda7, 0xa9446146, 0x0fd0030e, 0xecc8c73e,
            0xa4751e41, 0xe238cd99, 0x3bea0e2f, 0x3280bba1, 0x183eb331,
            0x4e548b38, 0x4f6db908, 0x6f420d03, 0xf60a04bf, 0x2cb81290,
            0x24977c79, 0x5679b072, 0xbcaf89af, 0xde9a771f, 0xd9930810,
            0xb38bae12, 0xdccf3f2e, 0x5512721f, 0x2e6b7124, 0x501adde6,
            0x9f84cd87, 0x7a584718, 0x7408da17, 0xbc9f9abc, 0xe94b7d8c,
            0xec7aec3a, 0xdb851dfa, 0x63094366, 0xc464c3d2, 0xef1c1847,
            0x3215d908, 0xdd433b37, 0x24c2ba16, 0x12a14d43, 0x2a65c451,
            0x50940002, 0x133ae4dd, 0x71dff89e, 0x10314e55, 0x81ac77d6,
            0x5f11199b, 0x043556f1, 0xd7a3c76b, 0x3c11183b, 0x5924a509,
            0xf28fe6ed, 0x97f1fbfa, 0x9ebabf2c, 0x1e153c6e, 0x86e34570,
            0xeae96fb1, 0x860e5e0a, 0x5a3e2ab3, 0x771fe71c, 0x4e3d06fa,
            0x2965dcb9, 0x99e71d0f, 0x803e89d6, 0x5266c825, 0x2e4cc978,
            0x9c10b36a, 0xc6150eba, 0x94e2ea78, 0xa5fc3c53, 0x1e0a2df4,
            0xf2f74ea7, 0x361d2b3d, 0x1939260f, 0x19c27960, 0x5223a708,
            0xf71312b6, 0xebadfe6e, 0xeac31f66, 0xe3bc4595, 0xa67bc883,
            0xb17f37d1, 0x018cff28, 0xc332ddef, 0xbe6c5aa5, 0x65582185,
            0x68ab9802, 0xeecea50f, 0xdb2f953b, 0x2aef7dad, 0x5b6e2f84,
            0x1521b628, 0x29076170, 0xecdd4775, 0x619f1510, 0x13cca830,
            0xeb61bd96, 0x0334fe1e, 0xaa0363cf, 0xb5735c90, 0x4c70a239,
            0xd59e9e0b, 0xcbaade14, 0xeecc86bc, 0x60622ca7, 0x9cab5cab,
            0xb2f3846e, 0x648b1eaf, 0x19bdf0ca, 0xa02369b9, 0x655abb50,
            0x40685a32, 0x3c2ab4b3, 0x319ee9d5, 0xc021b8f7, 0x9b540b19,
            0x875fa099, 0x95f7997e, 0x623d7da8, 0xf837889a, 0x97e32d77,
            0x11ed935f, 0x16681281, 0x0e358829, 0xc7e61fd6, 0x96dedfa1,
            0x7858ba99, 0x57f584a5, 0x1b227263, 0x9b83c3ff, 0x1ac24696,
            0xcdb30aeb, 0x532e3054, 0x8fd948e4, 0x6dbc3128, 0x58ebf2ef,
            0x34c6ffea, 0xfe28ed61, 0xee7c3c73, 0x5d4a14d9, 0xe864b7e3,
            0x42105d14, 0x203e13e0, 0x45eee2b6, 0xa3aaabea, 0xdb6c4f15,
            0xfacb4fd0, 0xc742f442, 0xef6abbb5, 0x654f3b1d, 0x41cd2105,
            0xd81e799e, 0x86854dc7, 0xe44b476a, 0x3d816250, 0xcf62a1f2,
            0x5b8d2646, 0xfc8883a0, 0xc1c7b6a3, 0x7f1524c3, 0x69cb7492,
            0x47848a0b, 0x5692b285, 0x095bbf00, 0xad19489d, 0x1462b174,
            0x23820e00, 0x58428d2a, 0x0c55f5ea, 0x1dadf43e, 0x233f7061,
            0x3372f092, 0x8d937e41, 0xd65fecf1, 0x6c223bdb, 0x7cde3759,
            0xcbee7460, 0x4085f2a7, 0xce77326e, 0xa6078084, 0x19f8509e,
            0xe8efd855, 0x61d99735, 0xa969a7aa, 0xc50c06c2, 0x5a04abfc,
            0x800bcadc, 0x9e447a2e, 0xc3453484, 0xfdd56705, 0x0e1e9ec9,
            0xdb73dbd3, 0x105588cd, 0x675fda79, 0xe3674340, 0xc5c43465,
            0x713e38d8, 0x3d28f89e, 0xf16dff20, 0x153e21e7, 0x8fb03d4a,
            0xe6e39f2b, 0xdb83adf7, 0xe93d5a68, 0x948140f7, 0xf64c261c,
            0x94692934, 0x411520f7, 0x7602d4f7, 0xbcf46b2e, 0xd4a20068,
            0xd4082471, 0x3320f46a, 0x43b7d4b7, 0x500061af, 0x1e39f62e,
            0x97244546, 0x14214f74, 0xbf8b8840, 0x4d95fc1d, 0x96b591af,
            0x70f4ddd3, 0x66a02f45, 0xbfbc09ec, 0x03bd9785, 0x7fac6dd0,
            0x31cb8504, 0x96eb27b3, 0x55fd3941, 0xda2547e6, 0xabca0a9a,
            0x28507825, 0x530429f4, 0x0a2c86da, 0xe9b66dfb, 0x68dc1462,
            0xd7486900, 0x680ec0a4, 0x27a18dee, 0x4f3ffea2, 0xe887ad8c,
            0xb58ce006, 0x7af4d6b6, 0xaace1e7c, 0xd3375fec, 0xce78a399,
            0x406b2a42, 0x20fe9e35, 0xd9f385b9, 0xee39d7ab, 0x3b124e8b,
            0x1dc9faf7, 0x4b6d1856, 0x26a36631, 0xeae397b2, 0x3a6efa74,
            0xdd5b4332, 0x6841e7f7, 0xca7820fb, 0xfb0af54e, 0xd8feb397,
            0x454056ac, 0xba489527, 0x55533a3a, 0x20838d87, 0xfe6ba9b7,
            0xd096954b, 0x55a867bc, 0xa1159a58, 0xcca92963, 0x99e1db33,
            0xa62a4a56, 0x3f3125f9, 0x5ef47e1c, 0x9029317c, 0xfdf8e802,
            0x04272f70, 0x80bb155c, 0x05282ce3, 0x95c11548, 0xe4c66d22,
            0x48c1133f, 0xc70f86dc, 0x07f9c9ee, 0x41041f0f, 0x404779a4,
            0x5d886e17, 0x325f51eb, 0xd59bc0d1, 0xf2bcc18f, 0x41113564,
            0x257b7834, 0x602a9c60, 0xdff8e8a3, 0x1f636c1b, 0x0e12b4c2,
            0x02e1329e, 0xaf664fd1, 0xcad18115, 0x6b2395e0, 0x333e92e1,
            0x3b240b62, 0xeebeb922, 0x85b2a20e, 0xe6ba0d99, 0xde720c8c,
            0x2da2f728, 0xd0127845, 0x95b794fd, 0x647d0862, 0xe7ccf5f0,
            0x5449a36f, 0x877d48fa, 0xc39dfd27, 0xf33e8d1e, 0x0a476341,
            0x992eff74, 0x3a6f6eab, 0xf4f8fd37, 0xa812dc60, 0xa1ebddf8,
            0x991be14c, 0xdb6e6b0d, 0xc67b5510, 0x6d672c37, 0x2765d43b,
            0xdcd0e804, 0xf1290dc7, 0xcc00ffa3, 0xb5390f92, 0x690fed0b,
            0x667b9ffb, 0xcedb7d9c, 0xa091cf0b, 0xd9155ea3, 0xbb132f88,
            0x515bad24, 0x7b9479bf, 0x763bd6eb, 0x37392eb3, 0xcc115979,
            0x8026e297, 0xf42e312d, 0x6842ada7, 0xc66a2b3b, 0x12754ccc,
            0x782ef11c, 0x6a124237, 0xb79251e7, 0x06a1bbe6, 0x4bfb6350,
            0x1a6b1018, 0x11caedfa, 0x3d25bdd8, 0xe2e1c3c9, 0x44421659,
            0x0a121386, 0xd90cec6e, 0xd5abea2a, 0x64af674e, 0xda86a85f,
            0xbebfe988, 0x64e4c3fe, 0x9dbc8057, 0xf0f7c086, 0x60787bf8,
            0x6003604d, 0xd1fd8346, 0xf6381fb0, 0x7745ae04, 0xd736fccc,
            0x83426b33, 0xf01eab71, 0xb0804187, 0x3c005e5f, 0x77a057be,
            0xbde8ae24, 0x55464299, 0xbf582e61, 0x4e58f48f, 0xf2ddfda2,
            0xf474ef38, 0x8789bdc2, 0x5366f9c3, 0xc8b38e74, 0xb475f255,
            0x46fcd9b9, 0x7aeb2661, 0x8b1ddf84, 0x846a0e79, 0x915f95e2,
            0x466e598e, 0x20b45770, 0x8cd55591, 0xc902de4c, 0xb90bace1,
            0xbb8205d0, 0x11a86248, 0x7574a99e, 0xb77f19b6, 0xe0a9dc09,
            0x662d09a1, 0xc4324633, 0xe85a1f02, 0x09f0be8c, 0x4a99a025,
            0x1d6efe10, 0x1ab93d1d, 0x0ba5a4df, 0xa186f20f, 0x2868f169,
            0xdcb7da83, 0x573906fe, 0xa1e2ce9b, 0x4fcd7f52, 0x50115e01,
            0xa70683fa, 0xa002b5c4, 0x0de6d027, 0x9af88c27, 0x773f8641,
            0xc3604c06, 0x61a806b5, 0xf0177a28, 0xc0f586e0, 0x006058aa,
            0x30dc7d62, 0x11e69ed7, 0x2338ea63, 0x53c2dd94, 0xc2c21634,
            0xbbcbee56, 0x90bcb6de, 0xebfc7da1, 0xce591d76, 0x6f05e409,
            0x4b7c0188, 0x39720a3d, 0x7c927c24, 0x86e3725f, 0x724d9db9,
            0x1ac15bb4, 0xd39eb8fc, 0xed545578, 0x08fca5b5, 0xd83d7cd3,
            0x4dad0fc4, 0x1e50ef5e, 0xb161e6f8, 0xa28514d9, 0x6c51133c,
            0x6fd5c7e7, 0x56e14ec4, 0x362abfce, 0xddc6c837, 0xd79a3234,
            0x92638212, 0x670efa8e, 0x406000e0, 0x3a39ce37, 0xd3faf5cf,
            0xabc27737, 0x5ac52d1b, 0x5cb0679e, 0x4fa33742, 0xd3822740,
            0x99bc9bbe, 0xd5118e9d, 0xbf0f7315, 0xd62d1c7e, 0xc700c47b,
            0xb78c1b6b, 0x21a19045, 0xb26eb1be, 0x6a366eb4, 0x5748ab2f,
            0xbc946e79, 0xc6a376d2, 0x6549c2c8, 0x530ff8ee, 0x468dde7d,
            0xd5730a1d, 0x4cd04dc6, 0x2939bbdb, 0xa9ba4650, 0xac9526e8,
            0xbe5ee304, 0xa1fad5f0, 0x6a2d519a, 0x63ef8ce2, 0x9a86ee22,
            0xc089c2b8, 0x43242ef6, 0xa51e03aa, 0x9cf2d0a4, 0x83c061ba,
            0x9be96a4d, 0x8fe51550, 0xba645bd6, 0x2826a2f9, 0xa73a3ae1,
            0x4ba99586, 0xef5562e9, 0xc72fefd3, 0xf752f7da, 0x3f046f69,
            0x77fa0a59, 0x80e4a915, 0x87b08601, 0x9b09e6ad, 0x3b3ee593,
            0xe990fd5a, 0x9e34d797, 0x2cf0b7d9, 0x022b8b51, 0x96d5ac3a,
            0x017da67d, 0xd1cf3ed6, 0x7c7d2d28, 0x1f9f25cf, 0xadf2b89b,
            0x5ad6b472, 0x5a88f54c, 0xe029ac71, 0xe019a5e6, 0x47b0acfd,
            0xed93fa9b, 0xe8d3c48d, 0x283b57cc, 0xf8d56629, 0x79132e28,
            0x785f0191, 0xed756055, 0xf7960e44, 0xe3d35e8c, 0x15056dd4,
            0x88f46dba, 0x03a16125, 0x0564f0bd, 0xc3eb9e15, 0x3c9057a2,
            0x97271aec, 0xa93a072a, 0x1b3f6d9b, 0x1e6321f5, 0xf59c66fb,
            0x26dcf319, 0x7533d928, 0xb155fdf5, 0x03563482, 0x8aba3cbb,
            0x28517711, 0xc20ad9f8, 0xabcc5167, 0xccad925f, 0x4de81751,
            0x3830dc8e, 0x379d5862, 0x9320f991, 0xea7a90c2, 0xfb3e7bce,
            0x5121ce64, 0x774fbe32, 0xa8b6e37e, 0xc3293d46, 0x48de5369,
            0x6413e680, 0xa2ae0810, 0xdd6db224, 0x69852dfd, 0x09072166,
            0xb39a460a, 0x6445c0dd, 0x586cdecf, 0x1c20c8ae, 0x5bbef7dd,
            0x1b588d40, 0xccd2017f, 0x6bb4e3bb, 0xdda26a7e, 0x3a59ff45,
            0x3e350a44, 0xbcb4cdd5, 0x72eacea8, 0xfa6484bb, 0x8d6612ae,
            0xbf3c6f47, 0xd29be463, 0x542f5d9e, 0xaec2771b, 0xf64e6370,
            0x740e0d8d, 0xe75b1357, 0xf8721671, 0xaf537d5d, 0x4040cb08,
            0x4eb4e2cc, 0x34d2466a, 0x0115af84, 0xe1b00428, 0x95983a1d,
            0x06b89fb4, 0xce6ea048, 0x6f3f3b82, 0x3520ab82, 0x011a1d4b,
            0x277227f8, 0x611560b1, 0xe7933fdc, 0xbb3a792b, 0x344525bd,
            0xa08839e1, 0x51ce794b, 0x2f32c9b7, 0xa01fbac9, 0xe01cc87e,
            0xbcc7d1f6, 0xcf0111c3, 0xa1e8aac7, 0x1a908749, 0xd44fbd9a,
            0xd0dadecb, 0xd50ada38, 0x0339c32a, 0xc6913667, 0x8df9317c,
            0xe0b12b4f, 0xf79e59b7, 0x43f5bb3a, 0xf2d519ff, 0x27d9459c,
            0xbf97222c, 0x15e6fc2a, 0x0f91fc71, 0x9b941525, 0xfae59361,
            0xceb69ceb, 0xc2a86459, 0x12baa8d1, 0xb6c1075e, 0xe3056a0c,
            0x10d25065, 0xcb03a442, 0xe0ec6e0e, 0x1698db3b, 0x4c98a0be,
            0x3278e964, 0x9f1f9532, 0xe0d392df, 0xd3a0342b, 0x8971f21e,
            0x1b0a7441, 0x4ba3348c, 0xc5be7120, 0xc37632d8, 0xdf359f8d,
            0x9b992f2e, 0xe60b6f47, 0x0fe3f11d, 0xe54cda54, 0x1edad891,
            0xce6279cf, 0xcd3e7e6f, 0x1618b166, 0xfd2c1d05, 0x848fd2c5,
            0xf6fb2299, 0xf523f357, 0xa6327623, 0x93a83531, 0x56cccd02,
            0xacf08162, 0x5a75ebb5, 0x6e163697, 0x88d273cc, 0xde966292,
            0x81b949d0, 0x4c50901b, 0x71c65614, 0xe6c6c7bd, 0x327a140a,
            0x45e1d006, 0xc3f27b9a, 0xc9aa53fd, 0x62a80f00, 0xbb25bfe2,
            0x35bdd2f6, 0x71126905, 0xb2040222, 0xb6cbcf7c, 0xcd769c2b,
            0x53113ec0, 0x1640e3d3, 0x38abbd60, 0x2547adf0, 0xba38209c,
            0xf746ce76, 0x77afa1c5, 0x20756060, 0x85cbfe4e, 0x8ae88dd8,
            0x7aaaf9b0, 0x4cf9aa7e, 0x1948c25c, 0x02fb8a8c, 0x01c36ae4,
            0xd6ebe1f9, 0x90d4f869, 0xa65cdea0, 0x3f09252d, 0xc208e69f,
            0xb74e6132, 0xce77e25b, 0x578fdfe3, 0x3ac372e6];
    var P_LEN = P.length,
        S_LEN = S.length;
    var bf_crypt_ciphertext = [0x4f727068, 0x65616e42, 0x65686f6c, 0x64657253,
            0x63727944, 0x6f756274];

    var S_offset = 0x0000,
        P_offset = 0x1000,
        P_last_offset = 0x1044,
        crypt_ciphertext_offset = 0x1048,
        LR_offset = 0x01060,
        password_offset = 0x1068,
        salt_offset = 0x10b0;
    
    var base64_code = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var index_64 = [0, 1,
            54, 55, 56, 57, 58, 59, 60, 61, 62, 63, -1, -1, -1, -1, -1, -1, -1,
            2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
            21, 22, 23, 24, 25, 26, 27, -1, -1, -1, -1, -1, -1, 28, 29, 30, 31,
            32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48,
            49, 50, 51, 52, 53, -1, -1, -1, -1, -1];

    function encode_base64(d, len) {
        var off = 0,
            rs = '',
            c1, c2;
        while (off < len) {
            c1 = d[off++] & 0xff;
            rs += base64_code[c1 >> 2];
            c1 = (c1 & 0x03) << 4;
            if (off >= len) {
                rs += base64_code[c1];
                break;
            }
            c2 = d[off++] & 0xff;
            c1 |= c2 >> 4;
            rs += base64_code[c1];
            c1 = (c2 & 0x0f) << 2;
            if (off >= len) {
                rs += base64_code[c1];
                break;
            }
            c2 = d[off++] & 0xff;
            c1 |= (c2 >> 6);
            rs += base64_code[c1];
            rs += base64_code[c2 & 0x3f];
        }
        return rs;
    }

    /**
     * salt is a 22 character string from the alphabet
     * './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
     */
    function decode_base64(salt) {
        var decoded = new Array(16);
        var i = 0, j = 0, c1, c2;
        while(true) {
            c1 = index_64[salt.charCodeAt(i++)-46];
            c2 = index_64[salt.charCodeAt(i++)-46];
            decoded[j++] = (c1 << 2 | c2 >> 4) & 0xff;
            if (i === 22) break;
            
            c1 = c2 << 4;
            c2 = index_64[salt.charCodeAt(i++)-46];
            decoded[j++] = (c1 | c2 >> 2) & 0xff;

            c1 = c2 << 6;
            c2 = index_64[salt.charCodeAt(i++)-46];
            decoded[j++] = (c1 | c2) & 0xff;
        }
        return decoded;
    }

    function cycle72(a) {
        var len = a.length, b = new Array(72), i = 0, j = 0;
        while (j < 72) {
            b[j++] = a[i++];
            if (i === len) i = 0;
        }
        return b;
    }

    function heap32Set(dest, source, offset) {
        for (var i = 0, o = offset >> 2; i < source.length; i++, o++) {
            dest[o] = source[i];
        }
    }

    function copyBigEndian(heap32, offset, data) {
        for (var i = 0; i < 72;) {
            heap32[offset++] = data[i++] << 24 | data[i++] << 16 | data[i++] << 8 | data[i++];
        }
    }


    function bcryptNoAsm(heap32) {
        var s0 = S_offset >> 2,
            s1 = (s0 + 0x100) | 0,
            s2 = (s1 + 0x100) | 0,
            s3 = (s2 + 0x100) | 0;

function encrypt(offset) {
  var h = heap32, i = P_offset >> 2, iend = i | BLOWFISH_NUM_ROUNDS;
  var o = offset>>2, L = h[o] ^ h[i], R = h[o|1];
  while (i<iend) {
    R^=(h[L>>>24]+h[s1|(L>>>16&0xff)]^h[s2|(L>>>8&0xff)])+h[s3|(L&0xff)]^h[++i];
    L^=(h[R>>>24]+h[s1|(R>>>16&0xff)]^h[s2|(R>>>8&0xff)])+h[s3|(R&0xff)]^h[++i];
  }
  h[o] = R ^ h[P_last_offset>>2];
  h[o|1] = L;
}
        function expandKey(offset) {
            var i;
            heap32[LR_offset >> 2] = 0;
            heap32[LR_offset + 4 >> 2] = 0;

            for (i = 0; i < P_LEN; i++) {
                heap32[P_offset >> 2 | i] ^= heap32[(offset >> 2) + i];
            }
            
            var h = heap32, j, jend, o, L, R;
            for (i = 0; i < P_LEN; i += 2) {
                j = P_offset >> 2; jend = j | BLOWFISH_NUM_ROUNDS;
                o = LR_offset>>2; L = h[o] ^ h[j]; R = h[o|1];
                while (j<jend) {
                    R^=(h[L>>>24]+h[s1|(L>>>16&0xff)]^h[s2|(L>>>8&0xff)])+h[s3|(L&0xff)]^h[++j];
                    L^=(h[R>>>24]+h[s1|(R>>>16&0xff)]^h[s2|(R>>>8&0xff)])+h[s3|(R&0xff)]^h[++j];
                }
                h[o] = R ^ h[P_last_offset>>2];
                h[o|1] = L;
                heap32[P_offset >> 2 | i] = h[o];
                heap32[P_offset >> 2 | i + 1] = L;
            }

            for (i = 0; i < S_LEN; i += 2) {
                j = P_offset >> 2; jend = j | BLOWFISH_NUM_ROUNDS;
                o = LR_offset>>2; L = h[o] ^ h[j]; R = h[o|1];
                while (j<jend) {
                    R^=(h[L>>>24]+h[s1|(L>>>16&0xff)]^h[s2|(L>>>8&0xff)])+h[s3|(L&0xff)]^h[++j];
                    L^=(h[R>>>24]+h[s1|(R>>>16&0xff)]^h[s2|(R>>>8&0xff)])+h[s3|(R&0xff)]^h[++j];
                }
                h[o] = R ^ h[P_last_offset>>2];
                h[o|1] = L;
                heap32[i] = h[o];
                heap32[i|1] = L;
            }
        }

        function expandLoop(i, counterEnd, maxIterations) {
            for (var j = 0; j <= maxIterations; j++) {
                if (i > counterEnd) break;
                expandKey(password_offset);
                expandKey(salt_offset);
                i++;
            }
            return i;
        }
        
        return {
            encrypt: encrypt,
            expandLoop: expandLoop
        };
    }

    /* @preserve BEGIN ASM */
    function bcryptAsm(stdlib, foreign, heap) {
        "use asm";

        var HEAP32 = new stdlib.Uint32Array(heap);

        var BLOWFISH_NUM_ROUNDS = 16;
        var S_offset = 0x0000;          // length 0x1000 octets
        var S1_offset = 0x0400;
        var S2_offset = 0x0800;
        var S3_offset = 0x0C00;
        var P_offset = 0x1000;          // length 0x0048 (72) octets
        var P_last_offset = 0x1044;
        var crypt_ciphertext_offset = 0x1048;    // length 0x0018 (24) octets
        var LR_offset = 0x01060;        // length 8 octets
        var password_offset = 0x1068;   // length 0x0048 (72) octets
        var salt_offset = 0x10b0;       // length 0x0048 (72) octets, 18 uint32
        var P_LEN = 18;
        var S_LEN = 1024;

        function encrypt(offset) {
            offset = offset|0;
            var i = 0;
            var n = 0;
            var L = 0;
            var R = 0;
            var imax = 0;
            imax = P_offset | BLOWFISH_NUM_ROUNDS << 2;
            
            L = HEAP32[offset >> 2]|0;
            R = HEAP32[offset + 4 >> 2]|0;

            L = L ^ HEAP32[P_offset>>2];
            for (i = P_offset; (i|0) < (imax|0);) {
                // Feistel substitution on left word
                i = (i + 4)>>>0;
                R = R ^ (
                    ((HEAP32[(L >>> 22)>>2] >>> 0) +
                    (HEAP32[(S1_offset | (L >>> 14 & 0x3ff))>>2] >>> 0) ^
                    (HEAP32[(S2_offset | (L >>> 6 & 0x3ff))>>2])) +
                    (HEAP32[(S3_offset | (L << 2 & 0x3ff))>>2] >>> 0)
                ) ^ HEAP32[i>>2];

                // Feistel substitution on right word
                i = (i + 4)>>>0;
                L = L ^ (
                    ((HEAP32[(R >>> 22)>>2] >>> 0) +
                    (HEAP32[(S1_offset | (R >>> 14 & 0x3ff))>>2] >>> 0) ^
                    (HEAP32[(S2_offset | (R >>> 6 & 0x3ff))>>2])) +
                    (HEAP32[(S3_offset | (R << 2 & 0x3ff))>>2] >>> 0)
                ) ^ HEAP32[i>>2];
            }
            HEAP32[offset>>2] = R ^ HEAP32[P_last_offset>>2];
            HEAP32[(offset+4)>>2] = L;
        }

        function expandKey(offset) {
            offset = offset|0;
            var i = 0;
            var off = 0;

            off = P_offset|0;
            for (i = 0; (i|0) < (P_LEN|0); i = (i+1)|0) {
                HEAP32[off >> 2] = HEAP32[off >> 2] ^ HEAP32[offset >> 2];
                offset = (offset + 4)|0;
                off = (off + 4)|0;
            }

            HEAP32[LR_offset >> 2] = 0;
            HEAP32[LR_offset + 4 >> 2] = 0;

            off = P_offset;
            for (i = 0; (i|0) < (P_LEN|0); i = (i+2)|0) {
                encrypt(LR_offset);
                HEAP32[off >> 2] = HEAP32[LR_offset >> 2];
                HEAP32[off + 4 >> 2] = HEAP32[LR_offset + 4 >> 2];
                off = (off + 8)|0;
            }

            off = S_offset;
            for (i = 0; (i|0) < (S_LEN|0); i = (i+2)|0) {
                encrypt(LR_offset);
                HEAP32[off >> 2] = HEAP32[LR_offset >> 2];
                HEAP32[off + 4 >> 2] = HEAP32[LR_offset + 4 >> 2];
                off = (off + 8)|0;
            }
        }

        function expandLoop(i, counterEnd, maxIterations) {
            i = i|0;
            counterEnd = counterEnd|0;
            maxIterations = maxIterations|0;
            var j = 0;

            for (j = 0; (j|0) <= (maxIterations|0); j = (j+1)|0) {
                if ((i>>>0) > (counterEnd>>>0)) break;
                expandKey(password_offset);
                expandKey(salt_offset);
                i = (i+1)>>>0;
            }
            return i|0;
        }

        return {
            encrypt: encrypt,
            expandLoop: expandLoop
        };
    }
    /* @preserve END ASM */

    function ekskey(data, key, engine, heap32) {
        var i, off, sw;
        var L_offset = LR_offset >> 2,
            R_offset = L_offset + 1;

        heap32[L_offset] = 0;
        heap32[R_offset] = 0;
        
        off = 0;
        for (i = 0; i < P_LEN; i++) {
            sw = key[off++] << 24 | key[off++] << 16 | key[off++] << 8 | key[off++];
            heap32[P_offset >> 2 | i] ^= sw;
        }
        
        off = 0;
        for (i = 0; i < P_LEN; i += 2) {
            sw = data[off++] << 24 | data[off++] << 16 | data[off++] << 8 | data[off++];
            off &= 0xff0f;   // &0xff0f === %BCRYPT_SALT_LEN
            heap32[L_offset] ^= sw;

            sw = data[off++] << 24 | data[off++] << 16 | data[off++] << 8 | data[off++];
            off &= 0xff0f;
            heap32[R_offset] ^= sw;

            engine.encrypt(LR_offset);
            heap32[P_offset >> 2 | i] = heap32[L_offset];
            heap32[P_offset >> 2 | (i + 1)] = heap32[R_offset];
        }
        
        var s_offset = S_offset >> 2;
        for (i = 0; i < S_LEN; i += 2) {
            sw = data[off++] << 24 | data[off++] << 16 | data[off++] << 8 | data[off++];
            off &= 0xff0f;   // &0xff0f === %BCRYPT_SALT_LEN
            heap32[L_offset] ^= sw;

            sw = data[off++] << 24 | data[off++] << 16 | data[off++] << 8 | data[off++];
            off &= 0xff0f;
            heap32[R_offset] ^= sw;

            engine.encrypt(LR_offset);
            heap32[s_offset | i] = heap32[L_offset];
            heap32[s_offset | (i + 1)] = heap32[R_offset];
        }
    }

    function eksBlowfishSetup(engine, heap32, counterStart, counterEnd, limit, progress, callback) {
        var i = counterStart;
        while (i <= counterEnd) {
            i = engine.expandLoop(i, counterEnd, limit);

            if (progress) {
                var result = progress(i / (counterEnd+1));
                if (result === false) return;
            }

            if (i > counterEnd) {
                if (callback) {
                    setImmediate(encryptECB.bind(null, engine, heap32, callback));
                    return;
                }
                return;
            }
            else if (callback) {
                setImmediate(eksBlowfishSetup.bind(null, engine, heap32, i, counterEnd, limit, progress, callback));
                return;
            }
        }
    }

    function encryptECB(engine, heap32, callback) {
        heap32Set(heap32, bf_crypt_ciphertext, crypt_ciphertext_offset);

        var i;
        for (i = 0; i < 64; i++) {
            engine.encrypt(crypt_ciphertext_offset + 0);
            engine.encrypt(crypt_ciphertext_offset + 8);
            engine.encrypt(crypt_ciphertext_offset + 16);
        }

        var u,
            j = 0,
            clen = bf_crypt_ciphertext.length,
            ret = new Array(clen * 4);
        for (i = 0; i < clen; i++) {
            u = heap32[(crypt_ciphertext_offset>>2) + i];
            ret[j++] = u >> 24;
            ret[j++] = (u >> 16 & 0xff);
            ret[j++] = (u >> 8 & 0xff);
            ret[j++] = u & 0xff;
        }
        if (callback) {
            callback(ret);
        }
        return ret;

    }

    function format(prefix, hashed) {
        // This has to be bug-compatible with the original implementation, so only encode 23 of the 24 bytes.
        return prefix + encode_base64(hashed, 23);
    }

    /**
     * @param {string|Array|Uint8Array} password
     * @param {string} salt - must be a valid string
     * @param {function} [progress]
     * @param {function} [callback]
     */
    function hashpw(password, salt, progress, callback) {
        var prefix = salt.substr(0, 1 + 2 + 1 + 2 + 1 + 22),    // 29
            log_rounds = +salt.substr(4, 2),
            real_salt = salt.substr(7, 22);

        var passwordb;
        if (typeof password === 'string') {
            passwordb = (exports.encodingMode === exports.ENCODING_UTF8) ?
                        string2utf8Bytes(password) : string2rawBytes(password);
        }
        else if (Array.isArray(password)) {
            passwordb = password.map(function(byte) { return byte & 0xff; });
        }
        else if (password instanceof Uint8Array) {
            passwordb = Array.prototype.slice.call(password);
        }
        else {
            throw new Error('Incorrect arguments');
        }
        // Since $2a$ prefix.
        passwordb.push(0);
        
        var saltb = decode_base64(real_salt, BCRYPT_SALT_LEN);

        var rounds = (log_rounds < 31) ? 1 << log_rounds : 2147483648;
        var counterEnd = rounds -1,
            limit = progress ? 127 : counterEnd;

        var heap32, engine;
        if (useAsm) {
            var heap = new ArrayBuffer(8192);
            heap32 = new Uint32Array(heap);
            engine = bcryptAsm({ Uint32Array: Uint32Array }, null, heap);
        }
        else {
            engine = bcryptNoAsm(heap32 = []);
        }

        heap32Set(heap32, S, S_offset);
        heap32Set(heap32, P, P_offset);
        passwordb = cycle72(passwordb);
        saltb = cycle72(saltb);
        copyBigEndian(heap32, salt_offset >> 2, saltb);
        copyBigEndian(heap32, password_offset >> 2, passwordb);

        ekskey(saltb, passwordb, engine, heap32);

        if (callback) {
            eksBlowfishSetup(engine, heap32, 0, counterEnd, limit, progress, function(result) {
                callback(format(prefix, result));
            });
        }
        else {
            eksBlowfishSetup(engine, heap32, 0, counterEnd, limit, progress);
            return format(prefix, encryptECB(engine, heap32));
        }
    }

    /**
     * @param {integer} [cost=10] - The cost parameter. The number of iterations is 2^cost.
     */
    function genSalt(cost) {
        if (!randomBytes) {
            throw new Error('No cryptographically secure pseudorandom number generator available.');
        }
        if (cost == null) cost = GENSALT_DEFAULT_LOG2_ROUNDS;
        cost = +cost|0;
        if (isNaN(cost) || cost < 4 || cost > 31) {
            throw new Error('Invalid cost parameter.');
        }
        var output = '$2y$';
        if (cost < 10) output += '0';
        output += cost + '$';
        output += encode_base64(randomBytes(BCRYPT_SALT_LEN), BCRYPT_SALT_LEN);
        return output;
    }

    var SALT_PATTERN = /^\$2[ay]\$(0[4-9]|[12][0-9]|3[01])\$[.\/A-Za-z0-9]{30}$/;

    /**
     * @param {string|Array|Uint8Array} data - the data to be encrypted.
     * @param {string|integer} [salt] - the salt to use to hash the password. If specified as a number then a salt will be generated (see examples).
     * @param {function} [progress] - a callback to be called during the hash calculation to signify progress
     */
    function hashSync(data, salt, progress) {
        if (!salt || typeof salt === 'number') salt = genSalt(salt);
        else if (typeof salt !== 'string' || !SALT_PATTERN.test(salt)) throw new Error('Invalid salt');
        return hashpw(data, salt, progress);
    }

    /**
     * @param {string|Array|Uint8Array} data - the data to be encrypted.
     * @param {string|integer} [salt] - the salt to use to hash the password. If specified as a number then a salt will be generated (see examples).
     * @param {function} [progress] - a callback to be called during the hash calculation to signify progress
     * @param {function} callback - a callback to be fired once the data has been encrypted.
     */
    function hash(data, salt, progress, callback) {
        if (arguments.length < 2) {
            throw new Error('Incorrect arguments');
        }
        if (arguments.length === 2) {
            callback = salt;
            salt = progress = null;
        }
        else if (arguments.length === 3) {
            callback = progress;
            progress = null;
            if (typeof salt === 'function') {
                progress = salt;
                salt = null;
            }
        }
        if (!salt || typeof salt === 'number') salt = genSalt(salt);
        else if (typeof salt !== 'string' || !SALT_PATTERN.test(salt)) throw new Error('Invalid salt');
        if (!callback || typeof callback !== 'function') {
            throw new Error('No callback function was given.');
        }

        hashpw(data, salt, progress, callback);
    }


    var HASH_PATTERN = /^\$2[ay]\$(0[4-9]|[12][0-9]|3[01])\$[.\/A-Za-z0-9]{21}[.Oeu][.\/A-Za-z0-9]{30}[.CGKOSWaeimquy26]$/;

    /**
     * @param {string|Array|Uint8Array} password - password to check.
     * @param {string} refhash - reference hash to check the password against.
     * @returns {boolean}
     */
    function compareSync(password, refhash) {
        if (typeof refhash !== 'string' || !HASH_PATTERN.test(refhash)) {
            throw new Error('Incorrect arguments');
        }

        var salt = refhash.substr(0, refhash.length - 31),
            hashedpw = hashSync(password, salt);
        return hashedpw === refhash;
    }

    /**
     * @param {string|Array|Uint8Array} password - password to check.
     * @param {string} refhash - reference hash to check the password against.
     * @param {function} [progress] - a callback to be called during the hash verification to signify progress
     * @param {function} callback - a callback to be fired once the data has been compared, with a boolean indicating the result.
     */
    function compare(password, refhash, progress, callback) {
        if (typeof refhash !== 'string' || !HASH_PATTERN.test(refhash)) {
            throw new Error('Incorrect arguments');
        }
        if (!callback) {
            callback = progress;
            progress = null;
        }
        if (!callback || typeof callback !== 'function') {
            throw new Error('No callback function was given.');
        }
        var salt = refhash.substr(0, refhash.length - 31);
        hash(password, salt, progress, function(result) {
            callback(result === refhash);
        });
    }

    exports.genSalt = genSalt;
    exports.hashSync = hashSync;
    exports.hash = hash;
    exports.compareSync = compareSync;
    exports.compare = compare;

    // 8-bit encoding mode: utf8 or raw.
    exports.ENCODING_UTF8 = 0;
    exports.ENCODING_RAW = 1;
    exports.encodingMode = exports.ENCODING_UTF8;

    exports.cryptoRNG = !!randomBytes;
    exports.randomBytes = randomBytes;
    exports.defaultCost = GENSALT_DEFAULT_LOG2_ROUNDS;
    exports.version = "{{ version }}";
}));