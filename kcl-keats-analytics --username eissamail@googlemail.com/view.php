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
$pagetitle = 'KEATS Analytics' . ": " . 'Page View';
$PAGE->set_title($pagetitle);

/*$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_rss_client'));
$PAGE->navbar->add(get_string('managefeeds', 'block_rss_client'), $managefeeds );
$PAGE->navbar->add($strtitle);*/

echo $OUTPUT->header();
//echo $OUTPUT->heading("Pages access");

switch($view)
{
   case 'page':
      echo $OUTPUT->heading("Page views report");
      $pageview = isset($_REQUEST['pageview']) &&  ! empty($_REQUEST['pageview'])? $_REQUEST['pageview']: array();
      display_pageview_chart($courseid, $pageview);
      break;

   case 'location':
      echo $OUTPUT->heading("Visits by location report");
      display_locationbased_chart($courseid, $data);
      break;

   case 'forum':
      echo $OUTPUT->heading("Forum Participation Report");
      display_forum_view($courseid);
      break;

   case 'ProgressTracker':
      echo $OUTPUT->heading("Progress Analytics Report");
      display_progress_tracker_chart($courseid);
      break;

   case 'learningdesign':
      echo $OUTPUT->heading("Learning Design Report");
      display_learning_design_chart($courseid);
      break;

   default: //Simply ignore the user request...
      break;
}

echo $OUTPUT->footer();