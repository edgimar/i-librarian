<?php
die('<p style="padding:10px">JSTOR API non-functional as of May 2012.</p>');
include_once 'data.php';

if (isset($_SESSION['auth'])) {

    include_once 'functions.php';

    $proxy_name = '';
    $proxy_port = '';
    $proxy_username = '';
    $proxy_password = '';

    if (isset($_SESSION['connection']) && ($_SESSION['connection'] == "autodetect" || $_SESSION['connection'] == "url")) {
        if (!empty($_GET['proxystr'])) {
            $proxy_arr = explode(';', $_GET['proxystr']);
            foreach ($proxy_arr as $proxy_str) {
                if (stripos(trim($proxy_str), 'PROXY') === 0) {
                    $proxy_str = trim(substr($proxy_str, 6));
                    $proxy_name = parse_url($proxy_str, PHP_URL_HOST);
                    $proxy_port = parse_url($proxy_str, PHP_URL_PORT);
                    $proxy_username = parse_url($proxy_str, PHP_URL_USER);
                    $proxy_password = parse_url($proxy_str, PHP_URL_PASS);
                    break;
                }
            }
        }
    } else {
        if (isset($_SESSION['proxy_name']))
            $proxy_name = $_SESSION['proxy_name'];
        if (isset($_SESSION['proxy_port']))
            $proxy_port = $_SESSION['proxy_port'];
        if (isset($_SESSION['proxy_username']))
            $proxy_username = $_SESSION['proxy_username'];
        if (isset($_SESSION['proxy_password']))
            $proxy_password = $_SESSION['proxy_password'];
    }

########## register empty checkboxes ##############

    for ($i = 1; $i < 51; $i++) {

        if (isset($_GET['jstor_last_search']) && !isset($_GET['jstor_category_' . $i]))
            $_GET['jstor_category_' . $i] = '';
    }

    for ($i = 1; $i < 6; $i++) {

        if (isset($_GET['jstor_last_search']) && !isset($_GET['jstor_type_' . $i]))
            $_GET['jstor_type_' . $i] = '';
    }

########## reset button ##############

    if (isset($_GET['newsearch'])) {

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_jstor'))
                unset($_SESSION[$key]);
        }
    }

########## save button ##############

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['jstor_searchname'])) {

        database_connect($database_path, 'library');

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $stmt2 = $dbHandle->prepare("INSERT INTO searches (userID,searchname,searchfield,searchvalue) VALUES (:user,:searchname,:searchfield,:searchvalue)");

        $stmt2->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt2->bindParam(':searchname', $searchname, PDO::PARAM_STR);
        $stmt2->bindParam(':searchfield', $searchfield, PDO::PARAM_STR);
        $stmt2->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

        $dbHandle->beginTransaction();

        $user = $_SESSION['user_id'];
        $searchname = "jstor#" . $_GET['jstor_searchname'];

        $stmt->execute();

        reset($_GET);

        while (list($key, $value) = each($_GET)) {

            if (!empty($key) && strstr($key, "jstor_") && $key != 'jstor_last_search') {

                $user = $_SESSION['user_id'];
                $searchname = "jstor#" . $_GET['jstor_searchname'];

                if ($key != "jstor_searchname") {

                    $searchfield = $key;
                    $searchvalue = $value;
                    $stmt2->execute();
                }
            }
        }

        $user = $_SESSION['user_id'];
        $searchname = "jstor#" . $_GET['jstor_searchname'];
        $searchfield = 'jstor_last_search';
        $searchvalue = '1';

        $stmt2->execute();

        $dbHandle->commit();
    }

########## load button ##############

    if (isset($_GET['load']) && $_GET['load'] == '1' && !empty($_GET['saved_search'])) {

        database_connect($database_path, 'library');

        $stmt = $dbHandle->prepare("SELECT searchfield,searchvalue FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "jstor#" . $_GET['saved_search'];

        $stmt->execute();

        reset($_SESSION);

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_jstor'))
                unset($_SESSION[$key]);
        }


        $_GET = array();
        $_GET['load'] = 'Load';

        $_GET['jstor_searchname'] = substr($searchname, 6);

        while ($search = $stmt->fetch(PDO::FETCH_BOTH)) {
            $_GET{$search['searchfield']} = $search['searchvalue'];
        }
    }

########## delete button ##############

    if (isset($_GET['delete']) && $_GET['delete'] == '1' && !empty($_GET['saved_search'])) {

        database_connect($database_path, 'library');

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "jstor#" . $_GET['saved_search'];

        $stmt->execute();

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_jstor'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
    }

########## main body ##############

    $microtime1 = microtime(true);

    reset($_GET);

    while (list($key, $value) = each($_GET)) {

        $_SESSION['session_download_' . $key] = $value;
    }

    if (isset($_GET['jstor_searchname']))
        $_SESSION['session_download_jstor_searchname'] = $_GET['jstor_searchname'];

########## register variables ##############

    $parameter_string = '';

    if (!isset($_GET['from'])) {
        $from = '1';
    } else {
        $from = intval($_GET['from']);
    }

    $j = $from;

