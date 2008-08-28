<?php

list($status, $rows, $location) = ona_get_location_record(array('id' => $record['location_id']));
if ($rows) {

// if it is a tooltip.. make it look different
$tip_style = $extravars['tipstyle'];
if (!$extravars['tipstyle']) {
    $tip_style = 'class="display_notes"';
}

$title_left_html = '';
$title_right_html = '';

if (auth('location_add')) {
    $title_left_html .= <<<EOL
        <a class="row"
            onMouseOver="this.className='hovered';"
            onMouseOut="this.className='row';"
            onClick="removeElement('start_menu'); xajax_window_submit('edit_location', 'id=>{$location['id']}', 'editor');"
            title="Modify location"
        ><img style="vertical-align: middle;" src="{$images}/silk/page_edit.png" border="0"/></a>
EOL;
    }

$title_left_html .= <<<EOL
        <a title="View map"
            class="act"
            onClick="window.open(
                        'http://maps.google.com/maps?q={$location['address']},{$location['city']},{$location['state']},{$location['zip_code']} ({$location['name']})',
                        'MapIT',
                        'toolbar=0,location=1,menubar=0,scrollbars=0,status=0,resizable=1,width=985,height=700')"
        ><img src="{$images}/silk/world_link.png" border="0"></a>
        <b>Location: {$location['reference']}</b>
EOL;


$modbodyhtml .= <<<EOL
            <table width=100% cellspacing="0" border="0" cellpadding="0" style="margin-bottom: 8px;">
            <tr>
                <td align="right" valign="top" nowrap="true" {$tip_style}><b>Reference</b>&nbsp;</td>
                <td class="padding" align="left" {$tip_style}>{$location['reference']}&nbsp;</td>
            </tr>

EOL;
    if ($location['name']) {
        $modbodyhtml .= <<<EOL
            <tr>
                <td align="right" valign="top" nowrap="true" {$tip_style}><b>Name</b>&nbsp;</td>
                <td class="padding" align="left" {$tip_style}>{$location['name']}&nbsp;</td>
            </tr>
EOL;
    }
    $address = '';
    if ($location['address']) {
        $address .= "{$location['address']}&nbsp;<br>\n";
    }
    if ($location['city']) {
        $address .= $location['city'];
    }
    if ($location['state']) {
        if ($location['state']) {
            $address .= ", ";
        }
        $address .= "{$location['state']}";
    }
    $address .= ' ' . $location['zip_code'];
    if ($address) {
        $modbodyhtml .= <<<EOL
            <tr>
                <td align="right" valign="top" nowrap="true" {$tip_style}><b>Address</b>&nbsp;</td>
                <td class="padding" valign="top" align="left" {$tip_style}>
                    {$address}&nbsp;
                </td>
            </tr>
EOL;
    }

    if ($location['latitude'] or $location['longitude']) {
        $modbodyhtml .= <<<EOL
            <tr>
                <td align="right" valign="top" nowrap="true" {$tip_style}><b>Lat/Long</b>&nbsp;</td>
                <td class="padding" align="left" {$tip_style}>{$location['latitude']} / {$location['longitude']}</td>
            </tr>
EOL;
    }
    if ($location['misc']) {
        $modbodyhtml .= <<<EOL
            <tr>
                <td align="right" valign="top" nowrap="true" {$tip_style}><b>Misc.</b>&nbsp;</td>
                <td class="padding" align="left" {$tip_style}><textarea size="256" cols=20 rows=3 {$tip_style}>{$location['misc']}</textarea></td>
            </tr>
EOL;
    }

$modbodyhtml .= <<<EOL
            </table>
EOL;

} else {
    // dont display anything
    $modbodyhtml = '';
}
?>