<?php
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

    // reset button

    if (isset($_GET['newsearch'])) {

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_sciencedirect'))
                unset($_SESSION[$key]);
        }
    }

    // save button

    if (isset($_GET['save']) && $_GET['save'] == '1' && !empty($_GET['sciencedirect_searchname'])) {

        database_connect($database_path, 'library');

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $stmt2 = $dbHandle->prepare("INSERT INTO searches (userID,searchname,searchfield,searchvalue) VALUES (:user,:searchname,'',:searchvalue)");

        $stmt2->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt2->bindParam(':searchname', $searchname, PDO::PARAM_STR);
        $stmt2->bindParam(':searchvalue', $save_string, PDO::PARAM_STR);

        $dbHandle->beginTransaction();

        $user = $_SESSION['user_id'];
        $searchname = "sciencedirect#" . $_GET['sciencedirect_searchname'];

        $stmt->execute();

        $user = $_SESSION['user_id'];
        $searchname = "sciencedirect#" . $_GET['sciencedirect_searchname'];
                
        reset($_GET);
        $save_array = $_GET;
        unset($save_array['_']);
        unset($save_array['save']);
        unset($save_array['action']);
        unset($save_array['sciencedirect_searchname']);
        $save_array = array_filter($save_array);
        $save_string = serialize($save_array);

        $stmt2->execute();

        $dbHandle->commit();
    }

    // load button

    if (isset($_GET['load']) && $_GET['load'] == '1' && !empty($_GET['saved_search'])) {

        database_connect($database_path, 'library');

        $stmt = $dbHandle->prepare("SELECT searchvalue FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "sciencedirect#" . $_GET['saved_search'];

        $stmt->execute();
        
        reset($_SESSION);
        while (list($key, $value) = each($_SESSION)) {
            if (strstr($key, 'session_download_sciencedirect'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
        
        $load_string = $stmt->fetchColumn();
        $_GET = unserialize($load_string);
        $_GET['load'] = 'Load';
        $_GET['sciencedirect_searchname'] = substr($searchname, 5);
        while (list($key, $value) = each($_GET)) {
            if (!empty($_GET[$key]))
                $_SESSION['session_download_' . $key] = $value;
        }
    }

    // delete button

    if (isset($_GET['delete']) && $_GET['delete'] == '1' && !empty($_GET['saved_search'])) {

        database_connect($database_path, 'library');

        $dbHandle->beginTransaction();

        $stmt = $dbHandle->prepare("DELETE FROM searches WHERE userID=:user AND searchname=:searchname");

        $stmt->bindParam(':user', $user, PDO::PARAM_STR);
        $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);

        $user = $_SESSION['user_id'];
        $searchname = "sciencedirect#" . $_GET['saved_search'];

        $stmt->execute();

        $dbHandle->commit();

        while (list($key, $value) = each($_SESSION)) {

            if (strstr($key, 'session_download_sciencedirect'))
                unset($_SESSION[$key]);
        }

        $_GET = array();
    }

    if (!empty($_GET['action'])) {
        
        $microtime1 = microtime(true);

        reset($_GET);
        unset($_SESSION['session_download_sciencedirect_refinements']);
        while (list($key, $value) = each($_GET)) {
            if (!empty($_GET[$key]))
                $_SESSION['session_download_' . $key] = $value;
        }

        if (!isset($_GET['from'])) {
            $_GET['from'] = '1';
            $from = $_GET['from'];
        } else {
            $from = intval($_GET['from']);
        }

        // PREPARE QUERY

        $url_string = 'action=search';
        
        // SEARCH TYPE
        
        $type_string = '&searchField=Search_All';
        if ($_GET['sciencedirect_type'] == 'fulltext') $type_string = '&searchField=Search_All_Text';
        $url_string .= '&sciencedirect_type='.urlencode($_GET['sciencedirect_type']);

        //ADD RANGE

        $year_string = 'addRange=1872_' . date('Y') . '_Publication_Year';

        if (isset($_GET['sciencedirect_range']) && $_GET['sciencedirect_range'] == 'range') {
            $year_from = '1872';
            $year_to = date('Y');
            if (!empty($_GET['sciencedirect_year_from']))
                $year_from = $_GET['sciencedirect_year_from'];
            if (!empty($_GET['sciencedirect_year_to']))
                $year_to = $_GET['sciencedirect_year_to'];
            $year_string = 'addRange=' . $year_from . '_' . $year_to . '_Publication_Year';

            $url_string .= '&sciencedirect_range=' . urlencode($_GET['sciencedirect_range']) . '&'
                    . 'sciencedirect_year_from=' . urlencode($_GET['sciencedirect_year_from']) . '&'
                    . 'sciencedirect_year_to=' . urlencode($_GET['sciencedirect_year_to']);
        }

        //REFINEMENTS

        $refinement_string = '';
        $refinement_array = array();

        if (isset($_GET['sciencedirect_refinements'])) {
            while (list($key, $value) = each($_GET['sciencedirect_refinements'])) {
                $refinement_array[] = 'refinements=' . urlencode($value);
                $refinement_string = join('&', $refinement_array);
                $url_string .= '&sciencedirect_refinements[]=' . urlencode($value);
            }
        }

        //SORTING

        $sortby_string = '';

        if (isset($_GET['sciencedirect_sort'])) {
            $sortby_string = 'sortType=' . urlencode($_GET['sciencedirect_sort']);
            $url_string .= '&sciencedirect_sort=' . urlencode($_GET['sciencedirect_sort']);
        }

        //PAGINATION

        $pagination_string = 'pageNumber=' . $from;

        //MAIN QUERY

        $query_string = '';
        $k = 1;

        for ($i = 1; $i < 11; $i++) {
            if (!empty($_GET['sciencedirect_query' . $i])) {
                $query_string .= (($k > 1) ? ' ' . $_GET['sciencedirect_operator' . $i] . ' ' : '') . $_GET['sciencedirect_parenthesis' . $i . '-1'] . $_GET['sciencedirect_searchin' . $i] . ':' . $_GET['sciencedirect_query' . $i] . $_GET['sciencedirect_parenthesis' . $i . '-2'];
                $k = $k + 1;
                if ($i > 1)
                    $url_string .= '&sciencedirect_operator' . $i . '=' . urlencode($_GET['sciencedirect_operator' . $i]);
                $url_string .= '&sciencedirect_parenthesis' . $i . '-1=' . urlencode($_GET['sciencedirect_parenthesis' . $i . '-1'])
                        . '&sciencedirect_searchin' . $i . '=' . urlencode($_GET['sciencedirect_searchin' . $i])
                        . '&sciencedirect_query' . $i . '=' . urlencode($_GET['sciencedirect_query' . $i])
                        . '&sciencedirect_parenthesis' . $i . '-2=' . urlencode($_GET['sciencedirect_parenthesis' . $i . '-2']);
            }
        }

        $query = urlencode('queryText=('. $query_string . ')');

        // SEARCH

        if (!empty($query_string) && empty($_GET['load']) && empty($_GET['save']) && empty($_GET['delete'])) {

            // CACHE

            $cache_name = cache_name();
            $cache_name .= '_download';
            $db_change = database_change(array(
                'library'
            ));
            cache_start($db_change);

            ########## register the time of search ##############

            if (!empty($_SESSION['session_download_sciencedirect_searchname']) && $from == 1) {

                database_connect($database_path, 'library');

                $stmt = $dbHandle->prepare("UPDATE searches SET searchvalue=:searchvalue WHERE userID=:user AND searchname=:searchname AND searchfield='sciencedirect_last_search'");

                $stmt->bindParam(':user', $user, PDO::PARAM_STR);
                $stmt->bindParam(':searchname', $searchname, PDO::PARAM_STR);
                $stmt->bindParam(':searchvalue', $searchvalue, PDO::PARAM_STR);

                $user = $_SESSION['user_id'];
                $searchname = "sciencedirect#" . $_SESSION['session_download_sciencedirect_searchname'];
                $searchvalue = time();

                $stmt->execute();
            }

            ########## search sciencedirect ##############

            $request_url = "http://sciencedirectxplore.sciencedirect.org/search/searchresult.jsp?action=search&rowsPerPage=25&matchBoolean=true&"
                    . $query . "&"
                    . $refinement_string . "&"
                    . $sortby_string . "&"
                    . $pagination_string . "&"
                    . $year_string . "&"
                    . $type_string;

            $dom = proxy_dom_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

            if (empty($dom))
                die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
        }

        // DISPLAY RESULTS
        
        if (!empty($dom)) {

            print '<div style="padding:2px;font-weight:bold">sciencedirect Xplore&reg; search';

            if (!empty($_SESSION['session_download_sciencedirect_searchname']))
                print ': ' . htmlspecialchars($_SESSION['session_download_sciencedirect_searchname']);

            print '</div>';

            //SCRAPE, BABY, SCRAPE!

            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML($dom);
            $xpath = new DOMXPath($doc);
            $div = $doc->getElementById('content');
            $count_string = '';
            $count_obj = $xpath->query("span", $div)->item(0);
            if(is_object($count_obj)) $count_string = $count_obj->nodeValue;
            $count = preg_replace('/\D/ui', '', $count_string);

            if (!empty($count) && $count > 0) {

                $j = 1;
                $maxfrom = $from * 25;
                if ($maxfrom > $count)
                    $maxfrom = $count;

                $microtime2 = microtime(true);
                $microtime = $microtime2 - $microtime1;
                $microtime = sprintf("%01.1f seconds", $microtime);

                print '<table cellspacing="0" class="top"><tr><td class="top" style="width: 20%">';

                print '<div class="ui-state-highlight ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:28px">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_sciencedirect.php?' . $url_string . '&from=1') . '">') .
                        '<span class="ui-icon ui-icon-triangle-1-w" style="float:right;width:16px"></span>
			<span class="ui-icon ui-icon-triangle-1-w" style="float:left;width:10px;overflow:hidden"></span>'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-top' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:5.1em">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_sciencedirect.php?' . $url_string . '&from=' . ($from - 1)) . '" style="color:black;display:block;width:100%">') .
                        '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '</td><td class="top" style="text-align: center">';

                print "Items " . (($from - 1) * 25 + 1) . " - $maxfrom of $count in $microtime.";

                print '</td><td class="top" style="width: 20%">';

                $lastpage = ceil($count / 25);

                print '<div class="ui-state-highlight ui-corner-top' . ($count >= $from * 25 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:29px">'
                        . ($count >= $from * 25 ? '<a class="navigation" href="' . htmlspecialchars('download_sciencedirect.php?' . $url_string . '&from=' . $lastpage) . '">' : '') .
                        '<span class="ui-icon ui-icon-triangle-1-e" style="float:right;width:16px"></span>
			   <span class="ui-icon ui-icon-triangle-1-e" style="float:left;width:11px;overflow:hidden"></span>'
                        . ($count >= $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-top' . ($count >= $from * 25 ? '' : ' ui-state-disabled') . '" style="width:4.6em;float:right;margin-right:2px">'
                        . ($count >= $from * 25 ? '<a class="navigation" href="' . htmlspecialchars("download_sciencedirect.php?$url_string&from=" . ($from + 1)) . '" style="color:black;display:block;width:100%">' : '') .
                        '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;Next'
                        . ($count >= $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-top pgdown" style="float: right;width: 5em;margin-right:2px">PgDown</div>';

                print '</td></tr></table>';

                print '<div class="alternating_row">';

                database_connect($database_path, 'library');

                function DOMinnerHTML($element) {
                    $innerHTML = "";
                    $children = $element->childNodes;
                    foreach ($children as $child) {
                        $tmp_dom = new DOMDocument();
                        $tmp_dom->appendChild($tmp_dom->importNode($child, true));
                        $innerHTML.=trim($tmp_dom->saveHTML());
                    }
                    return $innerHTML;
                }

                $form = $doc->getElementById('search_results_form');
                $entries = $xpath->query("ul[1]", $form);
                $items = $xpath->query("li/div/div[3]", $entries->item(0));

                foreach ($items as $item) {
                    $id = '';
                    $title = '';
                    $secondary_title = '';
                    $abstract = '';
                    $authors = '';
                    $doi = '';
                    $year = '';
                    $volume = '';
                    $issue = '';
                    $pages = '';
                    $title_obj = $xpath->query("h3/a", $item)->item(0);
                    if (is_object($title_obj))
                        $title = trim($title_obj->nodeValue);
                    $secondary_title_obj = $xpath->query("a[1]", $item)->item(0);
                    if (is_object($secondary_title_obj))
                        $secondary_title = trim($secondary_title_obj->nodeValue);
                    $abstract_obj = $xpath->query("div/p", $item)->item(0);
                    if (is_object($abstract_obj))
                        $abstract = trim($abstract_obj->nodeValue);
                    $item_html = DOMinnerHTML($item);
                    $item_html = str_replace("\r\n", " ", $item_html);
                    $item_html = str_replace("\n", " ", $item_html);
                    $item_html = str_replace("\r", " ", $item_html);
                    preg_match('/(?<=arnumber\=)\d+?(?=\&)/ui', $item_html, $id_match);
                    if (isset($id_match[0])) {
                        $id = trim(strip_tags($id_match[0]));
                        $uid = 'sciencedirect:' . $id;
                    }
                    preg_match('/(?<=\<\/h3\>).+?(?=\<br\>)/ui', $item_html, $authors_match);
                    if (isset($authors_match[0]))
                        $authors = trim(strip_tags($authors_match[0]));
                    preg_match('/10\.\d{4}\/.*?(?=\s)/ui', strip_tags($item_html), $doi_match);
                    if (isset($doi_match[0]))
                        $doi = trim($doi_match[0]);
                    preg_match('/(?<=Publication\syear\:\s)\d{4}/ui', strip_tags($item_html), $year_match);
                    if (isset($year_match[0]))
                        $year = trim($year_match[0]);
                    preg_match('/(?<=Volume\:\s)\d+/ui', $item_html, $volume_match);
                    if (isset($volume_match[0]))
                        $volume = trim(strip_tags($volume_match[0]));
                    preg_match('/(?<=Issue\:\s)\d+/ui', $item_html, $issue_match);
                    if (isset($issue_match[0]))
                        $issue = trim(strip_tags($issue_match[0]));
                    preg_match('/(?<=Page\(s\)\:\s).+?(?=\<)/ui', $item_html, $pages_match);
                    if (isset($pages_match[0]))
                        $pages = trim(strip_tags($pages_match[0]));

                    if (!empty($id) && !empty($title)) {

                        ########## gray out existing records ##############

                        $title_query = $dbHandle->quote(substr($title, 0, -1) . "%");
                        $result_query = $dbHandle->query("SELECT id FROM library WHERE title LIKE $title_query AND length(title) <= length($title_query)+2 LIMIT 1");
                        $existing_id = $result_query->fetchColumn();

                        print '<div class="items" data-uid="' . htmlspecialchars($id) . '">';

                        print '<div class="titles" style="margin-right:30px';

                        if ($existing_id['count(*)'] > 0)
                            print ';color: #999';

                        print '">' . $title . '</div>';

                        print '<div style="clear:both"></div>';

                        print '<table class="firstcontainer" style="width:100%"><tr><td class="items">';

                        print htmlspecialchars($secondary_title);

                        if ($year != '')
                            print " ($year)";

                        if (!empty($authors))
                            print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>' . htmlspecialchars($authors) . '</div></div>';

                        print '<a href="' . htmlspecialchars('http://sciencedirectxplore.sciencedirect.org/xpl/articleDetails.jsp?arnumber=' . $id) . '" target="_blank">sciencedirect</a>';
                        if (!empty($doi))
                            print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

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

                        if ($volume != '')
                            print " <b>$volume</b>";

                        if ($issue != '')
                            print " ($issue):";

                        if ($pages != '')
                            print " $pages";
                        print '</div>';

                        if (!empty($authors)) {
                            print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>' . htmlspecialchars($authors) . '</div></div>';
                            $new_authors = array();
                            $array = array();
                            $array = explode(';', $authors);
                            $array = array_filter($array);
                            if (!empty($array)) {
                                foreach ($array as $author) {
                                    $array2 = explode(',', $author);
                                    if (isset($array2[1])) {
                                        $last = trim($array2[0]);
                                        $first = trim($array2[1]);
                                    } else {
                                        $array3 = explode(' ', trim($author));
                                        $first = '';
                                        if (isset($array3[1])) {
                                            $last = trim($array3[1]);
                                            $first = trim($array3[0]);
                                        } else {
                                            $last = trim($array3[0]);
                                        }
                                    }
                                    $new_authors[] = 'L:"' . $last . '",F:"' . $first . '"';
                                }
                                $names = join(';', $new_authors);
                            }
                        }

                        print '</td></tr>';

                        print '<tr><td><div class="abstract">';

                        !empty($abstract) ? print htmlspecialchars($abstract)  : print 'No abstract available.';

                        print '</div></td></tr><tr><td class="items">';
                        ?>

                        <input type="hidden" name="uid[]" value="<?php if (!empty($uid)) print htmlspecialchars($uid); ?>">
                        <input type="hidden" name="doi" value="<?php if (!empty($doi)) print htmlspecialchars($doi); ?>">
                        <input type="hidden" name="authors" value="<?php if (!empty($names)) print htmlspecialchars($names); ?>">
                        <input type="hidden" name="title" value="<?php if (!empty($title)) print htmlspecialchars($title); ?>">
                        <input type="hidden" name="secondary_title" value="<?php if (!empty($secondary_title)) print htmlspecialchars($secondary_title); ?>">
                        <input type="hidden" name="year" value="<?php if (!empty($year)) print htmlspecialchars($year); ?>">
                        <input type="hidden" name="volume" value="<?php if (!empty($volume)) print htmlspecialchars($volume); ?>">
                        <input type="hidden" name="issue" value="<?php if (!empty($issue)) print htmlspecialchars($issue); ?>">
                        <input type="hidden" name="pages" value="<?php if (!empty($pages)) print htmlspecialchars($pages); ?>">
                        <input type="hidden" name="abstract" value="<?php print !empty($abstract) ? htmlspecialchars($abstract) : "No abstract available."; ?>">
                        <?php
                        ##########	print full text links	##########

                        print '<b>Full text options:</b><br>';

                        print '<a href="' . htmlspecialchars('http://sciencedirectxplore.sciencedirect.org/xpl/articleDetails.jsp?arnumber=' . $id) . '" target="_blank">sciencedirect</a>';
                        if (!empty($doi))
                            print ' <b>&middot;</b> <a href="' . htmlspecialchars("http://dx.doi.org/" . urlencode($doi)) . '" target="_blank">Publisher Website</a>';

                        print '<br><button class="save-item">Save</button> <button class="quick-save-item">Quick Save</button>';

                        print '</td></tr></table></form>';

                        print '</div>';

                        print '<div class="save_container"></div>';

                        print '</div>';

                        if ($j < 25 && $j < $maxfrom - ($from - 1) * 25)
                            print '<div class="separator"></div>';

                        $j = $j + 1;
                    }
                }

                $dbHandle = null;

                print '</div>';

                print '<table cellspacing="0" class="top"><tr><td class="top" style="width: 50%">';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:28px">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_sciencedirect.php?' . $url_string . '&from=1') . '">') .
                        '<span class="ui-icon ui-icon-triangle-1-w" style="float:right;width:16px"></span>
			<span class="ui-icon ui-icon-triangle-1-w" style="float:left;width:10px;overflow:hidden"></span>'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($from == 1 ? ' ui-state-disabled' : '') . '" style="float:left;margin-left:2px;width:5.1em">'
                        . ($from == 1 ? '' : '<a class="navigation" href="' . htmlspecialchars('download_sciencedirect.php?' . $url_string . '&from=' . ($from - 1)) . '" style="color:black;display:block;width:100%">') .
                        '<span class="ui-icon ui-icon-triangle-1-w" style="float:left"></span>Back&nbsp;'
                        . ($from == 1 ? '' : '</a>') .
                        '</div>';

                print '</td><td class="top" style="width: 50%">';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($count >= $from * 25 ? '' : ' ui-state-disabled') . '" style="float:right;margin-right:2px;width:29px">'
                        . ($count >= $from * 25 ? '<a class="navigation" href="' . htmlspecialchars('download_sciencedirect.php?' . $url_string . '&from=' . $lastpage) . '">' : '') .
                        '<span class="ui-icon ui-icon-triangle-1-e" style="float:right;width:16px"></span>
			   <span class="ui-icon ui-icon-triangle-1-e" style="float:left;width:11px;overflow:hidden"></span>'
                        . ($count >= $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-bottom' . ($count >= $from * 25 ? '' : ' ui-state-disabled') . '" style="width:4.6em;float:right;margin-right:2px">'
                        . ($count >= $from * 25 ? '<a class="navigation" href="' . htmlspecialchars("download_sciencedirect.php?$url_string&from=" . ($from + 1)) . '" style="color:black;display:block;width:100%">' : '') .
                        '<span class="ui-icon ui-icon-triangle-1-e" style="float:right"></span>&nbsp;Next'
                        . ($count >= $from * 25 ? '</a>' : '') .
                        '</div>';

                print '<div class="ui-state-highlight ui-corner-bottom pgup" style="float:right;width:5em;margin-right:2px">PgUp</div>';

                print '</td></tr></table>';
            } else {
                print '<div style="position:relative;top:43%;left:40%;color:#bbbbbb;font-size:28px;width:200px"><b>No Items</b></div>';
            }

            ############# caching #############
            cache_store();
        }
    } else {

########## input table ##############
        ?>
        <form enctype="application/x-www-form-urlencoded" action="download_sciencedirect.php" method="GET" id="download-form">	
            <input type="hidden" value="" name="rowsPerPage">
            <input type="hidden" value="search" name="action">
            <table cellspacing="0" class="threed" style="width:99%">
                <tr>
                    <td style="border: 0;background-color: transparent" colspan="2">
                        <button id="download-search">Search</button>
                        <button id="download-reset">Reset</button>
                        <button id="download-clear">Clear</button>
                    </td>
                    <td style="border: 0px; background-color: transparent;text-align:right">
                        <a href="http://www.sciencedirect.com" target="_blank">SienceDirect</a>
                    </td>
                </tr>
                <?php
                for ($i=1;$i<11;$i++) {
                    print '
                <tr>
                    <td class="threed" style="text-align:right">';
                    if ($i > 1) print '
                        <select name="sciencedirect_operator'.$i.'">
                            <option value="AND">
                                AND
                            </option>
                            <option value="OR"'.((isset($_SESSION['session_download_sciencedirect_operator'.$i]) && $_SESSION['session_download_sciencedirect_operator'.$i] == 'OR') ? ' selected' : '').'>
                                OR
                            </option>
                            <option value="NOT"'.((isset($_SESSION['session_download_sciencedirect_operator'.$i]) && $_SESSION['session_download_sciencedirect_operator'.$i] == 'NOT') ? ' selected' : '').'>
                                NOT
                            </option>
                        </select>';
                    print '
                        <select name="sciencedirect_parenthesis'.$i.'-1">
                            <option value=""></option>
                            <option value="("'.((isset($_SESSION['session_download_sciencedirect_parenthesis'.$i.'-1']) && $_SESSION['session_download_sciencedirect_parenthesis'.$i.'-1'] == '(') ? ' selected' : '').'>(</option>
                        </select>
                    </td>
                    <td class="threed">
                        <input type="text" size="50" name="sciencedirect_query'.$i.'" style="width:99%" value="'.(!empty($_SESSION['session_download_sciencedirect_query'.$i]) ? $_SESSION['session_download_sciencedirect_query'.$i] : '').'">
                    </td>
                    <td class="threed">
                        in
                        <select class="sciencedirect-searchin" name="sciencedirect_searchin'.$i.'">
                            <option value="">
                                All Fields
                            </option>
                            <option value="tak"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Document Title"') ? ' selected' : '').'>
                                Abstract, Title, Keywords
                            </option>
                            <option value="aut"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Authors"') ? ' selected' : '').'>
                                Authors
                            </option>
                            <option value="aus"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Publication Title"') ? ' selected' : '').'>
                                Specific Author
                            </option>
                            <option value="src"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Abstract"') ? ' selected' : '').'>
                                Source Title
                            </option>
                            <option value="ttl"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Index Terms"') ? ' selected' : '').'>
                                Title
                            </option>
                            <option value="key"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Author Affiliation"') ? ' selected' : '').'>
                                Keywords
                            </option>
                            <option value="abs"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Accession Number"') ? ' selected' : '').'>
                                Abstract
                            </option>
                            <option value="ref"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Article Number"') ? ' selected' : '').'>
                                References
                            </option>
                             <option value="DOI"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Accession Number"') ? ' selected' : '').'>
                                DOI
                            </option>
                            <option value="ISSN"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"Author Keywords"') ? ' selected' : '').'>
                                ISSN
                            </option>
                            <option value="ISBN"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"DOE Terms"') ? ' selected' : '').'>
                                ISBN
                            </option>
                            <option value="aff"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"DOI"') ? ' selected' : '').'>
                                Affiliation
                            </option>
                            <option value="FULL-TEXT"'.((isset($_SESSION['session_download_sciencedirect_searchin'.$i]) && $_SESSION['session_download_sciencedirect_searchin'.$i] == '"sciencedirect Terms"') ? ' selected' : '').'>
                                Full Text
                            </option>
                        </select>
                        <select name="sciencedirect_parenthesis'.$i.'-2">
                            <option value=""></option>
                            <option value=")"'.((isset($_SESSION['session_download_sciencedirect_parenthesis'.$i.'-2']) && $_SESSION['session_download_sciencedirect_parenthesis'.$i.'-2'] == ')') ? ' selected' : '').'>)</option>
                        </select>
                    </td>
                </tr>';
                }
                    ?>
                
            </table>
            &nbsp;Limits:
            <table class="threed" width="99%">
                <tr>
                    <td class="threed" style="width:10em">
                        Content Types:
                    </td>
                    <td class="threed">
                        <input type="checkbox" value="4291944246" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4291944246', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                        Journals
                        <input type="checkbox" value="4291944823" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4291944823', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                        Books
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Subjects:
                    </td>
                    <td class="threed">
                        <div style="float:left;margin-right:10px">
                            <input type="checkbox" value="5" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967045', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Agricultural and Biological Sciences<br>
                            <input type="checkbox" value="6" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967046', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Arts and Humanities<br>
                            <input type="checkbox" value="18" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967114', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Biochemistry, Genetics and Molecular Biology<br>
                            <input type="checkbox" value="7" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967044', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Business, Management and Accounting<br>
                            <input type="checkbox" value="8" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294966781', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Chemical Engineering<br>
                            <input type="checkbox" value="9" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967042', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Chemistry<br>
                            <input type="checkbox" value="11" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967254', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Computer Science<br>
                            <input type="checkbox" value="12" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967113', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Decision Sciences<br>
                            <input type="checkbox" value="13" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294966917', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Earth and Planetary Sciences<br>
                            <input type="checkbox" value="14" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294961108', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Economics, Econometrics and Finance<br>
                            <input type="checkbox" value="15" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967010', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Energy<br>
                            <input type="checkbox" value="16" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967026', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Engineering<br>
                        </div>
                        <div>
                            <input type="checkbox" value="17" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294966918', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Environmental Science<br>
                            <input type="checkbox" value="220" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967025', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Immunology and Microbiology<br>
                            <input type="checkbox" value="19" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967043', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Materials Science<br>
                            <input type="checkbox" value="20" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294964254', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Mathematics<br>
                            <input type="checkbox" value="21" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294966917', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Medicine and Dentistry<br>
                            <input type="checkbox" value="22" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294961108', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Neuroscience<br>
                            <input type="checkbox" value="466" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967010', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Nursing and Health Professions<br>
                            <input type="checkbox" value="23" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967026', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Pharmacology, Toxicology, Pharmaceutical Science<br>
                            <input type="checkbox" value="24" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294966918', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Physics and Astronomy<br>
                            <input type="checkbox" value="25" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967025', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Psychology<br>
                            <input type="checkbox" value="26" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294967043', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Social Sciences<br>
                            <input type="checkbox" value="487" name="sciencedirect_refinements[]"<?php print (is_array($_SESSION['session_download_sciencedirect_refinements']) && in_array('4294964254', $_SESSION['session_download_sciencedirect_refinements'])) ? ' checked' : ''  ?>>
                            Veterinary Science and Veterinary Medicine
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Publication Year:
                    </td>
                    <td class="threed">
                        <input type="radio" value="" name="sciencedirect_range"<?php print empty($_SESSION['session_download_sciencedirect_range']) ? ' checked' : ''  ?>> All Available Years<br>
                        <input type="radio" value="range" name="sciencedirect_range"<?php print (isset($_SESSION['session_download_sciencedirect_range']) && $_SESSION['session_download_sciencedirect_range'] == 'range') ? ' checked' : ''  ?>> Specify Year Range
                        from:
                        <input type="text" size="4" name="sciencedirect_year_from" value="<?php print isset($_SESSION['session_download_sciencedirect_year_from']) ? htmlspecialchars($_SESSION['session_download_sciencedirect_year_from']) : '1823'  ?>">
                        to:
                        <input type="text" size="4" name="sciencedirect_year_to" value="<?php print isset($_SESSION['session_download_sciencedirect_year_to']) ? htmlspecialchars($_SESSION['session_download_sciencedirect_year_to']) : date('Y')  ?>">
                    </td>
                </tr>
                <tr>
                    <td class="threed">
                        Save search as:
                    </td>
                    <td class="threed">
                        <input type="text" name="sciencedirect_searchname" size="35" style="float:left;width:50%" value="<?php print isset($_SESSION['session_download_sciencedirect_searchname']) ? htmlspecialchars($_SESSION['session_download_sciencedirect_searchname']) : ''  ?>">
                        &nbsp;<button id="download-save">Save</button>
                    </td>
                    <td style="border: 0px; background-color: transparent">
                    </td>
                </tr>
            </table>
        </form>
        <br>
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