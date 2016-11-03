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
function ciniki_surveys_questionGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'question_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Question'),
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stats'),
        'top_answers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Top Answers'),
        'answers'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Answers'),
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
    $rc = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.questionGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT "
        . "ciniki_survey_questions.id, "
        . "ciniki_survey_questions.qnumber, "
        . "ciniki_survey_questions.qtype, "
        . "ciniki_survey_questions.status, "
        . "ciniki_survey_questions.question, "
        . "ciniki_survey_questions.option1, "
        . "ciniki_survey_questions.option2, "
        . "ciniki_survey_questions.option3, "
        . "ciniki_survey_questions.option4, "
        . "ciniki_survey_questions.option5 "
        . "FROM ciniki_survey_questions "
        . "WHERE ciniki_survey_questions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_survey_questions.id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
        . "ORDER BY ciniki_survey_questions.id ASC ";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.surveys', array(
        array('container'=>'questions', 'fname'=>'id', 'name'=>'question',
            'fields'=>array('id', 'number'=>'qnumber', 'status', 'type'=>'qtype', 'question'=>'question',
                'option1', 'option2', 'option3', 'option4', 'option5')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['questions']) && !isset($rc['questions'][0]['question']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.surveys.7', 'msg'=>'Unable to find question'));
    }

    $question = $rc['questions'][0]['question'];

    if( isset($args['stats']) && $args['stats'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $question['stats'] = array();
        $strsql = "SELECT 'answer_count' AS name, COUNT(ciniki_survey_answers.id) "
            . "FROM ciniki_survey_answers "
            . "WHERE ciniki_survey_answers.question_id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
            . "AND ciniki_survey_answers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.surveys', 'stats');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['stats']) ) {
            $question['stats']['answer_count'] = $rc['stats']['answer_count'];
        } else {
            $question['stats']['answer_count'] = 0;
        }
    }

    //
    // Top Answers
    //
    if( isset($args['top_answers']) && $args['top_answers'] > 0 ) {
        $strsql = "SELECT COUNT(ciniki_survey_answers.id) AS answer_count, "
            . "ciniki_survey_answers.answer "
            . "FROM ciniki_survey_answers "
            . "WHERE ciniki_survey_answers.question_id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
            . "AND ciniki_survey_answers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "GROUP BY ciniki_survey_answers.answer "
            . "ORDER BY answer_count DESC "
            . "LIMIT " . ciniki_core_dbQuote($ciniki, $args['top_answers']) . " "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.surveys', array(
            array('container'=>'top_answers', 'fname'=>'answer', 'name'=>'answer',
                'fields'=>array('answer_count', 'answer')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $question['top_answers'] = $rc['top_answers'];
    }

    //
    // Check if answers should be returned
    //
    if( isset($args['answers']) && $args['answers'] == 'yes' ) {
        $strsql = "SELECT ciniki_survey_answers.id, "
            . "ciniki_survey_answers.customer_id, "
            . "ciniki_survey_answers.answer, "
            . "CONCAT_WS(' ', ciniki_customers.first, ciniki_customers.last) AS customer_name, "
            . "DATE_FORMAT(ciniki_survey_invites.date_answered, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_answered "
            . "FROM ciniki_survey_answers "
            . "LEFT JOIN ciniki_survey_invites ON (ciniki_survey_answers.invite_id = ciniki_survey_invites.id "
                . "AND ciniki_survey_invites.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
            . "LEFT JOIN ciniki_customers ON (ciniki_survey_answers.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
            . "WHERE ciniki_survey_answers.question_id = '" . ciniki_core_dbQuote($ciniki, $args['question_id']) . "' "
            . "AND ciniki_survey_answers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_survey_invites.date_answered DESC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.surveys', array(
            array('container'=>'answers', 'fname'=>'id', 'name'=>'answer',
                'fields'=>array('id', 'customer_id', 'customer_name', 'answer', 'date_answered')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $question['answers'] = $rc['answers'];
    }
    
    return array('stat'=>'ok', 'question'=>$question);
}
?>
