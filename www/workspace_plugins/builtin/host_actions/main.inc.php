<?php

$rec_content = print_r($record, true);
$debug_display = 0;  // Set this to 1 to see contents of the $record array


// Load any user specific entries and add them to the list
// If you re-define an existing entry, it will override the default
$mod_conf="{$base}/local/config/{$modulename}.config.php";
if (file_exists($mod_conf)) { require_once($mod_conf); }


$title_left_html = 'Host Actions';


$title_right_html .= '';


if ($debug_display) {
    $modbodyhtml .= <<<EOL
        <pre>{$rec_content}</pre>
EOL;
}

// If there is a location id then get the info so we can replace it later
if ($record['location_id'] > 0) {
    // TODO: maybe do some checks if we dont find a location but ohhhh well for now.
    list($status, $rows, $location) = ona_get_location_record(array('id' => $record['location_id']));
}

// Build hostaction list from the $conf['hostaction'] array
foreach ($conf[$modulename] as $hostaction=>$hval) {
    // Skip the module title entry
    // Use the title if there is one, otherwise just use the arrayname
    $hval['title'] = ($hval['title']) ? $hval['title'] : $hostaction;
    // Substitute %fqdn and %ip and %loc
    $hval['url'] = str_replace('%fqdn', $record['fqdn'], $hval['url']);
    $hval['url'] = str_replace('%ip', $record['ip_address'], $hval['url']);
    $hval['url'] = str_replace('%loc', $location['reference'], $hval['url']);



    // If the URL has data in it, print.
    // TODO: MDP, maybe offer an $hval['icon'] option to use a different icon specified in the $conf['hostaction']['Name']['icon'] variable
    if ($hval['url']) {
    $modbodyhtml .= <<<EOL
            <span>
                <a title="{$hval['title']}"
                    class="act"
                    href="{$hval['url']}"
                ><img src="{$images}/silk/lightning_go.png" border="0">{$hostaction}</a>&nbsp;
            </span><br>
EOL;
    }
}


?>