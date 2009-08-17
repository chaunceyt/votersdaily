<?php
//require '../phputils/EventScraper.php';

class PresidentialPressBriefings extends EventScraper_Abstract
{
    protected $url = 'http://www.whitehouse.gov/briefing_room/PressBriefings/';
    public $parser_name = 'Presidential Press Briefings Scraper';
    public $parser_version = '0.1';
    public $parser_frequency = '6.0';
    protected $csv_filename = 'data/presidentialpressbriefings.csv';

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
        $events = array();

        $response = $this->urlopen($this->url);
        $this->source_url = $this->url;
        $this->access_time = time();

        preg_match_all('#<div class="timeStamp smaller">(.+?)<\/div>#is',$response,$_timestamps);
        preg_match_all('#<h4 class="modhdgblue">(.+?)<\/h4>#is',$response,$_events);
        $data_arr[] = array('timestamp' => $_timestamps[1], 'description' => $_events[1]);
        
        $total_timestamps = sizeof($data_arr[0]['timestamp']);
        for($i=0; $i < $total_timestamps; $i++) {
            preg_match('#<a[^>]*>(.*?)</a>#is', $data_arr[0]['description'][$i], $title);
            $events[$i]['start_date'] = date('Y-m-d', strtotime($data_arr[0]['timestamp'][$i]));
            $events[$i]['end_date'] = '';
            $events[$i]['title'] = (string) $title[1];
            $events[$i]['description'] = $data_arr[0]['description'][$i];
            $events[$i]['branch'] = 'Executive';
            $events[$i]['entity'] = 'President';
            $events[$i]['source_url'] = $this->url;
            $events[$i]['source_text'] = '';
            $events[$i]['access_datetime'] = $access_time;
            $events[$i]['parser_name'] = $this->parser_name;
            $events[$i]['parser_version'] = $this->parser_version;
        }
        return $events;
    }
}
