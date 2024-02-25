<?php
	//PART 1: dapat URL -- akses page source
	
	// URL yang ingin Anda baca
	$url = "https://www.azlyrics.com/";

	// Mendapatkan konten dari URL
	$html = file_get_contents($url);   //ini mendapatkan seluruh page HTML

	//---------------------------------------

	//PART 2: dari teks html yang diperoleh, akses semua href yang ada 	

	// Buat objek DOMDocument
	$dom = new DOMDocument();

	// Matikan error PHP yang dihasilkan oleh HTML yang tidak valid
	libxml_use_internal_errors(true);

	// Memuat HTML ke dalam DOMDocument
	$dom->loadHTML($html);

	// Mengambil semua elemen <a>
	$links = $dom->getElementsByTagName('a');      //ini adalah array: menyimpan semua <a>

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
		if (isset($parsed_href['host'])){   //kalau dia punya host
			if (strpos($parsed_href['host'], $base_host) !== false) {  //kalau hostnya sama
				
				if (!preg_match("~^(?:f|ht)tps?:~i", $href)) {         //ini menambahkan https, kalau tidak ada https-nya
					$href = "https:" . $href;                 
				}
				
				$filtered_href_attributes[] = $href;                   //ini berisi link dengan host yang sama dan sudah dilengkapi https:
			}
		}else {
			// Jika URL yang ditemukan tidak memiliki subdomain, tambahkan subdomain
			$new_href = "https://" . $base_host . $href;
			$filtered_href_attributes[] = $new_href;
		}				
	}

	// Menampilkan semua nilai atribut href yang sesuai
	echo "Nilai atribut href dari elemen <a> yang berada dalam subdomain:<br>";
	foreach ($filtered_href_attributes as $href) {
		echo $href . "<br>";
	}
?>
