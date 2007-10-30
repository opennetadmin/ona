<?

$window['title'] = "User Info";

$window['js'] .= <<<EOL
    /* Put a minimize icon in the title bar */
    el('{$window_name}_title_r').innerHTML = 
        '&nbsp;<a onClick="toggle_window(\'{$window_name}\');" title="Minimize window" style="cursor: pointer;"><img src="{$images}/icon_minimize.gif" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;
    
    /* Put a help icon in the title bar */
    el('{$window_name}_title_r').innerHTML = 
        '&nbsp;<a href="{$_ENV['help_url']}{$window_name}" target="null" title="Help" style="cursor: pointer;"><img src="{$images}/silk/help.png" border="0" /></a>' +
        el('{$window_name}_title_r').innerHTML;
    
EOL;

global $conf;

$output['ona_username']    = $_SESSION['ona']['auth']['user']['username'];
$output['ona_user_level']  = $_SESSION['ona']['auth']['user']['level'];
$groups = array_keys($_SESSION['ona']['auth']['groups']); sort($groups);
$output['ona_groups']      = implode("\n", $groups);
$permissions = array_keys($_SESSION['ona']['auth']['perms']); sort($permissions);
$output['ona_permissions'] = implode("\n", $permissions);


// Escape data for display in html
foreach(array_keys($output) as $key) { $output[$key] = nl2br(htmlentities($output[$key], ENT_QUOTES)); }

// Display su buttons if we're in development mode
$su_button_html = '';
if ($conf['dev_mode'] == 1) {
    $su_button_html .= <<<EOL
        <tr>
            <td colspan="2" align="center" class="padding">
                <input class="edit" type="button" name="su_guest" value="su guest" onClick="xajax_window_submit('{$window_name}', ' ', 'su_guest');">
                &nbsp;
                <input class="edit" type="button" name="logout" value="Re-login as me" onClick="xajax_window_submit('{$window_name}', ' ', 'su_me');">
            </td>
        </td>
EOL;
}


$window['html'] .= <<<EOL
    
    <!-- Window Content -->
    <table style="background-color: {$color['window_content_bg']}; padding-left: 25px; padding-right: 25px;" width="100%" cellspacing="0" border="0" cellpadding="0">
    
    <tr>
        <td colspan="2" align="center" class="padding" style="font-weight: bold;">
            <u>ONA User Auth Info</u>
        </td>
    </td>
    
    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Username:
        </td>
        <td align="left" class="padding">
            <div title="Detailed info about network managment" style="float: right;"><a target="null" href="http://www.homestarrunner.com/sbemail152.html"><img src="{$images}/strongbad.gif" hspace="0" vspace="0" align="left" border="0"></a></div>
            {$output['ona_username']}
        </td>
    </td>
    
    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Groups:
        </td>
        <td align="left" class="padding">
            {$output['ona_groups']}&nbsp;
        </td>
    </td>
    
    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Permissions:
        </td>
        <td align="left" class="padding">
            {$output['ona_permissions']}&nbsp;
        </td>
    </td>
    
    {$su_button_html}
    
    <tr>
        <td colspan="2" align="left" class="padding">
            &nbsp;
        </td>
    </td>

    <!--  commented out for now till LDAP stuff is usefull
    <tr>
        <td colspan="2" align="center" class="padding" style="font-weight: bold;">
            <u>Domain Info</u>
        </td>
    </td>
    
    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Username:
        </td>
        <td align="left" class="padding">
            {$output['domain']} \ {$output['username']}
        </td>
    </td>
    
    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Full Name:
        </td>
        <td align="left" class="padding">
            {$output['fullname']}
        </td>
    </td>
    
    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Groups:
        </td>
        <td align="left" class="padding">
            {$output['groups']}
        </td>
    </td>
    -->
    
    </table>
    
EOL;



// Switch to guest
function ws_su_guest($window_name, $form) {
    global $conf, $self, $mysql;
    
    list($status, $rows, $user) = db_get_record($mysql, 'users', array('username' => 'guest'));
    $_SESSION['ona']['auth']['user'] = $user;
    $_SESSION['username'] = $_SESSION['ona']['auth']['user']['username'];
    $_SESSION['ona']['auth']['groups'] = array();
    $_SESSION['ona']['auth']['perms'] = array();
    
    $js = "alert('You are now a guest user!');";
    
    $response = new xajaxResponse();
    if ($js) { $response->addScript($js); }
    return($response->getXML());
    
}



// Re-login as yourself
function ws_su_me($window_name, $form) {
    global $conf, $self, $mysql;
    
    $_SESSION['ona']['auth']['user'] = array();
    $_SESSION['ona']['auth']['groups'] = array();
    $_SESSION['ona']['auth']['perms'] = array();
    
    $js = "document.location = 'login.php';";
    
    $response = new xajaxResponse();
    if ($js) { $response->addScript($js); }
    return($response->getXML());
    
}





?>