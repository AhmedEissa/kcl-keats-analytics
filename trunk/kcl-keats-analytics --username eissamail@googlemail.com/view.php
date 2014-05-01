<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * KEATS Analytics Moodle Block
 *
 * @package   blocks
 * @subpackage keatsanalytics
 * @copyright 2013 Eissa Creations Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/keats/lib.php');

require_login();
$courseid = optional_param('courseid', 2, PARAM_INT);//<====
$view = optional_param('view', 'page', PARAM_TEXT);
$data = optional_param('data', '', PARAM_TEXT);

$course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

$PAGE->set_url('/blocks/keats/view.php', array('courseid'=>$courseid));

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$pagetitle = 'KEATS Analytics v0.1 - Course ID: ' . $courseid;
$PAGE->set_title($pagetitle);

/*$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_rss_client'));
$PAGE->navbar->add(get_string('managefeeds', 'block_rss_client'), $managefeeds );
$PAGE->navbar->add($strtitle);*/

echo $OUTPUT->header();
//echo $OUTPUT->heading("Pages access");
$jquery = $CFG->wwwroot . '/blocks/keats/jquery';
$dateFilterHTML = '
<link rel="stylesheet" href="' . $jquery . '/themes/base/jquery.ui.all.css">
<script src="' . $jquery . '/jquery-1.10.2.js"></script>
<script src="' . $jquery . '/ui/jquery.ui.core.js"></script>
<script src="' . $jquery . '/ui/jquery.ui.widget.js"></script>
<script src="' . $jquery . '/ui/jquery.ui.datepicker.js"></script>
<link rel="stylesheet" href="' . $jquery . '/demos/demos.css">
<script>
	$(function() {
		$( "#from" ).datepicker({
			defaultDate: "+1w",
			changeMonth: true,
            altFormat: "dd-mm-yy",
			numberOfMonths: 1,
			onClose: function( selectedDate ) {
				$( "#to" ).datepicker( "option", "minDate", selectedDate );
			}
		});
		$( "#to" ).datepicker({
			defaultDate: "+1w",
			changeMonth: true,
            altFormat: "dd-mm-yy",
			numberOfMonths: 1,
			onClose: function( selectedDate ) {
				$( "#from" ).datepicker( "option", "maxDate", selectedDate );
			}
		});
	});
</script>
<Form method="post" action="' . $PAGE->url . '#tab' . $_SESSION["lasttabid"] . '">
<label for="from">Filter date from: </label>
<input type="text" id="from" name="from"/>
<label for="to"> to: </label>
<input type="text" id="to" name="to"/>
<input type="hidden" name="view" value="' . $view . '" />
<i> (leave it empty to select the whole date range)</i><br />
<input type="submit" value="Reload report"></Form>';

$from = $_POST['from'];
$to = $_POST['to'];
$datecheck = (is_date($from) && is_date($to));

switch($view)
{
   case 'page':
      $tabID = 1;
      if($from != false && $datecheck)
         $strFilter = " from: " . $from . " to " . $to;
      else
         $strFilter = "";
      echo $OUTPUT->heading("Page views report" . $strFilter);
      echo $dateFilterHTML;
      if(( ! is_null($from) ||  ! is_null($to)) &&  ! $datecheck)
      {
         echo "Empty or wrong entered dates in the date filter.";
         $from = "";
         $to = "";
      }
      display_pageview_chart($courseid, $from, $to);
      $from = false;
      break;

   case 'location':
      $tabID = 2;
      if($from != false && $datecheck)
         $strFilter = " from: " . $from . " to " . $to;
      else
         $strFilter = "";
      echo $OUTPUT->heading("Visits by location report" . $strFilter);
      echo $dateFilterHTML;
      if(( ! is_null($from) ||  ! is_null($to)) &&  ! $datecheck)
      {
         echo "Empty or wrong entered dates in the date filter.";
         $from = "";
         $to = "";
      }
      display_locationbased_chart($courseid, $data, $from, $to);
      $from = false;
      break;

   case 'forum':
      $tabID = 3;
      if($from != false && $datecheck)
         $strFilter = " from: " . $from . " to " . $to;
      else
         $strFilter = "";
      echo $OUTPUT->heading("Forum Participation Report" . $strFilter);
      echo $dateFilterHTML;
      if(( ! is_null($from) ||  ! is_null($to)) &&  ! $datecheck)
      {
         echo "Empty or wrong entered dates in the date filter.";
         $from = "";
         $to = "";
      }
      display_forum_view($courseid, $from, $to);
      $from = false;
      break;

   case 'ProgressTracker':
      $tabID = 4;
      if($from != false && $datecheck)
         $strFilter = " from: " . $from . " to " . $to;
      else
         $strFilter = "";
      echo $OUTPUT->heading("Progress Analytics Report" . $strFilter);
      echo $dateFilterHTML;
      if(( ! is_null($from) ||  ! is_null($to)) &&  ! $datecheck)
      {
         echo "Empty or wrong entered dates in the date filter.";
         $from = "";
         $to = "";
      }
      display_progress_tracker_chart($courseid, $from, $to);
      $from = false;
      break;

   case 'learningdesign':
      $tabID = 5;
      if($from != false && $datecheck)
         $strFilter = " from: " . $from . " to " . $to;
      else
         $strFilter = "";
      echo $OUTPUT->heading("Learning Design Report" . $strFilter);
      echo $dateFilterHTML;
      if(( ! is_null($from) ||  ! is_null($to)) &&  ! $datecheck)
      {
         echo "Empty or wrong entered dates in the date filter.";
         $from = "";
         $to = "";
      }
      display_learning_design_chart($courseid, $from, $to);
      $from = false;
      break;

   default: //Simply ignore the user request...
      break;
}

$_SESSION["lasttabid"] = $tabID;
echo $OUTPUT->footer();

function is_date($date, $format = 'd/m/Y')
{
   $d = DateTime::createFromFormat($format, $date);
   return $d && $d->format($format) == $date;
}
