<?php
namespace Bdd88\AssetsDirector\Model\Logic;

/** Generates cURL parameters for querying the TDA API. */
class TdaApiParameters
{
    /** Request brand new refresh and access tokens using a permission grant code. */
    public function newTokens(string $permissionCode, string $consumerKey, string $redirectUri): array
    {
        return array(
            'method' => 'post',
            'header' => array('Content-Type: application/x-www-form-urlencoded'),
            'url' => 'https://api.tdameritrade.com/v1/oauth2/token',
            'vars' => 'grant_type=authorization_code&refresh_token=&access_type=offline&code=' . urlencode($permissionCode) . '&client_id=' . urlencode($consumerKey) . '&redirect_uri=' . urlEncode($redirectUri)
        );
    }

    /** Request new refresh and access tokens using an unexpired refresh token. */
    public function refreshToken(string $refreshToken, string $consumerKey): array
    {
        return array(
            'method' => 'post',
            'header' => array('Content-Type: application/x-www-form-urlencoded'),
            'url' => 'https://api.tdameritrade.com/v1/oauth2/token',
            'vars' => 'grant_type=refresh_token&refresh_token=' . urlencode($refreshToken) . '&access_type=offline&code=&client_id=' . urlencode($consumerKey) . '&redirect_uri='
        );
    }

    /** Request a new access token using an unexpired refresh token. */
    public function accessToken(string $refreshToken, string $consumerKey): array
    {
        return array(
            'method' => 'post',
            'header' => array('Content-Type: application/x-www-form-urlencoded'),
            'url' => 'https://api.tdameritrade.com/v1/oauth2/token',
            'vars' => 'grant_type=refresh_token&refresh_token=' . urlencode($refreshToken) . '&access_type=&code=&client_id=' . urlencode($consumerKey) . '&redirect_uri='
        );
    }

    /** Request transaction history data. */
    public function transactions(string $accessToken, string $accountNumber, string $startDate, string $endDate): array
    {
        return array(
            'method' => 'get',
            'header' => array('Authorization: Bearer ' . $accessToken),
            'url' => 'https://api.tdameritrade.com/v1/accounts/' . $accountNumber . '/transactions?startDate=' . $startDate . '&endDate=' . $endDate
        );
    }

    /** Request orders data. */
    public function orders(string $startDate, string $endDate, string $accessToken, ?string $status = NULL): array
    {
        return array(
            'method' => 'get',
            'header' => array('Authorization: Bearer ' . $accessToken),
            'url' => 'https://api.tdameritrade.com/v1/orders?fromEnteredTime=' . $startDate . '&toEnteredTime=' . $endDate . '&status=' . $status
        );
    }

    /** Request general account information. */
    public function accountInfo(string $accessToken, string $accountNumber): array
    {
        return array(
            'method' => 'get',
            'header' => array('Authorization: Bearer ' . $accessToken),
            'url' => 'https://api.tdameritrade.com/v1/accounts/' . $accountNumber
        );
    }
}
