<?php

namespace Rememberly\Persistence;

  class NoteManager
  {
      private $dbconnection;
      public function __construct($dbconnection)
      {
          $this->dbconnection = $dbconnection;
      }
      public function createNote($noteName, $userID) {
        $sql = "INSERT INTO notes (noteName, owner) VALUES (:noteName, :userID)";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noteName", $noteName);
        $sth->bindParam("userID", $userID);
        $sth->execute();
        //  set list_id to the auto increment value from DB
        $noteID = $this->dbconnection->lastInsertId();
        $sql = "SELECT * FROM notes WHERE noteID = :noteID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noteID", $noteID);
        $sth->execute();
        $responseObject = $sth->fetch();
        $sth = null;
        return $responseObject;
      }
      // Delete Todolist with list_id and delete the Todolistpermissions and the todos related to the todolist


      public function deleteNote($noteID) {
        $sql = "DELETE FROM notes WHERE noteID=:noteID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noteID", $noteID);
        $sth->execute();
        $sth = null;
      }
      public function updateNote($noteID, $noteName, $noteContent, $noteDirectory) {
        $sql = "";
        if (isset($noteContent)) {
          $sql = "UPDATE notes SET noteName = :noteName, noteContent = :noteContent WHERE noteID = :noteID";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("noteName", $noteName);
          $sth->bindParam("noteContent", $noteContent);
          $sth->bindParam("noteID", $noteID);
          $sth->execute();
          $sth = null;
        } else {
        $sql = "UPDATE notes SET noteName = :noteName WHERE noteID = :noteID";
        $sth = $this->dbconnection->prepare($sql);
        $sth->bindParam("noteName", $noteName);
        $sth->bindParam("noteID", $noteID);
        $sth->execute();
        $sth = null;
      }
      }
      public function setNoteShared($noteID) {
        $sql = "UPDATE notes SET isShared = :isShared WHERE noteID = :noteID";
        try {
        $sth = $this->dbconnection->prepare($sql);
        $a = 1;
        $sth->bindParam("isShared", $a);
        $sth->bindParam("noteID", $noteID);
        $sth->execute();
        $responseObject = new \stdClass;
      } catch (\PDOException $e) {
              $responseObject->message = $e->getMessage();
              return $responseObject;
      }
      $responseObject->message = "Note successfully shared";
      return $responseObject;
      }
  }
  ?>
