<?php
namespace Bdd88\AssetsDirector\Model\Page;

use NexusFrame\Webpage\Model\AbstractPage;
use NexusFrame\Webpage\Model\ClientRequest;
use NexusFrame\Webpage\Controller\Session;

class LoginPage
{

    public function __construct(private ClientRequest $clientRequest, private Session $session)
    {
    }

    // Redirect the user.
    private function redirect(): void
    {
        if (isset($this->clientRequest->get->page) && $this->clientRequest->get->page !== 'login') {
            header('Location: ' . $this->clientRequest->get->page);
        } else {
            // Redirect without specifying a page, and allow the default route to handle what page to display.
            header("Location: ./");
        }
    }

    public function exec(?string $message = NULL): array
    {
        // Redirect if user is already logged in.
        if ($this->session->status() === TRUE) $this->redirect();

        // Set the default display message.
        $message ??= 'Please login.';

        // Attempt to authenticate if username and password have been provided.
        if (isset($this->clientRequest->post->username) && isset($this->clientRequest->post->password)) {
            $authentication = $this->session->authenticate($this->clientRequest->post->username, $this->clientRequest->post->password);
            if ($authentication === TRUE) $this->redirect();
            $message = 'Incorrect username/password.';
        }
        return array('message' => $message);
    }
}
