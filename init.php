<?php
try {
    // Vytvoření připojení k SQLite databázi (soubor se vytvoří, pokud neexistuje)
    $db = new PDO("sqlite:profile.db");
    
    // Zapnutí vyhazování výjimek pro lepší detekci chyb
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vytvoření tabulky interests
    $query = "
        CREATE TABLE IF NOT EXISTS interests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )
    ";
    
    $db->exec($query);
    
} catch (PDOException $e) {
    die("Chyba připojení k databázi: " . $e->getMessage());
}
?>
