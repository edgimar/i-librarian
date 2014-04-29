<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

$nozip = false;
if (extension_loaded('zip')) $nozip = true;

if (!empty($_GET['export_files']) && isset($_GET['export'])) {

    if (!isset($_GET['column'])) $_GET['column'][] = 'Title';

    if ($_GET['export_files'] == 'session') {
        $export_files = read_export_files(0);
        $_GET['export_files'] = implode(" ", $export_files);
    }

    $column_translation = array (
            "Unique ID" => "id",
            "Authors" => "authors",
            "Title" => "title",
            "Journal" => "journal",
            "Year" => "year",
            "Volume" => "volume",
            "Issue" => "issue",
            "Pages" => "pages",
            "Abstract" => "abstract",
            "Secondary Title" => "secondary_title",
            "Affiliation" => "affiliation",
            "Editor" => "editor",
            "Publisher" => "publisher",
            "Place Published" => "place_published",
            "Keywords" => "keywords",
            "Accession ID" => "uid",
            "DOI" => "doi",
            "URL" => "url",
            "Publication Type" => "reference_type");

    $column_translation = array_flip($column_translation);

    $export_files = preg_replace('/[^0-9\s]/i', '', $_GET['export_files']);
    $export_files = explode (" ", $export_files);
    $export_files = join ("','", $export_files);
    $export_files = "WHERE id IN ('$export_files')";

    if ($_GET['format'] == 'zip') {
        $zip = new ZipArchive;
        $zip->open($temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.'test.zip', ZIPARCHIVE::OVERWRITE);
    }

    $orderby = 'id DESC';
    if ($_GET['format'] == 'list') $orderby = 'authors COLLATE NOCASE ASC';

    database_connect($database_path, 'library');
    $result = $dbHandle->query("SELECT * FROM library $export_files ORDER BY $orderby");
    $dbHandle = null;

    $items = $result->fetchAll(PDO::FETCH_ASSOC);

    $paper = '';
    $i = 1;

    while (list($key, $item) = each($items)) {

        $add_item = array();

        if ($item['volume'] == 0)  $item['volume'] = '';

        while (list($key, $value) = each($_GET['column'])) {

            $column_name = array_search($value, $column_translation);

            $add_item[$column_name] = $item[$column_name];
        }

        reset ($_GET['column']);

        if ($_GET['encoding'] == 'ASCII') {

            while (list($key,$value) = each($add_item)) {

                if (!empty($value)) $add_item[$key] = utf8_deaccent($value);
            }

            reset($add_item);
        }

        if (isset($add_item['id'])) {

            $id_author = substr($item['authors'], 3);
            $id_author = substr($id_author, 0, strpos($id_author, '"'));
            if (empty($id_author)) $id_author = 'unknown';

            $id_year_array = explode('-', $item['year']);
            $id_year = '0000';
            if (!empty($id_year_array[0])) $id_year = $id_year_array[0];

            $add_item['id'] = utf8_deaccent($id_author).'-'.$id_year.'-ID'.$item['id'];

            if ($_GET['format'] == 'BibTex' && !empty($item['bibtex'])) $add_item['id'] = $item['bibtex'];
        }

        if ($_GET['format'] == 'list') {

            $new_authors = array();
            $array = explode(';', $item['authors']);
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
                $authors = join('; ', $new_authors);
            }

            $paper .= '<p>'.$authors
                    .' ('.trim($item['year']).'). '
                    .trim($item['title']).' '
                    .preg_replace('/(^[A-Z]|\s[A-Z]|[a-z])(\s)/u','$1.$2',trim($item['journal'])).'.';
            if (!empty($item['volume'])) $paper .= ' <i>'.$item['volume'].'</i>, ';
            if (!empty($item['pages'])) $paper .= $item['pages'];
            $paper .= '.</p>'.PHP_EOL;
        }

        if ($_GET['format'] == 'zip') {
            
            $new_authors = array();
            $array = explode(';', $item['authors']);
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
                $authors = join('; ', $new_authors);
            }
            
            $paper .= '<p style="text-align: justify;border:1px solid #959698;margin:10px;padding:10px;background-color:#fff;border-radius:4px">';
            if ($i < 513 && is_readable(dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$item['file']) && isset($_GET['include_pdf'])) $paper .= '<a href="library/'.$item['file'].'">';
            $paper .= '<b style="font-size:1.2em">'.$add_item['title'].'</b>';
            if ($i < 513 && is_readable(dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$item['file']) && isset($_GET['include_pdf'])) $paper .= '</a>';
            if (!empty($item['authors'])) $paper .= '<br>'.$authors;
            if (!empty($item['journal'])) $paper .= '<br>'.$item['journal'];
            if (empty($item['journal']) && !empty($item['secondary_title'])) $paper .= '<br>'.$item['secondary_title'];
            if (!empty($item['year'])) $paper .= ' ('.$item['year'].')';
            if (!empty($item['volume'])) $paper .= ' <i>'.$item['volume'].'</i>';
            if (!empty($item['issue'])) $paper .= ' ('.$item['issue'].')';
            if (!empty($item['pages'])) $paper .= ': '.$item['pages'];
            if (!empty($item['abstract'])) $paper .= '<br><br>'.$item['abstract'];
            $paper .= '</p>'.PHP_EOL;

            if ($i < 513 && is_readable(dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$item['file']) && isset($_GET['include_pdf'])) {
                $zip->addFile(dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$item['file'], 'library'.DIRECTORY_SEPARATOR.$item['file']);
                $i = $i + 1;
            }
        }

        if ($_GET['format'] == 'csv') {
            
            $new_authors = array();
            $array = explode(';', $add_item['authors']);
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
                $add_item['authors'] = join('; ', $new_authors);
            }

            while (list($key, $value) = each($add_item)) {

                $columns[] = '"'.str_replace("\"", "\"\"", $value).'"';
            }

            reset($add_item);

            $line = join (",", $columns);
            $paper .= "$line\r\n";
            $columns = null;
        }

        if ($_GET['format'] == 'EndNote') {

            $endnote_translation = array (
                    "%F" => "id",
                    "%A" => "authors",
                    "%+" => "affiliation",
                    "%T" => "title",
                    "%J" => "journal",
                    "%D" => "year",
                    "%V" => "volume",
                    "%N" => "issue",
                    "%P" => "pages",
                    "%X" => "abstract",
                    "%B" => "secondary_title",
                    "%E" => "editor",
                    "%I" => "publisher",
                    "%C" => "place_published",
                    "%K" => "keywords",
                    "%M" => "uid",
                    "%M" => "doi",
                    "%U" => "url");

            if (isset($add_item['authors'])) {
                
                $new_authors = array();
                $array = explode(';', $add_item['authors']);
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
                }
                $authors = join ("\r\n%A ", $new_authors);
                $add_item['authors'] = $authors;
            }

            if (isset($add_item['url'])) {

                $add_item['url'] = explode ("|", $add_item['url']);
                $urls = join ("\r\n%U ", $add_item['url']);
                $add_item['url'] = $urls;
            }

            if (isset($add_item['year'])) {

                if(!is_numeric($add_item['year'])) {
                    $add_item['year'] = substr($add_item['year'],0,4);
                }
            }

            while (list($key, $value) = each($add_item)) {

                $endnote_name = array_search($key, $endnote_translation);
                if ($endnote_name && !empty($value)) $columns[] = "$endnote_name $value";
            }

            reset($add_item);

            $type = convert_type($item['reference_type'], 'ilib', 'endnote');
            $line = join (PHP_EOL, $columns);
            $paper .= '%0 '.$type.PHP_EOL.$line.PHP_EOL.PHP_EOL;
            $columns = null;

        }

        if ($_GET['format'] == 'BibTex') {

            $ris_translation = array (
                    "ID  - " => "id",
                    "AU  - " => "authors",
                    "T1  - " => "title",
                    "JA  - " => "journal",
                    "PY  - " => "year",
                    "VL  - " => "volume",
                    "IS  - " => "issue",
                    "SP  - " => "starting_page",
                    "EP  - " => "ending_page",
                    "N2  - " => "abstract",
                    "JF  - " => "secondary_title",
                    "ED  - " => "editor",
                    "PB  - " => "publisher",
                    "CY  - " => "place_published",
                    "KW  - " => "keywords",
                    "M2  - " => "uid",
                    "M2  - " => "doi",
                    "UR  - " => "url");

            if (isset($add_item['authors'])) {
                
                $new_authors = array();
                $array = explode(';', $add_item['authors']);
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
                }
                $authors = join ("\r\nAU  - ", $new_authors);
                $add_item['authors'] = $authors;
            }

            if (isset($add_item['keywords'])) {

                $add_item['keywords'] = explode ("/", $add_item['keywords']);
                $add_item['keywords'] = preg_replace("/(\s?)(.+)/ui","\\2", $add_item['keywords']);
                $keywords = join ("\r\nKW  - ", $add_item['keywords']);
                $add_item['keywords'] = $keywords;
            }

            if (isset($add_item['url'])) {

                $add_item['url'] = explode ("|", $add_item['url']);
                $urls = join ("\r\nUR  - ", $add_item['url']);
                $add_item['url'] = $urls;
            }

            if (isset($add_item['year'])) {

                if(is_numeric($add_item['year'])) {
                    $add_item['year'] = $add_item['year']."///";
                } else {
                    $add_item['year'] = str_replace('-','/',$add_item['year']).'/';
                }
            }

            if (isset($add_item['pages'])) {

                $add_item['pages'] = explode ("-", $add_item['pages']);
                $add_item['starting_page'] = $add_item['pages'][0];

                if (!empty($add_item['pages'][1])) {

                    if (strlen($add_item['pages'][0]) == strlen($add_item['pages'][1])) $add_item['ending_page'] = $add_item['pages'][1];
                    if (strlen($add_item['pages'][0]) > strlen($add_item['pages'][1])) $add_item['ending_page'] = substr($add_item['pages'][0], 0, -1*strlen($add_item['pages'][1])).$add_item['pages'][1];
                } else {
                    $add_item['ending_page'] = '';
                }
            }

            while (list($key, $value) = each($add_item)) {

                $ris_name = array_search($key, $ris_translation);
                if ($ris_name && !empty($value)) $columns[] = $ris_name.$value;
            }

            reset($add_item);

            $type = convert_type($item['reference_type'], 'ilib', 'ris');
            $line = join (PHP_EOL, $columns);
            $paper .= 'TY  - '.$type.PHP_EOL;
            $paper .= $line.PHP_EOL;
            $paper .= 'L1  - ';
            #COMPENSATE FOR BIBUTILS BUG
            #$paper .= 'L1  - file://';
            #if (substr(strtoupper(PHP_OS),0,3) == 'WIN') $paper .= '/';
            $paper .= dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$item['file'].PHP_EOL;
            $paper .= 'ER  - '.PHP_EOL.PHP_EOL;
            $columns = null;
        }

        if ($_GET['format'] == 'RIS') {

            $ris_translation = array (
                    "ID  - " => "id",
                    "AU  - " => "authors",
                    "T1  - " => "title",
                    "JA  - " => "journal",
                    "PY  - " => "year",
                    "VL  - " => "volume",
                    "IS  - " => "issue",
                    "SP  - " => "starting_page",
                    "EP  - " => "ending_page",
                    "N2  - " => "abstract",
                    "JF  - " => "secondary_title",
                    "ED  - " => "editor",
                    "PB  - " => "publisher",
                    "CY  - " => "place_published",
                    "KW  - " => "keywords",
                    "M2  - " => "uid",
                    "M2  - " => "doi",
                    "UR  - " => "url");

            if (isset($add_item['authors'])) {
                
                $new_authors = array();
                $array = explode(';', $add_item['authors']);
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
                }
                $authors = join ("\r\nAU  - ", $new_authors);
                $add_item['authors'] = $authors;
            }

            if (isset($add_item['keywords'])) {

                $add_item['keywords'] = explode ("/", $add_item['keywords']);
                $add_item['keywords'] = preg_replace("/(\s?)(.+)/ui","\\2", $add_item['keywords']);
                $keywords = join ("\r\nKW  - ", $add_item['keywords']);
                $add_item['keywords'] = $keywords;
            }

            if (isset($add_item['url'])) {

                $add_item['url'] = explode ("|", $add_item['url']);
                $urls = join ("\r\nUR  - ", $add_item['url']);
                $add_item['url'] = $urls;
            }

            if (isset($add_item['year'])) {

                if(is_numeric($add_item['year'])) {
                    $add_item['year'] = $add_item['year']."///";
                } else {
                    $add_item['year'] = str_replace('-','/',$add_item['year']).'/';
                }
            }

            if (isset($add_item['pages'])) {

                $add_item['pages'] = explode ("-", $add_item['pages']);
                $add_item['starting_page'] = $add_item['pages'][0];

                if (!empty($add_item['pages'][1])) {

                    if (strlen($add_item['pages'][0]) == strlen($add_item['pages'][1])) $add_item['ending_page'] = $add_item['pages'][1];
                    if (strlen($add_item['pages'][0]) > strlen($add_item['pages'][1])) $add_item['ending_page'] = substr($add_item['pages'][0], 0, -1*strlen($add_item['pages'][1])).$add_item['pages'][1];
                } else {
                    $add_item['ending_page'] = '';
                }
            }

            while (list($key, $value) = each($add_item)) {

                $ris_name = array_search($key, $ris_translation);
                if ($ris_name && !empty($value)) $columns[] = $ris_name.$value;
            }

            reset($add_item);

            $type = convert_type($item['reference_type'], 'ilib', 'ris');
            $line = join (PHP_EOL, $columns);
            $paper .= 'TY  - '.$type.PHP_EOL;
            $paper .= $line.PHP_EOL;
            $paper .= 'L1  - file://';
            if (substr(strtoupper(PHP_OS),0,3) == 'WIN') $paper .= '/';
            $paper .= dirname(__FILE__).DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$item['file'].PHP_EOL;
            $paper .= 'ER  - '.PHP_EOL.PHP_EOL;
            $columns = null;
        }

    }

    if ($_GET['format'] == 'list') {

        $content_type = 'text/html';
        $filename = 'library.html';

        $content = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html>
                <head>
                 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                 <title>I, Librarian</title>
                </head><body style="margin:20px">'.$paper.'</body></html>';

    }

    if ($_GET['format'] == 'zip') {

        $content_type = 'application/zip';
        $filename = 'library.zip';

        $html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html>
                <head>
                 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                 <title>I, Librarian</title>
                </head><body style="padding:5px 10px;background-color:#E5E6E8">'.$paper.'</body></html>';
        $zip->addFromString ('index.html', $html);
        $zip->close();
    }

    if ($_GET['format'] == 'csv') {

        $content_type = 'text/csv';
        $filename = 'library.csv';

        while (list($key, $value) = each($add_item)) {

            $column_names[] = "\"$key\"";
        }

        $header = join (",", $column_names);
        $header .= "\r\n";

        $content = $header.$paper;
    }

    if ($_GET['format'] == 'EndNote') {

        $content_type = 'text/plain';
        $filename = 'library.txt';

        $content = $paper;
    }

    if ($_GET['format'] == 'RIS') {

        $content_type = 'application/x-research-info-systems';
        $filename = 'library.ris';

        $content = $paper;
    }

    if ($_GET['format'] == 'BibTex') {
        $latex = '-nl';
        if (isset($_GET['latex']) && $_GET['latex'] == 1) $latex = '';
        $tempfilename = tempnam($temp_dir,"lib");
        file_put_contents($tempfilename, $paper);
        exec(select_bibutil("ris2xml")." -i unicode $tempfilename > $tempfilename.xml");
        exec(select_bibutil("xml2bib")." -o unicode -nb ".$latex." $tempfilename.xml > $tempfilename.xml.bib");
        $paper = file_get_contents($tempfilename.".xml.bib");
        unlink($tempfilename);
        unlink($tempfilename.'.xml');
        unlink($tempfilename.'.xml.bib');

        $content_type = 'text/plain';
        $filename = 'library.bib';
        $content = $paper;
    }
    
    if ($_GET['output'] == 'attachment' || $_GET['format'] == 'zip') {
        header("Content-type: $content_type");
        header("Content-Disposition: attachment; filename=$filename");
    }
    header("Pragma: no-cache");
    header("Expires: 0");
    
    if ($_GET['output'] == 'inline' && $_GET['format'] != 'zip') print '<html><body><pre>';
    
    if ($_GET['format'] == 'zip') {
        $handle = fopen($temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.'test.zip', 'rb');
        while (!feof($handle)) {
            $content = fread($handle, 1024*1024);
            print $content;
        }
        fclose($handle);
        unlink($temp_dir.DIRECTORY_SEPARATOR.'lib_'.session_id().DIRECTORY_SEPARATOR.'test.zip');
    } else {
        print $content;
    }
    
    if ($_GET['output'] == 'inline' && $_GET['format'] != 'zip') print '</pre></body></html>';
    
} else {

    $get_post_export_files = 'session';
    if (isset($_GET['export_files'])) $get_post_export_files = $_GET['export_files'];

    if (ini_get('safe_mode')) print 'Warning! Your php.ini configuration does not alow to run scipts for a long time.
				This may cause the export of a larger number (>10,000) of items to fail. Please unset safe_mode directive in your php.ini.';
    ?>
<form id="exportform" action="export.php" method="GET">
    <table style="width:40em;margin:10px auto;">
        <tr>
            <td style="width:50%">
                <input type="hidden" name="export_files" value="<?php print $get_post_export_files ?>">
                <input type="hidden" name="export" value="1">
                <b>Include:</b><br><br>
                <span id="selectall" style="cursor:pointer">Select All</span> &bull;
                <span id="unselectall" style="cursor:pointer">Unselect All</span><br><br>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Unique ID" style="display:none" checked>
                            <span class="ui-icon ui-icon-check" style="float:left"></span>
                            Unique ID
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Authors" style="display:none" checked>
                            <span class="ui-icon ui-icon-check" style="float:left"></span>
                            Authors
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Title" style="display:none" checked>
                            <span class="ui-icon ui-icon-check" style="float:left"></span>
                            Title
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Journal" style="display:none" checked>
                            <span class="ui-icon ui-icon-check" style="float:left"></span>
                            Journal Abbreviation
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Year" style="display:none" checked>
                            <span class="ui-icon ui-icon-check" style="float:left"></span>
                            Year
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Volume" style="display:none" checked>
                            <span class="ui-icon ui-icon-check" style="float:left"></span>
                            Volume
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Pages" style="display:none" checked>
                            <span class="ui-icon ui-icon-check" style="float:left"></span>
                            Pages
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Issue" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Issue
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Abstract" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Abstract
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Secondary Title" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Secondary Title
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Keywords" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Keywords
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Affiliation" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Affiliation
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Accession ID" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Accession ID
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="DOI" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            DOI
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="URL" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            URL
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Editor" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Editor
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Publisher" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Publisher
                        </td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="checkbox" name="column[]" value="Place Published" style="display:none">
                            <span class="ui-icon ui-icon-close" style="float:left"></span>
                            Place Published
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:50%">
                <b>Export format:</b><br><br>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="format" value="EndNote" style="display:none">
                            <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                            EndNote
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="format" value="RIS" style="display:none" checked>
                            <span class="ui-icon ui-icon-radio-on" style="float:left"></span>
                            RIS
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="format" value="BibTex" style="display:none">
                            <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                            BibTeX
                            <table style="margin-left: 30px">
                                <tr>
                                    <td class="select_span">
                                        <input type="checkbox" name="latex" value="1" checked style="display:none">
                                        <span class="ui-icon ui-icon-check" style="float:left"></span>
                                        with LaTeX formatting
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span <?php print $nozip ? '' : ' ui-state-disabled' ?>">
                            <input type="radio" name="format" value="zip" <?php print $nozip ? '' : 'disabled' ?> style="display:none">
                            <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                            Zipped HTML
                            <table style="margin-left: 30px">
                                <tr>
                                    <td class="select_span <?php print $nozip ? '' : ' ui-state-disabled' ?>">
                                        <input type="checkbox" name="include_pdf" value="1" <?php print $nozip ? '' : 'disabled' ?> style="display:none">
                                        <span class="ui-icon ui-icon-close" style="float:left"></span>
                                        include PDFs (max. 500)
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="format" value="csv" style="display:none">
                            <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                            CSV (Office)
                        </td>
                    </tr>
                    <tr style="display:none">
                        <td class="select_span">
                            <input type="radio" name="format" value="list" style="display:none">
                            <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                            Formatted List<sup>&beta;</sup>
                            <table style="margin-left: 30px">
                                <tr>
                                    <td class="select_span">
                                        <input type="radio" name="style" value="text" style="display:none" checked>
                                        <span class="ui-icon ui-icon-radio-on" style="float:left"></span>
                                        Cell Press
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <br><br>
                <b>Character encoding:</b><br><br>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="encoding" value="utf-8" checked style="display:none">
                            <span class="ui-icon ui-icon-radio-on" style="float:left"></span>
                            UTF-8 (accented letters)
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="encoding" value="ASCII" style="display:none">
                            <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                            ASCII (no accents)
                        </td>
                    </tr>
                </table>
                <br><br>
                <b>Output options:</b><br><br>
                <table>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="output" value="inline" checked style="display:none">
                            <span class="ui-icon ui-icon-radio-on" style="float:left"></span>
                            display in browser
                        </td>
                    </tr>
                    <tr>
                        <td class="select_span">
                            <input type="radio" name="output" value="attachment" style="display:none">
                            <span class="ui-icon ui-icon-radio-off" style="float:left"></span>
                            download file
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</form>
    <?php
}
?>