########## prepare jstor query ##############
### article type
    $type_array = array();
    $type_string = '';

    $url_array = array();

    reset($_GET);

    while (list($key, $value) = each($_GET)) {

        if ($key != 'from')
            $url_array[] = "$key=" . urlencode($value);

        if (strstr($key, "jstor_type_")) {

            if (!empty($_GET[$key]))
                $type_array[] = "jstor.articletype = \"$_GET[$key]\"";
        }
    }

    $url_string = $url_string = join("&", $url_array);

    if (!empty($type_array)) {

        $type_string = join(" OR ", $type_array);
        $type_string = urlencode(" AND ($type_string)");
    }

### discipline
    $category_array = array();
    $category_string = '';

    reset($_GET);

    while (list($key, $value) = each($_GET)) {

        if (strstr($key, "jstor_category_")) {

            if (!empty($_GET[$key]))
                $category_array[] = "jstor.discipline = \"$_GET[$key]\"";
        }
    }

    if (!empty($category_array)) {

        $category_string = join(" OR ", $category_array);
        $category_string = urlencode(" AND ($category_string)");
    }

### language
    $language_string = null;
    if (isset($_GET['jstor_lan']) && !empty($_GET['jstor_lan']))
        $language_string = urlencode(" AND dc.language = \"$_GET[jstor_lan]\"");

### main query
    $query_array = array();

    $k = 1;

    for ($i = 1; $i < 8; $i++) {

        if (!empty($_GET['jstor_query' . $i])) {

            $query_array[] = (($k > 1) ? ' ' . $_GET['jstor_operator' . $i] . ' ' : '') . $_GET['jstor_selection' . $i] . ' = "' . $_GET['jstor_query' . $i] . '"';
            $k = $k + 1;
        }
    }

    $query_array = array_filter($query_array);
    $query_string = join('', $query_array);
    $query_string = urlencode("($query_string)");

### sorting
    $sorting_string = null;
    if (isset($_GET['jstor_sort']) && !empty($_GET['jstor_sort']))
        $sorting_string = urlencode(" sortBy $_GET[jstor_sort]");

########## search jstor ##############

    if (!empty($query_array) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

        ############# caching ################

        $cache_name = cache_name();
        $cache_name .= '_download';
        $db_change = database_change(array(
            'library'
        ));
        cache_start($db_change);
        
        ########## register the time of search ##############

        if (!empty($_SESSION['session_download_jstor_searchname']) && $from == 1) {

            database_connect($database_path, 'library');

            $stmt = $dbHandle->prepare("UPDATE searches SET searchvalue=:searchvalue WHERE userID=:user AND searchname=:searchname AND searchfield='jstor_last_search'");

            $stmt->bindParam(':user', $user, PDO::PARAM_STR);
            $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
            $stmt->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

            $user = $_SESSION['user_id'];
            $searchname = "jstor#" . $_SESSION['session_download_jstor_searchname'];
            $searchvalue = time();

            $stmt->execute();
        }

        ########## search jstor ##############

        $request_url = "http://dfr.jstor.org/sru/?operation=searchRetrieve&query=" . $query_string . $language_string . $category_string . $type_string . $sorting_string . "&startRecord=$from&maximumRecords=10&version=1.1&recordSchema=info:srw/schema/srw_jstor";

        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
        if (empty($xml))
            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
    }

