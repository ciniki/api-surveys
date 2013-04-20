<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the survey to.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_surveys_surveyAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
		'status'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('5', '10', '40', '60'), 'name'=>'Status'),
		'instructions'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Instructions'),
		'date_expires'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetime', 'name'=>'Expiry'),
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
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.surveyAdd'); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistoryReformat');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	$args['uuid'] = $rc['uuid'];

	//
	// Add the survey to the database
	//
	$strsql = "INSERT INTO ciniki_surveys (uuid, business_id, "
		. "name, status, instructions, date_expires, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['instructions']) . "', "
		. "CONVERT_TZ('" . ciniki_core_dbQuote($ciniki, $args['date_expires']) . "', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "', '+00:00'), "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.surveys');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1065', 'msg'=>'Unable to add survey'));
	}
	$survey_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'uuid',
		'name',
		'status',
		'instructions',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
				'ciniki_survey_history', $args['business_id'], 
				1, 'ciniki_surveys', $survey_id, $field, $args[$field]);
		}
	}
	$rc = ciniki_core_dbAddModuleHistoryReformat($ciniki, 'ciniki.surveys', 
		'ciniki_survey_history', $args['business_id'], 
		1, 'ciniki_surveys', $survey_id, 'date_expires', $args['date_expires'], 'utcdate') ;

	//
	// Add to the sync queue
	//
	$ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.survey', 
		'args'=>array('id'=>$survey_id));

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

	return array('stat'=>'ok', 'id'=>$survey_id);
}
?>
