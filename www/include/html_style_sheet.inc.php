<?php
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

/* --------------- Default Style Reset ---------------
   One of the many CSS reset starting points.. taken from:
   https://dev.to/neshaz/how-to-make-your-css-consistent-across-browsers--2hff
*/

html, body, div, span, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote,  pre, abbr, address, cite, code, del, dfn, em, img, ins, kbd, q, samp, small, strong, sub, sup, var, b, i, dl, dt, dd, ol, fieldset, form, label, legend, caption, article, aside, canvas, details, figcaption, figure, footer, header, hgroup, menu, nav, section, summary, time, mark, audio, video {
     margin:0;
     padding:0;
     border:0;
     outline:0;
     font-size:100%;
     vertical-align:baseline;
     background:transparent;
 }

table, tbody, tfoot, thead, tr, th, td, ul, li {
     font-size:100%;
     vertical-align:baseline;
     background:transparent;
 }

 body {
     line-height:1;
 }

 article,aside,details,figcaption,figure, footer,header,hgroup,menu,nav,section{
     display:block;
 }

 nav ul {
    list-style:none;
 }

 blockquote, q {
    quotes:none;
 }

 blockquote:before, blockquote:after, q:before, q:after {
    content:'';
    content:none;
 }

 a {
    margin:0;
    padding:0;
    font-size:100%;
    vertical-align:baseline;
    background:transparent;
    text-decoration:none;
    transition: color 0.5s ease;
    transition: text-shadow 0.5s ease;
 }

 /* change colors to suit your needs */
 ins {
    background-color:#ff9;
    color:#000;
    text-decoration:none;
 }

 /* change colors to suit your needs */
 mark {
    background-color:#ff9;
    color:#000;
    font-style:italic;
    font-weight:bold;
 }

 del {
    text-decoration: line-through;
 }

 abbr[title], dfn[title] {
    border-bottom:1px dotted;
    cursor:help;
 }

 table {
    /*border-collapse:collapse;*/
    border-spacing:0;
 }

 /* change border color to suit your needs */
 hr {
    display:block;
    height:1px;
    border:0;
    border-top:1px solid #cccccc;
    margin:1em 0;
    padding:0;
 }

 input, select {
    vertical-align:middle;
 }


/* --------------- Styles for common HTML elements --------------- */
html { height: 100%; }

body {
    margin: 0px;
    font-family: {$style['font-family']};
    color: {$color['font_default']};
    background-color: {$color['bg']};
    vertical-align: top;
    height:auto !important;
    height:100%;
    min-height:100%;
}

.alertContainer {
    display:block;
    position:fixed;
    top:1px;
    z-index:100;
    width:100%;
    filter:alpha(opacity=50);
    opacity: 0.5;
    -moz-opacity:0.5;
    background: #000;
}

.alertBox {
    background-color: #E0F0FF;
    border: 1px solid;
    margin-top: 100px;
    padding-bottom: 10px;
    z-index: 200;
    position:fixed;
    top:1px;
    width: 60%;
    text-align: center;
    -webkit-box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.2);
    -moz-box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.2);
    box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.2);
}

.alertBox > P {
    text-align: left;
    padding-left: 20px;
    padding-right: 20px;
}

