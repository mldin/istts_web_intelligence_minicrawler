<?php

	require 'Queue.php';
	require 'misc.php';
	
	// https://pergikuliner.com/restaurants/surabaya/onni-house-wonokromo/
	$S0 = $_POST["url"];
	
	$Q = new Queue();
	$Q->enqueue($S0);
	
	$counter = 0;
	while (!$Q->isEmpty()) {
	// while (!$Q->isEmpty() && $counter < 1000) {
		$u = $Q->dequeue();   //dapatkan sebuah URL dari Q
		$du = fetch($u);      //ambil teks HTML-nya
		
		if (trim($du)!=""){   //kalau dokumen HTML tersebut tidak kosong
			storeD($du, $u);  //simpan ke dalam D
		
			$L = [];
			$L = extractURL($u, $du);  //ekstrak semua href "bersih" dari d(u)
			
			foreach ($L as $v) {
				storeE($u, $v);
				
				if (!$Q->contains($v) && !containsD($v)) {
					$Q->enqueue($v);
				}
			}
			
			$counter++;
		}		
	}

	echo "Selesai";