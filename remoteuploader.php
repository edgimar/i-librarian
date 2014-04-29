<?php
include_once 'data.php';
include_once 'functions.php';

if (!empty($_FILES)) {

    if (isset($_GET['proxy_name']))
        $proxy_name = $_GET['proxy_name'];
    if (isset($_GET['proxy_port']))
        $proxy_port = $_GET['proxy_port'];
    if (isset($_GET['proxy_username']))
        $proxy_username = $_GET['proxy_username'];
    if (isset($_GET['proxy_password']))
        $proxy_password = $_GET['proxy_password'];
    if (!empty($_GET['user']))
        $user = $_GET['user'];
    if (!empty($_GET['userID']))
        $userID = $_GET['userID'];

    $database_pubmed = '';
    $database_nasaads = '';
    $database_crossref = '';
    $failed = '';

    if (isset($_GET['database_pubmed']))
        $database_pubmed = $_GET['database_pubmed'];
    if (isset($_GET['database_nasaads']))
        $database_nasaads = $_GET['database_nasaads'];
    if (isset($_GET['database_crossref']))
        $database_crossref = $_GET['database_crossref'];
    if (isset($_GET['failed']))
        $failed = $_GET['failed'];

    database_connect($usersdatabase_path, 'users');
    save_setting($dbHandle, 'batchimport_database_pubmed', $database_pubmed);
    save_setting($dbHandle, 'batchimport_database_nasaads', $database_nasaads);
    save_setting($dbHandle, 'batchimport_database_crossref', $database_crossref);
    save_setting($dbHandle, 'batchimport_failed', $failed);
    $dbHandle = null;

    $user_dir = $temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id();

    session_write_close();

    $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, figure, figures, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, table, tables, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

    $stopwords = explode(', ', $stopwords);

    $patterns = join("\b/ui /\b", $stopwords);
    $patterns = "/\b$patterns\b/ui";
    $patterns = explode(" ", $patterns);

    $order = array("\r\n", "\n", "\r");

    $i = 0;

    if (isset($_FILES['Filedata']) && is_uploaded_file($_FILES['Filedata']['tmp_name'])) {

        $file = $_FILES['Filedata']['tmp_name'];
        $orig_filename = $_FILES['Filedata']['name'];

        $i = $i + 1;

        if (is_readable($file)) {

            $string = '';
            $xml = '';
            $record = '';
            $count = '';
            $url = '';
            $authors = '';
            $authors_array = array();
            $affiliation = '';
            $title = '';
            $abstract = '';
            $secondary_title = '';
            $year = '';
            $volume = '';
            $issue = '';
            $pages = '';
            $last_page = '';
            $journal = '';
            $keywords = '';
            $name_array = array();
            $mesh_array = array();
            $new_file = '';
            $journal = '';
            $addition_date = date('Y-m-d');
            $rating = 2;
            $uid = '';
            $editor = '';
            $reference_type = 'article';
            $publisher = '';
            $place_published = '';
            $doi = '';
            $authors_ascii = '';
            $title_ascii = '';
            $abstract_ascii = '';
            $unpacked_files = array();

            if (file_exists($temp_dir . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt"))
                unlink($temp_dir . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt");

            ##########	extract text from pdf	##########

            system(select_pdftotext() . '"' . $file . '" "' . $temp_dir . DIRECTORY_SEPARATOR . 'librarian_temp' . $i . '.txt"', $ret);

            if (file_exists($temp_dir . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt"))
                $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . "librarian_temp" . $i . ".txt");

            if (empty($string)) {

                if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                    database_connect($database_path, 'library');
                    record_unknown($dbHandle, $string, $database_path, $file, $userID);

                    $put = basename($orig_filename) . ": Recorded as unknown. Full text not indexed (copying disallowed).<br>";
                } else {

                    $put = basename($orig_filename) . ": copying disallowed.<br>";
                }
            } else {

                $string = preg_replace('/[^[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}]/', ' ', $string);
                $string = str_replace($order, ' ', $string);
                $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                $replace = '-';
                $string = str_replace($order, $replace, $string);

                preg_match_all('/10\.\d{4}\/\S+/ui', $string, $doi, PREG_PATTERN_ORDER);

                if (count($doi[0]) < 1) {

                    if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                        $string = preg_replace($patterns, ' ', $string);
                        $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/u', ' ', $string);
                        $string = preg_replace('/\s{2,}/u', " ", $string);

                        $fulltext_array = array();
                        $fulltext_unique = array();

                        $fulltext_array = explode(" ", $string);
                        $fulltext_unique = array_unique($fulltext_array);
                        $string = implode(" ", $fulltext_unique);

                        database_connect($database_path, 'library');
                        record_unknown($dbHandle, $string, $database_path, $file, $userID);

                        $put = basename($orig_filename) . ": Recorded as unknown. DOI not found.<br>";
                    } else {

                        $put = basename($orig_filename) . ": DOI not found.<br>";
                    }
                } else {

                    $doi = $doi[0][0];

                    if (substr($doi, -1) == ')' || substr($doi, -1) == ']') {
                        preg_match_all('/(.)(doi:\s?)?(10\.\d{4}\/\S+)/ui', $string, $doi2, PREG_PATTERN_ORDER);
                        if (substr($doi, -1) == ')' && $doi2[1][0] == '(')
                            $doi = substr($doi, 0, -1);
                        if (substr($doi, -1) == ']' && $doi2[1][0] == '[')
                            $doi = substr($doi, 0, -1);
                    }

                    $title = '';

                    if (isset($_GET['database_pubmed']) && $_GET['database_pubmed'] == '1') {

                        ##########	open esearch, fetch PMID	##########

                        $pmid = '';

                        $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=" . $doi . "[AID]&usehistory=y&retstart=&retmax=1&sort=&tool=I,Librarian&email=i.librarian.software@gmail.com";

                        $xml = proxy_simplexml_load_file($request_url, $_GET['proxy_name'], $_GET['proxy_port'], $_GET['proxy_username'], $_GET['proxy_password']);

                        if (empty($xml))
                            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
                        Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                        $count = $xml->Count;
                        if ($count == 1)
                            $pmid = $xml->IdList->Id;

                        if (!empty($pmid)) {

                            ##########	open efetch, read xml	##########

                            $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id=" . $pmid . "&tool=I,Librarian&email=i.librarian.software@gmail.com";

                            $xml = proxy_simplexml_load_file($request_url, $_GET['proxy_name'], $_GET['proxy_port'], $_GET['proxy_username'], $_GET['proxy_password']);

                            if (empty($xml))
                                die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
                            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                            $uid = 'PMID:' . $pmid;

                            $url = "http://www.pubmed.org/$pmid";

                            $title = (string) $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

                            $abstract_array = array();

                            $xml_abstract = $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;

                            if (!empty($xml_abstract)) {
                                foreach ($xml_abstract as $mini_ab) {
                                    foreach ($mini_ab->attributes() as $a => $b) {
                                        if ($a == 'Label')
                                            $mini_ab = $b . ": " . $mini_ab;
                                    }
                                    $abstract_array[] = "$mini_ab";
                                }
                                $abstract = implode(' ', $abstract_array);
                            }

                            $secondary_title = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;

                            $day = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day;
                            $month = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month;
                            $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;

                            if (empty($year)) {
                                $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->MedlineDate;
                                preg_match('/\d{4}/', $year, $year_match);
                                $year = $year_match[0];
                            }

                            if (!empty($year)) {
                                if (empty($day))
                                    $day = '01';
                                if (empty($month))
                                    $month = '01';
                                $year = date('Y-m-d', strtotime($day . '-' . $month . '-' . $year));
                            }

                            $volume = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;

                            $issue = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;

                            $pages = (string) $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

                            $journal = (string) $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;

                            $affiliation = (string) $xml->PubmedArticle->MedlineCitation->Article->Affiliation;

                            $authors = $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

                            $name_array = array();
                            if (!empty($authors)) {
                                foreach ($authors as $author) {
                                    $name_array[] = 'L:"' . $author->LastName . '",F:"' . $author->ForeName . '"';
                                }
                            }
                            if (isset($name_array))
                                $authors = join(";", $name_array);

                            $mesh = $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

                            if (!empty($mesh)) {
                                foreach ($mesh as $meshheading) {
                                    $mesh_array[] = $meshheading->DescriptorName;
                                }
                            }
                            if (isset($mesh_array))
                                $keywords = join(" / ", $mesh_array);
                        }
                    }

                    if (isset($_GET['database_nasaads']) && $_GET['database_nasaads'] == '1' && empty($title)) {

                        ############ NASA ADS ##############

                        $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?doi=" . $doi . "&data_type=XML&return_req=no_params&start_nr=1&nr_to_return=1";

                        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                        if (empty($xml))
                            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
                        Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                        foreach ($xml->attributes() as $a => $b) {

                            if ($a == 'selected') {
                                $count = $b;
                                break;
                            }
                        }

                        if ($count == 1) {

                            $record = $xml->record;

                            $bibcode = (string) $record->bibcode;
                            $title = (string) $record->title;

                            $journal = (string) $record->journal;
                            if (strstr($journal, ","))
                                $secondary_title = substr($journal, 0, strpos($journal, ','));

                            $eprintid = (string) $record->eprintid;
                            if (!empty($eprintid))
                                $eprintid = substr($eprintid, strpos($eprintid, ":") + 1);
                            if (strstr($journal, "arXiv"))
                                $eprintid = substr($journal, strpos($journal, ":") + 1);

                            $volume = (string) $record->volume;
                            $pages = (string) $record->page;

                            $affiliation = $record->affiliation;

                            $year = (string) $record->pubdate;
                            $year = date('Y-m-d', strtotime($year));

                            $abstract = (string) $record->abstract;
                            $nasa_url = (string) $record->url;

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

                            $name_array = array();

                            if (!empty($authors)) {

                                foreach ($authors as $author) {
                                    $author_array = explode(",", $author);
                                    $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($author_array[1]) . '"';
                                }
                            }

                            if (isset($name_array))
                                $authors = join(";", $name_array);

                            $keywords = $record->keywords;

                            if (!empty($keywords)) {

                                foreach ($keywords as $keyword) {

                                    $keywords_array[] = $keyword->keyword;
                                }
                            }

                            if (isset($keywords_array))
                                $keywords = join(" / ", $keywords_array);

                            $uid_array = array();
                            if (!empty($bibcode))
                                $uid_array[] = "NASAADS:$bibcode";
                            if (!empty($eprintid))
                                $uid_array[] = "ARXIV:$eprintid";
                            $uid = join("|", $uid_array);

                            $url_array = array();
                            $url_array[] = $nasa_url;
                            if (!empty($eprintid))
                                $url_array[] = "http://arxiv.org/abs/$eprintid";
                            $url = join("|", $url_array);
                        }
                    }

                    if (isset($_GET['database_crossref']) && $_GET['database_crossref'] == '1' && empty($title)) {

                        ############ CrossRef ##############

                        $request_url = "http://www.crossref.org/openurl/?id=doi:" . $doi . "&noredirect=true&pid=i.librarian.software@gmail.com";

                        $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                        if (empty($xml))
                            die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
                        Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');

                        $record = $xml->query_result->body->query;

                        $resolved = false;

                        foreach ($record->attributes() as $a => $b) {

                            if ($a == 'status' && $b == 'resolved') {
                                $resolved = true;
                                break;
                            }
                        }

                        if ($resolved) {

                            $secondary_title = (string) $record->journal_title;
                            $year = (string) $record->year;
                            $year = $year . '-01-01';
                            $volume = (string) $record->volume;
                            $pages = (string) $record->first_page;
                            $last_page = (string) $record->last_page;
                            if (!empty($last_page))
                                $pages = $pages . "-" . $last_page;
                            $title = (string) $record->article_title;
                            $authors = array();
                            foreach ($record->contributors->contributor as $contributor) {
                                $authors1 = (string) $contributor->surname;
                                $authors2 = (string) $contributor->given_name;
                                $authors[] = 'L:"' . $authors1 . '",F:"' . $authors2 . '"';
                            }
                            $authors = join(";", $authors);
                        }
                    }

                    //TRY AGAIN WITH DOI ONE CHARACTER SHORTER
                    if (empty($title)) {

                        $doi = substr($doi, 0, -1);

                        if (isset($_GET['database_pubmed']) && $_GET['database_pubmed'] == '1') {

                            ##########	open esearch, fetch PMID	##########

                            $pmid = '';

                            $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=" . $doi . "[AID]&usehistory=y&retstart=&retmax=1&sort=&tool=I,Librarian&email=i.librarian.software@gmail.com";

                            $xml = proxy_simplexml_load_file($request_url, $_GET['proxy_name'], $_GET['proxy_port'], $_GET['proxy_username'], $_GET['proxy_password']);

                            $count = $xml->Count;
                            if ($count == 1)
                                $pmid = $xml->IdList->Id;

                            if (!empty($pmid)) {

                                ##########	open efetch, read xml	##########

                                $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id=" . $pmid . "&tool=I,Librarian&email=i.librarian.software@gmail.com";

                                $xml = proxy_simplexml_load_file($request_url, $_GET['proxy_name'], $_GET['proxy_port'], $_GET['proxy_username'], $_GET['proxy_password']);

                                $uid = 'PMID:' . $pmid;

                                $url = "http://www.pubmed.org/$pmid";

                                $title = (string) $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

                                $abstract_array = array();

                                $xml_abstract = $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;

                                if (!empty($xml_abstract)) {
                                    foreach ($xml_abstract as $mini_ab) {
                                        foreach ($mini_ab->attributes() as $a => $b) {
                                            if ($a == 'Label')
                                                $mini_ab = $b . ": " . $mini_ab;
                                        }
                                        $abstract_array[] = "$mini_ab";
                                    }
                                    $abstract = implode(' ', $abstract_array);
                                }

                                $secondary_title = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;

                                $day = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day;
                                $month = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month;
                                $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;

                                if (empty($year)) {
                                    $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->MedlineDate;
                                    preg_match('/\d{4}/', $year, $year_match);
                                    $year = $year_match[0];
                                }

                                if (!empty($year)) {
                                    if (empty($day))
                                        $day = '01';
                                    if (empty($month))
                                        $month = '01';
                                    $year = date('Y-m-d', strtotime($day . '-' . $month . '-' . $year));
                                }

                                $volume = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;

                                $issue = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;

                                $pages = (string) $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

                                $journal = (string) $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;

                                $affiliation = (string) $xml->PubmedArticle->MedlineCitation->Article->Affiliation;

                                $authors = $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

                                $name_array = array();
                                if (!empty($authors)) {
                                    foreach ($authors as $author) {
                                        $name_array[] = 'L:"' . $author->LastName . '",F:"' . $author->ForeName . '"';
                                    }
                                }
                                if (isset($name_array))
                                    $authors = join(";", $name_array);

                                $mesh = $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

                                if (!empty($mesh)) {
                                    foreach ($mesh as $meshheading) {
                                        $mesh_array[] = $meshheading->DescriptorName;
                                    }
                                }

                                if (isset($mesh_array))
                                    $keywords = join(" / ", $mesh_array);
                            }
                        }

                        if (isset($_GET['database_nasaads']) && $_GET['database_nasaads'] == '1' && empty($title)) {

                            ############ NASA ADS ##############

                            $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?doi=" . $doi . "&data_type=XML&return_req=no_params&start_nr=1&nr_to_return=1";

                            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                            foreach ($xml->attributes() as $a => $b) {

                                if ($a == 'selected') {
                                    $count = $b;
                                    break;
                                }
                            }

                            if ($count == 1) {

                                $record = $xml->record;

                                $bibcode = (string) $record->bibcode;
                                $title = (string) $record->title;

                                $journal = (string) $record->journal;
                                if (strstr($journal, ","))
                                    $secondary_title = substr($journal, 0, strpos($journal, ','));

                                $eprintid = (string) $record->eprintid;
                                if (!empty($eprintid))
                                    $eprintid = substr($eprintid, strpos($eprintid, ":") + 1);
                                if (strstr($journal, "arXiv"))
                                    $eprintid = substr($journal, strpos($journal, ":") + 1);

                                $volume = (string) $record->volume;
                                $pages = (string) $record->page;

                                $affiliation = $record->affiliation;

                                $year = (string) $record->pubdate;
                                $year = date('Y-m-d', strtotime($year));

                                $abstract = (string) $record->abstract;
                                $nasa_url = (string) $record->url;

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

                                $name_array = array();

                                if (!empty($authors)) {

                                    foreach ($authors as $author) {
                                        $author_array = explode(",", $author);
                                        $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($author_array[1]) . '"';
                                    }
                                }

                                if (isset($name_array))
                                    $authors = join(";", $name_array);

                                $keywords = $record->keywords;

                                if (!empty($keywords)) {

                                    foreach ($keywords as $keyword) {

                                        $keywords_array[] = $keyword->keyword;
                                    }
                                }

                                if (isset($keywords_array))
                                    $keywords = join(" / ", $keywords_array);

                                $uid_array = array();
                                if (!empty($bibcode))
                                    $uid_array[] = "NASAADS:$bibcode";
                                if (!empty($eprintid))
                                    $uid_array[] = "ARXIV:$eprintid";
                                $uid = join("|", $uid_array);

                                $url_array = array();
                                $url_array[] = $nasa_url;
                                if (!empty($eprintid))
                                    $url_array[] = "http://arxiv.org/abs/$eprintid";
                                $url = join("|", $url_array);
                            }
                        }

                        if (isset($_GET['database_crossref']) && $_GET['database_crossref'] == '1' && empty($title)) {

                            ############ CrossRef ##############

                            $request_url = "http://www.crossref.org/openurl/?id=doi:" . $doi . "&noredirect=true&pid=i.librarian.software@gmail.com";

                            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);

                            $record = $xml->query_result->body->query;

                            $resolved = false;

                            foreach ($record->attributes() as $a => $b) {

                                if ($a == 'status' && $b == 'resolved') {
                                    $resolved = true;
                                    break;
                                }
                            }

                            if ($resolved) {

                                $secondary_title = (string) $record->journal_title;
                                $year = (string) $record->year;
                                $year = $year . '-01-01';
                                $volume = (string) $record->volume;
                                $pages = (string) $record->first_page;
                                $last_page = (string) $record->last_page;
                                if (!empty($last_page))
                                    $pages = $pages . "-" . $last_page;
                                $title = (string) $record->article_title;
                                $authors = array();
                                foreach ($record->contributors->contributor as $contributor) {
                                    $authors1 = (string) $contributor->surname;
                                    $authors2 = (string) $contributor->given_name;
                                    $authors[] = 'L:"' . $authors1 . '",F:"' . $authors2 . '"';
                                }
                                $authors = join(";", $authors);
                            }
                        }
                    }

                    if (empty($title)) {

                        if (isset($_GET['failed']) && $_GET['failed'] == '1') {

                            $string = preg_replace($patterns, ' ', $string);
                            $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/', ' ', $string);
                            $string = preg_replace('/\s{2,}/', " ", $string);

                            $fulltext_array = array();
                            $fulltext_unique = array();

                            $fulltext_array = explode(" ", $string);
                            $fulltext_unique = array_unique($fulltext_array);
                            $string = implode(" ", $fulltext_unique);

                            database_connect($database_path, 'library');
                            record_unknown($dbHandle, $string, $database_path, $file, $userID);

                            $put = " ($i) " . basename($orig_filename) . ": Recorded into category !unknown. No database record found.<br>";
                        } else {

                            $put = " ($i) " . basename($orig_filename) . ": No database record found.<br>";
                        }
                    }

                    if (!empty($title)) {

                        database_connect($database_path, 'library');

                        if (!empty($authors))
                            $authors_ascii = utf8_deaccent($authors);

                        $title_ascii = utf8_deaccent($title);

                        if (!empty($abstract))
                            $abstract_ascii = utf8_deaccent($abstract);

                        ##########	record publication data, table library	##########

                        $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue, pages, secondary_title, editor,
                            url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by)
                            VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation, :title, :journal, :year, :addition_date, :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :editor,
                            :url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by)";

                        $stmt = $dbHandle->prepare($query);

                        $stmt->bindParam(':authors', $authors, PDO::PARAM_STR);
                        $stmt->bindParam(':affiliation', $affiliation, PDO::PARAM_STR);
                        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                        $stmt->bindParam(':journal', $journal, PDO::PARAM_STR);
                        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
                        $stmt->bindParam(':addition_date', $addition_date, PDO::PARAM_STR);
                        $stmt->bindParam(':abstract', $abstract, PDO::PARAM_STR);
                        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
                        $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
                        $stmt->bindParam(':volume', $volume, PDO::PARAM_STR);
                        $stmt->bindParam(':issue', $issue, PDO::PARAM_STR);
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
                        $stmt->bindParam(':added_by', $userID, PDO::PARAM_INT);

                        $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

                        $stmt->execute();
                        $stmt = null;

                        $last_insert = $dbHandle->query("SELECT last_insert_rowid(),max(file) FROM library");
                        $last_row = $last_insert->fetch(PDO::FETCH_ASSOC);
                        $last_insert = null;
                        $id = $last_row['last_insert_rowid()'];
                        $new_file = $last_row['max(file)'];

                        if (isset($_GET['shelf']) && !empty($userID)) {
                            $user_query = $dbHandle->quote($userID);
                            $file_query = $dbHandle->quote($id);
                            $dbHandle->exec("INSERT OR IGNORE INTO shelves (userID,fileID) VALUES ($user_query,$file_query)");

                            @unlink($user_dir . DIRECTORY_SEPARATOR . 'shelf_files');
                        }

                        if (isset($_GET['project']) && !empty($_GET['projectID'])) {
                            $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_GET['projectID']) . "," . intval($id) . ")");

                            $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR .'desk_files', GLOB_NOSORT);
                            foreach ($clean_files as $clean_file) {
                                if (is_file($clean_file) && is_writable($clean_file))
                                    @unlink($clean_file);
                            }
                        }

                        ####### record new category into categories, if not exists #########

                        if (!empty($_GET['category2'])) {

                            $category_ids = array();

                            $_GET['category2'] = preg_replace('/\s{2,}/', '', $_GET['category2']);
                            $_GET['category2'] = preg_replace('/^\s$/', '', $_GET['category2']);
                            $_GET['category2'] = array_filter($_GET['category2']);

                            $query = "INSERT INTO categories (category) VALUES (:category)";
                            $stmt = $dbHandle->prepare($query);
                            $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

                            while (list($key, $new_category) = each($_GET['category2'])) {
                                $new_category_quoted = $dbHandle->quote($new_category);
                                $result = $dbHandle->query("SELECT categoryID FROM categories WHERE category=$new_category_quoted");
                                $exists = $result->fetchColumn();
                                $category_ids[] = $exists;
                                $result = null;
                                if (empty($exists)) {
                                    $stmt->execute();
                                    $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM categories");
                                    $category_ids[] = $last_id->fetchColumn();
                                    $last_id = null;
                                }
                            }
                            $stmt = null;
                        }

                        ####### record new relations into filescategories #########

                        $categories = array();

                        if (!empty($_GET['category']) || !empty($category_ids)) {
                            $categories = array_merge((array) $_GET['category'], (array) $category_ids);
                            $categories = array_filter(array_unique($categories));
                        }

                        $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

                        $stmt = $dbHandle->prepare($query);
                        $stmt->bindParam(':fileid', $id);
                        $stmt->bindParam(':categoryid', $category_id);

                        while (list($key, $category_id) = each($categories)) {
                            if (!empty($id))
                                $stmt->execute();
                        }
                        $stmt = null;

                        $dbHandle->exec("COMMIT");
                        $dbHandle = null;

                        $string = preg_replace($patterns, ' ', $string);
                        $string = preg_replace('/(^|\s)\S{1,2}(\s|$)/', ' ', $string);
                        $string = preg_replace('/\s{2,}/', " ", $string);

                        $fulltext_array = array();
                        $fulltext_unique = array();

                        $fulltext_array = explode(" ", $string);
                        $fulltext_unique = array_unique($fulltext_array);
                        $string = implode(" ", $fulltext_unique);

                        database_connect($database_path, 'fulltext');

                        $file_query = $dbHandle->quote($id);
                        $fulltext_query = $dbHandle->quote($string);

                        $dbHandle->beginTransaction();
                        $dbHandle->exec("DELETE FROM full_text WHERE fileID=$file_query");
                        $insert = $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");
                        $dbHandle->commit();

                        $dbHandle = null;

                        copy($file, dirname(__FILE__) . DIRECTORY_SEPARATOR . "library" . DIRECTORY_SEPARATOR . $new_file);

                        $unpack_dir = $temp_dir . DIRECTORY_SEPARATOR . $new_file;
                        @mkdir($unpack_dir);
                        exec(select_pdftk() . '"' . $library_path . DIRECTORY_SEPARATOR . $new_file . '" unpack_files output "' . $unpack_dir . '"');
                        $unpacked_files = scandir($unpack_dir);
                        foreach ($unpacked_files as $unpacked_file) {
                            if (is_file($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file))
                                @rename($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file, $library_path . DIRECTORY_SEPARATOR . supplement . DIRECTORY_SEPARATOR . sprintf("%05d", intval($new_file)) . $unpacked_file);
                        }
                        @rmdir($unpack_dir);

                        $put = basename($orig_filename) . ": Recorded.<br>";
                    }
                }
            }
        } else {
            $put = basename($orig_filename) . ": Not readable.<br>";
        }
    } ####if
    ##########  ANALYZE  ##########
    database_connect($database_path, 'library');
    $dbHandle->exec("ANALYZE");
    $dbHandle = null;

    ###### clean the temp directory ########
    for ($j = $i; $j >= 1; $j--) {

        if (file_exists($temp_dir . DIRECTORY_SEPARATOR . "librarian_temp" . $j . ".txt"))
            unlink($temp_dir . DIRECTORY_SEPARATOR . "librarian_temp" . $j . ".txt");
    }

    die($put);
}

