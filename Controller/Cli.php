<?php
namespace Bdd88\AssetsDirector\Controller;

use NexusFrame\Database\MySql\MySql;

/** Command line controller that accepts commands and executes them as necessary. */
class Cli {

    public function __construct(private MySql $mySql, private TdaApi $tdaApi)
    {
    }

    public function updateTokens(): void
    {
        $accounts = $this->mySql->read()->table('tda_api')->getResults();
        foreach ($accounts as $account) $this->tdaApi->updateTokens($account['account_id']);
    }

}
