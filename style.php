<?php
include_once 'data.php';
include_once 'functions.php';
session_write_close();

$default_settings = parse_ini_file("ilibrarian.ini");

if (isset($_SESSION['auth'])) {

    ########## read users settings ##########

    database_connect($usersdatabase_path, 'users');
    $style_user_query = $dbHandle->quote($_SESSION['user_id']);
    $style_result = $dbHandle->query("SELECT setting_name,setting_value FROM settings WHERE userID=$style_user_query");
    $dbHandle = null;
    while ($custom_settings = $style_result->fetch(PDO::FETCH_BOTH)) {

        $custom_setting{$custom_settings['setting_name']} = $custom_settings['setting_value'];
    }
}

if (!empty($custom_setting)) {

    $user_settings = array_merge($default_settings, $custom_setting);
} else {

    $user_settings = $default_settings;
}

while (list($setting_name, $setting_value) = each($user_settings)) {

    ${$setting_name} = $setting_value;
}

$r1 = hexdec(substr($top_window_background_color, 0, 2));
$g1 = hexdec(substr($top_window_background_color, 2, 2));
$b1 = hexdec(substr($top_window_background_color, 4, 2));

$r2 = max($r1 - 40, 0);
$g2 = max($g1 - 40, 0);
$b2 = max($b1 - 40, 0);

$top_gradient_color = sprintf("%02X", $r2) . sprintf("%02X", $g2) . sprintf("%02X", $b2);

$r1 = hexdec(substr($top_window_background_color, 0, 2));
$g1 = hexdec(substr($top_window_background_color, 2, 2));
$b1 = hexdec(substr($top_window_background_color, 4, 2));

$r2 = max($r1 - 50, 0);
$g2 = max($g1 - 50, 0);
$b2 = max($b1 - 50, 0);

$top_button_color = sprintf("%02X", $r2) . sprintf("%02X", $g2) . sprintf("%02X", $b2);

$r1 = hexdec(substr($top_button_color, 0, 2));
$g1 = hexdec(substr($top_button_color, 2, 2));
$b1 = hexdec(substr($top_button_color, 4, 2));

$r2 = max($r1 - 70, 0);
$g2 = max($g1 - 70, 0);
$b2 = max($b1 - 70, 0);

$top_button_shadow_color = sprintf("%02X", $r2) . sprintf("%02X", $g2) . sprintf("%02X", $b2);

$r1 = hexdec(substr($top_button_color, 0, 2));
$g1 = hexdec(substr($top_button_color, 2, 2));
$b1 = hexdec(substr($top_button_color, 4, 2));

$r2 = min(70 + $r1, 255);
$g2 = min(70 + $g1, 255);
$b2 = min(70 + $b1, 255);

$top_button_shadow_color2 = sprintf("%02X", $r2) . sprintf("%02X", $g2) . sprintf("%02X", $b2);

$content = "body		{
			font-family: '" . $main_window_font_family . "',sans-serif;
			font-size: " . $main_window_font_size . "px;
			background-color: #" . $main_window_background_color . ";
			color: #" . $main_window_color . ";
			margin: 0;
			line-height: " . $main_window_line_height . "
		}

td		{
			font-size: " . $main_window_font_size . "px;
			padding: 0;
			vertical-align: top;
			line-height: " . $main_window_line_height . "
		}

a		{
			text-decoration: none;
			color: #" . $main_window_link_color . ";
		}

