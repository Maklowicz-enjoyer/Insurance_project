// js/admin_dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Segoe UI', 'Helvetica', 'Arial', sans-serif";
    Chart.defaults.color = '#666';

    // Przechowujemy referencje do wykresów globalnie, aby mieć do nich dostęp przy eksporcie
    const charts = {
        trend: null,
        brands: null,
        types: null,
        vehicle: null
    };

    // 1. Inicjalizacja Kalendarza
    const datePicker = flatpickr("#date-range", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [
            new Date(new Date().setDate(new Date().getDate() - 30)), 
            new Date()
        ],
        locale: "pl",
        onChange: function(selectedDates) {
            if (selectedDates.length === 2) {
                fetchData(selectedDates[0], selectedDates[1]);
            }
        }
    });

    // Inicjalne pobranie danych
    const initialDates = datePicker.selectedDates;
    fetchData(initialDates[0], initialDates[1]);

    // 2. Obsługa przycisku PDF
    document.getElementById('export-pdf-btn').addEventListener('click', async function() {
        const btn = this;
        const originalText = btn.innerHTML;
        
        // Zmień stan przycisku
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generowanie...';
        btn.disabled = true;

        try {
            await generatePDF();
        } catch (error) {
            console.error('PDF Error:', error);
            alert('Wystąpił błąd podczas generowania PDF.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });

    async function fetchData(startDate, endDate) {
        const format = d => d.toISOString().split('T')[0];
        try {
            const response = await fetch('../scripts/admin_stats_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    start_date: format(startDate),
                    end_date: format(endDate)
                })
            });
            if (!response.ok) throw new Error('Błąd API');
            const data = await response.json();
            updateDashboard(data);
        } catch (error) {
            console.error(error);
        }
    }

    function updateDashboard(data) {
        animateValue("kpi-searches", parseInt(data.total_searches));
        animateValue("kpi-favorites", parseInt(data.total_favorites));
        animateValue("kpi-users", parseInt(data.new_users));
        
        let conversion = data.total_searches > 0 ? ((data.total_favorites / data.total_searches) * 100).toFixed(1) : 0;
        document.getElementById('kpi-conversion').textContent = conversion + '%';

        updateCharts(data);
    }

    function updateCharts(data) {
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        if (charts.trend) charts.trend.destroy();
        charts.trend = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: data.search_trends.map(t => t.date),
                datasets: [{
                    label: 'Liczba wyszukiwań',
                    data: data.search_trends.map(t => t.count),
                    borderColor: '#00897b',
                    backgroundColor: 'rgba(0, 137, 123, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false, // Ważne dla PDF: wyłączamy animację przy renderowaniu dla spójności
                plugins: { legend: { display: false } }
            }
        });

        // Brands Chart
        const brandsCtx = document.getElementById('brandsChart').getContext('2d');
        if (charts.brands) charts.brands.destroy();
        charts.brands = new Chart(brandsCtx, {
            type: 'doughnut',
            data: {
                labels: data.top_brands.map(b => b.Brand_Name),
                datasets: [{
                    data: data.top_brands.map(b => b.count),
                    backgroundColor: ['#00897b', '#26a69a', '#4db6ac', '#80cbc4', '#b2dfdb']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false
            }
        });

        // Types Chart
        const typesCtx = document.getElementById('typesChart').getContext('2d');
        if (charts.types) charts.types.destroy();
        charts.types = new Chart(typesCtx, {
            type: 'bar',
            data: {
                labels: data.insurance_types.map(t => t.Insurance_type),
                datasets: [{
                    label: 'Ilość',
                    data: data.insurance_types.map(t => t.count),
                    backgroundColor: '#ff6f00',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: false } }
            }
        });

        // Vehicle Split
        const vehicleCtx = document.getElementById('vehicleSplitChart').getContext('2d');
        let carCount = 0, motoCount = 0;
        data.vehicle_split.forEach(item => {
            if(item.Vehicle_Type === 'CAR') carCount = item.count;
            if(item.Vehicle_Type === 'MOTORCYCLE') motoCount = item.count;
        });
        if (charts.vehicle) charts.vehicle.destroy();
        charts.vehicle = new Chart(vehicleCtx, {
            type: 'pie',
            data: {
                labels: ['Samochody', 'Motocykle'],
                datasets: [{
                    data: [carCount, motoCount],
                    backgroundColor: ['#1976d2', '#d32f2f']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false
            }
        });
    }

    async function generatePDF() {
        // Przygotuj dane do wysyłki
        const payload = {
            period: document.getElementById('date-range').value,
            kpi: {
                searches: document.getElementById('kpi-searches').textContent,
                favorites: document.getElementById('kpi-favorites').textContent,
                users: document.getElementById('kpi-users').textContent,
                conversion: document.getElementById('kpi-conversion').textContent
            },
            charts: {
                // Pobieramy obrazy wykresów jako Base64
                trend: charts.trend ? charts.trend.toBase64Image() : null,
                brands: charts.brands ? charts.brands.toBase64Image() : null,
                types: charts.types ? charts.types.toBase64Image() : null,
                vehicle: charts.vehicle ? charts.vehicle.toBase64Image() : null
            }
        };

        // Wyślij do PHP (używamy fetch z response blob dla pliku)
        const response = await fetch('../scripts/admin_export_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) throw new Error('Błąd generowania PDF');

        // Pobierz plik
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Raport_SkanPolis_${new Date().toISOString().slice(0,10)}.pdf`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    }

    function animateValue(id, end) {
        const obj = document.getElementById(id);
        obj.innerHTML = end; // Uproszczone dla stabilności eksportu
    }
});