<?php
namespace Bdd88\AssetsDirector\Model\Config;

use Exception;
use NexusFrame\Validate\AbstractConfig;

class MainConfig extends AbstractConfig
{
    public function validate(): bool
    {
            $this->assertExists([
                'application' => ['timezone', 'login_timeout'],
                'logs' => ['enabled']
            ]);
        return TRUE;
    }
}

