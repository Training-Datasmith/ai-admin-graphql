<?php

declare (strict_types=1);
/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2024
 * @package Admin
 * @subpackage GraphQL
 */
namespace Aimeos\Admin\Graphql\Media;

use Aimeos\Graph_Ql\Type\Definition\Upload;
use Graph_Ql\Type\Definition\Input_Object_Type;
use Graph_Ql\Type\Definition\Type;
/**
 * GraphQL class for special handling of media files
 *
 * @package Admin
 * @subpackage GraphQL
 */
class Standard extends \Aimeos\Admin\Graphql\Standard
{
    private Input_Object_Type $type;
    /**
     * Returns GraphQL schema definition for the available mutations
     *
     * @param string $domain Domain name of the responsible manager
     * @return array GraphQL mutation schema definition
     */
    public function mutation(string $domain): array
    {
        $list = parent::mutation($domain);
        $list['saveMedia'] = ['type' => $this->types()->output_type($domain), 'args' => [['name' => 'input', 'type' => $this->media_input_type($domain), 'description' => 'Item object']], 'resolve' => $this->save_item($domain)];
        $list['saveMedias'] = ['type' => Type::list_of($this->types()->output_type($domain)), 'args' => [['name' => 'input', 'type' => Type::list_of($this->media_input_type($domain)), 'description' => 'Item objects']], 'resolve' => $this->save_items($domain)];
        return $list;
    }
    /**
     * Defines the GraphQL media input type
     *
     * @param string $path Path of the domain manager
     * @return \GraphQL\Type\Definition\InputObjectType Input type definition
     */
    public function media_input_type(string $path): Input_Object_Type
    {
        $name = 'mediaInput';
        return $this->type ?? $this->type = new Input_Object_Type(['name' => $name, 'fields' => function () use ($path): array {
            $manager = \Aimeos\M_Shop::create($this->context(), $path);
            $list = $this->types()->fields($manager->get_search_attributes(false));
            $item = $manager->create();
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
                $list['lists'] = $this->types()->lists_input_type($path . '/lists');
            }
            if ($item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
                $list['property'] = Type::list_of($this->types()->input_type($path . '/property'));
            }
            $list['file'] = ['type' => Upload::type(), 'description' => 'File upload'];
            $list['filepreview'] = ['type' => Upload::type(), 'description' => 'Preview file upload'];
            return $list;
        }, 'parseValue' => fn(array $values) => $this->types()->prefix($path, $values)]);
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
        if (isset($entry['media.file'])) {
            $item = $manager->upload($item, $entry['media.file'], $entry['media.filepreview'] ?? null);
        }
        if (isset($entry['lists']) && $item instanceof \Aimeos\M_Shop\Common\Item\Lists_Ref\Iface) {
            $item = $this->update_lists($manager, $item, $entry['lists']);
        }
        if (isset($entry['property']) && $item instanceof \Aimeos\M_Shop\Common\Item\Property_Ref\Iface) {
            return $this->update_properties($manager, $item, $entry['property']);
        }
        return $item;
    }
}