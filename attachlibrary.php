<?php
die('Plan: convert this into import export of library?');

include_once 'data.php';
include_once 'functions.php';

ini_set('max_execution_time', -1);

$librarian_url = $url;

include_once 'index.inc.php';
print '<body style="padding: 4px">';

if (!empty($_GET['folder'])) {
    if (substr($_GET['folder'], -1) == DIRECTORY_SEPARATOR) $_GET['folder'] = substr($_GET['folder'], 0, -1);
}

if (!empty($_GET['folder']) && is_file($_GET['folder'].DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'library.sq3')) {

    print "Importing database.<br>";
    @ob_flush();
    flush();

    copy ($_GET['folder'].DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'library.sq3', $temp_dir.DIRECTORY_SEPARATOR.'templibrary.sq3');

    database_connect($database_path, 'library');

    $database_attach = $dbHandle->quote($temp_dir.DIRECTORY_SEPARATOR.'templibrary.sq3');

    $dbHandle->exec("ATTACH DATABASE $database_attach AS library2");

    $dbHandle->exec("CREATE TEMPORARY TABLE filenames (old_filename TEXT NOT NULL DEFAULT '', new_filename TEXT NOT NULL DEFAULT '')");

    $query = "INSERT INTO library (file, authors, title, journal, category, year, addition_date, abstract, rating, pmid, volume, pages, secondary_title, editor,
                    url, reference_type, publisher, place_published, keywords, attachments, access, doi, authors_ascii, title_ascii, abstract_ascii)
             VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :title, :journal, :category, :year, :addition_date, :abstract, :rating, :pmid, :volume, :pages, :secondary_title, :editor,
                    :url, :reference_type, :publisher, :place_published, :keywords, :attachments, :access, :doi, :authors_ascii, :title_ascii, :abstract_ascii)";

    $stmt = $dbHandle->prepare($query);
   
    $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

    $result = $dbHandle->query("SELECT * FROM library2.library");

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

        $stmt->bindValue(':authors', $row['authors'], PDO::PARAM_STR);
        $stmt->bindValue(':title', $row['title'], PDO::PARAM_STR);
        $stmt->bindValue(':journal', $row['journal'], PDO::PARAM_STR);
        $stmt->bindValue(':category', $row['category'], PDO::PARAM_STR);
        $stmt->bindValue(':year', $row['year'], PDO::PARAM_STR);
        $stmt->bindValue(':addition_date', $row['addition_date'], PDO::PARAM_STR);
        $stmt->bindValue(':abstract', $row['abstract'], PDO::PARAM_STR);
        $stmt->bindValue(':rating', $row['rating'], PDO::PARAM_INT);
        $stmt->bindValue(':pmid', $row['pmid'], PDO::PARAM_STR);
        $stmt->bindValue(':volume', $row['volume'], PDO::PARAM_STR);
        $stmt->bindValue(':pages', $row['pages'], PDO::PARAM_STR);
        $stmt->bindValue(':secondary_title', $row['secondary_title'], PDO::PARAM_STR);
        $stmt->bindValue(':editor', $row['editor'], PDO::PARAM_STR);
        $stmt->bindValue(':url', $row['url'], PDO::PARAM_STR);
        $stmt->bindValue(':reference_type', $row['reference_type'], PDO::PARAM_STR);
        $stmt->bindValue(':publisher', $row['publisher'], PDO::PARAM_STR);
        $stmt->bindValue(':place_published', $row['place_published'], PDO::PARAM_STR);
        $stmt->bindValue(':keywords', $row['keywords'], PDO::PARAM_STR);
        $stmt->bindValue(':attachments', $row['attachments'], PDO::PARAM_STR);
        $stmt->bindValue(':access', $row['access'], PDO::PARAM_STR);
        $stmt->bindValue(':doi', $row['doi'], PDO::PARAM_STR);
        $stmt->bindValue(':authors_ascii', $row['authors_ascii'], PDO::PARAM_STR);
        $stmt->bindValue(':title_ascii', $row['title_ascii'], PDO::PARAM_STR);
        $stmt->bindValue(':abstract_ascii', $row['abstract_ascii'], PDO::PARAM_STR);

        $stmt->execute();
        
        $dbHandle->exec("INSERT INTO filenames VALUES ('$row[file]',(SELECT file FROM library WHERE id=(SELECT max(id) FROM library)))");
    }

    $result = null;
    $row = null;

    $dbHandle->exec("COMMIT TRANSACTION");

    $dbHandle->exec("DETACH DATABASE '$database_attach'");

    print "Done.<br>Copying PDF files.<br>";
    @ob_flush();
    flush();

    $result = $dbHandle->query("SELECT old_filename,new_filename FROM filenames");

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $old_filename = $row['old_filename'];
        $new_filename = $row['new_filename'];
        if(is_file($_GET['folder'].DIRECTORY_SEPARATOR.$old_filename)) {
            copy ($_GET['folder'].DIRECTORY_SEPARATOR.$old_filename, dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$new_filename);
        }
    }

    $result = null;
    $row = null;
    $dbHandle = null;

    print 'Done.<br>Go to <a href="'.$librarian_url.'">library</a>.';
    @ob_flush();
    flush();


} elseif (!empty($_GET['folder'])) {
    die("Error! No database found.");
} elseif (!isset($_GET['folder'])) {
?>
    Path to the library folder:
    <form action="attachlibrary.php" method="GET">
        <input type="text" name="folder" value="" size="30">
        <input type="submit" value="Attach">
    </form>
<?php
}
?>
</body>
</html>