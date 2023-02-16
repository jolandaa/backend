<?php
require __DIR__ . '/../shared/Database.php';

class User {
  // (A) CONSTRUCTOR - CONNECT TO THE DATABASE
  private $pdo = null;
  private $stmt = null;
  public $error;
  function __construct () {
    $db_connection = new Database();
    $conn = $db_connection->dbConnection();
  }

  // (B) DESTRUCTOR - CLOSE DATABASE CONNECTION
  function __destruct () {
    if ($this->stmt !== null) { $this->stmt = null; }
    if ($this->pdo !== null) { $this->pdo = null; }
  }

  // (C) RUN SQL QUERY
  function query ($sql, $data=null) {
    $this->stmt = $this->pdo->prepare($sql);
    $this->stmt->execute($data);
  }

  // (D) LOGIN
  function login ($email, $password) {
    // (D1) GET USER & CHECK PASSWORD
    $this->query("SELECT * FROM `users` JOIN `roles` USING (`role_id`) WHERE `user_email`=?", [$email]);
    $user = $this->stmt->fetch();
    $valid = is_array($user);
    if ($valid) { $valid = $password == $user["user_password"]; }
    if (!$valid) {
      $this->error = "Invalid email/password";
      return false;
    }

    // (D2) GET PERMISSIONS
    $user["permissions"] = [];
    $this->query(
      "SELECT * FROM `roles_permissions` r
       LEFT JOIN `permissions` p USING (`perm_id`)
       WHERE r.`role_id`=?", [$user["role_id"]]
    );
    while ($r = $this->stmt->fetch()) {
      if (!isset($user["permissions"][$r["perm_mod"]])) {
        $user["permissions"][$r["perm_mod"]] = [];
      }
      $user["permissions"][$r["perm_mod"]][] = $r["perm_id"];
    }

    // (D3) DONE
    $_SESSION["user"] = $user;
    unset($_SESSION["user"]["user_password"]);
    return true;
  }

  // (E) CHECK PERMISSION
  function check ($module, $perm) {
    $valid = isset($_SESSION["user"]);
    if ($valid) { $valid = in_array($perm, $_SESSION["user"]["permissions"][$module]); }
    if ($valid) { return true; }
    else { $this->error = "No permission to access."; return false; }
  }

  // (F) GET USER
  function get ($email) {
    if (!$this->check("USR", 1)) { return false; }
    $this->query("SELECT * FROM `users` JOIN `roles` USING (`role_id`) WHERE `user_email`=?", [$email]);
    return $this->stmt->fetch();
  }

  // (G) SAVE USER
  function save ($username, $email, $password, $role=null) {
    if (!$this->check("USR", 2)) { return false; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {return false;}
    if (strlen($password) < 8) {return false;}

    if (strlen($username) < 3) {return false;}
    $sql = "INSERT INTO `users` (`username`,`email`,`password`, `role`) VALUES (?,?,?)";
    $data = [$username, $email, password_hash($password, PASSWORD_DEFAULT),$role];
    $this->query($sql, $data);
    return true;
  }

  // (H) DELETE USER
  function del ($id) {
    if (!$this->check("USR", 3)) { return false; }
    $this->query("DELETE FROM `users` WHERE `user_id`=?", [$id]);
    return true;
  }
}

// (I) DATABASE SETTINGS - CHANGE TO YOUR OWN!
define("DB_HOST", "localhost");
define("DB_NAME", "test");
define("DB_CHARSET", "utf8mb4");
define("DB_USER", "root");
define("DB_PASSWORD", "");

// (J) START!
$_USR = new User();
session_start();