<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Faktur;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VatController extends Controller
{
    public function index()
    {
        $page = request()->query('page', 'dashboard');
        $status = request()->query('status');

        $count_data = $this->generate_count_data();
        $amount_data = $this->generate_amount_data();

        $views = [
            'dashboard' => 'accounting.vat.dashboard',
            'search' => 'accounting.vat.search',
            'purchase' => $status == 'incomplete' ? 'accounting.vat.ap.incomplete' : 'accounting.vat.ap.complete',
            'sales' => $status == 'incomplete' ? 'accounting.vat.ar.incomplete' : 'accounting.vat.ar.complete',
        ];

        if ($page === 'search') {
            $customers = Customer::orderBy('name')->get();
            return view($views[$page], compact('customers'));
        }

        if ($page === 'dashboard') {
            return view($views[$page], compact('amount_data', 'count_data'));
        }

        return view($views[$page] ?? $views['default']);
    }

    public function purchase_update(Request $request, $id)
    {
        $document = Faktur::findOrFail($id);
        $document->response_by = auth()->user()->id;
        $document->response_at = now();

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = $file->getClientOriginalExtension();
            $filename = 'faktur_' . uniqid() . '.' . $extension;
            $file->move(public_path('faktur'), $filename);
            $document->attachment = $filename;
        }

        $document->save();

        return redirect()->back()->with('success', 'Faktur updated successfully');
    }

    public function sales_update(Request $request, $id)
    {
        $existingDocument = Faktur::where('doc_num', $request->input('doc_num'))->first();
        if ($existingDocument && $existingDocument->id != $id) {
            return redirect()->back()->with('error', 'Document number already exists.');
        }

        $document = Faktur::findOrFail($id);
        $document->doc_num = $request->input('doc_num');
        $document->posting_date = $request->input('posting_date');
        $document->user_code = auth()->user()->username;
        $document->save();

        return redirect()->back()->with('success', 'Document number and posting date updated successfully');
    }

    public function data()
    {
        $page = request()->query('page');
        $status = request()->query('status');

        $query = Faktur::query();

        if ($page === 'purchase') {
            $query->where('type', 'purchase');
            $action_button = $status === 'incomplete' ? 'accounting.vat.ap.action' : 'accounting.vat.ap.action_complete';

            if ($status === 'incomplete') {
                $query->whereNull('attachment');
            } else {
                $query->whereNotNull('attachment');
            }
        } else {
            $query->where('type', 'sales');
            $action_button = $status === 'incomplete' ? 'accounting.vat.ar.action' : 'accounting.vat.ar.action_complete';

            if ($status === 'incomplete') {
                $query->where(function ($query) {
                    $query->whereNull('doc_num')
                        ->orWhereNull('faktur_no');
                });
            } else {
                $query->whereNotNull('doc_num')
                    ->whereNotNull('faktur_no');
            }
        }

        $documents = $query->orderBy('create_date', 'desc')->get();

        return datatables()->of($documents)
            ->addColumn('amount', function ($document) {
                $dpp = number_format($document->dpp, 2);
                $ppn = number_format($document->ppn, 2);
                return '<small>DPP: ' . $dpp . '</small><br><small>PPN: ' . $ppn . '</small>';
            })
            ->editColumn('create_date', function ($document) {
                return date('d-M-Y', strtotime($document->create_date));
            })
            ->editColumn('posting_date', function ($document) {
                return date('d-M-Y', strtotime($document->posting_date));
            })
            ->addColumn('invoice', function ($document) {
                return '<small>No.' . $document->invoice_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->invoice_date)) . '</small>';
            })
            ->addColumn('faktur', function ($document) {
                if (is_null($document->faktur_date)) {
                    return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl. - </small>';
                }
                return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->faktur_date)) . '</small>';
            })
            ->addColumn('customer', function ($document) {
                return '<small>' . $document->customer->name . '</small>';
            })
            ->editColumn('remarks', function ($document) {
                return '<small>' . strtolower($document->remarks) . '</small>';
            })
            // add column name days that count the difference between posting_date and today
            ->editColumn('days', function ($document) {
                $today = date('Y-m-d');
                $diff = date_diff(date_create($document->posting_date), date_create($today));
                return $diff->format('%a');
            })
            ->editColumn('updated_by', function ($document) {
                $updatedAt = Carbon::parse($document->updated_at)->addHours(8)->format('d-M-Y H:i');
                return '<small>' . $document->updated_by . '</small><br><small>at ' . $updatedAt . '</small>';
            })
            ->addColumn('doc_date', function ($document) {
                $createDate = Carbon::parse($document->create_date)->format('d-M-Y');
                $postingDate = Carbon::parse($document->posting_date)->format('d-M-Y');
                return $createDate . '<br>' . $postingDate;
            })
            ->addColumn('sales_days', function ($document) {
                $today = date('Y-m-d');
                $diff = date_diff(date_create($document->invoice_date), date_create($today));
                return $diff->format('%a');
            })
            ->addColumn('action', $action_button)
            ->addIndexColumn()
            ->rawColumns(['remarks', 'action', 'updated_by', 'amount', 'invoice', 'customer', 'faktur'])
            ->toJson();
    }

    public function generate_count_data()
    {
        $years = DB::table('fakturs')
            ->select(DB::raw('DISTINCT YEAR(create_date) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
        ];

        $data = [];

        foreach ($years as $year) {
            $yearData = [
                'year' => $year,
                'purchase' => [
                    'total' => 0,
                    'percent_complete' => 0,
                    'outstanding' => 0,
                    'complete' => 0
                ],
                'sales' => [
                    'total' => 0,
                    'percent_complete' => 0,
                    'outstanding' => 0,
                    'complete' => 0
                ],
                'data' => []
            ];

            $total_purchase_outstanding = 0;
            $total_purchase_complete = 0;
            $total_sales_outstanding = 0;
            $total_sales_complete = 0;

            foreach ($months as $month => $monthName) {

                $purchase_outstanding = $this->count_outstanding_monthly($year, $month, 'purchase');
                $purchase_complete = $this->count_complete_monthly($year, $month, 'purchase');
                $sales_outstanding = $this->count_outstanding_sales_monthly($year, $month, 'sales');
                $sales_complete = $this->count_complete_sales_monthly($year, $month, 'sales');

                $monthData = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'purchase' => [
                        'outstanding' => $purchase_outstanding,
                        'complete' => $purchase_complete,
                        'percent' => $purchase_outstanding + $purchase_complete > 0 ? number_format($purchase_complete / ($purchase_outstanding + $purchase_complete) * 100, 1) : 0
                    ],
                    'sales' => [
                        'outstanding' => $sales_outstanding,
                        'complete' => $sales_complete,
                        'percent' => $sales_outstanding + $sales_complete > 0 ? number_format($sales_complete / ($sales_outstanding + $sales_complete) * 100, 1) : 0
                    ]
                ];

                $yearData['data'][] = $monthData;

                // Tambahkan jumlah bulanan ke jumlah tahunan
                $yearData['purchase']['total'] += $purchase_outstanding + $purchase_complete;
                $yearData['sales']['total'] += $sales_outstanding + $sales_complete;

                // Tambahkan ke total tahunan
                $total_purchase_outstanding += $purchase_outstanding;
                $total_purchase_complete += $purchase_complete;
                $total_sales_outstanding += $sales_outstanding;
                $total_sales_complete += $sales_complete;
            }

            // Hitung persentase penyelesaian pembelian tahunan
            $yearData['purchase']['outstanding'] = $total_purchase_outstanding;
            $yearData['purchase']['complete'] = $total_purchase_complete;
            $yearData['purchase']['percent_complete'] = $total_purchase_outstanding + $total_purchase_complete > 0 ? number_format($total_purchase_complete / ($total_purchase_outstanding + $total_purchase_complete) * 100, 1) : 0;

            // Hitung persentase penyelesaian penjualan tahunan
            $yearData['sales']['outstanding'] = $total_sales_outstanding;
            $yearData['sales']['complete'] = $total_sales_complete;
            $yearData['sales']['percent_complete'] = $total_sales_outstanding + $total_sales_complete > 0 ? number_format($total_sales_complete / ($total_sales_outstanding + $total_sales_complete) * 100, 1) : 0;

            $data[] = $yearData;
        }

        return $data;
    }

    public function generate_amount_data()
    {
        $years = DB::table('fakturs')
            ->select(DB::raw('DISTINCT YEAR(faktur_date) as year'))
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
        ];

        $data = [];

        foreach ($years as $year) {
            $yearData = [
                'year' => $year,
                'sales' => 0,
                'purchase' => 0,
                'difference' => 0,
                'data' => []
            ];

            foreach ($months as $month => $monthName) {

                $monthData = [
                    'month' => $month,
                    'month_name' => $monthName,
                    'sales' => number_format($this->sum_amount_monthly($year, $month, 'sales') / 1000, 2),
                    'purchase' => number_format($this->sum_amount_monthly($year, $month, 'purchase') / 1000, 2),
                    'difference' => number_format($this->calculate_difference_monthly($year, $month) / 1000, 2)
                ];

                $yearData['data'][] = $monthData;
            }

            // Format jumlah tahunan
            $yearData['sales'] = number_format($this->sum_amount_yearly($year, 'sales') / 1000, 2);
            $yearData['purchase'] = number_format($this->sum_amount_yearly($year, 'purchase') / 1000, 2);
            $yearData['difference'] = number_format($this->calculate_difference_yearly($year) / 1000, 2);

            $data[] = $yearData;
        }

        return $data;
    }

    public function search_data()
    {
        // Return empty data if search hasn't been clicked
        if (!request('search_clicked')) {
            return datatables()->of([])->addIndexColumn()->toJson();
        }

        $query = Faktur::query()
            ->with('customer');

        if (request('faktur_no')) {
            $query->where('faktur_no', 'like', '%' . request('faktur_no') . '%');
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('invoice_no')) {
            $query->where('invoice_no', 'like', '%' . request('invoice_no') . '%');
        }

        if (request('customer_name')) {
            $query->where('customer_id', request('customer_name'));
        }

        if (request('doc_num')) {
            $query->where('doc_num', 'like', '%' . request('doc_num') . '%');
        }

        return datatables()->of($query)
            ->addColumn('amount', function ($document) {
                $dpp = number_format($document->dpp, 2);
                $ppn = number_format($document->ppn, 2);
                return '<small>DPP: ' . $dpp . '</small><br><small>PPN: ' . $ppn . '</small>';
            })
            ->editColumn('create_date', function ($document) {
                return date('d-M-Y', strtotime($document->create_date));
            })
            ->addColumn('invoice', function ($document) {
                if (is_null($document->invoice_no)) {
                    return '<small>No. - </small><br><small>Tgl. - </small>';
                }
                if (is_null($document->invoice_date)) {
                    return '<small>No.' . $document->invoice_no . '</small><br><small>Tgl. - </small>';
                }
                return '<small>No.' . $document->invoice_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->invoice_date)) . '</small>';
            })
            ->addColumn('faktur', function ($document) {
                if (is_null($document->faktur_date)) {
                    return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl. - </small>';
                }
                return '<small>No.' . $document->faktur_no . '</small><br><small>Tgl.' . date('d-M-Y', strtotime($document->faktur_date)) . '</small>';
            })
            ->addColumn('customer', function ($document) {
                return '<small>' . $document->customer->name . '</small>';
            })
            ->addColumn('action', function ($document) {
                $showButton = '<a href="' . route('accounting.vat.show', $document->id) . '" class="btn btn-xs btn-success">show</a>';

                $attachmentButton = '';
                if ($document->attachment) {
                    $attachmentButton = ' <a href="' . $document->attachment . '" target="_blank" class="btn btn-xs btn-info"><i class="fas fa-paperclip"></i></a>';
                }

                return $showButton . $attachmentButton;
            })
            ->addIndexColumn()
            ->rawColumns(['amount', 'invoice', 'customer', 'faktur', 'action'])
            ->toJson();
    }

    private function sum_amount_monthly($year, $month, $type)
    {
        return Faktur::whereYear('faktur_date', $year)
            ->whereMonth('faktur_date', $month)
            ->where('type', $type)
            ->sum('ppn');
    }

    private function calculate_difference_monthly($year, $month)
    {
        $sales = $this->sum_amount_monthly($year, $month, 'sales');
        $purchase = $this->sum_amount_monthly($year, $month, 'purchase');
        return $purchase - $sales;
    }

    private function sum_amount_yearly($year, $type)
    {
        return Faktur::whereYear('faktur_date', $year)
            ->where('type', $type)
            ->sum('ppn');
    }

    private function calculate_difference_yearly($year)
    {
        $sales = $this->sum_amount_yearly($year, 'sales');
        $purchase = $this->sum_amount_yearly($year, 'purchase');
        return $purchase - $sales;
    }

    private function count_complete_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNotNull('attachment')
            ->count();
    }

    private function count_outstanding_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNull('attachment')
            ->count();
    }

    private function count_outstanding_sales_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNull('doc_num')
            ->count();
    }

    private function count_complete_sales_monthly($year, $month, $type)
    {
        return Faktur::whereYear('create_date', $year)
            ->whereMonth('create_date', $month)
            ->where('type', $type)
            ->whereNotNull('doc_num')
            ->count();
    }

    public function show(Faktur $faktur)
    {
        return view('accounting.vat.show', compact('faktur'));
    }

    public function update(Request $request, Faktur $faktur)
    {
        try {
            if ($faktur->type === 'purchase') {
                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $extension = $file->getClientOriginalExtension();
                    $filename = 'faktur_' . uniqid() . '.' . $extension;
                    $file->move(public_path('faktur'), $filename);

                    $faktur->attachment = $filename;
                    $faktur->response_by = auth()->user()->id;
                    $faktur->response_at = now();
                    $faktur->save();

                    return redirect()->back()->with('success', 'File uploaded successfully');
                }
                return redirect()->back()->with('error', 'No file uploaded');
            } else {
                // For sales type
                $request->validate([
                    'doc_num' => 'required|string|max:255',
                ]);

                // Check if doc_num already exists
                $existingFaktur = Faktur::where('doc_num', $request->doc_num)
                    ->where('id', '!=', $faktur->id)
                    ->first();

                if ($existingFaktur) {
                    return redirect()->back()->with('error', 'Document number already exists');
                }

                $faktur->doc_num = $request->doc_num;
                $faktur->user_code = auth()->user()->username;
                $faktur->save();

                return redirect()->back()->with('success', 'Document number updated successfully');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the record');
        }
    }
}
