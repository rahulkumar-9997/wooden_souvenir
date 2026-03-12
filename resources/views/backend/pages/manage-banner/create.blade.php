@extends('backend.layouts.master')
@section('title','Add Banner')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Add Banner</h4>
                    <a href="{{ route('manage-banner.index') }}"
                        data-title="Back to Banner List"
                        data-bs-toggle="tooltip"
                        title="Back to Banner List"
                        class="btn btn-sm btn-info">
                        << Back to Banner List
                    </a>

                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-banner.store') }}" enctype="multipart/form-data" id="addNewBanner">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Banner Title *</label>
                                    <input type="text" id="title" name="title" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="content" class="form-label">Banner Content</label>
                                    <textarea id="content" name="content" class="form-control"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="image_path_desktop" class="form-label">Desktop Banner Image</label>
                                    <input type="file" id="image_path_desktop" name="image_path_desktop" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="image_path_mobile" class="form-label">Mobile Banner Image</label>
                                    <input type="file" id="image_path_mobile" name="image_path_mobile" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="link_desktop" class="form-label">Desktop Link</label>
                                    <input type="text" id="link_desktop" name="link_desktop" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="link_mobile" class="form-label">Mobile Link</label>
                                    <input type="text" id="link_mobile" name="link_mobile" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="products" class="form-label">Select Products</label>
                                    <select name="products[]" id="products" class="form-control product-autocomplete" multiple></select>
                                </div>
                            </div>
                            <div class="modal-footer pb-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Banner</button>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@include('backend.layouts.common-modal-form')
@endsection
@push('scripts')
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>

<script>
$(document).ready(function(){
    $('.product-autocomplete').select2({
        placeholder: "Search Product",
        minimumInputLength: 0,
        ajax: {
            url: "{{ route('product.autocomplete') }}",
            dataType: 'json',
            delay: 250,
            data: function(params){
                return {
                    search: params.term
                };
            },
            processResults: function(data){
                return {
                    results: data
                };
            }
        }
    });

});
</script>

@endpush