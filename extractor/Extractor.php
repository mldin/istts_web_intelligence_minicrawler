<?php

// Import connection library
require 'Connection.php';

// Make class to convert html into structured table
class Extractor
{
	// db connection
	private $db;

	// initialize db connection
	public function __construct()
	{
		$connection = new Connection();
		$this->db = $connection->connection();
	}

	// get crawler result from db
    private function get()
	{
		// return only not synced
		// LIMIT 100 to prevent memory limit
        $sql = <<<EOT
SELECT *
FROM tableD as o
WHERE o.u NOT IN (
    SELECT url
    FROM restaurant as r
)
AND o.u LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
AND o.u NOT LIKE ?
LIMIT 100;
EOT;
		// Exclude unwanted URL
		$include = "%restaurants%";
		$exclude_1 = "%/gallery";
		$exclude_2 = "%/reviews/%";
		$exclude_3 = "%/menus";
		$exclude_4 = "%/claim";
		$exclude_5 = "%?filter_by=%";
		$exclude_6 = "%?default_search=%";
		$exclude_7 = "%/baru-buka";
		$exclude_8 = "%/new";
		$exclude_9 = "%/gallery/%";
		$exclude_10 = "%/gallery?page=%";
		$exclude_11 = "%?default_search%";

		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('ssssssssssss', $include,
			$exclude_1,
			$exclude_2,
			$exclude_3,
			$exclude_4,
			$exclude_5,
			$exclude_6,
			$exclude_7,
			$exclude_8,
			$exclude_9,
			$exclude_10,
			$exclude_11
		);
		$stmt->execute();

		$result = $stmt->get_result();

		return $result->fetch_all(MYSQLI_ASSOC);
    }

	private function save($data)
	{
		foreach ($data as $key => $value) {
			// echo '<pre>' , var_dump($value) , '</pre>';

			$sql = <<<EOT
INSERT INTO restaurant (
	url,
	name,
	category,
	price_min,
	price_max,
	address,
	phone,
	rating_overall,
	rating_flavor,
	rating_atmosphere,
	rating_relevant,
	rating_service,
	rating_cleanliness
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?);
EOT;

			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('sssssssssssss',
				$value['url'],
				$value['name'],
				$value['category'],
				$value['price_min'],
				$value['price_max'],
				$value['address'],
				$value['phone'],
				$value['rating_overall'],
				$value['rating_flavor'],
				$value['rating_atmosphere'],
				$value['rating_relevant'],
				$value['rating_service'],
				$value['rating_cleanliness']
			);

			$stmt->execute();

			$restaurantId = $this->db->insert_id;

			foreach ($value['facility'] as $kF => $vF) {
				$sql = <<<EOT
INSERT INTO facility (
	restaurant_id,
	facility
)
VALUES (?,?);
EOT;
				$stmt = $this->db->prepare($sql);
				$stmt->bind_param('ss',
					$restaurantId,
					$vF,
				);

				$stmt->execute();
			}

			foreach ($value['payment'] as $kP => $vP) {
				$sql = <<<EOT
INSERT INTO payment (
	restaurant_id,
	method
)
VALUES (?,?);
EOT;
				$stmt = $this->db->prepare($sql);
				$stmt->bind_param('ss',
					$restaurantId,
					$vP,
				);

				$stmt->execute();
			}

			foreach ($value['review'] as $kF => $vF) {
				$sql = <<<EOT
INSERT INTO review (
	restaurant_id,
	name,
	username,
	total_review,
	level,
	subject,
	rating,
	visit_date,
	price_from,
	price_to,
	review
)
VALUES (?,?,?,?,?,?,?,?,?,?,?);
EOT;

				$visitDate = isset($vF['visit_date']) ? $vF['visit_date']->format('Y-m-d') : null;
				$priceFrom = !empty($vF['price_from']) ? $vF['price_from'] : 0;
				$priceTo = !empty($vF['price_to']) ? $vF['price_to'] : 0;

				$stmt = $this->db->prepare($sql);
				$stmt->bind_param('sssssssssss',
					$restaurantId,
					$vF['name'],
					$vF['username'],
					$vF['total_review'],
					$vF['level'],
					$vF['subject'],
					$vF['rating'],
					$visitDate,
					$priceFrom,
					$priceTo,
					$vF['review']
				);

				$stmt->execute();
			}
		}
	}

