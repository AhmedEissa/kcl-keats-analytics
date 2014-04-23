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

defined('MOODLE_INTERNAL') || die();
include_once("lib_progress.php");

function display_tabs($courseid, $htmlcode)
{
   global $CFG, $DB;

   $pageviewdata = display_pageview($courseid);
   $formanalyticsdata = display_forum_analytics($courseid);
   $locationsdata = display_localtion_based($courseid);
   $ProgressTracker = display_progress_tracker($courseid);
   $LearningDesign = display_learning_design($courseid);

   $tab1 = "Page Views";
   $tab2 = "Visits by location";
   $tab3 = "Forum Participation";
   $tab4 = "Progress Analytics";
   $tab5 = "Learning Design";

   $Jurl = $CFG->wwwroot . '/blocks/keats';
   $htmlScript = '   <link rel="stylesheet" href="' . $Jurl . '/jquery/themes/base/jquery.ui.all.css">
                     <script src="' . $Jurl . '/jquery/jquery-1.10.2.js"></script>
                     <script src="' . $Jurl . '/jquery/ui/jquery.ui.core.js"></script>
                     <script src="' . $Jurl . '/jquery/ui/jquery.ui.widget.js"></script>
                     <script src="' . $Jurl . '/jquery/ui/jquery.ui.accordion.js"></script>
                     <link rel="stylesheet" href="' . $Jurl . '/jquery/demos/demos.css">
                     <script>
                            $(document).ready(function() {
                                        var a =0;
                                        if(location.href.indexOf("#' . $tab1 . '") > -1) { a = 0 };
                                        if(location.href.indexOf("#' . $tab2 . '") > -1) { a = 1 };
                                        if(location.href.indexOf("#' . $tab3 . '") > -1) { a = 2 };
	                                    if(location.href.indexOf("#' . $tab4 . '") > -1) { a = 3 };
	                                    if(location.href.indexOf("#' . $tab5 . '") > -1) { a = 4 };';

   $htmlScript = $htmlScript . "$('#accordion').accordion({ collapsible : true,";
   $htmlScript = $htmlScript . '                            heightStyle: "content",';
   $htmlScript = $htmlScript . "                            active : false });
                             });
                    </script>
                    <style>
                          body {";
   $htmlScript = $htmlScript . 'font-family: "Trebuchet MS", "Helvetica", "Arial",  "Verdana", "sans-serif";
	                               font-size: 62.5%;
                                   }
                                   .ui-accordion .ui-accordion-content {
	                               padding:10px;
	                               font-size:75%;
                                   }
                    </style>';

   $tabs = $htmlScript . '<div id="accordion">
			<h3>' . $tab1 . '</h3>
			<div>' . $pageviewdata . '</div>

			<h3>' . $tab2 . '</h3>
			<div>' . $locationsdata . '</div>

			<h3>' . $tab3 . '</h3>
			<div>' . $formanalyticsdata . '</div>

			<h3>' . $tab4 . '</h3>
			<div><p>' . $ProgressTracker . '</p></div>

			<h3>' . $tab5 . '</h3>
			<div><p>' . $LearningDesign . '</p></div>
			</div>';

   return $tabs;
}

function display_pageview($courseid)
{
   global $CFG, $DB;
   //need to be replaced later......
   $pageview = isset($_REQUEST['pageview']) &&  ! empty($_REQUEST['pageview'])? $_REQUEST['pageview']: array();
   $staffchecked = in_array('staff', $pageview)? 'checked': null;
   $studentschecked = in_array('students', $pageview)? 'checked': null;
   $allchecked = in_array('all', $pageview)? 'checked': null;

   $url = $CFG->wwwroot . '/blocks/keats/view.php?courseid=' . $courseid . '#tab1';
   $urlPref = $CFG->wwwroot . '/blocks/keats/urlforward.php';
   $data = "<form action='$url' method='post'>";
   $data .= "<table>";
   $data .= "<tr><td>*</td><td>Staff: 0</td>";
   $data .= "<tr><td>*</td><td>Students: 0</td>";
   $data .= "<tr><td>*</td><td>All: 0</td>";
   $data .= "</table>";
   $data .= "<input type='hidden' name='view' value='page' />";
   $data .= "<input type='hidden' name='url' value='$url' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";
   if(isset($_SESSION['View']))
      unset($_SESSION['View']);
   return $data;
}

function display_learning_design($courseid)
{
   $url = $CFG->wwwroot . '/blocks/keats/view.php?courseid=' . $courseid . '#tab5';
   $data = "<form action='$url' method='post'>";
   $data .= "<table>";
   $data .= "<tr><td>Click on \"More\" to view the chart.</td>";
   $data .= "</table>";
   $data .= "<input type='hidden' name='view' value='learningdesign' />";
   $data .= "<input type='hidden' name='url' value='$url' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";

   return $data;
}

function display_learning_design_chart($courseid, $MinDate = 0, $MaxDate = 0)
{
   echo "Under Construction...";
}

function display_progress_tracker($courseid)
{
   return display_progress_tracker_include($courseid);
}

function display_progress_tracker_chart($courseid, $MinDate = 0, $MaxDate = 0)
{
   return display_progress_tracker_chart_include($courseid);
}

