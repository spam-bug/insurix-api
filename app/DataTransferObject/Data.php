<?php

declare(strict_types=1);

namespace App\DataTransferObject;

use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

abstract class Data
{
    public static function fromArray(array $data): static
    {
        $parameters = [];

        foreach ((new ReflectionClass(static::class))->getConstructor()->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        $arguments = [];

        foreach ($data as $key => $value) {
            $property = Str::camel($key);
            $arguments[$property] = static::cast($parameters[$property] ?? null, $value);
        }

        return new static(...$arguments);
    }

    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $property => $value) {
            $data[Str::snake($property)] = $value;
        }

        return $data;
    }

    /**
     * Turns raw input into the constructor parameter's type: backed enums, dates (in UTC) and
     * scalars. A null for a non-nullable parameter falls back to its default.
     */
    private static function cast(?ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter?->getType();

        if (! $type instanceof ReflectionNamedType) {
            return $value;
        }

        if ($value === null) {
            return ! $type->allowsNull() && $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
        }

        $class = $type->getName();

        if ($type->isBuiltin()) {
            return is_scalar($value) && in_array($class, ['int', 'float', 'bool', 'string'], true) ? match ($class) {
                'int' => (int) $value,
                'float' => (float) $value,
                'bool' => (bool) $value,
                'string' => (string) $value,
            } : $value;
        }

        return match (true) {
            $value instanceof $class => $value,
            is_subclass_of($class, BackedEnum::class) => $class::from($value),
            is_a($class, CarbonImmutable::class, true) => CarbonImmutable::parse($value)->utc(),
            default => $value,
        };
    }
}
