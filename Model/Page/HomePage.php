<?php
namespace Bdd88\AssetsDirector\Model\Page;

use NexusFrame\Webpage\Model\AbstractPage;

class HomePage
{
    public function exec(): array
    {
        return array('content' => 'homepage stuff goes here');
    }
}
