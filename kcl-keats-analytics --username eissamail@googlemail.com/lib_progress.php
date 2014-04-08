<?php
/** 
 * @Copyright 2013 King's College London?
 * @ref http://docs.moodle.org/dev/Data_manipulation_API
 * @ref TODO http://docs.moodle.org/dev/Coding_style
 * @ref http://docs.moodle.org/dev/Database_schema_introduction
 * @ref https://developers.google.com/chart/
 * @ref dental-student-tester01 pwd:iTEL12345&
 * XXX: Variously uses moodle queries, moodle API and getLOG()  
 * TODO: Benchmark and speed up (sort out db querying)
 * TODO: Fix indentation 
 */

/* just for dev*/
error_reporting(7);
ini_set('display_errors', 'On');

define ('DISPLAY_LIMIT',15);

/**
 * Get style.css
 * TODO: Include in ?view.php or lib.php?
 *
 */
function addCSS() 
{
    return '<style type="text/css">
    h3 { margin-top: 1.5em; }
    ul.block-analytics-progress { list-style-type: none; margin-left: 0; }    
    ul.block-analytics-progress .summary { float: right; margin-top: 1em; /* to balance font-szie 200% of indicator */ }
    .indicator .done { color: darkgreen; font-weight: bold; font-size: 200%;  }
    .indicator .notdone { color: red; font-weight: bold; font-size: 200%;  }    
    /* .done {  }
    .notdone { } */
    .format_table th { text-align: left; }
    .format_table th, .format_table td { border: solid 1px silver; }    
    .chart { margin-top: 2em; } /* or hides the datatables bPaginate! */
    .dev { display: none; }
    </style>';
}

/**
 * Get (or overide) current userid 
 *
 */
function getUserId() 
{    
    global $USER,$COURSE;
    //return 3;
//    return get_userid_from_username("student");    
    //return get_userid_from_username("dental-student-tester01"); 
    if ($COURSE->id == 2) 
        return get_userid_from_username("student");
    else if ($COURSE->id == 6) 
        return get_userid_from_username("dental-student-tester01");
    else
        return $USER->id;
}

/**
 * show user details if overridden
 *
 */
function showUser() 
{
    global $USER;
    
    if (getUserId() != $USER->id) echo '<p>User overridden: '.get_username_from_userid(getUserId()).'</p>';
}

/**
 * Initialise global LOG variable
 *
 * @param int courseid
 */
function init_Log($courseid) 
{
    global $LOG;
    // get log, creating global $LOG variable
    if( ! isset($LOG))
        $LOG = getLog($courseid);//?            
}

/**
 * Build table showing $LOG (for instrumentation)
 *
 * @param int $courseid
 * @return html
 */
function build_Log($courseid) {    
    global $LOG;
    
    $data = "";
    
    if( ! isset($LOG)) $LOG = getLog($courseid);
    
    $data .= '<table class="datatable format_table">';
    $data .= '<thead>';
    $data .= '<tr>';
    $keys = array();
    foreach($LOG as $key => $_) {
        $keys[] = $key;
        $data .= '<th>'.$key.'</th>';
    }
    $data .= '</tr>';    
    $data .= '</thead>';    
    
    $data .= '<tbody>';        
    foreach($LOG['Index'] as $index) {
        $data .= '<tr>';
        foreach ($keys as $key) {
            $data .= '<td>'.$LOG[$key][$index].'</td>';
        }
        $data .= '</tr>';        
    }
    $data .= '</tbody>';
    $data .= '</table>';    
    
    return $data;
}

/**
 * Output table showing $LOG (for instrumentation)
 *
 * @param int $courseid
 * @return void -> html
 */
function show_Log($courseid) {    
    echo build_Log($courseid);
}

/**
 * Initialise global MODE variable (Student or Staff view)
 *
 * @param int courseid 
 */
function init_Mode($courseid) 
{
    global $USER,$MODE;
    if( ! isset($MODE)) {
        $MODE = is_student_on_course($courseid, getUserId())?"Student":"Staff";
    }
}

/**
 * Helper?: Get course format - not currently used
 *
 * @param int courseid
 * @return string format e.g. "topic"
 */
function get_course_format($courseid)
{
    global $CFG, $DB;

    $sql = "SELECT format FROM {course_format_options} WHERE courseid = ? LIMIT 1";
    $result = $DB->get_record_sql($sql, array($courseid));
    return $result->format;
}

/**
 * Helper: Get list of modules (activities) in course, combined with useful attribute information
 *
 * @param int courseid
 * @return array of fields
 * TODO? Split into get_course_modules_info and get_course_modules_detail ?
 */
function get_course_modules_info($courseid)
{
    global $CFG, $DB;

    $sql = "SELECT  {course_modules}.id,  {course_modules}.section,  {course_modules}.module,  {modules}.name as modname,
            if (completion > 0 or completiongradeitemnumber is not null or completionview > 0 or completionexpected <> 0,true,false)
                as required
            FROM {course_modules}, {modules}
            WHERE {course_modules}.module = {modules}.id
            AND course = ?
            AND {modules}.name <> 'label'
            ;"; // leaving out labels?
    $params = array($courseid);
    $result = $DB->get_records_sql($sql, $params);

    // add further info (e.g. titles) from module-specific tables
    $c = 0;
    for($c = 0; $c < count($result); $c++)
    {        
        $module_detail = get_coursemodule_from_id($result[$c]->modname, $result[$c]->id);
        //$result[$c]->name = trim(htmlentities($module_detail->name));
        $result[$c]->name = $module_detail->name;
        /*
        if (trim($result[$c]->name) == "") {
            $result[$c]->name = "Untitled"; 
        }
        */
        
        // and href        
        $result[$c]->href = $CFG->wwwroot.'/mod/'.$result[$c]->modname.'/view.php?id='.$result[$c]->id; // ?
    }    
    return $result;
}

