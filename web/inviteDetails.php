<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_surveys_web_inviteDetails(&$ciniki, $settings, $business_id, $permalink) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    date_default_timezone_set('UTC');
    $utc_datetime = strftime("%Y-%m-%d %H:%M:%S");

    //
    // Find the invite, and the survey from the permalink
    //
    $strsql = "SELECT ciniki_survey_invites.id AS invite_id, "
        . "ciniki_survey_invites.customer_id, "
        . "ciniki_surveys.id AS survey_id, "
        . "IF(ciniki_survey_invites.date_expires='0000-00-00 00:00:00', '999999', DATEDIFF(ciniki_survey_invites.date_expires, UTC_TIMESTAMP())) AS invite_expires_in, "
        . "IF(ciniki_surveys.date_expires='0000-00-00 00:00:00', '999999', DATEDIFF(ciniki_surveys.date_expires, UTC_TIMESTAMP())) AS survey_expires_in, "
        . "ciniki_survey_invites.status AS invite_status, "
        . "ciniki_surveys.status AS survey_status, "
        . "ciniki_surveys.name, "
        . "ciniki_surveys.instructions, "
        . "ciniki_survey_questions.id AS question_id, "
        . "ciniki_survey_questions.qnumber AS number, "
        . "ciniki_survey_questions.qtype AS type, "
        . "ciniki_survey_questions.question "
        . "FROM ciniki_survey_invites "
        . "LEFT JOIN ciniki_surveys ON (ciniki_survey_invites.survey_id = ciniki_surveys.id "
            . "AND ciniki_surveys.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "LEFT JOIN ciniki_survey_questions ON (ciniki_survey_invites.survey_id = ciniki_survey_questions.survey_id "
            . "AND ciniki_survey_questions.status = 10 "
            . "AND ciniki_survey_questions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "WHERE ciniki_survey_invites.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_survey_invites.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "ORDER BY ciniki_survey_invites.id, ciniki_survey_questions.qnumber "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.surveys', array(
        array('container'=>'surveys', 'fname'=>'survey_id', 'name'=>'survey',
            'fields'=>array('invite_id', 'survey_id', 'customer_id', 'name', 'instructions', 
                'invite_expires_in', 'expires_in'=>'survey_expires_in', 'invite_status', 'status'=>'survey_status')),
        array('container'=>'questions', 'fname'=>'question_id', 'name'=>'question',
            'fields'=>array('id'=>'question_id', 'number', 'type', 'question')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['surveys'][0]['survey']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.surveys.12', 'msg'=>'The survey does not exist'));
    }
    $survey = $rc['surveys'][0]['survey'];

    //
    // Check if the survey is still active, the invite is still active, and nothing has expired
    //
    if( $survey['invite_status'] >= 30 ) {
        return array('stat'=>'used', 'survey'=>$survey);
    }
    if( $survey['status'] > 10 ) {
        return array('stat'=>'closed', 'survey'=>$survey);
    }
    if( $survey['expires_in'] <= 0 ) {
        return array('stat'=>'expired', 'survey'=>$survey);
    }
    if( $survey['invite_expires_in'] <= 0 ) {
        return array('stat'=>'expired', 'survey'=>$survey);
    }

    //
    // Update the date_seen on the invite
    //
    $strsql = "UPDATE ciniki_survey_invites SET status = 20, date_seen = '" . ciniki_core_dbQuote($ciniki, $utc_datetime) . "', "
        . "last_updated = UTC_TIMESTAMP() "
        . "WHERE ciniki_survey_invites.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_survey_invites.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "AND status < 20 "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        2, 'ciniki_survey_invites', $survey['invite_id'], 'status', '20');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        2, 'ciniki_survey_invites', $survey['invite_id'], 'date_seen', $utc_datetime);

    return array('stat'=>'ok', 'survey'=>$survey);  
}
?>
