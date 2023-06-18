<?php
namespace Bdd88\AssetsDirector\Controller;

use DateTime;
use DateInterval;
use Bdd88\AssetsDirector\Model\Logic\TdaApiParameters;
use Bdd88\AssetsDirector\Model\Logic\TdaApiResponse;
use Bdd88\AssetsDirector\Model\Logic\TdaApiResponseFactory;
use Exception;
use NexusFrame\Database\MySql\MySql;
use NexusFrame\Utility\Curl;
use NexusFrame\Utility\Logger;
use NexusFrame\Utility\RateLimiter;
use NexusFrame\Utility\RateLimiterFactory;

/** TDA API controller that handles downloading data and saving it to the database. */
class TdaApi
{
    private RateLimiter $rateLimiter;

    public function __construct(
        private MySql $mySql,
        private Logger $logger,
        private TdaApiParameters $tdaApiParameters,
        private RateLimiterFactory $rateLimiterFactory,
        private Curl $curl,
        private TdaApiResponseFactory $tdaApiResponseFactory
        )
    {
        $this->rateLimiter = $this->rateLimiterFactory->create(100);
    }

    /** Create a new TdaApi log prefixed with the account number. */
    private function log(string $accountId, string $logMessage): void
    {
        $this->logger->log('TdaApi', 'Account#: ' . $accountId . ' - ' . $logMessage);
    }

    /** Store an access token in the database. */
    private function saveAccessToken(string $accountId, string $token, string $expiration): void
    {
        $affectedRows = $this->mySql->update()
            ->table("tda_api")
            ->addMatch('account_id', 'isEqual', $accountId)
            ->values([
                'accessToken' => $token,
                'accessTokenExpiration' => $expiration + time()
            ])
            ->getResults()
        ;
        if ($affectedRows === 1) {
            $this->log($accountId, 'Successfully saved access token.');
        } else {
            $this->log($accountId, 'Failed to save access token.');
        }
    }

    /** Store a refresh token in the database. */
    private function saveRefreshToken(string $accountId, string $token, string $expiration): void
    {
        $affectedRows = $this->mySql->update()
            ->table("tda_api")
            ->addMatch('account_id', 'isEqual', $accountId)
            ->values([
                'refreshToken' => $token,
                'refreshTokenExpiration' => $expiration + time()
            ])
            ->getResults()
        ;
        if ($affectedRows === 1) {
            $this->log($accountId, 'Successfully saved refresh token.');
        } else {
            $this->log($accountId, 'Failed to save refresh token.');
        }
    }

    /** Retrieve account information using an ID. */
    private function getAccount(string $accountId): array
    {
        $accounts = $this->mySql->read()
            ->table('tda_api')
            ->addMatch('account_id', 'isEqual', $accountId)
            ->getResults()
        ;
        if (sizeof($accounts) !== 1) throw new Exception('Couldn\'t retrieve account.');
        return $accounts[0];
    }

    /** Execute a request to the TDA API and return it's response as a TdaApiResponse object. */
    private function execRequest(array $curlParameters): TdaApiResponse
    {
        $response = $this->rateLimiter->exec([$this->curl, 'exec'], $curlParameters);
        return $this->tdaApiResponseFactory->create($response['code'], $response['response']);
    }

    /** Create and save brand new Refresh and Access tokens using a Permission Code. */
    public function createTokens(string $accountId, string $permissionCode): bool
    {
        $account = $this->getAccount($accountId);
        $parameters = $this->tdaApiParameters->newTokens($permissionCode, $account['consumerKey'], $account['redirectUri']);
        $apiRequest = $this->execRequest($parameters);

        if ($apiRequest->httpdCode === 200) {
            $this->log($accountId, 'Successfully retrieved new tokens.');
            $this->saveAccessToken($accountId, $apiRequest->response->access_token, $apiRequest->response->expires_in);
            $this->saveRefreshToken($accountId, $apiRequest->response->refresh_token, $apiRequest->response->refresh_token_expires_in);
            return TRUE;
        }
        $this->log($accountId, 'Error retrieving new tokens: ' . $apiRequest->response->error);
        return FALSE;
    }

    /**
     * Update and save tokens near expiration using an existing Refresh token.
     *
     * @param string $accountId
     * @return boolean FALSE if there is a problem, TRUE otherwise.
     */
    public function updateTokens(string $accountId): bool
    {
        $account = $this->getAccount($accountId);
        $currentTime = time();
        $logMessagePrefix = 'Account#: ' . $accountId . ' - ';

        // Update Refresh token if it is near expiration. This also updates the Access token.
        $refreshTokenNearExpiration = $account['refreshTokenExpiration'] - 86400;
        if ($currentTime >= $refreshTokenNearExpiration) {
            $parameters = $this->tdaApiParameters->refreshToken($account['refreshToken'], $account['consumerKey']);
            $apiRequest = $this->execRequest($parameters);
            if ($apiRequest->httpdCode === 200) {
                $this->log($accountId, 'Successfully retrieved updated tokens.');
                $this->saveAccessToken($accountId, $apiRequest->response->access_token, $apiRequest->response->expires_in);
                $this->saveRefreshToken($accountId, $apiRequest->response->refresh_token, $apiRequest->response->refresh_token_expires_in);
                return TRUE;
            }
            $this->log($accountId, 'Failed to retrieve updated tokens. Http code: ' . $apiRequest->response->error);
            return FALSE;
        }
        
        // Update the Access token if it is near expiration.
        $accessTokenNearExpiration = $account['accessTokenExpiration'] - 60;
        if ($currentTime >= $accessTokenNearExpiration) {
            $parameters = $this->tdaApiParameters->accessToken($account['refreshToken'], $account['consumerKey']);
            $apiRequest = $this->execRequest($parameters);
            if ($apiRequest->httpdCode === 200) {
                $this->log($accountId, 'Successfully retrieved updated access token.');
                $this->saveAccessToken($accountId, $apiRequest->response->access_token, $apiRequest->response->expires_in);
                return TRUE;
            }
            $this->log($accountId, 'Failed to retrieve updated access token. Http code: ' . $apiRequest->response->error);
            return FALSE;
        }

        return TRUE;
    }
    