function display_localtion_based($courseid)
{
   global $CFG, $DB;

   $campus1_name = 'Local Host';
   $campus2_name = 'Campus 1';
   $campus3_name = 'Campus 2';
   $outofcampus = 'Out of campus';

   $campus1_ipaddress = '127.0.0.1';//add kcl ips here...
   $campus2_ipaddress = '128.0.0.1';
   $campus3_ipaddress = '129.0.0.1';

   $sql = "SELECT count(*) FROM {log} WHERE course = $courseid";
   $total = $DB->count_records_sql($sql);

   // Campus1 percentage count
   $c1_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND ip = '$campus1_ipaddress'";
   $c1_total = $DB->count_records_sql($c1_sql);
   $c1_percentage = round(($c1_total * 100) / $total, 2);

   // Campus2 percentage count
   $c2_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND ip = '$campus2_ipaddress'";
   $c2_total = $DB->count_records_sql($c2_sql);
   $c2_percentage = round(($c2_total * 100) / $total, 2);

   // Campus3 percentage count
   $c3_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND ip = '$campus3_ipaddress'";
   $c3_total = $DB->count_records_sql($c3_sql);
   $c3_percentage = round(($c3_total * 100) / $total, 2);

   $outofcampus_percentage = 100 - ($c1_percentage + $c2_percentage + $c3_percentage);
   $campusarray = array($campus1_name=>$c1_percentage, $campus2_name=>$c2_percentage, $campus3_name=>$c3_percentage, $outofcampus=>$outofcampus_percentage);
   $campusarray = serialize($campusarray);

   $url = $CFG->wwwroot . '/blocks/keats/view.php?courseid=' . $courseid . '#tab2';

   $data = 'Visited by Locations:';
   $data .= "<form action='$url' method='post'>";
   $data .= "<table>";
   $data .= "<tr><td>$campus1_name</td><td>$c1_percentage%</td>";
   $data .= "<tr><td>$campus2_name</td><td>$c2_percentage%</td>";
   $data .= "<tr><td>$campus3_name</td><td>$c3_percentage%</td>";
   $data .= "<tr><td>$outofcampus</td><td>$outofcampus_percentage%</td>";
   $data .= "</table>";
   $data .= "<input type='hidden' name='view' value='location' />";
   $data .= "<input type='hidden' name='data' value='$campusarray' />";
   $data .= "<input type='hidden' name='url' value='$url' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";

   return $data;
}

function display_forum_analytics($courseid)
{
   global $CFG, $DB;

   // Add discussion count
   $add_discussion_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND module = 'forum' AND ACTION = 'add discussion'";
   $add_discussion_count = $DB->count_records_sql($add_discussion_sql);

   // Add post count
   $add_post_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND module = 'forum' AND ACTION = 'add post'";
   $add_post_count = $DB->count_records_sql($add_post_sql);

   // Update post count
   $update_post_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND module = 'forum' AND ACTION = 'update post'";
   $update_post_count = $DB->count_records_sql($update_post_sql);

   // View discussion count
   $view_discussion_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND module = 'forum' AND ACTION = 'view discussion'";
   $view_discussion_count = $DB->count_records_sql($view_discussion_sql);

   // View forum count
   $view_forum_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND module = 'forum' AND ACTION = 'view forum'";
   $view_forum_count = $DB->count_records_sql($view_forum_sql);

   // View forums count
   $view_forums_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND module = 'forum' AND ACTION = 'view forums'";
   $view_forums_count = $DB->count_records_sql($view_forums_sql);

   // search count
   $forum_search_sql = "SELECT count(*) FROM {log} WHERE course = $courseid AND module = 'forum' AND ACTION = 'search'";
   $forum_search_count = $DB->count_records_sql($forum_search_sql);

   $data = "<table>";
   $data .= "<tr><td>Add Discussion</td><td>$add_discussion_count</td>";
   $data .= "<tr><td>Add Post</td><td>$add_post_count</td>";
   $data .= "<tr><td>Update Post</td><td>$update_post_count</td>";
   $data .= "<tr><td>View Discussion</td><td>$view_discussion_count</td>";
   $data .= "<tr><td>View Forum</td><td>$view_forum_count</td>";
   $data .= "<tr><td>View Forums</td><td>$view_forums_count</td>";
   $data .= "<tr><td>Search</td><td>$forum_search_count</td>";
   $data .= "</table>";

   $url = $CFG->wwwroot . '/blocks/keats/view.php?courseid=' . $courseid . '#tab3';
   $data .= "<form action='$url' method='post'>";
   $data .= "<input type='hidden' name='view' value='forum' />";
   $data .= "<input type='hidden' name='url' value='$url' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";

   return $data;
}

