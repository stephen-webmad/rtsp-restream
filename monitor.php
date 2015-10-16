<?

/*

Description: rtsp restreaming script with image overlay capability. Re-streams a webcam through red5 for flash, and makes a frame copy onto disk for browsers that do not support flash.
Author: Stephen Price / Webmad
Version: 1.0.0
Author URI: http://www.webmad.co.nz
Notes: If you are keen to have watermarks clickable, use html elements overlaying the <img> element (ie: wrap <img> in a <div> with position:relative; and add an <a> element with display:block;position:absolute;bottom:0px;left:0px;width:100%;height:15px;background:transparent;z-index:2;)
Requirements: linux OS, ffprobe, ffmpeg, and a red5 / Adobe flash media server for your flash stream

*/

// These settings would read an rtsp stream from rtsp://192.168.1.1:80/user=admin&password=&channel=1&stream=1.sdp?real_stream--rtp-caching=100
$server = "192.168.1.1";
$port = "80";
$path = "/user=admin&password=&channel=1&stream=1.sdp?real_stream--rtp-caching=100";
$format = "rtsp";

$pathtowebcamjpg = "webcam.jpg";
$pathtowatermark = "overlay.png";
$user = "web100";	//linux user that this script will run under, so we can detect if it is still running
$flashstream = "rtmp://localhost:1935/live/cam";
$outputresolution = "352x288";	//of both flash stream and jpg


///////////////////////////////////////////////////////////////////////////////////////////////////
// Stuff below here will break things if edited. Avert your eyes unless you know what you are doing
// (or can make it look like you know what you are doing, and won't get naggy if you can't fix it.)
///////////////////////////////////////////////////////////////////////////////////////////////////

$procs = shell_exec("ps aux | grep $user | grep ffmpeg | grep $port");
$procs = explode("\n",trim($procs));
if(count($procs)<2 || !file_exists($pathtowebcamjpg) || filemtime($pathtowebcamjpg)<(time()-360)){
	
	$cmd = "";
	if($format=="rtsp"){
		$cmd='-f rtsp -rtsp_transport -vf h264 tcp "rtsp://'.$server.':'.$port.$path.'"';
	}
	
	execute('ffprobe '.$cmd, null, $out, $out, 30);
	
	foreach($procs as $line){
		$parts = explode("  ",$line);
		$row = array();
		foreach($parts as $p)if(trim($p)!='')$row[]=trim($p);
		shell_exec("kill -9 ".$row[1]);
	}
	
	$command = 'nohup ffmpeg '.$cmd.' -vf "movie='.$pathtowatermark.' [watermark]; [in][watermark] overlay=main_w-overlay_w-10:10 [out]" -f mpegts - | ffmpeg -i - -f flv -r 15 -an -s '.$outputresolution.'  "'.$flashstream.'" -f image2 -updatefirst 1 -s '.$outputresolution.' -vf fps=1 '.$pathtowebcamjpg.' > /dev/null 2>&1';
	if(stristr($out,"Video: h264") || filemtime($pathtowebcamjpg)<(time()-360)){
		unlink($pathtowebcamjpg);
		shell_exec($command);
	}
}
exit;

function execute($cmd, $stdin=null, &$stdout, &$stderr, $timeout=false)
{
    $pipes = array();
    $process = proc_open(
        $cmd,
        array(array('pipe','r'),array('pipe','w'),array('pipe','w')),
        $pipes
    );
    $start = time();
    $stdout = '';
    $stderr = '';

    if(is_resource($process))
    {
        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
    }

    while(is_resource($process))
    {
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        if($timeout !== false && time() - $start > $timeout)
        {
            proc_terminate($process, 9);
            return 1;
        }

        $status = proc_get_status($process);
        if(!$status['running'])
        {
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            return $status['exitcode'];
        }

        usleep(100000);
    }

    return 1;
}