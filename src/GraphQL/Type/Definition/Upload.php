<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org) 2024
 * @package GraphQL
 * @subpackage Type
 */
namespace Aimeos\Graph_Ql\Type\Definition;

use Graph_Ql\Error\Error;
use Graph_Ql\Error\Invariant_Violation;
use Graph_Ql\Type\Definition\Scalar_Type;
use Graph_Ql\Utils\Utils;
use Psr\Http\Message\Uploaded_File_Interface;
/**
 * Upload data type for GraphQL PHP library
 */
class Upload extends Scalar_Type
{
    private static ?\Aimeos\Graph_Ql\Type\Definition\Upload $object = null;
    public ?string $description = 'File upload type';
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
        throw new Invariant_Violation('"Upload" cannot be serialized, it can only be used as an argument.');
    }
    /**
     * Returns the deserialized value from the passed JSON string
     *
     * @param mixed $value Input value
     * @return UploadedFileInterface PSR-7 uploaded file object
     */
    public function parse_value($value)
    {
        if (!$value instanceof Uploaded_File_Interface) {
            $not_uploaded_file = Utils::print_safe($value);
            throw new Error("Could not get uploaded file, be sure to conform to GraphQL multipart request specification: https://github.com/jaydenseric/graphql-multipart-request-spec. Instead got: {$not_uploaded_file}.");
        }
        return $value;
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
        throw new Error('"Upload" cannot be hardcoded in a query. Be sure to conform to the GraphQL multipart request specification: https://github.com/jaydenseric/graphql-multipart-request-spec.');
    }
}