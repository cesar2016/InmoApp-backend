<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Contract;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return response()->json(Payment::with('contract.tenant', 'contract.property')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'amount' => 'required|numeric',
            'detail' => 'nullable|string',
            'subtotal' => 'nullable|numeric',
            'credit_balance' => 'nullable|numeric',
            'debit_balance' => 'nullable|numeric',
            'total' => 'required|numeric',
            'payment_date' => 'required|date',
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020',
            'note' => 'nullable|string',
        ]);

        // Generate a receipt number: YearMonth + Random
        $receiptPrefix = date('Ym', strtotime($validated['payment_date']));
        $validated['receipt_number'] = $receiptPrefix . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $payment = Payment::create($validated);

        return response()->json($payment, 201);
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load('contract.tenant', 'contract.property.owner'));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json(['message' => 'Payment deleted']);
    }
}
