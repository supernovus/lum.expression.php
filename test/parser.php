<?php

namespace Lum\Test;

require_once 'vendor/autoload.php';

\Lum\Test\Functional::start();

plan(26);

$in =
[
  'postfix' =>
  [
    1,
    2,
    'gt',
    'not',
    4,
    4,
    'eq',
    'and',
  ],
  'prefix' =>
  [
    'and',
    'not',
    'gt',
    1,
    2,
    'eq',
    4,
    4,
  ],
  'infix' =>
  [
    '(',
      '(',
        'not',
        '(',
          1,
          'gt',
          2,
        ')',
      ')',
      'and',
      '(',
        4,
        'eq',
        4,
      ')',
    ')'
  ],
];

// A 'loose' infix expression, depending on precedence rules.
$loose_infix = ['not', 1, 'gt', 2, 'and', 4, 'eq', 4];

$operators =
[
  'eq'   => ['precedence'=>3, 'evaluate'=>true],
  'gt'   => ['precedence'=>3, 'evaluate'=>true],
  'and'  => ['precedence'=>1, 'evaluate'=>true],
  'not'  => ['precedence'=>2, 'unary'=>true, 'evaluate'=>true],
  'add'  => ['precedence'=>2, 'evaluate'=>true],
  'mult' => ['precedence'=>3, 'evaluate'=>true],
  // An operator using a built-in evaluator but with a custom name.
  'negate' => ['precedence'=>4, 'unary'=>true, 'evaluate'=>'neg'],
  // A custom operator.
  'sqrt'   => ['precedence'=>4, 'unary'=>true, 'evaluate'=>
    function($items)
    {
      return sqrt($items[0]);
    }
  ],
  // One more custom operator that only works in prefix or postfix.
  // There's currently no way to support operands > 2 in infix.
  'ifelse' => ['precedence'=>1, 'operands'=>3, 'evaluate'=>
    function ($items)
    {
      return ($items[0] ? $items[1] : $items[2]);
    }
  ],
];

$expy = new \Lum\Expression\Parser(['operators'=>$operators]);

$to_types = array_keys($in);

$want_parsed = '[{"op":{"name":"and","operands":2,"precedence":1,"assoc":1},"items":[{"op":{"name":"not","operands":1,"precedence":2,"assoc":2},"items":[{"op":{"name":"gt","operands":2,"precedence":3,"assoc":1},"items":[1,2]}]},{"op":{"name":"eq","operands":2,"precedence":3,"assoc":1},"items":[4,4]}]}]';

// First, let's do the 12 automated conversion tests.
foreach ($in as $type => $exp_in)
{
  $meth = 'load'.ucfirst($type);
  $expy->$meth($exp_in);
  $parsed_obj = $expy->getData();
  $parsed_json = json_encode($parsed_obj);
  is($parsed_json, $want_parsed, "parsed $type");
  $exp_val = $expy->evaluate();
  is($exp_val, true, "evaluated $type");
  foreach ($to_types as $to_type)
  {
    $tometh = 'save'.ucfirst($to_type);
    $saved = $expy->$tometh();
    is($saved, $in[$to_type], "$type to $to_type");
  }
}

// Now let's try the loose infix parsing.
$expy->loadInfix($loose_infix);
$parsed_obj = $expy->getData();
$parsed_json = json_encode($parsed_obj);
is($parsed_json, $want_parsed, "parsed loose infix");
$exp_val = $expy->evaluate();
is($exp_val, true, "evaluated loose infix");
foreach ($to_types as $to_type)
{
  $tometh = 'save'.ucfirst($to_type);
  $saved = $expy->$tometh();
  is($saved, $in[$to_type], "loose infix to $to_type");
}

$false_exp = [1,2,'gt'];
$expy->loadPostfix($false_exp);
$false_val = $expy->evaluate();
is($false_val, false, 'false expression evaluated');

$num_exp = [1,2,'add',3, 'mult'];
$expy->loadPostfix($num_exp);
$num_val = $expy->evaluate();
is($num_val, 9, 'numeric expression evaluated');

$neg_exp = [100,'negate'];
$expy->loadPostfix($neg_exp);
$neg_val = $expy->evaluate();
is($neg_val, -100, 'explicitly defined negation operator evaluated');

$sqrt_exp = [9, 'sqrt'];
$expy->loadPostfix($sqrt_exp);
$sqrt_val = $expy->evaluate();
is($sqrt_val, 3.0, 'custom operator evaluated');

$ifelse_exp_1 = [2,1,'gt','first','second','ifelse'];
$expy->loadPostfix($ifelse_exp_1);
$ifelse_val = $expy->evaluate();
is($ifelse_val, 'first', 'ternary operator with true');

$ifelse_exp_2 = [1,2,'gt','first','second','ifelse'];
$expy->loadPostfix($ifelse_exp_2);
$ifelse_val = $expy->evaluate();
is($ifelse_val, 'second', 'ternary operator with false');

echo get_tap();
return test_instance();

