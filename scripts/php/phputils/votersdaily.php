<?php
abstract class VotersDaily_Abstract
{
    protected $parser_version;
    protected $parser_name;
    protected $storageEngine = 'couchdb';
    protected $couchdbName = 'phpvotedailydb';

    public function __construct()
    {
        if(!property_exists($this, 'parser_name')) {
            throw new Exception('EventScrapers must have a name property');
        }

        if(!property_exists($this, 'parser_version')) {
            throw new Exception('EventScrapers must have a version property');
        }

        if(!property_exists($this, 'parser_frequency')) {
            throw new Exception('EventScrapers must have a frequency property');
        }
    }
    
    protected function urlopen($url)
    {
        $userAgent = "robot: http://wiki.github.com/bouvard/votersdaily";
        $opts = array('http'=> array('method'=>"GET",'header'=>$userAgent));
        $context = stream_context_create($opts);
        $response = file_get_contents($this->url,false,$context);
        return $response;
    }

    abstract public function run();
    abstract protected function scrape();
    abstract protected function add_events($arr, $fn);
    
}


class StorageEngine {
    protected static $fields = array('start_time','end_time','title','description','branch','entity','source_url','source_text','access_datetime','parser_name','person_version');

    public static function couchDbStore($arr, $dbname)
    {
        $options['host'] = "localhost";
        $options['port'] = 5984;

        $couchDB = new CouchDbSimple($options);
        //$resp = $couchDB->send("DELETE", "/".$dbname."/");

        $resp = $couchDB->send("PUT", "/".$dbname);
        //var_dump($resp);
        foreach($arr as $data) {
            $_data = json_encode($data);
            $id = md5(uniqid(mt_rand(), true));;
            $resp = $couchDB->send("PUT", "/".$dbname."/".$id, $_data);
            //var_dump($resp);

        }        
    }
    
    public static function csvStore($arr, $fn)
    {
        $lines = array();

        foreach($arr as $v) {
            $lines[] = "\"" . implode ('","', $v). "\"\n";
        }
        $fp = fopen($fn, 'w');
        if(!$fp) {
            echo 'Unable to open $fn for output';
            exit();
        }
        fwrite($fp, implode(',', self::$fields)."\n");

        foreach($lines as $line) {
            fwrite($fp, $line);
        }        
    }
}
