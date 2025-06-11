<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use Illuminate\Support\Facades\Log;

class MidtransController extends Controller
{
    public function notifikasiHandler(Request $request)
    {
        // Setel Server Key Midtrans Anda
        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is3ds');

        // Dapatkan notifikasi JSON
        $notification = new \Midtrans\Notification();

        // Log notifikasi untuk debugging
        Log::info('Midtrans Notification:', (array) $notification);

        // Temukan transaksi terkait
        $transaction = $notification->transaction_status;
        $type = $notification->payment_type;
        $order_id = $notification->order_id;
        $fraud = $notification->fraud_status;

        // Log informasi transaksi
        Log::info('Transaction Status:', ['transaction' => $transaction, 'type' => $type, 'order_id' => $order_id, 'fraud' => $fraud]);

        // Temukan pesanan terkait
        $penjualan = Penjualan::where('order_id', $order_id)->first();

        if ($penjualan) {
            Log::info('Order found:', ['order_id' => $order_id]);

            if ($transaction == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                        $penjualan->status = 'Pembayaran Ditantang';
                    } else {
                        $penjualan->status = 'Dibayar';
                    }
                }
            } elseif ($transaction == 'settlement') {
                $penjualan->status = 'Dibayar';
            } elseif ($transaction == 'pending') {
                $penjualan->status = 'Tertunda';
            } elseif ($transaction == 'deny') {
                $penjualan->status = 'Pembayaran Ditolak';
            } elseif ($transaction == 'expire') {
                $penjualan->status = 'Pembayaran Kadaluarsa';
            } elseif ($transaction == 'cancel') {
                $penjualan->status = 'Pembayaran Dibatalkan';
            }

            // Simpan status pesanan
            $penjualan->save();

            Log::info('Order status updated:', ['order_id' => $order_id, 'status' => $penjualan->status]);
        } else {
            Log::error('Order not found:', ['order_id' => $order_id]);
        }

        return response()->json(['message' => 'Notifikasi berhasil ditangani']);
    }

}
