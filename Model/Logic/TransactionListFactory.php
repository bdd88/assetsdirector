<?php
namespace Bdd88\AssetsDirector\Model\Logic;

class TransactionListFactory
{


    public function __construct()
    {
    }

    public function create(): TransactionList
    {
        return new TransactionList();
    }

}
