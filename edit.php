<?php
include_once 'data.php';

if (isset($_SESSION['auth']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    $proxy_name = '';
    $proxy_port = '';
    $proxy_username = '';
    $proxy_password = '';

    if (isset($_SESSION['connection']) && ($_SESSION['connection'] == "autodetect" || $_SESSION['connection'] == "url")) {
        if (!empty($_POST['proxystr'])) {
            $proxy_arr = explode(';', $_POST['proxystr']);
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

    $user_id = intval($_SESSION['user_id']);
    $user = $_SESSION['user'];

    session_write_close();

    include_once 'functions.php';

    $error = '';

    if (isset($_POST['file']))
        $_GET['file'] = $_POST['file'];

##########	reference updating from PubMed	##########

    if (isset($_POST['autoupdate'])) {

        if (!empty($_POST['doi'])) {
            $doi = trim($_POST['doi']);
            if (stripos($doi, 'doi:') === 0)
                $doi = trim(substr($doi, 4));
        }

        foreach ($_POST['uid'] as $uid_element) {
            $uid_array2 = explode(":", $uid_element);
            $uid_array2[0] = trim(strtoupper($uid_array2[0]));
            if ($uid_array2[0] == 'PMID')
                $pmid = trim($uid_array2[1]);
            if ($uid_array2[0] == 'ARXIV')
                $arxiv_id = trim($uid_array2[1]);
            if ($uid_array2[0] == 'NASAADS')
                $nasa_id = trim($uid_array2[1]);
        }

        if ($_POST['database'] == 'pubmed') {

            if (empty($pmid)) {

                $pubmed_query = array();

                if (!empty($_POST['authors']))
                    preg_match('/(?<=L:").+?(?=")/i', $_POST['authors'], $author);

                if (!empty($_POST['title'])) {

                    $title_words = str_word_count($_POST['title'], 1);

                    $strlens = array();

                    while (list($key, $word) = each($title_words)) {

                        $strlens[$word] = strlen($word);
                    }

                    arsort($strlens);
                    $title_word = key($strlens);
                }

                if (!empty($_POST['year'])) {
                    if (is_numeric($_POST['year'])) {
                        $year = $_POST['year'];
                    } else {
                        $year = date('Y', strtotime($_POST['year']));
                    }
                }

                if (!empty($_POST['authors']))
                    $pubmed_query[] = "$author[0] [AU]";
                if (!empty($_POST['title']))
                    $pubmed_query[] = "$title_word [TI]";
                if (!empty($_POST['year']))
                    $pubmed_query[] = $year . " [DP]";
                if (!empty($_POST['volume']))
                    $pubmed_query[] = "$_POST[volume] [VI]";
                if (!empty($_POST['pages']))
                    $pubmed_query[] = "$_POST[pages] [PG]";

                $pubmed_query = join(" AND ", $pubmed_query);
                $pubmed_query = urlencode($pubmed_query);

                if (!empty($_POST['doi']))
                    $pubmed_query = $_POST['doi'] . '[AID]';

                $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=$pubmed_query&usehistory=y&retstart=0&retmax=1&sort=&tool=I,Librarian&email=i.librarian.software@gmail.com";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server. Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                $count = $xml->Count;
                if ($count == 1)
                    $pmid = $xml->IdList->Id;
            }

            if (!empty($pmid)) {

                ##########	open efetch, read xml	##########

                $xml_string = '';

                $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id=$pmid&tool=I,Librarian&email=i.librarian.software@gmail.com";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server. Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                foreach ($xml->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $articleid) {

                    preg_match('/10\.\d{4}\/\S+/i', $articleid, $doi_match);

                    if (count($doi_match) > 0) {
                        $_POST['doi'] = current($doi_match);
                        break;
                    }
                }

                $uid_array = array();
                $uid_array = $_POST['uid'];
                $_POST['uid'] = array_values(array_filter(array_unique(array_merge($uid_array, array("PMID:$pmid")))));

                $url_array = array();
                $url_array = $_POST['url'];
                $_POST['url'] = array_values(array_filter(array_unique(array_merge($url_array, array("http://www.pubmed.org/$pmid")))));

                $title = $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;
                if (!empty($title))
                    $_POST['title'] = (string) $title;

                $xml_abstract = $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;

                if (!empty($xml_abstract)) {
                    foreach ($xml_abstract as $mini_ab) {
                        foreach ($mini_ab->attributes() as $a => $b) {
                            if ($a == 'Label')
                                $mini_ab = $b . ": " . $mini_ab;
                        }
                        $abstract_array[] = "$mini_ab";
                    }
                    $_POST['abstract'] = implode(' ', $abstract_array);
                }

                $affiliation = $xml->PubmedArticle->MedlineCitation->Article->Affiliation;
                if (!empty($affiliation))
                    $_POST['affiliation'] = (string) $affiliation;

                $secondary_title = $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;
                if (!empty($secondary_title))
                    $_POST['secondary_title'] = (string) $secondary_title;

                $day = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day;
                $month = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month;
                $year = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;

                if (empty($year)) {
                    $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->MedlineDate;
                    preg_match('/\d{4}/', $year, $year_match);
                    $year = $year_match[0];
                }

                $_POST['year'] = '';
                if (!empty($year)) {
                    if (empty($day))
                        $day = '01';
                    if (empty($month))
                        $month = '01';
                    $_POST['year'] = date('Y-m-d', strtotime($day . '-' . $month . '-' . $year));
                }

                $volume = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;
                if (!empty($volume))
                    $_POST['volume'] = (string) $volume;

                $issue = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;
                if (!empty($issue))
                    $_POST['issue'] = (string) $issue;

                $pages = $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;
                if (!empty($pages))
                    $_POST['pages'] = (string) $pages;

                $journal = $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;
                if (!empty($journal))
                    $_POST['journal'] = (string) $journal;

                $authors = $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

                $name_array = array ();
                if (!empty($authors)) {
                    foreach ($authors as $author) {
                        $name_array[] = 'L:"' . $author->LastName . '",F:"' . $author->ForeName . '"';
                    }
                }

                $mesh = $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

                $mesh_array = array ();
                if (!empty($mesh)) {
                    foreach ($mesh as $meshheading) {
                        $mesh_array[] = $meshheading->DescriptorName;
                    }
                }

                if (count($name_array) > 0)
                    $_POST['authors'] = join(";", $name_array);
                if (count($mesh_array) > 0)
                    $_POST['keywords'] = join(" / ", $mesh_array);
            } else {
                $error = "Error! Unique record not found in PubMed.";
            }
        }

        if ($_POST['database'] == 'nasaads') {

            if (empty($nasa_id) && empty($doi)) {

                $lookfor_query = array();

                if (!empty($_POST['authors']))
                    preg_match('/(?<=L:").+?(?=")/i', $_POST['authors'], $author);

                if (!empty($_POST['title'])) {

                    $title_words = str_word_count($_POST['title'], 1);

                    $strlens = array();

                    while (list($key, $word) = each($title_words)) {

                        $strlens[$word] = strlen($word);
                    }

                    arsort($strlens);
                    $title_word = key($strlens);
                }

                if (!empty($_POST['year'])) {
                    if (is_numeric($_POST['year'])) {
                        $year = $_POST['year'];
                    } else {
                        $year = date('Y', strtotime($_POST['year']));
                    }
                }

                if (!empty($_POST['authors']))
                    $lookfor_query[] = "author=" . urlencode($author[0]) . "&aut_req=YES";
                if (!empty($_POST['title']))
                    $lookfor_query[] = "title=" . urlencode($title_word) . "&ttl_req=YES";
                if (!empty($_POST['year']))
                    $lookfor_query[] = "start_year=" . urlencode($year);
                if (!empty($_POST['volume']))
                    $lookfor_query[] = "volume=" . urlencode($_POST['volume']);
                if (!empty($_POST['pages'])) {
                    $pages = explode("-", $_POST['pages']);
                    $first_page = $pages[0];
                    $pages = null;
                    $lookfor_query[] = "page=" . urlencode($first_page);
                }

                $lookfor_query = join("&", $lookfor_query);

                $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?" . $lookfor_query . "&data_type=XML&return_req=no_params&start_nr=1&nr_to_return=1";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
                
                if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server. Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                foreach ($xml->attributes() as $a => $b) {

                    if ($a == 'selected') {
                        $count = (string) $b;
                        break;
                    }
                }
            } else {

                if (!empty($doi)) {
                    $lookfor = 'doi=' . urlencode($doi);
                    $_POST['doi'] = $doi;
                }
                if (!empty($nasa_id))
                    $lookfor = 'bibcode=' . urlencode($nasa_id);

                ############ NASA ADS ##############

                $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?" . $lookfor . "&data_type=XML&return_req=no_params&start_nr=1&nr_to_return=1";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server. Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                foreach ($xml->attributes() as $a => $b) {

                    if ($a == 'selected') {
                        $count = (string) $b;
                        break;
                    }
                }
            }

            if ($count == 1) {

                $record = $xml->record;

                $bibcode = (string) $record->bibcode;
                
                $_POST['title'] = (string) $record->title;

                $journal = (string) $record->journal;
                if (strstr($journal, ","))
                    $_POST['secondary_title'] = substr($journal, 0, strpos($journal, ','));

                $eprintid = (string) $record->eprintid;
                if (!empty($eprintid))
                    $eprintid = substr($eprintid, strpos($eprintid, ":") + 1);
                if (strstr($journal, "arXiv"))
                    $eprintid = substr($journal, strpos($journal, ":") + 1);

                $doi = (string) $record->DOI;
                if (!empty($doi))
                    $_POST['doi'] = $doi;

                $volume = (string) $record->volume;
                if (!empty($volume))
                    $_POST['volume'] = $volume;
                
                $pages = (string) $record->page;
                if (!empty($pages))
                    $_POST['pages'] = $pages;
                
                $last_page = (string) $record->lastpage;
                if (!empty($last_page))
                    $_POST['pages'] = $_POST['pages'] . '-' . $last_page;

                $affiliation = (string) $record->affiliation;
                if (!empty($affiliation))
                    $_POST['affiliation'] = $affiliation;

                $year = (string) $record->pubdate;
                if (!empty($year))
                    $_POST['year'] = date('Y-m-d', strtotime($year));

                $abstract = (string) $record->abstract;
                if (!empty($abstract))
                    $_POST['abstract'] = $abstract;
                
                $nasa_url = $record->url;

                foreach ($record->link as $links) {

                    foreach ($links->attributes() as $a => $b) {

                        if ($a == 'type' && $b == 'EJOURNAL') {
                            $ejournal_url = (string) $links->url;
                        } elseif ($a == 'type' && $b == 'PREPRINT') {
                            $preprint_url = (string) $links->url;
                        } elseif ($a == 'type' && $b == 'GIF') {
                            $gif_url = (string) $links->url;
                        }
                    }
                }

                $authors = $record->author;

                if (!empty($authors)) {

                    foreach ($authors as $author) {
                        $author_array = explode (",", $author);
                        $name_array[] = 'L:"'.trim($author_array[0]).'",F:"'.trim($author_array[1]).'"';
                    }
                }

                if (count($name_array) > 0)
                    $_POST['authors'] = join(";", $name_array);

                $keywords = $record->keywords;

                if (!empty($keywords)) {

                    foreach ($keywords as $keyword) {

                        $keywords_array[] = $keyword->keyword;
                    }
                }

                if (count($keywords_array) > 0)
                    $_POST['keywords'] = join(" / ", $keywords_array);

                $uid_array = array();
                if (!empty($bibcode))
                    $uid_array[] = "NASAADS:$bibcode";
                if (!empty($eprintid))
                    $uid_array[] = "ARXIV:$eprintid";
                $_POST['uid'] = array_values(array_filter(array_unique(array_merge($_POST['uid'], $uid_array))));

                $url_array = array();
                $url_array[] = $nasa_url;
                if (!empty($eprintid))
                    $url_array[] = "http://arxiv.org/abs/$eprintid";
                $_POST['url'] = array_values(array_filter(array_unique(array_merge($_POST['url'], $url_array))));
            } else {
                $error = "Error! Unique record not found in NASA ADS.";
            }
        }

        if ($_POST['database'] == 'crossref') {
            
            if (empty($doi)) {
                
                $lookfor_query = array();

                if (!empty($_POST['authors']))
                    preg_match('/(?<=L:").+?(?=")/i', $_POST['authors'], $author);

                if (!empty($_POST['year'])) {
                    if (is_numeric($_POST['year'])) {
                        $year = $_POST['year'];
                    } else {
                        $year = date('Y', strtotime($_POST['year']));
                    }
                }

                if (!empty($author[0]))
                    $lookfor_query[] = $author[0];
                if (!empty($_POST['title']))
                    $lookfor_query[] = $_POST['title'];
                if (!empty($year))
                    $lookfor_query[] = $year;
                if (!empty($_POST['volume']))
                    $lookfor_query[] = $_POST['volume'];
                if (!empty($_POST['pages'])) {
                    $pages = explode("-", $_POST['pages']);
                    $first_page = $pages[0];
                    $pages = null;
                    $lookfor_query[] = $first_page;
                }

                $lookfor_query = join(" ", $lookfor_query);
                $lookfor_query = preg_replace("/[^a-zA-Z0-9]/", " ", $lookfor_query);
                $lookfor_query = urlencode($lookfor_query);

                $request_url = "http://crossref.org/sigg/sigg/FindWorks?version=1&access=i.librarian.software@gmail.com&expression=".$lookfor_query;

                if (!empty($proxy_name)) {

                    $proxy_fp = @fsockopen($proxy_name, $proxy_port);

                    if ($proxy_fp) {

                        $result = '';

                        fputs($proxy_fp, "GET $request_url HTTP/1.0\r\nHost: $proxy_name\r\n");
                        fputs($proxy_fp, "User-Agent: \"$_SERVER[HTTP_USER_AGENT]\"\r\n");
                        fputs($proxy_fp, "Proxy-Authorization: Basic ". base64_encode ("$proxy_username:$proxy_password")."\r\n\r\n");

                        while(!feof($proxy_fp)) {
                            $result .= fgets($proxy_fp, 128);
                        }

                        fclose($proxy_fp);
                    }
                } else {
                    $result = file_get_contents($request_url);
                }

                $result = json_decode($result);
                if (count($result) == 1) $doi = $result[0]->doi;
                
                if (empty($doi)) $error = "Error! Unique record not found in Crossref.";
            }
            
            if (!empty($doi)) {

                $request_url = "http://www.crossref.org/openurl/?id=doi:" . urlencode($doi) . "&noredirect=true&pid=i.librarian.software@gmail.com";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server. Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                $resolved = false;

                if (!empty($xml)) {

                    $record = $xml->query_result->body->query;

                    foreach ($record->attributes() as $a => $b) {

                        if ($a == 'status' && $b == 'resolved') {
                            $resolved = true;
                            break;
                        }
                    }
                }

                if ($resolved) {

                    $_POST['doi'] = $doi;
                    
                    $secondary_title = (string) $record->journal_title;
                    if (!empty($secondary_title)) $_POST['secondary_title'] = $secondary_title;
                    
                    $year = (string) $record->year;
                    if (!empty($year)) $_POST['year'] = $year . '-01-01';
                    
                    $volume = (string) $record->volume;
                    if (!empty($volume)) $_POST['volume'] = $volume;
                    
                    $issue = (string) $record->issue;
                    if (!empty($issue)) $_POST['issue'] = $issue;
                    
                    $pages = (string) $record->first_page;
                    if (!empty($pages)) $_POST['pages'] = $pages;
                    
                    $last_page = (string) $record->last_page;
                    
                    if (!empty($last_page))
                        $_POST['pages'] = $_POST['pages'] . "-" . $last_page;
                    
                    $title = (string) $record->article_title;
                    if (!empty($title)) $_POST['title'] = $title;

                    foreach ($record->contributors->contributor as $contributor) {

                        $authors1 = $contributor->surname;
                        $authors2 = $contributor->given_name;
                        if (!empty($authors1)) $authors[] = 'L:"' . $authors1 . '",F:"' . $authors2 . '"';
                    }
                    if (count($authors) > 0) $_POST['authors'] = join(";", $authors);
                    $authors = null;
                } else {
                    $error = "Error! Unique record not found in CrossRef. DOI required.";
                }
            }
        }
    }

##########	reference updating	##########

    if (!empty($_POST['title']) && isset($_POST['form_sent'])) {

        ##########	remove line breaks from certain POST values	##########

        $order = array("\r\n", "\n", "\r");
        $keys = array('authors', 'title', 'abstract', 'keywords');

        while (list($key, $field) = each($keys)) {

            if (!empty($_POST[$field])) {

                $_POST[$field] = str_replace($order, ' ', $_POST[$field]);
            }
        }

        ##########	record publication data, table library	##########

        database_connect($database_path, 'library');

        $query = "UPDATE library SET authors=:authors, title=:title, journal=:journal, year=:year,
				abstract=:abstract, uid=:uid, volume=:volume, pages=:pages,
				secondary_title=:secondary_title, editor=:editor, url=:url,
				reference_type=:reference_type, publisher=:publisher, place_published=:place_published,
				keywords=:keywords, doi=:doi, authors_ascii=:authors_ascii,
				title_ascii=:title_ascii, abstract_ascii=:abstract_ascii,
                                custom1=:custom1, custom2=:custom2, custom3=:custom3, custom4=:custom4, bibtex=:bibtex,
				affiliation=:affiliation, issue=:issue, modified_by=:modified_by,
                                modified_date=strftime('%Y-%m-%dT%H:%M:%S', 'now', 'localtime')
			WHERE id=:id";

        $stmt = $dbHandle->prepare($query);

        $stmt->bindParam(':id', $file_id, PDO::PARAM_INT);
        $stmt->bindParam(':authors', $authors, PDO::PARAM_STR);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':journal', $journal, PDO::PARAM_STR);
        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':abstract', $abstract, PDO::PARAM_STR);
        $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
        $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
        $stmt->bindParam(':pages', $pages, PDO::PARAM_STR);
        $stmt->bindParam(':secondary_title', $secondary_title, PDO::PARAM_STR);
        $stmt->bindParam(':editor', $editor, PDO::PARAM_STR);
        $stmt->bindParam(':url', $url, PDO::PARAM_STR);
        $stmt->bindParam(':reference_type', $reference_type, PDO::PARAM_STR);
        $stmt->bindParam(':publisher', $publisher, PDO::PARAM_STR);
        $stmt->bindParam(':place_published', $place_published, PDO::PARAM_STR);
        $stmt->bindParam(':keywords', $keywords, PDO::PARAM_STR);
        $stmt->bindParam(':doi', $doi, PDO::PARAM_STR);
        $stmt->bindParam(':authors_ascii', $authors_ascii, PDO::PARAM_STR);
        $stmt->bindParam(':title_ascii', $title_ascii, PDO::PARAM_STR);
        $stmt->bindParam(':abstract_ascii', $abstract_ascii, PDO::PARAM_STR);
        $stmt->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
        $stmt->bindParam(':issue', $issue, PDO::PARAM_STR);
        $stmt->bindParam(':custom1', $custom1, PDO::PARAM_STR);
        $stmt->bindParam(':custom2', $custom2, PDO::PARAM_STR);
        $stmt->bindParam(':custom3', $custom3, PDO::PARAM_STR);
        $stmt->bindParam(':custom4', $custom4, PDO::PARAM_STR);
        $stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);
        $stmt->bindParam(':modified_by', $modified_by, PDO::PARAM_INT);

        $file_id = (integer) $_POST['file'];

        if (empty($_POST['authors'])) {

            $authors = '';
            $authors_ascii = '';
        } else {

            $authors = trim($_POST['authors']);
            $authors_ascii = utf8_deaccent($authors);
        }

        $title = trim($_POST['title']);
        $title_ascii = utf8_deaccent($title);

        empty($_POST['journal']) ? $journal = '' : $journal = trim($_POST['journal']);

        empty($_POST['secondary_title']) ? $secondary_title = '' : $secondary_title = trim($_POST['secondary_title']);

        empty($_POST['year']) ? $year = '' : $year = trim($_POST['year']);

        if (empty($_POST['abstract'])) {

            $abstract = '';
            $abstract_ascii = '';
        } else {

            $abstract = trim($_POST['abstract']);
            $abstract_ascii = utf8_deaccent($abstract);
        }

        empty($_POST['uid'][0]) ? $uid = '' : $uid = implode('|', array_filter($_POST['uid']));

        empty($_POST['volume']) ? $volume = '' : $volume = trim($_POST['volume']);

        empty($_POST['issue']) ? $issue = '' : $issue = trim($_POST['issue']);

        empty($_POST['pages']) ? $pages = '' : $pages = trim($_POST['pages']);

        empty($_POST['editor']) ? $editor = '' : $editor = trim($_POST['editor']);

        empty($_POST['url'][0]) ? $url = '' : $url = implode('|', array_filter($_POST['url']));

        empty($_POST['reference_type']) ? $reference_type = 'article' : $reference_type = trim($_POST['reference_type']);

        empty($_POST['publisher']) ? $publisher = '' : $publisher = trim($_POST['publisher']);

        empty($_POST['place_published']) ? $place_published = '' : $place_published = trim($_POST['place_published']);

        empty($_POST['keywords']) ? $keywords = '' : $keywords = trim($_POST['keywords']);

        empty($_POST['affiliation']) ? $affiliation = '' : $affiliation = trim($_POST['affiliation']);

        empty($user_id) ? $modified_by = '' : $modified_by = (integer) $user_id;

        empty($_POST['doi']) ? $doi = '' : $doi = trim($_POST['doi']);

        empty($_POST['custom1']) ? $custom1 = '' : $custom1 = trim($_POST['custom1']);

        empty($_POST['custom2']) ? $custom2 = '' : $custom2 = trim($_POST['custom2']);

        empty($_POST['custom3']) ? $custom3 = '' : $custom3 = trim($_POST['custom3']);

        empty($_POST['custom4']) ? $custom4 = '' : $custom4 = trim($_POST['custom4']);

        empty($_POST['bibtex']) ? $bibtex = '' : $bibtex = trim($_POST['bibtex']);

        if (!empty($title))
            $database_update = $stmt->execute();

        if ($database_update == false)
            $error = "Error! The database has not been updated.";

        $stmt = null;
        $dbHandle = null;

        if (empty($error))
            die('title:'.$title);
    } elseif (isset($_POST['form_sent'])) {
        $error = 'Error! Title is mandatory.';
    }

    if (!empty($error))
        die($error);

