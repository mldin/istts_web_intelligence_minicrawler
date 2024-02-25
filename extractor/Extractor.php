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
        $sql = <<<EOT
SELECT *
FROM tableD as o
WHERE o.u NOT IN (
    SELECT url
    FROM restaurant as r
)
LIMIT 100;
EOT;
		$stmt = $this->db->query($sql);
		return $stmt->fetch_all(MYSQLI_ASSOC);
    }

	private function save($data)
	{
// 		foreach ($data as $key => $value) {
// 			$sql = <<<EOT
// SELECT *
// FROM tableD as o
// WHERE o.u NOT IN (
//     SELECT url
//     FROM restaurant as r
// )
// LIMIT 3;
// EOT;
// 		}
	}

	// extract data from DOM to array
	private function extract($data)
	{
		$result = [];
		foreach ($data as $key => $value) {
			$data = [];

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
					$data['price_max'] = preg_replace('/[^0-9]+/', '', $tPrice);

				// if contains 'Di atas'
				if (str_contains($sPrice, 'Di atas'))
					$data['price_min'] = preg_replace('/[^0-9]+/', '', $tPrice);

				// if price in range get min - max
				$tPrice = explode("-", $sPrice);
				if (count($tPrice) > 1) {
					$data['price_min'] = preg_replace('/[^0-9]+/', '', $tPrice[0]);
					$data['price_max'] = preg_replace('/[^0-9]+/', '', $tPrice[1]);
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

			// if any element, save to db
			// it means a product page
			if (!empty($data)) {
				$data['url'] = $value['u'];
				$result[] = $data;
			}
		}

		return $result;
	}

    public function run()
	{
		// get data
		$data = $this->get();

		// convert data from DOM to array
		$result = $this->extract($data);

		print_r($result); die;

		// save data to database
		$this->save($result);
    }
}

?>