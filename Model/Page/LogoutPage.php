<?php
namespace Bdd88\AssetsDirector\Model\Page;

use NexusFrame\Webpage\Controller\Session;
use NexusFrame\Webpage\Model\AbstractPage;

class LogoutPage
{
    public function __construct(private Session $session)
    {
    }

    public function exec(): void
    {
        $this->session->stop();
        header("Location: ./");
    }
}
