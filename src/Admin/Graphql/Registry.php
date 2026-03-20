<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2022-2026
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql;

use Aimeos\M_Shop\Common\Item\Iface as ItemIface;
use Graph_Ql\Type\Definition\Input_Object_Type;
use Graph_Ql\Type\Definition\Object_Type;
use Graph_Ql\Type\Definition\Resolve_Info;
use Graph_Ql\Type\Definition\Type;
/**
 * Type registry for defining the GraphQL types
 *
 * @package Admin
 * @subpackage GraphQL
 */
class Registry
{
    private \Aimeos\M_Shop\Context_Iface $context;
    private array $types = [];
    /**
     * Initializes the object
     *
     * @param \Aimeos\MShop\ContextIface $context Context object
     */
    public function __construct(\Aimeos\M_Shop\Context_Iface $context)
    {
        $this->context = $context;
    }
    /**
     * Defines the GraphQL input types
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function input_type(string $path): Input_Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'Input';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            $list = $this->fields($manager->get_search_attributes(false));
            $item = $manager->create();
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Address_Ref\Iface) {
                $list['address'] = $this->address_input_type($path . '/address');
            }
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
                $list['lists'] = $this->lists_input_type($path . '/lists');
            }
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
                $list['property'] = Type::list_of($this->input_type($path . '/property'));
            }
            if ($item instanceof \Aimeos\M_Shop\Product\Item\Iface) {
                $list['stock'] = Type::list_of($this->input_type('stock'));
            }
            return $list;
        }, 'parseValue' => fn(array $values) => $this->prefix($path, $values)]);
    }
    /**
     * Defines the GraphQL address input type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function address_input_type(string $path): Input_Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'Input';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            return $this->fields($manager->get_search_attributes(false));
        }, 'parseValue' => fn(array $values) => $this->prefix($path, $values)]);
    }
    /**
     * Defines the GraphQL lists input type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function lists_input_type(string $path): Input_Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'refInput';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            if ($domains = $this->context->config()->get('admin/graphql/lists-domains', [])) {
                foreach ($domains as $domain) {
                    $list[str_replace('/', '', $domain)] = Type::list_of($this->lists_ref_input_type($path, $domain));
                }
            }
            return $list;
        }]);
    }
    /**
     * Defines the GraphQL lists input types referenced by lists
     *
     * @param string $path Path of the domain manager
     * @param string $domain Domain name of the referenced item
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function lists_ref_input_type(string $path, string $domain): Input_Object_Type
    {
        $name = str_replace('/', '', ucwords($path . '/' . $domain, '/')) . 'Input';
        return $this->types[$name] ?? $this->types[$name] = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path, $domain): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            $list = $this->fields($manager->get_search_attributes(false));
            $list['item'] = $this->input_type($domain);
            return $list;
        }, 'parseValue' => fn(array $values) => $this->prefix($path, $values)]);
    }
    /**
     * Defines the GraphQL output types
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function output_type(string $path): Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'Output';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            $list = $this->fields($manager->get_search_attributes(false));
            $item = $manager->create();
            if ($item instanceof \Aimeos\M_Shop\Customer\Item\Iface) {
                $list['groups'] = ['type' => Type::list_of(Type::String()), 'description' => 'List of group IDs assigned to the account', 'resolve' => fn($item, $args) => $item->get_groups()];
            }
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Address_Ref\Iface) {
                $list['address'] = Type::list_of($this->address_output_type($path . '/address'));
            }
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Tree\Iface) {
                $list['children'] = Type::list_of($this->tree_output_type($path));
            }
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
                $list['lists'] = ['type' => $this->lists_output_type($path . '/lists'), 'resolve' => fn(Item_Iface $item, array $args) => $item];
            }
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
                $list['property'] = ['type' => Type::list_of($this->property_output_type($path . '/property')), 'args' => ['type' => Type::list_of(Type::String())], 'resolve' => fn($item, $args) => $item->get_property_items($args['type'] ?? null, false)];
            }
            if ($item instanceof \Aimeos\M_Shop\Product\Item\Iface) {
                $list['stock'] = ['type' => Type::list_of($this->stock_output_type()), 'args' => ['type' => Type::list_of(Type::String())], 'resolve' => fn($item, $args) => $item->get_stock_items($args['type'] ?? null, false)];
            }
            return $list;
        }, 'resolveField' => fn(Item_Iface $item, array $args, $context, Resolve_Info $info) => $this->resolve($item, $path, $info->field_name)]);
    }
    /**
     * Defines the GraphQL address output type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function address_output_type(string $path): Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'Output';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            return $this->fields($manager->get_search_attributes(false));
        }, 'resolveField' => function (Item_Iface $item, array $args, $context, Resolve_Info $info) use ($path) {
            if ($info->field_name === 'address' && $item instanceof \Aimeos\M_Shop\Common\Item\Address_Ref\Iface) {
                return $item->get_address_items();
            }
            return $this->resolve($item, $path, $info->field_name);
        }]);
    }
    /**
     * Defines the GraphQL tree output type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function aggregate_output_type(string $path): Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'AggregateOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => fn() => ['aggregates' => Type::string()], 'resolveField' => fn(array $entry, array $args, $context, Resolve_Info $info) => json_encode($entry, JSON_FORCE_OBJECT)]);
    }
    /**
     * Defines the GraphQL config output type
     *
     * @param string $path Path of the domain to retrieve the configuration
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function config_output_type(string $path): Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'ConfigOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => fn() => ['code' => ['name' => 'code', 'type' => Type::String()], 'label' => ['name' => 'label', 'type' => Type::String()], 'type' => ['name' => 'type', 'type' => Type::String()], 'required' => ['name' => 'required', 'type' => Type::Boolean()], 'default' => ['name' => 'default', 'type' => \Aimeos\Graph_Ql\Type\Definition\Json::type()]], 'resolveField' => function ($item, array $args, $context, Resolve_Info $info) {
            switch ($info->field_name) {
                case 'code':
                    return $item->get_code();
                case 'label':
                    return $item->get_label();
                case 'type':
                    return $item->get_type();
                case 'required':
                    return $item->is_required();
                case 'default':
                    return $item->get_default();
            }
        }]);
    }
    /**
     * Defines the GraphQL list reference output type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function lists_output_type(string $path): Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'refOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            if ($domains = $this->context->config()->get('admin/graphql/lists-domains', [])) {
                foreach ($domains as $domain) {
                    $list[str_replace('/', '', $domain)] = ['type' => Type::list_of($this->lists_ref_output_type($path, $domain)), 'args' => ['listtype' => Type::list_of(Type::String()), 'type' => Type::list_of(Type::String())], 'resolve' => fn($item, $args) => $item->get_list_items($domain, $args['listtype'] ?? null, $args['type'] ?? null, false)];
                }
            }
            return $list;
        }]);
    }
    /**
     * Defines the GraphQL lists output type
     *
     * @param string $path Path of the domain manager
     * @param string $domain Domain name of the referenced item
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function lists_ref_output_type(string $path, string $domain): Object_Type
    {
        $name = str_replace('/', '', ucwords($path . '/' . $domain, '/')) . 'Output';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function () use ($path, $domain): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            $list = $this->fields($manager->get_search_attributes(false));
            $list['item'] = $this->output_type($domain);
            return $list;
        }, 'resolveField' => function (Item_Iface $item, array $args, $context, Resolve_Info $info) use ($path) {
            if ($info->field_name === 'item' && $item instanceof \Aimeos\M_Shop\Common\Item\Lists\Iface) {
                return $item->get_ref_item();
            }
            return $this->resolve($item, $path, $info->field_name);
        }]);
    }
    /**
     * Defines the GraphQL property output type
     *
     * @param string $path Path of the manager which is using the property item
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function property_output_type(string $path): Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'Output';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            return $this->fields($manager->get_search_attributes(false));
        }, 'resolveField' => function (Item_Iface $item, array $args, $context, Resolve_Info $info) use ($path) {
            if ($info->field_name === 'property' && $item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
                return $item->get_property_items();
            }
            return $this->resolve($item, $path, $info->field_name);
        }]);
    }
    /**
     * Defines the GraphQL search output types
     *
     * @param string $path Path of the domain manager
     * @param Closure|null Output type method (default: outputType())
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function search_output_type(string $path, ?\Closure $method = null): Object_Type
    {
        $name = 'search' . str_replace('/', '', ucwords($path)) . 'Output';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => fn() => ['items' => ['name' => 'items', 'description' => 'List of items', 'type' => Type::list_of($method ? $method($path) : $this->output_type($path))], 'total' => ['name' => 'total', 'description' => 'Total number of items', 'type' => Type::int()]], 'resolveField' => fn(array $map, array $args, $context, Resolve_Info $info) => $map[$info->field_name] ?? null]);
    }
    /**
     * Defines the GraphQL locale site output types
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function site_output_type(): Object_Type
    {
        $name = 'siteOutputType';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context, 'locale/site');
            $list = $this->fields($manager->get_search_attributes(false));
            $list['children'] = Type::list_of($this->site_output_type());
            $list['hasChildren'] = ['name' => 'hasChildren', 'description' => 'If node has children', 'type' => Type::boolean()];
            return $list;
        }, 'resolveField' => function (Item_Iface $item, array $args, $context, Resolve_Info $info) {
            if (!$item instanceof \Aimeos\M_Shop\Common\Item\Tree\Iface) {
                return $this->resolve($item, 'locale/site', $info->field_name);
            }
            if ($info->field_name === 'children') {
                return $item->get_children();
            }
            if ($info->field_name === 'hasChildren') {
                return $item->has_children();
            }
            return $this->resolve($item, 'locale/site', $info->field_name);
        }]);
    }
    /**
     * Defines the GraphQL stock output type
     *
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    protected function stock_output_type(): Object_Type
    {
        $name = 'StockOutputType';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function (): array {
            $manager = \Aimeos\M_Shop::create($this->context, 'stock');
            return $this->fields($manager->get_search_attributes(false));
        }, 'resolveField' => function (Item_Iface $item, array $args, $context, Resolve_Info $info) {
            if ($info->field_name === 'stock' && $item instanceof \Aimeos\M_Shop\Product\Item\Iface) {
                return $item->get_stock_items();
            }
            return $this->resolve($item, 'stock', $info->field_name);
        }]);
    }
    /**
     * Defines the GraphQL tree output type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\ObjectType Output type definition
     */
    public function tree_output_type(string $path): Object_Type
    {
        $name = str_replace('/', '', ucwords($path, '/')) . 'TreeOutput';
        return $this->types[$name] ?? $this->types[$name] = new Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context, $path);
            $item = $manager->create();
            $list = $this->fields($manager->get_search_attributes(false));
            $list['children'] = Type::list_of($this->tree_output_type($path));
            $list['hasChildren'] = ['name' => 'hasChildren', 'description' => 'If node has children', 'type' => Type::boolean()];
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
                $list['lists'] = ['type' => $this->lists_output_type($path . '/lists'), 'resolve' => fn(Item_Iface $item, array $args) => $item];
            }
            return $list;
        }, 'resolveField' => function (Item_Iface $item, array $args, $context, Resolve_Info $info) use ($path) {
            if ($info->field_name === 'children') {
                return $item->get_children();
            }
            if ($info->field_name === 'hasChildren') {
                return $item->has_children();
            }
            return $this->resolve($item, $path, $info->field_name);
        }]);
    }
    /**
     * Returns the field types for the passed search attributes
     *
     * @param array $attrs List of search attribute items implementing \Aimeos\Base\Criteria\Attribute\Iface
     * @return array Associative list of codes as keys and entries defining the field as values
     */
    public function fields(array $attrs): array
    {
        $list = [];
        foreach ($attrs as $attr) {
            if (!str_contains($attr->get_code(), ':')) {
                $code = $this->name($attr->get_code());
                $list[$code] = ['name' => $code, 'description' => $attr->get_label(), 'type' => $code !== 'id' ? $this->type($attr->get_type()) : Type::String()];
            }
        }
        return $list;
    }
    /**
     * Adds the prefix for the passed domain
     *
     * @param string $domain Domain name of the item the entry is for
     * @param array $entry Associative list of key/value pairs of the item
     * @return array Associative list of prefixed key/value pairs of the item
     */
    public function prefix(string $domain, array $entry): array
    {
        $map = [];
        $domain = str_replace('/', '.', $domain);
        foreach ($entry as $key => $value) {
            if (!in_array($key, ['property', 'lists', 'item'])) {
                $map[$domain . '.' . $key] = $value;
            } else {
                $map[$key] = $value;
            }
        }
        return $map;
    }
    /**
     * Returns the field value for the passed item, domain and name
     *
     * @param \Aimeos\MShop\Common\Item\Iface $item Item which contains the requested value
     * @param string $domain Domain name of the item
     * @param string $name Name of the requested value
     * @return string|null Requested value
     */
    public function resolve(Item_Iface $item, string $domain, string $name)
    {
        return $item->get($name) ?? $item->get(str_replace('/', '.', $domain) . '.' . $name);
    }
    /**
     * Returns the GraphQL type for passed Aimeos search attribute type
     *
     * @param string $name Name of the Aimeos type
     * @return \GraphQL\Type\Definition\Type GraphQL type
     */
    public function type(string $name): Type
    {
        return match ($name) {
            'bool', 'boolean' => Type::boolean(),
            'float' => Type::float(),
            'int', 'integer' => Type::int(),
            'json' => \Aimeos\Graph_Ql\Type\Definition\Json::type(),
            default => Type::string(),
        };
    }
    /**
     * Returns the name of the field without prefix
     *
     * @param string $value Search property name
     * @return string Field name without prefix
     */
    protected function name(string $value): string
    {
        $pos = strrpos($value, '.');
        return substr($value, $pos ? $pos + 1 : 0);
    }
}