/**
 * Helper: Get number of students completing specified module
 *
 * @param int moduleid Identifying module in course_modules_completion (and course_modules)
 * @return int
 */
function get_course_module_students_completed($courseid, $moduleid)
{
    global $CFG, $DB;

    // return $DB->count_records('course_modules_completion', array('coursemoduleid'=>$moduleid)); // overcounts
    $rows = $DB->get_records('course_modules_completion', array('coursemoduleid'=>$moduleid)); 
    $count = 0;
    foreach ($rows as $row) {
        if (is_student_on_course($courseid, $row->userid)) 
            $count++; 
    }
    return $count;    
}

/**
 * Helper: Check whether user has completed module
 *
 * @param int moduleid Identifying module in course_modules_completion 
 * @param int userid Identifying user
 * @return bool
 */
function get_course_module_user_has_completed($moduleid,$userid)
{
    global $CFG, $DB;

    return $DB->count_records('course_modules_completion', array('coursemoduleid'=>$moduleid,'userid'=>$userid)) > 0;
}

/**
 * Helper: Check if specified user is student enrolled in course
 *
 * @param int courseid
 * @param int userid
 * @return bool
 */
function is_student_on_course($courseid, $userid)
{
    global $CFG, $DB/*, $USER*/;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $students = get_role_users(5, $context);
    $student_ids = array_keys($students);
    return in_array($userid, $student_ids);
}

/**
 * Helper: Get userid from username
 *
 * @param string username
 * @return int userid
 * TODO Return userid from getLog?
 */
function get_userid_from_username($username)
{
    global $CFG, $DB;

    $user = $DB->get_record('user', array('username'=>$username));
    return $user->id;
}

/**
 * Helper: Get username from userid 
 *
 * @param int userid
 * @return string username
 * TODO Return userid from getLog?
 */
function get_username_from_userid($userid)
{
    global $CFG, $DB;

    $user = $DB->get_record('user', array('id'=>$userid));
    return $user->username;
}

/**
 * Helper: Get number of students enrolled in course
 *
 * @param int courseid
 * @return int
 */
function get_course_number_students($courseid)
{
    global $CFG, $DB;

    $context = get_context_instance(CONTEXT_COURSE, $courseid);
    $students = get_role_users(5, $context);
    return count($students);
}

/**
 * Helper: Get number of all users in course
 *
 * @param int courseid
 * @return int
 *
 * @ref  http://docs.moodle.org/dev/Enrolment_API#get_enrolled_users.28.29 ?
 * @ref https://moodle.org/mod/forum/discuss.php?d=205992 ?
 * @ref https://moodle.org/mod/forum/discuss.php?d=118532 
 */
function get_course_total_number_users($courseid)
{
    global $CFG, $DB;

    $contextid = get_context_instance(CONTEXT_COURSE, $courseid);
    
    $sql = "SELECT u.id, u.username
    FROM mdl_user u, mdl_role_assignments r
    WHERE u.id=r.userid AND r.contextid = {$contextid->id}";
    
    return $DB->count_records_sql($sql, array());    
}

/**
 * Helper: Get users on course
 *
 * @param int courseid
 * @return array of userids
 *
 */
function get_course_users($courseid)
{
    global $CFG, $DB;

    $contextid = get_context_instance(CONTEXT_COURSE, $courseid);
    
    $sql = "SELECT u.id
    FROM mdl_user u, mdl_role_assignments r
    WHERE u.id=r.userid AND r.contextid = {$contextid->id}";
    
    $results = $DB->get_records_sql($sql, array());
    $return = array();
    foreach ($results as $result) {
        $return[] = $result->id;
    }
    return $return;
}

/**
 * Helper: Get user's roles in course
 *
 * @param int courseid
 * @param int userid 
 * @return string array 
 *
 */
function get_course_user_roles($courseid,$userid)
{
    $context = get_context_instance(CONTEXT_COURSE, $courseid);    
    $roleinfos = get_user_roles($context, $userid, false);
    $roles = array();
    foreach ($roleinfos as $roleinfo) {
        $roles[] = $roleinfo->shortname;
    }    
    /*
    $role = key($roles);
    $roleid = $roles[$role]->roleid;
    */
    return $roles;
}

/**
 * Helper: Try count users by role
 *
 * @param int courseid
 * @return array ( role => int )
 *
 */
function get_user_count_by_roles($courseid) 
{
   $users_on_course = get_course_users($courseid);
   $rolecounter = array();
   foreach ($users_on_course as $userid) {         
        $this_roles = get_course_user_roles($courseid,$userid);
        foreach ($this_roles as $role) {
            if (!in_array($role,array_keys($rolecounter))) { $rolecounter[$role] = 0; }
            $rolecounter[$role]++;
        }
   }
   return $rolecounter;       
}

/**
 * Show summary counts of roles in course
 *
 * @param int courseid
 * @return html
 */
function get_roles_summary($courseid) {
    
   $rolecounts = get_user_count_by_roles($courseid);
   $data = "<h3>Summary of course roles</h3>";
   $data .= '<table class="format_table">';   
   foreach ($rolecounts as $role => $count) {       
       $data .= "<tr><th>".$role."</th>"."<td>".$count."</td></tr>"; 
   }
   $data .= "</table>";      
   return $data;
}

