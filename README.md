Massively Multiplayer Music Player
=================

Accepts worker clients to play five second snippets - in order - from a given music file (you'll need to include your own). In a packed room, the intended affect is for the song to play through to the end, but to jump from computer to computer. A future rev could, instead, split up the parts in an orchestral piece and have each machine play a part, creating a computer symphony.

Requires redis and vlc.

Built for the RESTFest 2013 hackday and based on the [worker spec](https://github.com/RESTFest/2013-greenville/wiki/Work%20order) defined by Erik Mogensen.

All of the code is stored in the index.php (yes, I know, it's a HACK).