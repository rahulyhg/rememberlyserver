<?php

namespace Rememberly\Validation;

  class BodyValidator
  {
    public static function validateNewTodo($input)
    {
      if ((isset($input['list_id']) && isset($input['todo_text']) &&
    isset($input['expires_on'])) ||
  (isset($input['list_id']) && isset($input['todo_text']))) {
    return true;
  } else {
    return false;
  }
    }
  }
      ?>