##########	read reference data	##########

    database_connect($database_path, 'library');

    $file_query = $dbHandle->quote($_GET['file']);
    $user_query = $dbHandle->quote($user);

    $record = $dbHandle->query("SELECT * FROM library WHERE id=$file_query LIMIT 1");
    $paper = $record->fetch(PDO::FETCH_ASSOC);

    $paper_urls = array();
    if (!empty($paper['url']))
        $paper_urls = explode('|', $paper['url']);

    $paper_uids = array();
    if (!empty($paper['uid']))
        $paper_uids = explode('|', $paper['uid']);

    if (empty($paper['bibtex'])) {
        $bibtex_author = substr($paper['authors'], 3);
        $bibtex_author = substr($bibtex_author, 0, strpos($bibtex_author, '"'));
        if (empty($bibtex_author))
            $bibtex_author = 'unknown';

        $bibtex_year_array = explode('-', $paper['year']);
        $bibtex_year = '0000';
        if (!empty($bibtex_year_array[0]))
            $bibtex_year = $bibtex_year_array[0];

        $bibtex_sugg = utf8_deaccent($bibtex_author) . '-' . $bibtex_year . '-ID' . $paper['id'];
    }

    $record = null;
    $dbHandle = null;
    ?>
    <form id="metadataform" enctype="multipart/form-data" action="edit.php" method="POST">
        <input type="hidden" name="form_sent" value="1">
        <input type="hidden" name="file" value="<?php print htmlspecialchars($paper['id']) ?>">
        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;margin-top: 0px;margin-bottom:1px">
            <tr>
                <td class="threedleft">
                    <button id="savemetadata">Save</button>
                </td>
                <td class="threedright">
                    <button id="autoupdate" title="Attempt to fetch more data from Internet repositories" style="float:left">Update</button>
                    <table style="float:left">
                        <tr>
                            <td class="select_span" style="line-height:22px">
                                <input type="radio" style="display:none" name="database" value="pubmed" checked>
                                <span class="ui-icon ui-icon-radio-on" style="float:left;margin-top:3px">
                                </span>PubMed
                            </td>
                            <td class="select_span" style="line-height:22px">
                                <input type="radio" style="display:none" name="database" value="nasaads">
                                <span class="ui-icon ui-icon-radio-off" style="float:left;margin-top:3px">
                                </span>NASA ADS
                            </td>
                            <td class="select_span" style="line-height:22px">
                                <input type="radio" style="display:none" name="database" value="crossref">
                                <span class="ui-icon ui-icon-radio-off" style="float:left;margin-top:3px">
                                </span>CrossRef
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    I, Librarian ID:
                </td>
                <td class="threedright">
    <?php print $paper['id'] ?>
                </td>
            </tr>
            <?php
            if (!empty($paper_uids)) {
                foreach ($paper_uids as $paper_uid) {
                    ?>
                    <tr>
                        <td class="threedleft">
                            Database UID:
                        </td>
                        <td class="threedright">
                            <input type="text" size="80" name="uid[]" style="width: 99%" value="<?php print htmlspecialchars($paper_uid) ?>">
                        </td>
                    </tr>
            <?php
        }
    }
    ?>
            <tr>
                <td class="threedleft">
                    Database UID:
                    <div id="adduidrow" style="float:right;cursor:pointer">+</div>
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="uid[]" style="width: 99%" value="" title="<b>Examples:</b><br>PMID:123456<br>PMCID:123456<br>NASAADS:123456<br>ARXIV:123456">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    DOI:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="doi" style="width: 99%" value="<?php print isset($paper['doi']) ? htmlspecialchars($paper['doi']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    BibTex key:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="bibtex" style="width: 99%" value="<?php print isset($paper['bibtex']) ? htmlspecialchars($paper['bibtex']) : ''  ?>" placeholder="<?php print isset($bibtex_sugg) ? htmlspecialchars($bibtex_sugg) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Title:
                </td>
                <td class="threedright">
                    <textarea name="title" cols="80" rows="2" style="width: 99%"><?php echo isset($paper['title']) ? htmlspecialchars($paper['title']) : '' ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Authors:
                </td>
                <td class="threedright">
                    <div class="author-inputs" style="max-height: 200px;overflow:auto">
                        <?php
                        if (!empty($paper['authors'])) {
                            $array = array();
                            $new_authors = array();
                            $array = explode(';', $paper['authors']);
                            $array = array_filter($array);
                            if (!empty($array)) {
                                foreach ($array as $author) {
                                    $array2 = explode(',', $author);
                                    $last = trim($array2[0]);
                                    $last = substr($array2[0], 3, -1);
                                    $first = trim($array2[1]);
                                    $first = substr($array2[1], 3, -1);
                                    if (!empty($last)) print '<div>Last name: <input type="text" value="' . $last . '">
                                        <span class="ui-icon ui-icon-transfer-e-w flipnames" style="display:inline-block;position:relative;top:2px"></span>
                                        First name: <input type="text" value="' . $first . '"></div>';
                                }
                            }
                        }
                        ?>
                        <div>
                            Last name: <input type="text" value="">
                            <span class="ui-icon ui-icon-transfer-e-w flipnames" style="display:inline-block;position:relative;top:2px"></span>
                            First name: <input type="text" value="">
                            <span class="addauthorrow" style="cursor:pointer">+</span>
                        </div>
                    </div>
                    <input type="hidden" name="authors" value="<?php echo isset($paper['authors']) ? htmlspecialchars($paper['authors']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Affiliation:
                </td>
                <td class="threedright">
                    <textarea cols="80" rows="2" name="affiliation" style="width: 99%"><?php echo isset($paper['affiliation']) ? htmlspecialchars($paper['affiliation']) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Journal abbreviation:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="journal" style="width: 99%" value="<?php print isset($paper['journal']) ? htmlspecialchars($paper['journal']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Secondary title:<br>(journal full name)
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="secondary_title" style="width: 99%" value="<?php print isset($paper['secondary_title']) ? htmlspecialchars($paper['secondary_title']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publication date:
                </td>
                <td class="threedright">
                    <input type="text" size="10" maxlength="10" name="year" value="<?php echo isset($paper['year']) ? htmlspecialchars($paper['year']) : '' ?>"> YYYY-MM-DD
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Volume:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="volume" value="<?php echo isset($paper['volume']) ? htmlspecialchars($paper['volume']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Issue:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="issue" value="<?php echo isset($paper['issue']) ? htmlspecialchars($paper['issue']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Pages:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="pages" value="<?php echo isset($paper['pages']) ? htmlspecialchars($paper['pages']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Abstract:
                </td>
                <td class="threedright">
                    <textarea name="abstract" cols="80" rows="6" style="width: 99%"><?php echo isset($paper['abstract']) ? htmlspecialchars($paper['abstract']) : '' ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Editor:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="editor" style="width: 99%" value="<?php echo isset($paper['editor']) ? htmlspecialchars($paper['editor']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publisher:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="publisher" style="width: 99%" value="<?php echo isset($paper['publisher']) ? htmlspecialchars($paper['publisher']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Place published:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="place_published" style="width: 99%" value="<?php echo isset($paper['place_published']) ? htmlspecialchars($paper['place_published']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Keywords:
                </td>
                <td class="threedright">
                    <textarea name="keywords" cols="80" rows=2 style="width: 99%" title="Reserved for keywords provided by internet databases. For your custom keywords use Categories.<br>Separator: space, forward slash, space &quot; / &quot;"><?php echo isset($paper['keywords']) ? htmlspecialchars($paper['keywords']) : '' ?></textarea>
                </td>
            </tr>
            <?php
            if (!empty($paper_urls)) {
                foreach ($paper_urls as $paper_url) {
                    ?>
                    <tr>
                        <td class="threedleft">
                            URL:
                        </td>
                        <td class="threedright">
                            <input type="text" size="80" name="url[]" style="width: 99%" value="<?php print htmlspecialchars($paper_url) ?>">
                        </td>
                    </tr>
            <?php
        }
    }
    ?>
            <tr>
                <td class="threedleft">
                    URL:
                    <div id="addurlrow" style="float:right;cursor:pointer">+</div>
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="url[]" style="width: 99%" value="">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publication type:
                </td>
                <td class="threedright">
                    <select name="reference_type">
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'article') ? 'selected' : ''  ?>>article</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'book') ? 'selected' : ''  ?>>book</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'chapter') ? 'selected' : ''  ?>>chapter</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'conference') ? 'selected' : ''  ?>>conference</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'manual') ? 'selected' : ''  ?>>manual</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'thesis') ? 'selected' : ''  ?>>thesis</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'patent') ? 'selected' : ''  ?>>patent</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'technical report') ? 'selected' : ''  ?>>technical report</option>
                        <option <?php print (!empty($paper['reference_type']) && $paper['reference_type'] == 'electronic') ? 'selected' : ''  ?>>electronic</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 1:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom1" style="width: 99%" value="<?php print isset($paper['custom1']) ? htmlspecialchars($paper['custom1']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 2:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom2" style="width: 99%" value="<?php print isset($paper['custom2']) ? htmlspecialchars($paper['custom2']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 3:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom3" style="width: 99%" value="<?php print isset($paper['custom3']) ? htmlspecialchars($paper['custom3']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 4:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom4" style="width: 99%" value="<?php print isset($paper['custom4']) ? htmlspecialchars($paper['custom4']) : ''  ?>">
                </td>
            </tr>
        </table>
    </form>
    <?php
} else {
    print 'Super User or User permissions required.';
}
?>