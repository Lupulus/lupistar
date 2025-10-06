<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php"); // Redirige immédiatement vers la page de connexion
    exit(); // Arrête l'exécution du script
}

// Connexion à la base de données via PDO
require_once 'co-bdd.php';

try {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $role = $stmt->fetchColumn();

    // Vérifier les rôles autorisés
    if ($role !== 'Admin' && $role !== 'SuperAdmin') {
        header("Location: /login.php"); // Redirige si l'utilisateur n'est pas admin
        exit(); // Empêche tout autre code d'être exécuté
    }
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}