<?php
session_start();
require "includes/database_connect.php";
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
$city_name = isset($_GET["city"]) ? mysqli_real_escape_string($conn, $_GET["city"]) : "";
$gender_filter = isset($_GET["gender"]) ? mysqli_real_escape_string($conn, $_GET["gender"]) : "";
$sort_order = isset($_GET["sort"]) ? mysqli_real_escape_string($conn, $_GET["sort"]) : "";
if ($city_name == "") {
    echo "<div class='text-center my-5'><h3>No city selected.</h3></div>";
    return;
}
// Get city
$sql_1 = "SELECT * FROM cities WHERE LOWER(name) = LOWER('$city_name')";
$result_1 = mysqli_query($conn, $sql_1);
if (!$result_1) {
    echo "<div class='text-center my-5'><h3>Something went wrong!</h3></div>";
    return;
}
$city = mysqli_fetch_assoc($result_1);
if (!$city) {
    echo "<div class='text-center my-5'><h3>Sorry! No PGs listed in this city.</h3></div>";
    return;
}
$city_id = $city['id'];
// Build property query with gender filter and sort
$where_clause = "city_id = $city_id";
if ($gender_filter != "") {
    $where_clause .= " AND gender = '$gender_filter'";
}
$order_clause = "";
if ($sort_order == "asc") {
    $order_clause = " ORDER BY rent ASC";
} elseif ($sort_order == "desc") {
    $order_clause = " ORDER BY rent DESC";
}
$sql_2 = "SELECT * FROM properties WHERE $where_clause" . $order_clause;
$result_2 = mysqli_query($conn, $sql_2);
if (!$result_2) {
    echo "<div class='text-center my-5'><h3>Something went wrong!</h3></div>";
    return;
}
$properties = mysqli_fetch_all($result_2, MYSQLI_ASSOC);
// Get interested users for this city
$sql_3 = "SELECT iup.* 
            FROM interested_users_properties iup
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
            <li class="breadcrumb-item active"><?= htmlspecialchars($city_name); ?></li>
        </ol>
    </nav>
    <div class="page-container">
        <!-- Filter & Sort Bar -->
        <div class="filter-bar row justify-content-around mb-4">
            <div class="col-auto" data-toggle="modal" data-target="#filter-modal" style="cursor:pointer;">
                <img src="img/filter.png" alt="filter" />
                <span>Filter</span>
            </div>
            <div class="col-auto" onclick="window.location.href='property_list.php?city=<?= urlencode($city_name) ?>&gender=<?= urlencode($gender_filter) ?>&sort=desc'" style="cursor:pointer;">
                <img src="img/desc.png" alt="sort-desc" />
                <span>Highest rent first</span>
            </div>
            <div class="col-auto" onclick="window.location.href='property_list.php?city=<?= urlencode($city_name) ?>&gender=<?= urlencode($gender_filter) ?>&sort=asc'" style="cursor:pointer;">
                <img src="img/asc.png" alt="sort-asc" />
                <span>Lowest rent first</span>
            </div>
        </div>
        <?php if (count($properties) == 0) { ?>
            <div class="no-property-container">
                <p>No PG to list</p>
            </div>
        <?php } ?>
        <?php foreach ($properties as $property) {
            $property_images = glob("img/properties/" . $property['id'] . "/*");
            $main_img = !empty($property_images) ? $property_images[0] : "img/unisex.png";
        ?>
            <div class="property-card property-id-<?= $property['id'] ?> row mb-4">
                <div class="image-container col-md-4">
                    <a href="property_detail.php?property_id=<?= $property['id'] ?>">
                        <img src="<?= $main_img ?>" class="img-fluid" />
                    </a>
                </div>
                <div class="content-container col-md-8">
                    <div class="row no-gutters justify-content-between">
                        <?php
                        $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
                        $total_rating = round($total_rating, 1);
                        ?>
                        <div class="star-container" title="<?= $total_rating ?>">
                            <?php for ($i = 0; $i < 5; $i++) {
                                if ($total_rating >= $i + 0.8) { ?>
                                    <i class="fas fa-star"></i>
                                <?php } elseif ($total_rating >= $i + 0.3) { ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php } else { ?>
                                    <i class="far fa-star"></i>
                                <?php }
                            } ?>
                        </div>
                        <div class="interested-container">
                            <?php
                            $interested_users_count = 0;
                            $is_interested = false;
                            foreach ($interested_users_properties as $iup) {
                                if ($iup['property_id'] == $property['id']) {
                                    $interested_users_count++;
                                    if ($iup['user_id'] == $user_id) {
                                        $is_interested = true;
                                    }
                                }
                            }
                            ?>
                            <i class="is-interested-image <?= $is_interested ? 'fas' : 'far' ?> fa-heart" property_id="<?= $property['id'] ?>"></i>
                            <div class="interested-text">
                                <span class="interested-user-count"><?= $interested_users_count ?></span> interested
                            </div>
                        </div>
                    </div>
                    <div class="detail-container">
                        <div class="property-name">
                            <a href="property_detail.php?property_id=<?= $property['id'] ?>"><?= htmlspecialchars($property['name']) ?></a>
                        </div>
                        <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
                        <div class="property-gender">
                            <?php if ($property['gender'] == "male") { ?>
                                <img src="img/male.png" />
                            <?php } elseif ($property['gender'] == "female") { ?>
                                <img src="img/female.png" />
                            <?php } else { ?>
                                <img src="img/unisex.png" />
                            <?php } ?>
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
    <!-- Filter Modal -->
    <div class="modal fade" id="filter-modal" tabindex="-1" role="dialog" aria-labelledby="filter-heading" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="filter-heading">Filters</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5>Gender</h5>
                    <hr />
                    <div class="filter-gender-options">
                        <button class="btn btn-outline-dark filter-gender-btn <?= ($gender_filter == "" || $gender_filter == "all") ? 'btn-active' : '' ?>" data-gender="">No Filter</button>
                        <button class="btn btn-outline-dark filter-gender-btn <?= ($gender_filter == "unisex") ? 'btn-active' : '' ?>" data-gender="unisex">Unisex</button>
                        <button class="btn btn-outline-dark filter-gender-btn <?= ($gender_filter == "male") ? 'btn-active' : '' ?>" data-gender="male">Male</button>
                        <button class="btn btn-outline-dark filter-gender-btn <?= ($gender_filter == "female") ? 'btn-active' : '' ?>" data-gender="female">Female</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button data-dismiss="modal" class="btn btn-success">Okay</button>
                </div>
            </div>
        </div>
    </div>
    <?php
    include "includes/signup_modal.php";
    include "includes/login_modal.php";
    include "includes/footer.php";
    ?>
    <script type="text/javascript" src="js/property_list.js"></script>
    <script>
        // Filter gender button click handler
        $(document).ready(function() {
            $('.filter-gender-btn').on('click', function() {
                var gender = $(this).data('gender');
                var cityName = '<?= urlencode($city_name) ?>';
                var sortOrder = '<?= urlencode($sort_order) ?>';
                var url = 'property_list.php?city=' + cityName;
                if (gender != '') {
                    url += '&gender=' + gender;
                }
                if (sortOrder != '') {
                    url += '&sort=' + sortOrder;
                }
                window.location.href = url;
            });
        });
    </script>
</body>
</html>
