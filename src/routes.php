<?php

use Rememberly\Persistence\TodolistManager;
use Rememberly\Authentication\TokenManager;
use Rememberly\Persistence\UserManager;
use Rememberly\Persistence\NoteManager;
use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

// Create new Todo for Todolist associated with user
$app->post('/api/todo/new', function (Request $request, Response $response, array $args) {
    $token = $request->getAttribute("decoded_token_data");
    $userID = $token['userID'];
    $todolistPermissions = $token['todolistPermissions'];
    $input = $request->getParsedBody();
    $todolistManager = new TodolistManager($this->db);
    $todolistManager->insertTodo($input, $todolistPermissions);
    return $this->response->withJson($todolistManager->getResponse(), $todolistManager->getHttpCode());
    });
// update todo (name, check status,..)
$app->put('/api/todo/update', function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute("decoded_token_data");
  $userID = $token['userID'];
  $todolistPermissions = $token['todolistPermissions'];
  $input = $request->getParsedBody();
  $listID = $input['listID'];
  $todoText = $input['todoText'];
  $todoID = $input['todoID'];
  $isChecked = intval($input['isChecked']);
  $this->logger->info("Update Todo with Check Status: " . $isChecked);
  $todolistManager = new TodolistManager($this->db);
  $responseObject;
  if (in_array($listID, $todolistPermissions)) {
    if (isset($expiresOn)) {
      $expiresOn = $input['expiresOn'];
      $this->logger->info("Update todo with expiration");
      $responseObject = $todolistManager->updateTodoWithExpiration($expiresOn, $todoText, $todoID, $isChecked, $listID);
    } else {
      $this->logger->info("Update todo without expiration");
      $responseObject = $todolistManager->updateTodo($todoID, $isChecked, $todoText, $listID);
    }
  } else {
    $responseObject->message = "Not authorized!";
    return $this->response->withJson($responseObject);
  }
  return $this->response->withJson($responseObject);
});
// Get all todos of a list with list id
$app->get('/api/todos/[{listID}]', function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute("decoded_token_data");
  $userID = $token['userID'];
  $permissions = $token['todolistPermissions'];
  $listID = $args['listID'];
  $responseObject;
  if (in_array($listID, $permissions)) {
    $todolistManager = new TodolistManager($this->db);
    $responseObject = $todolistManager->getTodos($listID);
  } else {
    $responseObject->message = "No permissions or list not found";
  }
  return $this->response->withJson($responseObject);
});
// get a new token with an old token (which is still valid and not older than 2h)
$app->post('/api/tokenrefresh', function (Request $request, Response $response, array $args) {
   $token = $request->getAttribute("decoded_token_data");
   $userID = $token['userID'];
   $username = $token['username'];
   // Maybe the permissions have changed
   $userManager = new UserManager($this->db);
   $todolistPermissions = $userManager->getUserTodolistPermissions($userID);
   $notePermissions = $userManager->getUserNotePermissions($userID);
   $androidAppID = $token['androidAppID'];
   $tokenManager = new TokenManager($this->get('settings'));
   $token = $tokenManager->createUserToken($userID, $username, $todolistPermissions, $notePermissions, $androidAppID);
   return $this->response->withJson(['token' => $token]);
});
// Endpoint should return Statuscode 401 if token is no more valid
$app->post('/api/tokenlogin', function (Request $request, Response $response, array $args) {
   return $this->response->withJson(['message' => "Login successful"]);
});
// update a note's name
$app->put('/api/note/update', function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute("decoded_token_data");
  $input = $request->getParsedBody();
  $noteID = $input['noteID'];
  $noteName = $input['noteName'];
  $permissions = $token['notePermissions'];
  if (in_array($noteID, $permissions)) {
    $noteManager = new NoteManager($this->db);
    if (isset($input['noteContent'])) {
      $noteContent = $input['noteContent'];
      $noteManager->updateNote($noteID, $noteName, $noteContent, null);
    }
    $noteManager->updateNote($noteID, $noteName, null, null);
  } else {
    // Deletion forbidden (not owner)
    return $this->response->withStatus(403);
  }
  $returnMessage = new \stdClass;
  $returnMessage->message = "Note updated";
  return $this->response->withJson($returnMessage);
});
// update todolist name
$app->put('/api/todolist/update', function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute("decoded_token_data");
  $input = $request->getParsedBody();
  $listID = $input['listID'];
  $listName = $input['listName'];
  $permissions = $token['todolistPermissions'];
  if (in_array($listID, $permissions)) {
    $todolistManager = new TodolistManager($this->db);
    $todolistManager->updateTodolist($listID, $listName);
  } else {
    // Update forbidden (not owner)
    return $this->response->withStatus(403);
  }
  $returnMessage = new \stdClass;
  $returnMessage->message = "Todolist updated";
  return $this->response->withJson($returnMessage);
});
// Create new todolist with user id from token
$app->post('/api/todolist/new', function (Request $request, Response $response, array $args) {
    $token = $request->getAttribute("decoded_token_data");
    // TODO: Error Handling
    $userID = $token['userID'];
    $input = $request->getParsedBody();
    $listName = $input['listName'];
    // set permissions in DB and create new todolist
    if (isset($listName)) {
      $todolistManager = new TodolistManager($this->db);
      $responseObject = $todolistManager->createTodolist($input['listName'], $userID);
      return $this->response->withJson($responseObject, 201);
    } else {
      $jsonResponse = array('message' => "Listname not found.", 'status' => 404);
      $statusCode = $jsonResponse["status"];
      return $this->response->withJson($jsonResponse, $statusCode);
    }
});
// create a new note
$app->post('/api/note/new', function (Request $request, Response $response, array $args) {
    $token = $request->getAttribute("decoded_token_data");
    // TODO: Error Handling
    $userID = $token['userID'];
    $input = $request->getParsedBody();
    // set permissions in DB and create new note
    $noteManager = new NoteManager($this->db);
    $responseObject = $noteManager->createNote($input['noteName'], $userID);
    return $this->response->withJson($responseObject);
});
// delete todolist with listID
$app->delete('/api/todolist/delete/[{listID}]', function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute("decoded_token_data");
  $listID = $args['listID'];
  $permissions = $token['todolistPermissions'];
  $todolistManager = new TodolistManager($this->db);
  if (in_array($listID, $permissions)) {
    $todolistManager->deleteTodolist($listID);
  } else {
    // Deletion forbidden (not owner)
    return $this->response->withStatus(403);
  }
  $jsonResponse = array('message' => "Todolist deleted.");
  return $this->response->withJson($jsonResponse, 200);
});
// delete note with noteID
$app->delete('/api/note/delete/[{noteID}]', function (Request $request, Response $response, array $args) {
  $token = $request->getAttribute("decoded_token_data");
  $noteID = $args['noteID'];
  $permissions = $token['notePermissions'];
  $noteManager = new NoteManager($this->db);
  $returnMessage = new \stdClass;
  if (in_array($noteID, $permissions)) {
    $noteManager->deletenote($noteID);
  } else {
    // Deletion forbidden (not owner)
    return $this->response->withStatus(403);
  }
  $returnMessage->message = "Todolist deleted";
  return $this->response->withJson($returnMessage);
});
// share todolist with user (username provided in html body)
$app->post('/api/todolist/share', function (Request $request, Response $response, array $args) {
    $token = $request->getAttribute("decoded_token_data");
    $parsedBody = $request->getParsedBody();
    $listID = $parsedBody['listID'];
    $username = $parsedBody['username'];
    $todolistPermissions = $token['todolistPermissions'];
    $responseObject = new \stdClass;
    if (in_array($listID, $todolistPermissions)) {
      // set permissions to new user
      try {
      $userManager = new UserManager($this->db);
      $todolistManager = new TodolistManager($this->db);
      $userID = $userManager->getUserID($username);
      // TODO: Do this by trigger
      $userManager->setTodolistPermissions($userID, $listID);
      $todolistManager->setTodolistShared($listID);
      $responseObject->message = "Todolist shared with " . $username;
    } catch (PDOException $pdoe) {
      $this->logger->info($pdoe->getMessage());
      $responseObject->message = "Failed to share Todolist";
      return $this->response->withJson($responseObject, 404);
    }
    } else {
      $responseObject->message = "Failed to share Todolist";
    }
    return $this->response->withJson($responseObject);
});
// share note with username (username in html body)
$app->post('/api/note/share', function (Request $request, Response $response, array $args) {
    $token = $request->getAttribute("decoded_token_data");
    $parsedBody = $request->getParsedBody();
    $noteID = $parsedBody['noteID'];
    $username = $parsedBody['username'];
    $notePermissions = $token['notePermissions'];
    $responseObject = new \stdClass;
    if (in_array($noteID, $notePermissions)) {
      // set permissions to new user
      try {
      $userManager = new UserManager($this->db);
      $noteManager = new NoteManager($this->db);
      $userID = $userManager->getUserID($username);
      // TODO: Do this by trigger
      $userManager->setNotePermissions($userID, $noteID);
      $noteManager->setNoteShared($noteID);
      $responseObject->message = "note shared with " . $username;
    } catch (PDOException $pdoe) {
      return $this->response->withStatus(404);
    }
    } else {
      $responseObject->message = "Failed to share Todolist";
    }
    return $this->response->withJson($responseObject);
});
// Get TodoList of User
$app->get('/api/todolist/[{listID}]', function (Request $request, Response $response, array $args) {
    $token = $request->getAttribute("decoded_token_data");
    $userID = $token['userID'];
    $permissions = $token['todolistPermissions'];
    $in  = str_repeat('?,', count($permissions) - 1) . '?';
    $this->logger->info("User ID: " . $userID . " is trying to access list with ID: " . $args['listID']
  . " with permissions to lists: " . print_r($permissions, true));
    $sql = "SELECT * FROM todolists WHERE listID=? AND listID IN ($in)";
    $sth = $this->db->prepare($sql);
    $params = array_merge([$args['listID']], $permissions);
    $sth->execute($params);
    $todolist = $sth->fetchObject();
    // false if none found or no permission
    if ($todolist == false) {
      // error
      $error->message = "No permissions or list not found!";
      return $this->response->withJson($error);
    }
    return $this->response->withJson($todolist);
});
// Get all Todolists associated with user id
$app->get('/api/todolists', function (Request $request, Response $response, array $args) {
    $token = $request->getAttribute("decoded_token_data");
        $userID = $token['userID'];
        $todolistPermissions = $token['todolistPermissions'];
        $in  = str_repeat('?,', count($todolistPermissions) - 1) . '?';
        $this->logger->info("User has permission to access lists: " . print_r($todolistPermissions, true));
        $sql = "SELECT * FROM todolists WHERE listID IN ($in)";
        $sth = $this->db->prepare($sql);
        try {
        $sth->execute($todolistPermissions);
        $todolist = $sth->fetchAll(); // false if none found or no permission
        return $this->response->withJson($todolist);
      } catch (PDOException $pdoe) {
        // no permissions found
        return $this->response->withStatus(404);
      }
    });
    // get all notes of user
    $app->get('/api/notes', function (Request $request, Response $response, array $args) {
        $token = $request->getAttribute("decoded_token_data");
            $userID = $token['userID'];
            $notePermissions = $token['notePermissions'];
            $in  = str_repeat('?,', count($notePermissions) - 1) . '?';
            $this->logger->info("User has permission to access notes: " . print_r($notePermissions, true));
            $sql = "SELECT * FROM notes WHERE noteID IN ($in)";
            $sth = $this->db->prepare($sql);
            try {
            $sth->execute($notePermissions);
            $notes = $sth->fetchAll(); // false if none found or no permission
            return $this->response->withJson($notes);
          } catch (PDOException $pdoe) {
            // no permissions found
            return $this->response->withStatus(404);
          }
        });
