<?php
	// Konfigurasi database
	$host = 'localhost';
	$dbname = 'crawler';
	$username = 'root';
	$password = '';

	try {
		// Membuat koneksi ke database menggunakan PDO
		$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
		
		// Set mode error menjadi exception
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
	} catch (PDOException $e) {
		echo "Error: " . $e->getMessage();
	}

	
	
?>