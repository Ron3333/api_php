<?php
class ProductController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function list() {
        $data = $this->db->query("SELECT * FROM foods");
        $all = $data->fetchAll(PDO::FETCH_ASSOC);
        Flight::json($all);
    }

      public function products($id) {
        $data = $this->db->query("SELECT * FROM foods WHERE id = $id");
        $all = $data->fetchAll(PDO::FETCH_ASSOC);
        Flight::json($all);
    }
}