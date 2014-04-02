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

function display_tabs($courseid, $htmlcode)
{
   global $CFG, $DB;

   $pageviewdata = display_pageview($courseid);
   $formanalyticsdata = display_form_analytics($courseid);
   $locationsdata = display_localtion_based($courseid);

   $tabs = $htmlcode . '<div id="accordion">
			<h3>Page View</h3>
			<div>' . $pageviewdata . '</div>

			<h3>Location Based</h3>
			<div>' . $locationsdata . '</div>

			<h3>Form Analytics</h3>
			<div>' . $formanalyticsdata . '</div>

			<h3>Progress Tracker</h3>
			<div><p>Section 4 Content</p></div>

			<h3>Learning Design</h3>
			<div><p>Section 5 Content</p></div>
			</div>';

   return $tabs;
}

function display_pageview($courseid)
{
   global $CFG, $DB;

   $pageview = isset($_REQUEST['pageview']) &&  ! empty($_REQUEST['pageview'])? $_REQUEST['pageview']: array();
   $staffchecked = in_array('staff', $pageview)? 'checked': null;
   $studentschecked = in_array('students', $pageview)? 'checked': null;
   $allchecked = in_array('all', $pageview)? 'checked': null;

   $url = $CFG->wwwroot . '/blocks/keats/view.php?courseid=' . $courseid . '#tab1';
   $data = "<form action='$url' method='post'>";
   $data .= "<table>";
   $data .= "<tr><td><input type='checkbox' name='pageview[]' value='staff' $staffchecked/></td><td>Staff</td>";
   $data .= "<tr><td><input type='checkbox' name='pageview[]' value='students' $studentschecked/></td><td>Students</td>";
   $data .= "<tr><td><input type='checkbox' name='pageview[]' value='all' $allchecked/></td><td>All</td>";
   $data .= "</table>";
   $data .= "<input type='hidden' name='view' value='page' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";

   return $data;
}

function display_localtion_based($courseid)
{
   global $CFG, $DB;

   $campus1_name = 'Campus 1';
   $campus2_name = 'Campus 2';
   $campus3_name = 'Campus 3';
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

   $data = 'Visited by Campus:';
   $data .= "<form action='$url' method='post'>";
   $data .= "<table>";
   $data .= "<tr><td>$campus1_name</td><td>$c1_percentage%</td>";
   $data .= "<tr><td>$campus2_name</td><td>$c2_percentage%</td>";
   $data .= "<tr><td>$campus3_name</td><td>$c3_percentage%</td>";
   $data .= "<tr><td>$outofcampus</td><td>$outofcampus_percentage%</td>";
   $data .= "</table>";
   $data .= "<input type='hidden' name='view' value='location' />";
   $data .= "<input type='hidden' name='data' value='$campusarray' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";

   return $data;
}

function display_form_analytics($courseid)
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
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";

   return $data;
}


