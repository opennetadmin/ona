<?

// Do some HTML headers before printing anything
header("Cache-control: private");

print <<<EOL
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<!-- BEGIN HEADER -->
<!-- This web site is copyrighted (c) 2006, OpenNetAdmin Team -->
<html>
<head>
    <title>{$conf['title']}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <link rel="stylesheet" type="text/css" href="{$baseURL}/include/html_style_sheet.php">
    <link rel="shortcut icon" type="image/ico" href="{$images}/favicon.ico">
    <script type="text/javascript" src="{$baseURL}/include/js/global.js" language="javascript"></script>
{$conf['html_headers']}

</head>
<body bgcolor="{$color['bg']}" link="{$color['link']}" alink="{$color['alink']}" vlink="{$color['vlink']}">
EOL;

// Include the Top (start) Bar
include("$include/html_top_bar.php");

print <<<EOL
    <!-- Workspace div -->
    <div id="content_table" style="height: 90%;" class="theWholeBananna">
        <!-- Parent div for all "windows" -->
        <span id="window_container"></span>

<!-- END HEADER -->
EOL;
?>