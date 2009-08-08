<?php
/*

  Interwiki for Habari

  Revision: $Id$
  Head URL: $URL$

*/

// include Interwiki Parser
require_once('interwiki_parser.php');

class Interwiki extends Plugin
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
        $this->parser->setEncoding( 'UTF-8' );
    }

    public function action_plugin_activation( $file )
    {
        if ( $file != $this->get_file() ) return;

        // Interwiki Parser
        $this->parser = new InterwikiParser();
        $this->parser->setEncoding( 'UTF-8' );

        Options::set( 'interwiki:interwiki', serialize( $this->parser->getInterwiki() ) );
        Options::set( 'interwiki:lang_default', 'en' );
        Options::set( 'interwiki:wiki_default', $this->parser->getDefaultWiki() );
    }

    public function action_plugin_ui($plugin_id, $action)
    {
        if ( $plugin_id != $this->plugin_id() ) return;
        if ( $action == _t( 'Configure' ) ) {
            $interwiki = unserialize(Options::get('interwiki:interwiki'));

            $ui = new FormUI( strtolower( get_class( $this ) ) );
            $wiki_default = $ui->add( 'select', 'wiki_default', _t('Default Wiki'), array_keys( $interwiki ), Options::get('interwiki:wiki_default') );
            $lang_default = $ui->add( 'select', 'lang_default', _t('Default Language'), $this->parser->getISO639(), Options::get('interwiki:lang_default') );
            $ui->on_success( array( $this, 'updated_config' ) );
            $ui->out();
        }
    }

    /**
     * FormUI callback
     *
     * @access public
     * @return boolean
     */
    public function updated_config($ui)
    {
        return true;
    }

    /**
     * action: update_check
     *
     * @access public
     * @return void
     */
    public function action_update_check()
    {
        Update::add( 'Interwiki', 'df59c0ab-1e6d-11dd-b5d6-001b210f913f', $this->info->version );
    }

    /**
     * filter: post_content_out
     *
     * @access public
     * @return string
     */
    public function filter_post_content_out($content)
    {
        preg_match_all( "/\[\[(.*?)\]\]/", $content, $match );
        for ( $i = 0; $i < count( $match[1] ); $i++ ) {
            if ( ( $result = $this->parser->parse( $match[1][$i] ) ) === false ) continue;
            $content = str_replace( $match[0][$i], "<a href=\"{$result['url']}\" onlick=\"window.open('{$result['url']}'); return false;\" target=\"_blank\">{$result['word']}</a>", $content );
        }
        return $content;
    }

    /**
     * filter: plugin_config
     *
     * @access public
     * @return array
     */
    public function filter_plugin_config($actions, $plugin_id)
    {
        if ( $plugin_id == $this->plugin_id() ) {
            $actions[] = _t('Configure');
        }
        return $actions;
    }
}
?>