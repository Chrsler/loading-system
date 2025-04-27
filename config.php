<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "college_management";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: ". $conn->connect_error);
}

function sanitize($data)
{
    global $conn;
    return $conn->real_escape_string(trim($data));
}
?>