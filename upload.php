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

    session_write_close();

    include_once 'functions.php';

    $error = array();
    $message = array();

##########	let user know when the file is bigger than the directives allow	##########

    $directives = ' Your current settings in php.ini are:';
    $directives .= '<br>upload_max_filesize: ' . ini_get('upload_max_filesize');
    $directives .= '<br>post_max_size: ' . ini_get('post_max_size');

    if (isset($_FILES['form_new_file']['error']) && $_FILES['form_new_file']['error'] > 0) {

        if ($_FILES['form_new_file']['error'] == 1)
            $error[] = "Error! The uploaded file size exceeds the <b>upload_max_filesize</b> directive in php.ini.$directives";
        if ($_FILES['form_new_file']['error'] == 3)
            $error[] = "Error! The uploaded file was only partially uploaded.";
        if ($_FILES['form_new_file']['error'] == 6)
            $error[] = "Error! Missing a temporary folder.";
        if ($_FILES['form_new_file']['error'] == 7)
            $error[] = "Error! Failed to write file to disk.";
        if ($_FILES['form_new_file']['error'] == 8)
            $error[] = "Error! File upload stopped by extension.";
    }

    if (isset($_FILES['form_supplementary_file']['error']) && $_FILES['form_supplementary_file']['error'] > 0) {

        if ($_FILES['form_supplementary_file']['error'] == 1)
            $error[] = "Error! The uploaded file size exceeds the <b>upload_max_filesize</b> directive in php.ini.$directives";
        if ($_FILES['form_supplementary_file']['error'] == 3)
            $error[] = "Error! The uploaded file was only partially uploaded.";
        if ($_FILES['form_supplementary_file']['error'] == 6)
            $error[] = "Error! Missing a temporary folder.";
        if ($_FILES['form_supplementary_file']['error'] == 7)
            $error[] = "Error! Failed to write file to disk.";
        if ($_FILES['form_supplementary_file']['error'] == 8)
            $error[] = "Error! File upload stopped by extension.";
    }

##########	clean temp directory		##########

    $temp_files = glob ($temp_dir. DIRECTORY_SEPARATOR . 'lib_*.pdf', GLOB_NOSORT);

    foreach ($temp_files as $temp_file) {
        if (time() - filemtime($temp_file) > 3600)
            @unlink($temp_file);
    }

    $temp_pngs = glob ($library_path . DIRECTORY_SEPARATOR . 'pngs' . DIRECTORY_SEPARATOR . 'lib_*.png', GLOB_NOSORT);

    foreach ($temp_pngs as $temp_png) {
        if (time() - filemtime($temp_png) > 3600)
            @unlink($temp_png);
    }

    if (isset($_POST['form_sent'])) {
        if (isset($_POST['uid'])) $_POST['uid'] = array_filter($_POST['uid']);
        if (isset($_POST['url'])) $_POST['url'] = array_filter($_POST['url']);
    }
    
