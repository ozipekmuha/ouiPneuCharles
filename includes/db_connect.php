<?php

$host = 'localhost:8889';    
$db_name = 'ouipneu.fr';     
$username = 'root';         
$password = 'root';          
$charset = 'utf8mb4';        

// Options PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,          
];


$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

try {
   
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());

    throw new PDOException($e->getMessage(), (int)$e->getCode());
}


?>