/**
 * Helper: Parse moduleid from actionURL - fallback needed if ModuleId weird
 *
 * @param string $actionurl e.g. view.php?id=6
 * @return int Module id or NULL
 */
function parse_moduleid_from_actionurl($actionurl)
{
    $moduleid = NULL;
    if (strpos($actionurl, "mod") < 0) return NULL; // looking for module accesses
    if(strpos($actionurl, "id=") > -1)
    {
        $id = substr($actionurl, strpos($actionurl, 'id=') + 3);
        if(is_numeric($id))
        {
            $moduleid = $id;
        }
    }
    return $moduleid;
}

/**
 * Helper: Get trackable module types
 *
 * @param int courseid
 * @return array string
 */
function get_trackable_module_types($courseid)
{
    // get unique module types to check for
    $modules = get_course_modules_info($courseid);
    $module_types = array();
    $skip_module_types = array("folder");
    foreach($modules as $module) {
        if($module->modname) {
            if (!in_array($module->modname,$skip_module_types))
                $module_types[] = $module->modname;
        }
    }
    $module_types = array_unique($module_types);
    return $module_types;    
}

/**
 * Helper: Get modules recently accessed (by students)
 *
 * @param int courseid
 * @param int number Number of modules to count
 * @param int optional userid To limit by userid
 * @param bool optional role To limit by role (currently only student implemented)
 * @return array ( [moduleid] => array ( details ) ..)
 */
function get_course_modules_recently_accessed($courseid, $number = 3, $userid = NULL)
{
    global $CFG, $DB, $LOG, $MODE;

    $modules = get_course_modules_info($courseid);    
    $module_types = get_trackable_module_types($courseid);

    // get $number unique module accesses
    $recent_accesses = array();

    foreach($LOG['Index'] as $index)   
    {
        // limit by userid if required
        if ($userid) {
        if (!isset($LOG['UserID'][$index])) $LOG['UserID'][$index] = get_userid_from_username($LOG['Users'][$index]);
        if ($LOG['UserID'][$index] != $userid) {
            continue;
        }
    }   
    
    // try work out module Id
    /*
    if (isset($LOG['InformationID']) && is_numeric($LOG['InformationID'][$index]))
        $moduleid = $LOG['InformationID'][$index]; 
    else 
    */
        $moduleid = parse_moduleid_from_actionurl($LOG['ActionURL'][$index]); // returns NULL if not module access

    // check conditions for inclusion
    if(//$LOG['User_Type'][$index] == "Student" // not working?
        //is_student_on_course($courseid, get_userid_from_username($LOG['Users'][$index]))      
        ($moduleid != NULL // has module id
        && in_array($LOG['Activity'][$index], $module_types)
        //&& ($LOG['Activity'][$index] != "course" && $LOG['Activity'][$index] != "folder") // should be handler by $module_types
        && is_student_on_course($courseid, $LOG['UserID'][$index]) )
    ) {
        // skip if already added
        if(in_array($moduleid, $recent_accesses)) continue;

        // get module name, href and modname
        $name = ""; $href = "";
        $found = false;
        foreach($modules as $module) {
            if($module->id == $moduleid) {
                $found = true;
                $name = $module->name;
                if ($name == "") $name = $moduleid; // revert to informationID if not found  
                $href = $module->href;
                $modname = $module->modname;
            }
        }
        //if (!$found) echo '<strong>'.$moduleid.' not found.'.'</strong>';
        
        // finally, add to list
        $recent_accesses[$moduleid] = array(
                                         'modname'=>$modname, // for icons
                                         'activity'=>$name,
                                         'href'=>$href,
                                         'datetime'=>$LOG['Date/Time'][$index],
                                         'student'=>$LOG['FirstName'][$index] . ' ' . $LOG['LastName'][$index],
                                         'index'=>$index,
                                         );
    }

    // got enough?
    if(count($recent_accesses) >= $number)
        break;
    }

    return $recent_accesses;
}

/**
 * Count accesses for a module
 *
 * @param int courseid
 * @param int moduleid
 * @param bool optional uniques for unique users only
 * @param string optional role = "Student"/*||"All" for accesses to count
 * @return int
 */
function get_course_module_accesses($courseid, $moduleid, $uniques = true, $role = "Student"/*||"All"*/)
{
    global $CFG, $DB, $LOG, $MODE;
    
    $users = array(); $accesses = 0;
    foreach($LOG['Index'] as $index)   
    {
        // work out module Id
        if (isset($LOG['InformationID']) && is_numeric($LOG['InformationID'][$index]))
            $module = $LOG['InformationID'][$index]; 
        else 
            $module = parse_moduleid_from_actionurl($LOG['ActionURL'][$index]); 
    
        // skip if not module parameter
        if ($module != $moduleid) continue;
        
        // work out userid
        if (!isset($LOG['UserID'][$index])) $LOG['UserID'][$index] = get_userid_from_username($LOG['Users'][$index]);        
        
        // check skip for role parameter
        if ($role != "All") {            
            if (!is_student_on_course($courseid,$LOG['UserID'][$index])) continue;
        }

        // check skip for unique parameter
        if ($uniques) if (in_array($LOG['UserID'][$index],$users)) continue;

        // note user and count
        if (!in_array($LOG['UserID'][$index],$users)) $users[] = $LOG['UserID'][$index];
        $accesses++;
    }  
    return $accesses;
}

