<?php
namespace Bdd88\AssetsDirector\Model\Page;

use Bdd88\AssetsDirector\Controller\TdaApi;
use Bdd88\AssetsDirector\Model\Logic\Calendar;
use NexusFrame\Database\MySql\MySql;
use NexusFrame\Webpage\Controller\Session;
use NexusFrame\Webpage\Model\ClientRequest;

class AccountPage
{
    public function __construct(
        private MySql $mySql,
        private Session $session,
        private ClientRequest $request,
        private TdaApi $tdaApi,
        private Calendar $calendar
        )
    {
    }

    public function exec(): array
    {
        // If a permission grant code has been submitted then generate new TDA tokens for the account.
        if (isset($this->request->get->code)) {
            $this->tdaApi->createTokens($this->session->getId(), htmlspecialchars($this->request->get->code, ENT_QUOTES));
        }

        // Retrieve TDA API related information for the account.
        $account = $this->mySql->read()
            ->table('tda_api')
            ->addMatch('account_id', 'isEqual', $this->session->getId())
            ->getResults()
            [0]
        ;

        $currentTime = time();

        $accessTokenExpirationSeconds = bcsub($account['accessTokenExpiration'], $currentTime);
        if ($accessTokenExpirationSeconds <= 0) {
            $accessTokenStatus = 'Expired';
        } else {
            $accessTokenStatus = $this->calendar->calculateTimespan($accessTokenExpirationSeconds, 2);
        }
        
        $refreshTokenExpirationSeconds = bcsub($account['refreshTokenExpiration'], $currentTime);
        if ($refreshTokenExpirationSeconds <= 0) {
            $refreshTokenStatus = 'Expired';
        } else {
            $refreshTokenStatus = $this->calendar->calculateTimespan($refreshTokenExpirationSeconds, 2);
        }

        $output = array(
            'consumerKey' => $account['consumerKey'],
            'redirectUri' => $account['redirectUri'],
            'accessTokenStatus' => $accessTokenStatus,
            'refreshTokenStatus' => $refreshTokenStatus
        );
        return $output;
    }
}
