<?php
include_once 'data.php';
include_once 'functions.php';

if (isset($_GET['file'])) {
    database_connect($database_path, 'library');
    $id_query = $dbHandle->quote($_GET['file']);
    $result = $dbHandle->query("SELECT file FROM library WHERE id=$id_query LIMIT 1");
    $paper = $result->fetch(PDO::FETCH_ASSOC);
    $result = null;
}

$dbHandle = null;

if (is_file("library/$paper[file]") && isset($_SESSION['auth'])) {

    if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external'))
        print PHP_EOL . "<table id=\"pdf-table\" cellspacing=0 style=\"width:100%;height:100%;border-top:1px solid #c6c8cc;border-bottom:1px solid #c6c8cc\"><tr><td>
			<iframe name=\"pdf\" class=\"pdf-file\" src=\"" . htmlspecialchars('downloadpdf.php?file=' . $paper['file'] . '#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0') . "\"
				frameborder=\"0\" class=\"noprint\" style=\"width:100%;height:100%\"></iframe></td></tr></table>";

    if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal')
        print PHP_EOL . '<table id="pdf-table" class="noprint" cellspacing=0 style="width:100%;height:100%"><tr><td>
			<iframe name="pdf" class="pdf-file" src="' . htmlspecialchars('viewpdf.php?file=' . $paper['file']) . '&navpanes=0"
				frameborder="0" style="width:100%;height:100%"></iframe></td></tr></table>';

} elseif (isset($_SESSION['auth'])) {

    print '<div style="margin-top:25%;margin-left:43%;color:#b6b8bc;font-size:28px;width:200px"><b>No PDF</b></div>';
}
?>
