@extends('layouts.master')

@section('title')
    Transaksi Penjualan
@endsection

@push('css')
<style>
    .tampil-bayar {
        font-size: 5em;
        text-align: center;
        height: 100px;
    }

    .tampil-terbilang {
        padding: 10px;
        background: #f0f0f0;
    }

    .table-penjualan tbody tr:last-child {
        display: none;
    }

    @media(max-width: 768px) {
        .tampil-bayar {
            font-size: 3em;
            height: 70px;
            padding-top: 5px;
        }
    }
</style>
@endpush

@section('breadcrumb')
    @parent
    <li class="active">Transaksi Penjualan</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-body">
                    
                <form class="form-produk">
                    @csrf
                    <div class="form-group row">
                        <label for="kode_produk" class="col-lg-2">Kode Produk</label>
                        <div class="col-lg-5">
                            <div class="input-group">
                                <input type="hidden" name="id_penjualan" id="id_penjualan" value="{{ $id_penjualan }}">
                                <input type="hidden" name="id_produk" id="id_produk">
                                <input type="text" class="form-control" name="kode_produk" id="kode_produk" oninput="searchProduct()">
                                <span class="input-group-btn">
                                    <button onclick="tampilProduk()" class="btn btn-info btn-flat" type="button"><i class="fa fa-arrow-right"></i></button>
                                </span>
                            </div>
                        </div>
                    </div>
                </form>

                <table class="table table-stiped table-bordered table-penjualan">
                    <thead>
                        <th width="5%">No</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th width="15%">Jumlah</th>
                        <th>Diskon Produk</th>
                        <th>PPN</th>
                        <th>Margin</th>
                        <th>Tuslah</th>
                        <th>Embalase</th>
                        <th>Subtotal</th>
                        <th width="15%"><i class="fa fa-cog"></i></th>
                    </thead>
                </table>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="tampil-bayar bg-primary"></div>
                        <div class="tampil-terbilang"></div>
                    </div>
                    <div class="col-lg-4">
                        <form action="{{ route('transaksi.simpan') }}" class="form-penjualan" method="post">
                            @csrf
                            <input type="hidden" name="id_penjualan" value="{{ $id_penjualan }}">
                            <input type="hidden" name="total" id="total">
                            <input type="hidden" name="total_item" id="total_item">
                            <input type="hidden" name="bayar" id="bayar">

                            <div class="form-group row">
                                <label for="totalrp" class="col-lg-2 control-label">Total</label>
                                <div class="col-lg-8">
                                    <input type="text" id="totalrp" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="bayar" class="col-lg-2 control-label">Bayar</label>
                                <div class="col-lg-8">
                                    <input type="text" id="bayarrp" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="diterima" class="col-lg-2 control-label">Diterima</label>
                                <div class="col-lg-8">
                                    <input type="number" id="diterima" class="form-control" name="diterima" value="{{ $penjualan->diterima ?? 0 }}">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="kembali" class="col-lg-2 control-label">Kembali</label>
                                <div class="col-lg-8">
                                    <input type="text" id="kembali" name="kembali" class="form-control" value="0" readonly>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <div class="parent-button pull-right">
                    <button type="submit" class="btn btn-primary btn-md btn-flat btn-simpan" id="bayar-cash"><i class="fa fa-floppy-o"></i> Bayar Cash</button>
                    <button type="submit"class="btn btn-success btn-md btn-flat btn-online" id="pay-button"><i class="fa fa-floppy-o"></i> Bayar Payment</button>
                </div>
            </div>
        </div>
    </div>
</div>


@includeIf('penjualan_detail.produk')
@includeIf('penjualan_detail.member')
@endsection