##########	reference recording	##########

    if (!empty($_POST['title']) && isset($_POST['form_sent'])) {
        
        ##########	remove line breaks from certain POST values	##########

        $order = array("\r\n", "\n", "\r");
        $keys = array('authors', 'affiliation', 'title', 'abstract', 'keywords');

        while (list($key, $field) = each($keys)) {
            if (!empty($_POST[$field])) {
                $_POST[$field] = str_replace($order, ' ', $_POST[$field]);
            }
        }

        ##########	record publication data, table library	##########

        database_connect($database_path, 'library');

        $query = "INSERT INTO library (file, authors, affiliation, title, journal, year, addition_date, abstract, rating, uid, volume, issue, pages, secondary_title, editor,
					url, reference_type, publisher, place_published, keywords, doi, authors_ascii, title_ascii, abstract_ascii, added_by, custom1, custom2, custom3, custom4, bibtex)
		 VALUES ((SELECT IFNULL((SELECT SUBSTR('0000' || CAST(MAX(file)+1 AS TEXT) || '.pdf',-9,9) FROM library),'00001.pdf')), :authors, :affiliation, :title, :journal, :year, :addition_date, :abstract, :rating, :uid, :volume, :issue, :pages, :secondary_title, :editor,
			:url, :reference_type, :publisher, :place_published, :keywords, :doi, :authors_ascii, :title_ascii, :abstract_ascii, :added_by, :custom1, :custom2, :custom3, :custom4, :bibtex)";

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
        $stmt->bindParam(':custom1', $custom1, PDO::PARAM_STR);
        $stmt->bindParam(':custom2', $custom2, PDO::PARAM_STR);
        $stmt->bindParam(':custom3', $custom3, PDO::PARAM_STR);
        $stmt->bindParam(':custom4', $custom4, PDO::PARAM_STR);
        $stmt->bindParam(':bibtex', $bibtex, PDO::PARAM_STR);

        if (empty($_POST['authors'])) {

            $authors = '';
            $authors_ascii = '';
        } else {

            $authors = $_POST['authors'];
            $authors_ascii = utf8_deaccent($authors);
        }

        empty($_POST['affiliation']) ? $affiliation = '' : $affiliation = $_POST['affiliation'];

        $title = $_POST['title'];
        $title_ascii = utf8_deaccent($title);

        empty($_POST['journal_abbr']) ? $journal = '' : $journal = $_POST['journal_abbr'];

        empty($_POST['secondary_title']) ? $secondary_title = '' : $secondary_title = $_POST['secondary_title'];

        empty($_POST['year']) ? $year = '' : $year = $_POST['year'];

        $addition_date = date('Y-m-d');

        if (empty($_POST['abstract'])) {

            $abstract = '';
            $abstract_ascii = '';
        } else {

            $abstract = $_POST['abstract'];
            $abstract_ascii = utf8_deaccent($abstract);
        }

        empty($_POST['rating']) ? $rating = '2' : $rating = $_POST['rating'];

        empty($_POST['uid'][0]) ? $uid = '' : $uid = join('|', array_filter($_POST['uid']));

        empty($_POST['volume']) ? $volume = '' : $volume = $_POST['volume'];

        empty($_POST['issue']) ? $issue = '' : $issue = $_POST['issue'];

        empty($_POST['pages']) ? $pages = '' : $pages = $_POST['pages'];

        empty($_POST['editor']) ? $editor = '' : $editor = $_POST['editor'];

        empty($_POST['url'][0]) ? $url = '' : $url = join('|', array_filter($_POST['url']));

        empty($_POST['reference_type']) ? $reference_type = 'article' : $reference_type = $_POST['reference_type'];

        empty($_POST['publisher']) ? $publisher = '' : $publisher = $_POST['publisher'];

        empty($_POST['place_published']) ? $place_published = '' : $place_published = $_POST['place_published'];

        if (empty($_POST['keywords'])) {
            $keywords = '';
        } else {
            $keywords = $_POST['keywords'];
            empty($_POST['keyword_separator']) ? $keyword_separator = '/' : $keyword_separator = $_POST['keyword_separator'];
            $keyword_array = explode($keyword_separator, $keywords);

            function trim_value(&$value) {
                $value = trim($value);
            }

            array_walk($keyword_array, 'trim_value');
            $keywords = implode(' / ', $keyword_array);
        }

        empty($_POST['doi']) ? $doi = '' : $doi = $_POST['doi'];

        empty($_POST['custom1']) ? $custom1 = '' : $custom1 = $_POST['custom1'];

        empty($_POST['custom2']) ? $custom2 = '' : $custom2 = $_POST['custom2'];

        empty($_POST['custom3']) ? $custom3 = '' : $custom3 = $_POST['custom3'];

        empty($_POST['custom4']) ? $custom4 = '' : $custom4 = $_POST['custom4'];

        empty($_POST['bibtex']) ? $bibtex = '' : $bibtex = $_POST['bibtex'];

        $added_by = $user_id;

        ##########	get the new filename and record item	##########
        $dbHandle->exec("BEGIN IMMEDIATE TRANSACTION");

        if (!empty($title))
            $insert = $stmt->execute();
        $stmt = null;

        $last_insert = $dbHandle->query("SELECT last_insert_rowid(),max(file) FROM library");
        $last_row = $last_insert->fetch(PDO::FETCH_ASSOC);
        $last_insert = null;
        $id = $last_row['last_insert_rowid()'];
        $new_file = $last_row['max(file)'];

        $dbHandle->exec("COMMIT");
        
        if ($insert == false)
            $error[] = htmlspecialchars('Error! The item has not been recorded.<br>' . $title);
        if ($insert == true)
            $message[] = htmlspecialchars('The item has been recorded.<br>' . $title);

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
        $stmt->bindParam(':fileid', $id);
        $stmt->bindParam(':categoryid', $category_id);

        $dbHandle->beginTransaction();
        while (list($key, $category_id) = each($categories)) {
            if (!empty($id))
                $stmt->execute();
        }
        $dbHandle->commit();
        $stmt = null;

        ##########	record publication data, table shelves	##########

        if (isset($_POST['shelf'])) {

            $user_query = $dbHandle->quote($user_id);
            $rows = $dbHandle->exec("INSERT OR IGNORE INTO shelves (fileID,userID) VALUES ($id,$user_query)");
            if ($rows == '0')
                $error[] = htmlspecialchars("Warning! The item has not been added to shelf.<br>" . $title);
            $rows = null;
            
            // DELETE SHELF CACHE
            @unlink($temp_dir . DIRECTORY_SEPARATOR . 'lib_' . session_id() . DIRECTORY_SEPARATOR . 'shelf_files');
        }

        ##########	record to projectsfiles	##########

        if (isset($_POST['project']) && !empty($_POST['projectID'])) {

            $rows = $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles (projectID,fileID) VALUES (" . intval($_POST['projectID']) . ",$id)");
            if ($rows == '0')
                $error[] = htmlspecialchars("Warning! The item has not been added to the project." . $title);
            $rows = null;
            
            // DELETE DESK CACHE
            $clean_files = glob($temp_dir . DIRECTORY_SEPARATOR . 'lib_*' . DIRECTORY_SEPARATOR .'desk_files', GLOB_NOSORT);
            foreach ($clean_files as $clean_file) {
                if (is_file($clean_file) && is_writable($clean_file))
                    @unlink($clean_file);
            }
        }

        ##########	ANALYZE	##########

        $dbHandle->exec("ANALYZE");
        $dbHandle = null;

        ##########	record to clipboard	##########

        if (isset($_POST['clipboard'])) {
            session_start();
            $_SESSION['session_clipboard'][] = $id;
            $_SESSION['session_clipboard'] = array_unique($_SESSION['session_clipboard']);
            session_write_close();
        }

        ##########	record pdf	##########

        $library_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'library';

        if (isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) {

            $pdf_contents = file_get_contents($_FILES['form_new_file']['tmp_name'], NULL, NULL, 0, 100);
            if (stripos($pdf_contents, '%PDF') === 0) {
                $move = move_uploaded_file($_FILES['form_new_file']['tmp_name'], $library_path . DIRECTORY_SEPARATOR . $new_file);
                if ($move == false)
                    $error[] = htmlspecialchars("Error! The PDF file has not been recorded.<br>" . $title);
                if ($move == true)
                    $message[] = htmlspecialchars("The PDF file has been recorded.<br>" . $title);
            } else {
                $error[] = "Error! No PDF was found. " . $pdf_contents;
            }
        }

        if (!empty($_POST['form_new_file_link'])) {
            $pdf_contents = proxy_file_get_contents($_POST['form_new_file_link'], $proxy_name, $proxy_port, $proxy_username, $proxy_password);
            if (stripos($pdf_contents, '%PDF') === 0) {
                $move = file_put_contents($library_path . DIRECTORY_SEPARATOR . $new_file, $pdf_contents);
                if ($move == false)
                    $error[] = htmlspecialchars("Error! The PDF file has not been recorded.<br>" . $title);
                if ($move == true)
                    $message[] = htmlspecialchars("The PDF file has been recorded.<br>" . $title);
            } else {
                $error[] = 'Error! I, Librarian could not find the PDF. Possible reasons:<br><br>You access the Web through a proxy server. Enter your proxy details in Tools->Settings.<br><br>The external service may be temporarily down. Try again later.<br><br>The link you provided is not for a PDF.';
            }
        }

        if (isset($_POST['filename']) && is_readable($temp_dir . DIRECTORY_SEPARATOR . $_POST['filename'])) {

            $copy = copy($temp_dir . DIRECTORY_SEPARATOR . $_POST['filename'], $library_path . DIRECTORY_SEPARATOR . $new_file);
            unlink($temp_dir . DIRECTORY_SEPARATOR . $_POST['filename']);
            if ($copy == false)
                $error[] = htmlspecialchars('Error! The PDF file has not been recorded.<br>' . $title);
            if ($copy == true)
                $message[] = htmlspecialchars("The PDF file has been recorded.<br>" . $title);
        }

        ##########	record supplementary files	##########

        for ($i = 1; $i <= 5; $i++) {

            if (isset($_FILES['form_supplementary_file' . $i]) && is_uploaded_file($_FILES['form_supplementary_file' . $i]['tmp_name']) && preg_match('/\.(php|php4|php3|htm|html)$/i', $_FILES['form_supplementary_file' . $i]['name']) == 0) {

                $supplement_filename = sprintf("%05d", intval($new_file)) . $_FILES['form_supplementary_file' . $i]['name'];

                $move = move_uploaded_file($_FILES['form_supplementary_file' . $i]['tmp_name'], "$library_path/supplement/$supplement_filename");
                if ($move == false)
                    $error[] = "Error! The supplementary file " . $_FILES['form_supplementary_file' . $i]['name'] . " has not been recorded.";
                if ($move == true)
                    $message[] = "The supplementary file file " . $_FILES['form_supplementary_file' . $i]['name'] . " has been recorded.";
            }
        }
        if (file_exists($library_path . DIRECTORY_SEPARATOR . $new_file)) {
            $unpack_dir = $temp_dir . DIRECTORY_SEPARATOR . $new_file;
            @mkdir($unpack_dir);
            exec(select_pdftk() . '"' . $library_path . DIRECTORY_SEPARATOR . $new_file . '" unpack_files output "' . $unpack_dir . '"');
            $unpacked_files = scandir($unpack_dir);
            foreach ($unpacked_files as $unpacked_file) {
                if (is_file($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file))
                    @rename($unpack_dir . DIRECTORY_SEPARATOR . $unpacked_file, $library_path . DIRECTORY_SEPARATOR . supplement . DIRECTORY_SEPARATOR . sprintf("%05d", intval($new_file)) . $unpacked_file);
            }
            @rmdir($unpack_dir);
        }


        ##########	record graphical abstract	##########

        if (isset($_FILES['form_graphical_abstract']) && is_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'])) {
            $extension = pathinfo($_FILES['form_graphical_abstract']['name'], PATHINFO_EXTENSION);
            if (empty($extension))
                $extension = 'jpg';
            $new_name = sprintf("%05d", intval($new_file)) . 'graphical_abstract.' . $extension;
            move_uploaded_file($_FILES['form_graphical_abstract']['tmp_name'], "$library_path" . DIRECTORY_SEPARATOR . "supplement" . DIRECTORY_SEPARATOR . "$new_name");
        }

        ##########	extract text from pdf	##########

        if ((isset($copy) && $copy) || (isset($move) && $move)) {

            system(select_pdftotext() . '"' . $library_path . DIRECTORY_SEPARATOR . $new_file . '" "' . $temp_dir . DIRECTORY_SEPARATOR . $new_file . '.txt"', $ret);

            if (is_file($temp_dir . DIRECTORY_SEPARATOR . $new_file . ".txt")) {

                $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, 				unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

                $stopwords = explode(', ', $stopwords);

                $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $new_file . ".txt");
                unlink($temp_dir . DIRECTORY_SEPARATOR . $new_file . ".txt");

                if (!empty($string)) {

                    $string = preg_replace('/[^[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}]/', ' ', $string);

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

                    database_connect($database_path, 'fulltext');

                    $file_query = $dbHandle->quote($id);
                    $fulltext_query = $dbHandle->quote($string);

                    $dbHandle->beginTransaction();

                    $dbHandle->exec("DELETE FROM full_text WHERE fileID=$file_query");
                    $dbHandle->exec("INSERT INTO full_text (fileID,full_text) VALUES ($file_query,$fulltext_query)");

                    $dbHandle->commit();

                    $dbHandle = null;
                } else {

                    $error[] = "Warning! The PDF file cannot be indexed for full text search.";
                }
            }
        }

        $_POST = array();

        if (!empty($error) || !empty($message)) {
            $json = array();
            if (!empty($error))
                $json['error'] = $error;
            if (!empty($message))
                $json['message'] = $message;
            $json_output = json_encode($json);
            die($json_output);
        }

