<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "marsrover";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$humidity = $_POST['humidity'];
$temperature = $_POST['temperature'];
$mq2_lpg = $_POST['mq2_lpg'];
$mq2_methane = $_POST['mq2_methane'];
$mq135_nh3 = $_POST['mq135_nh3'];
$mq135_co2 = $_POST['mq135_co2'];
$mq7_co = $_POST['mq7_co'];
$mq8_h2 = $_POST['mq8_h2'];

$sql = "INSERT INTO sensor_data (humidity, temperature, mq2_lpg, mq2_methane, mq135_nh3, mq135_co2, mq7_co, mq8_h2)
VALUES ('$humidity', '$temperature', '$mq2_lpg', '$mq2_methane', '$mq135_nh3', '$mq135_co2', '$mq7_co', '$mq8_h2')";

if ($conn->query($sql) === TRUE) {
  echo "✅ Data inserted successfully.";
} else {
  echo "❌ Error: " . $conn->error;
}

$conn->close();
?>
