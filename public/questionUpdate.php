<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to update the survey for.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $rc = ciniki_surveys_checkAccess($ciniki, $args['tnid'], 'ciniki.surveys.questionUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the survey_id for existing question
    //
    $strsql = "SELECT survey_id, qnumber AS number "
        . "FROM ciniki_survey_questions "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.surveys', 'question');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['question']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.surveys.8', 'msg'=>'Unable to find survey question'));
    }
    $question = $rc['question'];
    $survey_id = $question['survey_id'];
    $old_number = $question['number'];

    if( isset($args['number']) && $args['number'] != '' ) {
        //
        // Check if question number should be reduced to be at end of list.
        //
        $strsql = "SELECT MAX(qnumber) AS maxnumber "
            . "FROM ciniki_survey_questions "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
            $args['number'] = $rc['number']['maxnumber']+1;
            $args['qnumber'] = $args['number'];
        }
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
    // Update the question
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.surveys.question', $args['question_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
        return $rc;
    }

    // 
    // Update the question numbers
    //
    if( isset($args['number']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'updateQuestionNumbers');
        $rc = ciniki_surveys_updateQuestionNumbers($ciniki, $args['tnid'], $survey_id, $args['question_id'], $args['qnumber'], $old_number);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
            return $rc;
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.surveys');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'surveys');

    return array('stat'=>'ok');
}
?>
