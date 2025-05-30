<div class="card card-info mb-1">
    <div class="card-header p-1">
        <h3 class="card-title">By Record Count of Creating Date</h3>
    </div>
</div>

@foreach ($count_data as $item)
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $item['year'] }}</h3>
            <small class="float-right">
                <i class="fas fa-times" style="color: red"></i> = In-complete |
                <i class="fas fa-check" style="color: green"></i> = Complete
            </small>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th rowspan="2" class="align-middle">Desc</th>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-center" colspan="2"><small>{{ $sub_item['month_name'] }}</small></td>
                        @endforeach
                        <th colspan="2" class="text-center align-middle">Total</th>
                    </tr>
                    <tr>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small><i class="fas fa-times" style="color: red"></i></small></td>
                            <td class="text-right"><small><i class="fas fa-check" style="color: green"></i></small></td>
                        @endforeach
                        <td class="text-right"><small>count</small></td>
                        <td class="text-center"><small><i class="fas fa-check" style="color: green"></i></small></td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><small>Purchase</small></td>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small>{{ $sub_item['out']['outstanding'] }}</small>
                            <td class="text-right"><small>{{ $sub_item['out']['complete'] }}</small>
                            </td>
                        @endforeach
                        <td class="text-right"><small>{{ $item['out']['total'] }}</small></td>
                        <td class="text-right"><small>{{ $item['out']['percent_complete'] }} %</small></td>
                    </tr>
                    <tr>
                        <td><small>Sales</small></td>
                        @foreach ($item['data'] as $sub_item)
                            <td class="text-right"><small>{{ $sub_item['in']['outstanding'] }}</small></td>
                            <td class="text-right"><small>{{ $sub_item['in']['complete'] }}</small></td>
                        @endforeach
                        <td class="text-right"><small>{{ $item['in']['total'] }}</small></td>
                        <td class="text-right"><small>{{ $item['in']['percent_complete'] }} %</small></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endforeach
