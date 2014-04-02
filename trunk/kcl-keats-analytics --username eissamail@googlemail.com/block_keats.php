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

// Obviously required
require_once($CFG->dirroot . '/blocks/keats/lib.php');
class block_keats extends block_base
{

   function init()
   {
      $this->title = get_string('pluginname', 'block_keats');
   }

   function applicable_formats()
   {
      return array('all'=>true);
   }

   function instance_allow_multiple()
   {
      return false;
   }

   function get_content()
   {
      global $CFG, $DB, $PAGE;
      if($this->content !== NULL)
      {
         return $this->content;
      }
      $htmlScript = '<link rel="stylesheet" href="../jquery/themes/base/jquery.ui.all.css">
	                 <script src="../jquery/jquery-1.9.1.js"></script>
				     <script src="../jquery/ui/jquery.ui.core.js"></script>
				     <script src="../jquery/ui/jquery.ui.widget.js"></script>
				     <script src="../jquery/ui/jquery.ui.accordion.js"></script>
				     <link rel="stylesheet" href="../jquery/demos/demos.css">
                     <script>
                            $(document).ready(function() {
	                                    var a =0;
	                                    if(location.href.indexOf("#tab1") > -1) { a = 0 };
	                                    if(location.href.indexOf("#tab2") > -1) { a = 1 };
	                                    if(location.href.indexOf("#tab3") > -1) { a = 2 };
	                                    if(location.href.indexOf("#tab4") > -1) { a = 3 };
	                                    if(location.href.indexOf("#tab5") > -1) { a = 4 };';

      $htmlScript = $htmlScript . "$('#accordion').accordion({
                                                             collapsible : true,";
      $htmlScript = $htmlScript . '                          heightStyle: "content",';
      $htmlScript = $htmlScript . "                          active : false
                                                             });
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
      $this->content = new stdClass();
      $this->content->footer = '';
      $this->content->text = '';
      if(empty($this->instance))
      {
         return $this->content;
      }
      list($context, $course, $cm) = get_context_info_array($PAGE->context->id);

      $this->content = new stdClass();

      $this->content->text = display_tabs($course->id, $htmlScript);
      $this->content->footer = '';
      return $this->content;
   }
}