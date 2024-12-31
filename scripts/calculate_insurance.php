<?php
require 'db_connect.php';


try{
    if (empty($_POST['dob']) || empty($_POST['usage']) || empty($_POST['insurance-type'])) {
        die('Brak wymaganych danych!');
    }
    //Pobieranie danych z formularza
    $dob = $_POST['dob']; // Data urodzenia
    $damage = $_POST['damage']; // Liczba lat od ostatniego wypadku
    $usage = $_POST['usage']; // Typ użytkowania
    $type = $_POST['insurance-type']; //Typ ubezpieczenia


//Obliczanie wagi na podstawie wieku
function AgeWeight($birthDate){
    $age = date_diff(date_create($birthDate), date_create('now'))->y;

    if($age >= 18 && $age <= 24) return 1.7;
    if($age >= 25 && $age <= 30) return 1.5;
    if($age >= 31 && $age <= 40) return 1.2;
    if($age >= 41 && $age <= 60) return 0.8;
    if($age > 60) return 1.1;

    return 1.0; //Default weight
}
//Waga na podstawie typu nadwozia
function BodyTypeWeight($bodyType){
    $weight = [
        'SUV' => 1.3,
        'Van' => 1.4,
        'Sedan' => 1.0,
        'Hatchback' => 0.9,
        'Kombi' => 0.9,
        'Inne' => 1.1
    ];
    return $weight[$bodyType] ?? 1.0; //Default Weight
}
//Waga na podsatwie ostatniego wypadku
function LastAccidentWeight($yearsSinceAcc) {
    if ($yearsSinceAcc === null || $yearsSinceAcc === '') return 0.8; //brak wypadku
    if ($yearsSinceAcc === 0 ) return 1.5;
    if ($yearsSinceAcc <= 2) return 1.3;
    if ($yearsSinceAcc <= 5 ) return 1.1;

    return 1.0; // Powyżej 5 lat od wypadku
}

//Waga na podstawie tpyu użytkowania
function UsageTypeWeight($usageType){
    return $usageType === 'leasing' ? 0.9 : 1.1;
}

//Obliczanie ceny ubezpieczenia
function calculateInsurance($data){
    
    $ageWeight = AgeWeight($data['dob']);
    #$bodyTypeWeight = BodyTypeWeight($bodyType)
    $accidentWeigh = LastAccidentWeight($data['damage']);
    $usageWeight = UsageTypeWeight($data['usage']);

    $basePrice = 1000;

    $finalprice = $basePrice * $ageWeight * $accidentWeigh * $usageWeight;

    return round($finalprice, 2);
}


// Pobranie ofert z bazy danych
$stmt = $pdo->prepare("
    SELECT * 
    FROM Insurance 
    WHERE Insurance_type = :type AND Use_type = :usage
    "
);

$stmt->bindParam(':usage', $usage, PDO::PARAM_STR);    
$stmt->bindParam(':type', $type, PDO::PARAM_STR);
$stmt->execute();
$insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);// Pobieranie danych z bazy jako tablicę asocjacyjną



// Obliczanie cen dla każdej oferty
$results = [];
foreach ($insurances as $insurance) {
    //Losowanie ceny dla każdej z ofert
    $randomBasePrice = mt_rand(100,300);

    $yearsSinceAcc = $damage === '' ? null : intval($damage);
    $data = [
        'dob' => $dob,
        'damage' => $yearsSinceAcc,
        'usage' => $usage,
        'insurance-type' => $type
    ];
    $price = calculateInsurance($data) * ($randomBasePrice / 1000);

    $insurance['calculated_price'] = round($price, 2);

    $results[] = $insurance;
}
//Sortowanie cen
usort($results, function($a, $b){
    return $a['calculated_price'] <=> $b['calculated_price'];
});
session_start();
$_SESSION['vehicle_info'] = [
    'brand' => $_POST['brand'],
    'bodyType' => $_POST['bodyType'],
    'insurance-date' => $_POST['insurance-date']
];
// Przekierowanie wyników do pliku HTML
session_start();
$_SESSION['offers'] = $results;

//Przekierowanie na strone
header('Location: list.php');


} catch(PDOException $e){
    var_dump($e->getMessage());
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());
}
?>