// render the index page (welcome page)
$app->get('/', function (Request $request, Response $response, array $args) {
    return $this->renderer->render($response, 'index.phtml', $args);
});
// Testing purposes not for production
$app->get('/api/tokendecode', function (Request $request, Response $response, array $args) {
    // Sample log message
    $token = $request->getAttribute("decoded_token_data");
    return $this->response->withJson($token);
});
// create user
$app->post('/user/create', function ($request, $response, $args) {
    $parsedBody = $request->getParsedBody();
    if (isset($parsedBody['user']) && isset($parsedBody['password'])) {
      $user = $parsedBody['user'];
      $password = $parsedBody['password'];
      $userManager = new UserManager($this->db);
      // return message
      $jsonResponse = $userManager->createUser($user, $password);
      $statusCode = $jsonResponse["status"];
      return $this->response->withJson($jsonResponse, $statusCode);
    } else {
      $jsonResponse = array('message' => "Username/Password not found.", 'status' => 404);
      $statusCode = $jsonResponse["status"];
      return $this->response->withJson($jsonResponse, $statusCode);
    }
});
// route for logging in. This is just http basic authentication
$app->get('/login', function (Request $request, Response $response, array $args) {
    $username = $request->getServerParam('PHP_AUTH_USER');
    if (isset($username)) {
        $this->logger->info("Authenticated user: " . $username);
        $userManager = new UserManager($this->db);
        $tokenManager = new TokenManager($this->get('settings'));
        $userID = $userManager->getUserID($username);
        $todolistPermissions = $userManager->getUserTodolistPermissions($userID);
        $notePermissions = $userManager->getUserNotePermissions($userID);
        $androidAppID = $userManager->getAndroidAppID($userID);
        $token = $tokenManager->createUserToken($userID, $username,
         $todolistPermissions, $notePermissions, $androidAppID);
        return $this->response->withJson(['token' => $token]);
    }
});
