<?php
require 'db_connect.php';


try{
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
    if ($yearsSinceAcc === null) return 0.8;
    if ($yearsSinceAcc == 0 ) return 1.5;
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
    $birthDate = $data['dob'];
    #$bodyType = $data['']
    $yearsSinceAcc = $data['damage'];
    $usageType = $data['usage'];

    $ageWeight = AgeWeight($birthDate);
    #$bodyTypeWeight = BodyTypeWeight($bodyType)
    $accidentWeigh = LastAccidentWeight($yearsSinceAcc);
    $usageWeight = UsageTypeWeight($usageType);

    $basePrice = 1000;

    $finalprice = $basePrice * $ageWeight * $accidentWeigh * $usageWeight;

    return round($finalprice, 2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $formData = [
        'dob' => $_POST['dob'],
        'damage' => $_POST['damage'],
        'usage' => $_POST['usage']
    ];
    
    $price = calculateInsurance($formData);
    echo "Cena ubezpieczenia: $price zł";
}
} catch(PDOException $e){
    var_dump($e->getMessage());
    // Handle connection errors
    die("Database connection failed: " . $e->getMessage());
}
?>