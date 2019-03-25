<?php

namespace Rememberly\Validation;

  class BodyValidator
  {
    public static function validateNewTodo($input)
    {
      if ((isset($input['listID']) && isset($input['todoText']) &&
    isset($input['expiresOn'])) ||
  (isset($input['listID']) && isset($input['todoText']))) {
    return true;
  } else {
    return false;
  }
    }
  }
      ?>
