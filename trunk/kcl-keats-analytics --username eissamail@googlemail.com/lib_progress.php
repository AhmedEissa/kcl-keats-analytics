<?php
/** 
 * @Author 2013-2014 Brent.Cunningham@kcl.ac.uk
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

define ('DISPLAY_LIMIT_MAIN',15);
define ('DISPLAY_LIMIT_BLOCK',3);

/**
 * Get style.css
 * TODO: Include in ?view.php or lib.php?
 *
 */
function addCSS() 
{
    return '<style type="text/css">
    h3 { margin-top: 1.5em; }
    table.block-analytics-progress  { width: 100%; font-size: 85%; }
    table.block-analytics-progress tr, table.block-analytics-progress td { padding: 0; } 
    .indicator .done { color: darkgreen; font-weight: bold; font-size: 200%;  }
    .indicator .halfdone { color: orange; font-weight: bold; font-size: 200%;  }
    .indicator .notdone { color: red; font-weight: bold; font-size: 200%;  }
    table.block-analytics-progress td.summary { float: right; margin-top: 0.45em; }
    /* .done {  }
    .notdone { }
    .halfdone { } */
    .format_table th { text-align: left; background-color:#ffffff; }
    .format_table th, .format_table td { border: solid 1px #a00709 !important; background-color:#ffffff; }    	
    .chart { margin-top: 2em; } /* or hides the datatables bPaginate! */
    .dev { display: none; }
	table#block-layout td.overall { color: blue; }
    </style>';
}

/**
 * Get (or overide) current userid 
 *
 */
function getUserId() 
{    
    global $USER,$COURSE;
    //return $USER->id;
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
        $LOG = getLog($courseid,5); // use flag 5 to skip geo information, not needed for progress functions
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
 * Helper: Try clean up MS Word characters
 *
 * @param string
 * @return string
 * @thanks http://stackoverflow.com/questions/7419302/converting-microsoft-word-special-characters-with-php 
 */
function tidyMSWord($string)
{
    $search = array('&','<','>','"',chr(212),chr(213),chr(210),chr(211),chr(209),chr(208),chr(201),chr(145),chr(146),chr(147),chr(148),chr(151),chr(150),chr(133),chr(194));
    $replace = array('&amp;','&lt;','&gt;','&quot;','&#8216;','&#8217;','&#8220;','&#8221;','&#8211;','&#8212;','&#8230;','&#8216;','&#8217;','&#8220;','&#8221;','&#8211;','&#8212;','&#8230;','');

    return str_replace($search, $replace, $string);
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

    $sql = "SELECT  {course_modules}.id, {course_modules}.instance, {course_modules}.section,  {course_modules}.module,  {modules}.name as modname,
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
        //$result[$c]->name = $module_detail->name;
        $result[$c]->name = tidyMSWord($module_detail->name); // htmlentities || htmlspecialchars?
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
 * Helper: Get number of students completing specified module (current students)
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
 * Helper: Check if specified user is student (currently) enrolled in course
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
 * Helper: Get number of students currently enrolled in course
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
 * Helper: Get number of all users currently in course
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
 * Helper: Get users currently on course
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
 * Helper: Get user's current roles in course
 *
 * @param int courseid
 * @param int userid 
 * @return string array 
 */ 

 /* 
 * @ref http://stackoverflow.com/questions/22161606/sql-query-for-courses-enrolment-on-moodle ? 
 SELECT c.id as courseid, c.shortname, u.id as userid, u.username, concat(u.firstname,' ',u.lastname) as name,r.id as roleid, r.shortname as role
FROM mdl_user u
INNER JOIN mdl_role_assignments ra ON ra.userid = u.id
INNER JOIN mdl_context ct ON ct.id = ra.contextid AND ct.contextlevel = 50
INNER JOIN mdl_course c ON c.id = ct.instanceid
INNER JOIN mdl_role r ON r.id = ra.roleid
order by c.id, r.id
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
 * Helper: Try count (current) users by role
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
 * Show summary counts of (current) roles in course
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
 * Helper: Get modules recently accessed (by current students)
 *
 * @param int courseid
 * @param int number Number of modules to count
 * @param int optional userid To limit by userid
 * @param bool optional role To limit by role (currently only student implemented)
 * @return array ( [moduleid] => array ( details ) ..)
 */
function get_course_modules_recently_accessed($courseid, $number = DISPLAY_LIMIT_MAIN, $userid = NULL)
{
    global $CFG, $DB, $LOG, $MODE;

    $modules = get_course_modules_info($courseid);    
    $module_types = get_trackable_module_types($courseid);

    // get $number unique module accesses
    $recent_accesses = array();
	
    foreach($LOG['Index'] as $index)   
    {			
        if (!isset($LOG['UserID'][$index])) $LOG['UserID'][$index] = get_userid_from_username($LOG['Users'][$index]);
		
		// skip if not current student		
		if (!is_student_on_course($courseid, $LOG['UserID'][$index])) continue; 
			
        // limit by userid if required
        if ($userid) {
			if ($LOG['UserID'][$index] != $userid) continue;
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
			//&& ($LOG['Activity'][$index] != "course" && $LOG['Activity'][$index] != "folder") // should be handled by $module_types 
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
		if(count($recent_accesses) >= $number) break;
		
	} // foreach

    return $recent_accesses;
}

/**
 * Count accesses for a module. 
 *
 * If role == "Student" then counts accesses by current students only
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
		/*
        if (isset($LOG['InformationID']) && is_numeric($LOG['InformationID'][$index]))
            $module = $LOG['InformationID'][$index]; 
        else 
		*/
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
                'instance' => $module->instance,
                'title' => $module->name,
                'href' => $module->href,
            );
        }
    }
    return $assignments;
}

/**
 * Get assignments with submission data
 *
 * Submission count for current students only
 * 
 * @param int courseid
 * @param int optional userid to check whether that specific user has submitted TODO
 * @return array of assignments info with submissions numbers added
 * @ref mdl_assign_submission
 */
function get_course_assignment_submission_data($courseid, $userid = NULL)
{
    global $DB;
    
    $num_students = get_course_number_students($courseid);

    $assignments =  get_course_assignments($courseid);       
    // add submissions info to assignments
    for ($c = 0; $c < count($assignments); $c++) {         
        $sql = "SELECT userid,status FROM {assign_submission} WHERE assignment = '".$assignments[$c]['instance']."';";
        $results = $DB->get_records_sql($sql);
        $submissions = 0; $drafts = 0; $user_status = NULL;
        foreach ($results as $result) {
            if (!is_student_on_course($courseid, $result->userid)) continue;
            if ($result->status == "submitted") $submissions++;
            if ($result->status == "draft") $drafts++;
            if ($userid != NULL) {
                if ($result->userid == $userid) $user_status = $result->status;
            }
        }
        $assignments[$c]['submissions'] = $submissions;
       // and add percentages
       $assignments[$c]['percentage_submitted'] = round($assignments[$c]['submissions']/$num_students*100);
       $assignments[$c]['drafts'] = $drafts;
       $assignments[$c]['percentage_drafted'] = round($assignments[$c]['drafts']/$num_students*100);
       
        if ($userid != NULL) { if ($user_status == NULL) $user_status = "Not done"; $assignments[$c]['your_status'] = $user_status; }
    }
    return $assignments;
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
   $indicator_halfdone = '<span class = "halfdone">&#9679;</span>';      
   $indicator_notdone = '<span class = "notdone">&#9679;</span>';   
   
   $data = "";   
   
   // add some css in here   
   $data .= addCSS();

   if (!isset($LOG)) init_Log($courseid);
   if (!isset($MODE)) init_Mode($courseid);
   
   $num_students = get_course_number_students($courseid);

   // use for overall summary
   // placeholders e.g. {Recently_Accessed_Resources} in $data will be replaced after computations are completed
   $Recently_Accessed_Resources = '..';
   $Required_Resources_to_View = '..';
   $Resources_Summary = '..';
   $Assignments_Summary = '..';

   $url = $CFG->wwwroot . '/blocks/keats/view.php?courseid=' . $courseid . '#tab4';

   $data .= "<form action='$url' method='post'>";
   $data .= '<table id = "block-layout">';
   $data .= '<tr title = "Recent accesses from current students"><td><b><u>Recently Accessed Resources:</u></b></td><td class="overall" id = "recently_accessed_resources_overall" title="Rough indicator only">{Recently_Accessed_Resources}</td></tr>';

   $recent_accesses = get_course_modules_recently_accessed($courseid,DISPLAY_LIMIT_BLOCK);
   $data .= '<tr><td colspan = "2">';
   
   $data .= '<table class = "block-analytics-progress">';
   
   // counters for summary
   $student_accesses = 0; 
   
   foreach($recent_accesses as $index=>$access)
   {
	   // get access counts
      $num_accesses = get_course_module_accesses($courseid, $index);
      $percentage = round($num_accesses / $num_students * 100, 1);	   
	   	   
      if ($MODE == "Staff") {
          $indicator = "";
          $title = $percentage."% of students have accessed this activity";
		  $title .= ' (Most recent access: '.$access['datetime'].')';
          $indicatorclass = "";
      }
      else {
          $student_has_accessed = user_has_accessed_module($courseid, $index, getUserId());
          if ($student_has_accessed) {
              $indicator = $indicator_done;
              $title = "You are 1 of ".$percentage.'% of students who have accessed this activity';
              $indicatorclass = "done";
			  $student_accesses++;
          }
          else {
              $indicator = $indicator_notdone;
              $title = $percentage.'% of students have accessed this activity';			  
			  if ($percentage > 0) $title .= ' but not you';
              $indicatorclass = "notdone";
          }
      }       
       $data .= '<tr title = "'.$title.'">';
      $data .= '<td class="indicator '.$class.'">'; 
      $data .= $indicator;
      $date .= '/td>';
      $data .= '<td class="activity">'; 
      $data .= '<a href="'.$access['href'].'" title = "'.$title.'">';
      $data .= $access['activity'];
      $data .= '</a>';
      $date .= '/td>';
      $date .= '<tr>';      
   }
   $data .= '</table>';
    $data .= '<p><small>'.'(Showing '.DISPLAY_LIMIT_BLOCK.')'.'</small></p>'; 
	
	// add overall summary
	if ($MODE == "Staff") {
		$Recently_Accessed_Resources = "";
	} 
	else {
		$Recently_Accessed_Resources = round($student_accesses/DISPLAY_LIMIT_BLOCK*100) . "%";
	}
   $data = str_replace("{Recently_Accessed_Resources}",$Recently_Accessed_Resources,$data);	
   
   $data .= '</td></tr>';

   $data .= '<tr><td><b><u>Required Resources to View:</u></b></td><td id = "required_resources_to_view_overall" class="overall">{Required_Resources_to_View}</td>';
   
   $data .= '<tr><td colspan = "2">';   

   $modules = get_course_modules_info($courseid);
   /* storing counts for summary */
   $requireds = 0; // to count required activities
   $student_completions = 0; // to count a student's completions   
    
   $data .= '<table class = "block-analytics-progress">';
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
         if ($count < DISPLAY_LIMIT_BLOCK) {
              if ($MODE == "Staff") {
                  $indicator = "";
                  $title = "";
                  $indicatorclass = "";
              }
              else {
                  if ($student_has_completed) {
                      $indicator = $indicator_done;
                      $title = "You are 1 of ".$percentage."% of students who have completed this required activity.";
                      $indicatorclass = "done";
                  }
                  else {
                      $indicator = $indicator_notdone;
					  $title = $percentage.'% of students have completed this required activity';
					  if ($percentage > 0) $title .= ' but not you';
                      $indicatorclass = "notdone";              
                  }                  
              }
             $data .= '<tr title = "'.$title.'">';
             $data .= '<td class="indicator">';
              $data .= $indicator;
              $data .= '</td>';
              $data .= '<td class="activity">';              
              $data .= '<a href="'.$module->href.'">';
             $data .= $module->name;
              $data .= '</a>';                          
              $data .= '</td>';   
             $data .= '</tr>';
         }
         $count++;
      }
   }
   $data .= '</table>';
   if ($count >= DISPLAY_LIMIT_BLOCK) { 
    $data .= '<p><small>'.'(Showing '.DISPLAY_LIMIT_BLOCK.' of '.$count.')'.'</small></p>'; 
   }
   $data .= '</td></tr>';

   $data .= '<tr><td><b><u>Required resources Summary:</u></b></td><td class = "overall" id = "resources_overall">{Resources_Summary}</td></tr>';

   $data .= '<tr><td colspan="2">';

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
   
	// overall summary
   if ($MODE == "Staff") {
	   $Resources_Summary = "";
   } else {
	   $Resources_Summary = round($student_completions/$requireds*100) . "%";
   }
   $data = str_replace("{Resources_Summary}",$Resources_Summary,$data); // could be left out?
   // same for Required_Resources_to_View
   $data = str_replace("{Required_Resources_to_View}",$Resources_Summary,$data);
   
   $data .= '<tr><td><b><u>Assignment submissions:</u></b></td><td id = "assignments_overall" class="overall">{Assignments_Summary}</td></tr>';
   
   $data .= '<tr><td colspan="2">';   
   
   $student_flag = $MODE == "Student"?getUserId():NULL;
   $assignments = get_course_assignment_submission_data($courseid,$student_flag);
   $student_completions = 0;

   $data .= '<table class="block-analytics-progress">';
   foreach ($assignments as $assignment) {       
      if ($MODE == "Staff") {
          $indicator = "";
          $title = "";
          $indicatorclass = "";
      }
      else {
          if ($assignment['your_status'] == "submitted") {
              $indicator = $indicator_done;
              $title = "You are 1 of ".$assignment['percentage_submitted'] . "% of students who have submitted this assignment";
              $indicatorclass = "done";
			  $student_completions++;
          }
          else if ($assignment['your_status'] == "draft") {
              $indicator = $indicator_halfdone;			  
              $title = $assignment['percentage_submitted'] . '%'." of students have submitted this assignment, and you have drafted this assignment";
              $indicatorclass = "halfdone";
			  $student_completions += 0.5; //?
          }
          else {
              $indicator = $indicator_notdone;
              $title = "You have not yet done this assignment.";
              $title = $assignment['percentage_submitted'].'% of students have completed this assignment';			  
			  if ($assignment['percentage_submitted'] > 0) $title .= ' but not you';			  
              $indicatorclass = "notdone"; 
          }                  
      }       
              
     $data .= '<tr title = "'.$title.'">';
     $data .= '<td class="indicator '.$indicatorclass.'">';
      $data .= $indicator;
      $data .= '</td>';
      $data .= '<td class="activity">';              
      $data .= '<a href="'.$assignment['href'].'" title = "'.$title.'">';
     $data .= $assignment['title'];
      $data .= '</a>';                          
      $data .= '</td>';
     $data .= '</tr>';       
   }
   
	// overall summary
	if ($MODE == "Staff") {
		$Assignments_Summary = "";
	} 
	else {
		$Assignments_Summary = round($student_completions/count($assignments)*100) . "%";
	}
   $data = str_replace("{Assignments_Summary}",$Assignments_Summary,$data);	   
   
   $data .= '</table>';

   $data .= '</td></tr>';   

   $data .= "</table>";
   $data .= "<input type='hidden' name='view' value='ProgressTracker' />";
   $data .= "<div style='margin-top:25px'><input type='submit' name='submit' value='More' /></div>";
   $data .= "</form>";
   
   // include and activate jquery datatables (for main content but included here so after jquery loaded)
   if (!defined(JQUERY_DATATABLES_INCLUDED) || (defined(JQUERY_DATATABLES_INCLUDED) && JQUERY_DATATABLES_INCLUDED)) {
       $data .= '<script src="//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.js"></script>';
       $data .= '<style type="text/css">@import url("//ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css");</style>';
   }
   $data .= '<style type="text/css">
                .dataTables_length { float: right; opacity: 0.75; margin-top: -1em; }
                .dataTables_info { opacity: 0.75; margin-top: 0.25em; }
             </style>';   
   $data .= "<script>
   window.onload = function() {
       $(document).ready(function() { 
           $('.datatable').dataTable( {
                'iDisplayLength': ".DISPLAY_LIMIT_MAIN.", 
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

function display_assignment_submissions_student_graph($data)
{
   //echo '<h4>'.'Assignment submissions student graph'.'</h4>';
   echo '<noscript>Requires JavaScript.</noscript>';    
   
   echo '
   <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawAssignmentSubmissionsStudentGraph);
      function drawAssignmentSubmissionsStudentGraph() {
        var data = google.visualization.arrayToDataTable([
          ["Assignment", "Percentage of students submitted","Your progress"],
          ';
          foreach ($data as $c => $row) {
              echo '['.'"'.$row['assignment'].'"'.','.$row['percentage'].','.$row['student_percent'].']'.',';
              echo "\n";
          }
          echo '
        ]);

        var options = {
          title: "Assignment submissions",
          hAxis: {title: "Assignment"},
          seriesType: "bars",
          series: {0: {type: "bars", color: "green"}, 1: {type: "bars", color: "blue"}},
          vAxis: {ticks: [0,25,50,75,100], title: "%"} 
        };

        var chart = new google.visualization.ColumnChart(document.getElementById("assignment_submissions_student_graph_div"));
        chart.draw(data, options);
      }
        </script>
    <div id="assignment_submissions_student_graph_div" class="chart" style="width: 100%; height: auto; min-height: 300px"></div>    
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
   // some cols hidden by css
   echo '<table id = "table_recent" class = "datatable format_table">';
   echo '<thead>';   
   echo '<tr><th class="dev">Id</th><th class="activity">Activity</th>';
   echo '<th class="dev">Index</th>';   
   echo $MODE == "Staff"?'<th class="student">Student</th>':'';
   echo '<th class="acccesses">Student accesses</th>';
   echo '<th class="percentage">Percentage accessed</th>';   
   echo $MODE == "Student"?'<th class="accessed">Accessed?</th>':'';
   echo '</tr>';
   echo '</thead>';      

   echo '<tbody>';
   $c = 0;
   foreach($recent_modules as $moduleid=>$recent_details)
   {
       $data[$c]['name'] = $recent_details['activity'];
      echo '<tr>';
      echo '<td class="dev">'.$moduleid.'</td>';
      echo '<td class="activity">';
      echo '<a href="'.$recent_details['href'].'" title = "'.$recent_details['activity'].'">';      
      echo '<img src = "'.$OUTPUT->pix_url('icon', $recent_details['modname']).'" alt = "'.$recent_details['modname'].'" title = "'.$recent_details['modname'].'"/>'.'&nbsp;'; 
      echo $recent_details['activity'];
      echo '</a>';
      echo '</td>';
      echo '<td class="dev">'.$recent_details['index'].'</td>';      
      if ($MODE == "Staff") echo '<td class="student">' . $recent_details['student'] . '</td>';
      $recent_details['accesses'] = get_course_module_accesses($courseid, $moduleid); // default parameters used for these table headings/details
      echo '<td class="accesses">' . $recent_details['accesses'] . '</td>';
      $percentage = round($recent_details['accesses']/$num_students*100);
       $data[$c]['percentage'] = $percentage;
      echo '<td class="percentage" title = "Most recent access: '. $recent_details['datetime'].'">' . $percentage . '</td>';
      $accessed = user_has_accessed_module($courseid, $moduleid, getUserId());
      $data[$c]['accessed'] = $accessed;
      if ($MODE == "Student") echo '<td class="accessed">' . ($accessed?"Yes":"No") . '</td>'; 
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
   if ($MODE == "Student") echo '<th>Completed?</th>';
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
          
   echo '<br/><br/>' . "\n";
   
   echo '<h3>' . 'Assignment submissions' . '</h3>' . "\n";

   $student_flag = $MODE == "Student"?getUserId():NULL;
   $assignments = get_course_assignment_submission_data($courseid,$student_flag);
   
   $data = array(); // data for chart
   // make table, also computing chart data
   $include_fields = array('title','submissions'/*,'drafts','percentage_drafted'*/,'percentage_submitted','your_status');   
   echo '<table id = "table_assignments" class = "datatable format_table">';
   echo '<thead>';   
   echo '<tr>';
   foreach ($assignments[0] as $heading => $_) {
       if (!in_array($heading,$include_fields)) continue;
       echo '<th>'.(ucfirst(str_replace('_',' ',$heading))).'</th>';       
   }
   echo '</thead>';   
   echo '</tr>';
   echo '<tbody>';
   $c = 0; 
   foreach ($assignments as $assignment) {
       
       // store chart data
       $data[$c] = array();
       $data[$c]['assignment'] = $assignments[$c]['title'];
       $data[$c]['percentage'] = $assignments[$c]['percentage_submitted'];       
       if ($MODE == "Student") {
        if ($assignments[$c]['your_status']=="submitted") $data[$c]['student_percent'] = 100;
        else if ($assignments[$c]['your_status']=="draft") $data[$c]['student_percent'] = 50;
        else $data[$c]['student_percent'] = 0;
       }
       
       echo '<tr>';
       // output fields, adding icon and links to title       
       foreach ($assignment as $field => $val) {
           if (!in_array($field,$include_fields)) continue;
           echo '<td>';
           if ($field == "title") {
               echo '<a href="'.$assignments[$c]['href'].'" title = "'.$assignments[$c]['title'].'">';         
               echo '<img src = "'.$OUTPUT->pix_url('icon', 'assign').'" alt = "" title = "Assignment"/>'.'&nbsp;';
           }           
           echo ucfirst($val);
           if ($field == "title") {
               echo '</a>';
           }
           echo '</td>';
       }
       $c++;
       echo '</tr>';       
   }
   echo '</tbody>';
   echo '</table>';
   
   if ($MODE == "Student") {
       display_assignment_submissions_student_graph($data);
   }
   else if ($MODE == "Staff") {
       ; // TODO
   }   
   
   echo '<p style="margin-top: 1.6em; text-align: center;"><small>Note: These progress analytics reflect <em>current</em> students only.</small></p>';
   
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
   $htmlTable = '<table width="100%" cellpadding="3" cellspacing="0" class = "format_table">';
   $htmlTable = $htmlTable . "	<tr>
		            <th class='thTitle'></th>
		            <th class='thTitle'>Enrolled:</th>
		            <th class='thTitle'>Who have already viewed this course:</th>
		            <th class='thTitle'>Who have not yet viewed this course:</th>
	            </tr>
	            <tr>
		            <th class='thTitle'>Number of student users:</th>
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
   
   return;
}

