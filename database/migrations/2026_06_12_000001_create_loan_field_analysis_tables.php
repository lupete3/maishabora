<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loan_field_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('visit_date');
            $table->decimal('usd_cdf_rate', 15, 4)->nullable();
            $table->string('credit_number')->nullable();
            $table->string('education_level')->nullable();
            $table->string('origin_province')->nullable();
            $table->string('religion')->nullable();
            $table->text('quick_biography')->nullable();
            $table->string('housing_type')->nullable();
            $table->decimal('housing_value', 15, 2)->nullable();
            $table->string('residence_duration')->nullable();
            $table->decimal('monthly_rent', 15, 2)->nullable();
            $table->decimal('rent_paid_in_advance', 15, 2)->nullable();
            $table->text('home_directions')->nullable();
            $table->text('household_impressions')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('occupation')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_household_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->enum('reference_type', ['ecole', 'fournisseur', 'parent', 'voisin', 'autre'])->default('autre');
            $table->timestamps();
        });

        Schema::create('loan_business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('activity');
            $table->string('full_address');
            $table->date('started_at')->nullable();
            $table->unsignedInteger('employees_count')->nullable();
            $table->decimal('business_margin_percent', 8, 2)->nullable();
            $table->text('business_history')->nullable();
            $table->text('qualitative_observations')->nullable();
            $table->text('purchase_sales_competition_comments')->nullable();
            $table->text('business_location_notes')->nullable();
            $table->text('household_location_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_investment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('destination');
            $table->decimal('amount', 15, 2);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('client_share', 15, 2)->nullable();
            $table->decimal('institution_share', 15, 2)->nullable();
            $table->decimal('third_party_share', 15, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('loan_credit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->string('institution');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('status')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_balance_sheet_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('cash', 15, 2)->default(0);
            $table->decimal('bank', 15, 2)->default(0);
            $table->decimal('savings', 15, 2)->default(0);
            $table->decimal('receivables', 15, 2)->default(0);
            $table->decimal('supplier_advances', 15, 2)->default(0);
            $table->decimal('stock', 15, 2)->default(0);
            $table->decimal('machines_tools', 15, 2)->default(0);
            $table->decimal('transport_assets', 15, 2)->default(0);
            $table->decimal('buildings_land', 15, 2)->default(0);
            $table->decimal('supplier_debts', 15, 2)->default(0);
            $table->decimal('current_customer_credit', 15, 2)->default(0);
            $table->decimal('short_term_debt', 15, 2)->default(0);
            $table->decimal('long_term_debt', 15, 2)->default(0);
            $table->decimal('equity', 15, 2)->default(0);
            $table->decimal('total_assets', 15, 2)->default(0);
            $table->decimal('total_debts', 15, 2)->default(0);
            $table->decimal('total_liabilities_equity', 15, 2)->default(0);
            $table->text('comments')->nullable();
            $table->date('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_cashflow_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('cash_sales', 15, 2)->default(0);
            $table->decimal('credit_sales', 15, 2)->default(0);
            $table->decimal('retained_sales', 15, 2);
            $table->decimal('retained_purchases', 15, 2);
            $table->decimal('gross_margin', 15, 2)->default(0);
            $table->decimal('business_expenses_total', 15, 2);
            $table->decimal('household_income', 15, 2)->default(0);
            $table->decimal('household_expenses_total', 15, 2);
            $table->decimal('household_safety_margin', 15, 2)->default(0);
            $table->decimal('available_income', 15, 2)->default(0);
            $table->decimal('repayment_capacity', 15, 2)->default(0);
            $table->string('household_income_source')->nullable();
            $table->string('household_income_periodicity')->nullable();
            $table->text('comments')->nullable();
            $table->date('calculated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_expense_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->enum('section', ['business', 'household']);
            $table->string('label');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('loan_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->enum('section', ['stock', 'fixed_asset', 'camv', 'production_cost', 'off_balance_investment']);
            $table->string('description');
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('quantity', 15, 2)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_collateral_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('owner_name')->nullable();
            $table->string('document_type')->nullable();
            $table->decimal('market_value', 15, 2)->nullable();
            $table->text('address_references')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_co_borrowers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('postnom_prenom')->nullable();
            $table->string('phone')->nullable();
            $table->string('identity_document')->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('income', 15, 2)->nullable();
            $table->string('address')->nullable();
            $table->string('relationship')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_agent_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('final_conclusions');
            $table->decimal('proposed_amount', 15, 2);
            $table->decimal('proposed_rate', 8, 2);
            $table->unsignedInteger('proposed_maturity_months');
            $table->unsignedInteger('grace_period_months')->default(0);
            $table->string('repayment_modality')->nullable();
            $table->text('irregular_payment_explanation')->nullable();
            $table->foreignId('agent_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_agent_proposals');
        Schema::dropIfExists('loan_co_borrowers');
        Schema::dropIfExists('loan_collateral_properties');
        Schema::dropIfExists('loan_inventory_items');
        Schema::dropIfExists('loan_expense_lines');
        Schema::dropIfExists('loan_cashflow_analyses');
        Schema::dropIfExists('loan_balance_sheet_details');
        Schema::dropIfExists('loan_credit_histories');
        Schema::dropIfExists('loan_investment_plan_items');
        Schema::dropIfExists('loan_business_profiles');
        Schema::dropIfExists('loan_household_references');
        Schema::dropIfExists('loan_family_members');
        Schema::dropIfExists('loan_field_visits');
    }
};