function display_pageview_chart($courseid, $pageview)
{
   global $DB, $CFG;
   //put calculation code here...
   set_time_limit(300);
   $DataStruct = getLog($courseid);
   ///*
   $Otypes = $DataStruct["User_Type"];
   $Odates = $DataStruct["DateOnly"];
   $Onames = $DataStruct["Users"];

   $types = array_reverse($Otypes);
   $dates = array_reverse($Odates);
   $names = array_reverse($Onames);

   $unique_students = 0;
   $unique_staff = 0;
   $student_pageviews = 0;
   $staff_pageviews = 0;
   $daily_students = array();
   $daily_staff = array();

   for($i = 0; $i < sizeof($dates); $i++)
   {
      if($dates[$i + 1] == $dates[$i])
      {
         if($types[$i] == "Staff")
         {
            $staff_pageviews++;

            if( ! in_array($names[$i], $daily_staff))
            {
               array_push($daily_staff, $names[$i]);
               $unique_staff++;
            }
         }
         else
         {
            $student_pageviews++;

            if( ! in_array($names[$i], $daily_students))
            {
               array_push($daily_students, $names[$i]);
               $unique_students++;
            }
         }
      }
      else
      {
         if($types[$i] == "Staff")
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

            $unique_students = 0;
            $unique_staff = 0;
            $student_pageviews = 0;
            $staff_pageviews = 0;
            $daily_students = array();
            $daily_staff = array();
         }
         else
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

            $unique_students = 0;
            $unique_staff = 0;
            $student_pageviews = 0;
            $staff_pageviews = 0;
            $daily_students = array();
            $daily_staff = array();
         }
      }
   }
   $DateStack = $DataStruct["DateOnly"];
   $el = count($DateStack);

   $Fdate = $DateStack[$el - 1];
   $Ldate = $DateStack[0];
   $AllUsersTypes = $DataStruct["User_Type"];
   $AllUsersCount = array_count_values($AllUsersTypes);
   $NoOfEnrolledStudents = $AllUsersCount["Student"];
   $NoOfActvStudents = $NoOfEnrolledStudents;//Check this with Jonathan
   $NoOfInactStudents = $NoOfEnrolledStudents - $NoOfActvStudents;

   $NoOfActOtherUsers = $AllUsersCount["Staff"];
   $NoOfOtherUsers = $NoOfActOtherUsers;//Jonathan will get this from Fong later.....
   $NoOfInactOtherUsers = $NoOfOtherUsers - $NoOfActOtherUsers;

   $TNoOfUsers = $NoOfOtherUsers + $NoOfEnrolledStudents;
   $TNoOfActUsers = $NoOfActOtherUsers + $NoOfActvStudents;
   $TNoOfInactUsers = $NoOfInactOtherUsers + $NoOfInactStudents;

   $htmlTable = '<table border="1" bordercolor="#a00709" style="background-color:#ffffff" width="100%" cellpadding="3" cellspacing="0">';
   $htmlTable = $htmlTable . "	<tr>
		            <td class='tdTitle'>As of: $Ldate</td>
		            <td class='tdTitle'>Enrolled:</td>
		            <td class='tdTitle'>Who have already viewed this course:</td>
		            <td class='tdTitle'>Who have not yet viewed this course:</td>
	            </tr>
	            <tr>
		            <td class='tdTitle'>Number of student-users:</td>
		            <td>$NoOfEnrolledStudents</td>
		            <td>$NoOfActvStudents</td>
		            <td>$NoOfInactStudents</td>
	            </tr>
	            <tr>
		            <td class='tdTitle'>Number of other users:</td>
		            <td>$NoOfOtherUsers</td>
		            <td>$NoOfActOtherUsers</td>
		            <td>$NoOfInactOtherUsers</td>
	            </tr>
                <tr>
		            <td class='tdTitle'>Total users:</td>
		            <td>$TNoOfUsers</td>
		            <td>$TNoOfActUsers</td>
		            <td>$TNoOfInactUsers</td>
	            </tr>
            </table>";
   echo $htmlTable;
   // MotionChart is here...
   ?>
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
   //The TreeMap is here...
   $finalURL = "['Learning Resource', 'Information','Accessed'],\n";
   $finalURL = $finalURL . "['Learning Resource', null, 0],\n";
   $SearchedActions = array("book view", "page view", "url view", "resource view");
   $RecCount = count($DataStruct["Action"]);
   $bookviewCount = 0;
   $pageviewCount = 0;
   $urlviewCount = 0;
   $resourceviewCount = 0;
   for($C = 0; $C <= $RecCount; $C++)
   {
      $VUser = $DataStruct["User_Type"][$C];
      $TestUser = $DataStruct["Users"][$C];

      $V0 = str_replace(">", " ", $DataStruct["Action"][$C]);
      $V1 = $SearchedActions[0];//"book view";
      $V2 = $SearchedActions[1];//"page view";
      $V3 = $SearchedActions[2];//"url view";
      $V4 = $SearchedActions[3];//"resource view";

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
   $finalURL = $finalURL . "['Book view ($bookviewCount)','Learning Resource',$bookviewCount]" . ",\n";
   $finalURL = $finalURL . "['Page view ($pageviewCount)','Learning Resource',$pageviewCount]" . ",\n";
   $finalURL = $finalURL . "['URL view ($urlviewCount)','Learning Resource',$urlviewCount]" . ",\n";
   $finalURL = $finalURL . "['Resource view ($resourceviewCount)','Learning Resource',$resourceviewCount]" . ",\n";
   if($arrBookViewInfo != null)
   {
      $arrBookViewInfoU = array_count_values($arrBookViewInfo);
      foreach($arrBookViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $finalURL = $finalURL . "['$index ($value)','Book view ($bookviewCount)',$value]" . ",\n";
      }
   }

   if($arrPageViewInfo != null)
   {
      $arrPageViewInfoU = array_count_values($arrPageViewInfo);
      foreach($arrPageViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $finalURL = $finalURL . "['$index ($value)','Page view ($pageviewCount)',$value]" . ",\n";
      }
   }

   if($arrURLViewInfo != null)
   {
      $arrURLViewInfoU = array_count_values($arrURLViewInfo);
      foreach($arrURLViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $finalURL = $finalURL . "['$index ($value)','URL view ($urlviewCount)',$value]" . ",\n";
      }
   }

   if($arrResourceViewInfo != null)
   {
      $arrResourceViewInfoU = array_count_values($arrResourceViewInfo);
      foreach($arrResourceViewInfoU as $index=>$value)
      {
         $index = str_replace("'", "\'", $index);
         $finalURL = $finalURL . "['$index ($value)','Resource view ($resourceviewCount)',$value]" . ",\n";
      }
   }
   ?>
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
                    <script type="text/javascript">
                        google.load("visualization", "1", {packages:["treemap"]});
                        google.setOnLoadCallback(drawChart);
                        function drawChart() {
                            // Create and populate the data table.
                            var data = google.visualization.arrayToDataTable([
   <?php
   echo $finalURL;
   ?>
   ]);

                            // Create and draw the visualization.
                            var tree = new google.visualization.TreeMap(document.getElementById('chart_div'));
                            tree.draw(data, {
                            minColor: '#990000',
                            midColor: '#ddd',
                            maxColor: '#009900',
                            headerHeight: 15,
                            fontColor: 'black',
                            showScale: true});
                        }
                    </script>
   <div id="chart_div" style="width: 910px; height: 480px;"></div> <br />
   <?php

}