/**
 * Check whether user has accessed a module
 *
 * @param int courseid
 * @param int moduleid
 * @param int userid 
 * @return bool
 */
function user_has_accessed_module($courseid, $moduleid, $userid)
{
    global $LOG;
    
    foreach($LOG['Index'] as $index)
    {
        // work out module Id and skip if not parameter
        if (isset($LOG['InformationID']) && is_numeric($LOG['InformationID'][$index]))
            $module = $LOG['InformationID'][$index]; 
        else 
            $module = parse_moduleid_from_actionurl($LOG['ActionURL'][$index]); 
        if ($module != $moduleid) continue;
        
        // work out userid
        if (!isset($LOG['UserID'][$index])) $LOG['UserID'][$index] = get_userid_from_username($LOG['Users'][$index]);
        
        // check
        if ($LOG['UserID'][$index] == $userid) return true;
    }
    return false;       
}

/**
 * Check whether user has accessed a course
 *
 * @param int courseid
 * @param int userid 
 * @return bool
 * TODO Fix/use $LOG rather (needs other users included?)
 */
function user_has_accessed_course($courseid, $userid)
{
    global $DB;
    
    return $DB->get_record('log', array('course'=>$courseid,'userid'=>$userid),'id');    
}

/**
 * Get course assignments
 *
 * @param int courseid
 * @return array with moduleids,names
 */
function get_course_assignments($courseid)
{
    $modules = get_course_modules_info($courseid);
    $assignments = array();
    foreach ($modules as $module) {
        if ($module->modname == "assign") {
            $assignments[] = array(
                'id' => $module->id,
                'name' => $module->name,
            );
        }
    }
    return $assignments;
}

/**
 * Get assignment submission data
 *
 * @param int courseid
 * @param int optional userid to check whether that specific user has submitted TODO
 * @return array
 */
function get_course_assignment_submission_data($courseid, $userid = NULL)
{
    global $DB;

    $assignments =  get_course_assignments($courseid);       
    // add submission info
    foreach ($assignments as $assignment) {
                
    }
}
    
/**
 * Build and display progress analytics summary
 *
 * @param int courseid
 * @return string html
 */
function display_progress_tracker_include($courseid)
{
   global $CFG, $DB, $LOG, $USER, $MODE;
   
   //$indicator_done = '<span class = "done">&#10003;</span>';
   $indicator_done = '<span class = "done">&#9679;</span>';
   $indicator_notdone = '<span class = "notdone">&#9679;</span>';   
   
   $data = "";   
   
   // add some css in here   
   $data .= addCSS();

   if (!isset($LOG)) init_Log($courseid);
   if (!isset($MODE)) init_Mode($courseid);
   
   $num_students = get_course_number_students($courseid);

   // use for overall summary
   $Recently_Accessed_Resources = '..';
   $Required_Resources_to_View = '..';
   $Resources_Summary = '..';

   $url = $CFG->wwwroot . '/blocks/keats/view.php?courseid=' . $courseid . '#tab4';

   $data .= "<form action='$url' method='post'>";
   $data .= "<table>";
   $data .= '<tr title = "Recent accesses from all students"><td><b><u>Recently Accessed Resources:</u></b></td><td>'.$Recently_Accessed_Resources.'</td></tr>';

   $recent_accesses = get_course_modules_recently_accessed($courseid);
   $data .= '<tr><td>';
   $data .= '<ul class = "block-analytics-progress">';
   foreach($recent_accesses as $index=>$access)
   {
      if ($MODE == "Staff") {
          $indicator = "";
          $title = "";
          $class = "";
      }
      else {
          $student_has_accessed = user_has_accessed_module($courseid, $index, getUserId());
          if ($student_has_accessed) {
              $indicator = $indicator_done;
              $title = "You have accessed this activity.";
              $class = "done";
          }
          else {
              $indicator = $indicator_notdone;
              $title = "You have not yet accessed this activity.";
              $class = "notdone";              
          }
      }       
      $data .= '<li class="'.$class.'">';
      $data .= '<span class="indicator" title = "'.$title.'">';
      $data .= $indicator;
      $data .= '</span>';               
      $data .= '&nbsp;';      
      $title = ($MODE == "Staff")?$access['student']:"Someone accessed ";
      $title .= ' '.$access['datetime'];
      $data .= '<a href="'.$access['href'].'" title = "'.$title.'">';
      $data .= $access['activity'];
      $data .= '</a>';
      $data .= ':&nbsp;';
      $data .= '<span class="summary" title = "Percentage of students who have accessed this activity">';
      $num_accesses = get_course_module_accesses($courseid, $index);      
      $percentage = round($num_accesses / $num_students * 100, 1);
      $data .= $percentage;
      $data .= '%';
      $data .= '</span>';              
      $data .= '</li>';
   }
   $data .= '</ul>';
   $data .= '</td></tr>';

   $data .= "<tr><td><b><u>Required Resources to View:</u></b></td><td>$Required_Resources_to_View</td>";

   $modules = get_course_modules_info($courseid);
   /* storing counts for summary */
   $requireds = 0; // to count required activities
   $student_completions = 0; // to count a student's completions   
   $this_display_limit = round(DISPLAY_LIMIT / 2); 
   $data .= '<tr><td>';
   $data .= '<ul class = "block-analytics-progress">';
   $count = 0;
   foreach($modules as $module)
   {
      if($module->required)
      {
         $requireds++; 
         $num_students_completed = get_course_module_students_completed($courseid, $module->id);
         $percentage_completed = round($num_students_completed / $num_students * 100, 1);
         if ($MODE == "Student") {
             $student_has_completed = get_course_module_user_has_completed($module->id,getUserId());
            if ($student_has_completed) {
                $student_completions++;
            }
         }
         if ($count < $this_display_limit) {
              if ($MODE == "Staff") {
                  $indicator = "";
                  $title = "";
                  $class = "";
              }
              else {
                  if ($student_has_completed) {
                      $indicator = $indicator_done;
                      $title = "You have completed this activity.";
                      $class = "done";
                  }
                  else {
                      $indicator = $indicator_notdone;
                      $title = "You have not yet completed this activity.";
                      $class = "notdone";              
                  }                  
              }
             
             $data .= '<li class="'.$class.'">';
              $data .= '<span class="indicator" title = "'.$title.'">';
              $data .= $indicator;
              $data .= '</span>'; 
              $data .= '&nbsp;';
              $data .= '<a href="'.$module->href.'" title = "'.$title.'">';
             $data .= $module->name;
              $data .= '</a>';                          
             $data .= ':&nbsp;';
             $data .= '<span class="summary" title = "Percentage of students who have completed this activity">';
             $data .= $percentage_completed . '%';
             $data .= '</span>';         
             $data .= '</li>';
         }
         $count++;
      }
   }
   $data .= '</ul>';
   if ($count >= $this_display_limit) { 
    $data .= '<p><small>'.'(Showing '.$this_display_limit.' of '.$count.')'.'</small></p>'; 
   }
   $data .= '</td></tr>';

   $data .= "<tr><td><b><u>Required resources Summary:</u></b></td><td>$Resources_Summary</td></tr>";

   $data .= '<tr><td>';

   if ($MODE == "Staff") {
       $total = count($modules);
        $data .= 'Required activities: '.$requireds . '<br/>';
        $data .= 'Not required activities: '.($total - $requireds). '<br/>';
        $data .= 'Total: '.$total. '<br/>';
   }
   else {
      $data .= '<p>';
      $data .='You have completed '.'<strong>'.$student_completions. '</strong>'.' of ' . '<strong>'. $requireds .'</strong>'.' required activities.';
      $data .= '</p>';
   }
   $data .= '</td></tr>';

   $data .= "</table>";
   $data .= "<input type='hidden' name='view' value='ProgressTracker' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";
   
   // include and activate jquery datatables (for main content but included here so after jquery loaded)
   $data .= '<script src="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.js"></script>';
   $data .= '<style type="text/css">@import url("//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css");</style>';
   $data .= '<style type="text/css">
                .dataTables_length { float: right; opacity: 0.75; margin-top: -1em; }
                .dataTables_info { opacity: 0.75; margin-top: 0.25em; }
             </style>';   
   $data .= "<script>
   window.onload = function() {
       $(document).ready(function() { 
           $('.datatable').dataTable( {
                'iDisplayLength': ".DISPLAY_LIMIT.", 
                'bPaginate': true, 
                'bLengthChange': true, 
                'bFilter': false, 
                'aaSorting': [], // switches off any default sorting
           });
       })
   }
   </script>
   ";      

   return $data;
}