input[type=\"text\"],
input[type=\"password\"],
textarea,
option,
select		{
			font-family: '" . $main_window_form_font_family . "', sans-serif !important;
			font-size: " . $main_window_form_font_size . "px !important;
		}

table.items	{
			margin-left: auto;
			margin-right: auto;
			border-bottom: 1px #" . $alternating_row_background_color . " solid;
			width: 100%
		}

td.items, div.items	{
			background-color: #" . $alternating_row_background_color . "
		}

.ui-state-highlight
		{
			cursor: pointer;
			height:16px;
			line-height:17px;
			text-align: center;
			font-size: " . $main_window_font_size . "px;
		}

div.titles, div.titles-pdf	{
			font-family: '" . $main_window_title_font_family . "', sans-serif;
			font-size: " . $main_window_title_font_size . "px;
                        color: #" . $main_window_color . ";
			font-weight: bold;
                        cursor: pointer;
                        text-shadow: 1px 1px 1px white;
		}
                
.brief, div.titles-pdf  {
   height: ".$main_window_line_height."em;
   padding: 0.2em 5px;
}

div.authors       {
			height: ".$main_window_line_height."em;
			overflow: hidden;
                        line-height:".$main_window_line_height."em
		}

.abstract	{
			padding-top: 3px;
			padding-bottom: 4px;
			padding-left: 7px;
			padding-right: 7px;
			text-align: justify;
			font-family: '" . $main_window_abstract_font_family . "', serif;
			font-size: " . $main_window_abstract_font_size . "px;
			line-height: " . $main_window_abstract_line_height . "
		}

td.threed	{
			padding: 2px;
         	 	background-color: #" . $alternating_row_background_color . ";
			border-right: 1px #C6C8CC solid;
			border-bottom: 1px #C6C8CC solid
		}

td.threedleft	{
			width: 12em;
			padding: 2px;
         	 	background-color: #" . $alternating_row_background_color . ";
			border-top: 1px #FFFFFF solid;
			border-right: 1px #C6C8CC solid;
			border-bottom: 1px #C6C8CC solid
		}

td.threedright	{
			padding: 2px;
         	 	background-color: #" . $alternating_row_background_color . ";
			border-top: 1px #FFFFFF solid;
			border-left: 1px #FFFFFF solid;
			border-bottom: 1px #C6C8CC solid
		}

#top-panel, #top-panel-form	{
			background-color: #" . $top_window_background_color . ";
			padding: 0px;
			background-image: -moz-linear-gradient(
				top,
				#" . $top_window_background_color . " 10%,
				#" . $top_gradient_color . " 80%
			);
			background-image: -webkit-linear-gradient(
				top,
				#" . $top_window_background_color . " 10%,
				#" . $top_gradient_color . " 80%
			);
                        background-image: -webkit-gradient(
                            linear,
                            left top,
                            left bottom,
                            color-stop(0.1, #" . $top_window_background_color . "),
                            color-stop(0.8, #" . $top_gradient_color . ")
                        );
			background-image: -o-linear-gradient(
				top,
				#" . $top_window_background_color . " 10%,
				#" . $top_gradient_color . " 80%
			);
		}

td.topindex, div.topindex	{
			color: #" . $top_window_color . "
		}

a.topindex	{
			display: inline-block;
			padding:0px 5px;
			height:22px;
			font-size: 15px;
                     font-weight: bold;
			color: #" . $top_window_color . ";
			text-shadow: 1px 1px 1px #" . $top_button_shadow_color . ";
		}

a.topindex:hover	{
			background-color: #" . $top_button_color . ";
			box-shadow: #" . $top_button_shadow_color . " 0 0 2px 0 inset, #" . $top_button_shadow_color2 . " 0 1px 1px 0;
			-moz-box-shadow: #" . $top_button_shadow_color . " 0 0 2px 0 inset, #" . $top_button_shadow_color2 . " 0 1px 1px 0;
                     -webkit-box-shadow: #" . $top_button_shadow_color . " 0 0 2px 0 inset, #" . $top_button_shadow_color2 . " 0 1px 1px 0;
			text-shadow: 1px 1px 1px #" . $top_button_shadow_color . ";
			color: #" . $top_window_color . "
		}

a.topindex_clicked {
			background-color: #" . $top_button_color . ";
			box-shadow: #" . $top_button_shadow_color . " 0 0 2px 0 inset, #" . $top_button_shadow_color2 . " 0 1px 1px 0;
			-moz-box-shadow: #" . $top_button_shadow_color . " 0 0 2px 0 inset, #" . $top_button_shadow_color2 . " 0 1px 1px 0;
                     -webkit-box-shadow: #" . $top_button_shadow_color . " 0 0 2px 0 inset, #" . $top_button_shadow_color2 . " 0 1px 1px 0;
			text-shadow: 1px 1px 1px #" . $top_button_shadow_color . ";
			color: #" . $top_window_color . "
		}

a.navigation
		{
			color: #" . $main_window_color . "
		}

body.leftindex,
div.leftindex {
			background-color: #" . $left_window_background_color . ";
			margin: 0px;
		}
                
.alternating_row {
			background-color: #" . $alternating_row_background_color . "
		}

body.discussion
		{
			background-color: #" . $alternating_row_background_color . ";
			margin: 0;
			width:100%;
			height:100%
		}

div.add		{
			cursor: pointer;
                        float:left;
                        line-height:17px;
			color: " . $main_window_color . "
		}

div.remove	{
			cursor: pointer;
                        float:left;
                        line-height:17px;
			color: #" . $main_window_link_color . "
		}

.clicked	{
			color: #" . $main_window_link_color . ";
			text-decoration: underline
		}

div.middle-panel:hover {
    background-color: #" . $main_window_link_color . "
}

input.bibtex {
    background-color: #" . $alternating_row_background_color . ";
        border: 0;
        font-family: '" . $main_window_font_family . "',sans-serif;
			font-size: " . $main_window_font_size . "px;
                            color: #" . $main_window_color . ";
                                cursor:pointer
}

.ui-widget { font-family: '" . $main_window_font_family . "',sans-serif;  font-size: " . $main_window_font_size . "px;}
.ui-widget input, .ui-widget select, .ui-widget textarea, .ui-widget button { font-family: '" . $main_window_font_family . "',sans-serif; }
";

print $content;
?>