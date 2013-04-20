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
function ciniki_surveys_questionGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'question_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Question'),
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
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.questionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the main information
	//
	$strsql = "SELECT "
		. "ciniki_survey_questions.id, "
		. "ciniki_survey_questions.qnumber, "
		. "ciniki_survey_questions.qtype, "
		. "ciniki_survey_questions.status, "
		. "ciniki_survey_questions.question, "
		. "ciniki_survey_questions.option1, "
		. "ciniki_survey_questions.option2, "
		. "ciniki_survey_questions.option3, "
		. "ciniki_survey_questions.option4, "
		. "ciniki_survey_questions.option5 "
		. "FROM ciniki_survey_questions "
		. "WHERE ciniki_survey_questions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_survey_questions.id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
		. "ORDER BY ciniki_survey_questions.id ASC ";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.surveys', array(
		array('container'=>'questions', 'fname'=>'id', 'name'=>'question',
			'fields'=>array('id', 'number'=>'qnumber', 'status', 'type'=>'qtype', 'question'=>'question',
				'option1', 'option2', 'option3', 'option4', 'option5')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['questions']) && !isset($rc['questions'][0]['question']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1060', 'msg'=>'Unable to find question'));
	}
	
	return array('stat'=>'ok', 'question'=>$rc['questions'][0]['question']);
}
?>