/**
 * Graph displaying functions
 * @ref https://developers.google.com/chart/interactive/docs/gallery
 */
 
function display_required_modules_completion_staff_graph($data) {
 /* TODO: Percentage/fraction confusion    */
    //global $MODE;
    
   //echo '<h4>'.'Required activities completion staff graph'.'</h4>';
   echo '<noscript>Requires JavaScript.</noscript>';    
   
   echo '
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawRequiredModulesCompletionStaffGraph);
      function drawRequiredModulesCompletionStaffGraph() {
        var data = google.visualization.arrayToDataTable([
          ["Activity", "Percentage of students completed",'./*'{ role: "style" }'.*/'],
          ';
          foreach ($data as $c => $row) {
              $fraction = $row['percentage'] / 100;        
              $style = 'fill-color: blue';      
              echo '['.'"'.$row['name'].'"'.','.$fraction./*',"'.$style.'"'.*/']'.',';
              echo "\n";
          }
          echo '
        ]);

        var options = {
          title: "Required activities completion",
          hAxis: {title: "Activity"/*, titleTextStyle: {color: "blue"}*/},
          vAxis: {format: "#%", ticks: [0.1,0.2,0.3,0.4,0.5,0.6,0.7,0.8,0.9,1]} 
        };

        var chart = new google.visualization.ColumnChart(document.getElementById("required_modules_completion_staff_graph_div"));
        chart.draw(data, options);
      }
        </script>
    <div id="required_modules_completion_staff_graph_div" class="chart" style="width: 100%; height: auto; min-height: 300px"></div>    
    ';
}

function display_required_modules_completion_student_graph($data)
{
   //echo '<h4>'.'Required activities completion student graph'.'</h4>';
   echo '<noscript>Requires JavaScript.</noscript>';    
   
   echo '
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawRequiredModulesCompletionStudentGraph);
      function drawRequiredModulesCompletionStudentGraph() {
        var data = google.visualization.arrayToDataTable([
          ["Activity", "Percentage of students completed","You completed"],
          ';
          foreach ($data as $c => $row) {
              if ($row['completed']) $student_percent = 100; else $student_percent = 0;
              echo '['.'"'.$row['name'].'"'.','.$row['percentage'].','.$student_percent.']'.',';
              echo "\n";
          }
          echo '
        ]);

        var options = {
          title: "Required activities completion",
          hAxis: {title: "Activity"},
          seriesType: "bars",
          series: {0: {type: "bars", color: "green"}, 1: {type: "bars", color: "blue"}},
          vAxis: {ticks: [0,25,50,75,100], title: "%"} 
        };

        var chart = new google.visualization.ColumnChart(document.getElementById("required_modules_completion_student_graph_div"));
        chart.draw(data, options);
      }
        </script>
    <div id="required_modules_completion_student_graph_div" class="chart" style="width: 100%; height: auto; min-height: 300px"></div>    
    ';    
}

