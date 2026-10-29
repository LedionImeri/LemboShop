<?php
declare(strict_types=1);

/**
 * Small server-side validator.
 *
 *   $v = Validator::make($_POST, [
 *       'email' => 'required|email|max:190',
 *       'qty'   => 'required|int|min_value:1',
 *   ]);
 *   if ($v->fails()) { ... $v->errors() ... }
 *   $clean = $v->validated();
 */
final class Validator
{
    private array $errors = [];
    private array $clean = [];

    private function __construct(private array $data, private array $rules, private array $labels)
    {
        $this->run();
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function fails(): bool { return $this->errors !== []; }
    public function errors(): array { return $this->errors; }
    public function validated(): array { return $this->clean; }

    /** Attach an extra error after custom checks (e.g. "email already taken"). */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $raw = $this->data[$field] ?? null;
            $value = is_string($raw) ? trim($raw) : $raw;
            $rules = explode('|', $ruleString);
            $label = $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));

            if (is_array($value) && !in_array('array', $rules, true)) {
                $this->errors[$field] = "$label is invalid.";
                continue;
            }
            $empty = $value === null || $value === '' || $value === [];
            if ($empty) {
                if (in_array('required', $rules, true)) {
                    $this->errors[$field] = "$label is required.";
                } else {
                    $this->clean[$field] = in_array('bool', $rules, true) ? false : null;
                }
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $error = $this->check($name, $arg, $value, $label, $field);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    continue 2;
                }
            }
            $this->clean[$field] = $this->cast($rules, $value);
        }
    }

    private function check(string $name, ?string $arg, mixed $value, string $label, string $field): ?string
    {
        $len = is_string($value) ? mb_strlen($value) : 0;
        return match ($name) {
            'required', 'nullable', 'array', 'bool' => null,
            'string'    => is_string($value) ? null : "$label must be text.",
            'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? null : "Enter a valid email address.",
            'min'       => $len >= (int) $arg ? null : "$label must be at least $arg characters.",
            'max'       => $len <= (int) $arg ? null : "$label may not be longer than $arg characters.",
            'int'       => filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "$label must be a whole number.",
            'numeric'   => is_numeric($value) ? null : "$label must be a number.",
            'min_value' => is_numeric($value) && $value + 0 >= (float) $arg ? null : "$label must be at least $arg.",
            'max_value' => is_numeric($value) && $value + 0 <= (float) $arg ? null : "$label may not be greater than $arg.",
            'in'        => in_array((string) $value, explode(',', (string) $arg), true) ? null : "$label has an invalid value.",
            'same'      => ($this->data[$arg] ?? null) === $this->data[$field] ? null : "$label does not match.",
            'regex'     => preg_match((string) $arg, (string) $value) ? null : "$label has an invalid format.",
            'slug'      => preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value) ? null : "$label may only contain lowercase letters, numbers and dashes.",
            'phone'     => preg_match('/^\+?[0-9 ()\-]{6,25}$/', (string) $value) ? null : "Enter a valid phone number.",
            'date'      => strtotime((string) $value) !== false ? null : "$label must be a valid date.",
            'password'  => (preg_match('/[A-Za-z]/', (string) $value) && preg_match('/\d/', (string) $value))
                            ? null : "$label must contain at least one letter and one number.",
            default     => throw new InvalidArgumentException("Unknown validation rule: $name"),
        };
    }

    private function cast(array $rules, mixed $value): mixed
    {
        if (in_array('int', $rules, true)) return (int) $value;
        if (in_array('bool', $rules, true)) return in_array($value, ['1', 1, true, 'on', 'yes'], true);
        if (in_array('email', $rules, true)) return mb_strtolower((string) $value);
        return $value;
    }
}
