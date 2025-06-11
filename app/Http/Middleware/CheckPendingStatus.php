<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Penjualan;


class CheckPendingStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $pendingTransactions = Penjualan::where('status', 'Pending')->count();
        if ($pendingTransactions > 0) {
            return redirect()->route('transaksi.index')->with('error', 'You have pending transactions that need to be completed first.');
        }

        return $next($request);
    }
}
