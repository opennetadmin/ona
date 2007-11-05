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
            <div style="float: left;">{$output['ona_username']}</div>
        </td>
        <td align="left" rowspan="2" class="padding">
            <div title="Detailed info about network managment" style="float: right;"><a target="null" href="http://www.homestarrunner.com/sbemail152.html"><img src="{$images}/strongbad.gif" hspace="0" vspace="0" align="left" border="0"></a></div>
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

    <tr>
        <td class="padding">
            &nbsp;
        </td>
        <td class="padding">
            <input id="changebutton" type="button" name="change_pass" value="Change Password"
                    onclick="el('passchange_container').style.display = (el('passchange_container').style.display == 'none') ? '' : 'none';
                             el('changebutton').style.display = 'none';"
            >
        </td>
    </td>

    </table>


    <!-- PASSWORD CHANGE CONTAINER -->
    <span id="passchange_container" style="display:none;">
    <form id="passchange_form">
    <input id="old" name="old" type="hidden" value="">
    <input id="new1" name="new1" type="hidden" value="">
    <input id="new2" name="new2" type="hidden" value="">
    <table style="background-color: {$color['window_content_bg']}; padding-left: 25px; padding-right: 25px;" width="100%" cellspacing="0" border="0" cellpadding="0">
        <tr>
            <td align="right" nowrap="true" class="padding" style="font-weight: bold;">
                Old password:
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="old_pass"
                    name="old_pass"
                    value=""
                    class="edit"
                    type="password"
                    size="10" maxlength="20"
                />
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" class="padding" style="font-weight: bold;">
                New password:
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="new1_pass"
                    name="new1_pass"
                    value=""
                    class="edit"
                    type="password"
                    size="10" maxlength="20"
                />
            </td>
        </tr>

        <tr>
            <td align="right" nowrap="true" class="padding" style="font-weight: bold;">
                Confirm:
            </td>
            <td class="padding" align="left" width="100%">
                <input
                    id="new2_pass"
                    name="new2_pass"
                    value=""
                    class="edit"
                    type="password"
                    size="10" maxlength="20"
                />
            </td>
        </tr>

        <tr>
            <td colspan="2" align="center" nowrap="true" class="padding">
                <span style="color: red;" id="passchangemsg"></span>
            </td>
        </tr>


        <tr>
            <td class="padding">
                &nbsp;
            </td>
            <td class="padding">
                <input id="changego" type="button" name="changego" value="Change"
                        onclick="el('old').value = make_md5(el('old_pass').value);
                                 el('new1').value = make_md5(el('new1_pass').value);
                                 el('new2').value = make_md5(el('new2_pass').value);
                                 xajax_window_submit('{$window_name}', xajax.getFormValues('passchange_form'), 'change_user_password');"
                >
            </td>
        </td>
    </table>
    </form>
    </span>


EOL;


function ws_change_user_password($window_name, $form) {
    global $conf, $self, $onadb;



    $username = $_SESSION['ona']['auth']['user']['username'];
    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();
    $js = "el('passchangemsg').innerHTML = '<span style=\"color: green;\">Changed!</span>'";
    $exit_status = 0;

    // Validate the userid was passed and is "clean"
    if (!preg_match('/^[A-Za-z0-9.\-_]+$/', $username)) {
        $js = "el('passchangemsg').innerHTML = 'Invalid username format';";
        $response->addScript($js);
        return($response->getXML());
    }

    list($status, $rows, $user) = db_get_record($onadb, 'users', "username LIKE '{$username}'");

    if (!$rows) {
        $js = "el('passchangemsg').innerHTML = 'Unknown user';";
        // Return some javascript to the browser
        $response->addScript($js);
        return($response->getXML());
    }


    if ($user['password'] != $form['old']) {
        $js = "el('passchangemsg').innerHTML = 'Password incorrect (old)';";
        // Return some javascript to the browser
        $response->addScript($js);
        return($response->getXML());
    }

    if ($form['new1'] != $form['new2']) {
        $js = "el('passchangemsg').innerHTML = 'New passwords dont match.';";
        // Return some javascript to the browser
        $response->addScript($js);
        return($response->getXML());
    }

    list ($status, $rows) = db_update_record(
        $onadb,
        'users',
        array(
            'username' => $username
        ),
        array(
            'password' => $form['new2']
        )
    );


    // If the module returned an error code display a popup warning
    if ($status) {
        $js = "alert('Save failed: " . trim($self['error']) . "');";
    }


    if ($js) { $response->addScript($js); }
    return($response->getXML());

}




?>