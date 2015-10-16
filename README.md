# rtsp-restream
Re-stream camera footage to the masses. Re-stream footage through red5 or Adobe flash media server from your camera to bypass connection limits and stress on your web camera

Enjoy!
Questions? Contact us via http://webmad.co.nz

Requires:
linux OS, ffprobe, ffmpeg, and a red5 / Adobe flash media server for your flash stream

Installation: 
 - edit monitor.php, and modify the camera IP address, port, and url to the camera stream.
 - Modify any of the image settings - ie overlay image and fallback image locations. Note - if you don't want an overlay image, use a fully transparent png, or edit the code to remove this functionality yourself.
 - create a new cron job to run `php monitor.php` every minute or so - this acts as both starting and monitoring to make sure its still running.