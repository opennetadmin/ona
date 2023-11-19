<?php

$window['title'] = "Keyboard shortcuts";

$window['js'] .= <<<EOL
EOL;

global $conf;

$window['html'] .= <<<EOL

    <!-- Window Content -->
    <table style="background-color: {$color['window_content_bg']}; padding-left: 25px; padding-right: 25px;" width="100%" cellspacing="0" border="0" cellpadding="0">

    <tr>
        <td colspan="2" align="center" class="padding" style="font-weight: bold;">
            <u>Jumping</u>
        </td>
    </tr>

    <tr> <td class="padding"> <b>g</b> then <b>s</b>: </td> <td class="padding">Focus the quicksearch box</td> </tr>
    <tr> <td class="padding"> <b>g</b> then <b>c</b>: </td> <td class="padding">Open IP Calculator</td> </tr>
    <tr> <td class="padding"> <b>g</b> then <b>h</b>: </td> <td class="padding">Open main Home screen</td> </tr>
    <tr> <td class="padding"> <b>g</b> then <b>l</b>: </td> <td class="padding">Open login popup</td> </tr>

    <tr><td colspan="2">&nbsp;</td> </tr>

    <tr>
        <td colspan="2" align="center" class="padding" style="font-weight: bold;">
            <u>Advanced search Tab</u>
        </td>
    </tr>

    <tr> <td class="padding"> <b>s</b> then <b>b</b>: </td> <td class="padding">Block search tab</td> </tr>
    <tr> <td class="padding"> <b>s</b> then <b>v</b>: </td> <td class="padding">Vlan Campus search tab</td> </tr>
    <tr> <td class="padding"> <b>s</b> then <b>s</b>: </td> <td class="padding">Subnet search tab</td> </tr>
    <tr> <td class="padding"> <b>s</b> then <b>h</b>: </td> <td class="padding">Host search tab</td> </tr>
    <tr> <td class="padding"> <b>s</b> then <b>d</b>: </td> <td class="padding">DNS domain search tab</td> </tr>

    <tr><td colspan="2">&nbsp;</td> </tr>

    <tr>
        <td colspan="2" align="center" class="padding" style="font-weight: bold;">
            <u>Add New</u>
        </td>
    </tr>

    <tr> <td class="padding"> <b>a</b> then <b>h</b>: </td> <td class="padding">Add new Host</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>i</b>: </td> <td class="padding">Add new Interface</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>s</b>: </td> <td class="padding">Add new Subnet</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>d</b>: </td> <td class="padding">Add new DNS record</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>D</b>: </td> <td class="padding">Add new DNS domain</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>v</b>: </td> <td class="padding">Add new Vlan</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>V</b>: </td> <td class="padding">Add new Vlan Campus</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>b</b>: </td> <td class="padding">Add new Block</td> </tr>
    <tr> <td class="padding"> <b>a</b> then <b>l</b>: </td> <td class="padding">Add new Location</td> </tr>



    </table>



EOL;




?>