function display_recently_accessed_modules_student_graph($data)
{
   //echo '<h4>'.'Recently accessed modules student graph'.'</h4>';
   echo '<noscript>Requires JavaScript.</noscript>';    
   
   echo '
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawRecentlyAcccessedModulesStudentGraph);
      function drawRecentlyAcccessedModulesStudentGraph() {
        var data = google.visualization.arrayToDataTable([
          ["Activity", "Percentage of students accessed","You accessed"],
          ';
          foreach ($data as $c => $row) {
              if ($row['accessed']) $student_percent = 100; else $student_percent = 0;
              echo '['.'"'.$row['name'].'"'.','.$row['percentage'].','.$student_percent.']'.',';
              echo "\n";
          }
          echo '
        ]);

        var options = {
          title: "Recently accesssed activities",
          hAxis: {title: "Activity"},
          series: {0: {type: "line", color: "green"}, 1: {type: "line", color: "blue"}},
          vAxis: {ticks: [0,25,50,75,100], title: "%"} 
        };

        var chart = new google.visualization.ColumnChart(document.getElementById("recently_accessed_modules_student_graph_div"));
        chart.draw(data, options);
      }
        </script>
    <div id="recently_accessed_modules_student_graph_div" class="chart" style="width: 100%; height: auto; min-height: 300px"></div>    
    ';    
}