function display_pageview_chart($courseid, $MinDate = 0, $MaxDate = 0)
{
   global $DB, $CFG;

   set_time_limit(300);
   $DataStruct = getLog($courseid, 5);

   $arrDate = $DataStruct["Date/Time"];

   $Otypes = $DataStruct["User_Type"];
   $Odates = $DataStruct["DateOnly"];
   $Onames = $DataStruct["Users"];

   $types = array_reverse($Otypes);
   $dates = array_reverse($Odates);
   $names = array_reverse($Onames);

   date_default_timezone_set('GMT');

   if($MinDate == 0 || $MaxDate == 0)
   {
      $MinDate = reset($arrDate);
      $MaxDate = end($arrDate);
      $UnixMinDate = strtotime($MinDate);
      $UnixMaxDate = strtotime($MaxDate);
   }
   else
   {
      $GMMinDate = str_replace("/", "-", $MinDate);
      $GMMaxDate = str_replace("/", "-", $MaxDate);
      $UnixMinDate = strtotime($GMMinDate);
      $UnixMaxDate = strtotime($GMMaxDate);
   }

   //motion-chart painting code
   $unique_others = 0;
   $unique_students = 0;
   $unique_staff = 0;
   $others_pageviews = 0;
   $student_pageviews = 0;
   $staff_pageviews = 0;
   $daily_others = array();
   $daily_students = array();
   $daily_staff = array();

   for($i = 0; $i < sizeof($dates); $i++)
   {
      $Fdate = $arrDate[$i];
      $timestamp = strtotime($Fdate);

      if(($timestamp >= $UnixMinDate) && ($timestamp <= $UnixMaxDate))
      {
         if($dates[$i + 1] == $dates[$i])
         {
            if(strrpos($types[$i], "teacher")) //Staff
            {
               $staff_pageviews++;

               if( ! in_array($names[$i], $daily_staff))
               {
                  array_push($daily_staff, $names[$i]);
                  $unique_staff++;
               }
            }
            elseif($types[$i] == "student")
            {
               $student_pageviews++;

               if( ! in_array($names[$i], $daily_students))
               {
                  array_push($daily_students, $names[$i]);
                  $unique_students++;
               }
            }
            else
            {
               $others_pageviews++;

               if( ! in_array($names[$i], $daily_others))
               {
                  array_push($daily_others, $names[$i]);
                  $unique_others++;
               }
            }
         }
         else
         {
            if(strrpos($types[$i], "teacher")) //Staff
            {
               $staff_pageviews++;

               if( ! in_array($names[$i], $daily_staff))
               {
                  array_push($daily_staff, $names[$i]);
                  $unique_staff++;
               }
               //echo "Date: $dates[$i], Total student visits: $student_pageviews, No of Unique students: $unique_students, Names: " . print_r($daily_students, true) . "<br />";
               //echo "Date: $dates[$i], Total staff visits: $staff_pageviews, No of Unique staff: $unique_staff, Names: " . print_r($daily_staff, true) . "<br /><br />";

               $day = date("d", strtotime($dates[$i]));
               $month = date("m", strtotime($dates[$i]));
               $year = date("Y", strtotime($dates[$i]));
               $TheDate = mktime(0, 0, 0, $month, $day, $year);
               //$GoogleMDate = gmdate("Y,m,d", $TheDate);
               $GoogleMDate = "$year,$month-1,$day";
               $finalURL = $finalURL . "['Students', new Date($GoogleMDate), $unique_students, $student_pageviews],\n";
               $finalURL = $finalURL . "['Staff', new Date($GoogleMDate), $unique_staff, $staff_pageviews],\n";
               $finalURL = $finalURL . "['Other Users', new Date($GoogleMDate), $unique_others, $others_pageviews],\n";

               $unique_students = 0;
               $unique_staff = 0;
               $unique_others = 0;
               $student_pageviews = 0;
               $staff_pageviews = 0;
               $others_pageviews = 0;
               $daily_students = array();
               $daily_staff = array();
               $daily_others = array();
            }
            elseif(($types[$i] == "student")) //student
            {
               $student_pageviews++;

               if( ! in_array($names[$i], $daily_students))
               {
                  array_push($daily_students, $names[$i]);
                  $unique_students++;
               }

               //echo "Date: $dates[$i], Total student visits: $student_pageviews, No of Unique students: $unique_students, Names: " . print_r($daily_students, true) . "<br />";
               //echo "Date: $dates[$i], Total staff visits: $staff_pageviews, No of Unique staff: $unique_staff, Names: " . print_r($daily_staff, true) . "<br /><br />";

               $day = date("d", strtotime($dates[$i]));
               $month = date("m", strtotime($dates[$i]));
               $year = date("Y", strtotime($dates[$i]));
               $TheDate = mktime(0, 0, 0, $month, $day, $year);
               //$GoogleMDate = gmdate("Y,m,d", $TheDate);
               $GoogleMDate = "$year,$month-1,$day";
               $finalURL = $finalURL . "['Students', new Date($GoogleMDate), $unique_students, $student_pageviews],\n";
               $finalURL = $finalURL . "['Staff', new Date($GoogleMDate), $unique_staff, $staff_pageviews],\n";
               $finalURL = $finalURL . "['Other Users', new Date($GoogleMDate), $unique_others, $others_pageviews],\n";

               $unique_students = 0;
               $unique_staff = 0;
               $unique_others = 0;
               $student_pageviews = 0;
               $staff_pageviews = 0;
               $others_pageviews = 0;
               $daily_students = array();
               $daily_staff = array();
               $daily_others = array();
            }
            else
            {
               $others_pageviews++;

               if( ! in_array($names[$i], $daily_others))
               {
                  array_push($daily_others, $names[$i]);
                  $unique_others++;
               }

               //echo "Date: $dates[$i], Total student visits: $student_pageviews, No of Unique students: $unique_students, Names: " . print_r($daily_students, true) . "<br />";
               //echo "Date: $dates[$i], Total staff visits: $staff_pageviews, No of Unique staff: $unique_staff, Names: " . print_r($daily_staff, true) . "<br /><br />";

               $day = date("d", strtotime($dates[$i]));
               $month = date("m", strtotime($dates[$i]));
               $year = date("Y", strtotime($dates[$i]));
               $TheDate = mktime(0, 0, 0, $month, $day, $year);
               //$GoogleMDate = gmdate("Y,m,d", $TheDate);
               $GoogleMDate = "$year,$month-1,$day";
               $finalURL = $finalURL . "['Students', new Date($GoogleMDate), $unique_students, $student_pageviews],\n";
               $finalURL = $finalURL . "['Staff', new Date($GoogleMDate), $unique_staff, $staff_pageviews],\n";
               $finalURL = $finalURL . "['Other Users', new Date($GoogleMDate), $unique_others, $others_pageviews],\n";

               $unique_students = 0;
               $unique_staff = 0;
               $unique_others = 0;
               $student_pageviews = 0;
               $staff_pageviews = 0;
               $others_pageviews = 0;
               $daily_students = array();
               $daily_staff = array();
               $daily_others = array();
            }
         }
      }
   }
   //Summary table is here...
   $DateStack = $DataStruct["DateOnly"];
   $el = count($DateStack);
   $Fdate = $DateStack[$el - 1];
   $Ldate = $DateStack[0];
   show_course_views_summary($courseid, $Ldate);
   // MotionChart is here...
   ?>
	<h1>Motion Chart:</h1>
    <script type="text/javascript" src="//www.google.com/jsapi"></script>
	<script type="text/javascript">
	google.load('visualization', '1', {packages: ['motionchart']});

	function drawVisualization() {
		var data = new google.visualization.DataTable();
		data.addColumn('string', 'Users');
        data.addColumn('date', 'Date');
        data.addColumn('number', 'Unique users');
        data.addColumn('number', 'Pageviews');
		data.addRows([
   <?php
   //put the final text here...
   echo $finalURL;
   ?>
		]);
        var options = {};
   <?php
   echo 'options[\'state\'] = \'{"sizeOption":"3","nonSelectedAlpha":1,"dimensions":{"iconDimensions":["dim0"]},"yZoomedDataMin":1,"iconKeySettings":[],"xZoomedDataMax":586396800000,"yZoomedIn":false,"iconType":"BUBBLE","showTrails":false,"xLambda":1,"yAxisOption":"2","playDuration":15000,"uniColorForNonSelected":false,"xZoomedIn":false,"xAxisOption":"_TIME","yLambda":1,"orderedByX":false,"time":"1988","xZoomedDataMin":567993600000,"colorOption":"_UNIQUE_COLOR","duration":{"multiplier":1,"timeUnit":"D"},"orderedByY":false}\';';
   ?>
		options['width'] = 910;
        options['height'] = 400;
        options['showXMetricPicker'] = false;
        options['showAdvancedPanel'] = false;
        options['showSelectListComponent'] = false;
        var motionchart = new google.visualization.MotionChart(
		document.getElementById('visualization'));
		motionchart.draw(data, options);
	}

	google.setOnLoadCallback(drawVisualization);
	</script>
	<div id="visualization" style="width:910px;height:400px;margin:auto;padding-top:50px;"></div><br />
	<?php
   //The TreeMap painting code and html code are here...
   $finalURL = "['Learning Resource', 'Information','Accessed'],\n";
   $finalURL = $finalURL . "['Learning Resource', null, 0],\n";

   $StudentfinalURL = $finalURL;
   $StafffinalURL = $finalURL;

   $SearchedActions = array("book view", "page view", "url view", "resource view");
   $RecCount = count($DataStruct["Action"]);
   $bookviewCount = 0;
   $pageviewCount = 0;
   $urlviewCount = 0;
   $resourceviewCount = 0;
   $StaffbookviewCount = 0;
   $StaffpageviewCount = 0;
   $StaffurlviewCount = 0;
   $StaffresourceviewCount = 0;

   for($C = 0; $C <= $RecCount; $C++)
   {
      $SelUserType = $DataStruct["User_Type"][$C];

      $V0 = str_replace(">", " ", $DataStruct["Action"][$C]);
      $V1 = $SearchedActions[0];//"book view";
      $V2 = $SearchedActions[1];//"page view";
      $V3 = $SearchedActions[2];//"url view";
      $V4 = $SearchedActions[3];//"resource view";

      $Fdate = $arrDate[$C];
      $timestamp = strtotime($Fdate);

      if(($timestamp >= $UnixMinDate) && ($timestamp <= $UnixMaxDate))
      {
         if($SelUserType == "student")
         {
            if($V1 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrBookViewInfo[$bookviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrResourceViewInfo[$bookviewCount] = "N/A";
               }
               $bookviewCount++;
            }

            if($V2 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrPageViewInfo[$pageviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrResourceViewInfo[$pageviewCount] = "N/A";
               }
               $pageviewCount++;
            }

            if($V3 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrURLViewInfo[$urlviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrResourceViewInfo[$urlviewCount] = "N/A";
               }
               $urlviewCount++;
            }

            if($V4 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrResourceViewInfo[$resourceviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrResourceViewInfo[$resourceviewCount] = "N/A";
               }
               $resourceviewCount++;
            }
         }
         else //Staff and Others
         {
            if($V1 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrStaffBookViewInfo[$StaffbookviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrStaffResourceViewInfo[$StaffbookviewCount] = "N/A";
               }
               $StaffbookviewCount++;
            }

            if($V2 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrStaffPageViewInfo[$StaffpageviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrStaffResourceViewInfo[$StaffpageviewCount] = "N/A";
               }
               $StaffpageviewCount++;
            }

            if($V3 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrStaffURLViewInfo[$StaffurlviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrStaffResourceViewInfo[$StaffurlviewCount] = "N/A";
               }
               $StaffurlviewCount++;
            }

            if($V4 == $V0)
            {
               if($DataStruct["Information"][$C] != "")
               {
                  $arrStaffResourceViewInfo[$StaffresourceviewCount] = $DataStruct["Information"][$C];
               }
               else
               {
                  $arrStaffResourceViewInfo[$StaffresourceviewCount] = "N/A";
               }
               $StaffresourceviewCount++;
            }
         }
      }
   }

   $StudentfinalURL = $StudentfinalURL . "['Book view ($bookviewCount)','Learning Resource',$bookviewCount]" . ",\n";
   $StudentfinalURL = $StudentfinalURL . "['Page view ($pageviewCount)','Learning Resource',$pageviewCount]" . ",\n";
   $StudentfinalURL = $StudentfinalURL . "['URL view ($urlviewCount)','Learning Resource',$urlviewCount]" . ",\n";
   $StudentfinalURL = $StudentfinalURL . "['Resource view ($resourceviewCount)','Learning Resource',$resourceviewCount]" . ",\n";

   $StafffinalURL = $StafffinalURL . "['Book view ($StaffbookviewCount)','Learning Resource',$StaffbookviewCount]" . ",\n";
   $StafffinalURL = $StafffinalURL . "['Page view ($StaffpageviewCount)','Learning Resource',$StaffpageviewCount]" . ",\n";
   $StafffinalURL = $StafffinalURL . "['URL view ($StaffurlviewCount)','Learning Resource',$StaffurlviewCount]" . ",\n";
   $StafffinalURL = $StafffinalURL . "['Resource view ($StaffresourceviewCount)','Learning Resource',$StaffresourceviewCount]" . ",\n";

   //Student TreeMap Array builders
   if($arrBookViewInfo != null)
   {
      $arrBookViewInfoU = array_count_values($arrBookViewInfo);
      foreach($arrBookViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StudentfinalURL = $StudentfinalURL . "['$index ($value BV)','Book view ($bookviewCount)',$value]" . ",\n";
      }
   }

   if($arrPageViewInfo != null)
   {
      $arrPageViewInfoU = array_count_values($arrPageViewInfo);
      foreach($arrPageViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StudentfinalURL = $StudentfinalURL . "['$index ($value PV)','Page view ($pageviewCount)',$value]" . ",\n";
      }
   }

   if($arrURLViewInfo != null)
   {
      $arrURLViewInfoU = array_count_values($arrURLViewInfo);
      foreach($arrURLViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StudentfinalURL = $StudentfinalURL . "['$index ($value UV)','URL view ($urlviewCount)',$value]" . ",\n";
      }
   }

   if($arrResourceViewInfo != null)
   {
      $arrResourceViewInfoU = array_count_values($arrResourceViewInfo);
      foreach($arrResourceViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StudentfinalURL = $StudentfinalURL . "['$index ($value RV)','Resource view ($resourceviewCount)',$value]" . ",\n";
      }
   }

   //Staff and Others TreeMap Array builders

   if($arrStaffBookViewInfo != null)
   {
      $arrStaffBookViewInfoU = array_count_values($arrStaffBookViewInfo);
      foreach($arrStaffBookViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StafffinalURL = $StafffinalURL . "['$index ($value BV)','Book view ($StaffbookviewCount)',$value]" . ",\n";
      }
   }

   if($arrStaffPageViewInfo != null)
   {
      $arrStaffPageViewInfoU = array_count_values($arrStaffPageViewInfo);
      foreach($arrStaffPageViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StafffinalURL = $StafffinalURL . "['$index ($value PV)','Page view ($StaffpageviewCount)',$value]" . ",\n";
      }
   }

   if($arrStaffURLViewInfo != null)
   {
      $arrStaffURLViewInfoU = array_count_values($arrStaffURLViewInfo);
      foreach($arrStaffURLViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StafffinalURL = $StafffinalURL . "['$index ($value UV)','URL view ($StaffurlviewCount)',$value]" . ",\n";
      }
   }

   if($arrStaffResourceViewInfo != null)
   {
      $arrStaffResourceViewInfoU = array_count_values($arrStaffResourceViewInfo);
      foreach($arrStaffResourceViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $StafffinalURL = $StafffinalURL . "['$index ($value RV)','Resource view ($StaffresourceviewCount)',$value]" . ",\n";
      }
   }


   ?>
   <h1>Students TreeMap Chart:</h1>
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
                    <script type="text/javascript">
                        google.load("visualization", "1", {packages:["treemap"]});
                        google.setOnLoadCallback(drawChart);
                        function drawChart() {
                            // Create and populate the data table.
                            var data = google.visualization.arrayToDataTable([
   <?php
   echo $StudentfinalURL;
   ?>
   ]);

                            // Create and draw the visualization.
                            var tree = new google.visualization.TreeMap(document.getElementById('students_chart_div'));
                            tree.draw(data, {
                            minColor: '#990000',
                            midColor: '#ddd',
                            maxColor: '#009900',
                            headerHeight: 15,
                            fontColor: 'black',
                            showScale: true});
                        }
                    </script>
   <center>
   <div id="students_chart_div" style="width: 910px; height: 480px;"></div></center><br />

   <h1>Staff and Other Users TreeMap Chart:</h1>
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
                    <script type="text/javascript">
                        google.load("visualization", "1", {packages:["treemap"]});
                        google.setOnLoadCallback(drawChart);
                        function drawChart() {
                            // Create and populate the data table.
                            var data = google.visualization.arrayToDataTable([
   <?php
   echo $StafffinalURL;
   ?>
   ]);

                            // Create and draw the visualization.
                            var Stree = new google.visualization.TreeMap(document.getElementById('staff_chart_div'));
                            Stree.draw(data, {
                            minColor: '#990000',
                            midColor: '#ddd',
                            maxColor: '#009900',
                            headerHeight: 15,
                            fontColor: 'black',
                            showScale: true});
                        }
                    </script>
   <center>
   <div id="staff_chart_div" style="width: 910px; height: 480px;"></div></center><br />
   <?php

}

