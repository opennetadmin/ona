<?
$conf[$modulename]['Title'] = "Host Actions";
$conf[$modulename]['Splunk']['url'] = "https://splunk.keynetics.com:8001/?events/?eventspage=1&num=10&q=%fqdn";
$conf[$modulename]['Cacti Graph']['url'] = "https://cacti.keynetics.com/cacti/graph_view.php?action=tree&name=%fqdn";
$conf[$modulename]['Wiki Page']['url'] = "https://wiki.keynetics.com/dokuwiki/network/servers/%fqdn";
?>