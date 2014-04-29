<?php
include_once 'data.php';

ini_set('max_execution_time', 1800);

if (isset($_SESSION['auth']) && ($_SESSION['permissions'] == 'A' || $_SESSION['permissions'] == 'U')) {

    include_once 'functions.php';

    if (isset($_POST['form_sent']) && !isset($_FILES['form_import_file']) && empty($_POST['form_import_textarea']))
        die();

    if (isset($_POST['form_sent']) && isset($_POST['file_type']) && (isset($_FILES['form_import_file']) && is_uploaded_file($_FILES['form_import_file']['tmp_name']) || !empty($_POST['form_import_textarea']))) {

        function trim_value(&$value) {
            $value = trim($value);
        }

        $user_id = $_SESSION['user_id'];

        session_write_close();

        $record_count = 0;
        $duplicate_count = 0;
        $pdf_count = 0;
        $ids = array();

        $dbname = uniqid() . '-temp.sq3';
        $fdbname = uniqid() . '-ftemp.sq3';

        $dbHandle = new PDO('sqlite:' . $database_path . $dbname);
        $fdbHandle = new PDO('sqlite:' . $database_path . $fdbname);

        $dbHandle->beginTransaction();
        $create = $dbHandle->exec("CREATE TABLE library (
                id integer PRIMARY KEY,
                file text NOT NULL DEFAULT '',
                authors text NOT NULL DEFAULT '',
                affiliation text NOT NULL DEFAULT '',
                title text NOT NULL DEFAULT '',
                journal text NOT NULL DEFAULT '',
                secondary_title text NOT NULL DEFAULT '',
                year text NOT NULL DEFAULT '',
                volume text NOT NULL DEFAULT '',
                issue text NOT NULL DEFAULT '',
                pages text NOT NULL DEFAULT '',
                abstract text NOT NULL DEFAULT '',
                keywords text NOT NULL DEFAULT '',
                editor text NOT NULL DEFAULT '',
                publisher text NOT NULL DEFAULT '',
                place_published text NOT NULL DEFAULT '',
                reference_type text NOT NULL DEFAULT '',
                uid text NOT NULL DEFAULT '',
                doi text NOT NULL DEFAULT '',
                url text NOT NULL DEFAULT '',
                addition_date text NOT NULL DEFAULT '',
                rating integer NOT NULL DEFAULT '',
                authors_ascii text NOT NULL DEFAULT '',
                title_ascii text NOT NULL DEFAULT '',
                abstract_ascii text NOT NULL DEFAULT '',
                added_by integer NOT NULL DEFAULT '',
                modified_by integer NOT NULL DEFAULT '',
                modified_date text NOT NULL DEFAULT '',
                custom1 text NOT NULL DEFAULT '',
                custom2 text NOT NULL DEFAULT '',
                custom3 text NOT NULL DEFAULT '',
                custom4 text NOT NULL DEFAULT '',
                bibtex text NOT NULL DEFAULT ''
                )");
        $create = null;
        $create = $dbHandle->exec("CREATE TABLE notes (
                notesID integer PRIMARY KEY,
                userID integer NOT NULL,
                fileID integer NOT NULL,
                notes text NOT NULL DEFAULT ''
                )");
        $create = null;
        $dbHandle->commit();

        $create = $fdbHandle->exec("CREATE TABLE full_text (
                    id integer PRIMARY KEY,
                    fileID text NOT NULL DEFAULT '',
                    full_text text NOT NULL DEFAULT ''
                    )");
        $create = null;

        $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue, pages, secondary_title, editor,
                                        url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by, bibtex)
                 VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation, :title, :journal, :year, :addition_date, :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :editor,
                        :url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by, :bibtex)";

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
        $stmt->bindParam(':added_by', $added_by, PDO::PARAM_INT);
        $stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);

        $query = "INSERT INTO notes (userID, fileID, notes) VALUES (:userID, :fileID, :notes)";

        $stmt2 = $dbHandle->prepare($query);

        $stmt2->bindParam(':userID', $user_id, PDO::PARAM_INT);
        $stmt2->bindParam(':fileID', $last_id, PDO::PARAM_INT);
        $stmt2->bindParam(':notes', $notes, PDO::PARAM_STR);


        if ($_POST['file_type'] == "endnote") {

            if (is_uploaded_file($_FILES['form_import_file']['tmp_name'])) {
                try {
                    if (!$xml = @simplexml_load_file($_FILES['form_import_file']['tmp_name'])) {
                        throw new Exception('Not a valid XML file.');
                    }
                } catch (Exception $e) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    print "Error! " . $e->getMessage();
                    die();
                }
            } elseif (!empty($_POST['form_import_textarea'])) {
                try {
                    if (!$xml = @simplexml_load_string($_POST['form_import_textarea'])) {
                        throw new Exception('Not a valid XML.');
                    }
                } catch (Exception $e) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    print "Error! " . $e->getMessage();
                    die();
                }
            }

            $records = $xml->records->record;
            $record_count = count($records);

            $dbHandle->beginTransaction();

            foreach ($records as $record) {

                $authors = '';
                $authors_ascii = '';

                $authors_obj = $record->contributors->authors->author;

                if (!empty($authors_obj)) {

                    $authors2 = array();

                    foreach ($authors_obj as $author) {

                        $author = strip_tags($author->asXML());
                        $author_array = explode(",", $author);
                        $first_name = '';
                        if (isset($author_array[1]))
                            $first_name = $author_array[1];
                        $authors2[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($first_name) . '"';
                    }

                    $authors = join(";", $authors2);
                    $authors_ascii = utf8_deaccent($authors);
                }

                $affiliation = '';
                $affiliation_obj = $record->{'auth-address'};
                if (!empty($affiliation_obj))
                    $affiliation = strip_tags($affiliation_obj->asXML());

                $title = '';
                $title_obj = $record->titles->title;
                if (!empty($title_obj))
                    $title = strip_tags($title_obj->asXML());
                if (empty($title))
                    $title = 'No title.';
                $title_ascii = utf8_deaccent($title);

                $journal = '';
                $journal_obj = $record->titles->{'secondary-title'};
                if (!empty($journal_obj))
                    $journal = strip_tags($journal_obj->asXML());
                $journal = str_replace(".", "", strip_tags($journal));

                $year = '';
                $year_obj = $record->dates->year;
                if (!empty($year_obj))
                    $year = strip_tags($year_obj->asXML());

                $addition_date = date('Y-m-d');

                $abstract = '';
                $abstract_obj = $record->abstract;
                if (!empty($abstract_obj))
                    $abstract = strip_tags($abstract_obj->asXML());
                $abstract_ascii = utf8_deaccent($abstract);

                $rating = 2;

                $uid = '';
                $doi = '';
                $uid_obj = $record->{'accession-num'};
                if (!empty($uid_obj)) {
                    $uid = strip_tags($uid_obj->asXML());
                    if (preg_match('/10\.\d{4}\/\S+/ui', $uid) == 1) {
                        $doi = $uid;
                        $uid = '';
                    }
                }

                $volume = '';
                $volume_obj = $record->volume;
                if (!empty($volume_obj)) $volume = strip_tags($volume_obj->asXML());

                $issue = '';
                $issue_obj = $record->number;
                if (!empty($issue_obj)) $issue = strip_tags($issue_obj->asXML());

                $pages = '';
                $pages_obj = $record->pages;
                if (!empty($pages_obj)) $pages = strip_tags($pages_obj->asXML());

                $secondary_title = '';

                $editor = '';

                $reference_type = 'article';

                foreach ($record->{'ref-type'}->attributes() as $a => $b) {

                    if ($a == 'name') {

                        $reference_type = convert_type($b, 'endnote', 'ilib');
                        break;
                    }
                }

                $publisher = '';

                $place_published = '';

                $keywords = '';
                $keywords2 = array();
                $keywords_obj = $record->keywords;

                if (!empty($keywords_obj)) {

                    foreach ($keywords_obj->keyword as $keyword) {

                        if (!empty($keyword)) {
                            $keyword = strip_tags($keyword->asXML());
                            $keywords2[] = preg_replace('/\[|\]|\||\"|\/|\*/', ' ', $keyword);
                        }
                    }

                    $keywords = join(" / ", $keywords2);
                }

                $bibtex = '';

                $url = '';
                $urls = '';
                $urls2 = array();
                $urls_obj = $record->urls->{'related-urls'}->url;

                if (!empty($urls_obj)) {

                    foreach ($urls_obj as $url) {

                        if (!empty($url)) {
                            $url = strip_tags($url->asXML());
                            $urls2[] = $url;
                        } else {
                            $urls2[] = '';
                        }
                    }

                    $url = join("|", $urls2);
                }

                $added_by = $user_id;

                foreach ($record->database->attributes() as $a => $b) {

                    if ($a == 'path') {

                        $pdf_path1 = dirname($b) . DIRECTORY_SEPARATOR . basename($record->database, ".enl") . ".Data" . DIRECTORY_SEPARATOR;
                        break;
                    }
                }

                $pdf_path2 = $record->urls->{'pdf-urls'}->url;
                $file_to_copy = '';

                if (!empty($pdf_path2)) {
                    if (strstr($pdf_path2, "internal-pdf"))
                        $pdf_path = $pdf_path1 . 'PDF' . DIRECTORY_SEPARATOR . substr($pdf_path2, 15);
                    if (strstr($pdf_path2, "file:"))
                        $pdf_path = substr($pdf_path2, 7);
                    $file_to_copy = strtr($pdf_path, "/", DIRECTORY_SEPARATOR);
                }

                if (!empty($title))
                    $insert = $stmt->execute();

                $result = $dbHandle->query("SELECT last_insert_rowid() FROM library");
                $last_id = $result->fetchColumn();
                $ids[] = $last_id;
                $result = null;

                $item_count = count($ids);

                if (!isset($ids) || count($ids) == 0) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    die('No records found.');
                }

                if (is_file($file_to_copy) && is_readable($file_to_copy)) {
                    $result = $dbHandle->query("SELECT file FROM library WHERE id=" . $last_id);
                    $pdf_filename = $result->fetchColumn();
                    $pdf_filename = 'temp-' . $pdf_filename;
                    $result = null;
                    copy($file_to_copy, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $pdf_filename);

                    system(select_pdftotext() . '"' . $file_to_copy . '" "' . $temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . '.txt"');

                    if (is_file($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt")) {

                        $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, 				unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

                        $stopwords = explode(', ', $stopwords);

                        $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt");
                        unlink($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt");

                        if (!empty($string)) {

                            $patterns = join("\b/ui /\b", $stopwords);
                            $patterns = "/\b$patterns\b/ui";
                            $patterns = explode(" ", $patterns);

                            $order = array("\r\n", "\n", "\r");
                            $string = str_replace($order, ' ', $string);
                            $string = preg_replace($patterns, '', $string);
                            $string = preg_replace('/\s{2,}/ui', ' ', $string);

                            $fulltext_array = array();
                            $fulltext_unique = array();

                            $fulltext_array = explode(" ", $string);
                            $fulltext_unique = array_unique($fulltext_array);
                            $string = implode(" ", $fulltext_unique);

                            $fulltext_query = $fdbHandle->quote($string);

                            $fdbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES (" . $last_id . ",$fulltext_query)");
                        }
                    }
                }
            }

            $dbHandle->commit();

            $insert = null;
            $stmt = null;
        }

        if ($_POST['file_type'] == "RIS") {

            if (is_uploaded_file($_FILES['form_import_file']['tmp_name'])) {

                $file_contents = file_get_contents($_FILES['form_import_file']['tmp_name']);
                if (strpos($file_contents, "%PDF") === 0) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    die('Error! PDF files cannot be parsed. Please upload one of the indicated file types.');
                }
            } elseif (!empty($_POST['form_import_textarea'])) {

                $file_contents = $_POST['form_import_textarea'];
            }

            if (!empty($file_contents)) {

                #######	sniff UTF-8 encoding	###########
                $isutf = '';
                $isutf = preg_match('/^.{1}/us', $file_contents);
                if ($isutf != 1)
                    $file_contents = utf8_encode($file_contents);

                $file_records = explode('ER  -', $file_contents);
                array_walk($file_records, 'trim');
                $file_records = array_filter($file_records);
                $record_count = count($file_records) - 1;

                $addition_date = date('Y-m-d');
                $rating = 2;
                $uid = '';
                $bibtex = '';
                $affiliation = '';
                $added_by = $user_id;

                $dbHandle->beginTransaction();

                foreach ($file_records as $record) {

                    $title = preg_match("/(?<=T1  - |TI  - ).+/u", $record, $title_match);

                    if ($title == 1) {

                        $record_array = explode("\n", $record);

                        $type_match = array();
                        $journal_match = array();
                        $secondary_title_match = array();
                        $volume_match = array();
                        $issue_match = array();
                        $year_match = array();
                        $start_page_match = array();
                        $end_page_match = array();
                        $publisher_match = array();
                        $place_published_match = array();
                        $url_match = array();
                        $keywords_match = array();
                        $editors_match = array();
                        $authors_match = array();
                        $file_to_copy_match = array();
                        $doi_match = array();
                        $notes_match = array();

                        foreach ($record_array as $line) {

                            if (strpos($line, "TY") === 0)
                                $type_match[0] = trim(substr($line, 6));
                            if (strpos($line, "JA") === 0 || strpos($line, "J2") === 0)
                                $journal_match[0] = trim(substr($line, 6));
                            if (strpos($line, "JF") === 0 || strpos($line, "JO") === 0 || strpos($line, "T2") === 0)
                                $secondary_title_match[0] = trim(substr($line, 6));
                            if (strpos($line, "VL") === 0)
                                $volume_match[0] = trim(substr($line, 6));
                            if (strpos($line, "IS") === 0)
                                $issue_match[0] = trim(substr($line, 6));
                            if (strpos($line, "PY") === 0 || strpos($line, "Y1") === 0)
                                $year_match[0] = trim(substr($line, 6));
                            if (strpos($line, "SP") === 0)
                                $start_page_match[0] = trim(substr($line, 6));
                            if (strpos($line, "EP") === 0)
                                $end_page_match[0] = trim(substr($line, 6));
                            if (strpos($line, "PB") === 0)
                                $publisher_match[0] = trim(substr($line, 6));
                            if (strpos($line, "CY") === 0)
                                $place_published_match[0] = trim(substr($line, 6));
                            if (strpos($line, "UR") === 0)
                                $url_match[0][] = trim(substr($line, 6));
                            if (strpos($line, "KW") === 0)
                                $keywords_match[0][] = trim(substr($line, 6));
                            if (strpos($line, "ED") === 0 || strpos($line, "A2") === 0)
                                $editors_match[0][] = trim(substr($line, 6));
                            if (strpos($line, "AU") === 0 || strpos($line, "A1") === 0)
                                $authors_match[0][] = trim(substr($line, 6));
                            if (strpos($line, "L1") === 0)
                                $file_to_copy_match[0] = trim(substr($line, 6));
                            if (strpos($line, "DO") === 0)
                                $doi_match[0] = trim(substr($line, 6));
                            if (strpos($line, "N1") === 0)
                                $notes_match[0][] = trim(substr($line, 6));
                        }


                        preg_match("/(?<=AB  - \n|AB  - |AB  - \r\n|N2  - \n|N2  - |N2  - \r\n).+/u", $record, $abstract_match);
                        preg_match("/(?<=N1  - doi:|M3  - doi: DOI:).+|(10\.\d{4}\/\S+)/u", $record, $doi_match);

                        $authors = '';
                        $authors_ascii = '';

                        if (!empty($authors_match[0])) {
                            $name_array = array();
                            foreach ($authors_match[0] as $author) {
                                $author_array = explode(",", $author);
                                $first_name = '';
                                if (isset($author_array[1]))
                                    $first_name = $author_array[1];
                                $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($first_name) . '"';
                            }
                            $authors = join(";", $name_array);
                            $authors_ascii = utf8_deaccent($authors);
                        }


                        $title = '';
                        $title_ascii = '';

                        if (!empty($title_match[0])) {

                            $title = trim($title_match[0]);
                            $title_ascii = utf8_deaccent($title);
                        }

                        $journal = '';

                        if (!empty($journal_match[0]))
                            $journal = trim(str_replace(".", "", $journal_match[0]));

                        $year = '';

                        if (!empty($year_match[0])) {
                            $year = intval(substr($year_match[0], 0, 4));
                            if ($year == 0) {
                                preg_match('/\d{4}/u', $year_match[0], $year_match2);
                                if (!empty($year_match2[0]))
                                    $year = $year_match2[0];
                            }
                        }

                        $abstract = '';
                        $abstract_ascii = '';

                        if (!empty($abstract_match[0])) {

                            $abstract = trim($abstract_match[0]);
                            $abstract_ascii = utf8_deaccent($abstract);
                        }

                        $volume = '';

                        if (!empty($volume_match[0]))
                            $volume = trim($volume_match[0]);

                        $issue = '';

                        if (!empty($issue_match[0]))
                            $issue = trim($issue_match[0]);

                        $pages = '';

                        if (!empty($start_page_match[0]))
                            $pages = trim($start_page_match[0]);

                        if (!empty($end_page_match[0]))
                            $pages .= '-' . trim($end_page_match[0]);

                        $secondary_title = '';

                        if (!empty($secondary_title_match[0]))
                            $secondary_title = trim($secondary_title_match[0]);

                        $url = '';

                        if (!empty($url_match[0])) {
                            array_walk($url_match[0], 'trim_value');
                            $url = join('|', $url_match[0]);
                        }

                        $publisher = '';

                        if (!empty($publisher_match[0]))
                            $publisher = trim($publisher_match[0]);

                        $place_published = '';

                        if (!empty($place_published_match[0]))
                            $publisher = trim($place_published_match[0]);

                        $editor = '';

                        if (!empty($editors_match[0])) {
                            $order = array("\r\n", "\n", "\r");
                            $editors_match[0] = str_replace($order, ' ', $editors_match[0]);
                            $editors_match[0] = join("#", $editors_match[0]);
                            $patterns = array(',', '.', '#', '  ');
                            $replacements = array(' ', '', ', ', ' ');
                            $editor = str_replace($patterns, $replacements, $editors_match[0]);
                        }

                        $reference_type = 'article';

                        if (!empty($type_match[0]))
                            $reference_type = convert_type(trim($type_match[0]), 'ris', 'ilib');

                        $keywords = '';

                        if (!empty($keywords_match[0])) {
                            $order = array("\r\n", "\n", "\r");
                            $keywords_match[0] = str_replace($order, ' ', $keywords_match[0]);
                            $patterns = array('[', ']', '|', '"', '/', '*');
                            $keywords_match[0] = str_replace($patterns, ' ', $keywords_match[0]);
                            array_walk($keywords_match[0], 'trim_value');
                            $keywords_match[0] = join("#", $keywords_match[0]);
                            $keywords = str_replace("#", " / ", $keywords_match[0]);
                        }

                        $doi = '';

                        if (!empty($doi_match[0]))
                            $doi = trim($doi_match[0]);

                        $notes = '';

                        if (!empty($notes_match[0])) {
                            $notes = join(' ', $notes_match[0]);
                        }

                        $file_to_copy = '';
                        if (isset($file_to_copy_match[0]))
                            $file_to_copy = $file_to_copy_match[0];
                        if (strpos($file_to_copy, "file://") === 0)
                            $file_to_copy = preg_replace('/(file:\/\/.*\/)(.*)/Ui', "$2", $file_to_copy);
                        if (substr(strtoupper(PHP_OS), 0, 3) != 'WIN')
                            $file_to_copy = '/' . $file_to_copy;

                        if (!empty($title))
                            $insert = $stmt->execute();
                        $result = $dbHandle->query("SELECT last_insert_rowid() FROM library");
                        $last_id = $result->fetchColumn();
                        $ids[] = $last_id;
                        $result = null;
                        $insert = null;

                        if (!empty($notes)) {
                            $user_id = $_SESSION['user_id'];
                            $insert = $stmt2->execute();
                            $insert = null;
                        }

                        if (is_file($file_to_copy) && is_readable($file_to_copy)) {
                            $result = $dbHandle->query("SELECT file FROM library WHERE id=" . $last_id);
                            $pdf_filename = $result->fetchColumn();
                            $pdf_filename = 'temp-' . $pdf_filename;
                            $result = null;
                            copy($file_to_copy, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $pdf_filename);

                            system(select_pdftotext() . '"' . $file_to_copy . '" "' . $temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . '.txt"');

                            if (is_file($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt")) {

                                $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, 				unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

                                $stopwords = explode(', ', $stopwords);

                                $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt");
                                unlink($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt");

                                if (!empty($string)) {

                                    $patterns = join("\b/ui /\b", $stopwords);
                                    $patterns = "/\b$patterns\b/ui";
                                    $patterns = explode(" ", $patterns);

                                    $order = array("\r\n", "\n", "\r");
                                    $string = str_replace($order, ' ', $string);
                                    $string = preg_replace($patterns, '', $string);
                                    $string = preg_replace('/\s{2,}/ui', ' ', $string);

                                    $fulltext_array = array();
                                    $fulltext_unique = array();

                                    $fulltext_array = explode(" ", $string);
                                    $fulltext_unique = array_unique($fulltext_array);
                                    $string = implode(" ", $fulltext_unique);

                                    $fulltext_query = $fdbHandle->quote($string);

                                    $fdbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES (" . $last_id . ",$fulltext_query)");
                                }
                            }
                        }
                    }
                }

                $dbHandle->commit();

                $item_count = count($ids);

                if (!isset($ids) || count($ids) == 0) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    die('No records found.');
                }

                $insert = null;
                $stmt = null;
                $stmt2 = null;
            }
        }

        if ($_POST['file_type'] == "isi") {

            if (is_uploaded_file($_FILES['form_import_file']['tmp_name'])) {

                $file_contents = file_get_contents($_FILES['form_import_file']['tmp_name']);
                if (strpos($file_contents, "%PDF") === 0) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    die('Error! PDF files cannot be parsed. Please upload one of the indicated file types.');
                }
            } elseif (!empty($_POST['form_import_textarea'])) {

                $file_contents = $_POST['form_import_textarea'];
            }

            if (!empty($file_contents)) {

                #######	sniff UTF-8 encoding	###########
                $isutf = '';
                $isutf = preg_match('/^.{1}/us', $file_contents);
                if ($isutf != 1)
                    $file_contents = utf8_encode($file_contents);

                $file_records = explode("\nER\n", $file_contents);
                $record_count = count($file_records) - 1;

                $dbHandle->beginTransaction();

                foreach ($file_records as $record) {

                    $record = str_replace("\n   ", "{#}", $record);

                    if (!empty($record) && !ctype_cntrl($record) && strstr($record, "TI ")) {

                        preg_match("/(?<=TI ).+/u", $record, $title_match);
                        preg_match("/(?<=SO ).+/u", $record, $secondary_title_match);
                        preg_match("/(?<=VL ).+/u", $record, $volume_match);
                        preg_match("/(?<=IS ).+/u", $record, $issue_match);
                        preg_match("/(?<=PY ).+/u", $record, $year_match);
                        preg_match("/(?<=BP ).+/u", $record, $start_page_match);
                        preg_match("/(?<=EP ).+/u", $record, $end_page_match);
                        preg_match("/(?<=AB ).+/u", $record, $abstract_match);
                        preg_match("/(?<=AU ).+/u", $record, $authors_match);
                        preg_match("/(?<=DI ).+/u", $record, $doi_match);
                        preg_match("/(?<=ID ).+/u", $record, $keywords_match);
                        preg_match("/(?<=JI ).+/u", $record, $journal_match);

                        $authors = '';
                        $authors_ascii = '';
                        $name_array = array();

                        if (!empty($authors_match[0])) {
                            $name_array = array();
                            $authors_match[0] = explode("{#}", $authors_match[0]);
                            foreach ($authors_match[0] as $author) {
                                $author_array = explode(",", $author);
                                $first_name = '';
                                if (isset($author_array[1]))
                                    $first_name = $author_array[1];
                                $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($first_name) . '"';
                            }
                            $authors = join(";", $name_array);
                            $authors_ascii = utf8_deaccent($authors);
                        }

                        $affiliation = '';

                        $title = '';
                        $title_ascii = '';

                        if (!empty($title_match[0])) {

                            $title = trim($title_match[0]);
                            $title = str_replace("{#}", " ", $title);
                            $title_ascii = utf8_deaccent($title);
                        }

                        $journal = '';

                        if (!empty($journal_match[0]))
                            $journal = trim(str_replace(".", "", $journal_match[0]));

                        $year = '';

                        if (!empty($year_match[0]))
                            $year = intval(substr($year_match[0], 0, 4));

                        $addition_date = date('Y-m-d');

                        $abstract = '';
                        $abstract_ascii = '';

                        if (!empty($abstract_match[0])) {

                            $abstract = trim($abstract_match[0]);
                            $abstract = str_replace("{#}", " ", $abstract);
                            $abstract_ascii = utf8_deaccent($abstract);
                        }

                        $rating = 2;

                        $uid = '';

                        $bibtex = '';

                        $volume = '';

                        if (!empty($volume_match[0]))
                            $volume = trim($volume_match[0]);

                        $issue = '';

                        if (!empty($issue_match[0]))
                            $issue = trim($issue_match[0]);

                        $pages = '';

                        if (!empty($start_page_match[0]))
                            $pages = trim($start_page_match[0]);

                        if (!empty($end_page_match[0]))
                            $pages .= '-' . trim($end_page_match[0]);

                        $secondary_title = '';

                        if (!empty($secondary_title_match[0])) {

                            $secondary_title = str_replace("{#}", " ", $secondary_title_match[0]);
                            $secondary_title = ucfirst(strtolower(trim($secondary_title)));
                        }

                        $editor = '';

                        $reference_type = 'article';

                        $publisher = '';

                        $place_published = '';

                        $url = '';

                        $keywords = '';

                        if (!empty($keywords_match[0])) {

                            $keywords = str_replace("{#}", " ", $keywords_match[0]);
                            $keywords = preg_replace('/\[|\]|\||\"|\/|\*/u', ' ', $keywords);
                            $keywords = str_replace("; ", " / ", $keywords);
                            $keywords = ucwords(strtolower($keywords));
                        }

                        $doi = '';
                        if (!empty($doi_match[0]))
                            $doi = trim($doi_match[0]);

                        $added_by = $user_id;

                        if (!empty($title))
                            $insert = $stmt->execute();

                        $last_id = $dbHandle->query("SELECT last_insert_rowid() FROM library");
                        $ids[] = $last_id->fetchColumn();
                        $last_id = null;
                    }
                }

                $dbHandle->commit();

                $item_count = count($ids);

                if (!isset($ids) || count($ids) == 0) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    die('No records found.');
                }

                $insert = null;
                $stmt = null;
            }
        }

        if ($_POST['file_type'] == "bibtex") {

            if (isset($_FILES['form_import_file']) && is_uploaded_file($_FILES['form_import_file']['tmp_name'])) {

                $file_contents = file_get_contents($_FILES['form_import_file']['tmp_name']);
                if (strpos($file_contents, "%PDF") === 0) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    die('Error! PDF files cannot be parsed. Please upload one of the indicated file types.');
                }
            } elseif (!empty($_POST['form_import_textarea'])) {

                $file_contents = $_POST['form_import_textarea'];
            }

            if (!empty($file_contents)) {

                $tempfilename = tempnam($temp_dir, "lib");
                file_put_contents($tempfilename, $file_contents);
                exec(select_bibutil("bib2xml") . " -i unicode $tempfilename > $tempfilename.xml");
                exec(select_bibutil("xml2ris") . " -o unicode -nb $tempfilename.xml > $tempfilename.xml.ris");
                $file_contents = file_get_contents($tempfilename . ".xml.ris");

                unlink($tempfilename);
                unlink($tempfilename . '.xml');
                unlink($tempfilename . '.xml.ris');

                #######	sniff UTF-8 encoding	###########
                $isutf = '';
                $isutf = preg_match('/^.{1}/us', $file_contents);
                if ($isutf != 1)
                    $file_contents = utf8_encode($file_contents);

                $file_records = explode('ER  -', $file_contents);
                array_walk($file_records, 'trim');
                $file_records = array_filter($file_records);
                $record_count = count($file_records) - 1;

                $dbHandle->beginTransaction();

                foreach ($file_records as $record) {

                    $title = preg_match("/(?<=T1  - |TI  - ).+/u", $record, $title_match);

                    if ($title == 1) {

                        $addition_date = date('Y-m-d');
                        $rating = 2;
                        $uid = '';
                        $affiliation = '';
                        $added_by = $user_id;

                        $record_array = explode("\n", $record);

                        $type_match = array();
                        $journal_match = array();
                        $secondary_title_match = array();
                        $volume_match = array();
                        $issue_match = array();
                        $year_match = array();
                        $start_page_match = array();
                        $end_page_match = array();
                        $publisher_match = array();
                        $place_published_match = array();
                        $url_match = array();
                        $keywords_match = array();
                        $editors_match = array();
                        $authors_match = array();
                        $bibtex_match = array();
                        $abstract_match = array();
                        $doi_match = array();
                        $file_to_copy_match = array();

                        foreach ($record_array as $line) {

                            if (strpos($line, "TY") === 0)
                                $type_match[0] = trim(substr($line, 6));
                            if (strpos($line, "JA") === 0)
                                $journal_match[0] = substr($line, 6);
                            if (strpos($line, "JF") === 0 || strpos($line, "JO") === 0)
                                $secondary_title_match[0] = substr($line, 6);
                            if (strpos($line, "VL") === 0)
                                $volume_match[0] = substr($line, 6);
                            if (strpos($line, "IS") === 0)
                                $issue_match[0] = substr($line, 6);
                            if (strpos($line, "PY") === 0 || strpos($line, "Y1") === 0)
                                $year_match[0] = substr($line, 6);
                            if (strpos($line, "SP") === 0)
                                $start_page_match[0] = substr($line, 6);
                            if (strpos($line, "EP") === 0)
                                $end_page_match[0] = substr($line, 6);
                            if (strpos($line, "PB") === 0)
                                $publisher_match[0] = substr($line, 6);
                            if (strpos($line, "CY") === 0)
                                $place_published_match[0] = substr($line, 6);
                            if (strpos($line, "UR") === 0)
                                $url_match[0][] = substr($line, 6);
                            if (strpos($line, "KW") === 0)
                                $keywords_match[0][] = substr($line, 6);
                            if (strpos($line, "ED") === 0 || strpos($line, "A2") === 0)
                                $editors_match[0][] = substr($line, 6);
                            if (strpos($line, "AU") === 0 || strpos($line, "A1") === 0)
                                $authors_match[0][] = substr($line, 6);
                            if (strpos($line, "ID") === 0)
                                $bibtex_match[0] = substr($line, 6);
                            if (strpos($line, "L1") === 0)
                                $file_to_copy_match[0] = trim(substr($line, 6));
                        }

                        preg_match("/(?<=AB  - \n|AB  - |AB  - \r\n|N2  - \n|N2  - |N2  - \r\n).+/u", $record, $abstract_match);
                        preg_match("/(?<=N1  - doi:|M3  - doi: DOI:).+|(10\.\d{4}\/\S+)/u", $record, $doi_match);

                        $authors = '';
                        $authors_ascii = '';

                        if (!empty($authors_match[0])) {
                            $name_array = array();
                            foreach ($authors_match[0] as $author) {
                                $author_array = explode(",", $author);
                                $first_name = '';
                                if (isset($author_array[1]))
                                    $first_name = $author_array[1];
                                $name_array[] = 'L:"' . trim($author_array[0]) . '",F:"' . trim($first_name) . '"';
                            }
                            $authors = join(";", $name_array);
                            $authors_ascii = utf8_deaccent($authors);
                        }

                        $title = '';
                        $title_ascii = '';

                        if (!empty($title_match[0])) {

                            $title = trim($title_match[0]);
                            $title_ascii = utf8_deaccent($title);
                        }

                        $journal = '';

                        if (!empty($journal_match[0]))
                            $journal = trim(str_replace(".", "", $journal_match[0]));

                        $year = '';

                        if (!empty($year_match[0])) {

                            $year = intval(substr($year_match[0], 0, 4));

                            if ($year == 0) {
                                $year_match2[0] = '';
                                preg_match('/\d{4}/u', $year_match[0], $year_match2);
                                $year = $year_match2[0];
                            }
                        }

                        $abstract = '';
                        $abstract_ascii = '';

                        if (!empty($abstract_match[0])) {

                            $abstract = trim($abstract_match[0]);
                            $abstract_ascii = utf8_deaccent($abstract);
                        }

                        $volume = '';

                        if (!empty($volume_match[0]))
                            $volume = trim($volume_match[0]);

                        $issue = '';

                        if (!empty($issue_match[0]))
                            $issue = trim($issue_match[0]);

                        $pages = '';

                        if (!empty($start_page_match[0]))
                            $pages = trim($start_page_match[0]);

                        if (!empty($end_page_match[0]))
                            $pages .= '-' . trim($end_page_match[0]);

                        $secondary_title = '';

                        if (!empty($secondary_title_match[0]))
                            $secondary_title = trim($secondary_title_match[0]);

                        if ($journal == '')
                            $journal = $secondary_title;

                        $url = '';

                        if (!empty($url_match[0])) {
                            array_walk($url_match[0], 'trim_value');
                            $url = join('|', $url_match[0]);
                        }

                        $publisher = '';

                        if (!empty($publisher_match[0]))
                            $publisher = trim($publisher_match[0]);

                        $place_published = '';

                        if (!empty($place_published_match[0]))
                            $publisher = trim($place_published_match[0]);

                        $editor = '';

                        if (!empty($editors_match[0])) {
                            $order = array("\r\n", "\n", "\r");
                            $editors_match[0] = str_replace($order, ' ', $editors_match[0]);
                            $editors_match[0] = join("#", $editors_match[0]);
                            $patterns = array(',', '.', '#', '  ');
                            $replacements = array(' ', '', ', ', ' ');
                            $editor = str_replace($patterns, $replacements, $editors_match[0]);
                        }

                        $reference_type = 'article';

                        if (!empty($type_match[0]))
                            $reference_type = convert_type(trim($type_match[0]), 'ris', 'ilib');

                        $keywords = '';

                        /*
                         * KEYWORDS SUPPORT IN BIBTEX SUCKS, THEREFORE IT IS NOT SUPPORTED IN I, LIBRARIAN

                          if (!empty($keywords_match[0])) {
                          $keywords_arr = array();
                          $keywords = '';
                          $order = array("\r\n", "\n", "\r");
                          $keywords_match[0] = str_replace($order, ' ', $keywords_match[0]);
                          $patterns = array('[', ']', '|', '"', '/', '*');
                          $keywords_match[0] = str_replace($patterns, ' ', $keywords_match[0]);
                          array_walk($keywords_match[0], 'trim_value');
                          $keywords = join(" / ", $keywords_match[0]);
                          }
                         */

                        $doi = '';

                        if (!empty($doi_match[0]))
                            $doi = trim($doi_match[0]);

                        $bibtex = '';

                        if (!empty($bibtex_match[0]))
                            $bibtex = trim($bibtex_match[0]);

                        if (!empty($title)) {
                            $insert = $stmt->execute();
                            $insert = null;
                            $result = $dbHandle->query("SELECT last_insert_rowid() FROM library");
                            $last_id = $result->fetchColumn();
                            $ids[] = $last_id;
                            $result = null;
                        }

                        $file_to_copy = '';
                        if (isset($file_to_copy_match[0])) {
                            #DEALING WITH BIBUTILS BUG
                            $file_to_copy = str_ireplace('file://', '', $file_to_copy_match[0]);
                            if (substr(strtoupper(PHP_OS), 0, 3) == 'WIN' && $file_to_copy[0] == '/')
                                $file_to_copy = substr($file_to_copy, 1);
                        }

                        if (is_file($file_to_copy) && is_readable($file_to_copy)) {
                            $result = $dbHandle->query("SELECT file FROM library WHERE id=" . $last_id);
                            $pdf_filename = $result->fetchColumn();
                            $pdf_filename = 'temp-' . $pdf_filename;
                            $result = null;
                            copy($file_to_copy, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $pdf_filename);

                            system(select_pdftotext() . '"' . $file_to_copy . '" "' . $temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . '.txt"');

                            if (is_file($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt")) {

                                $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, 				unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

                                $stopwords = explode(', ', $stopwords);

                                $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt");
                                unlink($temp_dir . DIRECTORY_SEPARATOR . $pdf_filename . ".txt");

                                if (!empty($string)) {

                                    $patterns = join("\b/ui /\b", $stopwords);
                                    $patterns = "/\b$patterns\b/ui";
                                    $patterns = explode(" ", $patterns);

                                    $order = array("\r\n", "\n", "\r");
                                    $string = str_replace($order, ' ', $string);
                                    $string = preg_replace($patterns, '', $string);
                                    $string = preg_replace('/\s{2,}/ui', ' ', $string);

                                    $fulltext_array = array();
                                    $fulltext_unique = array();

                                    $fulltext_array = explode(" ", $string);
                                    $fulltext_unique = array_unique($fulltext_array);
                                    $string = implode(" ", $fulltext_unique);

                                    $fulltext_query = $fdbHandle->quote($string);

                                    $fdbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES (" . $last_id . ",$fulltext_query)");
                                }
                            }
                        }
                    }
                }

                $dbHandle->commit();

                $item_count = count($ids);

                if (!isset($ids) || count($ids) == 0) {
                    $stmt = null;
                    $dbHandle = null;
                    $fdbHandle = null;
                    unlink($database_path . $dbname);
                    unlink($database_path . $fdbname);
                    die('No records found.');
                }

                $insert = null;
                $stmt = null;
            }
        }

        $dbHandle = null;
        $fdbHandle = null;
        $ids = array();

        database_connect($database_path, 'library');

        $db_path_query = $dbHandle->quote($database_path . $dbname);
        $dbHandle->exec("ATTACH DATABASE " . $db_path_query . " AS tempdb");

        $db_path_query = $dbHandle->quote($database_path . $fdbname);
        $dbHandle->exec("ATTACH DATABASE " . $db_path_query . " AS tempfdb");

        $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue, pages, secondary_title, editor,
					url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by, bibtex)
		 VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation, :title, :journal, :year, :addition_date, :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :editor,
			:url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by, :bibtex)";

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
        $stmt->bindParam(':added_by', $added_by, PDO::PARAM_INT);
        $stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);

        $query = "INSERT INTO notes (userID, fileID, notes) VALUES (:userID, :fileID, :notes)";

        $stmt2 = $dbHandle->prepare($query);

        $stmt2->bindParam(':userID', $user_id, PDO::PARAM_INT);
        $stmt2->bindParam(':fileID', $new_id, PDO::PARAM_INT);
        $stmt2->bindParam(':notes', $notes, PDO::PARAM_STR);

        for ($i = 0; $i <= $item_count / 500; $i++) {

            $offset = 500 * $i;

            $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

            $tmpresult = $dbHandle->query("SELECT * FROM tempdb.library ORDER BY id ASC LIMIT 500 OFFSET $offset");

            while ($tmprow = $tmpresult->fetch(PDO::FETCH_ASSOC)) {

                $authors = $tmprow['authors'];
                $affiliation = $tmprow['affiliation'];
                $title = $tmprow['title'];
                $journal = $tmprow['journal'];
                $year = $tmprow['year'];
                $addition_date = $tmprow['addition_date'];
                $abstract = $tmprow['abstract'];
                $rating = $tmprow['rating'];
                $uid = $tmprow['uid'];
                $volume = $tmprow['volume'];
                $issue = $tmprow['issue'];
                $pages = $tmprow['pages'];
                $secondary_title = $tmprow['secondary_title'];
                $editor = $tmprow['editor'];
                $url = $tmprow['url'];
                $reference_type = $tmprow['reference_type'];
                $publisher = $tmprow['publisher'];
                $place_published = $tmprow['place_published'];
                $keywords = $tmprow['keywords'];
                $doi = $tmprow['doi'];
                $authors_ascii = $tmprow['authors_ascii'];
                $title_ascii = $tmprow['title_ascii'];
                $abstract_ascii = $tmprow['abstract_ascii'];
                $added_by = $tmprow['added_by'];
                $bibtex = $tmprow['bibtex'];

                $stmt->execute();
                $last_insert = $dbHandle->query("SELECT last_insert_rowid() FROM library");
                $new_id = $last_insert->fetchColumn();
                $last_insert = null;
                $last_insert = $dbHandle->query("SELECT file FROM library WHERE id=$new_id");
                $new_file = $last_insert->fetchColumn();
                $last_insert = null;
                $ids[] = $new_id;

                $noteresult = $dbHandle->query("SELECT notes FROM tempdb.notes WHERE fileID=" . intval($tmprow['id']) . " AND userID=" . intval($_SESSION['user_id']));
                $notes = $noteresult->fetchColumn();

                if (!empty($notes)) {
                    $user_id = $_SESSION['user_id'];
                    $insert = $stmt2->execute();
                    $insert = null;
                }

                if (is_writable(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'temp-' . $tmprow['file'])) {
                    $dbHandle->exec("UPDATE tempfdb.full_text SET fileID=" . $new_id . " WHERE fileID=" . $tmprow['id']);
                    rename(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'temp-' . $tmprow['file'], dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . $new_file);
                }

                $tmprow = null;
            }

            $dbHandle->exec("COMMIT");
            $tmpresult = null;
            $tmprow = null;
        }

        $stmt = null;

        $dbHandle->exec("DETACH DATABASE tempdb");
        $dbHandle->exec("DETACH DATABASE tempfdb");

        ####### record new category into categories, if not exists #########

        $category_ids = array();

        if (!empty($_POST['category2'])) {

            $_POST['category2'] = preg_replace('/\s{2,}/', '', $_POST['category2']);
            $_POST['category2'] = preg_replace('/^\s$/', '', $_POST['category2']);
            $_POST['category2'] = array_filter($_POST['category2']);

            $query = "INSERT INTO categories (category) VALUES (:category)";
            $stmt = $dbHandle->prepare($query);
            $stmt->bindParam(':category', $new_category, PDO::PARAM_STR);

            $dbHandle->beginTransaction();

            while (list($key, $new_category) = each($_POST['category2'])) {
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

            $dbHandle->commit();
            $stmt = null;
        }

        ####### record new relations into filescategories #########

        $categories = array();

        if (!empty($_POST['category']) || !empty($category_ids)) {
            $categories = array_merge((array) $_POST['category'], (array) $category_ids);
            $categories = array_filter(array_unique($categories));
        }

        $query = "INSERT OR IGNORE INTO filescategories (fileID,categoryID) VALUES (:fileid,:categoryid)";

        $stmt = $dbHandle->prepare($query);
        $stmt->bindParam(':fileid', $record_id);
        $stmt->bindParam(':categoryid', $category_id);

        $dbHandle->beginTransaction();
        while (list($key, $record_id) = each($ids)) {
            while (list($key, $category_id) = each($categories)) {
                if (!empty($record_id))
                    $stmt->execute();
            }
            reset($categories);
        }
        reset($ids);
        $dbHandle->commit();
        $stmt = null;

        ##########	record publication data, table shelves	##########

        if (isset($_POST['shelf'])) {

            $user_query = $dbHandle->quote($user_id);
            $dbHandle->beginTransaction();
            while (list($key, $record_id) = each($ids)) {
                $dbHandle->exec("INSERT OR IGNORE INTO shelves (fileID,userID) VALUES (" . intval($record_id) . ",$user_query)");
            }
            $dbHandle->commit();
            reset($ids);
            @unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files');
        }

        ##########	record to projectsfiles	##########

        if (isset($_POST['project']) && !empty($_POST['projectID'])) {

            $dbHandle->beginTransaction();
            while (list($key, $record_id) = each($ids)) {
                $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_POST['projectID']) . "," . intval($record_id) . ")");
            }
            $dbHandle->commit();
            reset($ids);
            $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR .'desk_files', GLOB_NOSORT);
            foreach ($clean_files as $clean_file) {
                if (is_file($clean_file) && is_writable($clean_file))
                    @unlink($clean_file);
            }
        }

        ##########  ANALYZE  ##########

        $dbHandle->exec("ANALYZE");
        $dbHandle = null;

        ##########  RECORD FULL TEXTS  ##########

        database_connect($database_path, 'fulltext');

        $db_path_query = $dbHandle->quote($database_path . $fdbname);
        $dbHandle->exec("ATTACH DATABASE " . $db_path_query . " AS tempdb");

        $query = "INSERT INTO full_text (fileID,full_text) VALUES (:fileID,:full_text)";

        $stmt = $dbHandle->prepare($query);

        $stmt->bindParam(':fileID', $fileID, PDO::PARAM_INT);
        $stmt->bindParam(':full_text', $full_text, PDO::PARAM_STR);

        $tmpresult = $dbHandle->query("SELECT * FROM tempdb.full_text");

        $dbHandle->beginTransaction();

        while ($tmprow = $tmpresult->fetch(PDO::FETCH_ASSOC)) {
            $fileID = $tmprow['fileID'];
            $full_text = $tmprow['full_text'];

            $stmt->execute();
        }

        $dbHandle->commit();

        $tmpresult = null;
        $stmt = null;

        $dbHandle->exec("DETACH DATABASE tempdb");

        $dbHandle = null;

        ##########	record to clipboard	##########

        session_start();

        if (isset($_POST['clipboard'])) {

            if (!isset($_SESSION['session_clipboard']))
                $_SESSION['session_clipboard'] = array();
            $_SESSION['session_clipboard'] = array_merge((array) $_SESSION['session_clipboard'], (array) $ids);
            $_SESSION['session_clipboard'] = array_unique($_SESSION['session_clipboard']);
        }

        unlink($database_path . $dbname);
        unlink($database_path . $fdbname);

        die('Done. Total items recorded: ' . $record_count);
    } else {
        ?>

        <table cellspacing=0 style="width:100%;margin-top: 4px;margin-bottom: 1px;">
            <tr>
                <td>
                    <div class="ui-state-highlight ui-corner-top" id="open2" style="float:right;margin-left:2px;padding-right:4px">
                        <span class="ui-icon ui-icon-tag" style="float:left"></span>Categories
                    </div>
                    <div class="ui-state-highlight ui-corner-top clicked" id="open1" style="float:right;padding-right:4px">
                        <span class="ui-icon ui-icon-document" style="float:left"></span>Metadata
                    </div>
                    <b>&nbsp;Import one or multiple items from a metadata file</b>
                </td>
            </tr>
        </table>
        <form enctype="multipart/form-data" action="import.php" method="POST" id="importform">
            <input type="hidden" name="MAX_FILE_SIZE" value="60000000">
            <input type="hidden" name="form_sent">
            <table cellspacing="0" style="width: 100%;border-top: solid 1px #D5D6D9">
                <tr>
                    <td valign="top" class="threedleft">
                        <button id="importbutton">Save</button>
                    </td>
                    <td valign="top" class="threedright">
                        <table cellspacing=0>
                            <tr>
                                <td class="select_span" style="line-height:22px;width:10em">
                                    <input type="checkbox" checked class="uploadcheckbox" style="display:none" name="shelf">
                                    <span class="ui-icon ui-icon-check" style="float:left;margin-top: 2px">
                                    </span>Add to Shelf
                                </td>
                                <td class="select_span" style="line-height:22px;width:11em">
                                    <input type="checkbox" class="uploadcheckbox" style="display:none" name="clipboard">
                                    <span class="ui-icon ui-icon-close" style="float:left;margin-top: 2px">
                                    </span>Add to Clipboard
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

                                        $result = $dbHandle->query("SELECT DISTINCT projects.projectID as projectID,project FROM projects
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
                <tr>
                    <td valign="top" class="threedleft">Import from file:
                    </td>
                    <td valign="top" class="threedright"><input type="file" name="form_import_file">
                    </td>
                </tr>
                <tr>
                    <td valign="top" class="threedleft">Paste metadata:
                    </td>
                    <td valign="top" class="threedright"><textarea cols="80" rows="10" name="form_import_textarea" style="width: 99%"></textarea>
                    </td>
                </tr>
                <tr>
                    <td valign="top" class="threedleft">Metadata format:
                    </td>
                    <td valign="top" class="threedright">
                        <table cellspacing="0">
                            <tr>
                                <td class="select_span" style="width: 12em">
                                    <input type="radio" class="uploadcheckbox" style="display:none" name="file_type" value="RIS">
                                    <span class="ui-icon ui-icon-radio-off" style="float:left"></span>RIS* (+PDFs**)
                                </td>
                                <td class="select_span" style="width: 16em">
                                    <input type="radio" class="uploadcheckbox" style="display:none" name="file_type" value="endnote">
                                    <span class="ui-icon ui-icon-radio-off" style="float:left"></span>Endnote XML (+PDFs**)
                                </td>
                                <td class="select_span" style="width: 13em">
                                    <input type="radio" class="uploadcheckbox" style="display:none" name="file_type" value="isi">
                                    <span class="ui-icon ui-icon-radio-off" style="float:left"></span>ISI Export Format
                                </td>
                                <td class="select_span" style="width: 8em">
                                    <input type="radio" class="uploadcheckbox" style="display:none" name="file_type" value="bibtex">
                                    <span class="ui-icon ui-icon-radio-off" style="float:left"></span>BibTex
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan=2>
                        <br><br>
                        <div style="margin-left: 4px">
                            *Supported repositories:<br>
                            <a href="http://pubs.acs.org" target="_blank">ACS Publications</a><br>
                            <a href="http://journals.cambridge.org" target="_blank">Cambridge Journals</a><br>
                            <a href="http://highwire.stanford.edu" target="_blank">HighWire Press</a><br>
                            <a href="http://ieeexplore.ieee.org" target="_blank">IEEE Xplore</a><br>
                            <a href="http://www.informaworld.com" target="_blank">informaworld</a><br>
                            <a href="http://www.ingentaconnect.com" target="_blank">IngentaConnect</a><br>
                            <a href="http://www.jstor.org" target="_blank">JSTOR</a><br>
                            <a href="http://www.oxfordjournals.org" target="_blank">Oxford Journals</a><br>
                            <a href="http://ideas.repec.org" target="_blank">RePEc</a><br>
                            <a href="http://online.sagepub.com" target="_blank">SAGE Journals</a><br>
                            <a href="http://www.sciencedirect.com" target="_blank">Science Direct</a><br>
                            <a href="http://scitation.aip.org" target="_blank">Scitation</a><br>
                            <a href="http://www.scopus.com" target="_blank">Scopus</a>
                        </div>
                        <br>
                        <div style="margin-left: 4px">
                            **Only works if PDFs are on the same computer where Apache and PHP is installed.<br>
                        </div>

                    </td>
                </tr>
            </table>
            <table cellspacing="0" style="width:100%;display:none" id="table2">
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
                        <span class="ui-icon ui-icon-triangle-1-n shrinklist" style="cursor: pointer;"></span>
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
        <?php
    }
} else {
    print 'Super User or User permissions required.';
}
?>
