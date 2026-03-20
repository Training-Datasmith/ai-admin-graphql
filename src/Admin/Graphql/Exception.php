<?php

declare (strict_types=1);
namespace Aimeos\Admin\Graphql;

/**
 * GraphQL exceptions which can be shown to the client
 */
class Exception extends \Exception implements \Graph_Ql\Error\Client_Aware
{
    /**
     * Returns if exception can be shown to the client
     *
     * @return bool TRUE if exception can be shown to the client, FALSE if not
     */
    public function is_client_safe(): bool
    {
        return true;
    }
}