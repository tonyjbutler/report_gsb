<?php
// This file is part of GSB module for Moodle - http://moodle.org/
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
 * Version info for GSB Report
 *
 * @package    report
 * @subpackage GSB
 * @copyright  2012 onwards Richard Havinga richard.havinga@southampton-city.ac.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
require_once(dirname(__FILE__) . '/../../config.php');
require_once (dirname(__FILE__) . '/../../lib/adminlib.php');
require_once(dirname(__FILE__).'/lib.php');

require_login();


$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('report/gsb:viewmygsbreport', $context);

global $DB, $CFG;

admin_externalpage_setup('report_gsb');

echo $OUTPUT->header().
     $OUTPUT->heading(get_string('gsbadmin', 'report_gsb'));

$config = get_config('gsb'); 

 if(isset($_POST['categoryid'])) {
	$submitted = $_POST['submit2'];
	$course = $_POST['course'];
	
	$categoryid = $_POST['categoryid'];
}

//Clean up course medals where a course has been deleted
gsb_cleanup_medals();
	
	
//Remove any previously awarded Sub category medals if sub categories are disabled. 
if($config->subcategories == 0) {

	gsb_subcat_cleanup();

}
	
	//Once medals are set from gsb_by_department.php
if (ISSET($submitted)) {

	$sql = "SELECT c.id, g.gsboverride 
	FROM {course}, {block_gsb_content} g JOIN {course} c ON c.id = g.ids 
	WHERE {course}.category = " . $categoryid . " ";
	$get_dept_codes = $DB->get_records_sql($sql);


	foreach($get_dept_codes as $row => $values) {

		$courseid = $values->id;
		
		

		$gsboverride = $values->gsboverride;	
		//loop through and update gsb status for courses

		if (isset($_POST['gsb'][$courseid]['gsbid'])) {
			$gsbid = $_POST['gsb'][$courseid]['gsbid'];
			//auto calculated
			$current = $_POST['gsb'][$courseid]['current'];
			//override value
			$override = $_POST['gsb'][$courseid]['override'];
			//current override
			$currentover = $_POST['gsb'][$courseid]['currentover'];
		} else {
			$gsboverride = "";
			$current = "";
			$override = "";
		}
		if ($override !=""){
		$currentover = "";
		}

		if ($gsboverride != "" && $override != $current && $currentover != "yes") {
		
		if(!isset($updgsb)){
			$updgsb = new stdClass;
		}
			switch ($override) {
				case "":
				case "remove":
					$finalgsb = $current;
					$updgsb->id = $gsbid;
					$updgsb->gsb = $finalgsb;
					$updgsb->gsboverride = 'no';
					if ($DB->record_exists('block_gsb_content', array('id' => $updgsb->id))) {
						$DB->update_record('block_gsb_content', $updgsb); 
					} 
					break;
				
				case "In Dev":
					$finalgsb = ""; 
					$updgsb->id = $gsbid;
					$updgsb->gsb = $finalgsb;
					$updgsb->gsboverride = 'yes';
					if ($DB->record_exists('block_gsb_content', array('id' => $updgsb->id))) {
						$DB->update_record('block_gsb_content', $updgsb); 
					} 
					break;
				
				default: 
					$finalgsb = $override;
					$updgsb->id = $gsbid;
					$updgsb->gsb = $finalgsb;
					$updgsb->gsboverride = 'yes';
					if ($DB->record_exists('block_gsb_content', array('id' => $updgsb->id))) {
						$DB->update_record('block_gsb_content', $updgsb); 
					} 
					break;
			}
		
		}
	}
	

	echo "<h3><font face='Arial' color='#FF0000'>Gsb medals have been processed for </font><font face='Arial' color='#0000FF'>$course</font></h3>";
	echo "<a href='javascript:location.reload(true)'>Refresh this page</a>";

}
		

if($config->subcategories == '1') {
	$sql = "SELECT id, name FROM {course_categories} ORDER BY name";
} else {
	$sql = "SELECT id, name FROM {course_categories} WHERE depth = '1' ORDER BY name";
}	

$params = array(1);
$get_dept_codes = $DB->get_records_sql_menu($sql,$params);

echo "<form method='POST' action='$CFG->wwwroot/report/gsb/gsb_by_department.php'><p>";
echo "<select size='1' name='dept'>";

//loop through and list department names in drop down box
foreach ($get_dept_codes as $record => $value) {
			
	$catid = $record;
	$catname = $value;
	echo "<option name='category' value=$catid>$catname</option>";
	echo "<br />";
}

echo "</select></p><p><input type='submit' value='Submit' name='submit'></p></form>";
$enrolments = $config->minenrolments;
	
