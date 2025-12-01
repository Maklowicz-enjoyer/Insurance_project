<?php
// Sprawdź sesję
require_once __DIR__ . '/../scripts/session_check.php';
require_once __DIR__ . '/../scripts/db_connect.php';

// Pobierz marki samochodów
$carBrandsStmt = $pdo->query("SELECT Brand_Name FROM CarBrands ORDER BY Brand_Name ASC");
$carBrands = $carBrandsStmt->fetchAll(PDO::FETCH_COLUMN);

// Pobierz marki motocykli
$motoBrandsStmt = $pdo->query("SELECT Brand_Name FROM MotorcycleBrands ORDER BY Brand_Name ASC");
$motoBrands = $motoBrandsStmt->fetchAll(PDO::FETCH_COLUMN);

// Pobierz ostatnie 3 wyszukiwania użytkownika
$searchHistory = [];
if (isset($_SESSION['user_id'])) {
    try {
        $historyStmt = $pdo->prepare("
            SELECT * FROM SearchHistory
            WHERE Users_ID = :user_id
            ORDER BY Date_of_search DESC
            LIMIT 3
        ");
        $historyStmt->execute([':user_id' => $_SESSION['user_id']]);
        $searchHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch search history: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkanPolis - Znajdź Ubezpieczenie</title>
  <link rel="stylesheet" href="../css/main.css">
</head>
<body>

  <header class="header">
    <div class="header-container">
      <h1 class="logo"><span class="part1">SKAN</span>POLIS</h1>
      <div class="header-buttons">
        <a href="../index.html" class="btn back">POWRÓT</a>
        <a href="account.php" class="btn acc">MOJE KONTO</a>
        <a href="../scripts/logout.php" class="btn logout">WYLOGUJ SIĘ</a>
      </div>
    </div>
  </header>

  <main>
    <h2 class="title">FORMULARZ WYSZUKIWANIA UBEZPIECZENIA</h2>

    <?php if (!empty($searchHistory)): ?>
    <div class="search-history">
      <h3 class="history-title">📋 Ostatnie wyszukiwania</h3>
      <div class="history-items">
        <?php foreach ($searchHistory as $index => $search): ?>
          <div class="history-item" data-search-id="<?php echo $search['Search_ID']; ?>">
            <div class="history-info">
              <span class="history-badge">
                <?php echo $search['Vehicle_Type'] === 'CAR' ? '🚗 Auto' : '🏍️ Moto'; ?>
              </span>
              <div class="history-details">
                <strong><?php echo htmlspecialchars($search['Brand_Name'] ?? 'Brak marki'); ?></strong>
                <?php if ($search['Vehicle_Type'] === 'CAR' && $search['Body_Type']): ?>
                  <span class="separator">•</span>
                  <span><?php echo htmlspecialchars($search['Body_Type']); ?></span>
                <?php endif; ?>
                <?php if ($search['Production_Year']): ?>
                  <span class="separator">•</span>
                  <span><?php echo $search['Production_Year']; ?>r.</span>
                <?php endif; ?>
                <span class="separator">•</span>
                <span><?php echo htmlspecialchars($search['Insurance_type']); ?></span>
              </div>
              <small class="history-date">
                <?php
                  $date = new DateTime($search['Date_of_search']);
                  echo $date->format('d.m.Y H:i');
                ?>
              </small>
            </div>
            <button type="button" class="btn-use-search"
                    data-search='<?php echo htmlspecialchars(json_encode($search), ENT_QUOTES); ?>'>
              Szukaj polisy
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="form-container">
      <div class="vehicle-type-switch">
        <button type="button" class="switch-btn active" data-type="car">
          🚗 SAMOCHODY
        </button>
        <button type="button" class="switch-btn" data-type="motorcycle">
          🏍️ MOTOCYKLE
        </button>
      </div>

      <form id="insurance-form" action="../scripts/manage_insurance.php" method="POST" class="insurance-form">
        <input type="hidden" id="vehicle_type" name="vehicle_type" value="CAR">

        <div class="field-group">
          <label for="dob">Data urodzenia kierowcy</label>
          <input type="date" id="dob" name="dob" required>
        </div>

        <div class="field-group">
          <label for="insurance-date">Data rozpoczęcia ubezpieczenia</label>
          <input type="date" id="insurance-date" name="insurance_date" required>
        </div>

        <!-- SELECT dla marek samochodów -->
        <div class="field-group" id="car-brand-group">
          <label for="car-brand">Marka samochodu</label>
          <select id="car-brand" name="car-brand" required>
            <option value="">Wybierz markę...</option>
            <?php foreach ($carBrands as $brand): ?>
              <option value="<?php echo htmlspecialchars($brand); ?>">
                <?php echo htmlspecialchars($brand); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- SELECT dla marek motocykli (ukryty domyślnie) -->
        <div class="field-group" id="moto-brand-group" style="display: none;">
          <label for="moto-brand">Marka motocykla</label>
          <select id="moto-brand" name="moto-brand">
            <option value="">Wybierz markę...</option>
            <?php foreach ($motoBrands as $brand): ?>
              <option value="<?php echo htmlspecialchars($brand); ?>">
                <?php echo htmlspecialchars($brand); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field-group">
          <label for="insurance-type">Typ ubezpieczenia</label>
          <select id="insurance-type" name="typ_ubezpieczenia" required>
            <option value="">Wybierz...</option>
            <option value="OC">OC</option>
            <option value="OC/AC">OC/AC</option>
            <option value="Assistance">Assistance</option>
          </select>
        </div>

        <div class="field-group car-only">
          <label for="typ_nadwozia">Typ Nadwozia</label>
          <select id="typ_nadwozia" name="typ_nadwozia">
            <option value="" disabled selected>Wybierz typ nadwozia</option>
            <option value="Sedan">Sedan</option>
            <option value="SUV">SUV</option>
            <option value="Kombi">Kombi</option>
            <option value="Hatchback">Hatchback</option>
            <option value="Kompakt">Kompakt</option>
            <option value="Coupe">Coupe</option>
            <option value="Kabriolet">Kabriolet</option>
          </select>
        </div>

        <div class="field-group">
          <label for="usage">Typ użytkowania</label>
          <select id="usage" name="use_type" required>
            <option value="">Wybierz...</option>
            <option value="LEASING">LEASING</option>
            <option value="PRYWATNIE">PRYWATNIE</option>
          </select>
        </div>

        <div class="field-group">
          <label for="year">Rok produkcji</label>
          <input type="number" id="year" name="year" min="1990" max="2025" required>
        </div>

        <div class="field-group">
          <label for="license-date">Data wydania prawa jazdy</label>
          <input type="date" id="license-date" name="license_date" required>
        </div>

        <div class="field-group">
          <label for="capacity" id="capacity-label">Pojemność silnika (cm³)</label>
          <input type="number" id="capacity" name="capacity" min="500" max="8000">
          <small id="capacity-hint" class="motorcycle-only" style="display:none;">Pole obowiązkowe dla motocykli</small>
        </div>

        <div class="field-group motorcycle-only" style="display:none;">
          <label for="power">Moc silnika (KM)</label>
          <input type="number" id="power" name="power_hp" min="5" max="300" placeholder="np. 75">
        </div>

        <div class="field-group motorcycle-only" style="display:none;">
          <label for="motorcycle-type">Typ motocykla</label>
          <select id="motorcycle-type" name="motorcycle_type">
            <option value="">Dowolny</option>
            <option value="naked">Naked</option>
            <option value="cruiser">Cruiser</option>
            <option value="bobber">Bobber</option>
            <option value="cross">Cross</option>
          </select>
        </div>

        <div class="field-group car-only">
          <label for="damage">Lata od ostatniej szkody</label>
          <input type="number" id="damage" name="damage" min="0" max="20">
        </div>

        <div class="field-group car-only">
          <label for="fuel">Rodzaj paliwa</label>
          <select id="fuel" name="fuel">
            <option value="">Wybierz...</option>
            <option value="BENZYNA">BENZYNA</option>
            <option value="ROPA">ROPA</option>
            <option value="BENZYNA+LPG">BENZYNA+LPG</option>
            <option value="HYBRYDA">HYBRYDA</option>
            <option value="PRĄD">PRĄD</option>
          </select>
        </div>

        <div class="field-group motorcycle-only" style="display:none;">
          <label for="moto-damage">Lata od ostatniej szkody</label>
          <input type="number" id="moto-damage" name="damage" min="0" max="20">
        </div>

        <div class="field-group car-only">
          <label for="mileage">Planowany kilometraż roczny (tys. km)</label>
          <select id="mileage" name="mileage">
            <option value="">Wybierz...</option>
            <option value="0-2">0-2</option>
            <option value="2-5">2-5</option>
            <option value="5-10">5-10</option>
            <option value="10-20">10-20</option>
            <option value="20 i więcej">20 i więcej</option>
          </select>
        </div>

        <div class="field-group placeholder motorcycle-only" style="display:none;"></div>

        <button type="submit" class="search-btn">SZUKAJ POLISY</button>
      </form>

    </div>

  </main>

  <footer class="footer">
    <p>© 2024 Skanpolis. Wszelkie prawa zastrzeżone.</p>
  </footer>

  <script>
    const switchButtons = document.querySelectorAll('.switch-btn');
    const form = document.getElementById('insurance-form');
    const vehicleTypeInput = document.getElementById('vehicle_type');
    const carBrandGroup = document.getElementById('car-brand-group');
    const motoBrandGroup = document.getElementById('moto-brand-group');
    const carBrandSelect = document.getElementById('car-brand');
    const motoBrandSelect = document.getElementById('moto-brand');
    const capacityLabel = document.getElementById('capacity-label');
    const capacityInput = document.getElementById('capacity');
    const capacityHint = document.getElementById('capacity-hint');

    switchButtons.forEach(button => {
      button.addEventListener('click', function() {
        const vehicleType = this.getAttribute('data-type');

        switchButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');

        if (vehicleType === 'car') {
          vehicleTypeInput.value = 'CAR';
          form.action = '../scripts/manage_insurance.php';
          
          // Pokaż select dla samochodów, ukryj dla motocykli
          carBrandGroup.style.display = 'flex';
          motoBrandGroup.style.display = 'none';
          carBrandSelect.required = true;
          motoBrandSelect.required = false;
          
          capacityLabel.textContent = 'Pojemność silnika (cm³)';
          capacityInput.name = 'capacity';
          capacityInput.min = '500';
          capacityInput.max = '8000';
          capacityInput.removeAttribute('required');
          capacityHint.style.display = 'none';

          document.querySelectorAll('.car-only').forEach(el => {
            el.style.display = 'flex';
          });

          document.querySelectorAll('.motorcycle-only').forEach(el => {
            el.style.display = 'none';
          });

        } else {
          vehicleTypeInput.value = 'MOTORCYCLE';
          form.action = '../scripts/manage_motorcycle_insurance.php';
          
          // Pokaż select dla motocykli, ukryj dla samochodów
          carBrandGroup.style.display = 'none';
          motoBrandGroup.style.display = 'flex';
          carBrandSelect.required = false;
          motoBrandSelect.required = true;
          
          capacityLabel.innerHTML = 'Pojemność silnika (cm³) <span style="color:red;">*</span>';
          capacityInput.name = 'engine_capacity';
          capacityInput.min = '50';
          capacityInput.max = '2500';
          capacityInput.setAttribute('required', 'required');
          capacityHint.style.display = 'block';

          document.querySelectorAll('.car-only').forEach(el => {
            el.style.display = 'none';
          });

          document.querySelectorAll('.motorcycle-only').forEach(el => {
            el.style.display = 'flex';
          });
        }
      });
    });

    // Obsługa przycisku "Szukaj polisy" z historii
    document.querySelectorAll('.btn-use-search').forEach(button => {
      button.addEventListener('click', function() {
        const searchData = JSON.parse(this.getAttribute('data-search'));

        // Ustaw typ pojazdu
        const vehicleType = searchData.Vehicle_Type;
        const switchBtn = document.querySelector(`.switch-btn[data-type="${vehicleType === 'CAR' ? 'car' : 'motorcycle'}"]`);
        if (switchBtn) {
          switchBtn.click();

          // Poczekaj na przełączenie formularza
          setTimeout(() => {
            // Wypełnij wspólne pola
            if (searchData.DOB) document.getElementById('dob').value = searchData.DOB;
            if (searchData.Insurance_Start_Date) document.getElementById('insurance-date').value = searchData.Insurance_Start_Date;
            if (searchData.License_Date) document.getElementById('license-date').value = searchData.License_Date;
            if (searchData.Insurance_type) document.getElementById('insurance-type').value = searchData.Insurance_type;
            if (searchData.Use_type) document.getElementById('usage').value = searchData.Use_type;
            if (searchData.Production_Year) document.getElementById('year').value = searchData.Production_Year;

            if (vehicleType === 'CAR') {
              // Pola dla samochodów
              if (searchData.Brand_Name) document.getElementById('car-brand').value = searchData.Brand_Name;
              if (searchData.Body_Type) document.getElementById('typ_nadwozia').value = searchData.Body_Type;
              if (searchData.Engine_Capacity) document.getElementById('capacity').value = searchData.Engine_Capacity;
              if (searchData.Fuel_Type) document.getElementById('fuel').value = searchData.Fuel_Type;
              if (searchData.Last_accident !== null) document.getElementById('damage').value = searchData.Last_accident;
              if (searchData.Planned_mileage) document.getElementById('mileage').value = searchData.Planned_mileage;
            } else {
              // Pola dla motocykli
              if (searchData.Brand_Name) document.getElementById('moto-brand').value = searchData.Brand_Name;
              if (searchData.Engine_Capacity) document.getElementById('capacity').value = searchData.Engine_Capacity;
              if (searchData.Power_HP) document.getElementById('power').value = searchData.Power_HP;
              if (searchData.Motorcycle_Type) document.getElementById('motorcycle-type').value = searchData.Motorcycle_Type;
              if (searchData.Last_accident !== null) document.getElementById('moto-damage').value = searchData.Last_accident;
            }

            // Scroll do formularza
            document.getElementById('insurance-form').scrollIntoView({ behavior: 'smooth' });
          }, 100);
        }
      });
    });
  </script>
</body>
</html>