@extends('templates.main')

@section('title_page')
    DOCUMENT CREATION
@endsection

@section('breadcrumb_title')
    accounting / document-creation
@endsection

@section('content')
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header text-center">
                    <div class="text-left">
                        <a href="{{ route('reports.document-creation.index', ['project' => '001H']) }}">Rekap</a> | <b>BY
                            USER</b> | <a
                            href="{{ route('reports.document-creation.detail', ['project' => '001H']) }}">Data</a>
                    </div>
                    <div class="d-inline-block">
                        Project: <b>{{ $project }}</b>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-primary float-right"><i
                            class="fas fa-arrow-left"></i> Back to Index</a>
                </div>
            </div>
            <!-- /.card-header -->

            @foreach ($dashboard_data as $year_data)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $year_data['year'] }}</h3>
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>User Code</th>
                                    <td class="text-right">Jan</td>
                                    <td class="text-right">Feb</td>
                                    <td class="text-right">Mar</td>
                                    <td class="text-right">Apr</td>
                                    <td class="text-right">May</td>
                                    <td class="text-right">Jun</td>
                                    <td class="text-right">Jul</td>
                                    <td class="text-right">Aug</td>
                                    <td class="text-right">Sep</td>
                                    <td class="text-right">Oct</td>
                                    <td class="text-right">Nov</td>
                                    <td class="text-right">Dec</td>
                                    <td class="text-right">Total</td>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($year_data['data'] as $user)
                                    <tr>
                                        <th class="pl-3"><small><b>{{ $user['user_code'] }}</b></small></th>
                                    <tr>
                                        <td><small>Document Count</small></td>
                                        @foreach ($user['data'] as $item)
                                            <td class="text-right"><small>{{ $item['invoice_count'] }}</small></td>
                                        @endforeach
                                        <td class="text-right"><small><b>{{ $user['year_summary']['count'] }}</b></small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><small>Average Duration</small></td>
                                        @foreach ($user['data'] as $item)
                                            <td class="text-right"><small>{{ $item['average_duration'] }}</small></td>
                                        @endforeach
                                        <td class="text-right"><small><b>{{ $user['year_summary']['average'] }}</b></small>
                                        </td>
                                    </tr>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

        </div> <!-- /.col -->
    </div> <!-- /.row -->
@endsection
