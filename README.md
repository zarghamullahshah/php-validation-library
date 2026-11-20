# PHP Validation Library

A tiny, dependency-free input validation library for PHP 8 — pipe-style rules, clear error messages, no framework required.

## Usage

```php
require 'src/Validator.php';

$v = new Validator($_POST);
$v->rule('email', 'required|email');
$v->rule('name', 'required|min:2|max:50');
$v->rule('website', 'url');              // optional: skipped when empty
$v->rule('age', 'numeric');
$v->rule('color', 'in:red,green,blue');

if (!$v->passes()) {
    print_r($v->errors());
    // ['email' => ['email must be a valid email address.'], ...]
}
```

## Available rules

| Rule | Meaning |
|---|---|
| `required` | Field must be present and non-empty |
| `email` | Must be a valid email address |
| `url` | Must be a valid URL |
| `numeric` | Must be a number |
| `min:n` | String length at least `n` |
| `max:n` | String length at most `n` |
| `in:a,b,c` | Must be one of the listed values |

Non-`required` rules are skipped for absent or empty values, so optional fields stay optional. Unknown rules throw an `InvalidArgumentException`.

## Run the tests

```bash
php tests/run_tests.php
```

## Project structure

```
├── src/Validator.php     # The library (single class)
└── tests/run_tests.php   # Self-contained test script
```

## Author

**Zargham Ullah Shah** — Full Stack Web Developer | Salesforce CRM Specialist
GitHub: [@zarghamullahshah](https://github.com/zarghamullahshah)
