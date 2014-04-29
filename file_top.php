<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

if (isset($_SESSION['auth'])) {
    $cache_name = cache_name();
    $db_change = database_change(array(
        'library',
        'shelves',
        'projectsusers',
        'projectsfiles',
        'filescategories',
        'notes'
    ));
    cache_start($db_change);
}

database_connect($database_path, 'library');

$shelf_files = array();
$shelf_files = read_shelf($dbHandle);

$desktop_projects = array();
$desktop_projects = read_desktop($dbHandle);

if (isset($_GET['select'])) {

    if ($_GET['select'] != 'library' &&
            $_GET['select'] != 'shelf' &&
            $_GET['select'] != 'project' &&
            $_GET['select'] != 'clipboard') {

        $_GET['select'] = 'library';
    }

    $select = $_GET['select'];
} else {
    $select = 'library';
}

if (isset($_GET['file'])) {

    $query = $dbHandle->quote($_GET['file']);

    if (!isset($paper)) {
        $result = $dbHandle->query("SELECT * FROM library WHERE id=$query LIMIT 1");
        $paper = $result->fetch(PDO::FETCH_ASSOC);
    }

    if (!empty($paper['id'])) {

        $paper['journal'] = htmlspecialchars($paper['journal']);
        $paper['title'] = htmlspecialchars($paper['title']);
        $paper['abstract'] = htmlspecialchars($paper['abstract']);
        $paper['year'] = htmlspecialchars($paper['year']);

        #######new date#########
        $date = '';
        if (!empty($paper['year'])) {
            $date_array = array();
            $date_array = explode('-', $paper['year']);
            if (count($date_array) == 1) {
                $date = $paper['year'];
            } else {
                if (empty($date_array[0]))
                    $date_array[0] = '1969';
                if (empty($date_array[1]))
                    $date_array[1] = '01';
                if (empty($date_array[2]))
                    $date_array[2] = '01';
                $date = date('Y M j', mktime(0, 0, 0, $date_array[1], $date_array[2], $date_array[0]));
            }
        }

        print '<div id="file-panel2"><div id="file-item-' . $paper['id'] . '" class="items alternating_row" data-file="' . $paper['file'] . '">';

        print '<div class="titles file-title">' . $paper['title'] . '</div>';
        
        $authors_string = '';
        if (!empty($paper['authors'])) {
            $array = explode(';', $paper['authors']);
            $array = array_filter($array);
            if (!empty($array)) {
                foreach ($array as $author) {
                    $array2 = explode(',', $author);
                    $last = trim($array2[0]);
                    $last = substr($array2[0], 3, -1);
                    $first = trim($array2[1]);
                    $first = substr($array2[1], 3, -1);
                    $new_authors[] = $last.', '.$first;
                }
                $authors_string = join('; ', $new_authors);
            }

            print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>'.htmlspecialchars($authors_string).'</div></div>';
        }

        if (!empty($paper['affiliation']))
            print '<div class="authors"><span class="author_expander ui-icon ui-icon-plus" style="float:left"></span><div>' . $paper['affiliation'] . '</div></div>';

        print '<i>';

        if (!empty($paper['journal'])) {
            print $paper['journal'];
        } elseif (!empty($paper['secondary_title'])) {
            print $paper['secondary_title'];
        }
        if (!empty($date))
            print " ($date)";
        if (!empty($paper['volume']))
            print " <b>$paper[volume]</b>";
        if (!empty($paper['issue']))
            print " ($paper[issue])";
        if (!empty($paper['pages']))
            print ": $paper[pages].";

        print "</i><br>";

        if (isset($_SESSION['auth'])) {

            print '<div class="star ' . (($paper['rating'] >= 1) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" id="file-star-' . $paper['id'] . '-1"><span class="ui-icon ui-icon-star"></span></div>';
            print '<div class="star ' . (($paper['rating'] >= 2) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" id="file-star-' . $paper['id'] . '-2"><span class="ui-icon ui-icon-star"></span></div>';
            print '<div class="star ' . (($paper['rating'] == 3) ? 'ui-state-error-text' : 'ui-priority-secondary') . '" id="file-star-' . $paper['id'] . '-3"><span class="ui-icon ui-icon-star"></span></div>';
        }

        $result = $dbHandle->query("SELECT categoryID,category
			FROM categories
			WHERE categoryID IN (SELECT categoryID
				FROM filescategories
				WHERE fileID=$query)
			ORDER BY category COLLATE NOCASE ASC");

        while ($categories = $result->fetch(PDO::FETCH_ASSOC)) {
            $category_array[] = htmlspecialchars($categories['category']);
        }

        if (empty($category_array[0]))
            $category_array[0] = '!unassigned';

        $category_string = join(", ", $category_array);
        $category_array = null;

        if (is_file("library/$paper[file]") && isset($_SESSION['auth'])) {

                if (!isset($_SESSION['pdfviewer']) || (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'external'))
                print '&nbsp;<b>&middot;</b> <a title="Open PDF in new window" href="'.htmlspecialchars('downloadpdf.php?file='.urlencode($paper['file']).'#pagemode=none&scrollbar=1&navpanes=0&toolbar=1&statusbar=0&page=1&view=FitH,0').'" target="_blank" class="pdf_link">
				<span class="ui-state-highlight" style="padding:0px 2px 0px 2px;margin-right:2px">&nbsp;PDF&nbsp;</span></a>';

                if (isset($_SESSION['pdfviewer']) && $_SESSION['pdfviewer'] == 'internal')
                print '&nbsp;<b>&middot;</b> <a title="Open PDF in new window" href="'.htmlspecialchars('viewpdf.php?file='.urlencode($paper['file']).'&title='.urlencode($paper['title'])).'" target="_blank" class="pdf_link">
				<span class="ui-state-highlight ui-corner-all" style="padding:0px 2px 0px 2px;margin-right:2px">&nbsp;PDF&nbsp;</span></a>';
        }

        if (!empty($paper['doi'])) {

            print '&nbsp;<b>&middot;</b> <a href="http://dx.doi.org/' . urlencode($paper['doi']) . '" target="_blank">Publisher Website</a>';
        }

        if (!empty($paper['url'])) {

            $pmid_url = '';
            $pmcid_url = '';
            $nasaads_url = '';
            $arxiv_url = '';
            $jstor_url = '';
            $pmid_related_url = '';
            $pmid_citedby_pmc = '';
            $nasa_related_url = '';
            $nasa_citedby_pmc = '';
            $other_urls = array();

            $urls = explode("|", $paper['url']);

            while (list($key, $url) = each($urls)) {

                if (preg_match('/pubmed\.org/', $url)) {

                    $pmid_url = $url;
                } elseif (preg_match('/pubmedcentral\.nih\.gov/', $url) || preg_match('/\/pmc\//', $url)) {

                    $pmcid_url = $url;
                } elseif (preg_match('/adsabs\.harvard\.edu/', $url)) {

                    $nasaads_url = $url;
                } elseif (preg_match('/arxiv\.org/', $url)) {

                    $arxiv_url = $url;
                } elseif (preg_match('/jstor\.org/', $url)) {

                    $jstor_url = $url;
                } else {

                    $other_urls[] = $url;
                }
            }
        }

        if (!empty($paper['uid'])) {

            $uids = explode("|", $paper['uid']);

            while (list($key, $uid) = each($uids)) {
                if (preg_match('/PMID:/', $uid))
                    $pmid = preg_replace('/PMID:/', '', $uid);
                if (preg_match('/NASAADS:/', $uid))
                    $nasaid = preg_replace('/NASAADS:/', '', $uid);
                if (preg_match('/IEEE:/', $uid))
                    $ieeeid = preg_replace('/IEEE:/', '', $uid);
            }
            $uids = null;
        }

        if (!empty($pmid)) {
            $pmid_related_url = 'http://www.ncbi.nlm.nih.gov/sites/entrez?db=pubmed&cmd=link&linkname=pubmed_pubmed&uid=' . $pmid;
            $pmid_citedby_pmc = 'http://www.ncbi.nlm.nih.gov/pubmed?db=pubmed&cmd=link&linkname=pubmed_pubmed_citedin&uid=' . $pmid;
        }

        if (!empty($nasaid)) {
            $nasa_related_url = 'http://adsabs.harvard.edu/cgi-bin/nph-abs_connect?return_req=no_params&text=' . urlencode($paper['abstract']) . '&title=' . urlencode($paper['title']);
            $nasa_citedby_pmc = 'http://adsabs.harvard.edu/cgi-bin/nph-data_query?bibcode=' . $nasaid . '&link_type=CITATIONS';
        }
        
        if (!empty($ieeeid)) {
            $ieee_url = 'http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber='.$ieeeid;
        }

        if (!empty($pmid_url)) {

            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($pmid_url) . "\" target=\"_blank\">PubMed</a>";
        }

        if (!empty($pmid_related_url)) {
            print '&nbsp;<b>&middot;</b> <a href="' . htmlspecialchars($pmid_related_url) . '" target="_blank">
			Related Articles</a>';
        }

        if (!empty($pmid_citedby_pmc)) {
            print '&nbsp;<b>&middot;</b> <a href="' . htmlspecialchars($pmid_citedby_pmc) . '" target="_blank">
			Cited by</a>';
        }

        if (!empty($pmcid_url)) {

            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($pmcid_url) . "\" target=\"_blank\">PubMed Central</a>";
        }

        if (!empty($nasaads_url)) {

            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($nasaads_url) . "\" target=\"_blank\">NASA ADS</a>";
        }

        if (!empty($nasa_related_url)) {
            print ' <b>&middot;</b> <a href="' . htmlspecialchars($nasa_related_url) . '" target="_blank" title="Related Articles">Related Articles</a>';
        }

        if (!empty($nasa_citedby_pmc)) {
            print ' <b>&middot;</b> <a href="' . htmlspecialchars($nasa_citedby_pmc) . '" target="_blank" title="Cited by">Cited by</a>';
        }

        if (!empty($arxiv_url)) {
            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($arxiv_url) . "\" target=\"_blank\">arXiv</a>";
        }

        if (!empty($jstor_url)) {
            print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($jstor_url) . "\" target=\"_blank\">
			JSTOR</a>";
        }
        
        if (!empty($ieee_url)) {
            print '&nbsp;<b>&middot;</b> <a href="' . htmlspecialchars($ieee_url) . '" target="_blank">IEEE</a>';
        }

        if (!empty($other_urls)) {

            foreach ($other_urls as $another_url) {
                $url_host = htmlspecialchars(parse_url($another_url, PHP_URL_HOST));
                print "&nbsp;<b>&middot;</b> <a href=\"" . htmlspecialchars($another_url) . "\" target=\"_blank\" class=\"another_url\" title=\"$url_host\">Link</a>";
            }
        }

        print '<div style="clear:both"></div>';

        if (isset($_SESSION['auth'])) {

            print ' <div class="noprint">';

            if (isset($shelf_files) && !in_array($paper['id'], $shelf_files))
                print '<div class="update_shelf add">
                                            <span class="update_shelf ui-icon ui-icon-close" style="float:left"></span>Shelf&nbsp;</div>';

            if (isset($shelf_files) && in_array($paper['id'], $shelf_files))
                print '<div class="update_shelf remove ui-state-error-text">
                                            <span class="update_shelf ui-icon ui-icon-check" style="float:left"></span>Shelf&nbsp;</div>';

            if (!isset($_SESSION['session_clipboard']) || (isset($_SESSION['session_clipboard']) && !in_array($paper['id'], $_SESSION['session_clipboard'])))
                print '<div class="update_clipboard add">
                                            <span class="update_clipboard ui-icon ui-icon-close" style="float:left"></span>Clipboard&nbsp;</div>';

            if (isset($_SESSION['session_clipboard']) && in_array($paper['id'], $_SESSION['session_clipboard']))
                print '<div class="update_clipboard remove ui-state-error-text">
                                            <span class="update_clipboard ui-icon ui-icon-check" style="float:left"></span>Clipboard&nbsp;</div>';

            foreach ($desktop_projects as $desktop_project) {

                $project_rowid = $dbHandle->query("SELECT ROWID FROM projectsfiles WHERE projectID=" . intval($desktop_project['projectID']) . " AND fileID=" . intval($paper['id']) . " LIMIT 1");
                $project_rowid = $project_rowid->fetchColumn();

                if (empty($project_rowid))
                    print '<div class="' . $desktop_project['projectID'] . ' update_project add">
                                            <span class="update_project ui-icon ui-icon-close" style="float:left"></span>' . htmlspecialchars($desktop_project['project']) . '&nbsp;</div>';

                if (!empty($project_rowid))
                    print '<div class="' . $desktop_project['projectID'] . ' update_project remove ui-state-error-text">
                                            <span class="update_project ui-icon ui-icon-check" style="float:left"></span>' . htmlspecialchars($desktop_project['project']) . '&nbsp;</div>';

                $project_rowid = null;
            }

            print ' </div>';
        }

        print '<div style="clear:both"></div>';

        print " </div>";

        print '<div style="margin:4px 0 4px 4px">';

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:361px;float:left">
                           <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">Abstract</div><div class="separator" style="margin:0"></div>
                           <div class="alternating_row abstract ui-corner-bottom" style="padding:4px 7px;overflow:auto;height:299px;column-count:auto;-moz-column-count:auto;-webkit-column-count:auto">' . $paper['abstract'] . '&nbsp;
                           </div></div>';

        $notes = null;

        if (isset($_SESSION['auth'])) {

            $user_query = $dbHandle->quote($_SESSION['user_id']);
            $result = $dbHandle->query("SELECT notesID,notes FROM notes WHERE fileID=$query AND userID=$user_query LIMIT 1");
            $fetched = $result->fetch(PDO::FETCH_ASSOC);
            $result = null;

            $notesid = $fetched['notesID'];
            $notes = $fetched['notes'];
        }

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:361px;float:left">
                           <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">Notes</div><div class="separator" style="margin:0"></div>
                           <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;height:299px;overflow:auto">' . $notes . '&nbsp;
                           </div></div>';

        $integer = sprintf("%05d", intval($paper['file']));
        $files_to_display = glob('library/supplement/' . $integer . '*');
        $url_filename = null;
        if (count($files_to_display) > 0) {
            foreach ($files_to_display as $supplementary_file) {
                $isimage = null;
                $image_array = array();
                $extension = pathinfo($supplementary_file, PATHINFO_EXTENSION);
                if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'gif' || $extension == 'png') {
                    $image_array = @getimagesize($supplementary_file);
                    $image_mime = $image_array['mime'];
                    if ($image_mime == 'image/jpeg' || $image_mime == 'image/gif' || $image_mime == 'image/png')
                        $isimage = true;
                }
                if ($isimage)
                    $url_filenames[] = '<li><a href="' . htmlspecialchars($supplementary_file) . '" target="_blank">' . substr(basename($supplementary_file), 5) . '</a>';
                if (!$isimage)
                    $url_filenames[] = '<li><a href="' . htmlspecialchars('attachment.php?attachment=' . basename($supplementary_file)) . '">' . substr(basename($supplementary_file), 5) . '</a>';
            }
            $url_filename = join('<br>', $url_filenames);
        }

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:239px;float:left">
                           <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">Supplementary Files</div><div class="separator" style="margin:0"></div>
                           <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;overflow:auto;height:128px"><ul style="padding-left:16px">' . $url_filename . '</ul>&nbsp;
                           </div></div>';

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:239px;float:left">
                           <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">IDs</div><div class="separator" style="margin:0"></div>
                           <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;overflow:auto;height:128px">';

        print "<u>I, Librarian ID:</u> <a href=\"stable.php?id=$paper[id]\" target=\"_blank\">" . $paper['id'] . " (stable link)</a>";

        $uid_array = explode("|", $paper['uid']);

        foreach ($uid_array as $uid) {
            $uids[] = $uid;
        }

        $bibtex_author = substr($paper['authors'], 3);
        $bibtex_author = substr($bibtex_author, 0, strpos($bibtex_author, '"'));
        if (empty($bibtex_author))
            $bibtex_author = 'unknown';

        $bibtex_year_array = explode('-', $paper['year']);
        $bibtex_year = '0000';
        if (!empty($bibtex_year_array[0]))
            $bibtex_year = $bibtex_year_array[0];

        $bibtex_key = utf8_deaccent($bibtex_author) . '-' . $bibtex_year . '-ID' . $paper['id'];

        print "<br><u>External IDs:</u> " . implode(", ", $uids);

        print "<br><u>DOI:</u> " . $paper['doi'];

        if (!empty($paper['bibtex'])) {
            print '<br><u>BibTex:</u> <input type="text" class="bibtex" value="' . $paper['bibtex'] . '" style="outline:none;width:165px">';
        } else {
            print '<br><u>BibTex:</u> <input type="text" class="bibtex" value="' . $bibtex_key . '" style="outline:none;width:165px">';
        }

        print '&nbsp;</div></div>';

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:239px;float:left">
               <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">Graphical Abstract</div><div class="separator" style="margin:0"></div>
               <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;height:128px;overflow:auto">';
        if (is_file(graphical_abstract($paper['file'])))
            print '<a href="' . graphical_abstract($paper['file']) . '" target="_blank">
               <img src="' . graphical_abstract($paper['file']) . '" style="width:100%"></a>';
        print '&nbsp;</div></div>';

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:239px;float:left">
                           <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">Categories</div><div class="separator" style="margin:0"></div>
                           <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;overflow:auto;height:128px">' . $category_string . '&nbsp;
                           </div></div>';

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:239px;float:left">
                           <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">Keywords</div><div class="separator" style="margin:0"></div>
                           <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;overflow:auto;height:128px">' . $paper['keywords'] . '&nbsp;
                           </div></div>';

        print '<div class="item-sticker ui-widget-content ui-corner-all" style="margin-left:4px;margin-top:4px;width:239px;float:left">
                           <div class="ui-widget-header ui-dialog-titlebar items ui-corner-top" style="border:0">Miscellaneous</div><div class="separator" style="margin:0"></div>
                           <div class="alternating_row ui-corner-bottom" style="padding:4px 7px;overflow:auto;height:128px">';

        print '<u>Publication type:</u> ' . $paper['reference_type'];

        print '<br><u>Editor:</u> ' . $paper['editor'];

        print '<br><u>Publisher:</u> ' . $paper['publisher'];

        print '<br><u>Place published:</u> ' . $paper['place_published'];

        print '<br><u>Custom 1:</u> ' . $paper['custom1'];

        print '<br><u>Custom 2:</u> ' . $paper['custom2'];

        print '<br><u>Custom 3:</u> ' . $paper['custom3'];

        print '<br><u>Custom 4:</u> ' . $paper['custom4'];

        print '&nbsp;</div></div>';

        print '<div style="clear:both"></div><br>&nbsp;<u>Added:</u> ' . date('F d, Y', strtotime($paper['addition_date'])) . ' by ' . get_username($dbHandle, $database_path, $paper['added_by']);

        if (!empty($paper['modified_date']))
            print " <b>&middot;</b> <u>Last Modified:</u> " . date("F j, Y, g:i A", strtotime($paper['modified_date'])) . " by " . get_username($dbHandle, $database_path, $paper['modified_by']);

        print '</div></div>';
        print '<script type="text/javascript">$("title").text("'.$paper['title'].'")</script>';
    }
}

if (isset($_SESSION['auth'])) cache_store();
?>