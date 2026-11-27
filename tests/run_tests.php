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

// date rule: real calendar dates in Y-m-d.
$v = new Validator(['dob' => '2026-11-26', 'leap' => '2024-02-29']);
$v->rule('dob', 'date')->rule('leap', 'date');
check('valid dates pass', $v->passes());

$v = new Validator(['dob' => '2026-02-30']);
$v->rule('dob', 'date');
check('impossible date fails', !$v->passes());
check('impossible date message', ($v->errors()['dob'][0] ?? '') === 'dob must be a valid date (YYYY-MM-DD).');

$v = new Validator(['dob' => '2026-13-01']);
$v->rule('dob', 'date');
check('month 13 fails date', !$v->passes());

$v = new Validator(['dob' => '26/11/2026']);
$v->rule('dob', 'date');
check('wrong-format date fails', !$v->passes());

$v = new Validator(['dob' => '2026-11-26 10:00']);
$v->rule('dob', 'date');
check('datetime string fails date', !$v->passes());

// boolean rule: true, false, 1, 0, "1", "0" only.
$v = new Validator([
    'a' => true, 'b' => false, 'c' => 1, 'd' => 0, 'e' => '1', 'f' => '0',
]);
$v->rule('a', 'boolean')->rule('b', 'boolean')->rule('c', 'boolean')
  ->rule('d', 'boolean')->rule('e', 'boolean')->rule('f', 'boolean');
check('true/false/1/0/"1"/"0" pass boolean', $v->passes());

$v = new Validator(['flag' => 'yes']);
$v->rule('flag', 'boolean');
check('"yes" fails boolean', !$v->passes());
check('boolean message', ($v->errors()['flag'][0] ?? '') === 'flag must be a boolean value.');

$v = new Validator(['flag' => 'maybe']);
$v->rule('flag', 'boolean');
check('"maybe" fails boolean', !$v->passes());

$v = new Validator(['flag' => 2]);
$v->rule('flag', 'boolean');
check('2 fails boolean', !$v->passes());

// ip rule: IPv4, IPv6, or either; v4/v6 pinning via "ip:v4" / "ip:v6".
$v = new Validator([
    'host' => '192.168.1.1', 'v6' => '2001:db8::1', 'either' => '10.0.0.5',
    'v6only' => 'fe80::1',
]);
$v->rule('host', 'ip')->rule('v6', 'ip')->rule('either', 'ip:v4')
  ->rule('v6only', 'ip:v6');
check('valid IPv4 and IPv6 addresses pass ip', $v->passes());

$v = new Validator(['host' => '999.1.1.1']);
$v->rule('host', 'ip');
check('out-of-range IPv4 fails ip', !$v->passes());
check('ip message', ($v->errors()['host'][0] ?? '') === 'host must be a valid IP address.');

$v = new Validator(['host' => 'not-an-ip']);
$v->rule('host', 'ip');
check('hostname fails ip', !$v->passes());

$v = new Validator(['host' => '2001:db8::1']);
$v->rule('host', 'ip:v4');
check('IPv6 fails ip:v4', !$v->passes());
check('ip:v4 message', ($v->errors()['host'][0] ?? '') === 'host must be a valid IPv4 address.');

$v = new Validator(['host' => '192.168.1.1']);
$v->rule('host', 'ip:v6');
check('IPv4 fails ip:v6', !$v->passes());
check('ip:v6 message', ($v->errors()['host'][0] ?? '') === 'host must be a valid IPv6 address.');

try {
    (new Validator(['x' => '1.2.3.4']))->rule('x', 'ip:v5');
    check('unknown ip version throws', false);
} catch (InvalidArgumentException $e) {
    check('unknown ip version throws', true);
}

// Unknown rule throws.
try {
    (new Validator(['x' => '1']))->rule('x', 'bogus');
    check('unknown rule throws', false);
} catch (InvalidArgumentException $e) {
    check('unknown rule throws', true);
}

echo $failures === 0 ? "\nAll tests passed.\n" : "\n$failures test(s) failed.\n";
exit($failures === 0 ? 0 : 1);