@push('scripts')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
    <script type="text/javascript">
        document.getElementById('pay-button').onclick = function() {
            var idPenjualan = {{ $id_penjualan }}; 
                getNewSnapToken(idPenjualan).then(function(token) {
                    window.snap.pay(token, {
                        onSuccess: function(result) {
                            Swal.fire({
                                title: 'Payment Berhasil!',
                                text: 'Pembayaran berhasil dilakukan.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            window.location.href = '{{ route('transaksi.selesai') }}'; 
                        },
                        onPending: function(result) {
                            Swal.fire({
                                title: 'Menunggu Pembayaran!',
                                text: 'Pembayaran sedang diproses.',
                                icon: 'info',
                                confirmButtonText: 'OK'
                            });
                            window.parent.postMessage(result, window.location.origin); 
                        },
                        onError: function(result) {
                            Swal.fire({
                                title: 'Pembayaran Gagal!',
                                text: 'Terjadi kesalahan dalam pembayaran.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            window.parent.postMessage(result, window.location.origin); 
                        },
                        onClose: function() {
                            window.parent.postMessage({status: 'closed'}, window.location.origin); 
                        }
                    });
                }).catch(function(error) {
                    console.error('Error getting snap token:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal mendapatkan Snap Token.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        };

        function getNewSnapToken(idPenjualan) {
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '/api/get-new-snap-token/' + idPenjualan);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        var response = JSON.parse(xhr.responseText);
                        console.log('Response:', response);
                        if (response.snapToken) {
                            resolve(response.snapToken);
                        } else {
                            reject('Error getting snap token');
                        }
                    } else if (xhr.readyState === 4) {
                        console.log('Error response:', xhr.responseText); 
                        reject('Error getting snap token');
                    }
                };
                xhr.send();
            });
        }
</script>
        
<script>

    let table, table2;

        $(function () {
            $('body').addClass('sidebar-collapse');

            table = $('.table-penjualan').DataTable({
                processing: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('transaksi.data', $id_penjualan) }}',
                },
                columns: [
                    {data: 'DT_RowIndex', searchable: false, sortable: false},
                    {data: 'kode_produk'},
                    {data: 'nama_produk'},
                    {data: 'harga_jual'},
                    {data: 'jumlah'},
                    {data: 'diskon'},
                    {data: 'ppn'},
                    {data: 'margin'},
                    {data: 'tuslah'},
                    {data: 'embalase'},
                    {data: 'subtotal'},
                    {data: 'aksi', searchable: false, sortable: false},
                ],
                dom: 'Brt',
                bSort: false,
                paginate: false
            }).on('draw.dt', function () {
                loadForm($('#diskon').val());
                setTimeout(() => {
                    $('#diterima').trigger('input');
                }, 300);
            });

            table2 = $('.table-produk').DataTable();

            let typingTimer;
            const doneTypingInterval = 500;
            let lastInputId;

            $(document).on('input', '.quantity', function () {
                clearTimeout(typingTimer);
                const input = $(this);
                const id = input.data('id');
                let jumlah = input.val();

                if (jumlah === "") {
                    return;
                }

                jumlah = parseInt(jumlah);

                if (isNaN(jumlah) || jumlah < 1) {
                    jumlah = 1;
                } else if (jumlah > 10000) {
                    jumlah = 10000;
                }

                lastInputId = `quantity-${id}`;

                typingTimer = setTimeout(function() {
                    if (!id) {
                        Swal.fire({
                            title: 'Error',
                            text: 'ID penjualan detail tidak ditemukan',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }

                    $.ajax({
                        url: `{{ url('/transaksi') }}/${id}`,
                        type: 'PUT',
                        data: {
                            '_token': $('[name=csrf-token]').attr('content'),
                            'jumlah': jumlah
                        },
                        success: function(response) {
                            table.ajax.reload(() => {
                                loadForm($('#diskon').val());
                                const lastInput = document.getElementById(lastInputId);
                                if (lastInput) {
                                    lastInput.focus();
                                    lastInput.select();
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            let errorMessage = xhr.status + ': ' + xhr.statusText;
                            Swal.fire({
                                title: 'Error',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            const lastInput = document.getElementById(lastInputId);
                            if (lastInput) {
                                lastInput.focus();
                                lastInput.select();
                            }
                        }
                    });
                }, doneTypingInterval);
            });

            $('#kode_produk').focus();

            $('#kode_produk').on('keydown', function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    tampilProduk();
                }
            });

            $('#modal-produk').on('shown.bs.modal', function () {
                $('#modal-produk').find('.dataTables_filter input').focus();
            });

            $(document).on('keydown', '.dataTables_filter input', function(event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    const firstProduct = $('#modal-produk').find('tbody tr:first-child');
                    if (firstProduct.length) {
                        const productId = firstProduct.find('a').attr('onclick').match(/'([^']+)'/g)[0].replace(/'/g, '');
                        const productCode = firstProduct.find('a').attr('onclick').match(/'([^']+)'/g)[1].replace(/'/g, '');
                        pilihProduk(productId, productCode);
                    }
                }
            });
        });

        function handleEnter(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                searchProduct(true);
            }
        }

        function searchProduct(isEnterKey = false) {
            let query = $('#kode_produk').val();

            if ($('#modal-produk').find('.dataTables_filter input').is(':focus')) {
                query = $('#modal-produk').find('.dataTables_filter input').val();
            }

            if (query.length > 0) {
                $.ajax({
                    url: '{{ route("transaksi.search") }}',
                    method: 'GET',
                    data: { query: query },
                    success: function(data) {
                        let productTable = $('#product-list');
                        productTable.empty();

                        if (data.length > 0) {
                            data.forEach(function(product) {
                                productTable.append(`
                                    <tr>
                                        <td>${product.kode}</td>
                                        <td>${product.nama}</td>
                                        <td>${product.harga}</td>
                                        <td><button class="btn btn-primary" onclick="pilihProduk(${product.id}, '${product.kode}')">Pilih</button></td>
                                    </tr>
                                `);
                            });

                            if (isEnterKey && data.length === 1) {
                                pilihProduk(data[0].id, data[0].kode);
                            } else {
                                $('#modal-produk').modal('show');
                            }
                        } else {
                            Swal.fire({
                                title: 'Produk tidak ditemukan',
                                text: 'Silahkan cek kembali kode produk yang Anda masukkan.',
                                icon: 'warning',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'Gagal mengambil data produk',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        }

        function tampilProduk() {
            $('#modal-produk').modal('show');
        }

        function hideProduk() {
            $('#modal-produk').modal('hide');
        }

        function pilihProduk(id, kode) {
            $('#id_produk').val(id);
            $('#kode_produk').val(kode);
            hideProduk();
            tambahProduk();
        }

        function tambahProduk() {
            let id_produk = $('#id_produk').val();
            let existingRow = table.rows().nodes().to$().find('input[data-id]').filter(function() {
                return $(this).closest('tr').find('td:eq(1)').text().trim() === id_produk;
            });

            if (existingRow.length > 0) {
                let currentQuantity = parseInt(existingRow.val());
                let newQuantity = currentQuantity + 1;
                let id_penjualan_detail = existingRow.data('id');

                if (!id_penjualan_detail) {
                    Swal.fire({
                        title: 'Error',
                        text: 'ID penjualan detail tidak ditemukan',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                $.ajax({
                    url: `/transaksi/${id_penjualan_detail}`,
                    type: 'PUT',
                    data: {
                        '_token': $('[name=csrf-token]').attr('content'),
                        'jumlah': newQuantity
                    },
                    success: function(response) {
                        table.ajax.reload(() => {
                            loadForm($('#diskon').val());
                            $('#kode_produk').focus(); // Fokus kembali ke input produk
                        });
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = xhr.status + ': ' + xhr.statusText;
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        $('#kode_produk').focus(); // Fokus kembali ke input produk
                    }
                });
            } else {
                $.post('{{ route('transaksi.store') }}', $('.form-produk').serialize())
                    .done(response => {
                        $('#kode_produk').focus();
                        table.ajax.reload(() => {
                            loadForm($('#diskon').val());
                            $('#kode_produk').focus(); // Fokus kembali ke input produk
                        });
                    })
                    .fail((xhr, status, error) => {
                        let errorMessage = xhr.status + ': ' + xhr.statusText;
                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        $('#kode_produk').focus(); // Fokus kembali ke input produk
                    });
            }
        }

        $(document).ready(function() {
            $('#input_kode_produk').on('input', function() {
                let kodeProduk = $(this).val();

                if (kodeProduk) {
                    $.ajax({
                        url: '{{ route("transaksi.store") }}',
                        method: 'GET',
                        data: { kode: kodeProduk },
                        success: function(data) {
                            if (data.id) {
                                $('#id_produk').val(data.id);
                                $('#kode_produk').val(data.kode);
                                $('#nama_produk').text(data.nama);
                                $('#harga_produk').text(data.harga);
                            } else {
                                Swal.fire({
                                    title: 'Produk tidak ditemukan',
                                    text: 'Silahkan cek kembali kode produk yang Anda masukkan.',
                                    icon: 'warning',
                                    confirmButtonText: 'OK'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Gagal mengambil data produk',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        });

        $(document).on('input', '#diskon', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }

            loadForm($(this).val());
        });

        $('#diterima').on('input', function () {
            if ($(this).val() == "") {
                $(this).val(0).select();
            }

            loadForm($('#diskon').val(), $(this).val());
        }).focus(function () {
            $(this).select();
        });

        $('.btn-simpan').on('click', function (event) {
            event.preventDefault();
            const total = parseFloat($('#bayar').val());
            const diterima = parseFloat($('#diterima').val());

            if (total === 0) {
                Swal.fire({
                    title: 'Error',
                    text: 'Data belum diinput',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else if (diterima < total) {
                Swal.fire({
                    title: 'Pembayaran Belum Selesai!',
                    text: 'Jumlah pembayaran kurang dari total harga.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            } else {
                $('.form-penjualan').submit();
            }
        });

        function deleteData(url) {
            Swal.fire({
                title: "Kamu Yakin?",
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Ya, hapus!",
                cancelButtonText: "Batal"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(url, {
                        '_token': $('[name=csrf-token]').attr('content'),
                        '_method': 'delete'
                    })
                    .done((response) => {
                        table.ajax.reload(() => loadForm($('#diskon').val()));
                        Swal.fire({
                            title: "Dihapus!",
                            text: "Data berhasil dihapus.",
                            icon: "success"
                        });
                    })
                    .fail((errors) => {
                        Swal.fire({
                            title: 'Error',
                            text: 'Tidak dapat menghapus data',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        }

        function loadForm(diskon = 0, diterima = 0) {
            $('#total').val($('.total').text());
            $('#total_item').val($('.total_item').text());

            $.get(`{{ url('/transaksi/loadform') }}/${diskon}/${$('.total').text()}/${diterima}`)
                .done(response => {
                    $('#totalrp').val('Rp. '+ response.totalrp);
                    $('#bayarrp').val('Rp. '+ response.bayarrp);
                    $('#bayar').val(response.bayar);
                    $('.tampil-bayar').text('Bayar: Rp. '+ response.bayarrp);
                    $('.tampil-terbilang').text(response.terbilang);

                    $('#kembali').val('Rp.'+ response.kembalirp);
                    if ($('#diterima').val() != 0) {
                        $('.tampil-bayar').text('Kembali: Rp. '+ response.kembalirp);
                        $('.tampil-terbilang').text(response.kembali_terbilang);
                    }
                })
                .fail(errors => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Tidak dapat menampilkan data',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        }

    </script>
@endpush