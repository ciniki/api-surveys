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
	// Add the question
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.surveys.question', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	$question_id = $rc['id'];

	// 
	// Update the question numbers
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'updateQuestionNumbers');
	$rc = ciniki_surveys_updateQuestionNumbers($ciniki, $args['business_id'], $args['survey_id'], $question_id, $args['qnumber'], -1);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
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

	return array('stat'=>'ok', 'id'=>$question_id);
}
?>
