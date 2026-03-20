<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql\Customer;

use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for special handling of customers
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
        $list['aggregateCustomers'] = ['type' => $this->types()->aggregate_output_type($domain), 'args' => [['name' => 'key', 'type' => Type::list_of(Type::string()), 'description' => 'Aggregation key to group results by, e.g. "customer.status"'], ['name' => 'value', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Aggregate values from that column, e.g "customer.status" (optional, only if type is passed)'], ['name' => 'type', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Type of aggregation like "sum" or "avg" (default: null for count)'], ['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'sort', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Sort keys'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 10000, 'description' => 'Slice size']], 'resolve' => $this->aggregate_items($domain)];
        $list['findCustomer'] = ['type' => $this->types()->output_type($domain), 'args' => [['name' => 'code', 'type' => Type::string(), 'description' => 'Unique code'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->find_item($domain)];
        return $list;
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
        $view = $this->context()->view();
        $site_id = (string) $this->context()->user()?->get_site_id();
        if ($view->access(['super']) || strlen($site_id) > 0 && !strncmp($item->get_site_id(), $site_id, strlen($site_id))) {
            $item = $item->from_array($entry);
            if ($view->access(['super', 'admin'])) {
                $item->set_groups(array_unique($entry['groups'] ?? []));
            }
            if ($view->access(['super', 'admin']) || $item->get_id() === $this->context()->user()?->get_id()) {
                !isset($entry['customer.password']) ?: $item->set_password($entry['customer.password']);
                !isset($entry['customer.code']) ?: $item->set_code($entry['customer.code']);
            }
            if (isset($entry['address']) && $item instanceof \Aimeos\M_Shop\Common\Item\Address_Ref\Iface) {
                $item = $this->update_addresses($manager, $item, $entry['address']);
            }
            if (isset($entry['lists']) && $item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
                $item = $this->update_lists($manager, $item, $entry['lists']);
            }
            if (isset($entry['property']) && $item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
                $item = $this->update_properties($manager, $item, $entry['property']);
            }
        }
        return $item;
    }
}