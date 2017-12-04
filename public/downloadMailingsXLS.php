<?php
//
// Description
// -----------
// The method will export the list of mailings and their survey answers to excel.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to export all open orders for.
//
// Returns
// -------
//
function ciniki_surveys_downloadMailingsXLS($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'checkAccess');
    $ac = ciniki_surveys_checkAccess($ciniki, $args['tnid'], 'ciniki.surveys.downloadMailingsXLS');
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
    // Get the mailings, surveys, questions and answers
    //
    $strsql = "SELECT CONCAT_WS('-', ciniki_mailings.id, ciniki_surveys.id, ciniki_survey_invites.id) AS rowid, "
        . "CONCAT_WS(' ', ciniki_customers.first, ciniki_customers.last) AS customer_name, "
        . "ciniki_mailings.subject AS mailing_name, "
        . "ciniki_survey_invites.date_sent, "
        . "ciniki_survey_invites.date_seen, "
        . "ciniki_survey_invites.date_answered, "
        . "ciniki_surveys.name AS survey_name, "
        . "ciniki_survey_questions.id AS question_id, "
        . "ciniki_survey_questions.qnumber, "
        . "ciniki_survey_questions.question, "
        . "ciniki_survey_answers.answer "
        . "FROM ciniki_mailings "
        . "LEFT JOIN ciniki_surveys ON (ciniki_mailings.survey_id = ciniki_surveys.id "
            . "AND ciniki_surveys.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_survey_invites ON (ciniki_surveys.id = ciniki_survey_invites.survey_id "
            . "AND ciniki_mailings.id = ciniki_survey_invites.mailing_id "
            . "AND ciniki_survey_invites.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON (ciniki_survey_invites.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.id > 0 "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_survey_questions ON (ciniki_surveys.id = ciniki_survey_questions.survey_id "
            . "AND ciniki_survey_questions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_survey_answers ON (ciniki_surveys.id = ciniki_survey_answers.survey_id "
            . "AND ciniki_survey_answers.question_id = ciniki_survey_questions.id "
            . "AND ciniki_survey_invites.customer_id = ciniki_survey_answers.customer_id "
            . "AND ciniki_survey_answers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_mailings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_mailings.survey_id > 0 "
        . "AND ciniki_customers.id > 0 "
        . "ORDER BY ciniki_mailings.date_added DESC, ciniki_customers.last, ciniki_customers.first, ciniki_surveys.id, ciniki_survey_questions.qnumber "
        . "";
    error_log($strsql);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.surveys', array(
        array('container'=>'rows', 'fname'=>'rowid',
            'fields'=>array('customer'=>'customer_name', 'mailing'=>'mailing_name', 
                'date_sent', 'date_seen', 'date_answered', 'survey'=>'survey_name')),
        array('container'=>'questions', 'fname'=>'question_id', 
            'fields'=>array('id'=>'question_id', 'question', 'answer')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc; 
    }
    if( isset($rc['rows']) ) {
        $rows = $rc['rows'];
    } else {
        $rows = array();
    }

    $sheet = $objPHPExcel->setActiveSheetIndex(0);
    $sheet->setTitle('Results', 0, 30);

    //
    // Setup the questions header
    //
    $i=0;
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Customer', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Mailing', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Date Sent', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Date Viewed', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Date Responsed', false);
    $sheet->setCellValueByColumnAndRow($i++, 1, 'Survey', false);
    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);
    $sheet->getColumnDimension('E')->setAutoSize(true);
    $sheet->getColumnDimension('F')->setAutoSize(true);

    //
    // Add customer results
    //
    $j = 2;
    $max_size = 0;
    foreach($rows as $rid => $row) {
        $i = 0;
        $sheet->setCellValueByColumnAndRow($i++, $j, $row['customer'], false);
        $sheet->setCellValueByColumnAndRow($i++, $j, $row['mailing'], false);
        $sheet->setCellValueByColumnAndRow($i++, $j, $row['date_sent'], false);
        $sheet->setCellValueByColumnAndRow($i++, $j, $row['date_seen'], false);
        $sheet->setCellValueByColumnAndRow($i++, $j, $row['date_answered'], false);
        $sheet->setCellValueByColumnAndRow($i++, $j, $row['survey'], false);
        foreach($row['questions'] AS $qid => $questions) {
            $sheet->setCellValueByColumnAndRow($i, $j, $questions['question'], false);
            $i++;
            $sheet->setCellValueByColumnAndRow($i, $j, $questions['answer'], false);
            $i++;
        }
        if( $i > $max_size ) { $max_size = $i; }
        $j++;
    }
    for($i=6;$i<$max_size;$i++) {
        $sheet->setCellValueByColumnAndRow($i, 1, 'Question', false);
        $sheet->getColumnDimension(chr($i+65))->setAutoSize(true);
        $i++;
        $sheet->setCellValueByColumnAndRow($i, 1, 'Answer', false);
        $sheet->getColumnDimension(chr($i+65))->setAutoSize(true);
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
    header('Content-Disposition: attachment;filename="Survey Results.xls"');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;

    return array('stat'=>'ok');
}
?>
