<?php

namespace Magiceverse\Contracts;

use InvalidArgumentException;

/**
 * A payload does not match its contract. Carries every error, keyed by the
 * JSON pointer of the offending value ("/" is the document itself), so a
 * caller can report all problems at once instead of one per attempt.
 */
class ContractViolation extends InvalidArgumentException
{
    /**
     * @param  array<string, list<string>>  $errors  JSON pointer => messages
     */
    public function __construct(
        public readonly string $schemaId,
        public readonly array $errors,
    ) {
        $lines = [];

        foreach ($errors as $pointer => $messages) {
            foreach ($messages as $message) {
                $lines[] = "  {$pointer}: {$message}";
            }
        }

        parent::__construct("Payload does not match {$schemaId}:\n".implode("\n", $lines));
    }

    /**
     * @return list<string>
     */
    public function pointers(): array
    {
        return array_keys($this->errors);
    }
}
