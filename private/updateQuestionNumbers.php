<?php
//
// Description
// ===========
// This function will renumber the questions in sequential order.
//
// Arguments
// =========
// ciniki:
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_surveys_updateQuestionNumbers($ciniki, $business_id, $survey_id, $question_id, $question_number, $old_number) {
    //
    // Get the questions
    //
    $strsql = "SELECT id, qnumber AS number "
        . "FROM ciniki_survey_questions "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $survey_id) . "' "
        . "AND status = 10 "
        . "";
    // Use the last_updated to determine which is in the proper position for duplicate numbers
    if( $question_number < $old_number || $old_number == -1) {
        $strsql .= "ORDER BY qnumber, last_updated DESC";
    } else {
        $strsql .= "ORDER BY qnumber, last_updated ";
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.surveys', 'question');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
        return $rc;
    }
    $cur_number = 1;
    if( isset($rc['rows']) ) {
        $questions = $rc['rows'];
        foreach($questions as $qid => $question) {
            //
            // If the number is not where it's suppose to be, change
            //
            if( $cur_number != $question['number'] ) {
                error_log($question['id'] . ' setting: ' . $question['number'] . ' to ' . $cur_number);
                $strsql = "UPDATE ciniki_survey_questions SET "
                    . "qnumber = '" . ciniki_core_dbQuote($ciniki, $cur_number) . "' "
                    . ", last_updated = UTC_TIMESTAMP() "
                    . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . "AND survey_id = '" . ciniki_core_dbQuote($ciniki, $survey_id) . "' "
                    . "AND id = '" . ciniki_core_dbQuote($ciniki, $question['id']) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.surveys');
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 
                    'ciniki_survey_history', $business_id, 
                    2, 'ciniki_survey_questions', $question['id'], 'qnumber', $cur_number);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.surveys.question', 
                    'args'=>array('id'=>$question['id']));
                
            }
            $cur_number++;
        }
    }
    
    return array('stat'=>'ok');
}
?>
