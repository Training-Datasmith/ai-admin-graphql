<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin;

use Graph_Ql\Error\Debug_Flag;
use Graph_Ql\Graph_Ql as GraphQLBase;
use Graph_Ql\Type\Definition\Object_Type;
use Graph_Ql\Type\Schema;
use Graph_Ql\Validator\Document_Validator;
use Graph_Ql\Validator\Rules\Query_Complexity;
use Graph_Ql\Validator\Rules\Query_Depth;
use Nyholm\Psr7\Factory\Psr17Factory;
/**
 * Central GraphQL class for handling requests
 *
 * @package Admin
 * @subpackage GraphQL
 */
class Graphql
{
    /**
     * Executes the GraphQL request
     *
     * @param \Aimeos\MShop\ContextIface $context Context object
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
     */
    public static function execute(\Aimeos\M_Shop\Context_Iface $context, \Psr\Http\Message\Server_Request_Interface $request): \Psr\Http\Message\Response_Interface
    {
        try {
            /** admin/graphql/debug
             * Send debug information withing responses to clients if an error occurrs
             *
             * By default, the Aimeos admin GraphQL API won't send any details
             * besides the error message to the client if an error occurred. This
             * prevents leaking sensitive information to attackers. For debugging
             * your requests it's helpful to see the stack strace. If you set this
             * configuration option to true, the stack trace will be returned too.
             *
             * @param boolean True to return the stack trace in response, false for error message only
             * @since 2022.10
             * @category Developer
             */
            $debug = $context->config()->get('admin/graphql/debug', false);
            if (!is_array($input = $request->get_parsed_body())) {
                if (empty($input = json_decode($request->get_body()->get_contents() ?? '', true))) {
                    throw new \Aimeos\Admin\Graphql\Exception('Invalid input');
                }
            }
            if (isset($input['operations'])) {
                $map = json_decode($input['map'] ?? '[]', true);
                $operations = json_decode($input['operations'], true);
                $operations = self::files($request->get_uploaded_files(), $operations, $map);
                $opname = $operations['operationName'] ?? null;
                $variables = $operations['variables'] ?? [];
                $query = $operations['query'] ?? '';
            } else {
                $opname = $input['operationName'] ?? null;
                $variables = $input['variables'] ?? [];
                $query = $input['query'] ?? '';
            }
            $max_depth = (int) $context->config()->get('admin/graphql/max-query-depth', 10);
            $max_complexity = (int) $context->config()->get('admin/graphql/max-query-complexity', 500);
            $validation_rules = array_merge(Document_Validator::all_rules(), [Query_Depth::class => new Query_Depth($max_depth), Query_Complexity::class => new Query_Complexity($max_complexity)]);
            $result = Graph_Ql_Base::execute_query(
                self::schema($context),
                $query,
                [],
                // root
                null,
                // context
                $variables,
                $opname,
                null,
                // fieldResolver
                $validation_rules
            )->to_array($debug ? Debug_Flag::INCLUDE_DEBUG_MESSAGE | Debug_Flag::INCLUDE_TRACE : 0);
        } catch (\Throwable $t) {
            $error = ['message' => $t->get_message()];
            if ($debug) {
                $error['locations'] = $t->get_trace();
            }
            $result = ['errors' => [$error]];
        }
        $body = \Nyholm\Psr7\Stream::create(json_encode($result));
        return (new Psr17Factory())->create_response()->with_body($body);
    }
    /**
     * Maps the files to the corresponding variables
     *
     * @param array $files List of \Psr\Http\Message\UploadedFileInterface objects
     * @param array $operations GraphQL operations with "variables" section
     * @param array $map GraphQL variable mapping
     * @return array Mapped operations array
     * @see https://github.com/Ecodev/graphql-upload
     */
    protected static function files(array $files, array $operations, array $map): array
    {
        foreach ($map as $file_key => $locations) {
            foreach ($locations as $location) {
                $items =& $operations;
                foreach (explode('.', $location) as $key) {
                    if (!isset($items[$key]) || !is_array($items[$key])) {
                        $items[$key] = [];
                    }
                    $items =& $items[$key];
                }
                if (!array_key_exists($file_key, $files)) {
                    throw new \Aimeos\Admin\Graphql\Exception("GraphQL query declared an upload in `{$location}`, but no corresponding file were actually uploaded");
                }
                $items = $files[$file_key];
            }
        }
        return $operations;
    }
    /**
     * Returns the GraphQL schema
     *
     * @param \Aimeos\MShop\ContextIface $context Context object
     * @return \GraphQL\Type\Schema List of GraphQL query schema definitions
     */
    protected static function schema(\Aimeos\M_Shop\Context_Iface $context): \Graph_Ql\Type\Schema
    {
        $query = $mutation = [];
        $config = $context->config();
        $registry = new \Aimeos\Admin\Graphql\Registry($context);
        $stdname = $config->get('admin/graphql/name', 'Standard');
        $domains = $config->get('admin/graphql/domains', []);
        foreach ($domains as $domain) {
            $name = $config->get('admin/graphql/' . $domain . '/name', 'Standard');
            $classname = '\Aimeos\Admin\Graphql\\' . str_replace('/', '\\', ucwords($domain, '/')) . '\\' . $name;
            if (!class_exists($classname)) {
                $classname = '\Aimeos\Admin\Graphql\\' . $stdname;
            }
            $object = new $classname($context, $registry);
            $mutation = array_replace_recursive($mutation, $object->mutation($domain));
            $query = array_replace_recursive($query, $object->query($domain));
        }
        return new Schema(['query' => new Object_Type(['name' => 'query', 'fields' => $query]), 'mutation' => new Object_Type(['name' => 'mutation', 'fields' => $mutation])]);
    }
}