    // TODO: Test tokens and then rewrite everything below here.
    //
    // /**
    //  * Download and save transactions for a specific date range.
    //  *
    //  * @param string $accountId TDA Account ID.
    //  * @param string $startDate Date in Y-m-d format.
    //  * @param string $endDate Date in Y-m-d format.
    //  * @return boolean Returns TRUE if update was successful, or FALSE if there was an error.
    //  */
    // public function updateTransactions(string $accountId, string $startDate, string $endDate): bool
    // {
    //     $accountInfo = $this->mySql->read('tda_api', NULL, [
    //         ['account_id', 'isEqual', $accountId]
    //     ])[0];
    //     $transactionsRequest = $this->tdaApiRequest->transactions($account['accessToken'], $account['accountNumber'], $startDate, $endDate);
    //     if ($transactionsRequest->httpdCode !== 200) {
    //         $this->logger->log('TdaApi', 'Error downloading transactions for account' . $accountId . ': ' . $transactionsRequest->response->error);
    //         return FALSE;
    //     }

    //     // Flatten each transacation and store in the database. Record transaction type (duplicate, pending, completed), and transaction id/order id.
    //     $transactions = array_reverse($transactionsRequest->response);
    //     $this->mySql->truncate('transactions_pending');
    //     $transactionsUpdated = [];
    //     $transactionsDuplicates = [];
    //     $transactionsPending = [];
    //     $transactionsErrors = [];
    //     foreach ($transactions as $transaction){
    //         $transaction->account_id = $accountId;
    //         $processedTransaction = $this->flattenTransaction($transaction);
    //         $transactionTable = (isset($transaction->transactionSubType)) ? 'transactions' : 'transactions_pending' ;
    //         $mySqlResponse = $this->mySql->create($transactionTable, $processedTransaction);
    //         if ($mySqlResponse === 0) {
    //             $transactionsDuplicates[] = $transaction->transactionId;
    //         } else {
    //             if ($transactionTable === 'transactions_pending') {
    //                 $transactionsPending[] = $transaction->orderId;
    //             } else {
    //                 $transactionsUpdated[] = $transaction->transactionId;
    //             }
    //         }
    //     }
    //     $logMessage = 'Account#: ' . $accountId;
    //     $logMessage .= ' - Transactions Downloaded for ' . $startDate . ' to ' . $endDate;
    //     $logMessage .= ' - New: ' . count($transactionsUpdated);
    //     $logMessage .= ' Duplicates: ' . count($transactionsDuplicates);
    //     $logMessage .= ' Pending: ' . count($transactionsPending);

    //     $this->logger->log('TdaApi', $logMessage);
    //     if (count($transactionsErrors) > 0) {
    //         $this->logger->log('TdaApi', 'Account#: ' . $accountId . ' - Failed to add transactions: ' . json_encode($transactionsErrors));
    //     }
    //     return TRUE;
    // }

    // /** Break transaction updates into daily batches to avoid issues with the TDA API silently dropping data when the response is too large. */
    // public function batchUpdateTransactions(string $accountId, string $startDate, string $stopDate): bool
    // {
    //     $startDate = new DateTime($startDate);
    //     $stopDate = new DateTime($stopDate);
    //     $oneDay = new DateInterval('P1D');
    //     $currentDate = new DateTime($startDate->format('Y-m-d'));
    //     while ($currentDate <= $stopDate) {
    //         $batchUpdate = $this->updateTransactions($accountId, $currentDate->format('Y-m-d'), $currentDate->format('Y-m-d'));
    //         if ($batchUpdate === FALSE) {
    //             return FALSE;
    //         }
    //         $currentDate->add($oneDay);
    //     }
    //     return TRUE;
    // }

    // /** Download and save orders for a specified date range. */
    // public function updateOrders(string $accountId, ?string $startDate = NULL, ?string $endDate = NULL)
    // {
    //     $accountInfo = $this->mySql->read('tda_api', NULL, [
    //         ['account_id', 'isEqual', $accountId]
    //     ])[0];
    //     //echo '<pre>';
    //     //var_dump($accountInfo);
    //     //echo '</pre>';
    //     $today = date("Y-m-d");
    //     $startDate = $startDate ?? $today;
    //     $endDate = $endDate ?? $today;
    //     $orders = $this->tdaApiRequest->orders($startDate, $endDate, $account['accessToken']);
    //     return $orders;
    // }

    // public function getTdaAccount(string $accountId): object
    // {
    //     $accountInfo = $this->mySql->read('tda_api', NULL, [
    //         ['account_id', 'isEqual', $accountId]
    //     ])[0];
    //     $tdaAccount = $this->tdaApiRequest->accountInfo($account['accessToken'], $account['accountNumber']);
    //     return $tdaAccount;
    // }

    // /** Flatten an array/objects into an associative array. */
    // private function flattenTransaction(array|object $parent): array
    // {
    //     $output = [];
    //     foreach ($parent as $key => $value) {
    //         if (is_array($value) || is_object($value)) {
    //             $child = $this->flattenTransaction($value);
    //             $output = array_merge($output, $child);
    //         } else {
    //             $output[$key] = $value;
    //         }
    //     }
    //     return $output;
    // }

}
