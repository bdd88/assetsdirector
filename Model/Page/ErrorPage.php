<?php
namespace Bdd88\AssetsDirector\Model\Page;

use NexusFrame\Http\StatusCodesTrait;
use NexusFrame\Webpage\Model\AbstractPage;

class ErrorPage
{
    use StatusCodesTrait;

    public function exec(): array
    {
        $code = http_response_code();
        $description = $this->getDescription($code);
        return array('code' => $code, 'description' => $description);
    }
}
