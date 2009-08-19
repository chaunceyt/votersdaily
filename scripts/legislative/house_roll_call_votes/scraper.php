#!/usr/bin/php -q
<?php
$PATH_TO_INCLUDES = dirname(dirname(dirname(__FILE__)));
require $PATH_TO_INCLUDES.'/phputils/EventScraper.php';
require $PATH_TO_INCLUDES.'/phputils/couchdb.php';

/*
 * Voters Daily: PHP - House Roll Call Votes Scraper
 * http://wiki.github.com/bouvard/votersdaily
 *
 * @author      Chauncey Thorn <chaunceyt@gmail.com>
 * Link: http://www.cthorn.com/
 *
 */

class HouseRollCallVotes extends EventScraper_Abstract
{
    
    protected $url = 'http://clerk.house.gov/evs/2009/index.asp';
    public $parser_name = 'House Roll Call Votes Scraper';
    public $parser_version = '0.1';
    public $parser_frequency = '6.0';

    public function __construct()
    {
        parent::__construct();
        $this->year = date("Y");
    }

    public function run()
    {
        $events = $this->scrape();
        //print_r($events);
        $this->add_events($events);
    }
    
    protected function scrape()
    {
        $events = array();
        $this->source_url = $this->url;
        $response = $this->urlopen($this->url);
        $this->source_text = $response;

        //$access_time = time();
        $this->access_time = time();
        preg_match_all('#<A HREF="ROLL(.*?)">#is',$response, $otherLinks);

        $voteLinks[] = $this->url;
        foreach($otherLinks[1] as $otherLink) {
             $voteLinks[] = 'http://clerk.house.gov/evs/2009/ROLL'.$otherLink;
        }
        asort($voteLinks);
        foreach($voteLinks as $voteLink) {
            $page_response = file_get_contents($voteLink);
            //get title
            preg_match('#<TITLE>(.*?)<\/TITLE>#is',$page_response, $title);

            preg_match('#<TABLE[^>]*>(.+?)<\/TABLE>#is',$page_response,$matches);
            preg_match_all('#<TR>(.+?)<\/TR>#is',$matches[1],$data);

            $i=0;
            foreach($data[1] as $event) {
                $event = str_replace(array("\r\n",'  :  ',' :  '),':',strip_tags(trim($event)));
                $event_arr = explode(':', $event);

                if(ctype_digit($event_arr[0])) {

                list($day, $month) = explode('-', $event_arr[1]);
                //format date
                $date_str = $month . ' '. $day.' '.$this->year ;
                $events[$i]['couchdb_id'] = (string) $this->_vd_date_format($date_str) . ' - Legislative - House of Representatives - ' .  trim($title[1]) .' - Roll Call # '.$event_arr[0];
                $events[$i]['datetime'] = (string) $this->_vd_date_format($date_str);
                $events[$i]['end_datetime'] = null;
                $events[$i]['title'] = (string) trim($title[1]);
            
                if($event_arr[6] == 'F') {
                    $status = 'Failed';
                }
                else if($event_arr[6] == 'P') {
                    $status = 'Passed';
                }
                
                //NOTICE: this block of code is causing notices $event_arr index .. 
                $description_str = 'Roll Call # '.$event_arr[0] . ' ' . $event_arr[3] . ' - ' . $event_arr[5] . ' ' . $event_arr[7] .' ('.$status.')';
                $description_str .= ' Links:  http://clerk.house.gov/cgi-bin/vote.asp?year=2009&rollnumber='.$event_arr[0];
                $bill_str = str_replace(' ','.',$event_arr[3]);
                $description_str .= ' http://thomas.loc.gov/cgi-bin/bdquery/z?d111:'.strtolower($bill_str).':';

                $events[$i]['description'] = (string) trim($description_str);
                $events[$i]['branch'] = 'Legislative';
                $events[$i]['entity'] = 'House of Representatives';
                $events[$i]['source_url'] = $this->url;
                $events[$i]['source_text'] = $event;
                $events[$i]['access_datetime'] = $this->access_time;
                $events[$i]['parser_name'] = $this->parser_name;
                $events[$i]['parser_version'] = $this->parser_version;

                }
                $i++;
            }
        }
        return $events;
    }
}


$parser = new HouseRollCallVotes;

//setup loggin array
$scrape_log['parser_name'] = $parser->parser_name;
$scrape_log['parser_version'] = $parser->parser_version;


$scrape_start = microtime_float();
$parser->run();
$scrape_end = microtime_float();

//value available only after scrape
$scrape_log['url'] = $parser->source_url;
$scrape_log['source_text'] = $parser->source_text;
$scrape_log['access_datetime'] = $parser->access_time;

//deal with logging here

//echo "Parse completed in ".bcsub($scrape_end, $scrape_start, 4)." seconds."."\n\n"; 