.alertBox > H1 {
    color: red;
    border-bottom: 1px solid #000000;
    margin: 0px;
    background-image: -moz-linear-gradient(top, #69A6DE, #D3DBFF);
    background-image: -webkit-gradient(linear, left top, left bottom, from(#69A6DE), to(#D3DBEE));
    background-color: #D3DBFF;
    text-align: center;
}

.alertBox > A {
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
    padding: 2px 10px 2px 10px;
    white-space: nowrap;
    text-align: center;
}

.alertBox > A:link {
    text-decoration:none;
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

a            { color: {$color['link']};  cursor: pointer; }
a:link       { color: {$color['link']};  }
a:visited    { color: {$color['vlink']}; }
a:active     { color: {$color['alink']}; }
a:hover      {
  cursor: poin#0069ba;
  text-decoration:underline;
  text-shadow: 0px 0px 1px #0090ff;
}

.content_box {
    margin: 10px 20px;
    padding: 2px 4px;
    background-color: #FFFFFF;
    vertical-align: top;
}

.ws_plugin_content {
  margin-bottom: 8px;
  float: left;
  padding-right: 4px;
  padding-left: 4px;
  text-align: left;
  resize: both;
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
    border-left:0px;
    padding-top: 8px;
    padding-bottom: 0px;
    margin:0px;
    vertical-align: middle;
}

.menubar {
    height:25px;
    background:{$color['bar_bg']};
    border: 1px solid {$color['border']};
    border-right: 0px;
    padding-top: 8px;
    padding-bottom: 0px;
    margin:0px;
    vertical-align: middle;
    float:left;
}

.main_menu_button {
    float:left;
    background-color: #AABBFF;
    border-top-right-radius: 3px;
    -moz-border-radius-topright: 3px;
    -webkit-border-top-right-radius: 3px;
    padding: 2px 8px 3px 8px;
    font-weight: bold;
    border-top: 1px solid #555555;
    border-right: 1px solid #555555;
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
    max-height: 23px;
}

.context_select_table {
    font-size: small;
    font-weight: bold;
    border-bottom: 1px solid #555555;
    border-right: 1px solid #555555;
    border-left: 1px solid #555555;
    padding: 0px 4px 0px 4px;
    -moz-border-radius-bottomright: 3px;
    -moz-border-radius-bottomleft: 3px;
    -webkit-border-bottom-right-radius: 3px;
    -webkit-border-bottom-left-radius: 3px;
    border-bottom-right-radius: 3px;
    border-bottom-left-radius: 3px;
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

.input_required {
    font-style: italic;
    text-decoration: underline;
}

.input_optional {
    font-style: normal;
    text-decoration: none;
}

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

input[disabled], textarea[disabled], option[disabled], optgroup[disabled], select[disabled] {
    -moz-user-focus:ignore;
    -moz-user-input:disabled;
    background-color:threedface;
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

td.tag {
  line-height: 24px;
  padding: 2px 4px;
  margin: 0px;
  font-size: small;
}

span.tag {
  cursor: pointer;
  color: #444444;
  background: #CDDEFA;
  padding: 2px 4px;
  margin-right: 4px;
  -moz-border-radius:4px;
  -webkit-border-radius:4px;
  border-radius:4px;
  border:1px solid #ccc;
}

span.tagdel  {
  cursor: pointer;
  font-weight: bold;
  margin-left: 3px;
  padding-left: 5px;
  border-left: 1px solid;
}
/* --------------- AJAX Boxes --------------- */

a.nav          { color: {$color['link_nav']};  }
a.nav:hover    { color: {$color['link_nav']};  }
a.act          { color: {$color['link_act']};  }
a.act:hover    { color: {$color['link_act']};  }
a.domain       { color: {$color['link_domain']}; }
a.domain:hover { color: {$color['link_domain']}; }

/* This is a new style action class that pads the images. */
a.linkact          { color: #FF8000;  text-decoration: none; }
a.linkact:hover    { color: #FF8000;  }
a.linkact img      { padding-right: 4px; }

.row-normal    { background-color: #FFFFFF; }
.row-highlight { background-color: #E8E9FD; }

.menu-title-normal    { float:left;padding: 0px 5px 0px 5px;background-color: #AABBFF; }
.menu-title-highlight { float:left;padding: 0px 5px 0px 5px;background-color: #8899FF; }

.topmenu-item {
    font-size: smaller;
    color: {$color['menu_item_text']};
    vertical-align: middle;
    margin: 0px;
    padding-left: 4px;
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
    left: 0px;
    top: 0px;
    padding: 4px;
    position: absolute;
    text-align: left;
    z-index: 20;
    -moz-border-radius: 4px;
    -webkit-border-radius: 4px;
    border-radius: 4px;
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

div.wwTT_login {
    background-color: #D3DBFF;
    color: #FFFFFF;
    font-family: "Trebuchet MS", sans-serif;
    font-size: small;
    position: absolute;
    text-align: left;
    z-index: 20;
    padding: 4px;
    padding-right: 6px;
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
    -moz-border-radius-bottomleft: 4px;
    -webkit-border-bottom-left-radius: 4px;
    -moz-border-radius-bottomright: 4px;
    -webkit-border-bottom-right-radius: 4px;
    border-left: 1px solid #000000;
    border-right: 1px solid #000000;
    border-bottom: 1px solid #000000;
    right: 10px;
    top: 1px;
    float: right;
}

/* Quick Filter Style */
div.wwTT_qf {
    background-color: #A6C3F5;
    color: #FFFFFF;
    font-family: "Trebuchet MS", sans-serif;
    left: 0px;
    top: 0px;
    position: absolute;
    text-align: left;
    z-index: 20;
    padding: 4px;
    -moz-border-radius: 4px;
    -webkit-border-radius: 4px;
    border-radius: 4px;
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
    border-radius: 4px;
    -webkit-border-radius: 4px;
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
    border-radius: 4px;
    -webkit-border-radius: 4px;
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
    white-space: nowrap;
    cursor: pointer;
    font-weight: bold;
    vertical-align: middle;
    background-color: {$color['window_tab_active_bg']};
    {$style['borderR']};
    padding: 2px 5px;
}

.table-tab-inactive {
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
    font-size: 80%;
    font-weight: bold;
}

.list-row {
    border-bottom: 1px solid {$color['border']};
    margin: 0px;
    padding: 2px 5px;
    white-space: nowrap;
    font-size: 80%;
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

/* ---- set the trace history so other imbeded junk wont change how it looks --- */
#trace_history a { color: {$color['link']};  cursor: pointer; font-family: {$style['font-family']}; }


EOL;
?>
