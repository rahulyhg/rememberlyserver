<?php

namespace Rememberly\Persistence;

  class NoteManager
  {
      private $dbconnection;
      public function __construct($dbconnection)
      {
          $this->dbconnection = $dbconnection;
      }
      public function createNotice($noticeName, $userID) {
        $sql = "INSERT INTO notices (noticeName, owner) VALUES (:noticeName, :userID)";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noticeName", $noticeName);
        $sth->bindParam("userID", $userID);
        $sth->execute();
        //  set list_id to the auto increment value from DB
        $noticeID = $this->dbconnection->lastInsertId();
        $input['noticeID'] = $noticeID;
        $this->setNoticePermissions($userID, $noticeID);
        $sql = "SELECT * FROM notices WHERE noticeID = :noticeID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noticeID", $noticeID);
        $sth->execute();
        $responseObject = $sth->fetch();
        $sth = null;
        return $responseObject;
      }
      // Delete Todolist with list_id and delete the Todolistpermissions and the todos related to the todolist


      public function deleteNotice($noticeID) {
        $sql = "DELETE FROM notices WHERE noticeID=:noticeID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noticeID", $noticeID);
        $sth->execute();
        $this->deleteNoticePermissions($noticeID);
        // TODO: delete content of notice
        //$this->deleteContent($noticeID);
        $sth = null;
      }
      public function updateNotice($noticeID, $noticeName) {
        $sql = "UPDATE notices SET noticeName = :noticeName WHERE noticeID = :noticeID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noticeName", $noticeName);
        $sth->bindParam("noticeID", $noticeID);
        $sth->execute();
        $sth = null;
      }
      public function setNoticeShared($noticeID) {
        $sql = "UPDATE notices SET isShared = :isShared WHERE noticeID = :noticeID";
        try {
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("isShared", $a = 1);
        $sth->bindParam("noticeID", $noticeID);
        $sth->execute();
      } catch (\PDOException $e) {
              $responseObject->message = $e->getMessage();
              return $responseObject;
      }
      $responseObject->message = "Notice successfully shared";
      return $responseObject;
      }
  }
  ?>
