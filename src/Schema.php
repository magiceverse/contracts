<?php

namespace Magiceverse\Contracts;

use InvalidArgumentException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

/**
 * Access to the JSON Schemas shipped in this package. The validator maps
 * every $id to its local file, so validation never touches the network and
 * always uses the schema version installed with the package.
 */
final class Schema
{
    public const BASE_URI = 'https://contracts.magiceverse.dev/';

    /** Reused by validate() so parsed schemas are cached between calls. */
    private static ?Validator $shared = null;

    public static function directory(): string
    {
        return dirname(__DIR__).'/schemas';
    }

    public static function id(string $entity, int $major): string
    {
        return self::BASE_URI."{$entity}/v{$major}";
    }

    public static function path(string $entity, int $major): string
    {
        // The entity becomes part of a file path; refuse anything that could
        // leave the schemas directory.
        if (! preg_match('/^[a-z-]+$/', $entity)) {
            throw new InvalidArgumentException("Invalid contract entity [{$entity}].");
        }

        $path = self::directory()."/{$entity}/v{$major}.json";

        if (! is_file($path)) {
            throw new InvalidArgumentException("No schema for [{$entity}] v{$major}.");
        }

        return $path;
    }

    /**
     * Every shipped schema as $id => file path.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $schemas = [];

        foreach (glob(self::directory().'/*/v*.json') ?: [] as $path) {
            $entity = basename(dirname($path));
            $major = (int) substr(basename($path, '.json'), 1);

            $schemas[self::id($entity, $major)] = $path;
        }

        ksort($schemas);

        return $schemas;
    }

    /**
     * A validator that resolves our $ids locally and collects all errors
     * rather than stopping at the first one.
     */
    public static function validator(): Validator
    {
        $validator = new Validator(max_errors: 1000, stop_at_first_error: false);

        foreach (self::all() as $id => $path) {
            $validator->resolver()->registerFile($id, $path);
        }

        return $validator;
    }

    /**
     * @throws ContractViolation listing every error with its JSON pointer
     */
    public static function validate(string $entity, int $major, array|object $json): void
    {
        self::path($entity, $major);

        // Opis needs JSON objects as stdClass. An associative PHP array cannot
        // express an empty object, so an empty map encodes as [] and fails;
        // callers should omit empty maps or pass the decoded object instead.
        if (is_array($json)) {
            $json = json_decode(json_encode($json, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION), false, flags: JSON_THROW_ON_ERROR);
        }

        $id = self::id($entity, $major);
        $result = (self::$shared ??= self::validator())->validate($json, $id);

        $errors = $result->isValid() ? [] : self::errors($result->error(), new ErrorFormatter);

        // A rule JSON Schema cannot express: the event's subject names the
        // product it carries.
        if ($entity === 'cloudevent' && is_string($json->subject ?? null) && is_string($json->data->uid ?? null)
            && $json->subject !== $json->data->uid) {
            $errors['/subject'][] = 'The subject must equal data.uid';
        }

        if ($errors !== []) {
            throw new ContractViolation($id, $errors);
        }
    }

    /**
     * Leaf errors keyed by JSON pointer. Opis treats properties that failed
     * their own schema as "not evaluated", so one bad value also triggers an
     * additionalProperties error on its parent naming every declared
     * property. Those echoes are dropped; only undeclared names are reported.
     * Likewise, when a nullable field (anyOf [X, null]) fails, the "must be
     * null" branch is dropped when the X branch explains what is wrong.
     *
     * @return array<string, list<string>>
     */
    private static function errors(ValidationError $error, ErrorFormatter $formatter): array
    {
        $subErrors = $error->subErrors();

        if ($error->keyword() === 'anyOf') {
            $explained = array_filter($subErrors, fn (ValidationError $sub) => ! self::isNullBranch($sub));

            if ($explained !== []) {
                $subErrors = $explained;
            }
        }

        if ($subErrors) {
            return array_merge_recursive(...array_map(
                fn (ValidationError $sub) => self::errors($sub, $formatter),
                array_values($subErrors),
            ));
        }

        $message = $formatter->formatErrorMessage($error);

        if ($error->keyword() === 'additionalProperties') {
            $declared = array_keys((array) ($error->schema()->info()->data()->properties ?? []));
            $extra = array_values(array_diff($error->args()['properties'] ?? [], $declared));

            if ($extra === []) {
                return [];
            }

            $message = 'Additional object properties are not allowed: '.implode(', ', $extra);
        }

        return [$formatter->formatErrorKey($error) => [$message]];
    }

    private static function isNullBranch(ValidationError $error): bool
    {
        return $error->keyword() === 'type' && ($error->args()['expected'] ?? null) === 'null';
    }
}
