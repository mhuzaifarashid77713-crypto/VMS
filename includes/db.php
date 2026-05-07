<?php
// =============================================
//  Database Configuration — PDO Version
// =============================================
 
$host = getenv('MYSQLHOST')     ?: 'localhost';
$user = getenv('MYSQLUSER')     ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$name = getenv('MYSQLDATABASE') ?: 'vms_db';
$port = getenv('MYSQLPORT')     ?: 3306;
 
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif;padding:20px;color:red;'>
        <h3>❌ Database Connection Failed</h3>
        <p>" . $e->getMessage() . "</p>
    </div>");
}
 
// mysqli compatibility wrapper
class MysqliWrapper {
    private $pdo;
    public $connect_error = null;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function prepare($sql) {
        return new StmtWrapper($this->pdo->prepare($sql));
    }
    
    public function query($sql) {
        $stmt = $this->pdo->query($sql);
        return new ResultWrapper($stmt);
    }
    
    public function set_charset($charset) { return true; }
    
    public function real_escape_string($str) {
        return addslashes($str);
    }
}
 
class StmtWrapper {
    private $stmt;
    public $num_rows = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function bind_param($types, &...$vars) {
        $i = 1;
        foreach ($vars as &$var) {
            $this->stmt->bindParam($i++, $var);
        }
    }
    
    public function execute() {
        $result = $this->stmt->execute();
        $this->num_rows = $this->stmt->rowCount();
        return $result;
    }
    
    public function store_result() {
        $this->num_rows = $this->stmt->rowCount();
    }
    
    public function get_result() {
        return new ResultWrapper($this->stmt);
    }
    
    public function close() {}
}
 
class ResultWrapper {
    private $stmt;
    public $num_rows = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        $this->num_rows = $stmt->rowCount();
    }
    
    public function fetch_assoc() {
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function fetch_all($mode = null) {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
 
$conn = new MysqliWrapper($pdo);
?>
