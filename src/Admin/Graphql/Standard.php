<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql;

use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for all domains
 *
 * @package Admin
 * @subpackage GraphQL
 */
class Standard extends Base
{
    /**
     * Returns GraphQL schema definition for the available mutations
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL mutation schema definition
     */
    public function mutation(string $domain): array
    {
        return ['delete' . str_replace('/', '', ucwords($domain, '/')) => ['type' => Type::string(), 'args' => [['name' => 'id', 'type' => Type::string(), 'description' => 'Item ID']], 'resolve' => $this->delete_items($domain)], 'delete' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => Type::list_of(Type::string()), 'args' => [['name' => 'id', 'type' => Type::list_of(Type::string()), 'description' => 'List of item IDs']], 'resolve' => $this->delete_items($domain)], 'save' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->types()->output_type($domain), 'args' => [['name' => 'input', 'type' => $this->types()->input_type($domain), 'description' => 'Item object']], 'resolve' => $this->save_item($domain)], 'save' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => Type::list_of($this->types()->output_type($domain)), 'args' => [['name' => 'input', 'type' => Type::list_of($this->types()->input_type($domain)), 'description' => 'Item objects']], 'resolve' => $this->save_items($domain)]];
    }
    /**
     * Returns GraphQL schema definition for the available queries
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL query schema definition
     */
    public function query(string $domain): array
    {
        $list = ['get' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->types()->output_type($domain), 'args' => [['name' => 'id', 'type' => Type::string(), 'description' => 'Unique ID'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_item($domain)], 'search' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => $this->types()->search_output_type($domain), 'args' => [['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include'], ['name' => 'sort', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Sort keys'], ['name' => 'offset', 'type' => Type::int(), 'defaultValue' => 0, 'description' => 'Slice offset'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 100, 'description' => 'Slice size']], 'resolve' => $this->search_items($domain)]];
        if (!substr_compare($domain, '/type', -5)) {
            $list['find' . str_replace('/', '', ucwords($domain, '/'))] = ['type' => $this->types()->output_type($domain), 'args' => [['name' => 'code', 'type' => Type::string(), 'description' => 'Unique code'], ['name' => 'domain', 'type' => Type::string(), 'description' => 'Domain of the type']], 'resolve' => $this->find_type_item($domain)];
        }
        return $list;
    }
}