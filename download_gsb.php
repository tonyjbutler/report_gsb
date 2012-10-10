<?php

//Written by Richard Havinga @ Southampton City College

require_once(dirname(__FILE__) . '/../../config.php');
require_once (dirname(__FILE__) . '/../../lib/adminlib.php');

defined('MOODLE_INTERNAL') || die;

require_login();


$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('report/gsb:viewmygsbreport', $context);


function objectToArray($select) {
		if (is_object($select)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$select = get_object_vars($select);
		}
 
		if (is_array($select)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map(__FUNCTION__, $select);
		}
		else {
			// Return array
			return $select;
		}
	}
	
$sql = "SELECT {block_gsb_content}.id, {course_categories}.name as category, {course}.fullname as coursename, {course}.shortname, {block_gsb_content}.linksnum, {block_gsb_content}.assignmentnum, 
		{block_gsb_content}.feedbacknum, {block_gsb_content}.questnum, {block_gsb_content}.quiznum, {block_gsb_content}.interactnum as interactive_learning_objects, {block_gsb_content}.embednum as embedded_videos, 
		{block_gsb_content}.booknum, {block_gsb_content}.databasenum, {block_gsb_content}.workshopnum, {block_gsb_content}.choicenum, {block_gsb_content}.glossarynum, 
		{block_gsb_content}.wikinum, {block_gsb_content}.chatnum, {block_gsb_content}.forumnum, {block_gsb_content}.gsb 
		FROM ({block_gsb_content} INNER JOIN {course} ON {block_gsb_content}.ids = {course}.id) INNER JOIN {course_categories} ON {course}.category = {course_categories}.id
		GROUP BY {block_gsb_content}.id
		ORDER by name ASC;"; 
$select = $DB->get_records_sql($sql);

$array = objectToArray($select);	

header("Content-type: application/octet-stream"); 
header("Content-Disposition: attachment; filename=gsb_report.xls"); 
$flag = false; foreach($array as $row) { if(!$flag) {
 // display field/column names as first row 
 echo implode("\t", array_keys($row)) . "\r\n"; $flag = true; } echo implode("\t", array_values($row)) . "\r\n"; } exit;