	// extract data from DOM to array
	private function extract($data)
	{
		$result = [];
		foreach ($data as $key => $value) {
			$data = [
				'url' => null,
				'name' => null,
				'category' => null,
				'price_min' => 0,
				'price_max' => 0,
				'address' => null,
				'phone' => null,
				'rating_overall' => 0,
				'rating_flavor' => 0,
				'rating_atmosphere' => 0,
				'rating_relevant' => 0,
				'rating_service' => 0,
				'rating_cleanliness' => 0
			];

			// set internal error
			libxml_use_internal_errors(true);

			// make DOM object
			$dom = new DOMDocument();

			// load from db data
			$dom->loadHTML($value['du']);

			// make xpath to get element with address
			$xpath = new DOMXpath($dom);

			// Name
			$eName = $xpath->query('//*[@id="height-mark"]/header/div[1]/h1/span');
			if ($eName->length > 0)
				$data['name'] = $eName[0]->nodeValue;

			// Category
			$eCategory = $xpath->query('//*[@id="height-mark"]/header/div[1]/span/span');
			if ($eCategory->length > 0)
				$data['category'] = $eCategory[0]->nodeValue;

			// Price
			$ePrice = $xpath->query('//*[@id="avg-price"]');
			if ($ePrice->length > 0) {
				$sPrice = $ePrice[0]->nodeValue;
				// if contains <
				if (str_contains($sPrice, 'Di bawah'))
					$data['price_max'] = preg_replace('/[^0-9]+/', '', $sPrice);

				// if contains 'Di atas'
				if (str_contains($sPrice, 'Di atas'))
					$data['price_min'] = preg_replace('/[^0-9]+/', '', $sPrice);

				// if price in range get min - max
				$sPrice = explode("-", $sPrice);
				if (count($sPrice) > 1) {
					$data['price_min'] = preg_replace('/[^0-9]+/', '', $sPrice[0]);
					$data['price_max'] = preg_replace('/[^0-9]+/', '', $sPrice[1]);
				}
			}

			// Address
			$eAddress = $xpath->query('//*[@id="height-mark"]/article/p[1]/span[1]');
			if ($eAddress->length > 0)
				$data['address'] = $eAddress[0]->nodeValue;

			// Phone, separate with comma
			$ePhone = $xpath->query('//*[@id="height-mark"]/article/p[2]/span[1]/span[3]');
			if ($ePhone->length > 0) {
				$phone = [];
				foreach ($ePhone as $x => $p) {
					$phone[] = preg_replace('/\s+/', '', $p->nodeValue);
				}

				$data['phone'] = implode(",", $phone);
			}

			// overall rating
			$eRatingOverall = $xpath->query('//*[@id="height-mark"]/header/div[2]/div/span[1]');
			if ($eRatingOverall->length > 0)
				$data['rating_overall'] = $eRatingOverall[0]->nodeValue;

			// flavor rating
			$eRatingFlavor = $xpath->query('//*[@id="height-mark"]/article/div[1]/div/div[1]/div[2]');
			if ($eRatingFlavor->length > 0)
				$data['rating_flavor'] = $eRatingFlavor[0]->nodeValue;

			// atmosphere rating
			$eRatingAtmosphere = $xpath->query('//*[@id="height-mark"]/article/div[1]/div/div[2]/div[2]');
			if ($eRatingAtmosphere->length > 0)
				$data['rating_atmosphere'] = $eRatingAtmosphere[0]->nodeValue;

			// relevant rating
			$eRatingRelevant = $xpath->query('//*[@id="height-mark"]/article/div[1]/div/div[3]/div[2]');
			if ($eRatingRelevant->length > 0)
				$data['rating_relevant'] = $eRatingRelevant[0]->nodeValue;

			// service rating	
			$eRatingService = $xpath->query('//*[@id="height-mark"]/article/div[1]/div/div[4]/div[2]');
			if ($eRatingService->length > 0)
				$data['rating_service'] = $eRatingService[0]->nodeValue;

			// cleanliness rating
			$eRatingCleanliness = $xpath->query('//*[@id="height-mark"]/article/div[1]/div/div[5]/div[2]');
			if ($eRatingCleanliness->length > 0)
				$data['rating_cleanliness'] = $eRatingCleanliness[0]->nodeValue;

			$data['facility'] = [];	
			$eFacility = $xpath->query('//*[@id="content-detail"]/div[1]/div/div[2]');
			if ($eFacility->length > 0) {
				$iFacility = $eFacility->item(0)->childNodes->item(3)->childNodes;
				foreach ($iFacility as $iFItem) {
					if ($iFItem instanceof DOMElement)
						if ($iFItem->childNodes->item(1)->getAttribute('checked'))
							$data['facility'][] = rtrim(ltrim($iFItem->nodeValue));
				}
			}

			$data['payment'] = [];	
			$ePayment = $xpath->query('//*[@id="content-detail"]/div[1]/div/div[1]/ul/li[3]');

			if (empty($ePayment[0]))
				$ePayment = $xpath->query('//*[@id="content-detail"]/div[1]/div/div[1]/ul/li[2]');

			if (!str_contains($ePayment[0]->nodeValue, 'Pembayaran'))
				$ePayment = $xpath->query('//*[@id="content-detail"]/div[1]/div/div[1]/ul/li[4]');
			
			if ($ePayment->length > 0) {
				if ($ePayment[0]->childNodes->item(2)) {
					$iPayment = explode(', ', $ePayment[0]->childNodes->item(2)->nodeValue);
					foreach ($iPayment as $kP => $vP) {
						if (!empty($vP))
							$data['payment'][] = $vP;
					}
				}
			}

			$data['review'] = [];	
			$eReview = $xpath->query('//*[@id="list_reviews"]');
			if ($eReview->length > 0) {
				foreach ($eReview[0]->childNodes as $rK => $vK) {
					$review = [];
					if ($vK instanceof DOMElement) {
						$boxIdA = $vK->childNodes->item(1);

						if (!empty($boxIdA)) {
							$boxId = $boxIdA->childNodes->item(3);

							if (!empty($boxId->childNodes)) {
								$username = $boxId->childNodes->item(1)
									->childNodes->item(1)
									->childNodes->item(1)
									->getAttribute('href');
	
								$review['username'] = substr($username, 1);

								if (!empty($review['username'])) {
									$name = $boxId->childNodes->item(1)
										->childNodes->item(1)
										->nodeValue;
		
									$review['name'] = rtrim(ltrim($name));
		
									$bReview = $boxId->childNodes->item(1);

									$totalReview = $bReview->childNodes->item(3);
									if (!str_contains($totalReview->nodeValue, 'Review')) {
										$totalReview = $bReview->childNodes->item(5);
									}	
		
									$review['total_review'] = str_replace(' Review', '', $totalReview->nodeValue);
		
									$bLevel = $boxId->childNodes->item(1);

									$level = $bLevel->childNodes->item(5);
									if (!str_contains($level->nodeValue, 'Level')) {
										$level = $bLevel->childNodes->item(7);
									}
		
									$review['level'] = str_replace('Level ', '', $level->nodeValue);
								}
							}
						}

						if (!empty($review['username'])) {
							$boxReview = $vK->childNodes->item(3);
							if (!empty($boxReview->childNodes) && $boxReview->tagName == 'div') {
								
								if (!empty($boxReview->childNodes->item(3))) {
									$subject = $boxReview->childNodes->item(3)
										->nodeValue;

									$review['subject'] = $subject;
								}

								$rRating = $boxReview->childNodes->item(1)
									->nodeValue;

								$review['rating'] = preg_replace('/[^0-9,.]+/', '', $rRating);

								$xBReview = $boxReview->childNodes->item(5);

								$xReview = $xBReview->childNodes->item(1);
								if (empty($xReview)) {
									$xBReview = $boxReview->childNodes->item(7);
									$xReview = $xBReview->childNodes->item(1);
								}

								if (!empty($xReview)) {
									$tReview = $xReview->childNodes->item(1);

									$ctReview = $tReview->childNodes->item(0);
									while (!empty($ctReview)) {
										if ($ctReview instanceof DOMElement) {
											if ($ctReview->tagName === 'br') {
												$space = $dom->createTextNode(',');

												$tReview->replaceChild($space, $ctReview);
												$ctReview = $space;
											}
										}

										$ctReview = $ctReview->nextSibling;
									}

									$review['review'] = ltrim(rtrim($tReview->nodeValue));
								}

								$boxDate = $boxReview->childNodes->item(7);

								if (!empty($boxDate)) {
									$bVisitDate = $boxDate->childNodes->item(3);

									if (empty($bVisitDate)) {
										$boxDate = $boxReview->childNodes->item(9);
										$bVisitDate = $boxDate->childNodes->item(1);
									}

									if ($bVisitDate instanceof DOMText)
										$bVisitDate = $boxDate->childNodes->item(1);

									if (!empty($bVisitDate)) {
										$visitDate = $bVisitDate->childNodes->item(2);

										if (empty($visitDate)) {
											$bVisitDate = $boxDate->childNodes->item(3);
											$visitDate = $bVisitDate->childNodes->item(2);
										}

										$tmpVisitDate = trim($visitDate->nodeValue);
										if (empty($tmpVisitDate)) {
											$visitDate = $boxReview->childNodes->item(11)
												->childNodes->item(1)
												->childNodes->item(2);
										}

										if (!empty($visitDate)) {
											$monthMapping = [
												'Januari' => 'January',
												'Februari' => 'February',
												'Maret' => 'March',
												'April' => 'April',
												'Mei' => 'May',
												'Juni' => 'June',
												'Juli' => 'July',
												'Agustus' => 'August',
												'September' => 'September',
												'Oktober' => 'October',
												'November' => 'November',
												'Desember' => 'December',
											];
				
											$dateParts = explode(' ', $visitDate->nodeValue);
											$day = $dateParts[0];
											$month = $dateParts[1];
				
											$englishMonth = $monthMapping[$month];
											$review['visit_date'] = DateTime::createFromFormat('d F Y', $day . ' ' . $englishMonth . ' ' . $dateParts[2]);
										}
									}
									
									$pBox = $boxDate->childNodes->item(6);
									if (empty($pBox)) {
										$pBox = $boxDate->childNodes->item(4);
									}
										
									if (empty($pBox)) {
										$boxDate = $boxReview->childNodes->item(9);
										$pBox = $boxDate->childNodes->item(4);
									}

									if (empty($pBox)) {
										$pBox = $boxReview->childNodes->item(11)
											->childNodes->item(4);
									}

									$price = $pBox->nodeValue;

									$price = str_replace('Harga per orang: ', '', $price);
									$price = str_replace('Rp. ', '', $price);
									$price = str_replace('.', '', $price);

									// if contains <
									if (str_contains($price, '<'))
										$review['price_to'] = preg_replace('/[^0-9]+/', '', $price);

									// if contains '>'
									if (str_contains($price, '>'))
										$review['price_from'] = preg_replace('/[^0-9]+/', '', $price);

									// if price in range get min - max
									$price = explode("-", $price);
									if (count($price) > 1) {
										$review['price_from'] = preg_replace('/[^0-9]+/', '', $price[0]);
										$review['price_to'] = preg_replace('/[^0-9]+/', '', $price[1]);
									}
								}
							}
						}
					}

					if (!empty($review))
						$data['review'][] = $review;
				}
			}

			// if any element, save to db
			// it means a product page
			if (!empty($data)) {
				$data['url'] = $value['u'];
				$result[] = $data;
			}
		}

		return $result;
	}

	private function filter($data)
	{
		$filtered = array_filter($data, function($x) {
			if (!empty($x['name']))
				return $x;
		});

		return $filtered;
	}

    public function run()
	{
		// $this->db->begin_transaction();
		// loop until insert all data
		while (1) {
			// get data
			$data = $this->get();

			if (empty($data))
				break;
			// else
				// var_dump($data[0]['u']);

			// convert data from DOM to array
			$result = $this->extract($data);

			// var_dump($result); die;

			$result = $this->filter($result);

			// save data to database
			$this->save($result);
		}

		// $this->db->rollback();
		echo "Done";
    }
}

?>