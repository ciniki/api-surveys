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
// tnid:         The ID of the tenant to get the history for.
// survery_id:          The ID of the question to get the history for.
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
function ciniki_surveys_questionHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'question_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Question'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $rc = ciniki_surveys_checkAccess($ciniki, $args['tnid'], 'ciniki.surveys.questionHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    if( $args['field'] == 'number' ) {
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
            $args['tnid'], 'ciniki_survey_questions', $args['question_id'], 'qnumber');
    } elseif( $args['field'] == 'type' ) {
        return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
            $args['tnid'], 'ciniki_survey_questions', $args['question_id'], 'qtype');
    }
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', 
        $args['tnid'], 'ciniki_survey_questions', $args['question_id'], $args['field']);
}
?>
