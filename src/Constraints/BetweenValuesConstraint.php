<?php

namespace Mindbreeze\Constraints;

class BetweenValuesConstraint extends Constraint
{
  public function create($values = [])
  {
    if (!is_array($values) || count($values) != 2) {
      throw new \Mindbreeze\Exceptions\RequestException('Value passed to BetweenValuesConstraint is invalid. Must be an array with two values.');
    }

    $this->filters[] = [
      'label' => $this->label,
      'and' => [
        [
          'num' => $values[0],
          'cmp' => 'GE',
          'unit' => 'ms_since_1970'
        ],
        [
          'num' => $values[1],
          'cmp' => 'LE',
          'unit' => 'ms_since_1970'
        ]
      ],
      'value' => [
        'num' => $values[0],
        'unit' => 'ms_since_1970'
      ]
    ];

    return $this;
  }
}
