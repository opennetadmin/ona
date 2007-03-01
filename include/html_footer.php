<?

$year = date('Y');
print <<<EOL
<!-- BEGIN FOOTER -->
    </div>

    <!-- Bottom Text -->
    <div id="bottombox_table" class="bottomBox" style="width: 100%; text-align: center;">
        &copy;{$year} <a href="http://www.opennetadmin.com">OpenNetAdmin</a> - {$conf['version']}<br>
        We recommend <a href="http://www.mozilla.com/firefox/" target="null">Firefox</a> &gt;= 1.5, but this site also works with <a href="http://konqueror.kde.org/" target="null">Konqueror</a> &gt;= 3.5 &amp; Internet Explorer &gt;= 5.5<br>
        This site was designed, written &amp; tested by <a href="mailto:hornet136@gmail.com">Matt Pascoe</a>, <a href="mailto:deacon@thedeacon.org">Paul Kreiner</a> &amp; <a href="mailto:caspian@dotconf.net">Brandon Zehm</a>.
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