<?php
//
// Description
// -----------
// This function will return the history for an element in the surveys.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to get the history for.
// survery_id:          The ID of the survey to get the history for.
// field:               The field to get the history for.
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_surveys_surveyHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'survey_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Survey'), 
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
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.surveyHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'date_expires' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
            $args['business_id'], 'ciniki_surveys', $args['survey_id'], $args['field'], 'utcdate');
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
        $args['business_id'], 'ciniki_surveys', $args['survey_id'], $args['field']);
}
?>
