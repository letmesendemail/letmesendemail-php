<?php

declare(strict_types=1);

namespace LetMeSendEmail\Requests;

final class TemplateVariable
{
    private string $key;
    private string $type;
    /** @var string|int|float */
    private $value;

    /**
     * @param string|int|float $value
     */
    public function __construct(string $key, string $type, $value)
    {
        $this->key = $key;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @return array{key: string, type: string, value: string|int|float}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
