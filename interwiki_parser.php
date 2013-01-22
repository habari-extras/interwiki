<?php
/*

  Interwiki Parser

  Revision: $Id$
  Head URL: $URL$
  
*/
class InterwikiParser
{
	var $lang_iso639 = array('aa', 'ab', 'af', 'ak', 'sq', 'am', 'ar', 'an', 'hy', 'as', 'av', 'ae', 'ay', 'az', 'ba', 'bm', 'eu', 'be', 'bn', 'bi', 'bs', 'br', 'bg', 'my', 'ca', 'ch', 'ce', 'zh', 'cu', 'cv', 'kw', 'co', 'cr', 'cs', 'da', 'dv', 'nl', 'dz', 'en', 'eo', 'et', 'ee', 'fo', 'fj', 'fi', 'fr', 'fy', 'ff', 'ka', 'de', 'gd', 'ga', 'gl', 'gv', 'el', 'gn', 'gu', 'ht', 'ha', 'he', 'hz', 'hi', 'ho', 'hu', 'ig', 'is', 'io', 'ii', 'iu', 'ie', 'ia', 'id', 'ik', 'it', 'jv', 'ja', 'kl', 'kn', 'ks', 'kr', 'kk', 'km', 'ki', 'rw', 'ky', 'kv', 'kg', 'ko', 'kj', 'ku', 'lo', 'la', 'lv', 'li', 'ln', 'lt', 'lb', 'lu', 'lg', 'mk', 'mh', 'ml', 'mi', 'mr', 'ms', 'mg', 'mt', 'mo', 'mn', 'na', 'nv', 'nr', 'nd', 'ng', 'ne', 'nn', 'nb', 'no', 'ny', 'oc', 'oj', 'or', 'om', 'os', 'pa', 'fa', 'pi', 'pl', 'pt', 'ps', 'qu', 'rm', 'ro', 'rn', 'ru', 'sg', 'sa', 'sr', 'hr', 'si', 'sk', 'sl', 'se', 'sm', 'sn', 'sd', 'so', 'st', 'es', 'sc', 'ss', 'su', 'sw', 'sv', 'ty', 'ta', 'tt', 'te', 'tg', 'tl', 'th', 'bo', 'ti', 'to', 'tn', 'ts', 'tk', 'tr', 'tw', 'ug', 'uk', 'ur', 'uz', 've', 'vi', 'vo', 'cy', 'wa', 'wo', 'xh', 'yi', 'yo', 'za', 'zh', 'zu');
	var $lang_default = 'ja';
	var $wiki_default = 'w';
	var $local_encoding = 'UTF-8';
	var $interwiki = array(
		'w' => 'http://{lang}.wikipedia.org/wiki/{word}|UTF-8',
		'b' => 'http://{lang}.wikibooks.org/wiki/{word}|UTF-8',
		'n' => 'http://{lang}.wikinews.org/wiki/{word}|UTF-8',
		'q' => 'http://{lang}.wikiquote.org/wiki/{word}|UTF-8',
		's' => 'http://{lang}.wikisource.org/wiki/{word}|UTF-8',
		't' => 'http://{lang}.wiktionary.org/wiki/{word}|UTF-8',
		'com' => 'http://commons.wikimedia.org/wiki/{word}|UTF-8',
		'meta' => 'http://meta.wikimedia.org/wiki/{word}|UTF-8',
		'google' => 'http://www.google.com/search?q={word}&ie=UTF-8&oe=UTF-8|UTF-8',
		'gmap' => 'http://maps.google.com/?q={word}|UTF-8',
		'alexa' => 'http://www.alexa.com/data/details/traffic_details?url={word}|UTF-8',
		'hatena' => 'http://d.hatena.ne.jp/keyword/{word}|EUC-JP',
		'medipedia' => 'http://medipedia.jp/index.php/{word}|UTF-8',
		'youtube' => 'http://www.youtube.com/results?search_query={word}|UTF-8',
		'amazon' => 'http://www.amazon.co.jp/s?ie=UTF8&field-keywords={word}|UTF-8',
	);
	var $interword = array(
		'P' => 'Wikipedia',
		'U' => 'User',
		'UT' => 'User_talk',
	);

	function parse($keyword)
	{
		$wiki = null;
		$words = array();
		$lang = $this->lang_default;
		if(preg_match('#^(.+)\s*\|\s*(.+)\s*$#', $keyword, $matches)) {
			$linkword = $matches[2];
			$keyword = $matches[1];
		}
		else {
			$linkword = '';
		}
		$w = explode(':', $keyword);
		for ($i = 0; $i < count($w); $i++) {
			// Site URL
			if( isset($this->interwiki[$w[$i]]) ) {
				$wiki = $w[$i];
				// Word
			} elseif( isset($this->interword[$w[$i]]) ) {
				$words[] = $this->interword[$w[$i]];
				// Language
			} elseif( in_array($w[$i], $this->lang_iso639) ) {
				$lang = $w[$i];
				// Language
			} else {
				$words[] = $w[$i];
			}
		}
		if( count($words) < 1 ) return false;
		if( empty($wiki) ) $wiki = $this->wiki_default;
		if( empty($lang) ) $lang = $this->lang_default;
		list($wiki_url, $wiki_encoding) = explode('|', $this->interwiki[$wiki]);
		$encoded_words = array();
		if( $wiki_encoding != $this->local_encoding ) { // need convert encoding
			for ($i = 0; $i < count($words); $i++) {
				$encoded_words[] = urlencode(mb_convert_encoding($words[$i], $wiki_encoding, $this->local_encoding));
			}
		} else {
			for ($i = 0; $i < count($words); $i++) {
				$encoded_words[] = urlencode($words[$i]);
			}
		}
		$url = $wiki_url;
		$url = str_replace('{word}', join(':', $encoded_words), $url);
		$url = str_replace('{lang}', $lang, $url);
		$url = str_replace('{rawword}', join(':', $words), $url);

		if($linkword == '') {
			$linkword = implode(':', $words);
		}

		return array('wiki' => $wiki, 'word' => $linkword, 'url' => $url, 'lang' => $lang);
	}

	function setInterwiki($interwiki)
	{
		if( !is_array($interwiki) ) return false;
		$this->interwiki = $interwiki;
		return true;
	}

	function getInterwiki()
	{
		return $this->interwiki;
	}

	function setInterword($interword)
	{
		if( !is_array($interword) ) return false;
		$this->interword = $interword;
		return true;
	}

	function getInterword()
	{
		return $this->interword;
	}

	function setDefaultLang($lang)
	{
		if( !in_array($lang, $this->lang_iso639) ) return false;
		$this->lang_default = $lang;
		return true;
	}

	function getDefaultWiki()
	{
		return $this->wiki_default;
	}

	function setDefaultWiki($wiki)
	{
		if( !isset($this->interwiki[$wiki]) ) return false;
		$this->wiki_default = $wiki;
		return true;
	}

	function getISO639()
	{
		return $this->lang_iso639;
	}

	function setEncoding($encoding)
	{
		$this->local_encoding = $encoding;
		return true;
	}
}

?>