<?php

use App\Models\Lang;
use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create customers table
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->string('name');
            $table->string('email')->nullable()->default(null);
            $table->string('phone')->nullable()->default(null);
            $table->string('website')->nullable()->default(null);
            $table->string('tax_number')->nullable()->default(null);
            $table->text('address')->nullable()->default(null);
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null')->onUpdate('cascade');
            $table->timestamps();
        });

        // 2. Modify projects table to add customer_id referencing customers
        Schema::table('projects', function (Blueprint $table) {
            $table->bigInteger('customer_id')->unsigned()->nullable()->default(null)->after('customer');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null')->onUpdate('cascade');
        });

        // 3. Seed translation keys for menu and customer
        $allLangs = Lang::all();
        $translations = [
            'menu' => [
                'customers' => 'Customers',
            ],
            'customer' => [
                'add' => 'Add New Customer',
                'edit' => 'Edit Customer',
                'created' => 'Customer Created Successfully',
                'updated' => 'Customer Updated Successfully',
                'deleted' => 'Customer Deleted Successfully',
                'delete_message' => 'Are you sure you want to delete this customer?',
                'selected_delete_message' => 'Are you sure you want to delete selected customers?',
                'name' => 'Customer Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'website' => 'Website',
                'tax_number' => 'Tax Number',
                'address' => 'Address',
                'no_customers' => 'There are no customers yet.',
            ],
        ];

        foreach ($translations as $group => $translation) {
            foreach ($translation as $transKey => $transValue) {
                foreach ($allLangs as $allLang) {
                    $exists = Translation::where('lang_id', $allLang->id)
                        ->where('group', $group)
                        ->where('key', $transKey)
                        ->exists();
                    if (!$exists) {
                        $newTrans = new Translation();
                        $newTrans->lang_id = $allLang->id;
                        $newTrans->group = $group;
                        $newTrans->key = $transKey;
                        $newTrans->value = $transValue;
                        $newTrans->save();
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
        Schema::dropIfExists('customers');
    }
};
