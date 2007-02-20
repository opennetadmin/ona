<?
print <<<EOL
<!-- BEGIN FOOTER -->
    </div>

    <!-- Bottom Text -->
    <div id="bottombox_table" class="bottomBox" style="width: 100%; text-align: center;">
        &copy;2006 <a href="http://opennetadmin.com">OpenNetAdmin</a> --
EOL;
print " " . $conf['version'];
print <<<EOL
<br>
        We recommend <a href="http://www.mozilla.com/firefox/" target="null">Firefox</a> &gt;= 1.5, but this site also works with <a href="http://konqueror.kde.org/" target="null">Konqueror</a> &gt;= 3.5 &amp; Internet Explorer &gt;= 5.5<br>
        This site was designed, written &amp; tested by <a href="mailto:hornet136@gmail.com">Matt Pascoe</a> &amp; <a href="mailto:caspin@dotconf.net">Brandon Zehm</a>.
    </div>

<!-- Set some preferences -->
<script type="text/javascript"><!--
    if (getcookie('pref_bg_repeat')) el('content_table').style.backgroundRepeat = getcookie('pref_bg_repeat');
    if (getcookie('pref_bg_url')) el('content_table').style.backgroundImage = 'url(\'' + getcookie('pref_bg_url') + '\')';
--></script>

</body>
</html>
EOL;

?>