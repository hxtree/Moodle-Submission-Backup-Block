<?php
/**
 * This file is part of the Moodle Submission Backup Block.
 *
 * (c) Matthew Heroux <matthewheroux@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class block_submission_backup extends block_base {
	public function init() {
		$this->title = get_string('pluginname', 'block_submission_backup');
	}

	public function get_content() {
		global $DB;
		global $USER;
		global $CFG;

		if ($this->content !== null) {
			return $this->content;
		}

		$results = $DB->get_records_sql("
			SELECT
				`mdl_assign_submission`.`id` AS `submission_id`,
				`name`,
				`mdl_assign`.`id` AS `assignment_id`,
				`course` AS `course_id`,
				`userid` AS `user_id`,
				`mdl_course`.`fullname` AS `course_name`
			FROM mdl_user
			LEFT JOIN mdl_assign_submission ON mdl_user.id = mdl_assign_submission.userid
			LEFT JOIN mdl_assign ON mdl_assign_submission.assignment = mdl_assign.id
			LEFT JOIN `mdl_course` ON `mdl_assign`.`course` = `mdl_course`.`id`
			WHERE mdl_user.username = :username
			AND `status` = 'submitted'", 
			['username'=>$USER->username]
		);

		$this->content =  new stdClass;

		foreach($results as $result){

			// locate information to find the files
			$submission = $DB->get_record('assign_submission', array('assignment'=>$result->assignment_id, 'userid'=>$USER->id));
			$course_module = get_coursemodule_from_instance('assign',$result->assignment_id, $course->id) ;
			$context = get_context_instance(CONTEXT_MODULE, $course_module->id);

			// get files
			$fs = get_file_storage();
			$files = $fs->get_area_files($context->id, 'assignsubmission_file', 'submission_files', $result->submission_id);

			foreach ($files as $file) {
				$filename = $file->get_filename();
				if($filename === '.'){
				    continue;
				}
				$url = moodle_url::make_pluginfile_url(
				    $file->get_contextid(), 
				    $file->get_component(), 
				    $file->get_filearea(), 
				    $file->get_itemid(), 
				    $file->get_filepath(), 
				    $file->get_filename(), 
				    false
				);

				if($last_course != $result->course_name){

					if(isset($last_course)){
						$this->content->text .= html_writer::end_tag('ul');
					}

					$last_course = $result->course_name;

					$this->content->text .= html_writer::start_tag('b');
					$this->content->text .= $result->course_name;
					$this->content->text .= html_writer::end_tag('b');			
				
					$this->content->text .= html_writer::start_tag('ul');
				}

				$this->content->text .= html_writer::start_tag('li');
				$this->content->text .= html_writer::link($url, $course_module->name);
				$this->content->text .= html_writer::end_tag('li');
			}
		}
		if(isset($last_course)){
			$this->content->text .= html_writer::end_tag('ul');
		}

		return $this->content;
	}
}
