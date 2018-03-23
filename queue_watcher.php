#!/usr/bin/php
<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

class QueueWatcher {

	protected $argv;
	protected $user;
	protected $password;
	protected $statsd;

	public function __construct($argv)
    {
        $this->argv = $argv;
        if (isset($this->argv[1])) {
            $this->user = $this->argv[1];
        }
        if (isset($this->argv[2])) {
            $this->password = $this->argv[2];
        }
    	// $connection = new UdpSocket(env('STATSD_HOST', 'localhost'), env('STATSD_PORT', 8125));
        // self::$client = new Client($connection, env('STATSD_NAMESPACE', ''));
        $connection = new \Domnikl\Statsd\Connection\UdpSocket('localhost', 8125);
        $this->statsd = new \Domnikl\Statsd\Client($connection, 'rabbit'); 
    }

    public function fire(){

        $start = microtime(true);
    	$rows = $this->parse();
    	$this->evaluate($rows);
        $diff = microtime(true) - $start;
        echo 'Total: '. $diff . PHP_EOL;
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

	    			$this->statsd->count($name.'.messages.total', $total);
	    			$this->statsd->count($name.'.messages.ready', $ready);
	    			$this->statsd->count($name.'.messages.ready.unacked', $unacked);
	    			$this->statsd->count($name.'.consumers', $consumers);
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
