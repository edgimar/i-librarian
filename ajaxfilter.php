<?php

include_once 'data.php';
include_once 'functions.php';

if ($_GET['select'] != 'library' &&
        $_GET['select'] != 'shelf' &&
        $_GET['select'] != 'project' &&
        $_GET['select'] != 'clipboard') {

    $_GET['select'] = 'library';
}

database_connect($database_path, 'library');

$in = '';

if ($_GET['select'] == 'shelf') {
    $shelf_files = array();
    $shelf_files = read_shelf($dbHandle);
    $in = join("','", $shelf_files);
    $in = "id IN ('$in')";
}

if ($_GET['select'] == 'clipboard' && !empty($_SESSION['session_clipboard'])) {
    $in = join("', '", $_SESSION['session_clipboard']);
    $in = "id IN ('$in')";
}

if ($_GET['select'] == 'clipboard' && empty($_SESSION['session_clipboard'])) {
    $in = "id IN ('')";
}

session_write_close();

empty($in) ? $and = '' : $and = 'AND';

if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    $filter_query = $dbHandle->quote("%$filter%");
} else {
    die();
}

######################################################################

if (isset($_GET['open']) && in_array("authors", $_GET['open'])) {

    $author_filter = $dbHandle->quote('%L:"$filter%');
    $result = $dbHandle->query("SELECT authors || ';' || authors_ascii FROM library WHERE $in $and (authors LIKE $filter_query OR authors_ascii LIKE $filter_query)");
    $authors = $result->fetchAll(PDO::FETCH_COLUMN);
    $dbHandle = null;

    $authors_string = '';

    $authors_string = implode(";", $authors);
    $authors = explode(";", $authors_string);

    function filter_authors($var) {
        return stripos($var, 'L:"' . $_GET['filter']) === 0;
    }

    $authors = array_filter($authors, 'filter_authors');

    if (empty($authors)) {
        print 'No such authors.';
        die();
    }

    $authors_unique = array_unique($authors);
    usort($authors_unique, "strnatcasecmp");

    while (list($key, $authors) = each($authors_unique)) {
        $authors = str_replace('L:"', '', $authors);
        $authors = str_replace(',F:"', ', ', $authors);
        $authors = str_replace('"', '', $authors);
        print PHP_EOL . '<span class="author" id="' . urlencode($authors) . '">' . htmlspecialchars($authors) . '</span><br>';
    }
}

######################################################################

if (isset($_GET['open']) && in_array("keywords", $_GET['open'])) {

    $result = $dbHandle->query("SELECT keywords FROM library WHERE $in $and keywords LIKE $filter_query");
    $keywords = $result->fetchAll(PDO::FETCH_COLUMN);
    $dbHandle = null;

    $keywords_string = '';
    $keywords_string = implode("/", $keywords);
    $keywords = explode("/", $keywords_string);

    foreach ($keywords as $value) {
        $trimmed_keywords[] = trim($value);
    }

    $trimmed_keywords = array_filter($trimmed_keywords);

    if (empty($trimmed_keywords)) {
        print 'No such keywords.';
        die();
    }

    $keywords_array = array_unique($trimmed_keywords);
    usort($keywords_array, "strnatcasecmp");

    while (list($key, $keywords) = each($keywords_array)) {
        if (!empty($keywords) && stripos($keywords, $filter) !== false) {
            print '<span class="key" id="' . htmlspecialchars(urlencode($keywords)) . '">' . htmlspecialchars($keywords) . '</span><br>';
        }
    }
}
?>