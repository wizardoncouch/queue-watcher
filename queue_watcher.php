#!/usr/bin/php
<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Plustelecom\Statsd\Statsd;


class QueueWatcher {

	protected $argv;
	protected $user;
	protected $password;

	public function __construct($argv)
    {
        $this->argv = $argv;
        if (isset($this->argv[1])) {
            $this->user = $this->argv[1];
        }
        if (isset($this->argv[2])) {
            $this->password = $this->argv[2];
        }
    }

    public function fire(){
    	$rows = $this->parse();
    	$this->evaluate($rows);
    }

    protected function parse(){

    	try{
    		$client = new Client();
    		$response = $client->get('http://localhost:15672/api/queues', ['auth' => [$this->user, $this->password]]);

    		$body = $response->getBody();
            $body = (string)$body;
    		$json = json_decode($body, true);

    		return $json;

    	}catch(\Exception $e){

    		die($e->getMessage());
    	}

    }

    protected function evaluate($rows){
    	try{
	    	foreach($rows as $row){
	    		$total = $row['messages'];
	    		$ready = $row['messages_ready'];
	    		$unacked = $row['messages_unacknowledged'];
	    		$consumers = $row['consumers'];
	    		$name = $row['name'];
	    		if($total > 0){

	    			echo 'Row reported: ' . json_encode($row) . PHP_EOL;

	    			Statsd::gauge('rabbit.'.$name.'.messages.total', $total);
	    			Statsd::gauge('rabbit.'.$name.'.messages.ready', $ready);
	    			Statsd::gauge('rabbit.'.$name.'.messages.ready.unacked', $unacked);
	    			Statsd::gauge('rabbit.'.$name.'.consumers', $consumers);
	    		}
	    	}
    	}catch(\Exception $e){

    		die($e->getMessage());
    	}

    }

    protected function file(){
    	try{
    		$file = file_get_contents('test.json');
    		$json = json_decode($file, true);

    		return $json;

    	}catch(\Exception $e){

    		die($e->getMessage());
    	}

    }

}

$watcher = new QueueWatcher($argv);
$watcher->fire();
