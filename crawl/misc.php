<?php

	// const prefix = [
	// 	'restoran',
	// 	'restaurants'
	// ];
		
	function fetch($url){
		// Mendapatkan konten dari URL
		$opts = [
			'http' => [
				'header' => "User-Agent:MyAgent/1.0\r\n"
			]
		];

		$context = stream_context_create($opts);
		$html = file_get_contents($url, false, $context);

		return $html;	
	}
	
	function extractURL($url, $html){
		// Buat objek DOMDocument
		$dom = new DOMDocument();	
		
		libxml_use_internal_errors(true);

		// Memuat HTML ke dalam DOMDocument
		$dom->loadHTML($html);

		// Mengambil semua elemen <a>
		$links = $dom->getElementsByTagName('a');

		// Array untuk menyimpan semua nilai atribut href yang sesuai
		$filtered_href_attributes = array();

		// Mengambil host dari URL yang diberikan
		$parsed_url = parse_url($url);
		$base_host = $parsed_url['host'];

		// Iterasi semua elemen <a> dan ambil nilai atribut href
		foreach ($links as $link) {
		$href = $link->getAttribute('href');
		// Parsing URL dari atribut href
		$parsed_href = parse_url($href);
				
		
		// Jika URL yang ditemukan memiliki host dan host-nya adalah subdomain dari host dasar
		if (isset($parsed_href['host'])){
			if (strpos($parsed_href['host'], $base_host) !== false) {
				
				if (!preg_match("~^(?:f|ht)tps?:~i", $href)) {
					$href = "https:" . $href;
				}
				
				$filtered_href_attributes[] = $href;
			}
		}else {
			// Jika URL yang ditemukan tidak memiliki subdomain, tambahkan subdomain
			$new_href = "https://" . $base_host . $href;
			$filtered_href_attributes[] = $new_href;
		}				
	}
		
		return $filtered_href_attributes;
		
	}
	
	function storeD($data1, $data2){		
		
		require "koneksi.php";
		
		// Menyiapkan query SQL untuk menyimpan data
		$sql = "INSERT INTO tableD (du, u) VALUES (:du, :u)";
		$stmt = $pdo->prepare($sql);
		
		// Mengeksekusi query dengan mengganti parameter bind dengan nilai yang sesuai
		$stmt->bindParam(':du', $data1);
		$stmt->bindParam(':u', $data2);
		$stmt->execute();
		
		echo "1 data tableD ditambahkan<br>";
		
		// Menutup koneksi database
		$pdo = null;
	}
	
	function storeE($data1, $data2){
		require "koneksi.php";
		
		$sql = "SELECT COUNT(*) FROM tableE WHERE u = :data1 and v = :data2";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':data1', $data1);
		$stmt->bindParam(':data2', $data2);
		$stmt->execute();
		
		// Mengambil hasil kueri
		$result = $stmt->fetchColumn();
		
		// Memeriksa apakah data ditemukan atau tidak
		if ($result <= 0) {
			// Menyiapkan query SQL untuk menyimpan data
			$sql = "INSERT INTO tableE (u, v) VALUES (:u, :v)";
			$stmt = $pdo->prepare($sql);
			
			// Mengeksekusi query dengan mengganti parameter bind dengan nilai yang sesuai
			$stmt->bindParam(':u', $data1);
			$stmt->bindParam(':v', $data2);
			$stmt->execute();
			
			echo "1 data tableE ditambahkan<br>";
		}						
		
		// Menutup koneksi database
		$pdo = null;
	}
	
	function containsD($v){
		
		require "koneksi.php";
		
		$sql = "SELECT COUNT(*) FROM tableD WHERE u = :v";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(':v', $v);
		$stmt->execute();
		
		// Mengambil hasil kueri
		$result = $stmt->fetchColumn();

		// Menutup koneksi database
		$pdo = null;
		
		// Memeriksa apakah data ditemukan atau tidak
		if ($result > 0) {
			return true;
		} else {
			return false;
		}				
	}

	// function getOnlyPrefix($url)
	// {
	// 	if ()
	// }
	
?>