if (isset($_SESSION['auth']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

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

    database_connect($usersdatabase_path, 'users');
    $batchimport_database_pubmed = get_setting($dbHandle, 'batchimport_database_pubmed');
    $batchimport_database_nasaads = get_setting($dbHandle, 'batchimport_database_nasaads');
    $batchimport_database_crossref = get_setting($dbHandle, 'batchimport_database_crossref');
    $batchimport_failed = get_setting($dbHandle, 'batchimport_failed');
    $dbHandle = null;
    ?>
    <div style="margin:4px;font-weight:bold">PDF Batch Import</div>
    <form id="batchimportform2" action="remoteuploader.php" method="GET">
        <input type="hidden" name="commence" value="1">
        <input type="hidden" name="user" value="<?php print htmlspecialchars($_SESSION['user']); ?>">
        <input type="hidden" name="userID" value="<?php print htmlspecialchars($_SESSION['user_id']); ?>">
        <input type="hidden" name="proxy_name" value="<?php print htmlspecialchars($proxy_name); ?>">
        <input type="hidden" name="proxy_port" value="<?php print htmlspecialchars($proxy_port); ?>">
        <input type="hidden" name="proxy_username" value="<?php print htmlspecialchars($proxy_username); ?>">
        <input type="hidden" name="proxy_password" value="<?php print htmlspecialchars($proxy_password); ?>">
        <table cellspacing="0" style="width: 100%;border-top: solid 1px #D5D6D9">
            <tr>
                <td valign="top" class="threedleft">
                    <div id="uploaderOverlay">
                        <button id="select-button">Select Files</button>
                    </div>
                </td>
                <td class="threedright" style="padding-left: 18px">
                    You selected <span id="file-count">0 files</span>.
                    (Note that PDFs must contain a <a href="http://en.wikipedia.org/wiki/Digital_object_identifier" target="_blank">DOI</a> in order to track the corresponding metadata.)
                </td>
            </tr>
            <tr>
                <td valign="top" class="threedleft">
                    <button id="import-button" disabled>Import</button>
                </td>
                <td valign="top" class="threedright">
                    <table cellspacing=0>
                        <tr>
                            <td class="select_span" style="line-height:22px;width:10em">
                                <input type="checkbox" checked class="uploadcheckbox" style="display:none" name="shelf">
                                <span class="ui-icon ui-icon-check" style="float:left;margin-top: 2px">
                                </span>Add to Shelf
                            </td>
                            <td class="select_span" style="line-height:22px;width: 10em;text-align:right">
                                <input type="checkbox" class="uploadcheckbox" style="display:none" name="project">
                                <div style="float:right">Add&nbsp;to&nbsp;Project&nbsp;</div>
                                <span class="ui-icon ui-icon-close" style="float:right;margin-top: 2px">
                                </span>
                            </td>
                            <td style="line-height:22px;width: 18em">
                                <select name="projectID" style="width:200px">
    <?php
    database_connect($database_path, 'library');

    $id_query = $dbHandle->quote($_SESSION['user_id']);

    $result = $dbHandle->query("SELECT DISTINCT projects.projectID,project FROM projects
                            LEFT OUTER JOIN projectsusers ON projects.projectID=projectsusers.projectID
                            WHERE projects.userID=$id_query OR projectsusers.userID=$id_query ORDER BY project COLLATE NOCASE ASC");

    while ($project = $result->fetch(PDO::FETCH_ASSOC)) {
        print '<option value="' . $project['projectID'] . '">' . htmlspecialchars($project['project']) . '</option>' . PHP_EOL;
    }

    $result = null;
    $dbHandle = null;
    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table cellspacing="0" style="width: 100%" id="table1">
            <tr><td class="threedleft">Select database:</td>
                <td class="threedright">
                    <table cellspacing="0">
                        <tr>
                            <td class="select_span"><input type="checkbox" name="database_pubmed" value="1" style="display:none" <?php print (isset($batchimport_database_pubmed) && $batchimport_database_pubmed == '1') ? 'checked' : ''  ?>>
                                <span class="ui-icon ui-icon-<?php print (isset($batchimport_database_pubmed) && $batchimport_database_pubmed == '1') ? 'check' : 'close'  ?>" style="float:left"></span>PubMed (biomedicine)</td>
                        </tr><tr>
                            <td class="select_span"><input type="checkbox" name="database_nasaads" value="1" style="display:none" <?php print (isset($batchimport_database_nasaads) && $batchimport_database_nasaads == '1') ? 'checked' : ''  ?>>
                                <span class="ui-icon ui-icon-<?php print (isset($batchimport_database_nasaads) && $batchimport_database_nasaads == '1') ? 'check' : 'close'  ?>" style="float:left"></span>NASA ADS (physics, astronomy)</td>
                        </tr><tr>
                            <td class="select_span"><input type="checkbox" name="database_crossref" value="1" style="display:none" <?php print (isset($batchimport_database_crossref) && $batchimport_database_crossref == '1') ? 'checked' : ''  ?>>
                                <span class="ui-icon ui-icon-<?php print (isset($batchimport_database_crossref) && $batchimport_database_crossref == '1') ? 'check' : 'close'  ?>" style="float:left"></span>CrossRef (other sciences)</td>
                        </tr>
                    </table>
                </td></tr>
            <tr><td class="threedleft">If metadata not found:</td>
                <td class="threedright" style="line-height: 16px">
                    <table cellspacing="0">
                        <tr>
                            <td class="select_span" style="line-height: 16px">
                                <input type="checkbox" name="failed" value="1" style="display: none" <?php print (isset($batchimport_failed) && $batchimport_failed == '1') ? 'checked' : ''  ?>>
                                <span class="ui-icon ui-icon-<?php print (isset($batchimport_failed) && $batchimport_failed == '1') ? 'check' : 'close'  ?>" style="float:left"></span>
                                Import the PDF into the category !unknown. All PDF files will be recorded and indexed!
                            </td>
                        </tr>
                    </table>
                </td>
            </tr></table>
        <table cellspacing="0" style="width:100%" id="table2">
            <tr>
                <td class="threedleft">
                    Choose&nbsp;category:<br>
                </td>
                <td class="threedright">
                    <div class="categorydiv" style="width: 99%;overflow:scroll; height: 400px;background-color: white;color: black;border: 1px solid #C5C6C9">
                        <table cellspacing=0 style="float:left;width: 49%">
    <?php
    $category_string = null;
    database_connect($database_path, 'library');
    $result = $dbHandle->query("SELECT count(*) FROM categories");
    $totalcount = $result->fetchColumn();
    $result = null;

    $i = 1;
    $isdiv = null;
    $result = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
    while ($category = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($i > (1 + $totalcount / 2) && !$isdiv) {
            print '</table><table cellspacing=0 style="width: 49%;float: right;padding:2px">';
            $isdiv = true;
        }
        print PHP_EOL . '<tr><td class="select_span">';
        print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
        print " style=\"display:none\"><span  class=\"ui-icon ui-icon-close\" style=\"float:left\"></span>" . htmlspecialchars($category['category']) . "</td></tr>";
        $i = $i + 1;
    }
    $result = null;
    $dbHandle = null;
    ?>
                        </table>
                    </div>
                    <span class="ui-icon ui-icon-triangle-1-s enlargelist" style="cursor: pointer;float:left"></span>
                    <span class="ui-icon ui-icon-triangle-1-n shrinklist" style="cursor: pointer"></span>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Add to new categories:
                </td>
                <td class="threedright">
                    <input type="text" size="30" name="category2[]" value=""><br>
                    <input type="text" size="30" name="category2[]" value=""><br>
                    <input type="text" size="30" name="category2[]" value="">
                </td>
            </tr>
        </table>
    </form>
    <br>
    <?php
}
?>