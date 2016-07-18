<?php
//
// Description
// -----------
// This method will export a list of the survey answers.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to export all open orders for.
//
// Returns
// -------
//
function ciniki_surveys_downloadXLS($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'survey_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Survey'), 
        'mailing_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Mailing'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $ac = ciniki_surveys_checkAccess($ciniki, $args['business_id'], 'ciniki.surveys.downloadXLS');
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Increase memory limits to be able to create entire file
    //
    ini_set('memory_limit', '4192M');

    //
    // Open Excel parsing library
    //
    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();

    //
    // Get the list of answers
    //
    if( isset($args['mailing_id']) && $args['mailing_id'] > 0 ) {
        $strsql = "SELECT ciniki_mailings.id, "
            . "ciniki_mailings.subject "
            . "FROM ciniki_mailings "
            . "WHERE ciniki_mailings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_mailings.id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mailings', 'mailing');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['mailing']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1131', 'msg'=>'Unable to find mailing'));
        }
        $mailing = $rc['mailing'];

        //
        // Get the details of the survey (name, questions, etc)
        //
        $strsql = "SELECT DISTINCT ciniki_surveys.id, "
            . "ciniki_surveys.name, "
            . "ciniki_survey_questions.id AS question_id, "
            . "ciniki_survey_questions.qnumber AS number, "
            . "ciniki_survey_questions.question "
            . "FROM ciniki_survey_invites "
            . "LEFT JOIN ciniki_surveys ON (ciniki_survey_invites.survey_id = ciniki_surveys.id "
                . "AND ciniki_surveys.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_survey_questions ON (ciniki_survey_invites.survey_id = ciniki_survey_questions.survey_id "
                . "AND ciniki_survey_questions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_survey_invites.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_survey_invites.survey_id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
            . "AND ciniki_survey_invites.mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
            . "ORDER BY ciniki_survey_questions.qnumber "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.surveys', array(
            array('container'=>'surveys', 'fname'=>'id',
                'fields'=>array('name')),
            array('container'=>'questions', 'fname'=>'question_id', 
                'fields'=>array('id'=>'question_id', 'number', 'question')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc; 
        }
        if( !isset($rc['surveys']) || count($rc['surveys']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1129', 'msg'=>'Unable to find survey'));
        }
        $survey = array_pop($rc['surveys']);
            
        //
        // Get the answers
        //
        $strsql = "SELECT ciniki_survey_answers.question_id, "
            . "ciniki_survey_answers.customer_id, "
            . "ciniki_survey_answers.id AS answer_id, "
            . "ciniki_survey_answers.answer, "
            . "CONCAT_WS(' ', ciniki_customers.first, ciniki_customers.last) AS customer_name "
            . "FROM ciniki_survey_invites "
            . "LEFT JOIN ciniki_survey_answers ON (ciniki_survey_invites.id = ciniki_survey_answers.invite_id "
                . "AND ciniki_survey_answers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers ON (ciniki_survey_answers.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_survey_invites.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_survey_invites.survey_id = '" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "' "
            . "AND ciniki_survey_invites.mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
            . "ORDER BY ciniki_survey_invites.customer_id, ciniki_survey_answers.question_id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.surveys', array(
            array('container'=>'customers', 'fname'=>'customer_id',
                'fields'=>array('name'=>'customer_name')),
            array('container'=>'answers', 'fname'=>'question_id', 
                'fields'=>array('id'=>'answer_id', 'answer')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc; 
        }
        if( isset($rc['customers']) ) {
            $customers = $rc['customers'];
        } else {
            $customers = array();
        }

        $sheet = $objPHPExcel->setActiveSheetIndex(0);

        $sheet->setTitle(substr($mailing['subject'], 0, 30));
    
        //
        // Setup the questions header
        //
        $sheet->setCellValueByColumnAndRow(0, 1, 'Customer', false);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $i = 1;
        foreach($survey['questions'] AS $qid => $question) {
            $sheet->setCellValueByColumnAndRow($i, 1, $question['question'], false);
            $sheet->getColumnDimension(chr($i+65))->setAutoSize(true);
            $i++;
        }
    
        //
        // Add customer results
        //
        $row = 2;
        foreach($customers as $cid => $customer) {
            $i = 0;
            $sheet->setCellValueByColumnAndRow($i++, $row, $customer['name'], false);
            foreach($survey['questions'] AS $qid => $question) {
                if( isset($customer['answers'][$qid]) ) {
                    $sheet->setCellValueByColumnAndRow($i, $row, $customer['answers'][$qid]['answer'], false);
                } else {
                    $sheet->setCellValueByColumnAndRow($i, $row, '', false);
                }
                $i++;
            }
            $row++;
        }

        //
        // Setup page
        //
        $sheet->getHeaderFooter()->setOddHeader('&C&H' + $mailing['subject']);
        $sheet->getHeaderFooter()->setOddFooter('&L&BPage &P of &N');
        $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->freezePane('A2');

        // Set printing to fit to one page wide
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        //
        // Redirect output to a client’s web browser (Excel5)
        //
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Survey Results - ' . $mailing['subject'] . '".xls"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    return array('stat'=>'ok');
}
?>
