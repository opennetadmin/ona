<?php

$title_right_html = '';
$title_left_html  = '';
$modbodyhtml = '';

// if this is a display host screen then go ahead and make a puppet facts window
if ($extravars['window_name'] == 'html_desktop') {

    // The following checks with the opennetadmin server to see what the most current version is.
    // It will do this each time the interface is opened so the traffic should be very minimal.
    // Dont perform a version check if the user has requested not to
    if (!$conf['skip_version_check']) {
        @ini_set('user_agent',$_SERVER['HTTP_USER_AGENT']."-----".$conf['version']);
        //$onachkserver = @gethostbynamel('opennetadmin.com');
        $onachkserver = 'opennetadmin.com';
        if ($onachkserver) {
            // use fsockopen to test that the connection works, if it does, open using fopen
            // for some reason the default_socket_timeout was not working properly.
            $fsock = @fsockopen("tcp://{$onachkserver}", 80, $errNo, $errString, 2);
            if ($fsock) {
                $old = @ini_set('default_socket_timeout', 2);
                $file = @fopen("http://{$onachkserver}/check_version.php", "r");
                @ini_set('default_socket_timeout', $old);
            }
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
Please <a href="http://{$onachkserver}/docs/download">DOWNLOAD</a> the latest version.
</div>
EOL;

            $title_left_html .= 'Newer Version Available';

        }
    }
}


?>
