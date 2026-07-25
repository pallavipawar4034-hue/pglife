<?php
$db_host = "sql306.infinityfree.com";
$db_user = "if0_42487701";
$db_pass = "Survi2105"; 
$db_name = "if0_42487701_pglife";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>


