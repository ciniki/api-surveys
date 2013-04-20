<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to update the survey for.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_surveys_questionUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'question_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Question'),
		'type'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('10'), 'name'=>'Type'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('10'), 'name'=>'Status'),
		'number'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number'),
		'question'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Question'),
		'option1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Option 1'),
		'option2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Option 2'),
		'option3'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Option 3'),
		'option4'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Option 4'),
		'option5'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Option 5'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
	if( isset($args['type']) ) {
		$args['qtype'] = $args['type'];
	}
	if( isset($args['number']) ) {
		$args['qnumber'] = $args['number'];
	}

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.questionUpdate'); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Get the survey_id for existing question
	//
	$strsql = "SELECT survey_id, qnumber AS number "
		. "FROM ciniki_survey_questions "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.surveys', 'question');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['question']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1066', 'msg'=>'Unable to find survey question'));
	}
	$question = $rc['question'];
	$survey_id = $question['survey_id'];
	$old_number = $question['number'];

	if( isset($args['number']) && $args['number'] != '' ) {
		//
		// Check if there is a question already with this number, and shift it and any others up one
		//
		$strsql = "SELECT id, qnumber AS number "
			. "FROM ciniki_survey_questions "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $survey_id) . "' "
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
				// Set the question to decrease in number until we reach new number
				//
				if( $question['number'] == $old_number && $update <= 0 ) {
					$update = -1;
				}
				//
				// Skip the current question
				//
				if( $question['id'] == $args['question_id'] ) {
					continue;
				}
				//
				// Decrease by one
				//
				if( $question['number'] > 1 && $update < 0 ) {
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
				}
				//
				// increase any numbers up one
				//
				if( $question['number'] == $args['number'] || $update == 1) {
					if( $update == 1 && $prev < ($question['number']-1) ) {
						$update = 0;
						break;
					} elseif( $question['number'] > 1 && $update <= 0 ) {
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
						$update = 0;
					} else {
						$strsql = "UPDATE ciniki_survey_questions SET "
							. "qnumber = '" . ciniki_core_dbQuote($ciniki, $question['number']+1) . "' "
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
							2, 'ciniki_survey_questions', $question['id'], 'qnumber', $question['number']+1);
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
			. "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $survey_id) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' " 
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
	}

	//
	// Add all the fields to the change log
	//
	$strsql = "UPDATE ciniki_survey_questions SET last_updated = UTC_TIMESTAMP() ";

	$changelog_fields = array(
		'status',
		'qtype',
		'qnumber',
		'question',
		'option1',
		'option2',
		'option3',
		'option4',
		'option5',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
				'ciniki_survey_history', $args['business_id'], 
				2, 'ciniki_survey_questions', $args['question_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1067', 'msg'=>'Unable to update survey question'));	
	}

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

	$ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
		'args'=>array('id'=>$args['question_id']));

	return array('stat'=>'ok');
}
?>
