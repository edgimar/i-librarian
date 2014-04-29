<?php
include_once 'data.php';
include_once 'functions.php';

$home = $url;

include_once 'index.inc.php';
?>
<body style="margin:0px;padding:0">
<div class="ui-state-highlight ui-corner-top" style="float:left;margin:2px 4px">
    <a href="<?php print $home; ?>?id=<?php print $_GET['id']; ?>" style="display:block;">
    <span class="ui-icon ui-icon-home" style="float:left"></span>Open in I, Librarian&nbsp;
    </a>
</div>
<div style="clear:both"></div>
<?php
if (isset($_GET['id'])) {
    $_GET['file'] = $_GET['id'];
    include 'file_top.php';
}
?>
<script type="text/javascript">
filetop.init();
</script>
</body>
</html>
