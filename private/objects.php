<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_surveys_objects($ciniki) {
    
    $objects = array();
    $objects['survey'] = array(
        'name'=>'Survey',
        'sync'=>'yes',
        'table'=>'ciniki_surveys',
        'fields'=>array(
            'name'=>array(),
            'status'=>array(),
            'instructions'=>array(),
            'date_expires'=>array(),
            ),
        'history_table'=>'ciniki_survey_history',
        );
    $objects['invite'] = array(
        'name'=>'Survey Invite',
        'sync'=>'yes',
        'table'=>'ciniki_survey_invites',
        'fields'=>array(
            'survey_id'=>array('ref'=>'ciniki.surveys.survey'),
            'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
            'customer_id'=>array('ref'=>'ciniki.customers.customer'),
            'status'=>array(),
            'permalink'=>array(),
            'date_sent'=>array(),
            'date_seen'=>array(),
            'date_answered'=>array(),
            'answered_user_agent'=>array(),
            'date_expires'=>array(),
            ),
        'history_table'=>'ciniki_survey_history',
        );
    $objects['question'] = array(
        'name'=>'Survey Question',
        'sync'=>'yes',
        'table'=>'ciniki_survey_questions',
        'fields'=>array(
            'survey_id'=>array('ref'=>'ciniki.surveys.survey'),
            'status'=>array(),
            'qnumber'=>array(),
            'qtype'=>array(),
            'question'=>array(),
            'option1'=>array(),
            'option2'=>array(),
            'option3'=>array(),
            'option4'=>array(),
            'option5'=>array(),
            ),
        'history_table'=>'ciniki_survey_history',
        );
    $objects['answer'] = array(
        'name'=>'Survey Answer',
        'sync'=>'yes',
        'table'=>'ciniki_survey_answers',
        'fields'=>array(
            'survey_id'=>array('ref'=>'ciniki.surveys.survey'),
            'invite_id'=>array('ref'=>'ciniki.surveys.invite'),
            'customer_id'=>array('ref'=>'ciniki.customers.customer'),
            'question_id'=>array('ref'=>'ciniki.surveys.question'),
            'answer'=>array(),
            ),
        'history_table'=>'ciniki_survey_history',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
