<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
//
// Returns
// -------
//
function ciniki_surveys_surveyGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'survey_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Survey'),
		'questions'=>array('required'=>'no', 'name'=>'Questions'),
		'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stats'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.surveyGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	//
	// Get the main information
	//
	if( isset($args['questions']) && $args['questions'] == 'yes' ) {
		$strsql = "SELECT "
			. "ciniki_surveys.id, ciniki_surveys.status, ciniki_surveys.status AS status_text, "
			. "ciniki_surveys.name, "
			. "ciniki_surveys.instructions, "
			. "IFNULL(DATE_FORMAT(CONVERT_TZ(date_expires, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_expires, "
			. "ciniki_survey_questions.id AS question_id, "
			. "ciniki_survey_questions.qnumber, "
			. "ciniki_survey_questions.qtype, "
			. "ciniki_survey_questions.question "
			. "FROM ciniki_surveys "
			. "LEFT JOIN ciniki_survey_questions ON (ciniki_surveys.id = ciniki_survey_questions.survey_id "
				. "AND ciniki_survey_questions.status = 10 "
				. "AND ciniki_survey_questions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "WHERE ciniki_surveys.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_surveys.id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
			. "ORDER BY ciniki_surveys.id ASC, ciniki_survey_questions.qnumber "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
			array('container'=>'surveys', 'fname'=>'id', 'name'=>'survey',
				'fields'=>array('id', 'name', 'status', 'status_text', 'instructions', 'date_expires'),
				'maps'=>array(
					'status_text'=>array('5'=>'Creating', '10'=>'Active', '40'=>'Closed', '60'=>'Deleted'),
					),
				),
			array('container'=>'questions', 'fname'=>'question_id', 'name'=>'question',
				'fields'=>array('id'=>'question_id', 'number'=>'qnumber', 'type'=>'qtype', 'question'=>'question')),
			));
	} else {
		$strsql = "SELECT "
			. "ciniki_surveys.id, ciniki_surveys.status, ciniki_surveys.status AS status_text, "
			. "ciniki_surveys.name, instructions, "
			. "IFNULL(DATE_FORMAT(CONVERT_TZ(date_expires, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_expires "
			. "FROM ciniki_surveys "
			. "WHERE ciniki_surveys.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_surveys.id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
			. "ORDER BY ciniki_surveys.id ASC ";

		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
			array('container'=>'surveys', 'fname'=>'id', 'name'=>'survey',
				'fields'=>array('id', 'name', 'status', 'status_text', 'instructions', 'date_expires'),
				'maps'=>array(
					'status_text'=>array('5'=>'Creating', '10'=>'Active', '40'=>'Closed', '60'=>'Deleted'),
					),
				),
			));
	}
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['surveys']) && !isset($rc['surveys'][0]['survey']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1057', 'msg'=>'Unable to find survey'));
	}

	$survey = $rc['surveys'][0]['survey'];

	//
	// Check if we should return the stats for this survey
	//
	if( isset($args['stats']) && $args['stats'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
		$question['stats'] = array();
		$strsql = "SELECT status, COUNT(ciniki_survey_invites.id) "
			. "FROM ciniki_survey_invites "
			. "WHERE ciniki_survey_invites.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_survey_invites.survey_id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
			. "GROUP BY status "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.surveys', 'stats');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['stats']) ) {
			$survey['stats']['total_invites'] = 0;
			$survey['stats']['invites_sent'] = 0;
			$survey['stats']['invites_seen'] = 0;
			$survey['stats']['invites_answered'] = 0;
			$survey['stats']['invites_unknown'] = 0;
			foreach($rc['stats'] as $status => $num) {
				$survey['stats']['total_invites'] += $num;
				if( $status == '10' ) { 
					$survey['stats']['invites_sent'] += $num; 
				} else if( $status == '20' ) { 
					$survey['stats']['invites_sent'] += $num; 
					$survey['stats']['invites_seen'] += $num; 
				} else if( $status == '30' ) { 
					$survey['stats']['invites_sent'] += $num; 
					$survey['stats']['invites_seen'] += $num; 
					$survey['stats']['invites_answered'] += $num; 
				} else { $survey['stats']['invites_unknown'] += $num; }
			}
		} else {
			
			$survey['stats']['answer_count'] = 0;
		}
	}

	return array('stat'=>'ok', 'survey'=>$survey);
}
?>
