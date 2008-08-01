<?
/* -------------------- COMMON HEADER ---------------------- */
$base = dirname(__FILE__);
while ($base and (!is_dir($base.'/include'))) $base = preg_replace('+/[^/]*$+', '', $base);
$include = $base . '/include';
if (!is_dir($include)) { print "ERROR => Couldn't find include folder!\n"; exit; }
require_once($base . '/config/config.inc.php');
/* --------------------------------------------------------- */

// Set the document content-type
header('Content-type: text/css');

// We do this to make sure we have the $color[] entries from the xajax_webwin library
require_once($conf['inc_xajax_stuff']);


// Display the style-sheet
print <<<EOL


/* --------------- Styles for common HTML elements --------------- */

body {
    margin: 0px;
    font-family: {$style['font-family']};
    color: {$color['font_default']};
    background-color: {$color['bg']};
    vertical-align: top;
}

td {
    margin: 0px;
    font-family: {$style['font-family']};
    color: {$color['font_default']};
    vertical-align: top;
}

ul {
    margin-top: 0px;
}

pre {
    font-size: small;
    font-family: courier, courier-new;
}

img {
    vertical-align: middle;
}

a            { color: {$color['link']};  cursor: pointer; text-decoration: underline; }
a:link       { color: {$color['link']};  cursor: pointer; text-decoration: underline; }
a:visited    { color: {$color['vlink']}; cursor: pointer; text-decoration: underline; }
a:active     { color: {$color['alink']}; cursor: pointer; text-decoration: underline; }
a:hover      { cursor: pointer; text-decoration: underline; }



.ws_plugin_content {
  margin-bottom: 8px;
  float: left;
  padding-right: 4px;
  padding-left: 4px;

}

.ws_plugin_title_right {
    font-weight: bold;
    padding: 2px 4px;
    border: solid 1px {$color['border']};
    background-color: {$color['window_content_bg']};
    border-right: 0px;
}

.ws_plugin_title_left {
    font-weight: bold;
    padding: 2px 4px;
    border: solid 1px {$color['border']};
    background-color: {$color['window_content_bg']};
    border-left: 0px;
    text-align: right;
}

/* ---------------  Used in the Site ---------------- */

/* Bar with Nav Links at the top */
.bar {
    height:25px;
    background:{$color['bar_bg']};
    border:1px solid {$color['border']};
    padding-top: 8px;
    padding-bottom: 1px;
    margin:0px;
    vertical-align: middle;
    clear: both;
}

.bar-left {
    float:left;
    max-height: 25px;
    vertical-align: middle;
}

.bar-right {
    float:right;
    text-align:right;
    vertical-align: middle;
}

/* The body of the page */
.theWholeBananna   {
    background-color: {$color['content_bg']};
    color: {$color['font_default']};
}

/* Text at bottom of the page */
.bottomBox {
    font-size: x-small;
    color: #868686;
    font-weight: normal;
    font-family: {$style['font-family']};
}

// color for the background of the version check box
.version_check_fail {
    background-color: #FFDDEE;
}
.version_check_unknown {
    background-color: #FFFFCC;
}

/* ---------------- forms ------------------------ */

form {
    border: none;
    margin: 0;
    display: inline;
}

textarea.edit {
    font-family: monospace;
    border: 1px solid #8CACBB;
    color: Black;
    background-color: white;
    padding: 3px;
    width:100%;
}

input.edit,select.edit {
    border: 1px solid #8CACBB;
    color: Black;
    background-color: white;
    vertical-align: middle;
    padding: 1px;
    display: inline;
}

input.checkbox {
    border: 1px solid #8CACBB;
    background-color: white;
    vertical-align: middle;
    padding: 1px;
    display: inline;
}

input.missing {
    border: 1px solid #8CACBB;
    height: 18px !important;
    max-height: 22px !important;
    min-height: 22px !important;
    color: Black;
    background-color: #ffcccc;
    vertical-align: middle;
    padding: 1px;
    display: inline;
}

input.button {
    border: 1px solid {$color['border']};
    color: Black;
    background-color: white;
    vertical-align: middle;
    text-decoration:none;
    cursor: pointer;
    height: 22px !important;
    max-height: 22px !important;
    min-height: 22px !important;
    margin: 1px;
    display: inline;
    white-space: nowrap;
}

input.button:hover {
    border: 1px solid {$color['border']};
    color: Black;
    background-color: #EBEBEB;
    vertical-align: middle;
    text-decoration:none;
    cursor: pointer;
    height: 22px !important;
    max-height: 22px !important;
    min-height: 22px !important;
    margin: 1px;
    display: inline;
    white-space: nowrap;
}

