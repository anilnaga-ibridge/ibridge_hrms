<?php

namespace App\Observers;

use App\Models\Customer;

class CustomerObserver
{
    public function saving(Customer $customer)
    {
        $company = company();

        // Cannot put in creating, because saving is fired before creating. And we need company id for check below
        if ($company && !$company->is_global) {
            $customer->company_id = $company->id;
        }
    }
}
