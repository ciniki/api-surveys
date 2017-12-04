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
function ciniki_surveys_surveyActiveList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'survey_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Survey'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $rc = ciniki_surveys_checkAccess($ciniki, $args['tnid'], 'ciniki.surveys.surveyActiveList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT "
        . "ciniki_surveys.id, "
        . "ciniki_surveys.name, "
        . "IFNULL(DATE_FORMAT(CONVERT_TZ(date_expires, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_expires "
        . "FROM ciniki_surveys "
        . "WHERE ciniki_surveys.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (status = 10 ";
    if( isset($args['survey_id']) && $args['survey_id'] > 0 ) {
        // Check if the currently selected survey should be included in the list, even if not active.
        $strsql .= "OR id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' ";
    }
    $strsql .= ") "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'surveys', 'fname'=>'id', 'name'=>'survey',
            'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['surveys']) ) {
        return array('stat'=>'ok', 'surveys'=>array());
    }
    
    return array('stat'=>'ok', 'surveys'=>$rc['surveys']);
}
?>
