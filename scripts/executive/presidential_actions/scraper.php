#!/usr/bin/php -q
<?php
$PATH_TO_INCLUDES = dirname(dirname(dirname(__FILE__)));
require $PATH_TO_INCLUDES.'/phputils/EventScraper.php';
require $PATH_TO_INCLUDES.'/phputils/couchdb.php';

/*
 * Voters Daily: PHP - Presidential Actions Scraper
 * http://wiki.github.com/bouvard/votersdaily
 *
 * @author      Chauncey Thorn <chaunceyt@gmail.com>
 * Link: http://www.cthorn.com/
 *
 */


class PresidentialActions extends EventScraper_Abstract
{
    protected $url = 'http://www.whitehouse.gov/briefing_room/PresidentialActions/';
    public $parser_name = 'Presidential Action Scraper';
    public $parser_version = '1.0';
    public $parser_frequency = '6.0';

    public function __construct()
    {
        parent::__construct();
    }

    public function run()
    {
        $events = $this->scrape();
        $this->add_events($events);
    }

    protected function scrape()
    {
        $scrape_start = microtime_float();

        $events = array();
        $this->source_url = $this->url;
        $response = $this->urlopen($this->url);
        $this->access_time = time();
        $this->source_text = $response;

        preg_match_all('#<div class="timeStamp smaller">(.+?)<\/div>#is',$response,$_timestamps);
        preg_match_all('#<h4 class="modhdgblue">(.+?)<\/h4>#is',$response,$_events);
        $data_arr[] = array('timestamp' => $_timestamps[1], 'description' => $_events[1]);
        
        $total_timestamps = sizeof($data_arr[0]['timestamp']);
        for($i=0; $i < $total_timestamps; $i++) {
            preg_match('#<a[^>]*>(.*?)</a>#is', $data_arr[0]['description'][$i], $title);

            $events[$i]['couchdb_id'] = (string) $this->_vd_date_format($data_arr[0]['timestamp'][$i]) . ' - ' .$this->parser_name. ' - '.$this->_escape_str($title[1], 'title');
            $events[$i]['datetime'] = (string) $this->_vd_date_format($data_arr[0]['timestamp'][$i]);
            $events[$i]['end_datetime'] = null;
            $events[$i]['title'] = (string) $this->_escape_str($title[1]);
            $events[$i]['description'] = (string) $this->_escape_str($data_arr[0]['description'][$i]);
            $events[$i]['branch'] = (string) BranchName::$executive;
            $events[$i]['entity'] = (string) EntityName::$whitehouse;
            $events[$i]['source_url'] = (string) $this->url;
            $events[$i]['source_text'] = (string) $_events[0];

            $_access_time = date('D, d M Y H:i:s T', $this->access_time);
            $events[$i]['access_datetime'] = (string) $this->_vd_date_format($_access_time);
            $events[$i]['parser_name'] = (string) $this->parser_name;
            $events[$i]['parser_version'] = (string) $this->parser_version;
        }
    
        $scrape_end = microtime_float();
        $this->parser_runtime = round(($scrape_end - $scrape_start), 4);

        return $events;
    }
    
}

$parser = new PresidentialActions;
$parser->run();
exit(0);
