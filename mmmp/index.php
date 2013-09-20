<?php 
	// prepend a base path if Predis is not present in your "include_path".
	require 'Predis/Autoloader.php';

	Predis\Autoloader::register();
	
	$redis = new Predis\Client();

	//Extract the resources from this. Shitty quick router.
	$basePath = preg_replace('/index\.php/', '', $_SERVER["PHP_SELF"]);
	$regex = '/'.preg_quote($basePath, '/').'(\/?index\.php)?/';
	$resourcePath = preg_replace($regex, '', $_SERVER["REQUEST_URI"]);
	$resources = split('/', $resourcePath);
	
	$base = $resources[0];
	
	$payload = array();

	define('MMMP_PLAYING', 'playing');
	define('MMMP_WAITING', 'waiting');
	
	$segment = 0; //Which segment in the song is currently playing
	$duration = 5; //Period of time for which to play the item
	$songLength = (3 * 60) + 30;
	$totalSegs = $songLength / $duration; //The song is 3:30 and I'm very lazy. Soooo lazy.
	
	$mediaUrl = 'http://10.0.12.127/mmmp/media/';
	$baseUrl = 'http://10.0.12.127/mmmp';
	//?command=seek&val=60

	switch($resources[0]) {
		case "workers":
			//Return the current collection of available work to be done
			doWorkers($_REQUEST);
			break;
		case "start":
			//Mark this as started
			doStart($_REQUEST);
			break;
		case "status":
			//Return the current state of the work
			doStatus($_REQUEST);
			break;
		case "fail":
			//Reset to the last point to be played
			doFail($_REQUEST);
			break;
		case "complete":
			doComplete($_REQUEST);
			break;
		case "play":
			//Get's the requested segment to play; accepts the segment number
			doPlay($_REQUEST);
			break;
		case "media":
			//Handles the playing of the actual media by proxy
			mediaPlayer($_REQUEST);
		default:
			//Return the list of available resources
			doDefault($_REQUEST);
			break;
	}
	
	
		//audio = new Audio('http://127.0.0.1/assets/reach-for-the-sky.mp3');
	//audio = new Audio('http://127.0.0.1:8080?command=seek&val=60');
	//audio.play();
	function doWorkers() {
		global $mediaUrl, $baseUrl, $redis;
		//handle the workers request here
		//Test lazy
		
		$payload = array();
		$payload["collection"] = array();
		$payload["collection"]["items"] = array();
		$payload["collection"]["items"][0] = array();
		$payload["collection"]["links"] = array();
		$payload["collection"]["links"][0] = array();
		 
		 
		if($redis->get('state') == MMMP_WAITING) {
			$segment = $redis->get('segment');
			if($segment == '') $segment = 0;
			$payload["collection"]["items"][0]["href"] = $baseUrl.'/play/?seg='.$segment;
			$redis->set('state', MMMP_PLAYING);
		}
		
		$payload["collection"]["links"][0]["rel"] = "next";
		$payload["collection"]["links"][0]["href"] = $baseUrl.'/workers';
		
		send($payload);
		
	}
	
	function doPlay() {
		global $baseUrl, $mediaUrl, $duration, $totalSegs;
		/*
		{
		  "type": "http://mogsie.com/static/work/compile-some-code/nuget",
		  "input":
		  {
			"source" : "http://github.com/tavis-software/Link",
			"test": "Tavis.Link.nuspec"
		  }

		  "start": "/work-orders/su9fw/take",
		  "status": "/work-orders/su9fw/status",
		  "complete": "/work-orders/su9fw/completed",
		  "fail": "/work-orders/su9fw/failed"
		}
		*/
		if(!isset($_REQUEST["seg"]) || !is_numeric($_REQUEST["seg"])) {
			//Return an error here
		}
		
		$seek = ($_REQUEST["seg"] * $duration) % $totalSegs;
		
		$payload = array();
		$payload["type"] = 'http://www.robzazueta.com/workitems/play';
		$payload["input"] = array();
		$payload["input"]["href"] = $mediaUrl."?seg=".$seek;
		$payload["input"]["duration"] = $duration;
		
		$payload["start"] = $baseUrl.'/start/';
		$payload["complete"] = $baseUrl.'/complete/';
		
		send($payload);
	}
	
	function doComplete() {
		global $redis;
		$segment = $redis->get('segment');
		$redis->set('segment', $segment + 1);
		$redis->set('state', MMMP_WAITING);
	}
	
	function mediaPlayer($request) {
		global $totalSegs, $duration;
		//$command = "/Applications/VLC.app/Contents/MacOS/VLC -I rc ../assets/reach-for-the-sky.mp3";
		//$command = $command. '--quiet --start-time 30 --stop-time 35 :sout=#standard{mux=raw,dst=,access=http}';

		$command = '/Applications/VLC.app/Contents/MacOS/VLC -I dummy --quiet --start-time '.$_REQUEST["seg"].' ../assets/reach-for-the-sky.mp3 --sout "#standard{mux=raw,dst=-,access=file}" vlc://quit';
		header('Content-type: audio/mpeg');
		passthru($command);
		//echo (file_get_contents('http://127.0.0.1:8080'));
		exit();
	}
	
	function send($payload) {
		header('Content-type: text/json');
		echo(json_encode($payload));
	}
?>