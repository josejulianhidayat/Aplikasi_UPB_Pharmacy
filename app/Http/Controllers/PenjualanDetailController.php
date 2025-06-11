<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PenjualanDetailController extends Controller
{
    public function index()
    {
        $produk   = Produk::orderBy('nama_produk')->get();
        $setting = Setting::first();
        
        if (!$setting) {
            return redirect()->route('home')->with('error', 'Settings not found.');
        }

        $ppn = $setting->ppn ?? 0;
        $margin = $setting->margin ?? 0;
        $tuslah = $setting->tuslah ?? 0;
        $embalase = $setting->embalase ?? 0;
        
        if ($id_penjualan = session('id_penjualan')) {
            $penjualan = Penjualan::find($id_penjualan);
            $detail = PenjualanDetail::where('id_penjualan', $id_penjualan)->get();
            $snapToken = session('snapToken');

            if (is_null($snapToken)) {
                $snapToken = $this->generateSnapToken($id_penjualan, $penjualan->total);
            }

            return view('penjualan_detail.index', compact('produk', 'id_penjualan', 'penjualan', 'snapToken', 'detail', 'ppn', 'margin', 'tuslah', 'embalase'));
        } else {
            if (auth()->user()->level == 1) {
                return redirect()->route('transaksi.baru');
            } else {
                return redirect()->route('home');
            }
        }
    }

    public function data($id)
    {
        $detail = PenjualanDetail::with('produk')->where('id_penjualan', $id)->get();
        $data = array();
        $total = 0;
        $total_item = 0;

        foreach ($detail as $item) {
            $grandTotal = $this->hitungTotal($item->harga_jual, $item->diskon, $item->jumlah);

            $row = array();
            $row['kode_produk'] = '<span class="label label-success">'. $item->produk['kode_produk'] .'</span>';
            $row['nama_produk'] = $item->produk['nama_produk'];
            $row['harga_jual']  = 'Rp. '. format_uang($item->harga_jual);
            $row['jumlah'] = '<input type="number" class="form-control input-sm quantity" id="quantity-' . $item->id_penjualan_detail . '" data-id="' . $item->id_penjualan_detail . '" value="' . $item->jumlah . '">';
            $row['diskon']      = $item->diskon . '%';
            $row['ppn']         = $this->getSettingValue('ppn') . '%';
            $row['margin']      = $this->getSettingValue('margin') . '%';
            $row['tuslah']      = 'Rp. ' . format_uang($this->getSettingValue('tuslah'));
            $row['embalase']    = 'Rp. ' . format_uang($this->getSettingValue('embalase'));
            $row['subtotal']    = 'Rp. '. format_uang($grandTotal);
            $row['aksi']        = '<div class="btn-group">
                                    <button onclick="deleteData(`'. route('transaksi.destroy', $item->id_penjualan_detail) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                                </div>';
            $data[] = $row;

            $total += $grandTotal;
            $total_item += $item->jumlah;
        }

        $data[] = [
            'kode_produk' => '
                <div class="total hide">'. $total .'</div>
                <div class="total_item hide">'. $total_item .'</div>',
            'nama_produk' => '',
            'harga_jual'  => '',
            'jumlah'      => '',
            'diskon'      => '',
            'ppn'         => '',
            'margin'      => '',
            'tuslah'      => '',
            'embalase'    => '',
            'subtotal'    => '',
            'aksi'        => '',
        ];

        return datatables()
            ->of($data)
            ->addIndexColumn()
            ->rawColumns(['aksi', 'kode_produk', 'jumlah'])
            ->make(true);
    }



    public function generateSnapToken($id, $total)
    {
        \Midtrans\Config::$serverKey = config('midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('midtrans.is3ds');

        $user = Auth::user();

        $order_id = $id . '-' . time();

        $penjualan = Penjualan::find($id);
        if ($penjualan) {
            $penjualan->order_id = $order_id;
            $penjualan->save();
        }

         $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $total,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            session(['snapToken' => $snapToken]);
            return $snapToken;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getNewSnapToken($id)
    {

        $penjualan = Penjualan::find($id);
        if (!$penjualan) {
            return response()->json(['error' => 'Penjualan tidak ditemukan'], 404);
        }

        $total = $penjualan->total;
        if (empty($total) || !is_numeric($total)) {
            $total = PenjualanDetail::where('id_penjualan', $id)->sum('subtotal');
        }

        if (empty($total) || !is_numeric($total)) {
            return response()->json(['error' => 'Total penjualan tidak valid'], 500);
        }

        $snapToken = $this->generateSnapToken($id, $total);
        
        if ($snapToken) {
            return response()->json(['snapToken' => $snapToken], 200);
        } else {
            return response()->json(['error' => 'Gagal mendapatkan Snap Token'], 500);
        }
    }

    public function store(Request $request)
    {
        $produk = Produk::where('id_produk', $request->id_produk)->first();
        if (!$produk) {
            return response()->json('Data gagal disimpan', 400);
        }

        $detail = PenjualanDetail::where('id_penjualan', $request->id_penjualan)
                    ->where('id_produk', $request->id_produk)
                    ->first();

        if ($detail) {

            $detail->jumlah += 1;
            $grandTotal = $this->hitungTotal($detail->harga_jual, $detail->diskon, $detail->jumlah);
            $detail->subtotal = $grandTotal;
            $detail->save();
            
        } else {
            $diskon = $produk->diskon;
            $grandTotal = $this->hitungTotal($produk->harga_jual, $diskon, 1);

            $detail = new PenjualanDetail();
            $detail->id_penjualan = $request->id_penjualan;
            $detail->id_produk = $produk->id_produk;
            $detail->harga_jual = $produk->harga_jual;
            $detail->jumlah = 1;
            $detail->diskon = $diskon;
            $detail->subtotal = $grandTotal;
            $detail->save();
        }

        return response()->json('Data berhasil disimpan', 200);
    }

    

    public function update(Request $request, $id)
    {
        $detail = PenjualanDetail::find($id);

        if (!$detail) {
            return response()->json('Detail penjualan tidak ditemukan', 404);
        }
        $grandTotal = $this->hitungTotal($detail->harga_jual, $detail->diskon, $request->jumlah);
        $detail->jumlah = $request->jumlah;
        $detail->subtotal = $grandTotal;

        $detail->update();

        return response()->json('Data berhasil diupdate', 200);
    }

    public function destroy($id)
    {
        $detail = PenjualanDetail::find($id);

        $detail->delete();

        return response(null, 204);
    }

    public function loadForm($diskon = 0, $total = 0, $diterima = 0)
    {
        $totalSetelahDiskon = $total - ($diskon / 100 * $total);
        $grandTotal = $totalSetelahDiskon;

        $kembali = ($diterima != 0) ? $diterima - $grandTotal : 0;
        $data = [
            'totalrp' => format_uang($total),
            'bayar' => $grandTotal,
            'bayarrp' => format_uang($grandTotal),
            'terbilang' => ucwords(terbilang($grandTotal). ' Rupiah'),
            'kembalirp' => format_uang($kembali),
            'kembali_terbilang' => ucwords(terbilang($kembali). ' Rupiah'),
        ];

        return response()->json($data);
    }

    public function hitungTotal($harga_jual, $diskon, $jumlah)
    {
        $setting = Setting::first();
        $ppn = $setting->ppn / 100;
        $margin = $setting->margin / 100;
        $tuslah = $setting->tuslah;
        $embalase = $setting->embalase;

        $subtotalSebelumDiskon = $harga_jual * $jumlah;
        $hargaSetelahDiskon = $subtotalSebelumDiskon - ($subtotalSebelumDiskon * ($diskon / 100));
        $subtotalSetelahPPN = $hargaSetelahDiskon + ($hargaSetelahDiskon * $ppn);
        $totalSetelahMargin = $subtotalSetelahPPN + ($subtotalSetelahPPN * $margin);
        $totalSetelahTuslah = $totalSetelahMargin + $tuslah;
        $grandTotal = $totalSetelahTuslah + $embalase;

        return round($grandTotal, 2);
    }

    private function getSettingValue($key)
    {
        return Setting::first()->{$key};
    }


}