function getNumberOfUniqueStudents($courseid)
{
   $DataStruct = getLog($courseid, 1);
   $UsersArray = $DataStruct["Users"];
   $UsersTypeArray = $DataStruct["User_Type"];
   $rec_size = count($UsersTypeArray);

   for($i = 1; $i <= $rec_size; $i++)
   {
      $user = $UsersArray[$i];
      $type = $UsersTypeArray[$i];
      if($UsersArray[$i + 1] == $user)
      {
         if($type == "student")
         {
            $c++;
         }
      }
   }
   return $c;
}

function getNumberOfUniqueStaff($courseid)
{
   $DataStruct = getLog($courseid, 1);
   $UsersArray = $DataStruct["Users"];
   $UsersTypeArray = $DataStruct["User_Type"];
   $rec_size = count($UsersTypeArray);

   for($i = 1; $i <= $rec_size; $i++)
   {
      $user = $UsersArray[$i];
      $type = $UsersTypeArray[$i];
      if($UsersArray[$i + 1] == $user)
      {
         if(strrpos($type, "teacher")) //"Staff"
         {
            $c++;
         }
      }
   }
   return $c;
}

function display_locationbased_chart($courseid, $data, $MinDate = 0, $MaxDate = 0)
{
   $DataStructure = getLog($courseid);//, 2);
   $NLLocations = $DataStructure["Location"];
   $arrDate = $DataStructure["Date/Time"];
   $Locations = array_replace($NLLocations, array_fill_keys(array_keys($NLLocations, null), 'Unknown'));

   date_default_timezone_set('GMT');

   if($MinDate == 0 || $MaxDate == 0)
   {
      $MinDate = reset($arrDate);
      $MaxDate = end($arrDate);
      $UnixMinDate = strtotime($MinDate);
      $UnixMaxDate = strtotime($MaxDate);
   }
   else
   {
      $GMMinDate = str_replace("/", "-", $MinDate);
      $GMMaxDate = str_replace("/", "-", $MaxDate);
      $UnixMinDate = strtotime($GMMinDate);
      $UnixMaxDate = strtotime($GMMaxDate);
   }

   //Code for the PI Chart
   $ArrLoc = $DataStructure["Location"];
   $ArrUsers = $DataStructure["User_Type"];
   $cnt = count($ArrUsers);
   for($p = 0; $p <= $cnt; $p++)
   {
      $var1 = $ArrLoc[$p];
      $var2 = $ArrUsers[$p];
      $Fdate = $arrDate[$p];
      $timestamp = strtotime($Fdate);
      if(($timestamp >= $UnixMinDate) && ($timestamp <= $UnixMaxDate))
      {
         /*if(($var2 == "student")) //Not agreed yet with Dr. Jonathan.
         {
         $arrStudentsResults[$p] = $var1;
         }
         else //Staff or other
         {
         $arrStaffResults[$p] = $var1;
         }*/
         $arrLocations[$p] = $var1;
      }
   }
   //$arrResultsStudents = array_count_values($arrStudentsResults);//Not agreed yet with Dr. Jonathan.
   //$arrResultsStaff = array_count_values($arrStaffResults);

   $arrResults = array_count_values($arrLocations);
   foreach($arrResults as $index=>$value)
   {
      $dataview = $dataview . "['$index',$value]" . ",\n";
   }

   ?>
	<script type="text/javascript" src="//www.google.com/jsapi"></script>
    <script type="text/javascript">
    google.load('visualization', '1', {packages: ['corechart']});
    </script>
    <script type="text/javascript">
    function drawVisualization() {
    	// Create and populate the data table.
    	var data = google.visualization.arrayToDataTable([
    	['Location', 'Total Pageviews'],
    	<?php echo $dataview;?>
    	]);

    	// Create and draw the visualization.
    	new google.visualization.PieChart(document.getElementById('visualization')).
    	draw(data, {title:"Visits by users location"});
    }

    google.setOnLoadCallback(drawVisualization);
    </script>
    <div id="visualization" style="width:750px;height:500px;margin:auto;padding-top:50px;"></div><br />
   <?php

   //Code for GeoMap Chart
   echo "      <script type='text/javascript' src='https://www.google.com/jsapi'></script>
                    <script type='text/javascript'>
                    google.load('visualization', '1', {'packages': ['geochart']});
                    google.setOnLoadCallback(drawMarkersMap);

                    function drawMarkersMap() {
                    var data = google.visualization.arrayToDataTable([
                        ['City',   'Number of Access'],";
   echo $dataview;
   echo "      ]);

                      var options = {
                        region: 'GB',
                        displayMode: 'markers',
                        colorAxis: {colors: ['green', 'blue']}
                    };

                    var chart = new google.visualization.GeoChart(document.getElementById('chart_div'));
                    chart.draw(data, options);
                    };
                    </script>
                ";
   echo '    <center><div id="chart_div" style="width: 750px; height: 500px;"></div></center>';
}

function display_forum_view($courseid, $MinDate = 0, $MaxDate = 0)
{
   global $CFG, $DB;
   $DataStruct = getLog($courseid, 5);
   $arrAction = $DataStruct["Action"];
   $arrDate = $DataStruct["Date/Time"];//the date needs to be reformated to be yyyy-mm-dd
   $SearchedActivity = "forum";// this must changed via GUI later.
   $SearchedActions = array("add discussion", "add post", "update post", "view discussion", "view forum", "view forums", "search");// this must changed via GUI later.
   date_default_timezone_set('GMT');

   if($MinDate == 0 || $MaxDate == 0)
   {
      $MinDate = reset($arrDate);
      $MaxDate = end($arrDate);
      $UnixMinDate = strtotime($MinDate);
      $UnixMaxDate = strtotime($MaxDate);
   }
   else
   {
      $GMMinDate = str_replace("/", "-", $MinDate);
      $GMMaxDate = str_replace("/", "-", $MaxDate);
      $UnixMinDate = strtotime($GMMinDate);
      $UnixMaxDate = strtotime($GMMaxDate);
   }

   //error_reporting(E_ALL ^ E_NOTICE);

   $finalURL = "\n['Date' ,'Add Discussion','Add Post','Update Post','View Discussion','View Forum','View Forums','Search'],\n";
   $rN = 0;
   $cntArrMax = count($arrAction);
   $RM = 0;
   //for($i = $cntArrMax; $i >= 0; $i--)
   for($i = 0; $i <= $cntArrMax; $i++)
   {
      $VUser = $DataStruct["User_Type"][$i];
      $TestUser = $DataStruct["Users"][$i];
      $Pos = explode(">", $arrAction[$i]);// [0] = Activity, [1] = Action
      if($Pos[0] == $SearchedActivity)
      {
         if(in_array($Pos[1], $SearchedActions))
         {
            $Fdate = $arrDate[$i];
            $timestamp = strtotime($Fdate);
            $Ndate = gmdate("Y/m/d", $timestamp);
            if(($timestamp >= $UnixMinDate) && ($timestamp <= $UnixMaxDate))
            {
               if($Ndate != $RM)
               {
                  $Count0 = CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[0], $Ndate);
                  $Count1 = CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[1], $Ndate);
                  $Count2 = CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[2], $Ndate);
                  $Count3 = CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[3], $Ndate);
                  $Count4 = CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[4], $Ndate);
                  $Count5 = CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[5], $Ndate);
                  $Count6 = CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[6], $Ndate);

                  $finalURL = $finalURL . "['$Ndate',   $Count0, $Count1, $Count2, $Count3, $Count4, $Count5, $Count6],\n";
                  $RM = $Ndate;
                  $rN++;
               }
            }
         }
      }
   }
   echo '      <script type="text/javascript" src="http://www.google.com/jsapi"></script>
                    <script type="text/javascript">';
   echo "          google.load('visualization', '1', {packages: ['corechart']});
                    </script>";
   echo '      <script type="text/javascript">
                        function drawVisualization() {
                            // Create and populate the data table.
                            var data = google.visualization.arrayToDataTable([';
   //get the data here...
   if($rN != 0)
   {
      echo $finalURL;
   }
   else
   {
      $finalURL = $finalURL . "['01/01/1900',   0, 0, 0, 0, 0, 0, 0],\n";
      echo $finalURL;
   }
   echo "              ]);
                            // Create and draw the visualization.
                            new google.visualization.LineChart(document.getElementById('visualization')).";
   echo '              draw(data, {curveType: "none",
                            width: 700, height: 500,';
   echo "              title: 'Number of daily forum actions per term',
                            axisTitlesPosition: 'out',
                            hAxis: {title: 'Date'},
                            vAxis: {title: 'Number of Actions per day', maxValue: 10}}
                            );
                        }
                        google.setOnLoadCallback(drawVisualization);
                    </script>";
   if($rN == 0)
      echo "<b>No forum data is availabe.</b> " . $MinDate . " - " . $MaxDate;
   echo '<center><div id="visualization" style="width: 700px; height: 500px;"></div></center><br />';
   //the second chart start here...


   $rN = 0;
   $arrAction = $DataStruct["Action"];
   $arrDate = $DataStruct["Date/Time"];//the date needs to be reformated to be yyyy-mm-dd

   $SearchedActivity = "forum";// this must changed via GUI later.
   $SearchedActions = array("add discussion", "add post", "update post", "view discussion", "view forum", "view forums", "search");// this must changed via GUI later.

   $finalURL = "\n['Date' ,'Add Discussion','Add Post','Update Post','View Discussion','View Forum','View Forums','Search'],\n";

   $cntArrMax = count($arrAction);
   $RM = 0;
   //for($i = $cntArrMax; $i >= 0; $i--)
   for($i = 0; $i <= $cntArrMax; $i++)
   {
      $VUser = $DataStruct["User_Type"][$i];
      $TestUser = $DataStruct["Users"][$i];
      $Pos = explode(">", $arrAction[$i]);// [0] = Activity, [1] = Action
      if($Pos[0] == $SearchedActivity)
      {
         if(in_array($Pos[1], $SearchedActions))
         {
            $Fdate = $arrDate[$i];
            $timestamp = strtotime($Fdate);
            $Ndate = date("Y/m/d", $timestamp);
            if(($timestamp >= $UnixMinDate) && ($timestamp <= $UnixMaxDate))
            {
               if($Ndate != $RM)
               {
                  $Count0 = $Count0 + CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[0], $Ndate);
                  $Count1 = $Count1 + CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[1], $Ndate);
                  $Count2 = $Count2 + CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[2], $Ndate);
                  $Count3 = $Count3 + CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[3], $Ndate);
                  $Count4 = $Count4 + CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[4], $Ndate);
                  $Count5 = $Count5 + CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[5], $Ndate);
                  $Count6 = $Count6 + CountSetAllUsers($DataStruct, $SearchedActivity, $SearchedActions[6], $Ndate);

                  $finalURL = $finalURL . "['$Ndate',   $Count0, $Count1, $Count2, $Count3, $Count4, $Count5, $Count6],\n";
                  $RM = $Ndate;
                  $rN++;
               }
            }
         }
      }
   }
   echo '    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
                <script type="text/javascript">';
   echo "      google.load('visualization', '1', {packages: ['corechart']});";
   echo '    </script>
                <script type="text/javascript">
                    function drawVisualization() {
                        var data = google.visualization.arrayToDataTable([';
   if($rN != 0)
   {
      echo $finalURL;
   }
   else
   {
      $finalURL = $finalURL . "['01/01/1900',   0, 0, 0, 0, 0, 0, 0],\n";
      echo $finalURL;
   }
   echo "            ]);
                        new google.visualization.ColumnChart(document.getElementById('visualization1')).
                            draw(data,";
   echo '                    {title:"Total forum action",
                                width:700, height:500,
                                hAxis: {title: "Date"},
                                vAxis: {title: "Number of Actions"}}
                            );
                    }


                    google.setOnLoadCallback(drawVisualization);
                </script>
                <center><div id="visualization1" style="width: 700px; height: 500px;"></div></center>';
}

