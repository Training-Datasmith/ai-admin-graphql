<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql\Locale\Site;

use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for special handling of locale sites
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
        return ['delete' . str_replace('/', '', ucwords($domain, '/')) => ['type' => Type::string(), 'args' => [['name' => 'id', 'type' => Type::string(), 'description' => 'Item ID']], 'resolve' => $this->delete_items($domain)], 'delete' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => Type::list_of(Type::string()), 'args' => [['name' => 'id', 'type' => Type::list_of(Type::string()), 'description' => 'List of item IDs']], 'resolve' => $this->delete_items($domain)], 'insert' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->types()->site_output_type(), 'args' => [['name' => 'input', 'type' => Type::non_null($this->types()->input_type($domain)), 'description' => 'Item object'], ['name' => 'parentid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'ID of the parent site'], ['name' => 'refid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Site ID the new item should be inserted before']], 'resolve' => $this->insert_item($domain)], 'move' . str_replace('/', '', ucwords($domain, '/')) => ['type' => Type::String(), 'args' => [['name' => 'id', 'type' => Type::non_null(Type::string()), 'description' => 'ID of the site to move'], ['name' => 'parentid', 'type' => Type::string(), 'description' => 'ID of the old parent site'], ['name' => 'targetid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'ID of the new parent site'], ['name' => 'refid', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Site ID the new item should be inserted before']], 'resolve' => $this->move_item($domain)], 'save' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->types()->site_output_type(), 'args' => [['name' => 'input', 'type' => $this->types()->input_type($domain), 'description' => 'Item object']], 'resolve' => $this->save_item($domain)], 'save' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => Type::list_of($this->types()->site_output_type()), 'args' => [['name' => 'input', 'type' => Type::list_of($this->types()->input_type($domain)), 'description' => 'Item objects']], 'resolve' => $this->save_items($domain)]];
    }
    /**
     * Returns GraphQL schema definition for the available queries
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL query schema definition
     */
    public function query(string $domain): array
    {
        return ['find' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->types()->site_output_type(), 'args' => [['name' => 'code', 'type' => Type::non_null(Type::string()), 'description' => 'Unique code'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->find_item($domain)], 'get' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->types()->site_output_type(), 'args' => [['name' => 'id', 'type' => Type::string(), 'description' => 'Unique ID'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_item($domain)], 'get' . str_replace('/', '', ucwords($domain, '/')) . 'Path' => ['type' => Type::list_of($this->types()->site_output_type()), 'args' => [['name' => 'id', 'type' => Type::non_null(Type::string()), 'description' => 'Unique site ID'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_path($domain)], 'get' . str_replace('/', '', ucwords($domain, '/')) . 'Tree' => ['type' => $this->types()->site_output_type(), 'args' => [['name' => 'id', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Unique site ID'], ['name' => 'level', 'type' => Type::int(), 'defaultValue' => 3, 'description' => '1 = node only, 2 = with children, 3 = whole subtree'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_tree($domain)], 'search' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => $this->types()->search_output_type($domain, fn($path): \Graph_Ql\Type\Definition\Object_Type => $this->types()->site_output_type()), 'args' => [['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include'], ['name' => 'sort', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Sort keys'], ['name' => 'offset', 'type' => Type::int(), 'defaultValue' => 0, 'description' => 'Slice offset'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 100, 'description' => 'Slice size']], 'resolve' => $this->search_items($domain)], 'search' . str_replace('/', '', ucwords($domain, '/')) . 'Tree' => ['type' => Type::list_of($this->types()->site_output_type()), 'args' => [['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 100, 'description' => 'Slice size']], 'resolve' => $this->search_tree($domain)]];
    }
    /**
     * Returns the item if not removed for security reasons
     *
     * @param \Aimeos\MShop\Common\Item\Iface $item Item to check
     * @return \Aimeos\MShop\Common\Item\Iface Item if not removed
     */
    protected function filter(\Aimeos\M_Shop\Common\Item\Iface $item): \Aimeos\M_Shop\Common\Item\Iface
    {
        $siteid = (string) $this->context()->user()?->get_site_id();
        if ($item->get_site_id() && strncmp($item->get_site_id(), $siteid, strlen($siteid))) {
            throw new \Aimeos\Admin\Graphql\Exception('Forbidden', 403);
        }
        return $item;
    }
    /**
     * Returns the items if not removed for security reasons
     *
     * @param iterable $items List of items to check
     * @return iterable List of items not removed
     */
    protected function filters(iterable $items): iterable
    {
        $list = [];
        $siteid = (string) $this->context()->user()?->get_site_id();
        foreach ($items as $id => $item) {
            if (!($item->get_site_id() && strncmp($item->get_site_id(), $siteid, strlen($siteid)))) {
                $list[$id] = $item;
            }
        }
        return $list;
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
        $filter = $manager->filter()->add('locale.site.siteid', '=~', (string) $this->context()->user()?->get_site_id())->add('locale.site.id', '==', $parent_ids->unique())->order(['-locale.site.level', 'sort:locale.site:position'])->slice(0, 0x7fffffff);
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
        return function ($root, array $args, $context) use ($domain): iterable {
            $this->access($domain, 'get');
            return $this->filters($this->manager()->get_path($args['id'], $args['include']));
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
        return function ($root, array $args, $context) use ($domain): \Aimeos\M_Shop\Common\Item\Iface {
            $this->access($domain, 'get');
            return $this->filter($this->manager()->get_tree($args['id'], $args['include'], $args['level']));
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
            $this->access($domain, 'insert');
            $manager = $this->manager();
            $item = $manager->create()->from_array($entry, true);
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
            $this->manager = \Aimeos\M_Shop::create($this->context(), 'locale/site');
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
            $this->access($domain, 'move');
            $this->manager()->move($args['id'], $args['parentid'], $args['targetid'], $args['refid']);
            return $args['id'];
        };
    }
    /**
     * Returns a closure for returning several items
     *
     * @param string $domain Domain path of the manager
     * @return \Closure Anonymous method returning several items
     */
    protected function search_items(string $domain): \Closure
    {
        return function ($root, array $args, $context) use ($domain): array {
            $this->access($domain, 'get');
            $manager = \Aimeos\M_Shop::create($this->context(), $domain);
            $prefix = str_replace('/', '.', $domain);
            $filter = $manager->filter()->order($args['sort'])->slice($args['offset'], $args['limit']);
            $filter->add($prefix . '.siteid', '=~', (string) $this->context()->user()?->get_site_id());
            $filter->add($filter->parse(json_decode($args['filter'], true)));
            $total = 0;
            $items = $manager->search($filter, $args['include'], $total)->to_array();
            return ['items' => $items, 'total' => $total];
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
            $filter = $manager->filter()->order(['-locale.site.level', 'sort:locale.site:position']);
            $filter->add('locale.site.siteid', '=~', (string) $this->context()->user()?->get_site_id());
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
    /**
     * Updates the item
     *
     * @param \Aimeos\MShop\Common\Manager\Iface $manager Manager object for the passed item
     * @param \Aimeos\MShop\Common\Item\AdddressRef\Iface $item Item to update
     * @param array $entry Associative list of key/value pairs of the item data
     * @return \Aimeos\MShop\Common\Item\Iface Updated item
     */
    protected function update_item(\Aimeos\M_Shop\Common\Manager\Iface $manager, \Aimeos\M_Shop\Common\Item\Iface $item, array $entry): \Aimeos\M_Shop\Common\Item\Iface
    {
        $super = $this->context()->view()->access(['super']);
        $siteid = (string) $this->context()->user()?->get_site_id();
        if (!$super && (!$siteid || !$item->get_site_id() || strncmp($item->get_site_id(), $siteid, strlen($siteid)))) {
            throw new \Aimeos\Admin\Graphql\Exception('Forbidden', 403);
        }
        return $item->from_array($entry, true);
    }
}