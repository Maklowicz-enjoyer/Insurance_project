<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Validate date of birth
    if (empty($_POST['dob'])) {
        $errors['dob'] = "Data urodzenia jest wymagana.";
    }

    // Validate insurance start date
    if (empty($_POST['insurance-date'])) {
        $errors['insurance-date'] = "Data rozpoczęcia ubezpieczenia jest wymagana.";
    }

    // Validate vehicle brand
    if (empty($_POST['brand'])) {
        $errors['brand'] = "Marka pojazdu jest wymagana.";
    } elseif (!preg_match("/^[a-zA-Z0-9\s]+$/", $_POST['brand'])) {
        $errors['brand'] = "Marka pojazdu może zawierać tylko litery i cyfry.";
    }

    // Validate insurance type
    if (empty($_POST['insurance-type'])) {
        $errors['insurance-type'] = "Typ ubezpieczenia jest wymagany.";
    }

    // Validate vehicle model
    if (empty($_POST['model'])) {
        $errors['model'] = "Model pojazdu jest wymagany.";
    }

    // Validate usage type
    if (empty($_POST['usage'])) {
        $errors['usage'] = "Typ użytkowania jest wymagany.";
    }

    // Validate production year
    if (empty($_POST['year'])) {
        $errors['year'] = "Rok produkcji jest wymagany.";
    } elseif (!filter_var($_POST['year'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1886, "max_range" => date("Y")]])) {
        $errors['year'] = "Podaj poprawny rok produkcji (1886 - obecny rok).";
    }

    // Validate license date
    if (empty($_POST['license-date'])) {
        $errors['license-date'] = "Data wydania prawa jazdy jest wymagana.";
    }

    // Validate engine capacity
    if (empty($_POST['capacity'])) {
        $errors['capacity'] = "Pojemność silnika jest wymagana.";
    } elseif (!filter_var($_POST['capacity'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 50, "max_range" => 10000]])) {
        $errors['capacity'] = "Podaj poprawną pojemność silnika (50-10000 cm³).";
    }

    // Validate damage-free years
    if (empty($_POST['damage'])) {
        $errors['damage'] = "Lata od ostatniej szkody są wymagane.";
    } elseif (!filter_var($_POST['damage'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]])) {
        $errors['damage'] = "Podaj poprawną liczbę lat od ostatniej szkody (0 lub więcej).";
    }

    // Validate fuel type
    if (empty($_POST['fuel'])) {
        $errors['fuel'] = "Rodzaj paliwa jest wymagany.";
    }

    // Validate mileage
    if (empty($_POST['mileage'])) {
        $errors['mileage'] = "Planowany kilometraż roczny jest wymagany.";
    }

    // Check if there are errors
    if (empty($errors)) {
        echo "Formularz przesłany pomyślnie!";

        // Process the form data here (e.g., save to database)
    } else {
        // Display errors
        foreach ($errors as $field => $error) {
            echo "<p>Błąd w polu $field: $error</p>";
        }
    }
    header("Location: login.php");
}
?>
