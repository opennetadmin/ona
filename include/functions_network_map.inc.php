<?
// This is to be included in files that need a subnet block map
// Brandon Zehm


// Returns javascript to set the portal
function get_portal_js($window_name, $ip) {
    $js .= <<<EOL

    /*** Setup the Portal ***/
    var _el = el('{$window_name}_portal');

    /* Finally reposition/resize the window and hide any overflow */
    _el.style.border   = '2px solid #000000';
    _el.style.overflow = 'hidden';

    /* Portal Mouse Down Handler */
    _el.onmousedown =
        function(ev) {
            if (typeof(ev) == 'undefined') ev = event;
            document.body.style.cursor = 'move';
            dragStart(ev, '{$window_name}_substrate',
                      'savePosition', 0,
                      'drag', 'vertical',
                      'detectEdge', 0,
                      'opacity', 1
            );

            /* Capture mouseup events .. when the document gets an onmouseup, call the myonmouseup function */
            if (browser.isNS || browser.isKONQ) {
                document.addEventListener("mouseup", el('{$window_name}_portal').myonmouseup, true);
                ev.preventDefault();
            }
            else if (browser.isIE) {
                document.attachEvent("onmouseup", el('{$window_name}_portal').myonmouseup);
                window.event.cancelBubble = true;
                window.event.returnValue = false;
            }

            return false;
        };


    /* Portal Mouse Up Handler */
    _el.myonmouseup =
        function(ev) {
            if (typeof(ev) == 'undefined') ev = event;
            document.body.style.cursor = 'default';

            /* Stop capturing mouseup events */
            if (browser.isNS || browser.isKONQ)
                document.removeEventListener("mouseup", el('{$window_name}_portal').myonmouseup, true);
            else if (browser.isIE)
                document.detachEvent("onmouseup", el('{$window_name}_portal').myonmouseup);

            /* Global variable (bad!) to store which blocks have been requested but not built yet */
            if (typeof(_map_requests_) == 'undefined') _map_requests_ = new Object;

            /* Determine which IP blocks should be currently displayed */

            /* Find some variables */
            var base_ip = {$ip};
            var portal_height = parseInt(el('{$window_name}_portal').offsetHeight);
            var substrate_offset = parseInt(el('{$window_name}_substrate').style.top);
            var row_padding = 4;
            var row_height = 0;
            var row_zoom = 0;
            var row_type = '';
            var ips_per_row = 0;
            var zoom = el('{$window_name}_zoom').value;
            switch (zoom) {
                case '7': row_type = 'class_c'; row_height =  8; row_zoom = 1; ips_per_row = 256; break;
                case '8': row_type = 'class_c'; row_height = 16; row_zoom = 2; ips_per_row = 256; break;
                case '9': row_type = 'class_c'; row_height = 24; row_zoom = 3; ips_per_row = 256; break;

                default : alert("Zoom level " + zoom + " doesn't work yet.");
            }

            var substrate_distance = parseInt(substrate_offset / (row_height + row_padding));
            var total_blocks = parseInt( portal_height / (row_height + row_padding) );
            var first_block = base_ip + ((-1 * (substrate_distance)) * ips_per_row);


            /** Build an array of block numbers to fetch **/
            /** Basically we fill the visible screen, fill a page below, then a page above. **/
            var blocks = Array();
            for (var block=0; block<(total_blocks*2); block++) blocks.push(block);
            for (var block=-1; block>=(-1 * total_blocks); block--) blocks.push(block);


            /** Request blocks that we don't have and aren't currently in the request queue **/
            for (var x=0; x<blocks.length; x++) {
                var block = blocks[x];
                var next_block = first_block + (block * ips_per_row);
                var _block = el(next_block + '_row');
                if (!_map_requests_[next_block + '_row'] && !_block) {
                    _map_requests_[next_block + '_row'] = 1;
                    var block_offset = parseInt( -1 * ((base_ip - next_block) / ips_per_row) * (row_height + row_padding) );
                    xajax_window_submit(
                        '{$window_name}',
                        'ip => ' + next_block + ',' +
                        'row_offset => ' + block_offset + ',' +
                        'row_type => ' + row_type + ',' +
                        'row_height => ' + row_height + ',' +
                        'row_zoom => ' + row_zoom,
                        'draw_block'
                    );
                }
            }


            /** Remove blocks that are too far off the screen **/
            /**
                NOTES:
                The variable 'nodes' below is NOT a static variable!
                Behaving just like javascript, it points to the current list
                of child nodes.  So if I don't make a list of nodes to remove,
                and then remove them later, I risk skiping the check for some
                nodes since the array shrinks each time I remove one!
            **/
            var cache_boundary_top    = first_block - (total_blocks * ips_per_row);
            var cache_boundary_bottom = first_block + (total_blocks * 2 * ips_per_row);
            nodes = el('{$window_name}_substrate').childNodes;
            var toremove = Array();
            for (var counter=0; counter < nodes.length; counter++) {
                var ip = parseInt(nodes[counter].id);
                if (ip < cache_boundary_top || ip > cache_boundary_bottom)
                    toremove.push(nodes[counter]);
            }
            /* Remove the elements we decided to remove */
            for (var counter=0; counter < toremove.length; counter++)
                toremove[counter].parentNode.removeChild(toremove[counter]);

            return false;
        };

    /*** Setup the Substrate ***/
    var _el = el('{$window_name}_substrate');
    _el.style.position = 'absolute';
    _el.style.top  = '0px';
    _el.style.left = '0px';


EOL;
    return($js);
}










