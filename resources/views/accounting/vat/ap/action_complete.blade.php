<!-- Button to trigger modal -->
<button type="button" class="btn btn-warning btn-xs" data-toggle="modal" data-target="#updateModal-{{ $model->id }}"
    title="update bupot">
    <i class="fas fa-edit"></i>
</button>
@if ($model->attachment)
    <a href="{{ $model->attachment }}" class="btn btn-primary btn-xs" target="_blank" title="show bupot"><i
            class="fas fa-file-pdf"></i></a>
@endif

<!-- Modal -->
<div class="modal fade" id="updateModal-{{ $model->id }}" tabindex="-1" role="dialog"
    aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Update Faktur</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="updateForm" method="POST" action="{{ route('accounting.vat.purchase_update', $model->id) }}"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">

                    <div class="form-group">
                        <label for="attachment-{{ $model->id }}">File Name</label>
                        <input type="file" class="form-control" id="attachment-{{ $model->id }}"
                            name="attachment">
                        @if ($model->attachment)
                            <a href="{{ $model->attachment }}" target="_blank">View current file</a>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm" form="updateForm">Save changes</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
