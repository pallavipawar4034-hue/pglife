<?php
session_start();
require "includes/database_connect.php";

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
$city_name = isset($_GET["city"]) ? mysqli_real_escape_string($conn, $_GET["city"]) : "Delhi";

// Fetch city dynamic record mapping
$sql_1 = "SELECT * FROM cities WHERE LOWER(name) = LOWER('$city_name')";
$result_1 = mysqli_query($conn, $sql_1);
$city = mysqli_fetch_assoc($result_1);

if (!$city) {
    echo "Sorry! We do not have any PG listed in this city currently.";
    return;
}
$city_id = $city['id'];

// Get 1 to 9 mapped sequential properties row
$sql_2 = "SELECT * FROM properties WHERE city_id = $city_id";
$result_2 = mysqli_query($conn, $sql_2);
$properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);

// Get interested metrics data row mapping list
$sql_3 = "SELECT * FROM interested_users_properties iup 
          INNER JOIN properties p ON iup.property_id = p.id 
          WHERE p.city_id = $city_id";
$result_3 = mysqli_query($conn, $sql_3);
$interested_users_properties = [];
if ($result_3) {
    $interested_users_properties = mysqli_fetch_all($result_3, MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Best PGs in <?= htmlspecialchars($city_name) ?> | PG Life</title>
    <?php include "includes/head_links.php"; ?>
    <link href="css/property_list.css" rel="stylesheet" />
</head>

<body>
    <?php include "includes/header.php"; ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($city_name); ?></li>
        </ol>
    </nav>

    <div class="page-container">
        <div class="filter-bar row justify-content-around">
            <div class="col-auto" data-toggle="modal" data-target="#filter-modal"><img src="img/filter.png" alt="filter" /> <span>Filter</span></div>
            <div class="col-auto"><img src="img/desc.png" alt="sort-desc" /> <span>Highest rent first</span></div>
            <div class="col-auto"><img src="img/asc.png" alt="sort-asc" /> <span>Lowest rent first</span></div>
        </div>

        <?php 
        if (empty($properties)) {
            echo "<div class='text-center my-5'><h3>No PG Listed in this region yet!</h3></div>";
        }
        foreach ($properties as $property) {
            // Mapping folder structure 1 to 9 logic dynamically safely
            $property_images = glob("img/properties/" . $property['id'] . "/*");
            $main_img = !empty($property_images) ? $property_images[0] : "img/unisex.png";
        ?>
            <!-- Clickable Row Redirection Path Target Fix -->
            <div class="property-card property-id-<?= $property['id'] ?> row mb-4">
                <div class="image-container col-md-4">
                    <a href="property_detail.php?property_id=<?= $property['id'] ?>">
                        <img src="<?= $main_img ?>" class="img-fluid" onerror="this.src='img/unisex.png'" />
                    </a>
                </div>
                <div class="content-container col-md-8">
                    <div class="row no-gutters justify-content-between">
                        <?php
                        $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                        $total_rating = round($total_rating, 1);
                        ?>
                        <div class="star-container" title="<?= $total_rating ?>">
                            <?php
                            for ($i = 0; $i < 5; $i++) {
                                if ($total_rating >= $i + 0.8) { echo '<i class="fas fa-star"></i>'; }
                                elseif ($total_rating >= $i + 0.3) { echo '<i class="fas fa-star-half-alt"></i>'; }
                                else { echo '<i class="far fa-star"></i>'; }
                            }
                            ?>
                        </div>
                        <div class="interested-container">
                            <?php
                            $interested_users_count = 0;
                            $is_interested = false;
                            foreach ($interested_users_properties as $interested_user_property) {
                                if ($interested_user_property['property_id'] == $property['id']) {
                                    $interested_users_count++;
                                    if ($interested_user_property['user_id'] == $user_id) { $is_interested = true; }
                                }
                            }
                            echo $is_interested ? '<i class="is-interested-image fas fa-heart"></i>' : '<i class="is-interested-image far fa-heart"></i>';
                            ?>
                            <div class="interested-text"><span class="interested-user-count"><?= $interested_users_count ?></span> interested</div>
                        </div>
                    </div>
                    <div class="detail-container">
                        <div class="property-name"><a href="property_detail.php?property_id=<?= $property['id'] ?>"><?= htmlspecialchars($property['name']) ?></a></div>
                        <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
                        <div class="property-gender">
                            <?php
                            if ($property['gender'] == "male") { echo '<img src="img/male.png">'; }
                            elseif ($property['gender'] == "female") { echo '<img src="img/female.png">'; }
                            else { echo '<img src="img/unisex.png">'; }
                            ?>
                        </div>
                    </div>
                    <div class="row no-gutters mt-3">
                        <div class="rent-container col-6">
                            <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                            <div class="rent-unit">per month</div>
                        </div>
                        <div class="button-container col-6">
                            <a href="property_detail.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">View details</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>

    <?php
    include "includes/signup_modal.php";
    include "includes/login_modal.php";
    include "includes/footer.php";
    ?>
</body>
</html>