function display_locationbased_chart($data)
{
   $data = unserialize($data);
   $dataview = null;

   if(is_array($data))
   {
      foreach($data as $key=>$val)
      {
         $dataview .= "['$key', $val],";
      }
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
    	['Campus', 'Page access'],
    	<?php echo $dataview;?>
    	]);

    	// Create and draw the visualization.
    	new google.visualization.PieChart(document.getElementById('visualization')).
    	draw(data, {title:"Visits by campus location"});
    }

    google.setOnLoadCallback(drawVisualization);
    </script>
    <div id="visualization" style="width:750px;height:500px;margin:auto;padding-top:50px;"></div>
   <?php
}

function display_forum_view($courseid)
{
   global $CFG, $DB;

   $data = null;

   $mintime = $DB->get_field_sql("SELECT MIN(TIME) FROM {log} WHERE course = $courseid");
   $maxtime = $DB->get_field_sql("SELECT MAX(TIME) FROM {log} WHERE course = $courseid");
   $context = context_course::instance($courseid);

   $time = time();
   $mintime = $time - 31 * 3600 * 24;

   while($mintime <= $time)
   {
      $endtime = $mintime + (24 * 60 * 60);
      // Add discussion count
      $add_discussion_sql = "SELECT count(*) FROM {log}
		                       WHERE course = $courseid AND module = 'forum' AND ACTION = 'add discussion' AND time BETWEEN $mintime AND $endtime";

      $add_discussion_count = $DB->count_records_sql($add_discussion_sql);

      // Add post count
      $add_post_sql = "SELECT count(*) FROM {log}
		                 WHERE course = $courseid AND module = 'forum' AND ACTION = 'add post' AND time BETWEEN $mintime AND $endtime";


      $add_post_count = $DB->count_records_sql($add_post_sql);

      // Update post count
      $update_post_sql = "SELECT count(*) FROM {log}
		                    WHERE course = $courseid AND module = 'forum' AND ACTION = 'update post' AND time BETWEEN $mintime AND $endtime";

      $update_post_count = $DB->count_records_sql($update_post_sql);

      // View discussion count
      $view_discussion_sql = "SELECT count(*) FROM {log}
		                        WHERE course = $courseid AND module = 'forum' AND ACTION = 'view discussion' AND time BETWEEN $mintime AND $endtime";
      $view_discussion_count = $DB->count_records_sql($view_discussion_sql);

      // View forum count
      $view_forum_sql = "SELECT count(*) FROM {log}
		                   WHERE course = $courseid AND module = 'forum' AND ACTION = 'view forum' AND time BETWEEN $mintime AND $endtime";
      $view_forum_count = $DB->count_records_sql($view_forum_sql);

      // View forums count
      $view_forums_sql = "SELECT count(*) FROM {log}
		                    WHERE course = $courseid AND module = 'forum' AND ACTION = 'view forums' AND time BETWEEN $mintime AND $endtime";

      $view_forums_count = $DB->count_records_sql($view_forums_sql);

      // search count
      $forum_search_sql = "SELECT count(*) FROM {log}
		                     WHERE course = $courseid AND module = 'forum' AND ACTION = 'search' AND time BETWEEN $mintime AND $endtime";

      $forum_search_count = $DB->count_records_sql($forum_search_sql);

      $displaydate = date('Y,m,d', $mintime);
      $mintime = $endtime;
      $data .= "['$displaydate', $add_discussion_count, $add_post_count, $update_post_count, $view_discussion_count, $view_forum_count, $view_forums_count, $forum_search_count],";
   }
   ?>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
    google.load("visualization", "1", {packages:["corechart"]});
    google.setOnLoadCallback(drawChart);
    function drawChart() {
    	var data = google.visualization.arrayToDataTable([
    	['Date', 'Add Discussion', 'Add Post', 'Update Post', 'View Discussion', 'View Forum', 'View Forums', 'Search'],
    	<?php echo $data;?>
    	]);

    	var options = {
    		title: 'Number of daily forum actions per term'
    	};

    	var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
    	chart.draw(data, options);
    }
    </script>

    <div id="chart_div" style="width: 700px; height: 500px;"></div>
   <?php
}

