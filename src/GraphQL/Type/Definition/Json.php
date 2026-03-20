<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org) 2023-2026
 * @package GraphQL
 * @subpackage Type
 */
namespace Aimeos\Graph_Ql\Type\Definition;

use Graph_Ql\Error\Error;
use Graph_Ql\Language\Printer;
use Graph_Ql\Type\Definition\Scalar_Type;
/**
 * JSON data type for GraphQL PHP library
 */
class Json extends Scalar_Type
{
    private static ?\Aimeos\Graph_Ql\Type\Definition\Json $object = null;
    public ?string $description = 'Arbitrary data encoded in JavaScript Object Notation (JSON)';
    /**
     * Returns a singleton of the type object
     *
     * @return ScalarType Singleton of the type object
     */
    public static function type(): Scalar_Type
    {
        if (!isset(self::$object)) {
            self::$object = new self();
        }
        return self::$object;
    }
    /**
     * Returns the passed value serialized as JSON
     *
     * @param mixed $value Input value
     * @return string Valued serialized as JSON
     */
    public function serialize($value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }
    /**
     * Returns the deserialized value from the passed JSON string
     *
     * @param mixed $value Input value
     * @return string Deserialized value from JSON
     */
    public function parse_value($value)
    {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }
    /**
     * Returns the deserialized value from the passed GraphQL node
     *
     * @param mixed $node GraphQL node
     * @param array|null $variables Additional variable data
     * @return string Deserialized value from JSON
     */
    public function parse_literal($node, ?array $variables = null)
    {
        if (!property_exists($node, 'value')) {
            throw new Error('Can not parse literals without a value: {' . Printer::do_print($node) . '}.');
        }
        return json_decode($node->value, true, 512, JSON_THROW_ON_ERROR);
    }
}