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
LIMIT 100;
EOT;
		$include = "%restaurants%";
		$exclude_1 = "%gallery%";
		$exclude_2 = "%reviews%";
		$exclude_3 = "%menus%";
		$exclude_4 = "%claim%";
		$exclude_5 = "%default_search%";
		$exclude_6 = "%baru-buka%";

		$stmt = $this->db->prepare($sql);
		$stmt->bind_param('sssssss', $include,
			$exclude_1,
			$exclude_2,
			$exclude_3,
			$exclude_4,
			$exclude_5,
			$exclude_6
		);
		$stmt->execute();

		$result = $stmt->get_result();

		return $result->fetch_all(MYSQLI_ASSOC);
    }

	private function save($data)
	{
		foreach ($data as $key => $value) {
			// var_dump($value['url'], $value['name']);

			$sql = <<<EOT
INSERT INTO restaurant (
	url,
	name,
	category,
	price_min,
	price_max,
	address,
	open_day_from,
	open_day_to,
	open_time_from,
	open_time_to,
	phone,
	rating_overall,
	rating_flavor,
	rating_atmosphere,
	rating_relevant,
	rating_service,
	rating_cleanliness
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);
EOT;

			$stmt = $this->db->prepare($sql);
			$stmt->bind_param('sssssssssssssssss',
				$value['url'],
				$value['name'],
				$value['category'],
				$value['price_min'],
				$value['price_max'],
				$value['address'],
				$value['open_day_from'],
				$value['open_day_to'],
				$value['open_time_from'],
				$value['open_time_to'],
				$value['phone'],
				$value['rating_overall'],
				$value['rating_flavor'],
				$value['rating_atmosphere'],
				$value['rating_relevant'],
				$value['rating_service'],
				$value['rating_cleanliness']
			);

			$stmt->execute();
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
				'open_day_from' => null,
				'open_day_to' => null,
				'open_time_from' => null,
				'open_time_to' => null,
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
		// loop until insert all data
		while (1) {
			// get data
			$data = $this->get();

			if (empty($data))
				break;

			// convert data from DOM to array
			$result = $this->extract($data);

			$result = $this->filter($result);

			// save data to database
			$this->save($result);
		}

		echo "Done";
    }
}

?>