function display_required_modules_staff_overview_graph($data) {

   // echo '<h4>'.'Required activities overview graph'.'</h4>';
   echo '<noscript>Requires JavaScript.</noscript>';
   
    /* initialise Google graphs code */
    // TODO: Can't load once separately?
    echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
    echo '
    <script type="text/javascript">
      // Load the Visualization API and the piechart package.
      google.load(\'visualization\', \'1.0\', {\'packages\':[\'corechart\']});
    ';
    echo '
      // Set a callback to run when the Google Visualization API is loaded.      
      google.setOnLoadCallback(drawRequiredModulesStaffOverviewGraph);
    ';
    echo '
      // Callback that creates and populates a data table,
      // instantiates the pie chart, passes in the data and
      // draws it.
      function drawRequiredModulesStaffOverviewGraph() {

        // Create the data table.
        var data = new google.visualization.DataTable();
        ';
        
        echo '
        data.addColumn(\'string\', \'Type\');
        data.addColumn(\'number\', \'Number\');
        ';
        
        echo '
        data.addRows([
          [\'Required\', '.$data['requireds'].'],
          [\'Not Required\', '.$data['notrequireds'].'],
        ]);
        ';

        echo '
        // Set chart options
        var options = {\'title\':\'Required vs Non-required Activities\',
                       \'width\':\'100%\',
                       \'height\':\'auto\',
                      };
        ';
        
        echo '
        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.PieChart(document.getElementById(\'required_modules_staff_overview_graph_div\'));
        chart.draw(data, options);
        ';
        
    echo '        
      }
    </script>
   ';
   
   echo '
    <!--Div that will hold the pie chart-->
    <div id="required_modules_staff_overview_graph_div" class="chart"></div>    
   ';

}

function display_required_modules_student_overview_graph($data) {

   // echo '<h4>'.'Required activities completion graph'.'</h4>';
   echo '<noscript>Requires JavaScript.</noscript>';
   
    echo '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
    echo '
    <script type="text/javascript">
      google.load(\'visualization\', \'1.0\', {\'packages\':[\'corechart\']});
    ';
    echo '
      google.setOnLoadCallback(drawRequiredModulesStudentOverviewGraph);
    ';
    echo '
      function drawRequiredModulesStudentOverviewGraph() {
        ';

        echo "
      var data = google.visualization.arrayToDataTable([
          ['Activities', 'Number'],
          ['Completed required activities',     ".$data['completeds']."],
          ['Non-completed required activities', ".$data['requireds']."]
        ]);
      ";

        echo '
        // Set chart options
        var options = {\'title\':\'Completion of required activities\',
                       \'width\':\'100%\',
                       \'height\':\'auto\',
                      };
        ';
        
        echo '
        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.PieChart(document.getElementById(\'required_modules_student_overview_graph_div\'));
        chart.draw(data, options);
        ';
        
    echo '        
      }
    </script>
   ';
   
   echo '
    <!--Div that will hold the pie chart-->
    <div id="required_modules_student_overview_graph_div" class="chart"></div>    
   ';  
}

/**
 * Build and display progress analytics main view
 *
 * @param int courseid
 * @return void
 */
function display_progress_tracker_chart_include($courseid)
{
   global $CFG, $DB, $USER, $MODE, $OUTPUT;
   
   if (!isset($LOG)) init_Log($courseid);
   if (!isset($MODE)) init_Mode($courseid);
   
   // instrumentation
   showUser(); echo '<h4>'.$MODE.' View'.'</h4>'; echo '<br/>';
   
   $num_students = get_course_number_students($courseid);
   echo '<p>' . 'Total students in course: ' . $num_students . '</p>';// getNumberOfUniqueStudents($courseid) != true?   

   $modules = get_course_modules_info($courseid);
   
   $data = array(); // for chart

   echo '<h3>' . 'Recently accessed activities' . '</h3>' . "\n";
   
   // show what modules are tracked
   $tracked_modules = get_trackable_module_types($courseid);
   echo '<p><small>Showing activity types: ';
   $output = "";
   foreach ($tracked_modules as $module) $output .= $module.', ';
   $output = substr($output,0,strlen($output)-2); // remove ', '
   echo $output;
   echo '</small></p>';
   
   // get data and display table
   $recent_modules = get_course_modules_recently_accessed($courseid, 10);
   echo '<table id = "table_recent" class = "datatable format_table">';
   echo '<thead>';   
   echo '<tr><th class="dev">Id</th><th>Activity</th><th>Last access</th>';
   echo '<th class="dev">Index</th>';   
   echo $MODE == "Staff"?'<th>Student</th>':'';
   echo '<th>Student accesses</th>';
   echo '<th>Percentage accessed</th>';   
   echo $MODE == "Student"?'<th>You accessed?</th>':'';
   echo '</tr>';
   echo '</thead>';      

   echo '<tbody>';
   $c = 0;
   foreach($recent_modules as $moduleid=>$recent_details)
   {
       $data[$c]['name'] = $recent_details['activity'];
      echo '<tr>';
      echo '<td class="dev">'.$moduleid.'</td>';
      echo '<td>';
      echo '<a href="'.$recent_details['href'].'" title = "'.$recent_details['activity'].'">';      
      echo '<img src = "'.$OUTPUT->pix_url('icon', $recent_details['modname']).'" alt = "'.$recent_details['modname'].'" title = "'.$recent_details['modname'].'"/>'.'&nbsp;'; 
      echo $recent_details['activity'];
      echo '</a>';
      echo '</td>';
      echo '<td>' . $recent_details['datetime'] . '</td>';
      echo '<td class="dev">'.$recent_details['index'].'</td>';      
      if ($MODE == "Staff") echo '<td>' . $recent_details['student'] . '</td>';
      $recent_details['accesses'] = get_course_module_accesses($courseid, $moduleid); // default parameters used for these table headings/details
      echo '<td>' . $recent_details['accesses'] . '</td>';
      $percentage = round($recent_details['accesses']/$num_students*100);
       $data[$c]['percentage'] = $percentage;
      echo '<td>' . $percentage . '</td>';      
      $accessed = user_has_accessed_module($courseid, $moduleid, getUserId());
      $data[$c]['accessed'] = $accessed;
      if ($MODE == "Student") echo '<td>' . ($accessed?"Yes":"No") . '</td>'; 
      echo '</tr>';
      $c++;
   }
   echo '</tbody>';   
   echo '</table>';
   
   if ($MODE == "Staff") echo '<br clear="all"/><br/><p><small>You can also view the course <a href="' . $CFG->wwwroot . '/report/outline' . '?id=' . $courseid . '">Activity report</a> and <a href="' . $CFG->wwwroot . '/report/log' . '?id=' . $courseid . '">Log</a>.</small></p>';
   
   if ($MODE == "Staff") {        
       ; // TODO
   }
   else {
       display_recently_accessed_modules_student_graph($data);
   }   

   echo '<br/><br/>' . "\n";

   echo '<h3>' . 'Required activities completion' . '</h3>' . "\n";
   // TODO: Sorting?
   
   /* storing counts for summary */
   $requireds = 0; // to count required activities
   $student_completions = 0; // to count a student's completions
   
   echo '<table id = "table_required" class = "datatable format_table">';
   echo '<thead>';   
   echo '<tr>';
   echo '<th>Activity</th><th>Due date</th><th>Students completed</th><th>Percentage completed</th>';
   if ($MODE == "Student") echo '<th>You completed?</th>';
   echo '</tr>';
   echo '</thead>';   
   
   echo '<tbody>';
      
   $data = array();   // data structure for chart

    $c = 0;   
   foreach($modules as $module)
   {
      if($module->required)
      {
          $data[$c] = array();
          $data[$c]['name'] = $module->name;
          
        $requireds++;
         $num_students_completed = get_course_module_students_completed($courseid, $module->id);         
         echo '<tr>';
         echo '<td>';
         echo '<a href="'.$module->href.'" title = "'.$module->name.'">';         
         echo '<img src = "'.$OUTPUT->pix_url('icon', $module->modname).'" alt = "'.$module->modname.'" title = "'.$module->modname.'"/>'.'&nbsp;';          
         echo $module->name;
         echo '</a>';
         echo '</td>';
         echo '<td>' . 'tbc' . '</td>';
         echo '<td>';
         echo $num_students_completed;
         echo '</td>';
         echo '<td>';
         $percentage = round($num_students_completed / $num_students * 100, 1);
         echo $percentage;
         $data[$c]['percentage'] = $percentage;
         echo '</td>';
         if ($MODE == "Student") {
             echo '<td>';
             $completed = get_course_module_user_has_completed($module->id,getUserId()); 
             if ($completed) $student_completions++;
             $data[$c]['completed'] = $completed; 
             echo $completed?"Yes":"No";
             echo '</td>';         
         }
         echo '</tr>';
         $c++;
      }
   }
   echo '</tbody>';   
   echo '</table>';
     
   if ($MODE == "Staff") echo '<br clear="all"/><br/><p><small>Note: Requires <a href="http://docs.moodle.org/23/en/Activity_completion">"Activity completion" enabled and setup</a> for activities as required.</small></p>';

   if ($MODE == "Staff") {        
       display_required_modules_completion_staff_graph($data);
   }
   else {
       display_required_modules_completion_student_graph($data);
   }

   echo '<br/><br/>' . "\n";   

   echo '<h3>' . 'Required activities summary' . '</h3>' . "\n";
   
   $total = count($modules);
   $notrequireds = $total - $requireds;

   if ($MODE == "Staff") {
     echo '<table id = "table_required_summary" class="format_table">';
     echo '<tr>'.'<th>Total activities:</th>'. '<td>'.$total.'</td>'.'</tr>';
     echo '<tr>'.'<th>Total required:</th>'. '<td>'.$requireds.'</td>'.'</tr>';
     echo '<tr>'.'<th>Total not required:</th>'.'<td>'.$notrequireds.'</td>'.'</tr>';
     echo '</table>';

     $data = array ( 
        'total' => $total,
        'requireds' => $requireds,
        'notrequireds' => $notrequireds          
      );
      display_required_modules_staff_overview_graph($data);
   }
   else {
      echo '<p>';
      echo 'You have completed '.'<strong>'.$student_completions. '</strong>'.' of ' . '<strong>'. $requireds .'</strong>'.' required activities.';
      echo '</p>';      

      $data = array ( 
        'requireds' => $requireds,
        'completeds' => $student_completions
      );
      display_required_modules_student_overview_graph($data);
   }
       
   
   /* 
   echo '<br/><br/>' . "\n";
   echo '<h3>' . 'Required activities overview' . '</h3>' . "\n";
   $requireds = 0; // to count total required activities
   echo '<table>';
   echo '<tr>';
   $include_columns = array('modname','required','name');
   foreach($modules[1] as $key=>$_)
   { 
      if (in_array($key,$include_columns)) {
          echo '<th>';
          if ($key == "modname") $key = "type";
          echo ucfirst($key);
          echo '</th>';
      }
   }
   echo '</tr>';
   foreach($modules as $module)
   {
      echo '<tr>';
      foreach($module as $key=>$val)
      {
        if (in_array($key,$include_columns)) {
            // do some counting here
           if($key == 'required' && $val == "1") $requireds++;
            
            echo '<td>';
            if ($key == "required") $val = ($val==0)?"No":"Yes";
            echo  $val;
            echo '</td>';
        }
      }
      echo '</tr>';
   }
   echo '</table>';
   */
   
   echo '<br/><br/>' . "\n";
   
   echo '<h3>' . 'Assignment submissions' . '</h3>' . "\n";

   get_course_assignment_submission_data($courseid);   
   
   return;
}

/**
 * Output summary of enrolled users and whether course accessed
 *
 * @param int $courseid
 * @return void -> html
 */
function show_course_views_summary($courseid) {
    
    global $OUTPUT;
    
   echo '<h3>' . 'Course views summary' . '</h3>' . "\n";    
    
   // get list of enrolled userids      
   $users_on_course = get_course_users($courseid);
   
   // init
   $NoOfEnrolledStudents = 0;  $TNoOfUsers = 0;    $NoOfOtherUsers = 0;     
   $NoOfActvStudents = 0; $NoOfInactStudents = 0;
   $NoOfActOtherUsers = 0; $NoOfInactOtherUsers = 0;
   
    // loop through, check and count
    foreach ($users_on_course as $userid) {        
       $has_accessed = user_has_accessed_course($courseid,$userid);
       if (is_student_on_course($courseid, $userid)) {           
           $NoOfEnrolledStudents++; 
           if ($has_accessed) $NoOfActvStudents++; else $NoOfInactStudents++;
       }
       else {
           $NoOfOtherUsers++;
           if ($has_accessed) $NoOfActOtherUsers++; else $NoOfInactOtherUsers++;
       }
        
    }
    // totals
    $TNoOfUsers = $NoOfEnrolledStudents + $NoOfOtherUsers;
    $TNoOfActUsers = $NoOfActvStudents + $NoOfActOtherUsers;
    $TNoOfInactUsers = $NoOfInactStudents + $NoOfInactOtherUsers;       
    
    //output
   $htmlTable = '<table class = "format_table">';
   $htmlTable = $htmlTable . "	<tr>
		            <th class='thTitle'></th>
		            <th class='thTitle'>Enrolled:</th>
		            <th class='thTitle'>Who have already viewed this course:</th>
		            <th class='thTitle'>Who have not yet viewed this course:</th>
	            </tr>
	            <tr>
		            <th class='thTitle'>Number of student-users:</th>
		            <td>$NoOfEnrolledStudents</td>
		            <td>$NoOfActvStudents</td>
		            <td>$NoOfInactStudents</td>
	            </tr>
	            <tr>
		            <th class='thTitle'>Number of other users:</th>
		            <td>$NoOfOtherUsers</td>
		            <td>$NoOfActOtherUsers</td>
		            <td>$NoOfInactOtherUsers</td>
	            </tr>
                <tr>
		            <th class='thTitle'>Total users:</th>
		            <td>$TNoOfUsers</td>
		            <td>$TNoOfActUsers</td>
		            <td>$TNoOfInactUsers</td>
	            </tr>
            </table>";
   echo $htmlTable;    
   
   echo '<p><small><strong>Note:</strong> Shows only <em>current</em> enrollments.</small></p>';
   
   echo get_roles_summary($courseid);

   return;
}

