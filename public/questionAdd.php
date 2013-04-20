<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the survey to.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_surveys_questionAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'survey_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Survey'),
		'type'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('10'), 'name'=>'Type'),
		'status'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('10'), 'name'=>'Status'),
		'number'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Number'),
		'question'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Question'),
		'option1'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Option 1'),
		'option2'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Option 2'),
		'option3'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Option 3'),
		'option4'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Option 4'),
		'option5'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Option 5'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
	$args['qnumber'] = $args['number'];
	$args['qtype'] = $args['type'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.questionAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	$args['uuid'] = $rc['uuid'];

	//
	// Check if there is a question already with this number, and shift it and any others up one
	//
	$strsql = "SELECT id, qnumber AS number "
		. "FROM ciniki_survey_questions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
		. "ORDER BY qnumber "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.surveys', 'question');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	if( isset($rc['rows']) ) {
		$questions = $rc['rows'];
		$update = 0;
		$prev = 0;
		foreach($questions as $qid => $question) {
			//
			// Skip the current question
			//
			if( $question['id'] == $args['question_id'] ) {
				continue;
			}
			//
			// Decrease number by one to fill gap
			//
			if( $question['number'] == $old_number && $update <= 0 ) {
				$strsql = "UPDATE ciniki_survey_questions SET "
					. "qnumber = '" . ciniki_core_dbQuote($ciniki, $question['number']-1) . "' "
					. ", last_updated = UTC_TIMESTAMP() "
					. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
					. "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $survey_id) . "' "
					. "AND id = '" . ciniki_core_dbQuote($ciniki, $question['id']) . "' "
					. "";
				$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
				if( $rc['stat'] != 'ok' ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
				}
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
					'ciniki_survey_history', $args['business_id'], 
					2, 'ciniki_survey_questions', $question['id'], 'qnumber', $question['number']-1);
				$ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
					'args'=>array('id'=>$question['id']));
				$update = -1;
			}

			//
			// increase any numbers up one
			//
			if( $question['number'] == $args['number'] || $update == 1 ) {
				if( $update == 1 && $prev < ($question['number']-1) ) {
					break;
				} elseif( $update <= 0 ) {
					$strsql = "UPDATE ciniki_survey_questions SET "
						. "qnumber = '" . ciniki_core_dbQuote($ciniki, $question['number']-1) . "' "
						. ", last_updated = UTC_TIMESTAMP() "
						. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
						. "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $survey_id) . "' "
						. "AND id = '" . ciniki_core_dbQuote($ciniki, $question['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
					}
					ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
						'ciniki_survey_history', $args['business_id'], 
						2, 'ciniki_survey_questions', $question['id'], 'qnumber', $question['number']-1);
					$ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
						'args'=>array('id'=>$question['id']));
					$update = 1;
				} else {
					$strsql = "UPDATE ciniki_survey_questions SET "
						. "qnumber = '" . ciniki_core_dbQuote($ciniki, $question['number']+1) . "' "
						. ", last_updated = UTC_TIMESTAMP() "
						. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
						. "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
						. "AND id = '" . ciniki_core_dbQuote($ciniki, $question['id']) . "' "
						. "";
					$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
					if( $rc['stat'] != 'ok' ) {
						ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
					}
					ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
						'ciniki_survey_history', $args['business_id'], 
						2, 'ciniki_survey_questions', $question['id'], 'number', $question['number']+1);
					$ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
						'args'=>array('id'=>$question['id']));
					$update = 1;
				}
			}
			$prev = $question['number'];
		}
	}

	//
	// Check if question number should be reduced to be at end of list.
	//
	$strsql = "SELECT MAX(qnumber) AS maxnumber "
		. "FROM ciniki_survey_questions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
		. "GROUP BY survey_id "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.surveys', 'number');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	if( isset($rc['number']) && $rc['number']['maxnumber'] < ($args['number']-1) ) {
		$args['number'] = $rc['number']['maxnumber'] + 1;
		$args['qnumber'] = $args['number'];
	}

	//
	// Add the survey question to the database
	//
	$strsql = "INSERT INTO ciniki_survey_questions (uuid, business_id, survey_id, "
		. "status, qnumber, qtype, question, "
		. "option1, option2, option3, option4, option5, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['qnumber']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['qtype']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['question']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['option1']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['option2']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['option3']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['option4']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['option5']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1064', 'msg'=>'Unable to add survey question'));
	}
	$question_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'uuid',
		'survey_id',
		'status',
		'qnumber',
		'qtype',
		'question',
		'option1',
		'option2',
		'option3',
		'option4',
		'option5',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
				'ciniki_survey_history', $args['business_id'], 
				1, 'ciniki_survey_questions', $question_id, $field, $args[$field]);
		}
	}

	$ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
		'args'=>array('id'=>$question_id));

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'surveys');

	return array('stat'=>'ok', 'id'=>$question_id);
}
?>
