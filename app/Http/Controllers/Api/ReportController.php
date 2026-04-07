<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\Maintenance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function cashFlow(Request $request)
    {
        $type = $request->get('type', 'daily'); // daily, monthly, yearly
        $date = $request->get('date', Carbon::now()->toDateString());

        $queryPayments = Payment::query();
        $queryMaintenance = Maintenance::query();

        if ($type === 'daily') {
            $queryPayments->whereDate('payment_date', $date);
            $queryMaintenance->whereDate('date', $date);
        } elseif ($type === 'monthly') {
            $queryPayments->whereMonth('payment_date', Carbon::parse($date)->month)
                ->whereYear('payment_date', Carbon::parse($date)->year);
            $queryMaintenance->whereMonth('date', Carbon::parse($date)->month)
                ->whereYear('date', Carbon::parse($date)->year);
        } else { // yearly
            $queryPayments->whereYear('payment_date', Carbon::parse($date)->year);
            $queryMaintenance->whereYear('date', Carbon::parse($date)->year);
        }

        $income = $queryPayments->sum('amount');
        $expenses = $queryMaintenance->sum('cost');

        return response()->json([
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $income - $expenses,
            'type' => $type,
            'date' => $date
        ]);
    }

    public function alerts()
    {
        $today = Carbon::now();
        $nextMonth = Carbon::now()->addMonth();

        // 1. Expirations (contracts ending in next 30 days)
        $expiringContracts = Contract::where('is_active', true)
            ->whereBetween('end_date', [$today, $nextMonth])
            ->with(['tenant', 'property'])
            ->get();

        // 2. Rent Increases (contracts with increase due)
        // Simple logic: if today > last_increase_date + increase_frequency
        $increaseAlerts = Contract::where('is_active', true)
            ->get()
            ->filter(function ($contract) use ($today) {
                $baseDate = $contract->last_increase_date ? Carbon::parse($contract->last_increase_date) : Carbon::parse($contract->start_date);
                $nextIncrease = $baseDate->addMonths($contract->increase_frequency_months);
                return $today->greaterThanOrEqualTo($nextIncrease);
            });

        // 3. Missing Payments
        // If current month/year has no payment for an active contract
        $missingPayments = Contract::where('is_active', true)
            ->whereDoesntHave('payments', function ($query) {
                $query->where('period_month', Carbon::now()->month)
                    ->where('period_year', Carbon::now()->year);
            })
            ->with(['tenant', 'property'])
            ->get();

        return response()->json([
            'expiring_contracts' => $expiringContracts,
            'increase_alerts' => $increaseAlerts->values(),
            'missing_payments' => $missingPayments
        ]);
    }

    public function liquidation($owner_id, Request $request)
    {
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $owner = \App\Models\Owner::findOrFail($owner_id);
        $properties = $owner->properties()->pluck('id');

        $contracts = Contract::whereIn('property_id', $properties)->pluck('id');

        $payments = Payment::whereIn('contract_id', $contracts)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->with('contract.property')
            ->get();

        $maintenances = Maintenance::whereIn('property_id', $properties)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        $totalRent = $payments->sum('amount');
        $totalExpenses = $maintenances->sum('cost');

        // Assume 10% commission
        $commission = $totalRent * 0.10;

        return response()->json([
            'owner' => $owner,
            'month' => $month,
            'year' => $year,
            'payments' => $payments,
            'maintenances' => $maintenances,
            'total_rent' => $totalRent,
            'total_expenses' => $totalExpenses,
            'commission' => $commission,
            'net_amount' => $totalRent - $totalExpenses - $commission
        ]);
    }
}
