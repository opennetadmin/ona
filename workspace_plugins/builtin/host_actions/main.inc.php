<?

// Build hostaction list from the $conf['hostaction'] array
foreach ($conf[$modulename] as $hostaction=>$hval) {
    // Skip the module title entry
    if ( $hval == 'Host Actions' ) { continue; }
    // Use the title if there is one, otherwise just use the arrayname
    $hval['title'] = ($hval['title']) ? $hval['title'] : $hostaction;
    // Substitute %fqdn and %ip
    $hval['url'] = str_replace('%fqdn', $record['fqdn'], $hval['url']);
    $hval['url'] = str_replace('%ip', $record['ip_address'], $hval['url']);

    // If the URL has data in it, print.
    // TODO: MDP, maybe offer an $hval['icon'] option to use a different icon specified in the $conf['hostaction']['Name']['icon'] variable
    if ($hval['url']) {
    $modhtml .= <<<EOL
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