function getLog($courseid, $Flag = 0)
{
   //ini_set('display_errors', 1);
   //error_reporting( ~ 0);
   ini_set('memory_limit', '-1');
   global $CFG, $DB, $COURSE, $USER, $SESSION;
   set_time_limit(300);
   date_default_timezone_set("GMT");
   require_once($CFG->dirroot . '/course/lib.php');
   require_once($CFG->dirroot . '/report/log/locallib.php');
   require_once($CFG->libdir . '/adminlib.php');
   require_once($CFG->libdir . '/csvlib.class.php');

   //error_reporting(E_ALL ^ E_NOTICE);

   if($Flag != 5)
   {
      require_once($CFG->dirroot . "/blocks/keats/geolib/geoipcity.inc");
      require_once($CFG->dirroot . "/blocks/keats/geolib/geoipregionvars.php");
      require_once($CFG->dirroot . "/blocks/keats/Excel/reader.php");
      $KCLIPMap = $CFG->dirroot . "/blocks/keats/KCLNET.xls";//KCL IP Addres XLS file path
      $KCLIPData = new Spreadsheet_Excel_Reader();//read the attached KCL IP Addresses file
      $KCLIPData->setOutputEncoding('CP1251//IGNORE');
      $KCLIPData->read($KCLIPMap);
      $MaxRowNum = $KCLIPData->sheets[0]['numRows'];
      for($p = 2; $p <= $MaxRowNum; $p++)
      {
         $IPRanges[$p - 2] = $KCLIPData->sheets[0]['cells'][$p][1];
         $Rv = $KCLIPData->sheets[0]['cells'][$p][2];
         if($Rv == "Waterloo")
         {
            $CampusNames[$p - 2] = $Rv . ", London";//Problem with London Waterloo Campus !!
         }
         elseif($Rv == "St Thomas")
         {
            $CampusNames[$p - 2] = $Rv . ", London";//Problem with London St Thomass Campus as well!!
         }
         elseif($Rv == "Guys")
         {
            $CampusNames[$p - 2] = $Rv . ", London";//Problem with London Guy's Campus as well!!
         }

         elseif($Rv == "Kingsway")
         {
            $CampusNames[$p - 2] = $Rv . ", London";//Oh... Yet another problem with another Campus!!
         }
         //....  Exception are added here...
         else
         {
            $CampusNames[$p - 2] = $Rv;
         }
      }

      $RangeArrSize = count($IPRanges);

      //accessing the GeoDatabase file.
      $gi = geoip_open($CFG->dirroot . "/blocks/keats/geolib/GeoLiteCity.dat", GEOIP_STANDARD);
   }
   /*
   $sql = 'SELECT c.fullname AS CourseName, from_unixtime(l.time) AS DateandTime, u.username AS Username,
   l.ip AS IPAddress, u.firstname AS FirstName, u.lastname AS LastName, u.email AS Email, l.module As Activity,
   l.action As Action, l.url As URL, rc.name AS Information, r.roleid AS RoleID, l.userid AS UserID, c.id as CourseID
   FROM {log} l, {user} u, {role_assignments} r, {course} c, {resource} rc
   WHERE r.userid = l.userid AND rc.id = l.info AND c.id = ' . $courseid;//c.fullname like :coursenamevar';
   */
   $sql = "SELECT c.fullname AS CourseName, from_unixtime(l.time) AS DateandTime,u.username Username,
           l.ip IPAddress, u.firstname FirstName, u.lastname LastName, u.email Email, l.module Activity,
           l.action Action, l.url URL, rc.name AS Information, l.userid AS UserID, l.info AS InformationID,
           rle.shortname UserRole

           FROM {log} l LEFT JOIN {user} u ON l.userid = u.id
           LEFT JOIN {resource} rc ON rc.id = l.info
           LEFT JOIN {course} c ON c.id = l.course
           LEFT JOIN {role_assignments} rl ON rl.userid = l.userid
           LEFT JOIN {role} rle ON rle.id = rl.roleid

           WHERE c.id = " . $courseid . "
           ";

   $CountSQL = "SELECT count(l.userid) FROM {log} l, {user} u, {role_assignments} r, {course} c, {resource} rc
                WHERE r.userid = l.userid AND c.id = " . $courseid;

   if($Flag == 0 || $Flag == 5)
   {
      //$sql = $sql . " GROUP BY u.username, l.time order by l.time DESC";
      $sql = $sql . " GROUP BY u.username, l.time order by DateandTime asc;";
   }
   elseif($Flag == 1)
   {
      //$sql = $sql . " GROUP BY u.username ORDER BY u.username Asc";
      $sql = $sql . " GROUP BY u.username, l.time ORDER BY Username Asc";
   }
   elseif($Flag == 2)
   {
      $sql = $sql . " GROUP BY u.username ORDER BY u.username Desc";
      //$sql = $sql . " GROUP BY Username ORDER BY Username Desc";
   }

   $indeces = array();
   $CourseName = array();
   $DateandTime = array();
   $Username = array();
   $IPAddress = array();
   $FirstName = array();
   $LastName = array();
   $Email = array();
   $Activity = array();
   $Action = array();
   $URL = array();
   $Information = array();
   $RoleID = array();
   $DateOnly = array();
   $UnixDateTime = array();
   $UserType = array();
   $City = array();
   // and
   $InformationID = array();
   $UserID = array();

   $recNo = 0;

   $context = get_context_instance(CONTEXT_COURSE, $courseid);
   //$context = get_context_instance(CONTEXT_SYSTEM);

   //ini_set('memory_limit', '-1');
   //$rs = $DB->get_recordset_sql($sql, $params);

   $OverFlow = 0;

   $params = array(null);
   $all_records_count = $DB->count_records_sql($CountSQL, $params);

   $rs = $DB->get_recordset_sql($sql);

   //set_time_limit(7200);  //2 hours

   while($rs->valid())
   {
      $L = 1;
      $recv = $rs->current();
      foreach($recv as $index=>$val)
      {
         if($L == 1)
         {
            $indeces[$recNo] = $recNo;
            $CourseName[$recNo] = $val;
         }
         if($L == 2)
         {
            $UnixDateTime[$recNo] = strtotime($val);//Unix Time stamp
            $DateOnly[$recNo] = gmdate("d-m-Y", $UnixDateTime[$recNo]);//dd-mm-yyyy
            $DateandTime[$recNo] = gmdate("d-m-Y H:i:s", $UnixDateTime[$recNo]);//dd-mm-yyyy HH:MM:SS
         }
         if($L == 3)
            $Username[$recNo] = $val;
         if($L == 4)
         {
            $IPAddress[$recNo] = $val;
            if($Flag != 5)
            {
               $ip = $val;
               $cityname = "";

               if($ip == "127.0.0.1")
               {
                  $cityname = "Localhost";
               }
               else
               {
                  $CampName = IPAddressCheck($IPRanges, $CampusNames, $ip);
                  if($CampName != "Out of KCL")
                  {
                     $cityname = $CampName;
                  }
                  else
                  {
                     $record = geoip_record_by_addr($gi, $ip);
                     $cityname = $record->city;
                  }
               }
               $City[$recNo] = $cityname;
            }
            else
            {
               $City[$recNo] = $val;
            }
         }
         if($L == 5)
            $FirstName[$recNo] = $val;
         if($L == 6)
            $LastName[$recNo] = $val;
         if($L == 7)
            $Email[$recNo] = $val;
         if($L == 8)
            $Activity[$recNo] = $val;
         if($L == 9)
            $Action[$recNo] = $Activity[$recNo] . ">" . $val;
         if($L == 10)
            $URL[$recNo] = $val;
         if($L == 11)
         {
            $info = $val;
            $info = format_string($info);
            $info = strip_tags(urldecode($info));// Some XSS protection
            $Information[$recNo] = $info;
         }
         if($L == 12)
         {
            $sel_user_id = $val;
            $UserID[$recNo] = $sel_user_id;
         }
         if($L == 13)
         {
            $InformationID[$recNo] = $val;
         }
         if($L == 14)
         {
            $user_role = $val;

            if (is_null($user_role))
            {
               $UType = "Other User";
            }
            else
            {
               $UType = $user_role;
            }

            $UserType[$recNo] = $UType;
         }
         $L++;
      }
      $rs->next();
      $recNo++;
   }

   $rs->close();

   $DataStructure = array("Index"=>$indeces,
                          "Course"=>$CourseName,
                          "DateOnly"=>$DateOnly,
                          "Date/Time"=>$DateandTime,
                          "Unix_Date/Time"=>$UnixDateTime,
                          "Users"=>$Username,
                          "IP"=>$IPAddress,
                          "FirstName"=>$FirstName,
                          "LastName"=>$LastName,
                          "Email"=>$Email,
                          "Activity"=>$Activity,
                          "Action"=>$Action,
                          "ActionURL"=>$URL,
                          "Information"=>$Information,
                          "Location"=>$City,
                          "User_Type"=>$UserType,
                          "RecordsNo"=>$all_records_count,
                          "UserID"=>$UserID,
                          "InformationID"=>$InformationID
                          );
   if($Flag != 5)
      geoip_close($gi);
   return $DataStructure;
}

