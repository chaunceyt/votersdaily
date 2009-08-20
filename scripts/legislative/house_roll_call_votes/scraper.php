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
        $_events = $this->scrape();
        foreach($_events as $events) {
            //print_r($events);
            $this->add_events($events);
        }
        //print_r($events);
    }
    
    protected function scrape()
    {
        $events = array();
        $this->source_url = $this->url;
        $response = $this->urlopen($this->url);
        $this->source_text = $response;

        //$access_time = time();
        $this->access_time = time();

        preg_match_all('#<TABLE[^>]*>(.+?)<\/TABLE>#is',$response,$top_page);

            foreach($top_page[1] as $toppage_data1) {
                preg_match_all('#<TR>(.+?)<\/TR>#is',$toppage_data1, $pages);
                //print_r($pages[1]);

                $i=0;
                foreach($pages[1] as $data2) {
                    preg_match_all('#<TD[^>]*>(.+?)<\/TD>#is',$data2, $data2_pages);

                    //print_r($data2_pages[1]);

                    //datetime
                    preg_match('#<FONT[^>]*>(.+?)<\/FONT>#is',$data2_pages[1][1],$date_str);

                    //rollcall number url
                    preg_match('#<A HREF="(.*?)">#is',$data2_pages[1][0], $rollnumber_url_str);
                    
                    //rollcall number
                    preg_match('#<A[^>]*>(.*?)<\/A>#is',$data2_pages[1][0], $rollnumber_str);

                    //issue
                    preg_match('#<A HREF="(.*?)>#is', $data2_pages[1][2], $issue_url_str);
                    preg_match('#<A[^>]*>(.+?)<\/A>#is',$data2_pages[1][2], $issue_str);

                    //question
                    preg_match('#<FONT[^>]*>(.+)<\/FONT>#is', $data2_pages[1][3], $question_str);

                    //result
                    preg_match('#<FONT[^>]*>(.+)<\/FONT>#is', $data2_pages[1][4], $result_str);

                    //title
                    preg_match('#<FONT[^>]*>(.+)<\/FONT>#is', $data2_pages[1][5], $title_str);


                    $toppage_events[$i]['couchdb_id'] = (string) strftime('%Y-%m-%dT%H:%M:%SZ', strtotime($date_str[1] .' 2009')) . ' - Legislative - House of Representives - ' . $title_str[1];
                    $toppage_events[$i]['datetime'] = (string) strftime('%Y-%m-%dT%H:%M:%SZ', strtotime($date_str[1] .' 2009'));
                    $toppage_events[$i]['end_datetime'] = null;
                    $toppage_events[$i]['roll_call'] = (string) trim($rollnumber_str[1]);
                    $toppage_events[$i]['roll_call_url'] = (string) trim(str_replace('"','',$rollnumber_url_str[1]));
                    $toppage_events[$i]['issue'] = (string) trim($issue_str[1]);
                    $toppage_events[$i]['issue_url'] = (string) trim(str_replace('"','',$issue_url_str[1]));
                    $toppage_events[$i]['question'] = (string) trim($question_str[1]);
                    $toppage_events[$i]['result'] = (string) trim($result_str[1]);
                    $toppage_events[$i]['title'] = (string) trim($title_str[1]);
                    //NOTICE: this block of code is causing notices $event_arr index .. 
                    $description_str = 'Roll Call # '.$event_arr[0] . ' ' . $event_arr[3] . ' - ' . $event_arr[5] . ' ' . $event_arr[7] .' ('.$status.')';
                    $description_str .= ' Links:  http://clerk.house.gov/cgi-bin/vote.asp?year=2009&rollnumber='.$event_arr[0];
                    $bill_str = str_replace(' ','.',$event_arr[3]);
                    $description_str .= ' http://thomas.loc.gov/cgi-bin/bdquery/z?d111:'.strtolower($bill_str).':';

                    $toppage_events[$i]['description'] = (string) trim($description_str);
                    $toppage_events[$i]['branch'] = (string) BranchName::$legislative;
                    $toppage_events[$i]['entity'] = (string) EntityName::$house;
                    $toppage_events[$i]['source_url'] = (string) $this->url;
                    $toppage_events[$i]['source_text'] = $event;
                    $toppage_events[$i]['access_datetime'] = (string) $this->access_time;
                    $toppage_events[$i]['parser_name'] = (string) $this->parser_name;
                    $toppage_events[$i]['parser_version'] = (string) $this->parser_version;
                    $i++;
                }

                $_tmp_events[] = array_merge($toppage_events,$events);
            }

        preg_match_all('#<A HREF="ROLL(.*?)">#is',$response, $otherLinks);
            foreach($otherLinks[1] as $otherLink) {
                //$voteLinks[] = 'http://clerk.house.gov/evs/2009/ROLL'.$otherLink;
                $_voteLink = 'http://clerk.house.gov/evs/2009/ROLL'.$otherLink;
                $page_response .= file_get_contents($_voteLink);
                preg_match_all('#<TITLE>(.*?)<\/TITLE>#is',$page_response, $title);
             
                //print_r($title[1]);
                preg_match_all('#<TABLE[^>]*>(.+?)<\/TABLE>#is',$page_response,$matches);

                $t=0;
                foreach($matches[1] as $data1) {
                    preg_match_all('#<TR>(.+?)<\/TR>#is',$data1, $pages);
                    //print_r($pages[1]);

                    $i=0;
                    foreach($pages[1] as $data2) {
                        preg_match_all('#<TD[^>]*>(.+?)<\/TD>#is',$data2, $data2_pages);

                        //print_r($data2_pages[1]);

                        //datetime
                        preg_match('#<FONT[^>]*>(.+?)<\/FONT>#is',$data2_pages[1][1],$date_str);

                        //rollcall number
                        preg_match('#<A[^>]*>(.*?)<\/A>#is',$data2_pages[1][0], $rollnumber_str);

                        //rollcall number url
                        preg_match('#<A HREF="(.*?)">#is',$data2_pages[1][0], $rollnumber_url_str);

                        //issue
                        preg_match('#<A HREF=(.*?)>#is', $data2_pages[1][2], $issue_url_str);
                        preg_match('#<A[^>]*>(.+?)<\/A>#is',$data2_pages[1][2], $issue_str);

                        //question
                        preg_match('#<FONT[^>]*>(.+)<\/FONT>#is', $data2_pages[1][3], $question_str);

                        //result
                        preg_match('#<FONT[^>]*>(.+)<\/FONT>#is', $data2_pages[1][4], $result_str);

                        //title
                        preg_match('#<FONT[^>]*>(.+)<\/FONT>#is', $data2_pages[1][5], $title_str);

                        $other_events[$i]['couchdb_id'] = (string) strftime('%Y-%m-%dT%H:%M:%SZ', strtotime($date_str[1] .' 2009')) . ' - Legislative - House of Representives - ' . $title_str[1];
                        $other_events[$i]['datetime'] = (string) strftime('%Y-%m-%dT%H:%M:%SZ', strtotime($date_str[1] .' 2009'));
                        $other_events[$i]['end_datetime'] = null;
                        $other_events[$i]['roll_call'] = (string) trim($rollnumber_str[1]);
                        $other_events[$i]['roll_call_url'] = (string) trim(str_replace('"','',$rollnumber_url_str[1]));
                        $other_events[$i]['issue'] = (string) $issue_str[1];
                        $other_events[$i]['issue_url'] = (string) trim(str_replace('"','',$issue_url_str[1]));
                        $other_events[$i]['question'] = (string) $question_str[1];
                        $other_events[$i]['result'] = (string) $result_str[1];
                        $other_events[$i]['title'] = (string) $title_str[1];
                        $i++;
                
                        //NOTICE: this block of code is causing notices $event_arr index .. 
                        $description_str = 'Roll Call # '.$event_arr[0] . ' ' . $event_arr[3] . ' - ' . $event_arr[5] . ' ' . $event_arr[7] .' ('.$status.')';
                        $description_str .= ' Links:  http://clerk.house.gov/cgi-bin/vote.asp?year=2009&rollnumber='.$event_arr[0];
                        $bill_str = str_replace(' ','.',$event_arr[3]);
                        $description_str .= ' http://thomas.loc.gov/cgi-bin/bdquery/z?d111:'.strtolower($bill_str).':';

                        $other_events[$i]['description'] = (string) trim($description_str);
                        $other_events[$i]['branch'] = (string) BranchName::$legislative;
                        $other_events[$i]['entity'] = (string) EntityName::$house;
                        $other_events[$i]['source_url'] = (string) $this->url;
                        $other_events[$i]['source_text'] = $event;
                        $other_events[$i]['access_datetime'] = (string) $this->access_time;
                        $other_events[$i]['parser_name'] = (string) $this->parser_name;
                        $other_events[$i]['parser_version'] = (string) $this->parser_version;
                
                    }

                    $_tmp_events[] = array_merge($other_events, $events);;
                }
        }



            
                //creating new field (bool)
                /*
                if($event_arr[6] == 'F') {
                    $status = 'Failed';
                    $events[$i]['vote_status'] = false;
                }
                else if($event_arr[6] == 'P') {
                    $status = 'Passed';
                    $events[$i]['vote_status'] = true;
                }*/
                
        return $_tmp_events;
    }
}


$parser = new HouseRollCallVotes;
$parser->run();
