<?php

/*
The following checks with the opennetadmin server to see what the most current version is.
It will do this each time the interface is opened so the traffic should be very minimal.

Since fopen is required, I had been getting 'unable to determine' messages.
I was able to fix it by adding the following to my apache config for the ona site

php_admin_flag allow_url_fopen on # not sure this is required anymore?
*/

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
        $onaver = 'Unable to determine';
        $buffer = '';

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
                $timeout = 2;
                $old = @ini_set('default_socket_timeout', $timeout);
                $file = @fopen($ona_check_version_url, 'r', false, $context);
                if (!$file) {
                  $onaver="Unable to reach check version server";
                } else {
                  @ini_set('default_socket_timeout', $old);
                  stream_set_timeout($file, $timeout);
                  #stream_set_blocking($file, 0);
                }
            //}
        }

        if (isset($file) && $file != false) {
            while (!feof ($file)) {
                $buffer .= trim(fread ($file, 1024));
            }
            fclose($file);
            $onaver = $buffer;
        }
        if ($conf['version'] == $onaver) {
            $versit = "You are on the official stable version! ({$onaver})<br/><br/>";
        }
        else {
            $sty='fail';
            if ($onaver == 'Unable to determine') $sty='_unknown';

            $modbodyhtml .= <<<EOL
<div class='version_check{$sty}'>
    <i class='nf nf-fa-exclamation_triangle' style='color:red;'></i> You are NOT on the latest release version<br>
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
