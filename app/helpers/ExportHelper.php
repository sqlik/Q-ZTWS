<?php
// app/helpers/ExportHelper.php
// Helper odpowiedzialny za eksport danych do różnych formatów

class ExportHelper {
    private $participantModel;
    private $questionModel;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->participantModel = new Participant();
        $this->questionModel = new Question();
    }
    
    /**
     * Eksportuje wyniki quizu do pliku PDF
     * 
     * @param Quiz $quiz Obiekt quizu
     * @param array $sessions Tablica sesji
     * @return void
     */
    public function exportQuizToPdf($quiz, $sessions) {
        // Wygeneruj unikalną nazwę pliku
        $filename = 'quiz_' . $quiz->id . '_' . date('Ymd_His') . '.pdf';
        $filepath = EXPORTS_PATH . '/pdf/' . $filename;
        
        // Upewnij się, że katalog istnieje
        if (!file_exists(EXPORTS_PATH . '/pdf/')) {
            mkdir(EXPORTS_PATH . '/pdf/', 0755, true);
        }
        
        // Użyj biblioteki TCPDF do wygenerowania PDF
        require_once ROOT_PATH . '/vendor/autoload.php';
        
        // Utwórz nowy dokument PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        
        // Ustaw informacje o dokumencie
        $pdf->SetCreator('Q-ZTWS');
        $pdf->SetAuthor('Q-ZTWS');
        $pdf->SetTitle($quiz->title . ' - ' . __('quiz_results'));
        $pdf->SetSubject($quiz->title . ' - ' . __('quiz_results'));
        
        // Usuń nagłówek i stopkę
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Ustaw marginesy
        $pdf->SetMargins(15, 15, 15);
        
        // Ustaw automatyczne podziały stron
        $pdf->SetAutoPageBreak(true, 15);
        
        // Dodaj stronę
        $pdf->AddPage();
        
        // Tytuł raportu
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $quiz->title, 0, 1, 'C');
        
        if (!empty($quiz->description)) {
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Ln(5);
            $pdf->writeHTML($quiz->description);
            $pdf->Ln(5);
        }
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, __('quiz_results'), 0, 1, 'L');
        
        // Dla każdej sesji
        foreach ($sessions as $index => $session) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, __('session') . ' #' . ($index + 1) . ' (' . $session['start_time'] . ')', 0, 1, 'L');
            
            // Pobierz uczestników sesji
            $participants = $this->participantModel->getBySessionIdWithResults($session['id']);
            
            if (empty($participants)) {
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(0, 10, __('no_participants'), 0, 1, 'L');
                continue;
            }
            
            // Sortuj uczestników według wyniku
            usort($participants, function($a, $b) {
                return $b['total_score'] - $a['total_score'];
            });
            
            // Tabela uczestników
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Ln(5);
            
            // Nagłówki tabeli
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
            $pdf->Cell(60, 7, __('nickname'), 1, 0, 'C', true);
            $pdf->Cell(30, 7, __('score'), 1, 0, 'C', true);
            $pdf->Cell(30, 7, __('answers'), 1, 0, 'C', true);
            $pdf->Cell(40, 7, __('average_time'), 1, 1, 'C', true);
            
            // Wiersze tabeli
            $pdf->SetFillColor(245, 245, 245);
            $fill = false;
            
            foreach ($participants as $key => $participant) {
                $pdf->Cell(10, 7, $key + 1, 1, 0, 'C', $fill);
                $pdf->Cell(60, 7, $participant['nickname'], 1, 0, 'L', $fill);
                $pdf->Cell(30, 7, $participant['total_score'], 1, 0, 'C', $fill);
                $pdf->Cell(30, 7, $participant['answers_count'] . ' / ' . $participant['questions_count'], 1, 0, 'C', $fill);
                $pdf->Cell(40, 7, round($participant['average_time'], 2) . ' ' . __('seconds'), 1, 1, 'C', $fill);
                
                $fill = !$fill;
            }
            
            $pdf->Ln(10);
        }
        
        // Pobierz pytania quizu
        $questions = $this->questionModel->getByQuizId($quiz->id);
        
        if (!empty($questions)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, __('quiz_questions'), 0, 1, 'L');
            
            foreach ($questions as $index => $question) {
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(0, 10, ($index + 1) . '. ' . $question['question_text'], 0, 1, 'L');
                
                // Pobierz odpowiedzi na pytanie
                $this->questionModel->id = $question['id'];
                $answers = $this->questionModel->getAnswers();
                
                $pdf->SetFont('helvetica', '', 10);
                
                foreach ($answers as $answer) {
                    $prefix = $answer['is_correct'] ? '✓ ' : '✗ ';
                    $pdf->Cell(0, 7, $prefix . $answer['answer_text'], 0, 1, 'L');
                }
                
                $pdf->Ln(5);
            }
        }
        
        // Zapisz plik
        $pdf->Output($filepath, 'F');
        
        // Pobierz plik
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        exit;
    }
    
    /**
     * Eksportuje wyniki quizu do pliku XLSX
     * 
     * @param Quiz $quiz Obiekt quizu
     * @param array $sessions Tablica sesji
     * @return void
     */
    public function exportQuizToXlsx($quiz, $sessions) {
        // Wygeneruj unikalną nazwę pliku
        $filename = 'quiz_' . $quiz->id . '_' . date('Ymd_His') . '.xlsx';
        $filepath = EXPORTS_PATH . '/xlsx/' . $filename;
        
        // Upewnij się, że katalog istnieje
        if (!file_exists(EXPORTS_PATH . '/xlsx/')) {
            mkdir(EXPORTS_PATH . '/xlsx/', 0755, true);
        }
        
        // Użyj biblioteki PhpSpreadsheet do wygenerowania pliku XLSX
        require_once ROOT_PATH . '/vendor/autoload.php';
        
        // Utwórz nowy arkusz
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Ustaw tytuł
        $sheet->setTitle(__('results'));
        
        // Nagłówek
        $sheet->setCellValue('A1', $quiz->title);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Dla każdej sesji utwórz nowy arkusz
        foreach ($sessions as $index => $session) {
            // Utwórz nowy arkusz
            $sessionSheet = $spreadsheet->createSheet();
            $sessionSheet->setTitle(__('session') . ' ' . ($index + 1));
            
            // Nagłówek
            $sessionSheet->setCellValue('A1', __('session') . ' #' . ($index + 1) . ' (' . $session['start_time'] . ')');
            $sessionSheet->mergeCells('A1:E1');
            $sessionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            
            // Pobierz uczestników sesji
            $participants = $this->participantModel->getBySessionIdWithResults($session['id']);
            
            if (empty($participants)) {
                $sessionSheet->setCellValue('A3', __('no_participants'));
                continue;
            }
            
            // Sortuj uczestników według wyniku
            usort($participants, function($a, $b) {
                return $b['total_score'] - $a['total_score'];
            });
            
            // Nagłówki tabeli
            $sessionSheet->setCellValue('A3', '#');
            $sessionSheet->setCellValue('B3', __('nickname'));
            $sessionSheet->setCellValue('C3', __('score'));
            $sessionSheet->setCellValue('D3', __('answers'));
            $sessionSheet->setCellValue('E3', __('average_time'));
            
            $sessionSheet->getStyle('A3:E3')->getFont()->setBold(true);
            $sessionSheet->getStyle('A3:E3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sessionSheet->getStyle('A3:E3')->getFill()->getStartColor()->setRGB('DDDDDD');
            
            // Wiersze tabeli
            foreach ($participants as $key => $participant) {
                $row = $key + 4;
                $sessionSheet->setCellValue('A' . $row, $key + 1);
                $sessionSheet->setCellValue('B' . $row, $participant['nickname']);
                $sessionSheet->setCellValue('C' . $row, $participant['total_score']);
                $sessionSheet->setCellValue('D' . $row, $participant['answers_count'] . ' / ' . $participant['questions_count']);
                $sessionSheet->setCellValue('E' . $row, round($participant['average_time'], 2) . ' ' . __('seconds'));
            }
            
            // Automatyczne dopasowanie szerokości kolumn
            foreach (range('A', 'E') as $column) {
                $sessionSheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Obramowanie tabeli
            $lastRow = count($participants) + 3;
            $sessionSheet->getStyle('A3:E' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        
        // Utwórz arkusz z pytaniami
        $questionsSheet = $spreadsheet->createSheet();
        $questionsSheet->setTitle(__('questions'));
        
        // Nagłówek
        $questionsSheet->setCellValue('A1', __('quiz_questions'));
        $questionsSheet->mergeCells('A1:C1');
        $questionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // Nagłówki tabeli
        $questionsSheet->setCellValue('A3', '#');
        $questionsSheet->setCellValue('B3', __('question'));
        $questionsSheet->setCellValue('C3', __('type'));
        $questionsSheet->setCellValue('D3', __('answers'));
        
        $questionsSheet->getStyle('A3:D3')->getFont()->setBold(true);
        $questionsSheet->getStyle('A3:D3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $questionsSheet->getStyle('A3:D3')->getFill()->getStartColor()->setRGB('DDDDDD');
        
        // Pobierz pytania quizu
        $questions = $this->questionModel->getByQuizId($quiz->id);
        
        if (!empty($questions)) {
            foreach ($questions as $index => $question) {
                $row = $index + 4;
                $questionsSheet->setCellValue('A' . $row, $index + 1);
                $questionsSheet->setCellValue('B' . $row, $question['question_text']);
                
                // Typ pytania
                switch ($question['question_type']) {
                    case 'single':
                        $type = __('single_choice');
                        break;
                    case 'multiple':
                        $type = __('multiple_choice');
                        break;
                    case 'boolean':
                        $type = __('true_false');
                        break;
                    default:
                        $type = $question['question_type'];
                }
                
                $questionsSheet->setCellValue('C' . $row, $type);
                
                // Pobierz odpowiedzi na pytanie
                $this->questionModel->id = $question['id'];
                $answers = $this->questionModel->getAnswers();
                
                $answersList = '';
                
                foreach ($answers as $answer) {
                    $prefix = $answer['is_correct'] ? '✓ ' : '✗ ';
                    $answersList .= $prefix . $answer['answer_text'] . "\n";
                }
                
                $questionsSheet->setCellValue('D' . $row, $answersList);
                $questionsSheet->getStyle('D' . $row)->getAlignment()->setWrapText(true);
            }
            
            // Automatyczne dopasowanie szerokości kolumn
            foreach (range('A', 'D') as $column) {
                $questionsSheet->getColumnDimension($column)->setAutoSize(true);
            }
            
            // Obramowanie tabeli
            $lastRow = count($questions) + 3;
            $questionsSheet->getStyle('A3:D' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }
        
        // Aktywuj pierwszy arkusz
        $spreadsheet->setActiveSheetIndex(0);
        
        // Zapisz plik
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);
        
        // Pobierz plik
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        
        readfile($filepath);
        exit;
    }
}