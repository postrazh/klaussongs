<?php

$servername = "localhost";
$username = "i2042577_wp4";
$password = "D.2EX5bxhCAIoQaxFqB96";
$dbname = "i2042577_wp4";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "SELECT carat, clarity, color FROM wp_woocommerce_attribute_taxonomies";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "carat: " . $row["carat"]. " - color: " . $row["color"]. " " . $row["clarity"]. "<br>";
    }
} else {
    echo "0 results";
}
$conn->close();

?>