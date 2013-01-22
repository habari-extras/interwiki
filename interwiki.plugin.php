<?php
/*

  Interwiki for Habari

  Revision: $Id$
  Head URL: $URL$

*/

// include Interwiki Parser
require_once('interwiki_parser.php');

class Interwiki extends Plugin implements FormStorage
{
	private $parser;

	/**
	 * action: init
	 *
	 * @access public
	 * @retrun void
	 */
	public function action_init()
	{
		// Interwiki Parser
		$this->parser = new InterwikiParser();
		$this->parser->setEncoding('UTF-8');
		$this->parser->setDefaultLang(Options::get('interwiki__lang_default', 'en'));
		$this->parser->setDefaultWiki(Options::get('interwiki__wiki_default', $this->parser->getDefaultWiki()));
		$this->parser->setInterwiki(Options::get('interwiki__wikis', $this->parser->getInterwiki()));
	}

	public function action_plugin_activation($file)
	{
		if( $file != $this->get_file() ) return;

		// Interwiki Parser
		$this->parser = new InterwikiParser();
		$this->parser->setEncoding('UTF-8');

		Options::set('interwiki__wikis', $this->parser->getInterwiki());
		Options::set('interwiki__lang_default', 'en');
		Options::set('interwiki__wiki_default', $this->parser->getDefaultWiki());
	}

	public function configure()
	{
		$interwiki = Options::get('interwiki__interwiki', $this->parser->getInterwiki());
		$interwiki = array_combine(array_keys($interwiki), array_keys($interwiki));
		$langs = $this->parser->getISO639();
		$langs = array_combine($langs, $langs);

		$ui = new FormUI(strtolower(get_class($this)));
		$wiki_default = $ui->append(new FormControlSelect('wiki_default', 'interwiki__wiki_default', _t('Default Wiki'), $interwiki));
		$lang_default = $ui->append(new FormControlSelect('lang_default', 'interwiki__lang_default', _t('Default Language'), $langs));
		$wikis = $ui->append(new FormControlTextArea('interwiki__wikis', $this, _t('Wikis')));
		$ui->append(new FormControlSubmit('submit', 'Submit'));
		return $ui;
	}

	/**
	 * filter: post_content_out
	 *
	 * @access public
	 * @return string
	 */
	public function filter_post_content_out($content)
	{
		preg_match_all("/\[\[(.*?)\]\]/", $content, $match);
		for ($i = 0; $i < count($match[1]); $i++) {
			if( ($result = $this->parser->parse($match[1][$i])) === false ) continue;
			$content = str_replace($match[0][$i], "<a href=\"{$result['url']}\" onlick=\"window.open('{$result['url']}'); return false;\" target=\"_blank\">{$result['word']}</a>", $content);
		}
		return $content;
	}

	/**
	 * Stores a form value into the object
	 *
	 * @param string $key The name of a form component that will be stored
	 * @param mixed $value The value of the form component to store
	 */
	function field_save($key, $value)
	{
		switch($key) {
			case 'interwiki__wikis':
				$lines = explode("\n", $value);
				$wikis = array();
				foreach($lines as $line) {
					if(preg_match('#^\s*(\w+?)\s*\|\s*(.+?)\s*$#', $line, $matches)) {
						$wikis[$matches[1]] = $matches[2];
					}
				}
				Options::set('interwiki__wikis', $wikis);
				break;
		}
	}

	/**
	 * Loads form values from an object
	 *
	 * @param string $key The name of a form component that will be loaded
	 * @return mixed The stored value returned
	 */
	function field_load($key)
	{
		switch($key) {
			case 'interwiki__wikis':
				$out = '';
				$wikis = Options::get('interwiki__wikis', $this->parser->getInterwiki());
				foreach($wikis as $wikiname => $wiki) {
					$out .= "{$wikiname} | {$wiki}\n";
				}
				return $out;
				break;
		}
	}
}

?>