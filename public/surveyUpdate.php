<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to update the survey for.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_surveys_surveyUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'survey_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Survey'),
        'name'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('5','10','40','60'), 'name'=>'Status'),
        'instructions'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Instructions'),
        'date_expires'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetime', 'name'=>'Expires'),
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
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.surveyUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistoryReformat');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.surveys');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add all the fields to the change log
    //
    $strsql = "UPDATE ciniki_surveys SET last_updated = UTC_TIMESTAMP()";

    $changelog_fields = array(
        'name',
        'status',
        'instructions',
        'date_expires',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            if( $field == 'date_expires' ) {
                $strsql .= ", $field = CONVERT_TZ('" . ciniki_core_dbQuote($ciniki, $args[$field]) . "', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "', '+00:00') ";
                $rc = ciniki_core_dbAddModuleHistoryReformat($ciniki, 'ciniki.surveys', 
                    'ciniki_survey_history', $args['business_id'], 
                    2, 'ciniki_surveys', $args['survey_id'], $field, $args[$field], 'utcdate');
            } else {
                $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
                $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
                    'ciniki_survey_history', $args['business_id'], 
                    2, 'ciniki_surveys', $args['survey_id'], $field, $args[$field]);
            }
        }
    }
    $strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.surveys.11', 'msg'=>'Unable to update survey'));  
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

    $ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.survey', 
        'args'=>array('id'=>$args['survey_id']));

    return array('stat'=>'ok');
}
?>
