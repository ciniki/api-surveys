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
function ciniki_surveys_surveyListByStatus($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.surveyListByStatus'); 
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
        . "ciniki_surveys.status, ciniki_surveys.status AS status_text, "
        . "ciniki_surveys.name, "
        . "IFNULL(DATE_FORMAT(CONVERT_TZ(date_expires, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS date_expires "
        . "FROM ciniki_surveys "
        . "WHERE ciniki_surveys.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "ORDER BY ciniki_surveys.status, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'statuses', 'fname'=>'status', 'name'=>'status',
            'fields'=>array('id'=>'status', 'name'=>'status_text'),
            'maps'=>array(
                'status_text'=>array('5'=>'Creating', '10'=>'Active', '40'=>'Closed', '60'=>'Deleted'),
                ),
            ),
        array('container'=>'surveys', 'fname'=>'id', 'name'=>'survey',
            'fields'=>array('id', 'name', 'status', 'status_text', 'date_expires'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['statuses']) ) {
        return array('stat'=>'ok', 'statuses'=>array());
    }
    
    return array('stat'=>'ok', 'statuses'=>$rc['statuses']);
}
?>
