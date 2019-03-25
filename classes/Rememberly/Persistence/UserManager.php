<?php

namespace Rememberly\Persistence;

  class UserManager
  {
      private $dbconnection;
      public function __construct($dbconnection)
      {
          $this->dbconnection = $dbconnection;
      }
      public function getUserTodolistPermissions($userID)
      {
          $query = "SELECT listID FROM todolistPermissions WHERE userID='$userID'";
          $sth = $this->dbconnection->prepare($query);
          $sth->execute();
          $resultPermissions = "";
          for ($i = 0; $i < $sth->rowCount(); $i++) {
            if ($i == $sth->rowCount() - 1) {
              $result = $sth->fetchColumn();
              $resultPermissions .= $result;
            } else {
              $result = $sth->fetchColumn();
              $resultPermissions .= $result . ",";
            }
          }
          return explode(',', $resultPermissions);
      }
      public function getUserNotePermissions($userID)
      {
          $query = "SELECT noteID FROM notePermissions WHERE userID='$userID'";
          $sth = $this->dbconnection->prepare($query);
          $sth->execute();
          $resultPermissions = "";
          for ($i = 0; $i < $sth->rowCount(); $i++) {
            if ($i == $sth->rowCount() - 1) {
              $result = $sth->fetchColumn();
              $resultPermissions .= $result;
            } else {
              $result = $sth->fetchColumn();
              $resultPermissions .= $result . ",";
            }
          }
          return explode(',', $resultPermissions);
      }
      public function getUserID($username)
      {
          $sqluser = "SELECT userID FROM users WHERE username='$username'";
          $sth = $this->dbconnection->prepare($sqluser);
          $sth->execute();
          $res = $sth->fetch(\PDO::FETCH_ASSOC);
          return $res['userID'];
      }
      public function getAndroidAppID($userID) {
        $sql = "SELECT androidAppID FROM users WHERE userID = :userID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("userID", $userID);
        $sth->execute();
        $res = $sth->fetch(\PDO::FETCH_ASSOC);
        return $res['androidAppID'];
      }
      public function createUser($user, $password)
      {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $jsonResponse = array('message' => "Unknown error.", 'status' => 500);
              try {
                  $sth = $this->dbconnection->prepare("INSERT INTO users (username, passwordhash) VALUES ('{$user}', '{$hash}')");
                  $sth->execute();
                  $jsonResponse = array('message' => "User " . $user . " successfully created.", 'status' => 201);
              } catch (\PDOException $e) {
                  if ($e->getCode() == 23000) {
                      $jsonResponse = array('message' => "Username already registered.", 'status' => 403);
                  } else {
                      $jsonResponse = array('message' => "Unknown error.", 'status' => 400);
                  }
              }
          return $jsonResponse;
      }
      public function deleteTodolistPermissions($listID) {
        $sql = "DELETE FROM todolistPermissions WHERE listID=:listID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("listID", $listID);
        $sth->execute();
        $sth = null;
      }
      public function deleteNotePermissions($noteID) {
        $sql = "DELETE FROM notePermissions WHERE noteID=:noteID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noteID", $noteID);
        $sth->execute();
        $sth = null;
      }
      public function setTodolistPermissions($userID, $listID) {
        $sql = "INSERT INTO todolistPermissions (listID, userID) VALUES (:listID, :userID)";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("listID", $listID);
        $sth->bindParam("userID", $userID);
        $sth->execute();
        $sth = null;
      }
      public function setNotePermissions($userID, $noteID) {
        $sql = "INSERT INTO notePermissions (noteID, userID) VALUES (:noteID, :userID)";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noteID", $noteID);
        $sth->bindParam("userID", $userID);
        $sth->execute();
        $sth = null;
      }
    }
      ?>
