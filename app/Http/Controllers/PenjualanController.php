<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\Setting;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


class PenjualanController extends Controller
{
    public function index()
    {
        return view('penjualan.index');
    }

    public function data()
    {
        $penjualan = Penjualan::orderBy('id_penjualan', 'desc')->get();

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('total_harga', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->total_harga);
            })
            ->addColumn('bayar', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->bayar);
            })
            ->addColumn('diterima', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->diterima);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('kode_produk', function ($penjualan) {
                $detail = PenjualanDetail::with('produk')->where('id_penjualan', $penjualan->id_penjualan)->first();
                return $detail && $detail->produk ? '<span class="label label-success">'. $detail->produk->kode_produk .'</span>' : '';
            })
            ->addColumn('nama_produk', function ($penjualan) {
                $detail = PenjualanDetail::with('produk')->where('id_penjualan', $penjualan->id_penjualan)->first();
                return $detail && $detail->produk ? $detail->produk->nama_produk : '';
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . '%';
            })
            ->addColumn('status', function ($penjualan) {
                return $penjualan->status == 'Bayar Payment' ? '<span class="label label-success">Bayar Payment</span>' : 
                       ($penjualan->status == 'Bayar Cash' ? '<span class="label label-success">Bayar Cash</span>' : 
                       '<span class="label label-warning">Pending</span>');
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                return '
                <div class="btn-group">
                    <button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                    <button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                </div>
                ';
            })
            ->rawColumns(['aksi', 'kode_produk', 'status'])
            ->make(true);
    }

    public function create()
    {

        $pendingTransactions = Penjualan::where('status', 'Pending')->count();
        if ($pendingTransactions > 0) {
            return redirect()->route('transaksi.index')->with('error', 'You have pending transactions that need to be completed first.');
        }
        
        $penjualan = new Penjualan();
        $penjualan->kode_produk = '';
        $penjualan->nama_produk = '';
        $penjualan->order_id = '';
        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->status = 'Pending';
        $penjualan->id_user = auth()->id();
        $penjualan->save();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        return redirect()->route('transaksi.index');
    }

    public function store(Request $request)
    {
        $penjualan = Penjualan::findOrFail($request->id_penjualan);

        if ($request->total <= 0) {
            return redirect()->back()->with('error', 'Total harga tidak boleh 0.');
        }

        $firstDetail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->first();
        if ($firstDetail) {
            $penjualan->kode_produk = $firstDetail->produk->kode_produk;
            $penjualan->nama_produk = $firstDetail->produk->nama_produk;
        }
        $penjualan->total_item = $request->total_item;
        $penjualan->total_harga = $request->total;
        $penjualan->bayar = $request->bayar;
        $penjualan->diterima = $request->diterima;

        // Simpan detail produk ke dalam sesi
        $detailProduk = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get()->toArray();
        session(['detail_produk' => $detailProduk]);

        // Cek metode pembayaran dan set status
        if ($request->metode_pembayaran == 'Midtrans') {
            $penjualan->status = 'Bayar Payment';
        } else {
            $penjualan->status = $penjualan->bayar >= $penjualan->total_harga ? 'Bayar Cash' : 'Pending';
        }

        $totalDiskon = 0;
        foreach ($detailProduk as $item) {
            $totalDiskon += $item['diskon'];
        }
        $averageDiskon = count($detailProduk) > 0 ? $totalDiskon / count($detailProduk) : 0;
        $penjualan->diskon = $averageDiskon;
        $penjualan->update();

        foreach ($detailProduk as $item) {
            $produk = Produk::find($item['id_produk']);
            $produk->stok -= $item['jumlah'];
            $produk->update();
        }

        return redirect()->route('transaksi.selesai');
    }



    public function show($id)
    {
        $detail = PenjualanDetail::with('produk')->where('id_penjualan', $id)->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">'. $detail->produk->kode_produk .'</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('harga_jual', function ($detail) {
                return 'Rp. '. format_uang($detail->harga_jual);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah);
            })
            ->addColumn('subtotal', function ($detail) {
                return 'Rp. '. format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }

    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        $detail    = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok += $item->jumlah;
                $produk->update();
            }

            $item->delete();
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function selesai()
    {
        $setting = Setting::first();
        // Fetch the penjualan data using session or other method
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (!$penjualan) {
            abort(404);
        }
        
        // Load the relevant details for the penjualan
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();
        
        // Return view with necessary data
        return view('penjualan.selesai', compact('setting', 'penjualan', 'detail'));
    }

    public function notaKecil()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();
        
        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail'));
    }

    public function notaBesar()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        $customPaper = array(0, 0, 609, 440);
        $pdf->setPaper($customPaper, 'portrait');
        return $pdf->stream('Transaksi-'. date('Y-m-d-his') .'.pdf');
    }

    public function search(Request $request)
    {
        $query = $request->get('query');
        $products = Produk::where('kode_produk', 'LIKE', "%{$query}%")
                            ->orWhere('nama_produk', 'LIKE', "%{$query}%")
                            ->get();

        return response()->json($products);
    }

}
