<?php

declare(strict_types=1);

require __DIR__ . '/../src/Validator.php';

$failures = 0;

function check(string $label, bool $condition): void
{
    global $failures;
    if ($condition) {
        echo "PASS  $label\n";
    } else {
        echo "FAIL  $label\n";
        $failures++;
    }
}

// Valid input passes.
$v = new Validator(['email' => 'a@b.com', 'name' => 'Zargham', 'site' => 'https://example.com', 'age' => '21', 'color' => 'green']);
$v->rule('email', 'required|email')
  ->rule('name', 'required|min:2|max:50')
  ->rule('site', 'url')
  ->rule('age', 'numeric')
  ->rule('color', 'in:red,green,blue');
check('valid input passes', $v->passes());
check('valid input has no errors', $v->errors() === []);

// Invalid email fails with the right message.
$v = new Validator(['email' => 'not-an-email']);
$v->rule('email', 'required|email');
check('bad email fails', !$v->passes());
check('bad email message', ($v->errors()['email'][0] ?? '') === 'email must be a valid email address.');

// Missing required field fails; optional empty field is skipped.
$v = new Validator(['nickname' => '']);
$v->rule('name', 'required');
$v->rule('nickname', 'min:3');
check('missing required fails', !$v->passes());
check('optional empty field skipped', !isset($v->errors()['nickname']));

// Length rules.
$v = new Validator(['pw' => 'short']);
$v->rule('pw', 'min:8');
check('too-short value fails min', !$v->passes());

$v = new Validator(['code' => 'toolongcode']);
$v->rule('code', 'max:5');
check('too-long value fails max', !$v->passes());

// in: rule.
$v = new Validator(['color' => 'purple']);
$v->rule('color', 'in:red,green,blue');
check('value outside list fails in', !$v->passes());

// Unknown rule throws.
try {
    (new Validator(['x' => '1']))->rule('x', 'bogus');
    check('unknown rule throws', false);
} catch (InvalidArgumentException $e) {
    check('unknown rule throws', true);
}

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
