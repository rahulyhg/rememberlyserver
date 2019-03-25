<?php

namespace Rememberly\Persistence;

use Rememberly\Validation\BodyValidator;
/**
* This class is responsible for Todolists and Todo's. It manages inserting, updating,
* deleting and manipulating them.
*/
  class TodolistManager
  {
      /** This variable represents the database connection. */
      private $dbconnection;
      /** This variable represents the http status code of the last operation. */
      private $httpcode;
      /** This variable represents the return message of the last operation. */
      private $response;
      /** This variable shows if the last operation was successful. */
      private $success;
      /**
      * @param $dbconnection: The database connection object.
      */
      public function __construct($dbconnection)
      {
          $this->dbconnection = $dbconnection;
      }
      /**
      * This function inserts a Todo into the Database if the user has permissions
      * and all needed data is provided by the client.
      *
      * @param $input: The input of the HTML Body.
      * @param $todolistPermissions: The user's todolistPermissions.
      */
      public function insertTodo($input, $todolistPermissions)
      {
        if (BodyValidator::validateNewTodo($input))
        {
          $listID = $input['listID'];
          if (in_array($listID, $todolistPermissions))
          {
            if ($this->todoWithExpiration($input)) {
              $expiresOn = $input['expiresOn'];
              $todoText = $input['todoText'];
              $this->insertTodoWithExpiration($listID, $expiresOn, $todoText);
            } else {
            $todoText = $input['todoText'];
            $this->insertTodoWithoutExpiration($listID, $todoText);
          }
        } else {
          $this->setResponse(array('message' => "No permissions.", 'status' => 403));
          $this->setHttpCode(403);
        }
      } else {
        $this->setResponse(array('message' => "No valid input.", 'status' => 400));
        $this->setHttpCode(400);
      }
    }
      private function insertTodoWithExpiration($listID, $expiresOn, $todoText) {
      try {
          $checkedDefault = 0;
          $sql = "INSERT INTO todos (listID, expiresOn, todoText, isChecked)
          VALUES (:listID, :expiresOn, :todoText, :isChecked)";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("listID", $listID);
          $sth->bindValue("expiresOn", $expiresOn);
          $sth->bindParam("todoText", $todoText);
          $sth->bindParam("isChecked", $checkedDefault);
          $sth->execute();
          $newTodoID = $this->dbconnection->lastInsertId();
          $sql ="SELECT * FROM todos WHERE todoID=:newTodoID";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("newTodoID", $newTodoID);
          $sth->execute();
          $this->setResponse($sth->fetch());
          $sth = null;
          $this->setHttpCode(201);
          $this->isSuccess(true);
      } catch (\PDOException $e) {
              $this->setResponse(array('message' => "Unknown error."));
              $this->setHttpCode(500);
              $this->isSuccess(false);
      }
    }
    private function insertTodoWithoutExpiration($listID, $todoText) {
      try {
          $checkedDefault = 0;
          $sql = "INSERT INTO todos (listID, todoText, isChecked)
          VALUES (:listID, :todoText, :isChecked)";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("listID", $listID);
          $sth->bindParam("todoText", $todoText);
          $sth->bindParam("isChecked", $checkedDefault);
          $sth->execute();
          $newTodoID = $this->dbconnection->lastInsertId();
          $sql ="SELECT * FROM todos WHERE todoID=:newTodoID";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("newTodoID", $newTodoID);
          $sth->execute();
          $this->setResponse($sth->fetch());
          $sth = null;
          $this->setHttpCode(201);
          $this->isSuccess(true);
      } catch (\PDOException $e) {
        $this->setResponse(array('message' => "Unknown error."));
        $this->setHttpCode(500);
        $this->isSuccess(false);
      }
    }
    public function getTodos($listID) {
      $sql = "SELECT listID, createdAt, expiresOn, todoText, todoID, isChecked
      FROM todos WHERE listID=:listID";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("listID", $listID);
      $sth->execute();
      return ($sth->fetchAll());
    }
    public function createTodolist($listName, $userID) {
      $sql = "INSERT INTO todolists (listName, owner) VALUES (:listName, :userID)";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("listName", $listName);
      $sth->bindParam("userID", $userID);
      $sth->execute();
      //  set listID to the auto increment value from DB
      $listID = $this->dbconnection->lastInsertId();
      $input['listID'] = $listID;
      // Done by trigger
      // $this->setTodolistPermissions($userID, $listID);
      $sql = "SELECT * FROM todolists WHERE listID = :listID";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("listID", $listID);
      $sth->execute();
      $responseObject = $sth->fetch();
      $sth = null;
      return $responseObject;
    }
    public function deleteTodolist($listID) {
      $sql = "DELETE FROM todolists WHERE listID=:listID";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("listID", $listID);
      $sth->execute();
      //$this->deleteTodolistPermissions($listID);
      //$this->deleteTodos($listID);
      $sth = null;
    }
    public function updateTodolist($listID, $listName) {
      $sql = "UPDATE todolists SET listName = :listName WHERE listID = :listID";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("listName", $listName);
      $sth->bindParam("listID", $listID);
      $sth->execute();
      $sth = null;
    }
    public function deleteTodos($listID) {
      $sql = "DELETE FROM todos WHERE listID=:listID";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("listID", $listID);
      $sth->execute();
      $sth = null;
    }
    public function setTodolistShared($listID) {
      $sql = "UPDATE todolists SET isShared = :isShared WHERE listID = :listID";
      try {
      $a = 1;
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("isShared", $a);
      $sth->bindParam("listID", $listID);
      $sth->execute();
      $responseObject = new \stdClass;
    } catch (\PDOException $e) {
            $responseObject->message = $e->getMessage();
            return $responseObject;
    }
    $responseObject->message = "Todolist successfully shared";
    return $responseObject;
    }
  public function updateTodoWithExpiration($expiresOn, $todoText, $todoID, $isChecked, $listID) {
    $responseObject;
  try {
      $sql = "UPDATE todos SET expiresOn = :expiresOn, isChecked = :isChecked, todoText = :todoText
      WHERE todoID = :todoID";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindValue("expiresOn", $expiresOn);
      $sth->bindParam("isChecked", $isChecked);
      $sth->bindParam("todoText", $todoText);
      $sth->bindParam("todoID", $todoID);
      $sth->execute();
      $this->removeOldTodos($listID);
  } catch (\PDOException $e) {
          $responseObject->message = $e->getMessage();
          return $responseObject;
  }
  $responseObject->message = "Todo successfully updated";
  return $responseObject;
}
public function updateTodo($todoID, $isChecked, $todoText, $listID) {
  $responseObject = new \stdClass;
try {
    $sql = "UPDATE todos SET isChecked = :isChecked, todoText = :todoText
    WHERE todoID = :todoID";
    $sth = $this->dbconnection->prepare($sql);
    $sth->bindParam("isChecked", $isChecked);
    $sth->bindParam("todoText", $todoText);
    $sth->bindParam("todoID", $todoID);
    $sth->execute();
    $this->removeOldTodos($listID);
} catch (\PDOException $e) {
        $responseObject->message = $e->getMessage();
        return $responseObject;
}
$responseObject->message = "Todo successfully updated";
return $responseObject;
}
  private function removeOldTodos($listID) {
    try {
      $sql = "DELETE FROM todos WHERE todoID IN
      (SELECT todoID FROM
        (SELECT todoID FROM todos
          WHERE listID = :listID
          AND isChecked = :isChecked
          order by createdAt DESC LIMIT 15, 50
        ) a
      )";
    $sth = $this->dbconnection->prepare($sql);
    $a = 1;
    $sth->bindParam("listID", $listID);
    $sth->bindParam("isChecked", $a);
    $sth->execute();
    } catch (\PDOException $e) {
      $responseObject->message = "An Error occured";
    }
  }

    private function todoWithExpiration($input)
    {
      return isset($input['expiresOn']);
    }

    private function setHttpCode($value)
    {
      $this->httpcode = $value;
    }
    public function getHttpCode()
    {
      return $this->httpcode;
    }
    private function setResponse($json)
    {
      $this->response = $json;
    }
    public function getResponse()
    {
      return $this->response;
    }
    private function isSuccess($boolean)
    {
      $this->success = $boolean;
    }
    public function success()
    {
      return $this->success;
    }
    }
      ?>
