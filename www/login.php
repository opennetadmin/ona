<?php
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
/* --------------------------------------------------------- */
// MP: Since we know ONA will generate a ton of notice level errors, lets turn them off here
// I dont believe this will be impactful to anyone. keep an eye out for it however.
error_reporting (E_ALL ^ E_NOTICE);

// clear out existing session info
$_SESSION['ona']['auth'] = array();

// enforce the HTTPS page if required
if (($_SERVER['SERVER_PORT'] != 443) and ($conf['force_https'] == 1)) {
        echo <<<EOL
<html><body>
Redirecting you to: <a href="{$https}{$baseURL}/login.php">{$https}{$baseURL}/login.php</a>
<script type="text/javascript"><!--
    setTimeout("window.location = \"{$https}{$baseURL}/login.php\";", 10);
--></script>
</body></html>
EOL;
    exit;
}

//
// Save a redirect url..
if (!isset($_SESSION['redirect']))
    $_SESSION['redirect'] = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : "{$https}{$baseURL}/"; 

// Include xajax stuff (ajax calls will never make it past this line)
require_once($conf['inc_xajax_stuff']);

$message = '';
if (!empty($_SESSION['login_failure'])) $message = $_SESSION['login_failure'];
unset($_SESSION['login_failure']);

print <<<EOL

<html>
<head>
    <title>{$conf['title']} Standalone Login</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="{$baseURL}/include/html_style_sheet.inc.php">
    <link rel="shortcut icon" type="image/ico" href="{$images}/favicon.ico">
    <script type="text/javascript" src="{$baseURL}/include/js/global.js" language="javascript"></script>
    {$conf['html_headers']}
</head>
<body>
<center>
    <h1 style="color: #5262F2;">OpenNetAdmin Login</h1>
    <div>
        <form id="standalone_loginform_form" onSubmit="return(false);">
            <input id="onapassword" type="hidden" name="onapassword">
            <input id="standalone" type="hidden" name="standalone" value="standalone">
            <table cellspacing="2" border="0" cellpadding="0">
                <tr>
                    <td class="menu-item" align="right">
                        <img src="{$images}/silk/user.png" alt="Username" title="Username" align="left" border="0" style="display: inline;">
                        <input
                            id="onausername"
                            name="onausername"
                            class="edit"
                            style="width: 100px; font-size: 8pt;" type="text" maxlength="64" tabindex="1" accesskey="u"
                            onkeypress="if (event.keyCode == 13) { el('getpass').focus(); }"
                        >
                    </td>
                </tr>
                <tr>
                    <td class="menu-item" align="right">
                        <img src="{$images}/silk/key.png" alt="Password" title="Password" align="left" border="0" style="display: inline;">
                        <input
                            id="getpass"
                            name="getpass"
                            class="edit"
                            style="width: 100px; font-size: 8pt;" type="password" maxlength="64" tabindex="2" accesskey="p"
                            onkeypress="if (event.keyCode == 13) { el('loginbutton').click(); }"
                        >
                    </td>
                </tr>
                <tr>
                    <td class="menu-item" align="right">
                        <input id="loginbutton" class="button" style="font-size: smaller;" type="button" name="logon" value="Login"
                                onClick="el('onapassword').value = el('getpass').value;
                                         xajax_window_submit('tooltips', xajax.getFormValues('standalone_loginform_form'), 'logingo');"
                        >
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <br>
    <span style="color: red; font-weight: bold;" id="loginmsg">{$message}</span>

    <script type="text/javascript"><!--
        /* Focus the username field */
        el('onausername').focus();
    //--></script>

    <br>

</center>
</body>
</html>

EOL;



?>