function getSingleUserAddressRec($ReqUserID)
{
   global $DB;
   $UserSQL = "SELECT address FROM {user} where id = ?";
   $params = array($ReqUserID);
   $UserTypeRec = $DB->get_field_sql($UserSQL, $params);
   return $UserTypeRec;
}

function IPAddressCheck($RangeIP, $CampusNameArr, $checkip)
{
   set_time_limit(300);
   $RangeArrSize = count($RangeIP);

   for($i = 0; $i <= $RangeArrSize; $i++)
   {
      $range = $RangeIP[$i];

      @list($ip, $len) = explode('/', $range);

      if(($min = ip2long($ip)) !== false &&  ! is_null($len))
      {
         $clong = ip2long($checkip);
         $max = ($min | (1 << (32 - $len)) - 1);
         if($clong > $min && $clong < $max)
         {
            // ip is in range
            $Answer = $CampusNameArr[$i];
            break;
         }
         else
         {
            $Answer = "Out of KCL";
         }
      }
   }
   return $Answer;
}

function CountSetAllUsers($DataClass, $ActivityName, $ActionName, $InsDate)
{
   $DClass = $DataClass;//$_SESSION['DataClass'];
   $Answer = 0;
   $DataDate = $DClass["Date/Time"];
   $DataAction = $DClass["Action"];
   $MaxRecNumber = count($DataAction) - 1;//to start from 0

   for($i = $MaxRecNumber; $i >= 0; $i--)
   {
      $ActionStr = $DataAction[$i];
      $Poz = explode(">", $ActionStr);// [0] = Activity, [1] = Action
      if($Poz[0] == $ActivityName)
      {
         if($Poz[1] == $ActionName)
         {
            $ClassDate = $DataDate[$i];
            $JstDate = date("Y/m/d", strtotime($ClassDate));
            if($JstDate == $InsDate)
            {
               $Answer++;
            }
         }
      }
   }
   return $Answer;
}
