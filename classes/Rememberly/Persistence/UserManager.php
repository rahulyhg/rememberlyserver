<?php

namespace Rememberly\Persistence;

  class UserManager
  {
      private $dbconnection;
      public function __construct($dbconnection)
      {
          $this->dbconnection = $dbconnection;
      }
      public function getUserTodolistPermissions($user_id)
      {
          $query = "SELECT list_id FROM todolistPermissions WHERE user_id='$user_id'";
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
      public function getUserNoticesPermissions($user_id)
      {
          $query = "SELECT noticeID FROM noticesPermissions WHERE userID='$user_id'";
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
          $sqluser = "SELECT user_id FROM users WHERE username='$username'";
          $sth = $this->dbconnection->prepare($sqluser);
          $sth->execute();
          $res = $sth->fetch(\PDO::FETCH_ASSOC);
          return $res['user_id'];
      }
      public function getAndroidAppID($user_id) {
        $sql = "SELECT androidAppID FROM users WHERE user_id = :user_id";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("user_id", $user_id);
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
      public function deleteTodolistPermissions($list_id) {
        $sql = "DELETE FROM todolistPermissions WHERE list_id=:list_id";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("list_id", $list_id);
        $sth->execute();
        $sth = null;
      }
      public function deleteNoticePermissions($noticeID) {
        $sql = "DELETE FROM noticesPermissions WHERE noticeID=:noticeID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noticeID", $noticeID);
        $sth->execute();
        $sth = null;
      }
      public function setTodolistPermissions($userID, $listID) {
        $sql = "INSERT INTO todolistPermissions (list_id, user_id) VALUES (:list_id, :user_id)";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("list_id", $listID);
        $sth->bindParam("user_id", $userID);
        $sth->execute();
        $sth = null;
      }
      public function setNoticePermissions($userID, $noticeID) {
        $sql = "INSERT INTO noticesPermissions (noticeID, userID) VALUES (:noticeID, :userID)";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noticeID", $noticeID);
        $sth->bindParam("userID", $userID);
        $sth->execute();
        $sth = null;
      }
    }
      ?>