########## display search result summaries ##############

    if (!empty($xml)) {

        print '<div style="padding:2px;font-weight:bold">JSTOR search';

        if (!empty($_SESSION['session_download_jstor_searchname']))
            print ': ' . htmlspecialchars($_SESSION['session_download_jstor_searchname']);

        print '</div>';

        while ($xml->read()) {
            if ($xml->name == 'srw:numberOfRecords' && $xml->nodeType == XMLReader::ELEMENT) {
                $xml->read();
                $count = $xml->value;
                break;
            }
        }

        if (!empty($count) && $count > 0) {

            $maxfrom = $from + 9;
            if ($maxfrom > $count)
                $maxfrom = $count;

            $microtime2 = microtime(true);
            $microtime = $microtime2 - $microtime1;
            $microtime = sprintf("%01.1f seconds", $microtime);

            print '<table cellspacing="0" class="top"><tr><td class="top" style="width: 20%">';

            print '<div class="ui-state-highlight ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:28px">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_jstor.php?' . $url_string . '&from=1') . '">') .
                    '<span class="ui-icon ui-icon-triangle-1-w" style="float:right;width:16px"></span>
			<span class="ui-icon ui-icon-triangle-1-w" style="float:left;width:10px;overflow:hidden"></span>'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:5.1em">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_jstor.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                    '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="text-align: center">';

            print "Items $from - $maxfrom of $count in $microtime.";

            print '</td><td class="top" style="width: 20%">';

            (($count % 10) == 0) ? $lastpage = 1 + $count - 10 : $lastpage = 1 + $count - ($count % 10);

            print '<div class="ui-state-highlight ui-corner-top' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:29px">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars('download_jstor.php?' . $url_string . '&from=' . $lastpage) . '">' : '') .
                    '<span class="ui-icon ui-icon-triangle-1-e" style="float:right;width:16px"></span>
			   <span class="ui-icon ui-icon-triangle-1-e" style="float:left;width:11px;overflow:hidden"></span>'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="width:4.6em;float:right;margin-right:2px">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_jstor.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                    '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;Next'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-top pgdown" style="float: right;width: 5em;margin-right:2px">PgDown</div>';

            print '</td></tr></table>';

            print '<div class="alternating_row">';

            $i = 1;

            while ($xml->read()) {

                $id = '';
                $title = '';
                $journal = '';
                $pub_date = '';
                $abstract = '';
                $volume = '';
                $issue = '';
                $pages = '';
                $reference_type = 'article';
                $publisher = '';
                $creator = '';
                $issn = '';

                if ($xml->name == 'jstor:id' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $id = (string) $xml->value;
                    $records[$i]['id'] = $id;
                    $records[$i]['url'] = 'http://www.jstor.org/stable/' . $id;
                }
                if ($xml->name == 'jstor:journaltitle' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $journal = (string) $xml->value;
                    $records[$i]['journal'] = $journal;
                }
                if ($xml->name == 'jstor:volume' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $volume = (string) $xml->value;
                    $records[$i]['volume'] = $volume;
                }
                if ($xml->name == 'jstor:issue' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $issue = (string) $xml->value;
                    $records[$i]['issue'] = $issue;
                }
                if ($xml->name == 'jstor:pagerange' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $pages = (string) $xml->value;
                    $records[$i]['pages'] = $pages;
                }
                if ($xml->name == 'jstor:year' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $pub_date = (string) $xml->value;
                    if (strpos($pub_date, " YEAR: ") !== false) {
                        $date_match = array();
                        preg_match('/\d{4}/', $pub_date, $date_match);
                        $records[$i]['pub_date'] = $date_match[0];
                    }
                }
                if ($xml->name == 'jstor:title' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $title = (string) $xml->value;
                    $records[$i]['title'] = $title;
                }
                if ($xml->name == 'jstor:abstract' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $abstract = (string) $xml->value;
                    $records[$i]['abstract'] = $abstract;
                }
                if ($xml->name == 'jstor:publisher' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $publisher = (string) $xml->value;
                    $records[$i]['publisher'] = $publisher;
                }
                if ($xml->name == 'jstor:issn' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $issn = (string) $xml->value;
                    $records[$i]['issn'] = substr_replace($issn, '-', 4, 0);
                }
                if ($xml->name == 'jstor:author' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $creator = (string) $xml->value;
                    $author[] = str_replace(',', '', $creator);
                }
                if ($xml->name == 'jstor:resourcetype' && $xml->nodeType == XMLReader::ELEMENT) {
                    $xml->read();
                    $reference_type = (string) $xml->value;
                    $records[$i]['reference_type'] = $reference_type;
                    $authors = implode(', ', $author);
                    $records[$i]['authors'] = $authors;
                    $author = array();
                    $i = $i + 1;
                    $records[$i]['id'] = '';
                    $records[$i]['url'] = '';
                    $records[$i]['title'] = '';
                    $records[$i]['journal'] = '';
                    $records[$i]['pub_date'] = '';
                    $records[$i]['abstract'] = '';
                    $records[$i]['authors'] = '';
                    $records[$i]['volume'] = '';
                    $records[$i]['issue'] = '';
                    $records[$i]['pages'] = '';
                    $records[$i]['reference_type'] = '';
                    $records[$i]['publisher'] = '';
                    $records[$i]['issn'] = '';
                }
            }

            $xml->close();

            database_connect($database_path, 'library');
            $jdbHandle = new PDO('sqlite:journals.sq3');

            foreach ($records as $record) {

                $id = $record['id'];
                $title = strip_tags($record['title']);
                $secondary_title = $record['journal'];
                $year = $record['pub_date'] . '-01-01';
                if (isset($record['abstract']))
                    $abstract = strip_tags($record['abstract']);
                $names = $record['authors'];
                $volume = $record['volume'];
                $issue = $record['issue'];
                $pages = $record['pages'];
                $url = $record['url'];
                $reference_type = $record['reference_type'];
                $publisher = $record['publisher'];
                $issn = $record['issn'];

                if (!empty($id) && !empty($title)) {

                    //JOURNAL RATING
                    if (!empty($issn)) {
                        $issn_query = $jdbHandle->quote($issn);
                        $result = $jdbHandle->query("SELECT rating FROM journals WHERE issn=$issn_query LIMIT 1");
                        $rating = $result->fetchColumn();
                        if (empty($rating))
                            $rating = 0;
                        $result = null;
                    }

                    ########## gray out existing records ##############

                    $title_query = $dbHandle->quote(substr($title, 0, -1) . "%");
                    $result_query = $dbHandle->query("SELECT id FROM library WHERE title LIKE $title_query AND length(title) <= length($title_query)+2 LIMIT 1");
                    $existing_id = $result_query->fetchColumn();

                    print '<div class="items" data-uid="' . htmlspecialchars($id) . '">';

                    print '<div class="titles" style="margin-right:30px';

                    if ($existing_id['count(*)'] > 0)
                        print ';color: #999';

                    print '">' . $title . '</div>';

                    print '<table class="firstcontainer" style="width:100%"><tr><td class="items">';

                    print '<div style="float:left">';

                    print '<div class="star ' . (($rating >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"><span class="ui-icon ui-icon-star"></span></div>';
                    print '<div class="star ' . (($rating >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"><span class="ui-icon ui-icon-star"></span></div>';
                    print '<div class="star ' . (($rating == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" style="cursor:auto"><span class="ui-icon ui-icon-star"></span></div>';

                    print '</div>&nbsp;<b>&middot;</b> ';

                    print htmlspecialchars($secondary_title);

                    if ($year != '')
                        print " ($year)";

                    print '<div style="clear:both"></div>';

                    if (!empty($names))
                        print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>' . htmlspecialchars($names) . '</div></div>';

                    print '<a href="' . htmlspecialchars('http://www.jstor.org/stable/' . $id) . '" target="_blank">JSTOR</a>';

                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                    print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://www.jstor.org/stable/pdfplus/" . $id . ".pdf?acceptTC=true") . '" target="_blank">PDF</a>*';

                    print '<td></tr></table>';

                    print '<div class="abstract_container" style="display:none">';

                    ##########	print results into table	##########

                    print '<form enctype="application/x-www-form-urlencoded" action="upload.php" method="POST" class="fetch-form">';

                    print '<table cellspacing="0" width="100%"><tr><td class="items">';

                    print '<div>';
                    if (!empty($secondary_title))
                        print htmlspecialchars($secondary_title);
                    if (!empty($year))
                        print " (" . htmlspecialchars($year) . ")";
                    if (!empty($volume))
                        print " " . htmlspecialchars($volume);
                    if (!empty($issue))
                        print " ($issue)";
                    if (!empty($pages))
                        print ": " . htmlspecialchars($pages);
                    print '</div>';

                    if (!empty($names))
                        print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>' . htmlspecialchars($names) . '</div></div>';

                    print '</td></tr>';

                    print '<tr><td><div class="abstract">';

                    !empty($abstract) ? print htmlspecialchars($abstract)  : print 'No abstract available.';

                    print '</div></td></tr><tr><td class="items">';
                    ?>

                    <input type="hidden" name="uid[]" value="<?php if (!empty($id)) print htmlspecialchars('JSTOR:' . $id); ?>">
                    <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
                    <input type="hidden" name="url[]" value="<?php if (!empty($url)) print htmlspecialchars($url); ?>">
                    <input type="hidden" name="reference_type" value="<?php if (!empty($reference_type)) print htmlspecialchars($reference_type); ?>">
                    <input type="hidden" name="authors" value="<?php if (!empty($names)) print htmlspecialchars($names); ?>">
                    <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
                    <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
                    <input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($year); ?>">
                    <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
                    <input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
                    <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
                    <input type="hidden" name="keywords" value="<?php if (!empty($keywords)) print htmlspecialchars($keywords); ?>">
                    <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">
                    <input type="hidden" name="publisher" value="<?php if (!empty($publisher)) print htmlspecialchars($publisher); ?>">
                    <input type="hidden" name="form_new_file_link" value="<?php print !empty($id) ? htmlspecialchars("http://www.jstor.org/stable/pdfplus/" . $id . ".pdf?acceptTC=true") : ""; ?>">

                    <?php
                    ##########	print full text links	##########

                    print '<b>Full text options:</b><br>';

                    print '<a href="' . htmlspecialchars('http://www.jstor.org/stable/' . $id) . '" target="_blank">JSTOR</a>';
                    if (!empty($doi))
                        print ' <b>&middot;</b> <a href="' . htmlspecialchars('http://dx.doi.org/' . urlencode($doi)) . '" target="_blank">Publisher Website</a>';
                    print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://www.jstor.org/stable/pdfplus/" . $id . ".pdf?acceptTC=true") . '" target="_blank">PDF</a>*';

                    print '<br><button class="save-item">Save</button> <button class="quick-save-item">Quick Save</button>';

                    print '</td></tr></table></form>';

                    print '</div>';

                    print '<div class="save_container"></div>';

                    print '</div>';

                    if ($j < $from + 10 && $j < $maxfrom)
                        print '<div class="separator"></div>';

                    $j = $j + 1;
                }
            }

            $dbHandle = null;
            $jdbHandle = null;

            print '</div>';

            print '<table cellspacing="0" class="top"><tr><td class="top" style="width: 50%">';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:28px">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_jstor.php?' . $url_string . '&from=1') . '">') .
                    '<span class="ui-icon ui-icon-triangle-1-w" style="float:right;width:16px"></span>
			<span class="ui-icon ui-icon-triangle-1-w" style="float:left;width:10px;overflow:hidden"></span>'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:5.1em">'
                    . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_jstor.php?' . $url_string . '&from=' . ($from - 10)) . '" style="color:black;display:block;width:100%">') .
                    '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;'
                    . ($from == 1 ? '' : '</a>') .
                    '</div>';

            print '</td><td class="top" style="width: 50%">';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:29px">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars('download_jstor.php?' . $url_string . '&from=' . $lastpage) . '">' : '') .
                    '<span class="ui-icon ui-icon-triangle-1-e" style="float:right;width:16px"></span>
			   <span class="ui-icon ui-icon-triangle-1-e" style="float:left;width:11px;overflow:hidden"></span>'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom' . ($count >= $from + 10 ? '' : ' ui-state-disabled') . '" style="width:4.6em;float:right;margin-right:2px">'
                    . ($count >= $from + 10 ? '<a class="navigation" href="' . htmlspecialchars("download_jstor.php?$url_string&from=" . ($from + 10)) . '" style="color:black;display:block;width:100%">' : '') .
                    '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;Next'
                    . ($count >= $from + 10 ? '</a>' : '') .
                    '</div>';

            print '<div class="ui-state-highlight ui-corner-bottom pgup" style="float:right;width:5em;margin-right:2px">PgUp</div>';

            print '</td></tr></table><br>* Access to PDFs requires subscription<br>';
        } else {
            print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
        }

        ############# caching #############
        cache_store();
    } else {

########## input table ##############
        ?>
        <div style="text-align: left">
            <form enctype="application/x-www-form-urlencoded" action="download_jstor.php" method="GET" id="download-form">
                <table cellspacing="0" class="threed">
                    <tr>
                        <td style="border: 0px; background-color: transparent" colspan=2>
                            <button id="download-search">Search</button>
                            <button id="download-reset">Reset</button>
                            <button id="download-clear">Clear</button>  </td>
                        <td style="border: 0px; background-color: transparent;text-align:right">
                            <a href="http://www.jstor.org" target="_blank">JSTOR</a>
                        </td>
                    <tr>
        <?php
        for ($i = 1; $i < 7; $i++) {

            print ' <tr>
  <td class="threed" ' . (($i == 1) ? 'style="visibility: hidden"' : '') . '>
  <select name="jstor_operator' . $i . '">
	<option value="AND" ' . ((isset($_SESSION['session_download_jstor_operator' . $i]) && $_SESSION['session_download_jstor_operator' . $i] == 'AND') ? 'selected' : '') . '>AND</option>
	<option value="OR" ' . ((isset($_SESSION['session_download_jstor_operator' . $i]) && $_SESSION['session_download_jstor_operator' . $i] == 'OR') ? 'selected' : '') . '>OR</option>
	<option value="NOT" ' . ((isset($_SESSION['session_download_jstor_operator' . $i]) && $_SESSION['session_download_jstor_operator' . $i] == 'NOT') ? 'selected' : '') . '>ANDNOT</option>
  </select>
  </td>
  <td class="threed">
  <input type="text" name="jstor_query' . $i . '" value="' . htmlspecialchars((isset($_SESSION['session_download_jstor_query' . $i])) ? $_SESSION['session_download_jstor_query' . $i] : '') . '" size="65">
  </td>
  <td class="threed">
  <select name="jstor_selection' . $i . '">
	<option value="jstor.text" ' . ((($i == 1 && !isset($_SESSION['session_download_jstor_selection' . $i])) || (isset($_SESSION['session_download_jstor_selection' . $i]) && $_SESSION['session_download_jstor_selection' . $i] == 'jstor.text')) ? 'selected' : '') . '>anywhere</option>
	<option value="dc.creator" ' . ((($i == 2 && !isset($_SESSION['session_download_jstor_selection' . $i])) || (isset($_SESSION['session_download_jstor_selection' . $i]) && $_SESSION['session_download_jstor_selection' . $i] == 'dc.creator')) ? 'selected' : '') . '>authors</option>
	<option value="dc.title" ' . ((($i == 3 && !isset($_SESSION['session_download_jstor_selection' . $i])) || (isset($_SESSION['session_download_jstor_selection' . $i]) && $_SESSION['session_download_jstor_selection' . $i] == 'dc.title')) ? 'selected' : '') . '>title</option>
	<option value="dc.description" ' . ((($i == 4 && !isset($_SESSION['session_download_jstor_selection' . $i])) || (isset($_SESSION['session_download_jstor_selection' . $i]) && $_SESSION['session_download_jstor_selection' . $i] == 'dc.description')) ? 'selected' : '') . '>abstract</option>
	<option value="jstor.journaltitle" ' . ((($i == 5 && !isset($_SESSION['session_download_jstor_selection' . $i])) || (isset($_SESSION['session_download_jstor_selection' . $i]) && $_SESSION['session_download_jstor_selection' . $i] == 'jstor.journaltitle')) ? 'selected' : '') . '>journal</option>
	<option value="dc.identifier" ' . ((($i == 6 && !isset($_SESSION['session_download_jstor_selection' . $i])) || (isset($_SESSION['session_download_jstor_selection' . $i]) && $_SESSION['session_download_jstor_selection' . $i] == 'dc.identifier')) ? 'selected' : '') . '>identifier</option>
  </select>
  </td>
 </tr>';
        }
        ?>
                </table>

                <table cellpadding="0" cellspacing="0" border="0" class="threed">
                    <tr>
                        <td style="border: 0px; background-color: transparent">
                            Limits and sorting:
                        </td>
                        <td style="border: 0px; background-color: transparent">
                        </td>
                    <tr>
                        <td class="threed">
                            Item Type:
                        </td>
                        <td class="threed">
                            <input type="checkbox" name="jstor_type_1" value="research-article" <?php print (!empty($_SESSION['session_download_jstor_type_1'])) ? 'checked' : ''  ?>>Research Article
                            <input type="checkbox" name="jstor_type_2" value="book-review" <?php print (!empty($_SESSION['session_download_jstor_type_2'])) ? 'checked' : ''  ?>>Book Review
                            <input type="checkbox" name="jstor_type_3" value="editorial" <?php print (!empty($_SESSION['session_download_jstor_type_3'])) ? 'checked' : ''  ?>>Editorial
                            <input type="checkbox" name="jstor_type_4" value="news" <?php print (!empty($_SESSION['session_download_jstor_type_4'])) ? 'checked' : ''  ?>>News
                            <input type="checkbox" name="jstor_type_5" value="misc" <?php print (!empty($_SESSION['session_download_jstor_type_5'])) ? 'checked' : ''  ?>>Miscellaneous
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Discipline:
                        </td>
                        <td class="threed">
                            <div style="float: left;margin-right: 10px">
                                <input type="checkbox" name="jstor_category_1" value="africanamericanstudies" <?php print (!empty($_SESSION['session_download_jstor_category_1'])) ? 'checked' : ''  ?>>African American Studies<br>
                                <input type="checkbox" name="jstor_category_2" value="africanstudies" <?php print (!empty($_SESSION['session_download_jstor_category_2'])) ? 'checked' : ''  ?>>African Studies<br>
                                <input type="checkbox" name="jstor_category_3" value="americanindianstudies" <?php print (!empty($_SESSION['session_download_jstor_category_3'])) ? 'checked' : ''  ?>>American Indian Studies<br>
                                <input type="checkbox" name="jstor_category_4" value="anthropology" <?php print (!empty($_SESSION['session_download_jstor_category_4'])) ? 'checked' : ''  ?>>Anthropology<br>
                                <input type="checkbox" name="jstor_category_5" value="aquaticsciences" <?php print (!empty($_SESSION['session_download_jstor_category_5'])) ? 'checked' : ''  ?>>Aquatic Sciences<br>
                                <input type="checkbox" name="jstor_category_6" value="archaeology" <?php print (!empty($_SESSION['session_download_jstor_category_6'])) ? 'checked' : ''  ?>>Archaeology<br>
                                <input type="checkbox" name="jstor_category_7" value="architecture" <?php print (!empty($_SESSION['session_download_jstor_category_7'])) ? 'checked' : ''  ?>>Architecture & Architectural History<br>
                                <input type="checkbox" name="jstor_category_8" value="arthistory" <?php print (!empty($_SESSION['session_download_jstor_category_8'])) ? 'checked' : ''  ?>>Art & Art History<br>
                                <input type="checkbox" name="jstor_category_9" value="asianstudies" <?php print (!empty($_SESSION['session_download_jstor_category_9'])) ? 'checked' : ''  ?>>Asian Studies<br>
                                <input type="checkbox" name="jstor_category_10" value="bibliography" <?php print (!empty($_SESSION['session_download_jstor_category_10'])) ? 'checked' : ''  ?>>Bibliography<br>
                                <input type="checkbox" name="jstor_category_11" value="biologicalsciences" <?php print (!empty($_SESSION['session_download_jstor_category_11'])) ? 'checked' : ''  ?>>Biological Sciences<br>
                                <input type="checkbox" name="jstor_category_12" value="botany" <?php print (!empty($_SESSION['session_download_jstor_category_12'])) ? 'checked' : ''  ?>>Botany & Plant Sciences<br>
                                <input type="checkbox" name="jstor_category_13" value="britstud" <?php print (!empty($_SESSION['session_download_jstor_category_13'])) ? 'checked' : ''  ?>>British Studies<br>
                                <input type="checkbox" name="jstor_category_14" value="business" <?php print (!empty($_SESSION['session_download_jstor_category_14'])) ? 'checked' : ''  ?>>Business<br>
                                <input type="checkbox" name="jstor_category_15" value="classicalstudies" <?php print (!empty($_SESSION['session_download_jstor_category_15'])) ? 'checked' : ''  ?>>Classical Studies<br>
                                <input type="checkbox" name="jstor_category_16" value="developmentalcellbiology" <?php print (!empty($_SESSION['session_download_jstor_category_16'])) ? 'checked' : ''  ?>>Developmental & Cell Biology<br>
                                <input type="checkbox" name="jstor_category_17" value="ecology" <?php print (!empty($_SESSION['session_download_jstor_category_17'])) ? 'checked' : ''  ?>>Ecology & Evolutionary Biology<br>
                                <input type="checkbox" name="jstor_category_18" value="economics" <?php print (!empty($_SESSION['session_download_jstor_category_18'])) ? 'checked' : ''  ?>>Economics<br>
                                <input type="checkbox" name="jstor_category_19" value="education" <?php print (!empty($_SESSION['session_download_jstor_category_19'])) ? 'checked' : ''  ?>>Education<br>
                                <input type="checkbox" name="jstor_category_20" value="womensstudies" <?php print (!empty($_SESSION['session_download_jstor_category_20'])) ? 'checked' : ''  ?>>Feminist & Women's Studies<br>
                                <input type="checkbox" name="jstor_category_21" value="film" <?php print (!empty($_SESSION['session_download_jstor_category_21'])) ? 'checked' : ''  ?>>Film Studies<br>
                                <input type="checkbox" name="jstor_category_22" value="finance" <?php print (!empty($_SESSION['session_download_jstor_category_22'])) ? 'checked' : ''  ?>>Finance<br>
                                <input type="checkbox" name="jstor_category_23" value="folklore" <?php print (!empty($_SESSION['session_download_jstor_category_23'])) ? 'checked' : ''  ?>>Folklore<br>
                                <input type="checkbox" name="jstor_category_24" value="generalscience" <?php print (!empty($_SESSION['session_download_jstor_category_24'])) ? 'checked' : ''  ?>>General Science<br>
                                <input type="checkbox" name="jstor_category_25" value="geography" <?php print (!empty($_SESSION['session_download_jstor_category_25'])) ? 'checked' : ''  ?>>Geography<br>
                                <input type="checkbox" name="jstor_category_26" value="health" <?php print (!empty($_SESSION['session_download_jstor_category_26'])) ? 'checked' : ''  ?>>Health Policy<br>
                                <input type="checkbox" name="jstor_category_27" value="healthsciences" <?php print (!empty($_SESSION['session_download_jstor_category_27'])) ? 'checked' : ''  ?>>Health Sciences<br>
                            </div>
                            <div style="float: left;margin-right: 10px">
                                <input type="checkbox" name="jstor_category_28" value="history" <?php print (!empty($_SESSION['session_download_jstor_category_28'])) ? 'checked' : ''  ?>>History<br>
                                <input type="checkbox" name="jstor_category_29" value="historyofscience & Technology" <?php print (!empty($_SESSION['session_download_jstor_category_29'])) ? 'checked' : ''  ?>>History of Science & Technology<br>
                                <input type="checkbox" name="jstor_category_30" value="irishstudies" <?php print (!empty($_SESSION['session_download_jstor_category_30'])) ? 'checked' : ''  ?>>Irish Studies<br>
                                <input type="checkbox" name="jstor_category_31" value="jewishstudies" <?php print (!empty($_SESSION['session_download_jstor_category_31'])) ? 'checked' : ''  ?>>Jewish Studies<br>
                                <input type="checkbox" name="jstor_category_32" value="literature" <?php print (!empty($_SESSION['session_download_jstor_category_32'])) ? 'checked' : ''  ?>>Language & Literature<br>
                                <input type="checkbox" name="jstor_category_33" value="latinamericanstudies" <?php print (!empty($_SESSION['session_download_jstor_category_33'])) ? 'checked' : ''  ?>>Latin American Studies<br>
                                <input type="checkbox" name="jstor_category_34" value="law" <?php print (!empty($_SESSION['session_download_jstor_category_34'])) ? 'checked' : ''  ?>>Law<br>
                                <input type="checkbox" name="jstor_category_35" value="libraryscience" <?php print (!empty($_SESSION['session_download_jstor_category_35'])) ? 'checked' : ''  ?>>Library Science<br>
                                <input type="checkbox" name="jstor_category_36" value="linguistics" <?php print (!empty($_SESSION['session_download_jstor_category_36'])) ? 'checked' : ''  ?>>Linguistics<br>
                                <input type="checkbox" name="jstor_category_37" value="management" <?php print (!empty($_SESSION['session_download_jstor_category_37'])) ? 'checked' : ''  ?>>Management & Organizational Behavior<br>
                                <input type="checkbox" name="jstor_category_38" value="marketing" <?php print (!empty($_SESSION['session_download_jstor_category_38'])) ? 'checked' : ''  ?>>Marketing & Advertising<br>
                                <input type="checkbox" name="jstor_category_39" value="mathematics" <?php print (!empty($_SESSION['session_download_jstor_category_39'])) ? 'checked' : ''  ?>>Mathematics<br>
                                <input type="checkbox" name="jstor_category_40" value="middleeaststudies" <?php print (!empty($_SESSION['session_download_jstor_category_40'])) ? 'checked' : ''  ?>>Middle East Studies<br>
                                <input type="checkbox" name="jstor_category_41" value="music" <?php print (!empty($_SESSION['session_download_jstor_category_41'])) ? 'checked' : ''  ?>>Music<br>
                                <input type="checkbox" name="jstor_category_42" value="paleontology" <?php print (!empty($_SESSION['session_download_jstor_category_42'])) ? 'checked' : ''  ?>>Paleontology<br>
                                <input type="checkbox" name="jstor_category_43" value="performingarts" <?php print (!empty($_SESSION['session_download_jstor_category_43'])) ? 'checked' : ''  ?>>Performing Arts<br>
                                <input type="checkbox" name="jstor_category_44" value="philosophy" <?php print (!empty($_SESSION['session_download_jstor_category_44'])) ? 'checked' : ''  ?>>Philosophy<br>
                                <input type="checkbox" name="jstor_category_45" value="politicalscience" <?php print (!empty($_SESSION['session_download_jstor_category_45'])) ? 'checked' : ''  ?>>Political Science<br>
                                <input type="checkbox" name="jstor_category_46" value="populationstudies" <?php print (!empty($_SESSION['session_download_jstor_category_46'])) ? 'checked' : ''  ?>>Population Studies<br>
                                <input type="checkbox" name="jstor_category_47" value="psychology" <?php print (!empty($_SESSION['session_download_jstor_category_47'])) ? 'checked' : ''  ?>>Psychology<br>
                                <input type="checkbox" name="jstor_category_48" value="publicpolicy" <?php print (!empty($_SESSION['session_download_jstor_category_48'])) ? 'checked' : ''  ?>>Public Policy & Administration<br>
                                <input type="checkbox" name="jstor_category_49" value="religion" <?php print (!empty($_SESSION['session_download_jstor_category_49'])) ? 'checked' : ''  ?>>Religion<br>
                                <input type="checkbox" name="jstor_category_50" value="slavicstudies" <?php print (!empty($_SESSION['session_download_jstor_category_50'])) ? 'checked' : ''  ?>>Slavic Studies<br>
                                <input type="checkbox" name="jstor_category_51" value="sociology" <?php print (!empty($_SESSION['session_download_jstor_category_51'])) ? 'checked' : ''  ?>>Sociology<br>
                                <input type="checkbox" name="jstor_category_52" value="statistics" <?php print (!empty($_SESSION['session_download_jstor_category_52'])) ? 'checked' : ''  ?>>Statistics<br>
                                <input type="checkbox" name="jstor_category_53" value="zoology" <?php print (!empty($_SESSION['session_download_jstor_category_53'])) ? 'checked' : ''  ?>>Zoology<br>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Language:
                        </td>
                        <td class="threed">
                            <select name="jstor_lan" style="float:left;width:50%">
                                <option value="">All Languages</option>
                                <option value="eng" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'eng' ? 'selected' : ''; ?>>English</option>
                                <option value="dut" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'dut' ? 'selected' : ''; ?>>Dutch</option>
                                <option value="fre" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'fre' ? 'selected' : ''; ?>>French</option>
                                <option value="ger" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'ger' ? 'selected' : ''; ?>>German</option>
                                <option value="ita" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'ita' ? 'selected' : ''; ?>>Italian</option>
                                <option value="lat" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'lat' ? 'selected' : ''; ?>>Latin</option>
                                <option value="por" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'por' ? 'selected' : ''; ?>>Portuguese</option>
                                <option value="spa" <?php print isset($_SESSION['session_download_jstor_lan']) && $_SESSION['session_download_jstor_lan'] == 'spa' ? 'selected' : ''; ?>>Spanish</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Sort by:
                        </td>
                        <td class="threed">
                            <select name="jstor_sort" style="float:left;width:50%">
                                <option></option>
                                <option value="dc.date/sort.descending" <?php print isset($_SESSION['session_download_jstor_sort']) && $_SESSION['session_download_jstor_sort'] == 'dc.date/sort.descending' ? 'selected' : ''; ?>>publication year - new first</option-->
                                <option value="dc.date/sort.ascending" <?php print isset($_SESSION['session_download_jstor_sort']) && $_SESSION['session_download_jstor_sort'] == 'dc.date/sort.ascending' ? 'selected' : ''; ?>>publication year - older first</option-->
                                <option value="jstor.journaltitle/sort.ascending" <?php print isset($_SESSION['session_download_jstor_sort']) && $_SESSION['session_download_jstor_sort'] == 'jstor.journaltitle/sort.ascending' ? 'selected' : ''; ?>>journal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="threed">
                            Save search as:
                        </td>
                        <td class="threed">
                            <input type="text" name="jstor_searchname" size="35" style="float:left;width:50%" value="<?php print isset($_SESSION['session_download_jstor_searchname']) ? htmlspecialchars($_SESSION['session_download_jstor_searchname']) : '' ?>">
                            &nbsp;<button id="download-save">Save</button>
                        </td>
                    </tr>
                </table>
                </td>
                </tr>
                </table>
                &nbsp;<a href="http://www.jstor.org/page/info/about/policies/terms.jsp" TARGET="_blank">Terms and Conditions</a>
            </form>
        </div>
        <?php
// CLEAN DOWNLOAD CACHE
         $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id(). DIRECTORY_SEPARATOR . 'page_*_download', GLOB_NOSORT);
        foreach ($clean_files as $clean_file) {
            if (is_file($clean_file) && is_writable($clean_file))
                @unlink($clean_file);
        }
    }
}
?>
<br>