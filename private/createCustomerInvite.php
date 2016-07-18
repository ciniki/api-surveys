<?php
//
// Description
// ===========
// This function will create an invite for a customer to fill out a survey.
// Each invite has a unique key, so they can login directly.
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_surveys_createCustomerInvite($ciniki, $business_id, $survey_id, $mailing_id, $customer_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // Get uuid
    //
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.surveys');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
        return $rc;
    }
    $uuid = $rc['uuid'];

    //
    // Get a permalink
    //
    $permalink = md5(date('Y-m-d-H-i-s') . rand());

    //
    // Add an entry in the invite table
    //
    $strsql = "INSERT INTO ciniki_survey_invites (uuid, business_id, "
        . "survey_id, mailing_id, customer_id, status, "
        . "permalink, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $survey_id) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $mailing_id) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $customer_id) . "', "
        . "'5', "
        . "'" . ciniki_core_dbQuote($ciniki, $permalink) . "', "
        . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.surveys');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invite_id = $rc['insert_id'];

    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        1, 'ciniki_survey_invites', $invite_id, 'uuid', $uuid);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        1, 'ciniki_survey_invites', $invite_id, 'survey_id', $survey_id);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        1, 'ciniki_survey_invites', $invite_id, 'mailing_id', $mailing_id);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        1, 'ciniki_survey_invites', $invite_id, 'customer_id', $customer_id);
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        1, 'ciniki_survey_invites', $invite_id, 'status', '5');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id,
        1, 'ciniki_survey_invites', $invite_id, 'permalink', $permalink);

    $url = "/surveys/invite/$permalink";

    return array('stat'=>'ok', 'id'=>$invite_id, 'url'=>$url);
}
?>