##########	no form data, only PDF, try to fetch DOI and PMID	##########
    } elseif (isset($_POST['form_sent']) && ((isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) || !empty($_POST['form_new_file_link']))) {

        $dir = dirname(__FILE__);
        $rand = uniqid('lib_' . mt_rand(10, 99));

        if (isset($_FILES['form_new_file']) && is_uploaded_file($_FILES['form_new_file']['tmp_name'])) {
            $pdf_contents = file_get_contents($_FILES['form_new_file']['tmp_name'], NULL, NULL, 0, 100);
            if (stripos($pdf_contents, '%PDF') === 0) {
                move_uploaded_file($_FILES['form_new_file']['tmp_name'], $temp_dir . DIRECTORY_SEPARATOR . $rand . ".pdf");
                $_POST['title'] = $_FILES['form_new_file']['name'];
                $_POST['tempfile'] = $rand . ".pdf";
            } else {
                $error[] = "Error! No PDF was found.";
            }
        }
        if (!empty($_POST['form_new_file_link'])) {
            $pdf_contents = proxy_file_get_contents($_POST['form_new_file_link'], $proxy_name, $proxy_port, $proxy_username, $proxy_password);
            if (stripos($pdf_contents, '%PDF') === 0) {
                if (!empty($pdf_contents))
                    file_put_contents($temp_dir . DIRECTORY_SEPARATOR . $rand . ".pdf", $pdf_contents);
                $_POST['title'] = $rand . ".pdf";
                $_POST['tempfile'] = $rand . ".pdf";
            } else {
                die('Error! I, Librarian could not find the PDF. Possible reasons:<br><br>You access the Web through a proxy server. Enter your proxy details in Tools->Settings.<br><br>The external service may be temporarily down. Try again later.<br><br>The link you provided is not for a PDF.');
            }
        }

        if (is_readable($temp_dir . DIRECTORY_SEPARATOR . $rand . ".pdf")) {

            ##########	try to find DOI on the first page	##########

            system(select_pdftotext() . '-l 1 "' . $temp_dir . DIRECTORY_SEPARATOR . $rand . '.pdf" "' . $temp_dir . DIRECTORY_SEPARATOR . $rand . '.txt"', $ret);

            if (is_file($temp_dir . DIRECTORY_SEPARATOR . $rand . ".txt")) {

                $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $rand . ".txt");
                unlink($temp_dir . DIRECTORY_SEPARATOR . $rand . ".txt");
            }

            if (!empty($string)) {

                $string = preg_replace('/[^[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}]/', ' ', $string);

                $order = array("\r\n", "\n", "\r");
                $replace = ' ';
                $string = str_replace($order, $replace, $string);

                $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                $replace = '-';
                $string = str_replace($order, $replace, $string);

                preg_match('/10\.\d{4}\/\S+/ui', $string, $doi);
                $doi = current($doi);

                if (substr($doi, -1) == ')' || substr($doi, -1) == ']') {
                    preg_match_all('/(.)(doi:\s?)?(10\.\d{4}\/\S+)/ui', $string, $doi2, PREG_PATTERN_ORDER);
                    if (substr($doi, -1) == ')' && $doi2[1][0] == '(')
                        $doi = substr($doi, 0, -1);
                    if (substr($doi, -1) == ']' && $doi2[1][0] == '[')
                        $doi = substr($doi, 0, -1);
                }

                preg_match('/(?<=arXiv:)\S+/ui', $string, $arxiv_id);
                $arxiv_id = current($arxiv_id);
            }

            if (empty($doi) && empty($arxiv_id)) {

                ##########	try to find DOI in the whole PDF	##########

                system(select_pdftotext() . '"' . $temp_dir . DIRECTORY_SEPARATOR . $rand . '.pdf" "' . $temp_dir . DIRECTORY_SEPARATOR . $rand . '.txt"', $ret);

                if (is_file($temp_dir . DIRECTORY_SEPARATOR . $rand . ".txt")) {

                    $string = file_get_contents($temp_dir . DIRECTORY_SEPARATOR . $rand . ".txt");
                    unlink($temp_dir . DIRECTORY_SEPARATOR . $rand . ".txt");
                }

                if (!empty($string)) {

                    $string = preg_replace('/[^[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2}]/', ' ', $string);

                    $order = array("\r\n", "\n", "\r");
                    $replace = ' ';
                    $string = str_replace($order, $replace, $string);

                    $order = array("\xe2\x80\x93", "\xe2\x80\x94");
                    $replace = '-';
                    $string = str_replace($order, $replace, $string);

                    preg_match('/10\.\d{4}\/\S+/ui', $string, $doi);
                    $doi = current($doi);

                    if (substr($doi, -1) == ')' || substr($doi, -1) == ']') {
                        preg_match_all('/(.)(doi:\s?)?(10\.\d{4}\/\S+)/ui', $string, $doi2, PREG_PATTERN_ORDER);
                        if (substr($doi, -1) == ')' && $doi2[1][0] == '(')
                            $doi = substr($doi, 0, -1);
                        if (substr($doi, -1) == ']' && $doi2[1][0] == '[')
                            $doi = substr($doi, 0, -1);
                    }

                    preg_match('/(?<=arXiv:)\S+/ui', $string, $arxiv_id);
                    $arxiv_id = current($arxiv_id);
                }
            }
        }
    }

