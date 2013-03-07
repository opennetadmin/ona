<?php
// The following checks with the opennetadmin server to see what the most current version is.
// It will do this each time the interface is opened so the traffic should be very minimal.


$title_right_html = '';
$title_left_html  = '';
$modbodyhtml = '';

// Display only on the desktop
if ($extravars['window_name'] == 'html_desktop') {
    // Dont perform a version check if the user has requested not to
    if (!$conf['skip_version_check']) {
        //@ini_set('user_agent',$_SERVER['HTTP_USER_AGENT']."-----".$conf['version']);

        // Define the URL we use to check what version is the latest release
        $ona_check_version_url = 'http://checkversion.opennetadmin.com';

        if ($ona_check_version_url) {
            $stream_opts = array(
                'http'=> array(
                    'method'=>   'GET',
                    'user_agent'=>  $_SERVER['HTTP_USER_AGENT']."-----".$conf['version']
                )
             );

            // If we have a proxy url set up, use it to check with
            if(isset($conf['http_proxy'])) {
                if(preg_match('/http:\/\/([^:]+:?\d*)/',$conf['http_proxy'],$proxyhost)) {
                    $stream_opts['http']['proxy'] = 'tcp://'.$proxyhost[1];
                    $stream_opts['http']['request_fulluri'] = true;
                }
            }

            // create a stream context
            $context = stream_context_create($stream_opts);

            // use fsockopen to test that the connection works, if it does, open using fopen
            // for some reason the default_socket_timeout was not working properly.
            //$fsock = @fsockopen("tcp://{$onachkserver}", 80, $errNo, $errString, 2);
            //if ($fsock) {
                $old = @ini_set('default_socket_timeout', 2);
                $file = @fopen($ona_check_version_url, 'r', false, $context);
                @ini_set('default_socket_timeout', $old);
            //}
        }

        $onaver = "Unable to determine";
        if ($file) {
            while (!feof ($file)) {
                $buffer = trim(fgets ($file, 4096));
                $onaver = $buffer;
            }
            fclose($file);
        }
        if ($conf['version'] == $onaver) {
            $versit = "<img src='{$images}/silk/accept.png'> You are on the official stable version! ({$onaver})<br/><br/>";
        }
        else {
            $sty='fail';
            if ($onaver == "Unable to determine") $sty='_unknown';

            $modbodyhtml .= <<<EOL
<div class='version_check{$sty}'>
    <img src='{$images}/silk/exclamation.png'> You are NOT on the latest release version<br>
    Your version &nbsp;&nbsp;&nbsp;= {$conf['version']}<br>
    Latest version = {$onaver}<br>
<br>
Please <a href="http://opennetadmin.com/download.html">DOWNLOAD</a> the latest version.
</div>
EOL;

            $title_left_html .= 'Newer Version Available';

        }
    }
}


?>
