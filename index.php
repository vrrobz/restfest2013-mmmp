<html>
<head>
<title>Distributed Playback</title>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
</head>
<body>
<h1>Massively Multiplayer Jukebox</h1>

<pre id="log"></pre>

<script>
var queue=[];
//var speed = 1000; // 1000 for real time
var speed = 3000;
function log(text) {
  $("#log").append(".\n" + text);
}

// The next URL to retrieve
var next = undefined;

// TODO: get this from the UI :-)
$(document).ready(function() {
  next = '/mmmp/workers';
  tick();
});


// Follow the "next" document, add them to the queue, and "tock()"
function tick() {
  if (next === undefined) {
    log("Done.");
    return;
  }
  log("Following next " + next);
  $.getJSON(next, function(data) {
    var currentPage = next;
    console.log("got data", data);
    if (data.collection && data.collection.items) {
      if (data.collection.items.length == 0) {
        log("Empty collection, retrying again later");
        setTimeout(tick, 10 * speed);
        return;
      }
      next = undefined;
      log("Found " + data.collection.items.length + " items");
      // assume it's a collection json; since this is a
      // demo it uses introspection instead of media types
      queue = queue.concat(data.collection.items);

      if (typeof data.collection.links == 'array') {
        $.each(data.collection.links, function(key, val) {
          if (val.rel == "next") next = val.href;
          console.log("next", next);
        });
      }
      else {
        console.log("no next link, reloading in a bit.");
        setTimeout(function () {
          next = currentPage;
          tick();
        }, 10 * speed);
      }
    }
    tock();
  });
}

// figure out what to do...
function tock() {
  log("Tock");
  if (queue.length == 0) {
  	log("Empty queue, returning");
    tick();
    return;
  }
  var task = queue.shift();
  log("Following " + task.href);
  $.getJSON(task.href, function(data) {
  	log("HREF: " + data.input.href);
  	log("DURATION: " + data.input.duration);
  	log("START: " + typeof data.start);
    if (data.type != "http://www.robzazueta.com/workitems/play") {
      log("Bad data type");
      tock();
      return;
    }
    // take job if it says so
    if (typeof data.start == 'string') {
      console.log("I should really start work before doing this..");
    }
    if (data.input.href) {
      	log("Playing " + data.input.href);
		audio = new Audio(data.input.href);
		//audio.oncanplay = function() {
			log("Playing");
			audio.play();
			setTimeout(function() {
				log("Stopping...");
				audio.pause();
				callComplete(data.complete);
				t//ock();
			}, data.input.duration * 1000);
		//};
    } else {
    	log("No href");
    }
  });
  return;
}

function callComplete(href) {
	$.getJSON(href, function(data) {
		log("Called complete");
	});
}
</script>
</body>
</html>