//////////////////////////////////////////////////////////////////////////////
// Function: ws_draw_block()
//
// Description:
//   Redraws the contents of the portal div given proper input
//   We get:
//    * What the base IP address is
//    * What the row offset is
//    * What the row type is
//    * What the row height is
//    * What the zoom level is
//
// Zoom details: (only class c for now!)
//   1-3 :: Class A views
//   4-6 :: Class B views
//   7-9 :: Class C views
//////////////////////////////////////////////////////////////////////////////
function ws_draw_block($window_name, $form='') {
    global $conf, $self, $ona;
    global $images, $color, $style;
    $html = '';
    $js = '';

    // Instantiate the xajaxResponse object
    $response = new xajaxResponse();

    // If the user supplied an array in a string, build the array and store it in $form
    $form = parse_options_string($form);

    // Find out if $ip is valid
    $ip = ip_mangle($form['ip'], 'numeric');
    if ($ip == -1) {
        $js .= "alert('Invalid IP address!');";
        $response->addScript($js);
        return($response->getXML());
    }

    // Build a few variables
    $function   = "get_{$form['row_type']}_html";
    $row_zoom   = $form['row_zoom'];
    $row_height = $form['row_height'];
    $row_offset = $form['row_offset'];
    // This is the number of pixels to leave for the label on the left (.45 * row height, * 15 possible characters)
    $label_width = round($form['row_height'] * 0.45 * 15);
    $font_height = $form['row_height'] - 2;  // Label font-size
    if ($font_height < 8) $font_height = 8;
    $row_left = $label_width + 10;           // Left offset for the block row


    // Get some html (the html that goes inside the class c div)
    $html = $function($ip, $row_zoom, $row_height);
    $el_name = $ip . '_row';

    // Add a row label
    $label = ip_mangle($ip, 'dotted');
    $response->addCreate("{$window_name}_substrate", "div", $el_name . '_label');
    $response->addScript(<<<EOL
        var _el = el('{$el_name}_label');
        _el.style.visibility = 'hidden';
        _el.style.position = 'absolute';
        _el.style.textAlign = 'right';
        _el.style.color = '#000000';
        _el.style.fontSize = '{$font_height}px';
        _el.style.top  = '{$row_offset}px';
        _el.style.left = '2px';
        _el.style.width = '{$label_width}px';
        _el.style.overflow = 'visible';
        _el.style.whiteSpace = 'nowrap';
EOL
    );

    // Add the row
    $response->addCreate("{$window_name}_substrate", "div", $el_name);
    $response->addScript(<<<EOL
        var _el = el('{$el_name}');
        _el.style.visibility = 'hidden';
        _el.style.position = 'absolute';
        _el.style.top  = '{$row_offset}px';
        _el.style.left = '{$row_left}px';
        _el.style.borderTop    = '1px solid #000000';
        _el.style.borderBottom = '1px solid #000000';
        _el.style.borderLeft   = '1px solid #000000';
        if (browser.isIE)
            _el.style.fontSize = ({$font_height} - 2) + 'px';
EOL
    );

    // Fill the label and row
    $response->addAssign($el_name, "innerHTML", $html);
    $response->addAssign($el_name . '_label', "innerHTML", $label);

    // Javascript to make sure the container (row) div is the exact length to hold it's contents in one row
    $response->addScript(<<<EOL
        var nodes = _el.childNodes;
        var width = 0;
        for (var counter=0; counter < nodes.length; counter++)
            if (nodes[counter].nodeType == 1)
                width += parseInt(nodes[counter].offsetWidth);
        if (browser.isIE) width += 1; /* for the left border */
        _el.style.width = width + 'px';
EOL
    );

    // Display the label and block
    $response->addScript(<<<EOL
        el('{$el_name}_label').style.visibility = 'visible';
        el('{$el_name}').style.visibility = 'visible';
        /* Tell the browser we've sent it this block so it knows it can re-request it if it needs to */
        _map_requests_['{$el_name}'] = undefined;
EOL
    );
    if ($js) { $response->addScript($js); }

    return($response->getXML());
}








