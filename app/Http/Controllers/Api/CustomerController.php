<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\Customer\IndexRequest;
use App\Http\Requests\Api\Customer\StoreRequest;
use App\Http\Requests\Api\Customer\UpdateRequest;
use App\Http\Requests\Api\Customer\DeleteRequest;
use App\Models\Customer;

class CustomerController extends ApiBaseController
{
    protected $model = Customer::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    public function storing($customer)
    {
        $loggedUser = user();

        $customer->created_by = $loggedUser->id;

        return $customer;
    }
}
