<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {

            // Rename address_line1 → adddress_line_1
            if (Schema::hasColumn('customer_addresses', 'address_line1')) {
                $table->renameColumn('address_line1', 'adddress_line_1');
            }

            // Rename address_line2 → adddress_line_2
            if (Schema::hasColumn('customer_addresses', 'address_line2')) {
                $table->renameColumn('address_line2', 'adddress_line_2');
            }

            // Rename zip_code → pincode
            if (Schema::hasColumn('customer_addresses', 'zip_code')) {
                $table->renameColumn('zip_code', 'pincode');
            }

            // Make ALL columns nullable
            $nullableColumns = [
                'adddress_line_1',
                'adddress_line_2',
                'city',
                'state',
                'pincode',
                'country',
                'latitude',
                'longitude'
            ];

            foreach ($nullableColumns as $column) {
                if (Schema::hasColumn('customer_addresses', $column)) {
                    $table->string($column)->nullable()->change();
                }
            }

            // Numeric columns must be changed separately
            if (Schema::hasColumn('customer_addresses', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->change();
            }

            // Add new nullable column: landmark
            if (!Schema::hasColumn('customer_addresses', 'landmark')) {
                $table->string('landmark')->nullable()->after('adddress_line_2');
            }

            // Add new column: is_default (nullable integer)
            if (!Schema::hasColumn('customer_addresses', 'is_default')) {
                $table->integer('is_default')->nullable()->after('landmark');
            }

            // ensure type is nullable
            if (!Schema::hasColumn('customer_addresses', 'type')) {
                $table->string('type')->nullable()->after('customer_id');
            } else {
                $table->string('type')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {

            if (Schema::hasColumn('customer_addresses', 'adddress_line_1')) {
                $table->renameColumn('adddress_line_1', 'address_line1');
            }

            if (Schema::hasColumn('customer_addresses', 'adddress_line_2')) {
                $table->renameColumn('adddress_line_2', 'address_line2');
            }

            if (Schema::hasColumn('customer_addresses', 'pincode')) {
                $table->renameColumn('pincode', 'zip_code');
            }

            if (Schema::hasColumn('customer_addresses', 'landmark')) {
                $table->dropColumn('landmark');
            }

            if (Schema::hasColumn('customer_addresses', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
