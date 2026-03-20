<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql;

/**
 * Trait providing the methods for updating the items
 *
 * @package Admin
 * @subpackage GraphQL
 */
trait Update_Trait
{
    /**
     * Updates the addresses of the item
     *
     * @param \Aimeos\MShop\Common\Manager\Iface $manager Manager object for the passed item
     * @param \Aimeos\MShop\Common\Item\AdddressRef\Iface $item Item to update
     * @param array $entries List of entries with key/value pairs of the address data
     * @return \Aimeos\MShop\Common\Item\Iface Updated item
     */
    protected function update_addresses(\Aimeos\M_Shop\Common\Manager\Iface $manager, \Aimeos\M_Shop\Common\Item\Adddress_Ref\Iface $item, array $entries): \Aimeos\M_Shop\Common\Item\Iface
    {
        $address_items = $item->get_addresses()->reverse();
        foreach ($entries as $subentry) {
            $address = $address_items->pop() ?: $manager->create_address_item();
            $item->add_address_item($address->from_array($subentry));
        }
        return $item->delete_address_items($address_items);
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
        $item = $item->from_array($entry, true);
        if (isset($entry['address']) && $item instanceof \Aimeos\M_Shop\Common\Item\Address_Ref\Iface) {
            $item = $this->update_addresses($manager, $item, $entry['address']);
        }
        if (isset($entry['lists']) && $item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
            $item = $this->update_lists($manager, $item, $entry['lists']);
        }
        if (isset($entry['property']) && $item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
            return $this->update_properties($manager, $item, $entry['property']);
        }
        return $item;
    }
    /**
     * Updates the list references of the item
     *
     * @param \Aimeos\MShop\Common\Manager\Iface $manager Manager object for the passed item
     * @param \Aimeos\MShop\Common\Item\ListsRef\Iface $item Item to update
     * @param array $entries List of entries with key/value pairs of the reference data
     * @return \Aimeos\MShop\Common\Item\Iface Updated item
     */
    protected function update_lists(\Aimeos\M_Shop\Common\Manager\Iface $manager, \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface $item, array $entries): \Aimeos\M_Shop\Common\Item\Iface
    {
        $resource = $item->get_resource_type();
        foreach ($entries as $domain => $list) {
            $domain_manager = \Aimeos\M_Shop::create($this->context(), $domain);
            $list_items = $item->get_list_items($domain, null, null, false);
            $ref_items = $item->get_ref_items($domain, null, null, false);
            foreach ($list as $subentry) {
                $ref_item = null;
                $list_id = $subentry[$resource . '.lists.id'] ?? '';
                $list_type = $subentry[$resource . '.lists.type'] ?? 'default';
                $ref_id = $subentry['item'][$domain . '.id'] ?? $subentry[$resource . '.lists.refid'] ?? '';
                $list_item = $list_items->get($list_id) ?? $item->get_list_item($domain, $list_type, $ref_id) ?? $manager->create_list_item();
                if (isset($subentry['item'])) {
                    $ref_item = ($list_item->get_ref_item() ?? $ref_items->get($ref_id) ?? $domain_manager->create())->from_array($subentry['item'], true);
                }
                if (isset($subentry['item']['address']) && $ref_item instanceof \Aimeos\M_Shop\Common\Item\Address_Ref\Iface) {
                    $ref_item = $this->update_addresses($domain_manager, $ref_item, $subentry['item']['address']);
                }
                if (isset($subentry['item']['lists']) && $ref_item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
                    $ref_item = $this->update_lists($domain_manager, $ref_item, $subentry['item']['lists']);
                }
                if (isset($subentry['item']['property']) && $ref_item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
                    $ref_item = $this->update_properties($domain_manager, $ref_item, $subentry['item']['property']);
                }
                $item->add_list_item($domain, $list_item->from_array($subentry, true), $ref_item);
                unset($list_items[$list_item->get_id()]);
            }
            $item->delete_list_items($list_items);
        }
        return $item;
    }
    /**
     * Updates the properties of the item
     *
     * @param \Aimeos\MShop\Common\Manager\Iface $manager Manager object for the passed item
     * @param \Aimeos\MShop\Common\Item\ListsRef\Iface $item Item to update
     * @param array $entries List of entries with key/value pairs of the property data
     * @return \Aimeos\MShop\Common\Item\Iface Updated item
     */
    protected function update_properties(\Aimeos\M_Shop\Common\Manager\Iface $manager, \Aimeos\M_Shop\Common\Item\Property_Ref\Iface $item, array $entries): \Aimeos\M_Shop\Common\Item\Iface
    {
        $prop_items = $item->get_property_items()->reverse();
        foreach ($entries as $subentry) {
            $prop_item = $prop_items->pop() ?: $manager->create_property_item();
            $item->add_property_item($prop_item->from_array($subentry));
        }
        return $item->delete_property_items($prop_items);
    }
}