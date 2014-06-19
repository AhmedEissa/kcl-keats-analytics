<?php
/** 
  * Code for page views tab specifically 
  *
  */

function display_modules_summary_table($courseid)
{
   set_time_limit(300);
   date_default_timezone_set("GMT");
   $data = '<table width="100%" cellpadding="3" cellspacing="0" class = "format_table">
            <th>Activity</th>
            <th>Students Access</th>
            <th>Students Page Views</th>
            <th>Other Users Access</th>
            <th>Other Users Page Views</th>
            <th>Total Users Access</th>
            <th>Total Views Page Views</th>
            <th>Last Accessed Date</th>';

   $DataStruct = getLog($courseid);
   $Info = $DataStruct["Information"];
   $UxDateTime = $DataStruct["Unix_Date/Time"];
   $UserTypes = $DataStruct["User_Type"];
   $ScannedInfo = array();
   foreach($Info as $Index=>$Value)
   {
      if( ! in_array($Value, $ScannedInfo) &&  ! is_null($Value) && $Value != "" && $Value != "NULL")
      {
         $Accesses = getAccess($Value, $courseid);
         $Pageviews = getPageViews($Value, $courseid);

         $arrAccess = explode("|", $Accesses);
         $arrPageviews = explode("|", $Pageviews);

         $StudentAccess = $arrAccess[0];
         $StudentPV = $arrPageviews[0];
         $OthersAccess = $arrAccess[1];
         $OthersPV = $arrPageviews[1];

         $TotalAccess = $StudentAccess + $OthersAccess;
         $TotalPV = $StudentPV + $OthersPV;

         $data = $data . '<tr align="center">
                        <td colspan="" rowspan="" headers="">' . $Value . '</td>
                        <td colspan="" rowspan="" headers="">' . $StudentAccess . '</td>
                        <td colspan="" rowspan="" headers="">' . $StudentPV . '</td>
                        <td colspan="" rowspan="" headers="">' . $OthersAccess . '</td>
                        <td colspan="" rowspan="" headers="">' . $OthersPV . '</td>
                        <td colspan="" rowspan="" headers="">' . $TotalAccess . '</td>
                        <td colspan="" rowspan="" headers="">' . $TotalPV . '</td>
                        <td colspan="" rowspan="" headers="">' . date("D d / M / Y H:i:s", $UxDateTime[$Index]) . '</td>
                    </tr>';
         $ab++;
      }
      array_push($ScannedInfo, $Value);

      if($ab >= 10)
         break;//to show only the top 10.
   }
   $data = $data . "</table>";
   echo $data;
}

