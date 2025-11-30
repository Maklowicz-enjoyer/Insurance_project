<?php
/**
 * PdfService - Serwis do generowania PDF z ofertami ubezpieczeniowymi
 *
 * Używa TCPDF do tworzenia profesjonalnych dokumentów PDF
 * z brandingiem SkanPolis i szczegółami oferty.
 *
 * Security: Wszystkie dane są escapowane przed dodaniem do PDF
 */

require_once __DIR__ . '/../vendor/autoload.php';

class PdfService {

    private $pdf;
    private $primaryColor = [0, 137, 123];     // #00897b (teal)
    private $accentColor = [255, 111, 0];      // #ff6f00 (orange)

    /**
     * Generuje PDF z ofertą ubezpieczeniową
     *
     * @param array $offer Dane oferty z bazy danych (Insurance VIEW)
     * @param array $userData Dane użytkownika (email, wiek)
     * @return string Zawartość PDF jako string (do zapisu lub wysłania)
     */
    public function generateOfferPdf(array $offer, array $userData): string {

        // Inicjalizacja TCPDF
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Ustawienia dokumentu
        $this->pdf->SetCreator('SkanPolis');
        $this->pdf->SetAuthor('SkanPolis');
        $this->pdf->SetTitle('Oferta ubezpieczenia - ' . htmlspecialchars($offer['Insurance_name']));
        $this->pdf->SetSubject('Szczegóły oferty ubezpieczeniowej');

        // Wyłącz domyślny header/footer
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);

        // Ustawienia marginesów
        $this->pdf->SetMargins(20, 20, 20);
        $this->pdf->SetAutoPageBreak(true, 20);

        // Dodaj stronę
        $this->pdf->AddPage();

        // Generuj zawartość
        $this->addHeader();
        $this->addOfferDetails($offer);
        $this->addUserInfo($userData);
        $this->addFooter();

        // Zwróć PDF jako string
        return $this->pdf->Output('', 'S');
    }

    /**
     * Dodaje header z logo SkanPolis
     */
    private function addHeader(): void {
        // Logo text (możesz zastąpić obrazkiem jeśli masz logo.png)
        $this->pdf->SetFont('dejavusans', 'B', 32);
        $this->pdf->SetTextColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->pdf->Cell(0, 15, 'SKANPOLIS', 0, 1, 'C');

        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->Cell(0, 5, 'Porównaj i wybierz najlepsze ubezpieczenie', 0, 1, 'C');

        // Linia oddzielająca
        $this->pdf->SetDrawColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->pdf->SetLineWidth(1);
        $y = $this->pdf->GetY() + 5;
        $this->pdf->Line(20, $y, 190, $y);

        $this->pdf->Ln(10);
    }

    /**
     * Dodaje szczegóły oferty
     */
    private function addOfferDetails(array $offer): void {
        // Tytuł sekcji
        $this->pdf->SetFont('dejavusans', 'B', 18);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 10, 'SZCZEGÓŁY OFERTY', 0, 1, 'L');
        $this->pdf->Ln(3);

        // Nazwa ubezpieczyciela - wyróżniona
        $this->pdf->SetFillColor(244, 246, 248);
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->Cell(0, 12, htmlspecialchars($offer['Insurance_name']), 1, 1, 'C', true);
        $this->pdf->Ln(5);

        // Cena - duża i wyróżniona
        $this->pdf->SetFont('dejavusans', 'B', 24);
        $this->pdf->SetTextColor($this->accentColor[0], $this->accentColor[1], $this->accentColor[2]);
        $price = number_format($offer['Price'], 2, ',', ' ');
        $this->pdf->Cell(0, 15, $price . ' PLN', 0, 1, 'C');
        $this->pdf->Ln(5);

        // Szczegóły w tabeli
        $this->pdf->SetFont('dejavusans', '', 11);
        $this->pdf->SetTextColor(0, 0, 0);

        $this->addDetailRow('Typ ubezpieczenia:', htmlspecialchars($offer['Insurance_type']));
        $this->addDetailRow('Rodzaj użytkowania:', htmlspecialchars($offer['Use_type']));
        $this->addDetailRow('Ważność oferty:', htmlspecialchars($offer['License_release_date']));

        // Szczegóły specyficzne dla typu pojazdu
        if ($offer['Vehicle_Category'] === 'CAR') {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('dejavusans', 'B', 14);
            $this->pdf->Cell(0, 8, 'Informacje o pojeździe:', 0, 1, 'L');
            $this->pdf->SetFont('dejavusans', '', 11);

            $this->addDetailRow('Kategoria:', 'Samochód osobowy');
            $this->addDetailRow('Typ nadwozia:', htmlspecialchars($offer['Typ_nadwozia']));
            $this->addDetailRow('Planowany przebieg:', number_format($offer['Planned_mileage'], 0, ',', ' ') . ' km/rok');

        } else if ($offer['Vehicle_Category'] === 'MOTORCYCLE') {
            $this->pdf->Ln(5);
            $this->pdf->SetFont('dejavusans', 'B', 14);
            $this->pdf->Cell(0, 8, 'Informacje o motocyklu:', 0, 1, 'L');
            $this->pdf->SetFont('dejavusans', '', 11);

            $this->addDetailRow('Kategoria:', 'Motocykl');
            if (!empty($offer['Typ_nadwozia']) && $offer['Typ_nadwozia'] !== 'MOTORCYCLE') {
                $this->addDetailRow('Typ motocykla:', htmlspecialchars($offer['Typ_nadwozia']));
            }
        }

        $this->pdf->Ln(10);
    }

    /**
     * Dodaje wiersz z danymi (klucz: wartość)
     */
    private function addDetailRow(string $label, string $value): void {
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->SetTextColor(80, 80, 80);
        $this->pdf->Cell(60, 8, $label, 0, 0, 'L');

        $this->pdf->SetFont('dejavusans', '', 11);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 8, $value, 0, 1, 'L');
    }

    /**
     * Dodaje informacje o użytkowniku
     */
    private function addUserInfo(array $userData): void {
        // Ramka z danymi użytkownika
        $this->pdf->SetFillColor(240, 248, 255);
        $this->pdf->Rect(20, $this->pdf->GetY(), 170, 25, 'F');

        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 8, 'Dane użytkownika:', 0, 1, 'L');

        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->SetTextColor(60, 60, 60);
        $this->pdf->Cell(0, 6, 'Email: ' . htmlspecialchars($userData['email']), 0, 1, 'L');

        if (!empty($userData['Wiek'])) {
            $this->pdf->Cell(0, 6, 'Wiek: ' . htmlspecialchars($userData['Wiek']) . ' lat', 0, 1, 'L');
        }

        $this->pdf->Ln(10);
    }

    /**
     * Dodaje stopkę z informacjami kontaktowymi
     */
    private function addFooter(): void {
        // Pozycjonuj na dole strony
        $this->pdf->SetY(-30);

        // Linia oddzielająca
        $this->pdf->SetDrawColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->pdf->SetLineWidth(0.5);
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());

        $this->pdf->Ln(3);

        // Informacje kontaktowe
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->Cell(0, 5, 'SkanPolis - Twój partner w wyborze ubezpieczenia', 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Email: kontakt@skanpolis.pl | www.skanpolis.pl', 0, 1, 'C');

        $this->pdf->SetFont('dejavusans', 'I', 8);
        $this->pdf->Cell(0, 5, 'Dokument wygenerowany automatycznie dnia ' . date('d.m.Y'), 0, 1, 'C');
    }
}
