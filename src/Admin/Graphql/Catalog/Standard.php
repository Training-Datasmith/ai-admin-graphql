<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql\Catalog;

use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for special handling of categories
 *
 * @package Admin
 * @subpackage GraphQL
 */
class Standard extends \Aimeos\Admin\Graphql\Standard
{
    private \Aimeos\M_Shop\Common\Manager\Iface $manager;
    /**
     * Returns GraphQL schema definition for the available mutations
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL mutation schema definition
     */
    public function mutation(string $domain): array
    {
        return ['deleteCatalog' => ['type' => Type::string(), 'args' => [['name' => 'id', 'type' => Type::string(), 'description' => 'Item ID']], 'resolve' => $this->delete_items($domain)], 'deleteCatalogs' => ['type' => Type::list_of(Type::string()), 'args' => [['name' => 'id', 'type' => Type::list_of(Type::string()), 'description' => 'List of item IDs']], 'resolve' => $this->delete_items($domain)], 'saveCatalog' => ['type' => $this->types()->tree_output_type($domain), 'args' => [['name' => 'input', 'type' => $this->types()->input_type($domain), 'description' => 'Item object']], 'resolve' => $this->save_item($domain)], 'saveCatalogs' => ['type' => Type::list_of($this->types()->tree_output_type($domain)), 'args' => [['name' => 'input', 'type' => Type::list_of($this->types()->input_type($domain)), 'description' => 'Item objects']], 'resolve' => $this->save_items($domain)], 'insertCatalog' => ['type' => $this->types()->tree_output_type($domain), 'args' => [['name' => 'input', 'type' => Type::non_null($this->types()->input_type($domain)), 'description' => 'Item object'], ['name' => 'parentid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'ID of the parent category'], ['name' => 'refid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Category ID the new item should be inserted before']], 'resolve' => $this->insert_item($domain)], 'moveCatalog' => ['type' => Type::String(), 'args' => [['name' => 'id', 'type' => Type::non_null(Type::string()), 'description' => 'ID of the category to move'], ['name' => 'parentid', 'type' => Type::string(), 'description' => 'ID of the old parent category'], ['name' => 'targetid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'ID of the new parent category'], ['name' => 'refid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Category ID the new item should be inserted before']], 'resolve' => $this->move_item($domain)]];
    }
    /**
     * Returns GraphQL schema definition for the available queries
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL query schema definition
     */
    public function query(string $domain): array
    {
        return ['getCatalog' => ['type' => $this->types()->tree_output_type($domain), 'args' => [['name' => 'id', 'type' => Type::string(), 'description' => 'Unique ID'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_item($domain)], 'getCatalogPath' => ['type' => Type::list_of($this->types()->tree_output_type($domain)), 'args' => [['name' => 'id', 'type' => Type::non_null(Type::string()), 'description' => 'Unique category ID'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_path($domain)], 'getCatalogTree' => ['type' => $this->types()->tree_output_type($domain), 'args' => [['name' => 'id', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Unique category ID'], ['name' => 'level', 'type' => Type::int(), 'defaultValue' => 3, 'description' => '1 = node only, 2 = with children, 3 = whole subtree'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_tree($domain)], 'findCatalog' => ['type' => $this->types()->tree_output_type($domain), 'args' => [['name' => 'code', 'type' => Type::non_null(Type::string()), 'description' => 'Unique code'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->find_item($domain)], 'searchCatalogs' => ['type' => $this->types()->search_output_type($domain, fn(string $path): \Graph_Ql\Type\Definition\Object_Type => $this->types()->tree_output_type($path)), 'args' => [['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include'], ['name' => 'sort', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Sort keys'], ['name' => 'offset', 'type' => Type::int(), 'defaultValue' => 0, 'description' => 'Slice offset'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 100, 'description' => 'Slice size']], 'resolve' => $this->search_items($domain)], 'searchCatalogTree' => ['type' => Type::list_of($this->types()->tree_output_type($domain)), 'args' => [['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 100, 'description' => 'Slice size']], 'resolve' => $this->search_tree($domain)]];
    }
    /**
     * Returns the tree of parents including the given items as leaf nodes
     *
     * @param \Aimeos\Map $items List of items (with numeric indexes)
     * @param array $refs List of domains to fetch in addition
     * @return \Aimeos\Map List of parent items
     */
    protected function get_parents(\Aimeos\Map $items, array $refs): \Aimeos\Map
    {
        if (($parent_ids = $items->get_parent_id()->filter())->is_empty()) {
            return $items;
        }
        $manager = $this->manager();
        $filter = $manager->filter()->add('catalog.id', '==', $parent_ids->unique())->order(['-catalog.level', 'sort:catalog:position'])->slice(0, 0x7fffffff);
        $parents = $manager->search($filter, $refs);
        $indexes = $parent_ids->unique()->flip();
        $itemkeys = $items->get_id()->flip();
        foreach ($parents as $pid => $parent) {
            if (isset($itemkeys[$pid])) {
                $items[$itemkeys[$pid]]->add_child($items[$indexes[$pid]]);
                unset($items[$indexes[$pid]]);
            } else {
                $items[$indexes[$pid]] = $parent->add_child($items[$indexes[$pid]]);
            }
        }
        return $this->get_parents($items, $refs);
    }
    /**
     * Returns a closure for returning the nodes from the passed ID up to the root node
     *
     * @param string $domain Domain path of the manager
     * @return \Closure Anonymous method returning one item
     */
    protected function get_path(string $domain): \Closure
    {
        return function ($root, array $args, $context) use ($domain) {
            $this->access($domain, 'get');
            return $this->manager()->get_path($args['id'], $args['include']);
        };
    }
    /**
     * Returns a closure for returning the node tree
     *
     * @param string $domain Domain path of the manager
     * @return \Closure Anonymous method returning one item
     */
    protected function get_tree(string $domain): \Closure
    {
        return function ($root, array $args, $context) use ($domain) {
            $this->access($domain, 'get');
            return $this->manager()->get_tree($args['id'], $args['include'], $args['level']);
        };
    }
    /**
     * Returns a closure for inserting a new node into the tree
     *
     * @param string $domain Domain path of the manager
     * @return \Closure Anonymous method returning one item
     */
    protected function insert_item(string $domain): \Closure
    {
        return function ($root, array $args, $context) use ($domain) {
            if (empty($entry = $args['input'])) {
                throw new \Aimeos\Admin\Graphql\Exception('Parameter "input" must not be empty');
            }
            $this->access($domain, 'save');
            $manager = $this->manager();
            $item = $this->update_item($manager, $manager->create(), $entry);
            return $manager->insert($item, $args['parentid'], $args['refid']);
        };
    }
    /**
     * Returns the manager for the site items
     *
     * @return \Aimeos\MShop\Common\Manager\Iface Manager object
     */
    protected function manager(): \Aimeos\M_Shop\Common\Manager\Iface
    {
        if (!isset($this->manager)) {
            $this->manager = \Aimeos\M_Shop::create($this->context(), 'catalog');
        }
        return $this->manager;
    }
    /**
     * Returns a closure for moving a node within the tree
     *
     * @param string $domain Domain path of the manager
     * @return \Closure Anonymous method returning one item
     */
    protected function move_item(string $domain): \Closure
    {
        return function ($root, array $args, $context) use ($domain) {
            $this->access($domain, 'save');
            $this->manager()->move($args['id'], $args['parentid'], $args['targetid'], $args['refid']);
            return $args['id'];
        };
    }
    /**
     * Returns a closure for searching the tree
     *
     * @param string $domain Domain path of the manager
     * @return \Closure Anonymous method returning one item
     */
    protected function search_tree(string $domain): \Closure
    {
        return function ($root, array $args, $context) use ($domain): \Aimeos\Map {
            $this->access($domain, 'get');
            $manager = $this->manager();
            $filter = $manager->filter()->order(['-catalog.level', 'sort:catalog:position']);
            $filter->add($filter->parse(json_decode($args['filter'], true)));
            $items = $manager->search($filter->slice(0, $args['limit']), $args['include']);
            foreach ($items as $key => $item) {
                if (isset($items[$item->get_parent_id()])) {
                    $items[$item->get_parent_id()]->add_child($item);
                    unset($items[$key]);
                }
            }
            return $this->get_parents($items->values(), $args['include']);
        };
    }
}