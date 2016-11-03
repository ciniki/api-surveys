<?php
//
// Description
// ===========
// This method will remore a question from a survey.  If the survey is in the status 5, it will remove
// the question from the database.  If the question is active, then it will mark the question as deleted.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to remove the question from.
// question_id:         The ID of the question to remove.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_surveys_questionDelete(&$ciniki) {
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
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.questionDelete'); 
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.surveys');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the uuid of the survey question to be deleted
    //
    $strsql = "SELECT ciniki_survey_questions.uuid, ciniki_survey_questions.survey_id, "
        . "ciniki_surveys.status "
        . "FROM ciniki_survey_questions "
        . "LEFT JOIN ciniki_surveys ON (ciniki_survey_questions.survey_id = ciniki_surveys.id "
            . "AND ciniki_surveys.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
        . "WHERE ciniki_survey_questions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_survey_questions.id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.surveys', 'question');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['question']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.surveys.5', 'msg'=>'Unable to find question'));
    }
    $uuid = $rc['question']['uuid'];
    $survey_id = $rc['question']['survey_id'];
    if( isset($rc['question']['status']) ) {
        $status = $rc['question']['status'];
    } else {
        $status = '5';
    }

    $db_updated = 0;
    if( $status == '5' ) {
        //
        // Survey has not been published, we are safe to delete question
        //
        $strsql = "DELETE FROM ciniki_survey_questions "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.surveys');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
            return $rc;
        }
        if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.surveys.6', 'msg'=>'Unable to delete question'));
        }

        $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
            $args['business_id'], 3, 'ciniki_survey_questions', $args['question_id'], '*', '');
        $ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
            'args'=>array('delete_uuid'=>$uuid, 'delete_id'=>$args['question_id']));
        $db_updated = 1;
    } elseif( $status != '60' ) {
        //
        // Survey has been activated/published, we can only disable questions now
        //
        $strsql = "UPDATE ciniki_survey_questions SET status = 60, last_updated = UTC_TIMESTAMP() "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
            return $rc;
        }
        $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
            $args['business_id'], 2, 'ciniki_survey_questions', $args['question_id'], 'status', '60');
        $ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
            'args'=>array('id'=>$args['question_id']));
        $db_updated = 1;
    }

    // 
    // Update the question numbers
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'updateQuestionNumbers');
    $rc = ciniki_surveys_updateQuestionNumbers($ciniki, $args['business_id'], $survey_id, 0, 0, -1);
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
    if( $db_updated > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
        ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'surveys');
    }

    return array('stat'=>'ok');
}
?>
