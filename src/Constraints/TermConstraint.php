<?php

namespace Mindbreeze\Constraints;

class TermConstraint extends Constraint
{
  public function create($values = [])
  {
    if (!is_array($values)) {
      $values = (array) $values;
    }

    foreach ($values as $value) {
      $this->filters[] = [
        'label' => $this->label,
        'or' => array_map(function ($value) {
          return ['quoted_term' => $value];
        }, $values)  
      ];
    }

    return $this;
  }
}