##########	if user sent only DOI, or PMID, or PDF	##########

    if (isset($_POST['form_sent']) && (!empty($doi) || !empty($_POST['doi']) || !empty($_POST['uid'][0]) || !empty($arxiv_id))) {

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

        if (!empty($arxiv_id)) {

            ##########	arXiv	##########

            $request_url = "http://export.arxiv.org/api/query?id_list=" . urlencode($arxiv_id) . "&start=0&max_results=1";

            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
            if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
	
            $record = $xml->entry;

            $_POST['title'] = $record->title;

            $children = $record->children('http://arxiv.org/schemas/atom');
            $_POST['secondary_title'] = $children->journal_ref;

            $_POST['doi'] = $children->doi;

            $pub_date = $record->published;
            $_POST['year'] = date("Y-m-d", strtotime($pub_date));

            $_POST['abstract'] = trim($record->summary);

            $authors = $record->author;

            $name_array = array();
            if (!empty($authors)) {

                foreach ($authors as $author) {

                    $author = $author->name;
                    $author_array = explode(' ', $author);
                    $last = array_pop($author_array);
                    $first = join (' ', $author_array);
                    $name_array[] = 'L:"'.$last.'",F:"'.$first.'"';
                }
            }

            if (isset($name_array))
                $_POST['authors'] = join(";", $name_array);

            $category = $children->primary_category;
            $_POST['keywords'] = $category->attributes();

            $_POST['uid'] = "ARXIV:$arxiv_id";

            $_POST['url'] = "http://arxiv.org/abs/$arxiv_id";
        }

        ##########	open esearch, fetch PMID	##########

        if (empty($pmid) && !empty($doi)) {

            $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=Pubmed&term=" . $doi . "[AID]&usehistory=y&retstart=&retmax=1&sort=&tool=I,Librarian&email=i.librarian.software@gmail.com";

            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
            if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
	
            $count = $xml->Count;
            if ($count == 1)
                $pmid = $xml->IdList->Id;
        }

        if (!empty($pmid)) {

            ##########	open efetch, read xml	##########

            $request_url = "http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=Pubmed&rettype=abstract&retmode=XML&id=" . urlencode($pmid) . "&tool=I,Librarian&email=i.librarian.software@gmail.com";

            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
            if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
	
            $istitle = '';
            if (!empty($xml))
                $istitle = $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

            if (!empty($istitle)) {

                if (!isset($doi)) {

                    foreach ($xml->PubmedArticle->PubmedData->ArticleIdList->ArticleId as $articleid) {

                        preg_match('/10\.\d{4}\/\S+/i', $articleid, $doi);

                        if (count($doi) > 0) {
                            $doi = current($doi);
                            break;
                        }
                    }
                }

                if (!empty($doi))
                    $_POST['doi'] = $doi;

                $_POST['uid'] = 'PMID:' . $pmid;

                $_POST['url'] = "http://www.pubmed.org/$pmid";

                $_POST['reference_type'] = strtolower($xml->PubmedArticle->MedlineCitation->Article->PublicationTypeList->PublicationType[0]);

                $_POST['title'] = $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;

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
                    $_POST['abstract'] = implode(' ', $abstract_array);
                }

                $_POST['secondary_title'] = $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;

                $day = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Day;
                $month = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Month;
                $year = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate->Year;

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

                $_POST['volume'] = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;

                $_POST['issue'] = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;

                $_POST['pages'] = $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

                $_POST['journal_abbr'] = $xml->PubmedArticle->MedlineCitation->MedlineJournalInfo->MedlineTA;

                $_POST['affiliation'] = $xml->PubmedArticle->MedlineCitation->Article->Affiliation;

                $authors = $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author;

                $name_array = array();
                if (!empty($authors)) {
                    foreach ($authors as $author) {
                        $name_array[] = 'L:"' . $author->LastName . '",F:"' . $author->ForeName . '"';
                    }
                }

                $mesh = $xml->PubmedArticle->MedlineCitation->MeshHeadingList->MeshHeading;

                if (!empty($mesh)) {
                    foreach ($mesh as $meshheading) {
                        $mesh_array[] = $meshheading->DescriptorName;
                    }
                }

                if (isset($name_array))
                    $_POST['authors'] = join(";", $name_array);
                if (isset($mesh_array))
                    $_POST['keywords'] = join(" / ", $mesh_array);
            }
        }

        if ((!empty($doi) || !empty($nasa_id)) && empty($pmid)) {

            if (!empty($nasa_id))
                $lookfor = 'bibcode=' . urlencode($nasa_id);
            if (!empty($doi)) {
                $lookfor = 'doi=' . urlencode($doi);
                $_POST['doi'] = $doi;
            }

            ############ NASA ADS ##############

            $request_url = "http://adsabs.harvard.edu/cgi-bin/abs_connect?" . $lookfor . "&data_type=XML&return_req=no_params&start_nr=1&nr_to_return=1";

            $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
            if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
            Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
	
            foreach ($xml->attributes() as $a => $b) {

                if ($a == 'selected') {
                    $count = $b;
                    break;
                }
            }

            if ($count == 1) {

                $record = $xml->record;

                $bibcode = $record->bibcode;
                $_POST['title'] = $record->title;

                $journal = $record->journal;
                if (strstr($journal, ","))
                    $_POST['secondary_title'] = substr($journal, 0, strpos($journal, ','));

                $eprintid = $record->eprintid;
                if (!empty($eprintid))
                    $eprintid = substr($eprintid, strpos($eprintid, ":") + 1);
                if (strstr($journal, "arXiv"))
                    $eprintid = substr($journal, strpos($journal, ":") + 1);

                $doi = $record->DOI;
                if (!empty($doi))
                    $_POST['doi'] = $doi;

                $_POST['volume'] = $record->volume;
                $_POST['pages'] = $record->page;
                $last_page = $record->lastpage;
                if (!empty($last_page))
                    $_POST['pages'] = $_POST['pages'] . '-' . $last_page;

                $_POST['affiliation'] = $record->affiliation;

                $year = $record->pubdate;
                $_POST['year'] = date('Y-m-d', strtotime($year));

                $_POST['abstract'] = $record->abstract;
                $nasa_url = $record->url;

                foreach ($record->link as $links) {

                    foreach ($links->attributes() as $a => $b) {

                        if ($a == 'type' && $b == 'EJOURNAL') {
                            $ejournal_url = $links->url;
                        } elseif ($a == 'type' && $b == 'PREPRINT') {
                            $preprint_url = $links->url;
                        } elseif ($a == 'type' && $b == 'GIF') {
                            $gif_url = $links->url;
                        }
                    }
                }

                $authors = $record->author;
                
                $name_array = array();

                if (!empty($authors)) {

                    foreach ($authors as $author) {
                        $author_array = explode (",", $author);
                        $name_array[] = 'L:"'.trim($author_array[0]).'",F:"'.trim($author_array[1]).'"';
                    }
                }

                if (isset($name_array))
                    $_POST['authors'] = join(";", $name_array);

                $keywords = $record->keywords;

                if (!empty($keywords)) {

                    foreach ($keywords as $keyword) {

                        $keywords_array[] = $keyword->keyword;
                    }
                }

                if (isset($keywords_array))
                    $_POST['keywords'] = join(" / ", $keywords_array);

                $uid_array = array();
                if (!empty($bibcode))
                    $uid_array[] = "NASAADS:$bibcode";
                if (!empty($eprintid))
                    $uid_array[] = "ARXIV:$eprintid";
                $_POST['uid'] = join("|", $uid_array);

                $url_array = array();
                $url_array[] = $nasa_url;
                if (!empty($eprintid))
                    $url_array[] = "http://arxiv.org/abs/$eprintid";
                $_POST['url'] = join("|", $url_array);
            }

            if ($count < 1) {

                ############ CrossRef ##############

                $request_url = "http://www.crossref.org/openurl/?id=doi:" . urlencode($doi) . "&noredirect=true&pid=i.librarian.software@gmail.com";

                $xml = proxy_simplexml_load_file($request_url, $proxy_name, $proxy_port, $proxy_username, $proxy_password);
                if (empty($xml)) die('Error! I, Librarian could not connect with an external web service. This usually indicates that you access the Web through a proxy server.
                Enter your proxy details in Tools->Settings. Alternatively, the external service may be temporarily down. Try again later.');
	
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

                    $_POST['secondary_title'] = $record->journal_title;
                    if(empty($_POST['secondary_title'])) $_POST['secondary_title'] = $record->volume_title;
                    $_POST['year'] = $record->year;
                    $_POST['year'] = $_POST['year'] . '-01-01';
                    $_POST['volume'] = $record->volume;
                    $_POST['issue'] = $record->issue;
                    $_POST['pages'] = $record->first_page;
                    $last_page = $record->last_page;
                    if (!empty($last_page))
                        $_POST['pages'] = $_POST['pages'] . "-" . $last_page;
                    $_POST['title'] = $record->article_title;
                    
                    $authors = array();

                    foreach ($record->contributors->contributor as $contributor) {

                        $authors1 = $contributor->surname;
                        $authors2 = $contributor->given_name;
                        $authors[] = 'L:"' . $authors1 . '",F:"' . $authors2 . '"';
                    }
                    $_POST['authors'] = join(";", $authors);
                }
            }
        }

        ##########	check for duplicate titles in table library	##########

        database_connect($database_path, 'library');

        if (!empty($_POST['doi'])) {
            $doi_query = $dbHandle->quote($_POST['doi']);
            $result = $dbHandle->query("SELECT id,title FROM library WHERE doi=$doi_query LIMIT 1");
        } else {
            $title_query = $dbHandle->quote(substr($_POST['title'], 0, -2) . "%");
            $result = $dbHandle->query("SELECT id,title FROM library WHERE title LIKE $title_query AND length(title) <= " . (strlen($_POST['title']) + 2) . " LIMIT 1");
        }
        $result = $result->fetchAll(PDO::FETCH_ASSOC);
        $dbHandle = null;

        if (count($result) > 0) {
            $error[] = "Warning! This article is a possible duplicate of:<br>
		<a href=\"" . htmlspecialchars("stable.php?id=" . urlencode($result[0]['id'])) . "\" style=\"color:#fff;font-weight:bold\" target=\"_blank\">" . htmlspecialchars($result[0]['title']) . "</a>";
        }
        $result = null;

        $paper_urls = array();
        if (!empty($_POST['url']) && is_string($_POST['url']))
            $paper_urls = explode('|', $_POST['url']);

        $paper_uids = array();
        if (!empty($_POST['uid']) && is_string($_POST['uid']))
            $paper_uids = explode('|', $_POST['uid']);
    }

    if (!empty($_POST['title']) && !isset($_POST['form_sent'])) {

        ##########	check for duplicate titles in table library	##########

        database_connect($database_path, 'library');

        if (!empty($_POST['doi'])) {
            $doi_query = $dbHandle->quote($_POST['doi']);
            $result = $dbHandle->query("SELECT id,title FROM library WHERE doi=$doi_query LIMIT 1");
        } else {
            $title_query = $dbHandle->quote(substr($_POST['title'], 0, -2) . "%");
            $result = $dbHandle->query("SELECT id,title FROM library WHERE title LIKE $title_query AND length(title) <= " . (strlen($_POST['title']) + 2) . " LIMIT 1");
        }
        $result = $result->fetchAll(PDO::FETCH_ASSOC);
        $dbHandle = null;

        if (count($result) > 0) {
            $error[] = "Warning! This article is a possible duplicate of:<br>
		<a href=\"" . htmlspecialchars("stable.php?id=" . urlencode($result[0]['id'])) . "\" style=\"color:#fff;font-weight:bold\" target=\"_blank\">" . htmlspecialchars($result[0]['title']) . "</a>";
        }
        $result = null;
    }

    if (!empty($_POST['uid']) && is_array($_POST['uid'])) {
        $paper_uids = array();
        $paper_uids = $_POST['uid'];
        foreach ($paper_uids as $paper_uid) {
            $split_uid = array();
            $split_uid = explode(':', $paper_uid);
            if ($split_uid[0] == 'PMCID')
                $web_pdf = 'http://www.ncbi.nlm.nih.gov/pmc/articles/PMC' . urlencode($split_uid[1]) . '/pdf';
        }
    }
    if (!empty($_POST['url']) && is_array($_POST['url']) && empty($paper_urls)) {
        $paper_urls = $_POST['url'];
    }
    if (!empty($_POST['form_new_file_link']))
        $web_pdf = $_POST['form_new_file_link'];
    if (!empty($error)) {
        foreach ($error as $err) {
            print '<div class="upload-errors" style="display:none">' . $err . '</div>';
        }
    }
    
    if (!isset($_POST['form_sent']) && empty($_POST['title']) && empty($_GET['none'])) {
        ?>
    <form enctype="multipart/form-data" action="upload.php" method="POST" class="uploadform">
        <input type="hidden" name="form_sent">
        <div style="margin:4px;font-weight:bold">Add single item using:</div><br><br>
        <div class="item-sticker alternating_row ui-widget-content ui-corner-all" style="width:80%;margin:auto;padding:0">
            <table cellspacing="0" class="alternating_row ui-corner-all" style="width:100%;border-spacing:6px;margin:auto">
                <tr>
                    <td style="width:11em">
                        Local PDF file:
                    </td>
                    <td>
                        <input type="file" name="form_new_file" accept="application/pdf">
                    </td>
                </tr>
                <tr>
                    <td>
                        PDF from the Web:
                    </td>
                    <td>
                        <input type="text" name="form_new_file_link" value="" size="85" style="width:99%" placeholder="http://www.example.com/document.pdf">
                    </td>
                </tr>
                <tr>
                    <td>
                        Database UID:
                    </td>
                    <td>
                        <input type="text" size="80" name="uid[]" style="width:99%" value=""
                               placeholder="PMID:123456 or PMCID:123456 or NASAADS:123456 or ARXIV:123456">
                    </td>
                </tr>
                <tr>
                    <td>
                        DOI number:
                    </td>
                    <td>
                        <input type="text" size="80" name="doi" style="width:99%" value="" placeholder="10.1000/182">
                    </td>
                </tr>
            </table>
            <div class="separator" style="margin:0"></div>
            <table cellspacing="0" class="alternating_row ui-corner-all" style="width:100%;border-spacing:6px;margin:auto">
                    <tr>
                        <td style="width:11em">
                            <button class="uploadsave">Proceed</button>
                        </td>
                        <td>
                            <button id="button-none">None of the above</buttton>
                        </td>
                    </tr>
            </table>
        </div>
    </form>
<?php
    } else {
    ?>

    <form>
        <!-- This mock form is here due to IE and/or jQuery form plug-in bug. When form is inserted after ajaxSubmit dynamically, IE erases it. -->
        <input type="submit" style="display:none">
    </form>

    <form enctype="multipart/form-data" action="upload.php" method="POST" class="uploadform">
        <input type="hidden" name="form_sent">
        <table cellspacing=0 style="width:100%;margin-top: 4px;margin-bottom: 1px;">
            <tr>
                <td>
                    <div class="ui-state-highlight ui-corner-top open2" style="float:right;margin-left:2px;margin-right:4px;padding-right:4px">
                        <span class="ui-icon ui-icon-document-b" style="float:left"></span>Supplements
                    </div>
                    <div class="ui-state-highlight ui-corner-top open3" style="float:right;margin-left:2px;padding-right:4px">
                        <span class="ui-icon ui-icon-tag" style="float:left"></span>Categories
                    </div>
                    <div class="ui-state-highlight ui-corner-top open1 clicked" style="float:right;padding-right:4px">
                        <span class="ui-icon ui-icon-document" style="float:left"></span>Metadata
                    </div>
                    <b>&nbsp;Add new record</b>
                </td>
            </tr>
        </table>

        <table cellspacing="0" style="width:100%;border-top: solid 1px #D5D6D8" class="test">
            <tr>
                <td class="threedleft">
                    <button class="uploadsave">Save</button>
                </td>
                <td class="threedright">
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

                    $id_query = $dbHandle->quote($user_id);

                    $result = $dbHandle->query("SELECT DISTINCT projects.projectID AS projectID,project FROM projects
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
            <tr>
                <td class="threedleft">
                    Paper rating:
                </td>
                <td class="threedright">
                    <table cellspacing=0>
                        <tr>
                            <td class="select_span" style="line-height:16px;width:6em">
                                <input type="radio" class="uploadcheckbox" style="display:none" name="rating" value="1">
                                <span class="ui-icon ui-icon-radio-off" style="float:left">
                                </span>Low
                            </td>
                            <td class="select_span" style="line-height:16px;width:8em">
                                <input type="radio" checked class="uploadcheckbox" style="display:none" name="rating" value="2">
                                <span class="ui-icon ui-icon-radio-on" style="float:left">
                                </span>Medium
                            </td>
                            <td class="select_span" style="line-height:16px;width:6em">
                                <input type="radio" class="uploadcheckbox" style="display:none" name="rating" value="3">
                                <span class="ui-icon ui-icon-radio-off" style="float:left">
                                </span>High
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table cellspacing="0" style="width:100%" class="table1">
            <tr>
                <td class="threedleft">
                    Local PDF file:
                </td>
                <td class="threedright">
    <?php
    if (!empty($_POST['tempfile'])) {

        print "<input type=\"hidden\" name=\"filename\" value=\"$_POST[tempfile]\">";
        if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external'))
            print "<iframe class=\"pdf-file\" src=\"temp.php?tempfile=$_POST[tempfile]#pagemode=none&scrollbar=1&page=1&navpanes=0&toolbar=0&statusbar=0&view=FitH,20\" style=\"display:block;width:99%;height:300px;border:1px inset #afaea9\"></iframe>";
        if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal')
            print "<iframe class=\"pdf-file\" src=\"viewpdf.php?file=$_POST[tempfile]&page=1&navpanes=0&toolbar=0\" style=\"display:block;width:99%;height:300px;border:1px inset #a5a6a9\"></iframe>";
    } else {
        print '<input type="file" name="form_new_file" accept="application/pdf">';
    }
    ?>
                </td>
            </tr>
    <?php
    if (empty($_POST['tempfile'])) {
        ?>
                <tr>
                    <td class="threedleft">
                        PDF from the Web:
                    </td>
                    <td class="threedright">
                        <input type="text" name="form_new_file_link" value="<?php if (!empty($web_pdf)) print $web_pdf; ?>" size="85" style="width:99%">
                    </td>
                </tr>
        <?php
    }
    ?>
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
                    <div class="adduidrow" style="float:right;cursor:pointer">+</div>
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="uid[]" style="width:99%" value=""
                           title="<b>Examples:</b><br>PMID:123456<br>PMCID:123456<br>NASAADS:123456<br>ARXIV:123456">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    DOI:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="doi" style="width:99%" value="<?php print isset($_POST['doi']) ? htmlspecialchars($_POST['doi']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Title:
                </td>
                <td class="threedright">
                    <textarea name="title" cols=80 rows=2 wrap="soft" style="width:99%"><?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Authors:
                </td>
                <td class="threedright">
                    <div class="author-inputs" style="max-height: 200px;overflow:auto">
                        <?php
                        if (empty($_POST['authors'])) {
                            ?>
                        <div>
                            Last name: <input type="text" value=""> 
                            First name: <input type="text" value="">
                        </div>
                        <div>
                            Last name: <input type="text" value=""> 
                            First name: <input type="text" value="">
                        </div>
                        <div>
                            Last name: <input type="text" value=""> 
                            First name: <input type="text" value="">
                        </div>
                        <div>
                            Last name: <input type="text" value=""> 
                            First name: <input type="text" value="">
                        </div>
                        <div>
                            Last name: <input type="text" value=""> 
                            First name: <input type="text" value="">
                            <span class="addauthorrow" style="cursor:pointer">+</span>
                        </div>
                        <?php
                        } else {
                            $array = array();
                            $new_authors = array();
                            $array = explode(';', $_POST['authors']);
                            $array = array_filter($array);
                            if (!empty($array)) {
                                foreach ($array as $author) {
                                    $array2 = explode(',', $author);
                                    $last = trim($array2[0]);
                                    $last = substr($array2[0], 3, -1);
                                    $first = trim($array2[1]);
                                    $first = substr($array2[1], 3, -1);
                                    if (!empty($last)) print '<div>Last name: <input class="author-last" type="text" value="' . $last . '"> First name: <input class="author-first" type="text" value="' . $first . '"></div>';
                                }
                            }
                            print '<div>
                            Last name: <input type="text" value=""> 
                            First name: <input type="text" value="">
                            <span class="addauthorrow" style="cursor:pointer">+</span>
                            </div>';
                        }
                        ?>
                    </div>
                    <input type="hidden" name="authors" value="<?php echo isset($_POST['authors']) ? htmlspecialchars($_POST['authors']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Affiliation:
                </td>
                <td class="threedright">
                    <textarea cols="80" rows="2" name="affiliation" style="width:99%"><?php echo isset($_POST['affiliation']) ? htmlspecialchars($_POST['affiliation']) : ''; ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Journal abbreviation:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="journal_abbr" style="width:99%" value="<?php print isset($_POST['journal_abbr']) ? htmlspecialchars($_POST['journal_abbr']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Secondary title:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="secondary_title" style="width:99%" value="<?php print isset($_POST['secondary_title']) ? htmlspecialchars($_POST['secondary_title']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publication date:
                </td>
                <td class="threedright">
                    <input type="text" size="10" maxlength="10" name="year" value="<?php echo isset($_POST['year']) ? htmlspecialchars($_POST['year']) : '' ?>"> YYYY-MM-DD
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Volume:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="volume" value="<?php echo isset($_POST['volume']) ? htmlspecialchars($_POST['volume']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Issue:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="issue" value="<?php echo isset($_POST['issue']) ? htmlspecialchars($_POST['issue']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Pages:
                </td>
                <td class="threedright">
                    <input type="text" size="10" name="pages" value="<?php echo isset($_POST['pages']) ? htmlspecialchars($_POST['pages']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Abstract:
                </td>
                <td class="threedright">
                    <textarea name="abstract" cols=80 rows=5 wrap="soft" style="width:99%"><?php echo isset($_POST["abstract"]) ? htmlspecialchars($_POST['abstract']) : '' ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Editor:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="editor" style="width:99%" value="<?php echo isset($_POST['editor']) ? htmlspecialchars($_POST['editor']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publisher:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="publisher" style="width:99%" value="<?php echo isset($_POST['publisher']) ? htmlspecialchars($_POST['publisher']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Place published:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="place_published" style="width:99%" value="<?php echo isset($_POST['place_published']) ? htmlspecialchars($_POST['place_published']) : '' ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Keywords:
                </td>
                <td class="threedright">
                    <textarea name="keywords" cols=80 rows=2 wrap="soft" style="width:99%" title="Reserved for keywords provided by internet databases. For your custom keywords use Categories.<br>Separator: space, forward slash, space &quot; / &quot;"><?php echo isset($_POST["keywords"]) ? htmlspecialchars($_POST['keywords']) : '' ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Keyword separator:
                </td>
                <td class="threedright">
                    <select name="keyword_separator">
                        <option> / </option>
                        <option>;</option>
                        <option>,</option>
                    </select>
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
                    <div class="addurlrow" style="float:right;cursor:pointer">+</div>
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="url[]" style="width:99%" value="">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Publication type:
                </td>
                <td class="threedright">
                    <select name="reference_type">
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'article') ? 'selected' : ''  ?>>article</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'book') ? 'selected' : ''  ?>>book</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'chapter') ? 'selected' : ''  ?>>chapter</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'conference') ? 'selected' : ''  ?>>conference</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'manual') ? 'selected' : ''  ?>>manual</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'thesis') ? 'selected' : ''  ?>>thesis</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'patent') ? 'selected' : ''  ?>>patent</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'technical report') ? 'selected' : ''  ?>>technical report</option>
                        <option <?php print (!empty($_POST['reference_type']) && $_POST['reference_type'] == 'electronic') ? 'selected' : ''  ?>>electronic</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    BibTex key:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="bibtex" style="width:99%" value="<?php print isset($_POST['bibtex']) ? htmlspecialchars($_POST['bibtex']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 1:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom1" style="width: 99%" value="<?php print isset($_POST['custom1']) ? htmlspecialchars($_POST['custom1']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 2:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom2" style="width: 99%" value="<?php print isset($_POST['custom2']) ? htmlspecialchars($_POST['custom2']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 3:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom3" style="width: 99%" value="<?php print isset($_POST['custom3']) ? htmlspecialchars($_POST['custom3']) : ''  ?>">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Custom 4:
                </td>
                <td class="threedright">
                    <input type="text" size="80" name="custom4" style="width: 99%" value="<?php print isset($_POST['custom4']) ? htmlspecialchars($_POST['custom4']) : ''  ?>">
                </td>
            </tr>
        </table>
        <table cellspacing="0" style="width:100%;display:none" class="table2">
            <tr>
                <td class="threedleft">
                    Graphical abstract:
                </td>
                <td class="threedright">
                    <input type="file" name="form_graphical_abstract">
                </td>
            </tr>
            <tr>
                <td class="threedleft">
                    Supplementary files:
                </td>
                <td class="threedright">
                    <input type="file" name="form_supplementary_file1"><br>
                    <input type="file" name="form_supplementary_file2"><br>
                    <input type="file" name="form_supplementary_file3"><br>
                    <input type="file" name="form_supplementary_file4"><br>
                    <input type="file" name="form_supplementary_file5">
                </td>
            </tr>
        </table>
        <table cellspacing="0" style="width:100%;display:none" class="table3">
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
        $cat_all[$category['categoryID']] = $category['category'];
        if ($i > (1 + $totalcount / 2) && !$isdiv) {
            print '</table><table cellspacing=0 style="width: 49%;float: right;padding:2px">';
            $isdiv = true;
        }
        print PHP_EOL . '<tr><td class="select_span">';
        print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
        print " style=\"display:none\"><span class=\"ui-icon ui-icon-close\" style=\"float:left\"></span>" . htmlspecialchars($category['category']) . "</td></tr>";
        $i = $i + 1;
    }
    $result = null;
    $dbHandle = null;
    ?>
                        </table>
                    </div>
                    <span class="ui-icon ui-icon-triangle-1-s enlargelist" style="cursor: pointer;float:left"></span>
                    <span class="ui-icon ui-icon-triangle-1-n shrinklist" style="cursor: pointer"></span>
                    <div class="suggestions" style="width: 574px">
    <?php
    if (!empty($_POST['title'])) {
        $cat_all = array_unique($cat_all);
        while (list($key, $value) = each($cat_all)) {
            if (stristr("$_POST[title] $_POST[abstract]", $value))
                $suggested_categories[] = "<span style=\"cursor: pointer\">$value</span>";
        }
        if (!empty($suggested_categories))
            print 'Suggestions: ' . implode(", ", $suggested_categories);
    }
    ?>
                    </div>
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