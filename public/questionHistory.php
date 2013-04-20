<?php
//
// Description
// -----------
// This function will return the history for an question in the surveys.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the history for.
// survery_id:			The ID of the question to get the history for.
// field:				The field to get the history for.
//
// Returns
// -------
//	<history>
//		<action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//		...
//	</history>
//	<users>
//		<user id="1" name="users.display_name" />
//		...
//	</users>
//
function ciniki_surveys_questionHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'question_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Question'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
	$rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.questionHistory');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	if( $args['field'] == 'number' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
			$args['business_id'], 'ciniki_survey_questions', $args['question_id'], 'qnumber');
	} elseif( $args['field'] == 'type' ) {
		return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
			$args['business_id'], 'ciniki_survey_questions', $args['question_id'], 'qtype');
	}
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
		$args['business_id'], 'ciniki_survey_questions', $args['question_id'], $args['field']);
}
?>