a.button {
    border: 1px solid {$color['border']};
    background-color: white;
    vertical-align: middle;
    text-align: center;
    text-decoration:none;
    cursor: pointer;
    height: 22px !important;
    max-height: 22px !important;
    min-height: 22px !important;
    display: inline;
    margin-top: 0;
    margin-bottom: .2em;
    padding: 2px;
    white-space: nowrap;
}

a.button:hover {
    border: 1px solid {$color['border']};
    background-color: #EBEBEB;
    vertical-align: middle;
    text-align: center;
    text-decoration:none;
    cursor: pointer;
    height: 22px !important;
    max-height: 22px !important;
    min-height: 22px !important;
    display: inline;
    margin-top: 0;
    margin-bottom: .2em;
    padding: 2px;
    white-space: nowrap;
}


/* --------------- AJAX Boxes --------------- */

a.nav          { color: {$color['link_nav']};  cursor: pointer; text-decoration: none; }
a.nav:hover    { color: {$color['link_nav']};  cursor: pointer; text-decoration: underline; }
a.act          { color: {$color['link_act']};  cursor: pointer; text-decoration: none; }
a.act:hover    { color: {$color['link_act']};  cursor: pointer; text-decoration: underline; }
a.domain       { color: {$color['link_domain']}; cursor: pointer; text-decoration: none; }
a.domain:hover { color: {$color['link_domain']}; cursor: pointer; text-decoration: underline; }