// This function needs the folowing colors defined in the global variable $color
//   $color['bgcolor_map_empty']
//   $color['bgcolor_map_subnet']
function get_class_c_html($ip=0, $zoom=2, $row_height) {
    global $conf, $self, $onadb, $color, $style, $images;
    $html = '';
    if ($ip == 0) { return($html); }
    $ip_end = $ip + 255;

    $x_px_per_ip = $zoom;

    // Select all subnet records in this class C
    //$where = "ip_addr >= {$ip} AND ip_addr <= " . $ona->qstr($ip_end);
    $where = "ip_addr >= {$ip} AND ip_addr <= {$ip_end}";
    list ($status, $num_subnets, $subnets) =
        db_get_records(
            $onadb,
            'subnets',
            $where,
            "ip_addr ASC"
        );

    // If the first record isn't a subnet, see if the first IP is in another subnet
    if ($subnets[0]['ip_addr'] != $ip) {
        $where = "ip_addr < {$ip} AND ((4294967295 - ip_mask) + ip_addr) >= {$ip}";
        list ($status, $rows, $subnet) = db_get_record($onadb, 'subnets', $where);
        if ($rows) {
            $num_subnets++;
            array_unshift($subnets, $subnet);
        }
    }

    $block_start = $ip;

    // Find the next block of addresses
    while ($block_start < $ip_end) {
        if ( (!is_array($subnet)) or ($block_start > $subnet['ip_addr']) ) {
            $subnet =  array_shift($subnets);
            if (is_array($subnet)) {
                $subnet['SIZE'] = (0xffffffff - $subnet['ip_mask']) + 1;
                $subnet['ip_addr_end'] = $subnet['ip_addr'] + $subnet['SIZE'] - 1;
            }
            else {
                // pretend like the next subnet record is the next class C
                $subnet['SIZE'] = $ip_end - $block_start + 1;
                $subnet['ip_addr'] = $ip_end + 1;
            }
        }

        // If it's unallocated space
        if ($block_start < $subnet['ip_addr']) {
            $block_end = $subnet['ip_addr'] - 1;
            $block_color = $color['bgcolor_map_empty'];
        }
        // If it's allocated space
        else {
            $block_end = $subnet['ip_addr_end'];
            if ($block_end > $ip_end) { $block_end = $ip_end; }
            $block_color = $color['bgcolor_map_subnet'];
        }
        $block_size = ($block_end - $block_start + 1);
        $block_size_total += $block_size;
        // $block_title = htmlentities($subnet['DESCRIPTION'] . " :: Size={$block_size}", ENT_QUOTES) . ' :: ' . ip_mangle($block_start, 'dotted') . " -> " . ip_mangle($block_end, 'dotted');

        // Display the current block (-1 for px border unless it's IE)
        $x = ($block_size * $x_px_per_ip) - 1;
        if (strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') != false) { $x++; }
            $html .= <<<EOL
<div id="{$block_start}_block"
     style="
       clear: none; float: left;
       border-right: 1px solid #000000; background-color: {$block_color};
       width: {$x}px; height: {$row_height}px;"
     onMouseOver="
       wwTT(this, event,
         'id', 'tt_subnet_{$block_start}',
         'type', 'velcro',
         'styleClass', 'wwTT_niceTitle',
         'direction', 'south',
         'javascript', 'xajax_window_submit(\'tooltips\', \'tooltip=>subnet,id=>tt_subnet_{$block_start},subnet_ip=>{$block_start}\');'
       );"
></div>
EOL;
        $block_start = $block_end + 1;
    }



    return($html);
}







?>