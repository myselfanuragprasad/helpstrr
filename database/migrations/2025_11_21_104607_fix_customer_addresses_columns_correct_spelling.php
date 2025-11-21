<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {

            // Rename wrong columns to correct ones
            if (Schema::hasColumn('customer_addresses', 'address_line1')) {
                $table->renameColumn('address_line1', 'address_line_1');
            }

            if (Schema::hasColumn('customer_addresses', 'address_line2')) {
                $table->renameColumn('address_line2', 'address_line_2');
            }

            // Rename zip_code → pincode
            if (Schema::hasColumn('customer_addresses', 'zip_code')) {
                $table->renameColumn('zip_code', 'pincode');
            }

            // Make all address fields nullable
            $nullableStrings = [
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'country',
                'pincode',
            ];

            foreach ($nullableStrings as $col) {
                if (Schema::hasColumn('customer_addresses', $col)) {
                    $table->string($col)->nullable()->change();
                }
            }

            // Numeric columns
            if (Schema::hasColumn('customer_addresses', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->change();
            }

            if (Schema::hasColumn('customer_addresses', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->change();
            }

            // Add landmark if missing
            if (!Schema::hasColumn('customer_addresses', 'landmark')) {
                $table->string('landmark')->nullable()->after('address_line_2');
            }

            // Add type if missing
            if (!Schema::hasColumn('customer_addresses', 'type')) {
                $table->string('type')->nullable()->after('customer_id');
            } else {
                $table->string('type')->nullable()->change();
            }

            // Add is_default if missing
            if (!Schema::hasColumn('customer_addresses', 'is_default')) {
                $table->integer('is_default')->nullable()->after('landmark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {

            if (Schema::hasColumn('customer_addresses', 'address_line_1')) {
                $table->renameColumn('address_line_1', 'address_line1');
            }

            if (Schema::hasColumn('customer_addresses', 'address_line_2')) {
                $table->renameColumn('address_line_2', 'address_line2');
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
