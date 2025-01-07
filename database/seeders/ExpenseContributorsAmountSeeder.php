<?php

namespace Database\Seeders;

use App\Models\Expense;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseContributorsAmountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            Expense::where(['type' => 'expend'])
                ->with('contributors')
                ->chunk(100, function ($expenses) {
                    foreach ($expenses as $expense) {
                        if ($expense->contributors->count() > 0) {

                            $expense_total_amount = $expense->amount;
                            $amount_per_contributor = round($expense_total_amount / $expense->contributors->count());

                            $first_contributor = $expense->contributors->first();
                            $remainder = $expense_total_amount % $expense->contributors->count();

                            $first_contributor->update([
                                'amount' => $amount_per_contributor + $remainder
                            ]);

                            $expense->contributors->skip(1)->each(function ($contributor) use ($amount_per_contributor) {
                                $contributor->update([
                                    'amount' => $amount_per_contributor
                                ]);
                            });
                        }
                    }
                });
        });
    }
}