function getPageViews($RInfo, $courseid)
{
   $DataStruct = getLog($courseid);
   $Info = $DataStruct["Information"];
   $UserTypes = $DataStruct["User_Type"];
   $StudentsCounter = 0;
   $OthersCounter = 0;

   foreach($Info as $Index=>$Value)
   {
      if($Value == $RInfo)
      {
         if($UserTypes[$Index] == "student")
         {
            $StudentsCounter++;
         }
         else
         {
            $OthersCounter++;
         }
      }
   }
   $Answer = $StudentsCounter . "|" . $OthersCounter;
   return $Answer;
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
            elseif(($types[$i] == "student"))//student
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

   //Showing the module view access for student and stuff   
   show_module_accesses_breakdown($courseid);
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


 /**
 * Get all module accesses breakdown
 *
 * Module types filtered for inclusion in show_module_accesses_breakdown()
 *
 * @param int courseid
 * @return array of module ids with access details
 * @ref Post to forum urls:
 * 1. http://localhost/moodle/mod/forum/post.php?forum=2
 * 2. reply: http://localhost/moodle/mod/forum/post.php?reply=130#mformforum
 */
function get_course_module_accesses_breakdown($courseid)
{
   global $CFG, $DB, $LOG, $MODE;
   global $FILTERDATES, $from, $to;
   
   $flags = array('check_view_access_only'=>true, 'check_duplicates'=>false);
   
    if( ! isset($LOG))
        $LOG = getLog($courseid, 5);   
        
//    show_Log();        

   $module_types = get_reportable_module_types($courseid);
   /*
   $module_types[] = 'url';
   $module_types[] = 'resource';   
   */
    
   $last = array ( // to store previously counted access to try prevent duplicate counting
       'module_type' => NULL,
       'moduleid' => NULL,
       'date' => NULL,
       'userid' => NULL,
   );
   $module_records = array();
   foreach($LOG['Index'] as $index)
   {
      // check date filters
      if($FILTERDATES && $from && $to)
      {
         if ($LOG['Unix_Date/Time'][$index] < $from) continue;
         if ($LOG['Unix_Date/Time'][$index] > $to) break;
      }       
       
      // get activity details
      $view_access = strpos($LOG['Action'][$index],'view') > -1;
      $module_type = strtolower($LOG['Activity'][$index]);
      //$moduleid = parse_moduleid_from_actionurl($LOG['ActionURL'][$index]);
      $moduleid = $LOG['cmid'][$index];
      
    // check for inclusions
    if ($moduleid == 0 || $moduleid == NULL) { continue; }      
    if (!in_array($module_type,$module_types)) { continue; }          
    if ($flags['check_view_access_only'] && !($view_access)) { continue; }
    if ($flags['check_duplicates']) {
        if (
           $last->module_type == $module_type
           && 
           $last->moduleid == $moduleid
           &&  
           $last->date == $LOG['DateOnly']['Index']
           && 
           $last->userid == $LOG['UserID']['Index']
        ) {
            continue;
        }
        else {
           $last->module_type = $module_type;
           $last->moduleid = $moduleid;
           $last->date = $LOG['DateOnly']['Index'];
           $last->userid = $LOG['UserID']['Index'];
        }
    }

      // create module record if not already exists
      if (!array_key_exists($moduleid,$module_records)) {
      	$module_records[$moduleid] = array();
      	$module_records[$moduleid]['student_pageviews'] = 0;
      	$module_records[$moduleid]['other_pageviews'] = 0;
        $module_records[$moduleid]['students'] = array();
      	$module_records[$moduleid]['others'] = array();
      	//$module_records[$moduleid]['accessed'] = $LOG['Unix_Date/Time'][$index];
      }
      
      // check and set last access 
	  if ($module_records[$moduleid]['accessed'] < $LOG['Unix_Date/Time'][$index]) {
        $module_records[$moduleid]['accessed'] = $LOG['Unix_Date/Time'][$index];          
      }
      

      // compute accesses
      if(is_student_on_course($courseid, $LOG['UserID'][$index])) {
      	$module_records[$moduleid]['student_pageviews']++;
      	if (!in_array($LOG['UserID'][$index],$module_records[$moduleid]['students'])) $module_records[$moduleid]['students'][] = $LOG['UserID'][$index];
      }
      else {
      	$module_records[$moduleid]['other_pageviews']++;
      	if (!in_array($LOG['UserID'][$index],$module_records[$moduleid]['others'])) $module_records[$moduleid]['others'][] = $LOG['UserID'][$index];
      }
    }

   return $module_records;
}

/** show module accesses breakdown table 
 *
 * @param courseid
 * @return void -> html
 * TODO: Don't show user ids
 */
function show_module_accesses_breakdown($courseid)
{
    global $OUTPUT, $MODE;

   $arrayListAssign= array();
   $arrayListForum= array();
   $arrayListPage= array();
   $arrayListResource= array();
   $arrayListURL= array();
   $arrayListQuiz= array();
   //$arrayElse= array();

   $arrayListData= array();

   $staffArrayListAssign= array();
   $staffArrayListForum= array();
   $staffArrayListPage= array();
   $staffArrayListResource= array();
   $staffArrayListURL= array();
   $staffArrayListQuiz= array();
   $staffArrayListData= array();

	$module_accesses = get_course_module_accesses_breakdown($courseid);
    	
    $module_types = get_reportable_module_types($courseid);
    
    echo '<h3>'.'Activity accesses'.'</h3>' . "\n";
    
   echo '<p><small>Showing activity types: ';
   $output = "";
   foreach($module_types as $module)$output .= $module . ', ';
   $output = substr($output, 0, strlen($output) - 2); // remove ', '
   echo $output;
   echo '</small></p>';    
   
	echo '<table width="100%" cellpadding="3" cellspacing="0" class = "datatable format_table">';		
	echo '
    <thead>
    <tr>
    <th rowspan="2">Activity</th>
    <th colspan="2">Students</th>
    <th colspan="2">Others *</th>
    <th rowspan="2">Last accessed</th>
    </tr>
    <tr>    
    <th>Total accesses</th>
    <th>Individuals</th>
    <th>Total accesses</th>
    <th>Individuals</th>
    </tr>
    </thead>';

    echo "\n";    
    echo '<tbody>';
    echo "\n";    
    foreach ($module_accesses as $key => $module_access) {   
        // get user details for hover info // for dev only
        $students = array_map(function($value) { return get_username_from_userid($value); }, $module_access['students']);
        $others = array_map(function($value) { return get_username_from_userid($value); }, $module_access['others']);
    
    	$module_info = get_course_module_info($courseid, $key);
        
    	//if ( trim($module_info->name) == "" || $module_info->name == NULL) continue;
    	if (!in_array($module_info->modname,$module_types)) continue;
        
		echo '<tr>';
        echo "\n";        
        
	   	echo '<td>';
        echo '<a href="' . $module_info->href . '" title = "' . $module_info->name. '">';
        echo '<img src = "' . $OUTPUT->pix_url('icon', $module_info->modname) . '" alt = "' . $module_info->modname . '" title = "' . $module_info->modname . '"/>' . '&nbsp;';
         if ($module_info->modname == "assign") {
         # code...
         array_push($arrayListAssign, $module_info->name, $module_info->modname);
         //array_push($staffArrayListAssign, $module_info->name, $module_info->modname);
      }
      elseif ($module_info->modname == "forum") {
         # code...
         array_push($arrayListForum, $module_info->name, $module_info->modname);
         //array_push($staffArrayListAssign, $module_info->name, $module_info->modname);
      }
      elseif ($module_info->modname == "page") {
         # code...
         array_push($arrayListPage, $module_info->name, $module_info->modname);
        // array_push($staffArrayListAssign, $module_info->name, $module_info->modname);
      }
      elseif ($module_info->modname == "resource") {
         # code...
         array_push($arrayListResource, $module_info->name, $module_info->modname);
         //array_push($staffArrayListAssign, $module_info->name, $module_info->modname);
      }
      elseif ($module_info->modname == "url") {
         # code...

         array_push($arrayListURL, $module_info->name, $module_info->modname);
         //array_push($staffArrayListAssign, $module_info->name, $module_info->modname);
      }
      elseif ($module_info->modname == "quiz") {
         # code...

         array_push($arrayListQuiz, $module_info->name, $module_info->modname);
         //array_push($staffArrayListAssign, $module_info->name, $module_info->modname);
      }
      else
      {
         goto av;
      }

      array_push($arrayListData, $module_info->name, $module_info->modname);
      array_push($staffArrayListData, $module_info->name, $module_info->modname);

      av:
        echo $module_info->name;
        echo '</a>';
    	echo '</td>';
        echo "\n";        
    	echo '<td>';

      if ($module_info->modname == "assign") {
         # code...
         array_push($arrayListAssign, $module_access['student_pageviews']);
      }
      elseif ($module_info->modname == "forum") {
         # code...
         array_push($arrayListForum, $module_access['student_pageviews']);
      }
      elseif ($module_info->modname == "page") {
         # code...
         array_push($arrayListPage, $module_access['student_pageviews']);
      }
      elseif ($module_info->modname == "resource") {
         # code...
         array_push($arrayListResource, $module_access['student_pageviews']);
      }
      elseif ($module_info->modname == "url") {
         # code...
         array_push($arrayListURL, $module_access['student_pageviews']);
      }
      elseif ($module_info->modname == "quiz") {
         # code...
         array_push($arrayListQuiz, $module_access['student_pageviews']);
      }
      else
      {
         goto a;
      }

      array_push($arrayListData, $module_access['student_pageviews']);
      a:

    	echo $module_access['student_pageviews'];
    	echo '</td>';
        echo "\n";        
    	echo '<td>';
        // echo '<span title = "'; foreach ($students as $user) echo $user.' '; echo '">'; // for dev only
    	echo count($module_access['students']);
    	//echo '</span>';
    	echo '</td>';
        echo "\n";        
    	echo '<td>';
       if ($module_info->modname == "assign") {
         # code...
         array_push($staffArrayListAssign, $module_access['other_pageviews']);
      }
      elseif ($module_info->modname == "forum") {
         # code...
         array_push($staffArrayListForum, $module_access['other_pageviews']);
      }
      elseif ($module_info->modname == "page") {
         # code...
         array_push($staffArrayListPage, $module_access['other_pageviews']);
      }
      elseif ($module_info->modname == "resource") {
         # code...
         array_push($staffArrayListResource, $module_access['other_pageviews']);
      }
      elseif ($module_info->modname == "url") {
         # code...
         array_push($staffArrayListURL, $module_access['other_pageviews']);
      }
      elseif ($module_info->modname == "quiz") {
         # code...
         array_push($staffArrayListQuiz, $module_access['other_pageviews']);
      }
      else
      {
         goto b;
      }
      array_push($staffArrayListData, $module_access['other_pageviews']);
      b:
    	echo $module_access['other_pageviews'];
    	echo '</td>';
        echo "\n";        
    	echo '<td>';
    	//echo '<span title = "'; foreach ($others as $user) echo $user.' '; echo '">';
    	echo count($module_access['others']);
    	//echo '</span>';
    	echo '</td>';
        echo "\n";        
    	echo '<td>';
    	echo date("D, j F Y H:i", $module_access['accessed']);
    	echo '</td>';
        echo "\n";        
    	echo '</tr>';
        echo "\n";        
    }
    echo '</tbody>';    
    echo '</table>';   
    echo '<br/><br/><p><small>* Others may include unknown user types if enrolments have changed.</small></p>';
   $finalURL = "['Learning Resource', 'Information','Accessed'],\n";
   $finalURL = $finalURL . "['Learning Resource', null, 0],\n";
   foreach ($module_types as $modType) {
      # code...
      if ($modType == "assign") {
         $numberOfItems = count($arrayListAssign)/3;
         $finalURL=$finalURL."['".$modType."', 'Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "forum") {
         $numberOfItems = count($arrayListForum)/3;
         $finalURL=$finalURL."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "page") {
         $numberOfItems = count($arrayListPage)/3;
         $finalURL=$finalURL."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "resource") {
         $numberOfItems = count($arrayListResource)/3;
         $finalURL=$finalURL."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "url") {
         $numberOfItems = count($arrayListURL)/3;
         $finalURL=$finalURL."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "quiz") {
         $numberOfItems = count($arrayListQuiz)/3;
         $finalURL=$finalURL."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
   }

  for ($i=0; $i < count($arrayListData); $i++) { 

   $vowels = array(",","'","/","\\");
   $onlyconsonants = str_replace($vowels, "", $arrayListData[$i]);
      # code...
      $finalURL=$finalURL."['".$onlyconsonants." ".$i." (".$arrayListData[$i+2].")"."','".$arrayListData[$i+1]."',".$arrayListData[$i+2]."],\n";
      $i+=2;
   }
   //Calculating and printing the forums views
   $numberOfFormViews=0;
   for ($i=0; $i < count($arrayListForum); $i++) {
      $numberOfFormViews+=$arrayListForum[$i+2];
      $i+=2;
   }

   $numberOfItems = count($arrayListForum)/3;
   $numberOfItems = "'Forum View (Number of forums: ".$numberOfItems.", Total Views: ".$numberOfFormViews.")'";
   $finalURL = str_replace("'forum'", $numberOfItems, $finalURL);

   //Calculating and printing the Assign views
   $numberOfAssignViews=0;
   for ($i=0; $i < count($arrayListAssign); $i++) {
      $numberOfAssignViews+=$arrayListAssign[$i+2];
      $i+=2;
   }

   $numberOfItems = count($arrayListAssign)/3;
   $numberOfItems = "'Assign View (Number of assigns: ".$numberOfItems.", Total Views: ".$numberOfAssignViews.")'";
   $finalURL = str_replace("'assign'", $numberOfItems, $finalURL);

   //Calculating and printing the url views
   $numberOfUrlViews=0;
   for ($i=0; $i < count($arrayListURL); $i++) {
      $numberOfUrlViews+=$arrayListURL[$i+2];
      $i+=2;
   }

   $numberOfItems = count($arrayListURL)/3;
   $numberOfItems = "'URL View (Number of URLS: ".$numberOfItems.", Total Views: ".$numberOfUrlViews.")'";
   $finalURL = str_replace("'url'", $numberOfItems, $finalURL);

   //Calculating and printing the page views
   $numberOfPageViews=0;
   for ($i=0; $i < count($arrayListPage); $i++) {
      $numberOfPageViews+=$arrayListPage[$i+2];
      $i+=2;
   }

   $numberOfItems = count($arrayListPage)/3;
   $numberOfItems = "'Page View (Number of Pages: ".$numberOfItems.", Total Views: ".$numberOfPageViews.")'";
   $finalURL = str_replace("'page'", $numberOfItems, $finalURL);

   //Calculating and printing the Resource views
   $numberOfResourceViews=0;
   for ($i=0; $i < count($arrayListResource); $i++) {
      $numberOfResourceViews+=$arrayListResource[$i+2];
      $i+=2;
   }

   $numberOfItems = count($arrayListResource)/3;

   $numberOfItems = "'Resource View (Number of Resources: ".$numberOfItems.", Total Views: ".$numberOfResourceViews.")'";
   $finalURL = str_replace("'resource'", $numberOfItems, $finalURL);

   //Calculating and printing the Quiz views
   $numberOfQuizViews=0;
   for ($i=0; $i < count($arrayListQuiz); $i++) {
      $numberOfQuizViews+=$arrayListQuiz[$i+2];
      $i+=2;
   }

   $numberOfItems = count($arrayListQuiz)/3;
   $numberOfItems = "'Quiz View (Number of Resources: ".$numberOfItems.", Total Views: ".$numberOfQuizViews.")'";
   $finalURL = str_replace("'quiz'", $numberOfItems, $finalURL);

echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
      <script type="text/javascript">google.load("visualization", "1", {packages:["treemap"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        // Create and populate the data table.
        var data = google.visualization.arrayToDataTable([';
echo $finalURL;
echo "]);
        // Create and draw the visualization.
        var tree = new google.visualization.TreeMap(document.getElementById('students_chart_div_New1'));
        tree.draw(data, {
          minColor: '#f00',
          midColor: '#ddd',
          maxColor: '#0d0',
          headerHeight: 15,
          fontColor: 'black',
          showScale: true});
        }
    </script>
    <h1>Students TreeMap Chart:</h1>
 <center>
   <div id=\"students_chart_div_New1\" style=\"width: 910px; height: 480px;\"></div></center><br />";

$staffTreeData = "['Learning Resource', 'Information','Accessed'],\n";
   $staffTreeData = $staffTreeData . "['Learning Resource', null, 0],\n";
   foreach ($module_types as $modType) {
      # code...
      if ($modType == "assign") {
         $numberOfItems = count($staffArrayListAssign)/3;
         $staffTreeData=$staffTreeData."['".$modType."', 'Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "forum") {
         $numberOfItems = count($staffArrayListForum)/3;
         $staffTreeData=$staffTreeData."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "page") {
         $numberOfItems = count($staffArrayListPage)/3;
         $staffTreeData=$staffTreeData."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "resource") {
         $numberOfItems = count($staffArrayListResource)/3;
         $staffTreeData=$staffTreeData."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "url") {
         $numberOfItems = count($staffArrayListURL)/3;
         $staffTreeData=$staffTreeData."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
      elseif ($modType == "quiz") {
         $numberOfItems = count($staffArrayListQuiz)/3;
         $staffTreeData=$staffTreeData."['".$modType."','Learning Resource',".$numberOfItems."],\n";
      }
   }

  for ($i=0; $i < count($staffArrayListData); $i++) { 
   $vowels = array(",","'","/","\\");
   $onlyconsonants = str_replace($vowels, "", $staffArrayListData[$i]);
      # code...
      $staffTreeData=$staffTreeData."['".$onlyconsonants." ".$i." (".$staffArrayListData[$i+2].")"."','".$staffArrayListData[$i+1]."',".$staffArrayListData[$i+2]."],\n";
      $i+=2;
   }

   //Calculating and printing the forums views
   $numberOfFormViews=0;
   for ($i=0; $i < count($staffArrayListForum); $i++) {
      $numberOfFormViews+=$staffArrayListForum[$i];
   }
   $numberOfItems = count($staffArrayListForum);
   $numberOfItems = "'Forum View (Number of forums: ".$numberOfItems.", Total Views: ".$numberOfFormViews.")'";
   $staffTreeData = str_replace("'forum'", $numberOfItems, $staffTreeData);

   //Calculating and printing the Assign views
   $numberOfAssignViews=0;
   for ($i=0; $i < count($staffArrayListAssign); $i++) {
      $numberOfAssignViews+=$staffArrayListAssign[$i];
   }
   $numberOfItems =count($staffArrayListAssign);
   $numberOfItems = "'Assign View (Number of assigns: ".$numberOfItems.", Total Views: ".$numberOfAssignViews.")'";
   $staffTreeData = str_replace("'assign'", $numberOfItems, $staffTreeData);

   //Calculating and printing the url views
   $numberOfUrlViews=0;
   for ($i=0; $i < count($staffArrayListURL); $i++) {
      $numberOfUrlViews+=$staffArrayListURL[$i];
   }

   $numberOfItems = count($staffArrayListURL);
   $numberOfItems = "'URL View (Number of URLS: ".$numberOfItems.", Total Views: ".$numberOfUrlViews.")'";
   $staffTreeData = str_replace("'url'", $numberOfItems, $staffTreeData);

   //Calculating and printing the page views
   $numberOfPageViews=0;
   for ($i=0; $i < count($staffArrayListPage); $i++) {
      $numberOfPageViews+=$staffArrayListPage[$i];
   }

   $numberOfItems = count($staffArrayListPage);
   $numberOfItems = "'Page View (Number of Pages: ".$numberOfItems.", Total Views: ".$numberOfPageViews.")'";
   $staffTreeData = str_replace("'page'", $numberOfItems, $staffTreeData);


   //Calculating and printing the Resource views
   $numberOfResourceViews=0;
   for ($i=0; $i < count($staffArrayListResource); $i++) {
      $numberOfResourceViews+=$staffArrayListResource[$i];
   }

   $numberOfItems = count($staffArrayListResource);
   $numberOfItems = "'Resource View (Number of Resources: ".$numberOfItems.", Total Views: ".$numberOfResourceViews.")'";
   $staffTreeData = str_replace("'resource'", $numberOfItems, $staffTreeData);

//Calculating and printing the Quiz views
   $numberOfQuizViews=0;
   for ($i=0; $i < count($staffArrayListQuiz); $i++) {
      $numberOfQuizViews+=$staffArrayListQuiz[$i];
   }

   $numberOfItems = count($staffArrayListQuiz);
   $numberOfItems = "'Quiz View (Number of Resources: ".$numberOfItems.", Total Views: ".$numberOfQuizViews.")'";
   $staffTreeData = str_replace("'quiz'", $numberOfItems, $staffTreeData);

echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>
      <script type="text/javascript">google.load("visualization", "1", {packages:["treemap"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        // Create and populate the data table.
        var data = google.visualization.arrayToDataTable([';
echo $staffTreeData;
echo "]);
        // Create and draw the visualization.
        var tree = new google.visualization.TreeMap(document.getElementById('staff_chart_div_NewStaff'));
        tree.draw(data, {
          minColor: '#f00',
          midColor: '#ddd',
          maxColor: '#0d0',
          headerHeight: 15,
          fontColor: 'black',
          showScale: true});
        }
    </script>
    <h1>Staff and Other Users TreeMap Chart:</h1>
 <center>
   <div id=\"staff_chart_div_NewStaff\" style=\"width: 910px; height: 480px;\"></div></center><br />";
} 