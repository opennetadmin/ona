
// Open the keyboard shortcuts
Mousetrap.bind("?", function() { toggle_window('app_keyboard_shortcuts'); return false; });

// Go to quick search
Mousetrap.bind("g s", function() { el('qsearch').focus(); return false; });
Mousetrap.bind("g c", function() { toggleBox('ipcalc_content'); el('calc_ip').focus(); return false; });
Mousetrap.bind("g h", function() { removeElement('work_space'); return false; });
Mousetrap.bind("g l", function() { el('login_userid').click(); return false; });

// Open editor for adding new things
Mousetrap.bind("a h", function() { xajax_window_submit('edit_host', 'js=> ', 'editor'); return false; });
Mousetrap.bind("a i", function() { xajax_window_submit('edit_interface', 'js=> ', 'editor'); return false; });
Mousetrap.bind("a s", function() { xajax_window_submit('edit_subnet', 'js=> ', 'editor'); return false; });
Mousetrap.bind("a d", function() { xajax_window_submit('edit_record', 'blank=>nope', 'editor'); return false; });
Mousetrap.bind("a D", function() { xajax_window_submit('edit_domain', 'js=> ', 'editor'); return false; });
Mousetrap.bind("a v", function() { xajax_window_submit('edit_vlan', 'js=> ', 'editor'); return false; });
Mousetrap.bind("a V", function() { xajax_window_submit('edit_vlan_campus', 'js=> ', 'editor'); return false; });
Mousetrap.bind("a b", function() { xajax_window_submit('edit_block', 'js=> ', 'editor'); return false; });
Mousetrap.bind("a l", function() { xajax_window_submit('edit_location', 'js=> ', 'editor'); return false; });

// need to adjust custom attribut to have a selctor on the type for this to work
//Mousetrap.bind("a c", function() { xajax_window_submit('edit_custom_attribute', ' ', 'editor'); return false; });

// Advanced search tab access
Mousetrap.bind("s h", function() { xajax_window_submit('search_results', 'search_form_id=>host_search_form,one_go=>n'); return false; });
Mousetrap.bind("s s", function() { xajax_window_submit('search_results', 'search_form_id=>subnet_search_form,one_go=>n'); return false; });
Mousetrap.bind("s v", function() { xajax_window_submit('search_results', 'search_form_id=>vlan_campus_search_form,one_go=>n'); return false; });
Mousetrap.bind("s b", function() { xajax_window_submit('search_results', 'search_form_id=>block_search_form,one_go=>n'); return false; });
Mousetrap.bind("s d", function() { xajax_window_submit('search_results', 'search_form_id=>dns_record_search_form,one_go=>n'); return false; });
