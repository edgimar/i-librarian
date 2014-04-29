<?php
include_once 'data.php';

//METADATA

if (isset($_GET['searchtype']) && $_GET['searchtype'] == 'metadata') {

    $input = $_GET['search-metadata'];

    //SPLIT SEARCH INTO INDIVIDUAL TERMS
    $search_array = preg_split('/ AND | OR | NOT /', $input);

    //CREATE A QUERY FOR EACH TERM
    foreach ($search_array as $search) {

        //SPLIT THE TERM INTO AN OPTIONAL PARETHESIS, LAZY TEXT, AND A TAG
        if (preg_match('/([\s\(]*)(.*)?(\[[a-z]{2}\])/i', $search, $matches) == 1) {

            //FORMAT THE TEXT FOR SEARCH
            $like_query_esc = str_replace("\\", "\\\\", trim($matches[2]));
            $like_query_esc = str_replace("%", "\%", $like_query_esc);
            $like_query_esc = str_replace("_", "\_", $like_query_esc);
            $like_query_esc = str_replace("<*>", "%", $like_query_esc);
            $like_query_esc = str_replace("<?>", "_", $like_query_esc);

            $regexp_query_esc = addcslashes(trim($matches[2]), "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query_esc = str_replace('\<\*\>', '.*', $regexp_query_esc);
            $regexp_query_esc = str_replace('\<\?\>', '.?', $regexp_query_esc);

            $like_query = $dbHandle->quote("%$like_query_esc%");
            $regexp_query = str_replace("'", "''", $regexp_query_esc);

            //CHECK WHETHER TEXT CONTAINS UTF-8
            $translation = utf8_deaccent($like_query_esc);

            //AUTHORS [AU]
            if (strtolower($matches[3]) == '[au]') {
                
                $like_query = $dbHandle->quote("%L:\"$like_query_esc%");
                $regexp_query = str_replace("'", "''", "%L:\"".$regexp_query);

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%L:\"$translation%");
                    $regexp_query_translated = str_replace("'", "''", "%L:\"".$translation_regexp);
                    $like_sql = "(authors LIKE $like_query ESCAPE '\' OR authors_ascii LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(authors, '$regexp_query', $case2) OR regexp_match(authors_ascii, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "authors_ascii LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(authors_ascii, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //JOURNAL [JO]
            } elseif (strtolower($matches[3]) == '[jo]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(journal LIKE $like_query ESCAPE '\' OR journal LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(journal, '$regexp_query', $case2) OR regexp_match(journal, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "journal LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(journal, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //SECONDARY TITLE [ST]
            } elseif (strtolower($matches[3]) == '[st]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(secondary_title LIKE $like_query ESCAPE '\' OR secondary_title LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(secondary_title, '$regexp_query', $case2) OR regexp_match(secondary_title, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "secondary_title LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(secondary_title, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //AFFILIATION [AF]
            } elseif (strtolower($matches[3]) == '[af]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(affiliation LIKE $like_query ESCAPE '\' OR affiliation LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(affiliation, '$regexp_query', $case2) OR regexp_match(affiliation, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "affiliation LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(affiliation, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //TITLE [TI]
            } elseif (strtolower($matches[3]) == '[ti]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(title LIKE $like_query ESCAPE '\' OR title_ascii LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(title, '$regexp_query', $case2) OR regexp_match(title_ascii, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "title_ascii LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(title_ascii, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //KEYWORDS [KW]
            } elseif (strtolower($matches[3]) == '[kw]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(keywords LIKE $like_query ESCAPE '\' OR keywords LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(keywords, '$regexp_query', $case2) OR regexp_match(keywords, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "keywords LIKE $like_query ESCAPE '\'";
                    $regexp_sql = "regexp_match(keywords, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //ABSTRACT AND TITLE [AB]
            } elseif (strtolower($matches[3]) == '[ab]') {

                if ($translation != $like_query_esc) {
                    $translation_regexp = utf8_deaccent($regexp_query_esc);
                    $like_query_translated = $dbHandle->quote("%$translation%");
                    $regexp_query_translated = str_replace("'", "''", $translation_regexp);
                    $like_sql = "(title LIKE $like_query ESCAPE '\' OR abstract LIKE $like_query ESCAPE '\' OR
						title_ascii LIKE $like_query_translated ESCAPE '\' OR abstract_ascii LIKE $like_query_translated ESCAPE '\')";
                    $regexp_sql = "(regexp_match(title, '$regexp_query', $case2) OR regexp_match(abstract, '$regexp_query', $case2) OR
						regexp_match(title_ascii, '$regexp_query_translated', $case2) OR regexp_match(abstract_ascii, '$regexp_query_translated', $case2))";
                } else {
                    $like_sql = "(title_ascii LIKE $like_query ESCAPE '\' OR abstract_ascii LIKE $like_query ESCAPE '\')";
                    $regexp_sql = "(regexp_match(title_ascii, '$regexp_query', $case2) OR regexp_match(abstract_ascii, '$regexp_query', $case2))";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);

                //YEAR [YR]
            } elseif (strtolower($matches[3]) == '[yr]') {

                $final = "(year=" . intval($matches[2]) . " OR strftime('%Y', year) LIKE '" . intval($matches[2]) . "')";

                $input = str_replace($matches[0], $matches[1] . " $final ", $input);
            }
        } else {
            $input = '';
            break;
        }
    }

    $search_string = str_ireplace(' NOT ', ' AND NOT ', $input);

//PDFS

} elseif (isset($_GET['searchtype']) && $_GET['searchtype'] == 'pdf') {

    $stopwords = "a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c'mon, c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, far, few, fifth, first, five, followed, following, follows, for, former, formerly, forth, four, from, further, furthermore, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, just, keep, keeps, kept, know, knows, known, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, mainly, many, may, maybe, me, mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, que, quite, qv, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, un, under, 				unfortunately, unless, unlikely, until, unto, up, upon, us, use, used, useful, uses, using, usually, value, various, very, via, viz, vs, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, zero";

    $stopwords = explode(', ', $stopwords);

    $patterns = join("\b/ui /\b", $stopwords);
    $patterns = "/\b$patterns\b/ui";
    $patterns = explode(" ", $patterns);

    $input = $_GET['search-pdfs'];

    //SPLIT SEARCH INTO INDIVIDUAL TERMS
    $search_array = preg_split('/ AND | OR | NOT /', $input);

    //CREATE A QUERY FOR EACH TERM
    foreach ($search_array as $search) {

        //SPLIT THE TERM INTO AN OPTIONAL PARETHESES, LAZY TEXT, AND PARETHESES
        if (preg_match('/([\s\(]*)([^\(\)]*)?([\s\)]*)/i', $search, $matches) == 1) {

            //REMOVE STOPWORDS
            $matches[2] = preg_replace($patterns, '<*>', $matches[2]);

            //FORMAT THE TEXT FOR SEARCH
            $like_query = str_replace("\\", "\\\\", trim($matches[2]));
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes(trim($matches[2]), "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            //CHECK WHETHER TEXT CONTAINS UTF-8
            $translation = utf8_deaccent($matches[2]);

                if ($translation != $matches[2]) {
            $like_query_translated = $dbHandle->quote("%$translation%");
            $regexp_query_translated = str_replace("'", "''", $translation);

            $like_sql = "(full_text LIKE $like_query ESCAPE '\' OR full_text LIKE $like_query_translated ESCAPE '\')";
            $regexp_sql = "(regexp_match(full_text, '$regexp_query', $case2) OR regexp_match(full_text, '$regexp_query_translated', $case2))";
                } else {
            $like_sql = "full_text LIKE $like_query ESCAPE '\'";
            $regexp_sql = "regexp_match(full_text, '$regexp_query', $case2)";
                }

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final " . $matches[3], $input);

        } else {
            $input = '';
            break;
        }
    }

    $search_string = str_ireplace(' NOT ', ' AND NOT ', $input);

//NOTES

} elseif (isset($_GET['searchtype']) && $_GET['searchtype'] == 'notes') {

    $input = $_GET['search-notes'];

    //SPLIT SEARCH INTO INDIVIDUAL TERMS
    $search_array = preg_split('/ AND | OR | NOT /', $input);

    //CREATE A QUERY FOR EACH TERM
    foreach ($search_array as $search) {

        //SPLIT THE TERM INTO AN OPTIONAL PARETHESES, LAZY TEXT, AND PARETHESES
        if (preg_match('/([\s\(]*)([^\(\)]*)?([\s\)]*)/i', $search, $matches) == 1) {

            //FORMAT THE TEXT FOR SEARCH
            $like_query = str_replace("\\", "\\\\", trim($matches[2]));
            $like_query = str_replace("%", "\%", $like_query);
            $like_query = str_replace("_", "\_", $like_query);
            $like_query = str_replace("<*>", "%", $like_query);
            $like_query = str_replace("<?>", "_", $like_query);

            $regexp_query = addcslashes(trim($matches[2]), "\044\050..\053\056\057\074\076\077\133\134\136\173\174");
            $regexp_query = str_replace('\<\*\>', '.*', $regexp_query);
            $regexp_query = str_replace('\<\?\>', '.?', $regexp_query);

            $like_query = $dbHandle->quote("%$like_query%");
            $regexp_query = str_replace("'", "''", $regexp_query);

            $like_sql = "search_strip_tags(notes) LIKE $like_query ESCAPE '\'";
            $regexp_sql = "regexp_match(search_strip_tags(notes), '$regexp_query', $case2)";

                $final = $like_sql;
                if ($whole_words == 1)
                    $final = $final . ' AND ' . $regexp_sql;

                $input = str_replace($matches[0], $matches[1] . " $final " . $matches[3], $input);

        } else {
            $input = '';
            break;
        }
    }

    $search_string = str_ireplace(' NOT ', ' AND NOT ', $input);

    //EXPERT SEARCH SPECIFIC END
} else {
    ?>
    <form action="search.php" method="GET" id="expertsearchform">
        <input type="hidden" name="searchtype" value="metadata">
        <input type="hidden" name="searchmode" value="expert">
        <table cellspacing=0 class="threed" style="width:100%">
            <tr>
                <td style="width:50%">
                    <table style="margin-left:1px;margin-right:auto" id="expertsearchtabs">
                        <tr>
                            <td>
                                <div class="ui-state-highlight ui-corner-top clicked" style="margin-left:2px;padding:0 4px" id="tab-search-ref">
                                    Search References
                                </div>
                            </td>
                            <td>
                                <div class="ui-state-highlight ui-corner-top" style="margin-left:2px;padding:0 4px" id="tab-search-pdf">
                                    Search PDFs
                                </div>
                            </td>
                            <td style="<?php if (!isset($_SESSION['auth'])) print 'display:none'; ?>">
                                <div class="ui-state-highlight ui-corner-top" style="margin-left:2px;padding:0 4px" id="tab-search-notes">
                                    Search Notes
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:50%" colspan=2>
                    <table style="margin-left:1px;margin-right:auto;width:100%">
                        <tr>
                            <td>
                    <table cellspacing=0>
                        <tr>
                            <td class="select_span" style="line-height:16px">
                                <input type="radio" name="include-categories" value="1" style="display:none"
                                    <?php if (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 1) print ' checked' ?>>
                                <span class="ui-icon ui-icon-radio-<?php print (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 1) ? 'on' : 'off' ?>" style="float:left">
                                </span>Include
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="radio" name="include-categories" value="2" style="display:none"
                                    <?php if (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 2) print ' checked' ?>>
                                <span class="ui-icon ui-icon-radio-<?php print (isset($_SESSION['session_include-categories']) && $_SESSION['session_include-categories'] == 2) ? 'on' : 'off' ?>" style="float:left">
                                </span>Exclude these categories:
                            </td>
                        </tr>
                    </table>
                            </td>
                            <td style="text-align:right">
                                <input type="text" id="expert-filter" value="Filter" style="margin-left:auto;margin-right:2px">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table cellspacing=0 class="threed" style="width:100%">
            <tr>
                <td class="threed" style="width:50%">
                    <textarea name="search-metadata" rows=10 cols=75
                              style="resize:none;width:99%;height:249px"><?php print isset($_SESSION['session_search-metadata']) ? htmlspecialchars($_SESSION['session_search-metadata']) : ''; ?></textarea>
                    <textarea name="search-pdfs" rows=10 cols=75
                              style="resize:none;width:99%;height:249px;display:none"><?php print isset($_SESSION['session_search-pdfs']) ? htmlspecialchars($_SESSION['session_search-pdfs']) : ''; ?></textarea>
                    <textarea name="search-notes" rows=10 cols=75
                              style="resize:none;width:99%;height:249px;display:none"><?php print isset($_SESSION['session_search-notes']) ? htmlspecialchars($_SESSION['session_search-notes']) : ''; ?></textarea>
                </td>
                <td class="threed" style="width:50%" colspan=2>
                    <div style="height:250px;overflow:auto;border: 1px solid #C5C6C9;background-color:#FFF">
                        <table cellspacing=0 style="width: 50%;float: left;padding:2px">
                            <tr>
                                <td class="select_span">
                                    <input type="checkbox" name="category[]" value="0" style="display:none"><span class="ui-icon ui-icon-close" style="float:left"></span>!unassigned
                                </td>
                            </tr>
                            <?php
                            include_once 'functions.php';
                            database_connect($database_path, 'library');
                            $category_string = null;

                            $result3 = $dbHandle->query("SELECT count(*) FROM categories");
                            $totalcount = $result3->fetchColumn();
                            $result3 = null;

                            $i = 1;
                            $isdiv = null;
                            $result3 = $dbHandle->query("SELECT categoryID,category FROM categories ORDER BY category COLLATE NOCASE ASC");
                            while ($category = $result3->fetch(PDO::FETCH_ASSOC)) {
                                $cat_all[$category['categoryID']] = $category['category'];
                                if ($i > 1 && $i > ($totalcount / 2) && !$isdiv) {
                                    print '</table><table cellspacing=0 style="width: 50%;padding:2px">';
                                    $isdiv = true;
                                }
                                print PHP_EOL . '<tr><td class="select_span">';
                                print "<input type=\"checkbox\" name=\"category[]\" value=\"" . htmlspecialchars($category['categoryID']) . "\"";
                                print " style=\"display:none\"><span class=\"ui-icon ui-icon-close\" style=\"float:left\"></span>" . htmlspecialchars($category['category']) . "</td></tr>";
                                $i = $i + 1;
                            }
                            $result3 = null;
                            ?>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="threed" rowspan=3>
                    <button style="width:52px;margin:2px 0 5px 4px">AND</button> <button style="width:52px">OR</button> <button style="width:52px">NOT</button>
                    <button>(</button> <button>)</button>
                    <div id="metadata-buttons">
                    <button title="author" style="width:52px;margin-left:4px">[AU]</button> <button title="title" style="width:52px">[TI]</button>
                    <button title="title and abstract" style="width:52px">[AB]</button> <button title="journal" style="width:52px">[JO]</button>
                    <button title="secondary title" style="width:52px">[ST]</button> <button title="affiliation" style="width:52px">[AF]</button>
                    <button title="keyword" style="width:52px">[KW]</button> <button title="publication year" style="width:52px">[YR]</button>
                    </div>
                </td>
                <td class="threed" style="width:6em">
                    Match:
                </td>
                <td class="threed">
                    <table border=0 cellspacing=0 cellpadding=0>
                        <tr>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="whole_words" value="1" style="display:none" <?php if (isset($_SESSION['session_whole_words']) && $_SESSION['session_whole_words'] == 1)
                            print ' checked' ?>>
                                <span class="ui-icon ui-icon-<?php print (isset($_SESSION['session_whole_words']) && $_SESSION['session_whole_words'] == 1) ? 'check' : 'close'  ?>" style="float:left">
                                </span>Whole words only&nbsp;
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="case" value="1" style="display:none" <?php print (isset($_SESSION['session_case']) && $_SESSION['session_case'] == 1) ? 'checked' : ''  ?>>
                                <span class="ui-icon ui-icon-<?php print (isset($_SESSION['session_case']) && $_SESSION['session_case'] == 1) ? 'check' : 'close'  ?>" style="float:left">
                                </span>Match case&nbsp;
                            </td>
                        </tr>
                    </table>
                </td></tr>
            <tr>
                <td class="threed">
                    Rating:
                </td>
                <td class="threed">
                    <table border=0 cellspacing=0 cellpadding=0>
                        <tr>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="rating[]" value="1" style="display:none" <?php if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(1, $_SESSION['session_rating'])))
                                   print ' checked' ?>>
                                <span class="ui-icon ui-icon-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(1, $_SESSION['session_rating']))) ? 'check' : 'close'  ?>" style="float:left">
                                </span>Low&nbsp;
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="rating[]" value="2" style="display:none" <?php if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(2, $_SESSION['session_rating'])))
                                   print ' checked' ?>>
                                <span class="ui-icon ui-icon-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(2, $_SESSION['session_rating']))) ? 'check' : 'close'  ?>" style="float:left">
                                </span>Medium&nbsp;
                            </td>
                            <td class="select_span" style="line-height:16px">
                                <input type="checkbox" name="rating[]" value="3" style="display:none" <?php if (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(3, $_SESSION['session_rating'])))
                                   print ' checked' ?>>
                                <span class="ui-icon ui-icon-<?php print (!isset($_SESSION['session_rating']) || (isset($_SESSION['session_rating']) && in_array(3, $_SESSION['session_rating']))) ? 'check' : 'close'  ?>" style="float:left">
                                </span>High&nbsp;
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <?php
            if (isset($_SESSION['auth'])) {
                ?>
                <tr>
                    <td class="threed">
                        Save as:
                    </td>
                    <td class="threed">
                        <input type="text" name="searchname" style="width:99.5%">
                    </td></tr>
                <?php
            }
            ?>
        </table>
        <input type="submit" style="position:absolute;left:-999px;top:0;height:1px">
    </form>
    <?php
}
?>
