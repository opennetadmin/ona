<?php

$window['title'] = "About ONA";

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
$year = date('Y');

$window['html'] .= <<<EOL

    <!-- Window Content -->
    <table style="background-color: {$color['window_content_bg']}; padding-left: 25px; padding-right: 25px;" width="100%" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td colspan="2" align="center" class="padding" style="font-weight: bold;">
            <u>&copy; {$year} OpenNetAdmin - {$conf['version']}</u>
        </td>
    </tr>

    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Main Website:
        </td>
        <td align="left" rowspan="1" class="padding">
            <a href="http://opennetadmin.com">http://opennetadmin.com</a>
        </td>
    </tr>

    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Issues/Discussion:
        </td>
        <td align="left" rowspan="1" class="padding">
            <a href="https://github.com/opennetadmin/ona/issues">https://github.com/opennetadmin/ona/issues</a>
        </td>
    </tr>

    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Documentation:
        </td>
        <td align="left" rowspan="1" class="padding">
            <a href="http://opennetadmin.com/docs/">http://opennetadmin.com/docs</a>
        </td>
    </tr>

    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Developed By:
        </td>
        <td align="left" rowspan="1" class="padding">
            <a href="mailto:matt@opennetadmin.com">Matt Pascoe</a>,<br> <a href="mailto:caspian@dotconf.net">Brandon Zehm</a>,<br> <a href="mailto:deacon@thedeacon.org">Paul Kreiner</a>
        </td>
    </tr>

    <tr>
        <td align="right" valign="top" class="padding" style="font-weight: bold;">
            Special Thanks:
        </td>
        <td align="left" rowspan="1" class="padding">
            <a href="http://www.famfamfam.com/lab/icons/silk/">Silk Icons, by Mark James</a>
        </td>
    </tr>

    </table>



EOL;




?>
