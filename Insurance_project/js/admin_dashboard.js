// js/admin_dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    // Konfiguracja globalna wykresów
    Chart.defaults.font.family = "'Segoe UI', 'Helvetica', 'Arial', sans-serif";
    Chart.defaults.color = '#666';

    let charts = {}; // Przechowuje instancje wykresów

    // 1. Inicjalizacja Flatpickr (Kalendarz)
    const datePicker = flatpickr("#date-range", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [
            new Date(new Date().setDate(new Date().getDate() - 30)), // 30 dni temu
            new Date() // Dzisiaj
        ],
        locale: "pl", // Polski język
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                fetchData(selectedDates[0], selectedDates[1]);
            }
        }
    });

    // Pobierz daty początkowe z pickera
    const initialDates = datePicker.selectedDates;
    fetchData(initialDates[0], initialDates[1]);

    // 2. Funkcja pobierająca dane z API
    async function fetchData(startDate, endDate) {
        // Formatowanie dat do YYYY-MM-DD (uwzględniając lokalną strefę)
        const format = d => d.toISOString().split('T')[0];
        
        try {
            const response = await fetch('../scripts/admin_stats_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    start_date: format(startDate),
                    end_date: format(endDate)
                })
            });

            if (!response.ok) throw new Error('Błąd sieci');
            
            const data = await response.json();
            updateDashboard(data);

        } catch (error) {
            console.error('Error fetching stats:', error);
            alert('Nie udało się pobrać statystyk.');
        }
    }

    // 3. Aktualizacja UI
    function updateDashboard(data) {
        // A. Aktualizacja KPI
        animateValue("kpi-searches", parseInt(data.total_searches));
        animateValue("kpi-favorites", parseInt(data.total_favorites));
        animateValue("kpi-users", parseInt(data.new_users));
        
        // Oblicz konwersję
        let conversion = 0;
        if(data.total_searches > 0) {
            conversion = ((data.total_favorites / data.total_searches) * 100).toFixed(1);
        }
        document.getElementById('kpi-conversion').textContent = conversion + '%';

        // B. Aktualizacja Wykresów
        updateTrendChart(data.search_trends);
        updateBrandsChart(data.top_brands);
        updateTypesChart(data.insurance_types);
        updateVehicleSplitChart(data.vehicle_split);
    }

    // --- Funkcje pomocnicze do wykresów ---

    function updateTrendChart(trends) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        const labels = trends.map(t => t.date);
        const values = trends.map(t => t.count);

        if (charts.trend) charts.trend.destroy();

        charts.trend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Liczba wyszukiwań',
                    data: values,
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
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function updateBrandsChart(brands) {
        const ctx = document.getElementById('brandsChart').getContext('2d');
        const labels = brands.map(b => b.Brand_Name);
        const values = brands.map(b => b.count);

        if (charts.brands) charts.brands.destroy();

        charts.brands = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        '#00897b', '#26a69a', '#4db6ac', '#80cbc4', '#b2dfdb'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
                cutout: '70%'
            }
        });
    }

    function updateTypesChart(types) {
        const ctx = document.getElementById('typesChart').getContext('2d');
        const labels = types.map(t => t.Insurance_type);
        const values = types.map(t => t.count);

        if (charts.types) charts.types.destroy();

        charts.types = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ilość',
                    data: values,
                    backgroundColor: '#ff6f00',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function updateVehicleSplitChart(data) {
        const ctx = document.getElementById('vehicleSplitChart').getContext('2d');
        // Mapowanie danych (może brakować któregoś typu)
        let carCount = 0, motoCount = 0;
        data.forEach(item => {
            if(item.Vehicle_Type === 'CAR') carCount = item.count;
            if(item.Vehicle_Type === 'MOTORCYCLE') motoCount = item.count;
        });

        if (charts.vehicle) charts.vehicle.destroy();

        charts.vehicle = new Chart(ctx, {
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
                maintainAspectRatio: false
            }
        });
    }

    // Prosta animacja liczb
    function animateValue(id, end) {
        const obj = document.getElementById(id);
        let start = 0;
        if(obj.textContent !== '...') start = parseInt(obj.textContent) || 0;
        
        if (start === end) {
            obj.innerHTML = end;
            return;
        }

        const duration = 1000;
        const range = end - start;
        let startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            obj.innerHTML = Math.floor(progress * range + start);
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerHTML = end;
            }
        }
        window.requestAnimationFrame(step);
    }
});