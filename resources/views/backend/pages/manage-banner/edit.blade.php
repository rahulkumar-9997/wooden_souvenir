@extends('backend.layouts.master')
@section('title','Edit Banner')
@section('main-content')
@push('styles')
@endpush
<!-- Start Container Fluid -->
<div class="container-fluid">
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-1">
                    <h4 class="card-title flex-grow-1">Edit Banner</h4>
                    <a href="{{ route('manage-banner.index') }}" data-title="Back to Banner List"
                        data-bs-toggle="tooltip" title="Back to Banner List" class="btn btn-sm btn-info">
                        << Back to Banner List
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('manage-banner.update',$banner->id) }}"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <label>Banner Title</label>
                                <input type="text" name="title" class="form-control"
                                    value="{{ old('title',$banner->title) }}">
                            </div>
                            <div class="col-md-6">
                                <label>Banner Content</label>
                                <textarea name="content"
                                    class="form-control">{{ old('content',$banner->content) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label>Desktop Banner</label>
                                <input type="file" name="desktop_image" class="form-control">
                                @if($banner->image_path_desktop)
                                <img src="{{ asset('storage/images/banner-desktop/' . $banner->image_path_desktop) }}" width="120">
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label>Mobile Banner</label>
                                <input type="file" name="mobile_image" class="form-control">
                                @if($banner->image_path_mobile)
                                <img src="{{ asset('storage/images/banner-mobile/' . $banner->image_path_mobile) }}" width="120">
                                @endif
                            </div>
                            <div class="col-md-12">
                                <label>Select Products</label>
                                <select name="products[]" class="form-control product-autocomplete" multiple>
                                    @foreach($banner->products as $product)
                                    <option value="{{ $product->id }}" selected>
                                        {{ $product->title }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mt-3">
                                <button class="btn btn-primary">Update Banner</button>
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
    $(document).ready(function() {
        $('.product-autocomplete').select2({
            placeholder: "Search Product",
            minimumInputLength: 1,
            ajax: {
                url: "{{ route('product.autocomplete') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        selected_ids: $('.product-autocomplete').val() || []
                    };
                },
                processResults: function(data) {
                    return data;
                },
                cache: true
            }
        });
    });
</script>

@endpush