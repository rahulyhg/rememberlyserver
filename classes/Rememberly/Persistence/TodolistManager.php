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
          $listID = $input['list_id'];
          if (in_array($listID, $todolistPermissions))
          {
            if ($this->todoWithExpiration($input)) {
              $expires_on = $input['expires_on'];
              $todo_text = $input['todo_text'];
              $this->insertTodoWithExpiration($listID, $expires_on, $todo_text);
            } else {
            $todo_text = $input['todo_text'];
            $this->insertTodoWithoutExpiration($listID, $todo_text);
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
      private function insertTodoWithExpiration($listID, $expires_on, $todo_text) {
      try {
          $checkedDefault = 0;
          $sql = "INSERT INTO todos (list_id, expires_on, todo_text, is_checked)
          VALUES (:list_id, :expires_on, :todo_text, :is_checked)";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("list_id", $listID);
          $sth->bindValue("expires_on", $expires_on);
          $sth->bindParam("todo_text", $todo_text);
          $sth->bindParam("is_checked", $checkedDefault);
          $sth->execute();
          $newTodoID = $this->dbconnection->lastInsertId();
          $sql ="SELECT * FROM todos WHERE todo_id=:newTodoID";
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
    private function insertTodoWithoutExpiration($listID, $todo_text) {
      try {
          $checkedDefault = 0;
          $sql = "INSERT INTO todos (list_id, todo_text, is_checked)
          VALUES (:list_id, :todo_text, :is_checked)";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("list_id", $listID);
          $sth->bindParam("todo_text", $todo_text);
          $sth->bindParam("is_checked", $checkedDefault);
          $sth->execute();
          $newTodoID = $this->dbconnection->lastInsertId();
          $sql ="SELECT * FROM todos WHERE todo_id=:newTodoID";
          $sth = $this->dbconnection->prepare($sql);
          $sth->bindParam("newTodoID", $newTodoID);
          $sth->execute();
          $this->setResponse($sth->fetch());
          $sth = null;
          $this->setHttpCode(201);
          $this->isSuccess(true);
      } catch (\PDOException $e) {
        $this->setResponse(array('message' => "Unknown errorrrr."));
        $this->setHttpCode(500);
        $this->isSuccess(false);
      }
    }
    public function getTodos($listID) {
      $sql = "SELECT list_id, created_at, expires_on, todo_text, todo_id, is_checked
      FROM todos WHERE list_id=:list_id";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("list_id", $listID);
      $sth->execute();
      return ($sth->fetchAll());
    }
    public function createTodolist($list_name, $userID) {
      $sql = "INSERT INTO todolists (list_name, owner) VALUES (:list_name, :user_id)";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("list_name", $list_name);
      $sth->bindParam("user_id", $userID);
      $sth->execute();
      //  set list_id to the auto increment value from DB
      $listID = $this->dbconnection->lastInsertId();
      $input['list_id'] = $listID;
      $this->setTodolistPermissions($userID, $listID);
      $sql = "SELECT * FROM todolists WHERE list_id = :list_id";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("list_id", $listID);
      $sth->execute();
      $responseObject = $sth->fetch();
      $sth = null;
      return $responseObject;
    }
    public function deleteTodolist($listID) {
      $sql = "DELETE FROM todolists WHERE list_id=:list_id";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("list_id", $listID);
      $sth->execute();
      $this->deleteTodolistPermissions($listID);
      $this->deleteTodos($listID);
      $sth = null;
    }
    public function updateTodolist($listID, $list_name) {
      $sql = "UPDATE todolists SET list_name = :list_name WHERE list_id = :list_id";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("list_name", $list_name);
      $sth->bindParam("list_id", $listID);
      $sth->execute();
      $sth = null;
    }
    public function deleteTodos($listID) {
      $sql = "DELETE FROM todos WHERE list_id=:list_id";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("list_id", $listID);
      $sth->execute();
      $sth = null;
    }
    public function setTodolistShared($listID) {
      $sql = "UPDATE todolists SET isShared = :isShared WHERE list_id = :list_id";
      try {
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindParam("isShared", $a = 1);
      $sth->bindParam("list_id", $listID);
      $sth->execute();
    } catch (\PDOException $e) {
            $responseObject->message = $e->getMessage();
            return $responseObject;
    }
    $responseObject->message = "Todolist successfully shared";
    return $responseObject;
    }
  public function updateTodoWithExpiration($expires_on, $todo_text, $todo_id, $is_checked, $listID) {
    $responseObject;
  try {
      $sql = "UPDATE todos SET expires_on = :expires_on, is_checked = :is_checked, todo_text = :todo_text
      WHERE todo_id = :todo_id";
      $sth = $this->dbconnection->prepare($sql);
      $sth->bindValue("expires_on", $expires_on);
      $sth->bindParam("is_checked", $is_checked);
      $sth->bindParam("todo_text", $todo_text);
      $sth->bindParam("todo_id", $todo_id);
      $sth->execute();
      $this->removeOldTodos($listID);
  } catch (\PDOException $e) {
          $responseObject->message = $e->getMessage();
          return $responseObject;
  }
  $responseObject->message = "Todo successfully updated";
  return $responseObject;
}
public function updateTodo($todo_id, $is_checked, $todo_text, $listID) {
  $responseObject;
try {
    $sql = "UPDATE todos SET is_checked = :is_checked, todo_text = :todo_text
    WHERE todo_id = :todo_id";
    $sth = $this->dbconnection->prepare($sql);
    $sth->bindParam("is_checked", $is_checked);
    $sth->bindParam("todo_text", $todo_text);
    $sth->bindParam("todo_id", $todo_id);
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
      $sql = "DELETE FROM todos WHERE todo_id IN
      (SELECT todo_id FROM
        (SELECT todo_id FROM todos
          WHERE list_id = :list_id
          AND is_checked = :is_checked
          order by created_at DESC LIMIT 15, 50
        ) a
      )";
    $sth = $this->dbconnection->prepare($sql);
    $sth->bindParam("list_id", $listID);
    $sth->bindParam("is_checked", $a = 1);
    $sth->execute();
    } catch (\PDOException $e) {
      $responseObject->message = "An Error occured";
    }
  }

    private function todoWithExpiration($input)
    {
      return isset($input['expires_on']);
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
