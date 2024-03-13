<?php
class Queue {
    private $queue;

    public function __construct() {
        $this->queue = array();
    }

    // Menambahkan elemen ke antrian (enqueue)
    public function enqueue($item) {
        array_push($this->queue, $item);
    }

    // Menghapus dan mengembalikan elemen dari depan antrian (dequeue)
    public function dequeue() {
        if ($this->isEmpty()) {
            return null;
        }
        return array_shift($this->queue);
    }

    // Mendapatkan elemen dari depan antrian tanpa menghapusnya (peek)
    public function peek() {
        if ($this->isEmpty()) {
            return null;
        }
        return $this->queue[0];
    }

    // Memeriksa apakah antrian kosong
    public function isEmpty() {
        return empty($this->queue);
    }

    // Mendapatkan jumlah elemen dalam antrian
    public function size() {
        return count($this->queue);
    }
	
	public function contains($item) {
        return in_array($item, $this->queue);
    }
}


?>
