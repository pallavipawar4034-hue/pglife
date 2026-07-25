<?php
header("Access-Control-Allow-Origin: *");
session_start();

header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

require "../includes/database_connect.php";

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

if (!isset($_GET["city"])) {
    echo json_encode([]);
    return;
}

$city_name = mysqli_real_escape_string($conn, $_GET["city"]);

// Fix: Handle both spelling variations for Bengaluru/Bangalore
if (strcasecmp($city_name, "bengaluru") === 0 || strcasecmp($city_name, "bangalore") === 0) {
    $sql_city = "SELECT id FROM cities WHERE name = 'Bengaluru' OR name = 'Bangalore' LIMIT 1";
} else {
    $sql_city = "SELECT id FROM cities WHERE name = '$city_name' LIMIT 1";
}

$result_city = mysqli_query($conn, $sql_city);
if (!$result_city || mysqli_num_rows($result_city) == 0) {
    echo json_encode([]);
    return;
}
$city_row = mysqli_fetch_assoc($result_city);
$city_id = $city_row['id'];

$sql_2 = "SELECT * FROM properties WHERE city_id = '$city_id'";
$result_2 = mysqli_query($conn, $sql_2);
if (!$result_2) {
    echo json_encode([]);
    return;
}
$properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);

$sql_3 = "SELECT iup.* 
            FROM interested_users_properties iup
            INNER JOIN properties p ON iup.property_id = p.id
            WHERE p.city_id = '$city_id'";
$result_3 = mysqli_query($conn, $sql_3);
if (!$result_3) {
    echo json_encode([]);
    return;
}
$interested_users_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);

$new_properties = array();
foreach ($properties as $property) {
    $property_images = glob("../img/properties/" . $property['id'] . "/*");
    
    if (!empty($property_images)) {
        // Fix: Explicitly pull the first image from the glob array
        $property_image = "img/properties/" . $property['id'] . "/" . basename($property_images[0]);
    } else {
        $property_image = "img/properties/default.png"; 
    }

    $interested_users_count = 0;
    $is_interested = false;
    foreach ($interested_users_properties as $interested_user_property) {
        if ($interested_user_property['property_id'] == $property['id']) {
            $interested_users_count++;

            if ($interested_user_property['user_id'] == $user_id) {
                $is_interested = true;
            }
        }
    }
    $property['interested_users_count'] = $interested_users_count;
    $property['is_interested'] = $is_interested;
    $property['image'] = $property_image;
    $new_properties[] = $property;
}

echo json_encode($new_properties);
?>