/* This is a new style action class that pads the images. */
a.linkact          { color: #FF8000;  cursor: pointer; text-decoration: none; }
a.linkact:hover    { color: #FF8000;  cursor: pointer; text-decoration: underline; }
a.linkact img      { padding-right: 4px; }

.row-normal    { background-color: #FFFFFF; }
.row-highlight { background-color: #E8E9FD; }

.topmenu-item {
    font-size: smaller;
    color: {$color['menu_item_text']};
    vertical-align: middle;
    margin: 0px;
    padding: 2px 4px;
}

.asearch-line {
    vertical-align: top;
    font-size: smaller;
    margin: 0px;
    padding-top:    2px;
    padding-bottom: 2px;
    padding-left:   4px;
    padding-right:  4px;
}

.qf-search-line {
    color: #000000;
    vertical-align: top;
    font-size: smaller;
    margin: 0px;
    padding: 2px 4px;
}

/* Ona Menus .. used with webwinTT library */
div.wwTT_ona_menu {
    border: 1px solid #333366;
    background-color: #333366;
}
div.wwTT_ona_menu .row {
    font-size: 10px;
    font-family: Verdana, Helvetica;
    vertical-align: center;
    padding: 2px;
    color: #4A4A4A;
    background-color: #F1F1FF;
    border-bottom: 1px solid #E3E3F0;
}
div.wwTT_ona_menu .hovered {
    font-size: 10px;
    font-family: Verdana, Helvetica;
    vertical-align: center;
    padding: 2px;
    color: #ECECEC;
    background-color: #7392DC;
    border-bottom: 1px solid #E3E3F0;
}

/* Nicetitle Style */
div.wwTT_ona
{
    background-color: #007ADE;
    color: #FFFFFF;
    font-weight: bold;
    font-size: 13px;
    font-family: "Trebuchet MS", sans-serif;
    left: 0;
    top: 0;
    padding: 4px;
    position: absolute;
    text-align: left;
    z-index: 20;
    -moz-border-radius: 4px;
    filter: progid:DXImageTransform.Microsoft.Alpha(opacity=87);
    -moz-opacity: .87;
    -khtml-opacity: .87;
    opacity: .87;
}
div.wwTT_ona .contents
{
    margin: 0;
    padding: 0 3px;
    filter: progid:DXImageTransform.Microsoft.Alpha(opacity=100);
    -moz-opacity: 1;
    -khtml-opacity: 1;
    opacity: 1;
}
div.wwTT_ona p
{
    color: #D17E62;
    font-size: 9px;
    padding: 3px 0 0 0;
    margin: 0;
    text-align: left;
    -moz-opacity: 1;
}

/* Quick Filter Style */
div.wwTT_qf {
    background-color: #A6C3F5;
    color: #FFFFFF;
    font-weight: bold;
    font-size: 13px;
    font-family: "Trebuchet MS", sans-serif;
    left: 0;
    top: 0;
    position: absolute;
    text-align: left;
    z-index: 20;
    padding: 4px;
    -moz-border-radius: 4px;
}
div.wwTT_qf .contents {
    margin: 0;
    padding: 0 3px;
}

/* Interface menu tooltips */
div.wwTT_int_menu
{
    background-color: #F2F2F2;
    font-size: 13px;
    font-family: "Trebuchet MS", sans-serif;
    left: 0;
    top: 0;
    padding: 4px;
    position: absolute;
    text-align: left;
    z-index: 20;
    -moz-border-radius: 4px;
    filter: progid:DXImageTransform.Microsoft.Alpha(opacity=87);
    -moz-opacity: .87;
    -khtml-opacity: .87;
    opacity: .87;
    border: solid black 1px
}
div.wwTT_int_menu .contents
{
    margin: 0;
    padding: 0 3px;
    filter: progid:DXImageTransform.Microsoft.Alpha(opacity=100);
    -moz-opacity: 1;
    -khtml-opacity: 1;
    opacity: 1;
}
div.wwTT_int_menu .row {
    font-size: 10px;
    font-family: Verdana, Helvetica;
    vertical-align: center;
    padding: 2px;
    color: #4A4A4A;
    background-color: #F1F1FF;
    border-bottom: 1px solid #E3E3F0;
}
div.wwTT_int_menu .hovered {
    font-size: 10px;
    font-family: Verdana, Helvetica;
    vertical-align: center;
    padding: 2px;
    color: #ECECEC;
    background-color: #7392DC;
    border-bottom: 1px solid #E3E3F0;
}
div.wwTT_int_menu p
{
    color: #D17E62;
    font-size: 9px;
    padding: 3px 0 0 0;
    margin: 0;
    text-align: left;
    -moz-opacity: 1;
    -khtml-opacity: 1;
}


/* Custom Attribute info tooltips */
div.wwTT_ca_info
{
    background-color: #F2F2F2;
    font-size: 13px;
    font-family: "Trebuchet MS", sans-serif;
    left: 0;
    top: 0;
    padding: 4px;
    position: absolute;
    text-align: left;
    z-index: 20;
    -moz-border-radius: 4px;
    filter: progid:DXImageTransform.Microsoft.Alpha(opacity=87);
    border: solid black 1px
}
div.wwTT_ca_info .contents
{
    margin: 0;
    padding: 0 3px;
    filter: progid:DXImageTransform.Microsoft.Alpha(opacity=100);
}
div.wwTT_ca_info p
{
    color: #D17E62;
    font-size: 9px;
    padding: 3px 0 0 0;
    margin: 0;
    text-align: left;
}




.table-tab-active {
    font-size: 10pt;
    white-space: nowrap;
    cursor: pointer;
    font-weight: bold;
    vertical-align: middle;
    background-color: {$color['window_tab_active_bg']};
    {$style['borderR']};
    padding: 2px 5px;
}

.table-tab-inactive {
    font-size: 10pt;
    white-space: nowrap;
    cursor: pointer;
    font-weight: normal;
    vertical-align: middle;
    background-color: {$color['window_tab_inactive_bg']};
    {$style['borderR']};
    padding: 2px 5px;
}

input.filter {
    font-size: 12px;
    border: 1px solid #8CACBB;
    color: #000000;
    background-color: white;
    vertical-align: middle;
    display: inline;
    padding: 2px;
    margin: 2px;
}

.act-box { background-color: #E9FFE1; }

.list-box {
    background-color: #ffffff;
    border-top: 1px solid {$color['border']};
    margin-bottom: 0.2em;
    width: 100%;
}

/*  Nasty hack to make IE display the lists properly...
    it kinda screws with the subnet usage div but tough for the IE users. */
* html .list-box {width:96%}

.list-header {
    background-color: {$color['window_content_bg']};
    border-bottom: 1px solid {$color['border']};
    margin: 0px;
    padding: 2px 5px;
    white-space: nowrap;
    font-size: 12px;
    font-weight: bold;
}

.list-row {
    border-bottom: 1px solid {$color['border']};
    margin: 0px;
    padding: 2px 5px;
    white-space: nowrap;
    font-size: 12px;
}


/* ---------------------------- Diff rendering --------------------------*/
table.diff { background:white; }
td.diff-blockheader {font-weight:bold}
td.diff-header {
    border-bottom: 1px solid #8CACBB;
    font-size: small;
    font-weight: bold;
    text-align: center;
}
td.diff-addedline {
    background:#ddffdd;
    font-family: monospace;
    font-size: 100%;
}
td.diff-deletedline {
    background:#ffffbb;
    font-family: monospace;
    font-size: 100%;
}
td.diff-context {
    background:#f7f9fa;
    font-family: monospace;
    font-size: 100%;
}
span.diffchange { color: red; }




/* ---------------------------- Misc --------------------------*/

.padding {
    margin: 0px;
    padding: 2px 4px;
    font-size: small;
}

.hidden  { visibility:hidden; display:none; }

.display_notes { border: none; }

EOL;
?>
