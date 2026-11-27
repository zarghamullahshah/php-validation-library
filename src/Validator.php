<?php

declare(strict_types=1);

/**
 * Validator - a tiny, dependency-free input validation library for PHP 8.
 *
 * Usage:
 *   $v = new Validator(['email' => 'not-an-email', 'name' => '']);
 *   $v->rule('email', 'required|email');
 *   $v->rule('name', 'required|min:2|max:50');
 *   if (!$v->passes()) { print_r($v->errors()); }
 */
final class Validator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, string[]> */
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Apply a pipe-separated rule string to a field, e.g. "required|email"
     * or "required|min:8" or "in:red,green,blue".
     */
    public function rule(string $field, string $rules): self
    {
        $value = $this->data[$field] ?? null;

        foreach (explode('|', $rules) as $rule) {
            [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

            // Non-required rules are skipped for absent/empty values.
            if ($name !== 'required' && ($value === null || $value === '')) {
                continue;
            }

            $error = $this->check($field, $value, $name, $param);
            if ($error !== null) {
                $this->errors[$field][] = $error;
            }
        }
        return $this;
    }

    private function check(string $field, mixed $value, string $rule, ?string $param): ?string
    {
        switch ($rule) {
            case 'required':
                return ($value === null || $value === '') ? "$field is required." : null;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) === false
                    ? "$field must be a valid email address." : null;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) === false
                    ? "$field must be a valid URL." : null;
            case 'ip':
                // Plain "ip" accepts either version; "ip:v4" / "ip:v6" pin one.
                $flags = 0;
                $label = 'IP';
                if ($param === 'v4') {
                    $flags = FILTER_FLAG_IPV4;
                    $label = 'IPv4';
                } elseif ($param === 'v6') {
                    $flags = FILTER_FLAG_IPV6;
                    $label = 'IPv6';
                } elseif ($param !== null) {
                    throw new InvalidArgumentException("Unknown ip version: $param (use ip, ip:v4 or ip:v6)");
                }
                return filter_var($value, FILTER_VALIDATE_IP, $flags) === false
                    ? "$field must be a valid $label address." : null;
            case 'date':
                $parsed = DateTimeImmutable::createFromFormat('Y-m-d', (string) $value);
                $valid = $parsed !== false && $parsed->format('Y-m-d') === (string) $value;
                return !$valid ? "$field must be a valid date (YYYY-MM-DD)." : null;
            case 'boolean':
                return !in_array($value, [true, false, 1, 0, '1', '0'], true)
                    ? "$field must be a boolean value." : null;
            case 'numeric':
                return !is_numeric($value) ? "$field must be a number." : null;
            case 'min':
                return strlen((string) $value) < (int) $param
                    ? "$field must be at least $param characters." : null;
            case 'max':
                return strlen((string) $value) > (int) $param
                    ? "$field must not exceed $param characters." : null;
            case 'in':
                $options = explode(',', (string) $param);
                return !in_array((string) $value, $options, true)
                    ? "$field must be one of: " . implode(', ', $options) . '.' : null;
            default:
                throw new InvalidArgumentException("Unknown validation rule: $rule");
        }
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }
}
