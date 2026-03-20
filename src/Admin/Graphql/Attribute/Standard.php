<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql\Attribute;

use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for special handling of attributes
 *
 * @package Admin
 * @subpackage GraphQL
 */
class Standard extends \Aimeos\Admin\Graphql\Standard
{
    /**
     * Returns GraphQL schema definition for the available queries
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL query schema definition
     */
    public function query(string $domain): array
    {
        $list = parent::query($domain);
        $list['findAttribute'] = ['type' => $this->types()->output_type($domain), 'args' => [['name' => 'code', 'type' => Type::string(), 'description' => 'Unique code'], ['name' => 'domain', 'type' => Type::string(), 'description' => 'Domain of the attribute'], ['name' => 'type', 'type' => Type::string(), 'description' => 'Attribute type'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->find_item($domain)];
        return $list;
    }
    /**
     * Returns a closure for returning a single item by its code
     *
     * @param string $domain Domain path of the manager
     * @return \Closure Anonymous method returning one item
     */
    protected function find_item(string $domain): \Closure
    {
        return fn($root, $args, $context) => \Aimeos\M_Shop::create($this->context(), $domain)->find($args['code'], $args['include'], $args['domain'], $args['type']);
    }
}