<?php

$window['title'] = "User Info";

$window['js'] .= <<<EOL

EOL;

global $conf, $self;

$output['ona_username']    = $_SESSION['ona']['auth']['user']['username'];
$output['ona_user_level']  = $_SESSION['ona']['auth']['user']['level'];
$groups = array_keys($_SESSION['ona']['auth']['user']['grps']); sort($groups);
$output['ona_groups']      = implode("\n", $groups);
$permissions = array_keys($_SESSION['ona']['auth']['perms']); sort($permissions);
$output['ona_permissions'] = implode("\n", $permissions);


// Escape data for display in html
foreach(array_keys($output) as $key) { $output[$key] = nl2br(htmlentities($output[$key], ENT_QUOTES, $conf['php_charset'])); }


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
    <form id="passchange_form" autocomplete="off">
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

    <div style="background-color: {$color['window_content_bg']};">
    <table style="padding: 25px;" cellspacing="0" border="0" cellpadding="0">
    <tr><td class="padding" style="font-weight: bold;" align="center"  colspan="3"><u>Current DB connection info</u></td></tr>
    <tr><td class="padding" style="font-weight: bold;">Database Host</td><td class="padding">{$self['db_host']}</td></tr>
    <tr><td class="padding" style="font-weight: bold;">Database Type</td><td class="padding">{$self['db_type']}</td></tr>
    <tr><td class="padding" style="font-weight: bold;">Database Name</td><td class="padding">{$self['db_database']}</td></tr>
    <tr><td class="padding" style="font-weight: bold;">Database User</td><td class="padding">{$self['db_login']}</td></tr>
    <tr><td class="padding" style="font-weight: bold;">Database Context</td><td class="padding">{$self['context_name']}</td></tr>
    <tr><td class="padding" style="font-weight: bold;">Database Context Desc</td><td class="padding">{$self['context_desc']}</td></tr>
    <tr><td class="padding" style="font-weight: bold;">Database Context Color</td><td class="padding" style="background-color: {$self['context_color']}">{$self['context_color']}</td></tr>
    </table>
    </div>


EOL;



?>
