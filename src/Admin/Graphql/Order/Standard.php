<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql\Order;

use Graph_Ql\Type\Definition\Input_Object_Type;
use Graph_Ql\Type\Definition\Object_Type;
use Graph_Ql\Type\Definition\Resolve_Info;
use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for special handling of customers
 *
 * @package Admin
 * @subpackage GraphQL
 */
class Standard extends \Aimeos\Admin\Graphql\Standard
{
    private array $types = [];
    /**
     * Returns GraphQL schema definition for the available mutations
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL mutation schema definition
     */
    public function mutation(string $domain): array
    {
        return ['save' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->order_output_type(), 'args' => [['name' => 'input', 'type' => $this->order_input_type($domain), 'description' => 'Item object']], 'resolve' => $this->save_item($domain)], 'save' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => Type::list_of($this->order_output_type()), 'args' => [['name' => 'input', 'type' => Type::list_of($this->order_input_type($domain)), 'description' => 'Item objects']], 'resolve' => $this->save_items($domain)]];
    }
    /**
     * Returns GraphQL schema definition for the available queries
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL query schema definition
     */
    public function query(string $domain): array
    {
        return ['aggregate' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => $this->types()->aggregate_output_type($domain), 'args' => [['name' => 'key', 'type' => Type::list_of(Type::string()), 'description' => 'Aggregation key to group results by, e.g. ["order.status", "order.price"]'], ['name' => 'value', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Aggregate values from that column, e.g "order.price" (optional, only if type is passed)'], ['name' => 'type', 'type' => Type::string(), 'defaultValue' => null, 'description' => 'Type of aggregation like "sum" or "avg" (default: null for count)'], ['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'sort', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Sort keys'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 10000, 'description' => 'Slice size']], 'resolve' => $this->aggregate_items($domain)], 'get' . str_replace('/', '', ucwords($domain, '/')) => ['type' => $this->order_output_type(), 'args' => [['name' => 'id', 'type' => Type::string(), 'description' => 'Unique ID'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include']], 'resolve' => $this->get_item($domain)], 'search' . str_replace('/', '', ucwords($domain, '/')) . 's' => ['type' => $this->types()->search_output_type($domain, fn($path): \Graph_Ql\Type\Definition\Object_Type => $this->order_output_type()), 'args' => [['name' => 'filter', 'type' => Type::string(), 'defaultValue' => '{}', 'description' => 'Filter conditions'], ['name' => 'include', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Domains to include'], ['name' => 'sort', 'type' => Type::list_of(Type::string()), 'defaultValue' => [], 'description' => 'Sort keys'], ['name' => 'offset', 'type' => Type::int(), 'defaultValue' => 0, 'description' => 'Slice offset'], ['name' => 'limit', 'type' => Type::int(), 'defaultValue' => 100, 'description' => 'Slice size']], 'resolve' => $this->search_items($domain)]];
    }
    /**
     * Defines the GraphQL order input type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function order_input_type(string $path): Input_Object_Type
    {
        $name = 'orderInput';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context(), $path);
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['address'] = Type::list_of($this->types()->input_type($path . '/address'));
            $list['product'] = Type::list_of($this->order_product_input_type($path . '/product'));
            $list['service'] = Type::list_of($this->order_service_input_type($path . '/service'));
            return $list;
        }, 'parseValue' => fn(array $values) => $this->types()->prefix($path, $values)]);
    }
    /**
     * Defines the GraphQL order product input type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function order_product_input_type(string $path): Input_Object_Type
    {
        $name = 'orderProductInput';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context(), $path);
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['product'] = Type::list_of($this->order_sub_product_input_type($path));
            $list['attribute'] = Type::list_of($this->types()->input_type($path . '/attribute'));
            return $list;
        }, 'parseValue' => fn(array $values) => $this->types()->prefix($path, $values)]);
    }
    /**
     * Defines the GraphQL order sub-product input type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function order_sub_product_input_type(string $path): Input_Object_Type
    {
        $name = 'orderSubProductInput';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context(), $path);
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['attribute'] = Type::list_of($this->types()->input_type($path . '/attribute'));
            return $list;
        }, 'parseValue' => fn(array $values) => $this->types()->prefix($path, $values)]);
    }
    /**
     * Defines the GraphQL order service input type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function order_service_input_type(string $path): Input_Object_Type
    {
        $name = 'orderServiceInput';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context(), $path);
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['attribute'] = Type::list_of($this->types()->input_type($path . '/attribute'));
            $list['transaction'] = Type::list_of($this->types()->input_type($path . '/transaction'));
            return $list;
        }, 'parseValue' => fn(array $values) => $this->types()->prefix($path, $values)]);
    }
    /**
     * Defines the GraphQL order output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_output_type(): Object_Type
    {
        $name = 'orderOutputType';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order');
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['address'] = ['type' => Type::list_of($this->order_address_output_type()), 'resolve' => fn($item) => $item->get_addresses()->flat(1)];
            $list['coupon'] = ['type' => Type::list_of($this->order_coupon_output_type()), 'resolve' => fn($item) => $item->get_coupons()->keys()->all()];
            $list['product'] = ['type' => Type::list_of($this->order_product_output_type()), 'resolve' => fn($item) => $item->get_products()];
            $list['service'] = ['type' => Type::list_of($this->order_service_output_type()), 'resolve' => fn($item) => $item->get_services()->flat(1)];
            $list['status'] = ['type' => Type::list_of($this->order_status_output_type()), 'resolve' => fn($item) => $item->get_statuses()->flat(1)];
            return $list;
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order address output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_address_output_type(): Object_Type
    {
        $name = 'orderAddressOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/address');
            return $this->types()->fields($manager->get_search_attributes(false));
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Address\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/address', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order coupon output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_coupon_output_type(): Object_Type
    {
        $name = 'orderCouponOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => fn() => ['code' => ['name' => 'code', 'description' => 'Coupon codes', 'type' => Type::String()]], 'resolveField' => fn($codes, array $args, $context, Resolve_Info $info) => (string) $codes]);
    }
    /**
     * Defines the GraphQL order product output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_product_output_type(): Object_Type
    {
        $name = 'orderProductOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/product');
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['product'] = ['type' => Type::list_of($this->order_sub_product_output_type()), 'resolve' => fn($item) => $item->get_products()];
            $list['attribute'] = ['type' => Type::list_of($this->order_product_attribute_output_type()), 'args' => ['type' => Type::String()], 'resolve' => fn($item, $args) => $item->get_attribute_items($args['type'] ?? null)];
            return $list;
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Product\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/product', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order sub-product output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_sub_product_output_type(): Object_Type
    {
        $name = 'orderSubProductOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/product');
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['attribute'] = ['type' => Type::list_of($this->order_product_attribute_output_type()), 'args' => ['type' => Type::String()], 'resolve' => fn($item, $args) => $item->get_attribute_items($args['type'] ?? null)];
            return $list;
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Product\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/product', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order product attribute output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_product_attribute_output_type(): Object_Type
    {
        $name = 'orderProductAttributeOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/product/attribute');
            return $this->types()->fields($manager->get_search_attributes(false));
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Product\Attribute\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/product/attribute', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order service output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_service_output_type(): Object_Type
    {
        $name = 'orderServiceOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/service');
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $list['attribute'] = ['type' => Type::list_of($this->order_service_attribute_output_type()), 'args' => ['type' => Type::String()], 'resolve' => fn($item, $args) => $item->get_attribute_items($args['type'] ?? null)];
            $list['transaction'] = ['type' => Type::list_of($this->order_service_transaction_output_type()), 'args' => ['type' => Type::String()], 'resolve' => fn($item, $args) => $item->get_transactions($args['type'] ?? null)];
            return $list;
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Service\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/service', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order service attribute output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_service_attribute_output_type(): Object_Type
    {
        $name = 'orderServiceAttributeOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/service/attribute');
            return $this->types()->fields($manager->get_search_attributes(false));
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Service\Attribute\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/service/attribute', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order service transaction output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_service_transaction_output_type(): Object_Type
    {
        $name = 'orderServiceTransactionOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/service/transaction');
            return $this->types()->fields($manager->get_search_attributes(false));
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Service\Transaction\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/service/transaction', $info->field_name)]);
    }
    /**
     * Defines the GraphQL order status output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function order_status_output_type(): Object_Type
    {
        $name = 'orderStatusOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context(), 'order/status');
            return $this->types()->fields($manager->get_search_attributes(false));
        }, 'resolveField' => fn(\Aimeos\M_Shop\Order\Item\Status\Iface $item, array $args, $context, Resolve_Info $info) => $this->types()->resolve($item, 'order/status', $info->field_name)]);
    }
}