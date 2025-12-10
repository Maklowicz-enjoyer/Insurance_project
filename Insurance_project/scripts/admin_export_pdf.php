<?php
// scripts/admin_export_pdf.php
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Załaduj TCPDF

// 1. Security Check
if (!$GLOBALS['current_user_is_admin']) {
    http_response_code(403);
    die('Unauthorized');
}

// Odbierz dane JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    die("Brak danych do wygenerowania raportu.");
}

// 2. Konfiguracja PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Meta dane
$pdf->SetCreator('SkanPolis Admin');
$pdf->SetAuthor('SkanPolis System');
$pdf->SetTitle('Raport Analityczny - ' . date('Y-m-d'));

// Brak nagłówka/stopki domyślnej dla czystszego wyglądu
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true); // Stopka z numeracją stron

$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// --- 3. Treść Raportu ---

// Tytuł i Data
$pdf->SetFont('dejavusans', 'B', 24);
$pdf->SetTextColor(0, 137, 123); // #00897b
$pdf->Cell(0, 10, 'SKANPOLIS', 0, 1, 'L');

$pdf->SetFont('dejavusans', '', 14);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 10, 'Raport Analityczny', 0, 1, 'L');

$pdf->SetFont('dejavusans', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 10, 'Okres raportu: ' . $input['period'], 0, 1, 'L');
$pdf->Ln(5);

// Linia oddzielająca
$pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(200, 200, 200)));
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(10);

// KPI Section (Tabela z wynikami)
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'KLUCZOWE WSKAŹNIKI EFEKTYWNOŚCI (KPI)', 0, 1, 'L');
$pdf->Ln(2);

$html = '
<table border="0" cellpadding="8" cellspacing="5" style="width: 100%;">
    <tr>
        <td style="background-color: #e0f2f1; width: 25%;">
            <span style="font-size: 8pt; color: #555;">WYSZUKIWANIA</span><br>
            <span style="font-size: 14pt; font-weight: bold; color: #00897b;">'.$input['kpi']['searches'].'</span>
        </td>
        <td style="background-color: #fff3e0; width: 25%;">
            <span style="font-size: 8pt; color: #555;">POLUBIENIA</span><br>
            <span style="font-size: 14pt; font-weight: bold; color: #ff6f00;">'.$input['kpi']['favorites'].'</span>
        </td>
        <td style="background-color: #e3f2fd; width: 25%;">
            <span style="font-size: 8pt; color: #555;">NOWI UŻYTKOWNICY</span><br>
            <span style="font-size: 14pt; font-weight: bold; color: #1976d2;">'.$input['kpi']['users'].'</span>
        </td>
        <td style="background-color: #f3e5f5; width: 25%;">
            <span style="font-size: 8pt; color: #555;">KONWERSJA</span><br>
            <span style="font-size: 14pt; font-weight: bold; color: #8e24aa;">'.$input['kpi']['conversion'].'</span>
        </td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

// --- WYKRESY ---
// Funkcja pomocnicza do dekodowania Base64
function insertChart($pdf, $base64, $title, $x, $y, $w, $h) {
    if (empty($base64)) return;
    
    // Usuń nagłówek data:image/png;base64,
    $img_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
    
    $pdf->SetXY($x, $y);
    $pdf->SetFont('dejavusans', 'B', 11);
    $pdf->Cell($w, 10, $title, 0, 1, 'L');
    
    // Użyj '@' aby wczytać dane z pamięci
    $pdf->Image('@'.$img_data, $x, $y + 10, $w, $h, 'PNG');
}

// Układ Wykresów
// Wykres 1: Trendy (Szeroki)
insertChart($pdf, $input['charts']['trend'], 'Aktywność Użytkowników', 15, $pdf->GetY(), 180, 70);
$pdf->SetY($pdf->GetY() + 85); // Przesuń kursor w dół

// Wykres 2 i 3 obok siebie
$y_pos = $pdf->GetY();
insertChart($pdf, $input['charts']['brands'], 'Top 5 Marek', 15, $y_pos, 85, 60);
insertChart($pdf, $input['charts']['types'], 'Rodzaje Polis', 110, $y_pos, 85, 60);

// Stopka generowania
$pdf->SetY(-20);
$pdf->SetFont('dejavusans', 'I', 8);
$pdf->Cell(0, 10, 'Wygenerowano automatycznie przez system SkanPolis: ' . date('Y-m-d H:i:s'), 0, 0, 'C');

// Output
$pdf->Output('skanpolis_raport.pdf', 'D'); // D = Download
?>