function getLog($courseid)
{
   global $CFG, $DB, $COURSE, $USER, $SESSION;
   set_time_limit(300);

   $sql = 'SELECT c.fullname AS CourseName, from_unixtime(l.time) AS DateandTime, u.username AS Username,
           l.ip AS IPAddress, u.firstname AS FirstName, u.lastname AS LastName, u.email AS Email, l.module As Activity,
           l.action As Action, l.url As URL, l.info AS Information, r.roleid AS RoleID, l.userid AS UserID
           FROM {log} l, {user} u, {role_assignments} r, {course} c
           WHERE r.userid = l.userid AND c.fullname like :coursenamevar
           ORDER BY DateandTime Desc';

   $params = array('coursenamevar'=>'BDS%');
   $rs = $DB->get_recordset_sql($sql, $params);

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

   $recNo = 0;

   $context = get_context_instance(CONTEXT_COURSE, $courseid);
   //$context = get_context_instance(CONTEXT_SYSTEM);
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
            //$City[$recNo] = getCityFromIP($val);
            $City[$recNo] = "London";//must be changed and fixed... BUG
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
            $Information[$recNo] = $val;
         if($L == 12)
         {
            $RoleID[$recNo] = $val;
         }
         if($L == 13)
         {
            $sel_user_id = $val;
            $UType = "Staff";
            if( ! has_capability('mod/page:addinstance', $context, $sel_user_id))
               $UType = "Student";
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
                          "RoleID"=>$RoleID,
                          "Location"=>$City,
                          "User_Type"=>$UserType
                          );
   return $DataStructure;
}

function getCityFromIP($ip)
{
   set_time_limit(300);
   require_once($CFG->dirroot . "/blocks/keats/geolib/geoipcity.inc");
   require_once($CFG->dirroot . "/blocks/keats/geolib/geoipregionvars.php");
   error_reporting(E_ALL ^ E_NOTICE);
   //accessing the GeoDatabase file.
   $gi = geoip_open($CFG->dirroot . "/blocks/keats/geolib/GeoLiteCity.dat", GEOIP_STANDARD);
   if($ip == "127.0.0.1")
   {
      $cityname = "Localhost";
   }
   {
      $record = geoip_record_by_addr($gi, $ip);
      $cityname = "";
      $cityname = $record->city;
   }

   geoip_close($gi);
   return $cityname;
}