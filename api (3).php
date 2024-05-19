<?php
class CommentAPI {
    private $db;
    private $status;
    private $data;

    // Constructor to open the database connection to the php my admin
    public function __construct() {
        $this->db = new mysqli("localhost", "jt1077_user", "x18qsVN8XTM", "jt1077_db");
        if ($this->db->connect_error) {
            $this->status = 500;
            $this->data = [];
            return;
        }
    }
    // Destructor to close the database
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
    // method to handle the request
    public function handleRequest() {
        if (!isset($this->db)) {
            $this->status = 500;
            $this->data = [];
            $this->sendResponse();
            return;
        }
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $this->post();
                break;
            case 'GET':
                $this->get();
                break;
            default:
                $this->status = 405; // Method Not Allowed
                $this->data = [];
        }
        $this->sendResponse();
    }

    // method to get comments
   private function get() {
    // Check if 'oid' parameter is set and not empty
    if (!isset($_GET['oid']) || empty($_GET['oid'])) {
        $this->status = 400; // Bad Request
        $this->data = [];
        return;
    }
    // Sanitize the input so no one can SQL inject my database.
    $oid = $this->db->real_escape_string($_GET['oid']);
    $query = "SELECT * FROM Comments WHERE oid = '$oid' ORDER BY date ASC";
    $result = $this->db->query($query);

    if (!$result) {
        $this->status = 500; // Server Error
        $this->data = [];
        return;
    }
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            "id" => $row["id"],
            "date" => date("d F Y", strtotime($row["date"])),
            "name" => $row["name"],
            "comment" => $row["comment"]
        ];
    }
    if (empty($comments)) {
        $this->status = 400; // Bad Request
        $this->data = ["error" => "OID not found"];
    } else {
        $has_non_empty_comment = false;
        foreach ($comments as $comment) {
            if (!empty($comment["comment"])) {
                $has_non_empty_comment = true;
                break;
            }
        }
        if ($has_non_empty_comment) {
            $this->status = 200; // OK request
            $this->data = [
                "oid" => $oid,
                "comments" => $comments
            ];
        } else {
            $this->status = 204; // No Content at all
            $this->data = [];
        }
    }
}
    private function post() {
        // Validate and sanitize input parameters
        $oid = isset($_POST['oid']) ? $this->db->real_escape_string($_POST['oid']) : '';
        $name = isset($_POST['name']) ? $this->db->real_escape_string($_POST['name']) : '';
        $comment = isset($_POST['comment']) ? $this->db->real_escape_string($_POST['comment']) : '';

        // Checks if parameter is empty
        if (empty($oid) || empty($name) || empty($comment)) {
            $this->status = 400; // Bad Request
            $this->data = [];
            return;
        }
        // Inserts the comment into the database
        $query = "INSERT INTO Comments (oid, name, comment, date) VALUES ('$oid', '$name', '$comment', NOW())";
        $result = $this->db->query($query);

        if ($result) {
            $this->status = 201; 
            $this->data = ["id" => $this->db->insert_id];
        } else {
            $this->status = 500; // Internal Server Error
            $this->data = [];
        }
    }
    // Method to send the response
    private function sendResponse() {
        header('Content-Type: application/json');

        http_response_code($this->status);
        if ($this->status == 200 || $this->status == 201) {
            echo json_encode($this->data);
        } else {
            echo json_encode([]);
        }
    }
}
$api = new CommentAPI();
$api->handleRequest();
?>