if($config->subcategories == '1') {
	
	$totalcourses = $DB->get_records_sql("SELECT {course}.id, {role_assignments}.roleid, Count({role_assignments}.roleid) AS studentsenrolled
										FROM {user} INNER JOIN (({role_assignments} INNER JOIN {context} ON {role_assignments}.contextid = {context}.id) INNER JOIN ({course} INNER JOIN {course_categories} ON {course}.category = {course_categories}.id) ON {context}.instanceid = {course}.id) ON {user}.id = {role_assignments}.userid
										GROUP BY {role_assignments}.roleid, {course}.id
										HAVING ((({role_assignments}.roleid)=5) AND ((Count({role_assignments}.roleid))>= $enrolments))
										ORDER BY Count({role_assignments}.roleid)");	
} else {

	$totalcourses = $DB->get_records_sql("SELECT {course}.id, {role_assignments}.roleid, Count({role_assignments}.roleid) AS studentsenrolled
										FROM {user} INNER JOIN (({role_assignments} INNER JOIN {context} ON {role_assignments}.contextid = {context}.id) INNER JOIN ({course} INNER JOIN {course_categories} ON {course}.category = {course_categories}.id) ON {context}.instanceid = {course}.id) ON {user}.id = {role_assignments}.userid
										WHERE ((({course_categories}.depth)=1))
										GROUP BY {role_assignments}.roleid, {course}.id
										HAVING ((({role_assignments}.roleid)=5) AND ((Count({role_assignments}.roleid))>= $enrolments))
										ORDER BY Count({role_assignments}.roleid)");
								 						 
}							 

$total = count($totalcourses);

$gold_total = $DB->count_records('block_gsb_content', array('gsb'=>'Gold'));
$silver_total = $DB->count_records('block_gsb_content', array('gsb'=>'Silver'));
$bronze_total = $DB->count_records('block_gsb_content', array('gsb'=>'Bronze'));		

$total_medals = $gold_total + $silver_total + $bronze_total;				
$indev_count = $total - $total_medals ;

if($total_medals >0) {

	$gold_perc = $gold_total / $total * 100;
	$gold_perc_form = sprintf ('%01.1f', $gold_perc);

	$silver_perc = $silver_total / $total *100;
	$silver_perc_form = sprintf ('%01.1f', $silver_perc);

	$bronze_perc = $bronze_total / $total *100;
	$bronze_perc_form = sprintf ('%01.1f', $bronze_perc);

	$indev_perc = $indev_count / $total *100;
	$indev_perc_form = sprintf ('%01.1f', $indev_perc);
	
} else {

	$gold_perc_form = 0;
	$silver_perc_form = 0;
	$bronze_perc_form = 0;
	$indev_perc_form = 0;
	
}
		
$total = $gold_total + $silver_total + $bronze_total + $indev_count;

$table = "<div align='center'>
			<table style='text-align: left; width: 20%;' border='0' cellpadding='2' cellspacing='2'>
				<tbody>
					<tr>
					<td style='width: 100%;'>
						<div align='center'>
						  <table width='100%' border='0' cellspacing='0' cellpadding='0'>
							<tr>
							  <td width='100%' colspan='4'><font face='Arial'><h3 align='center'>GSB Summary</h3></font></td>
							</tr>
							<tr>
							  <td width='30%'><div align='left'><img src='$CFG->wwwroot/report/gsb/pics/gold_icon.png'></div></td>
							  <td width='35%'><font face='Arial' size='2'><div align='left'>Gold</div></td>
							  <td width='25%'><font face='Arial' size='2'><div align='left'>" . $gold_total . "</div></td>
							  <td width='10%'><div align='left'><font face='Arial' size='1'>" . $gold_perc_form . "%</div></td>
							</tr>
							<tr>
							  <td width='30%'><div align='left'><img src='$CFG->wwwroot/report/gsb/pics/silver_icon.png'></div></td>
							  <td width='35%'><font face='Arial' size='2'><div align='left'>Silver</div></td>
							  <td width='25%'><font face='Arial' size='2'><div align='left'>" . $silver_total . "</div></td>
							  <td width='10%'><div align='left'><font face='Arial' size='1'>" . $silver_perc_form . "%</div></td>
							</tr>
							<tr>
							  <td width='30%'><div align='left'><img src='$CFG->wwwroot/report/gsb/pics/bronze_icon.png'></div></td>
							  <td width='35%'><font face='Arial' size='2'><div align='left'>Bronze</div></td>
							  <td width='25%'><font face='Arial' size='2'><div align='left'>" . $bronze_total . "</div></td>
							  <td width='10%'><div align='left'><font face='Arial' size='1'>" . $bronze_perc_form . "%</div></td>
							</tr>
							<tr>
							  <td width='30%'>&nbsp;</td>
							  <td width='35%'><font face='Arial' size='2'><div align='left'>In Dev</div></td>
							  <td width='25%'><font face='Arial' size='2'><div align='left'>" . $indev_count . "</div></td>
							  <td width='10%'><div align='left'><font face='Arial' size='1'>" . $indev_perc_form . "%</div></td>
							</tr>
								  </table>
						  <hr>
						<table width='100%' border='0' cellpadding='0' cellspacing='0'>
							<tr>
							  <td width='30%'><div align='left'>&nbsp;</div></td>
							  <td width='35%'><font face='Arial' size='2'><div align='left'>Total Courses</div></td>
							  <td width='25%'><font face='Arial' size='2'><div align='left'>" . $total . "</div></td>
							  <td width='10%'><div align='left'>&nbsp;</div></td>
							</tr>
						</table>
						</div>
					</td>
				</tr>
			</tbody>
			</table>
		</div>";

echo $table;
			
$downloadgsb = "<p><font face='Arial'><a href='$CFG->wwwroot/report/gsb/download_gsb.php'>Download GSB Report to Excel</a></font></p>";
echo $downloadgsb;
echo "<p><font face='Arial' size='1'><br/>Note: you should process the GSB Medals for each department before attempting to download the GSB Report</a></font></p>";
echo $OUTPUT->footer();

	