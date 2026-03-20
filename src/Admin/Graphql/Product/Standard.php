<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql\Product;

use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for special handling of products
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
        $list['findProduct'] = ['type' => $this->types()->output_type($domain), 'args' => [['name' => 'code', 'type' => Type::string(), 'description' => 'Unique code'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->find_item($domain)];
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
        $item = parent::update_item($manager, $item, $entry);
        if (isset($entry['product.stock'])) {
            $stock_items = $item->get_stock_items()->col(null, 'stock.type');
            foreach ($entry['product.stock'] as $subentry) {
                $stock_item = $stock_items->get($subentry['stock.type'] ?? null) ?: $manager->create_stock_item();
                $item->add_stock_item($stock_item->from_array($subentry));
            }
            $item->delete_stock_items($stock_items);
        }
        return $item;
    }
}