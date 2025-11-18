<?php
// Installation script for School Management System
if ($_POST) {
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute SQL file
        $sql = file_get_contents('database/school_ecosystem.sql');
        $pdo->exec($sql);
        
        // Update config file
        $config = "<?php
class Database {
    private \$host = '$host';
    private \$db_name = 'school_ecosystem';
    private \$username = '$username';
    private \$password = '$password';
    private \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=utf8mb4\",
                \$this->username,
                \$this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4\"
                ]
            );
        } catch(PDOException \$e) {
            echo \"Connection Error: \" . \$e->getMessage();
            die();
        }
        
        return \$this->conn;
    }
}

function getDB() {
    \$database = new Database();
    return \$database->getConnection();
}
?>";
        
        file_put_contents('config/db.php', $config);
        
        echo "<div class='alert alert-success'>Installation completed successfully! <a href='index.php'>Go to Login</a></div>";
        echo "<div class='alert alert-info'>Default Admin Login:<br>Email: admin@school.com<br>Password: password</div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Install School Management System</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Database Host</label>
                                <input type="text" name="host" class="form-control" value="localhost" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Database Username</label>
                                <input type="text" name="username" class="form-control" value="root" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Database Password</label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Install</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>