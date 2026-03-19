@if (isset($data['customer_list']) && $data['customer_list']->count() > 0)
<table class="table align-middle mb-0 table-hover table-centered">
    <thead class="bg-light-subtle">
        <tr>
            <th>Sr. No.</th>
            <th style="width: 15%;">Name</th>
            <th>Email</th>
            <th>Google Id</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @php
        $sr_no = 1;
        @endphp
        @foreach($data['customer_list'] as $customer_list_row)
        <tr>
            <td>
                {{ $sr_no }}
            </td>
            <td>
                {{ $customer_list_row->name }}
                <br><span class="text-success">
                    {{ $customer_list_row->created_at->format('d F Y') }}
                </span>

            </td>
            <td>
                {{ $customer_list_row->email }}
                <br>
                <strong>Phone No. </strong> {{$customer_list_row->phone_number}}
            </td>
            <td>
                {{ $customer_list_row->google_id }}
            </td> 
            <td>
                <div class="d-flex gap-1">
                    <a href="{{ route('customer-wishlist', ['id' => $customer_list_row->id]) }}" title="View Wishlist">
                        <span class="mb-1 mt-1 badge border border-warning text-warning py-1 px-1">
                            View Wishlist
                        </span>
                    </a>
                    <a href="{{ route('manage-customer.show', ['manage_customer' => $customer_list_row->id]) }}" title="View Details">
                        <span class="mb-1 mt-1 badge border border-info text-info py-1 px-1">
                            View Details
                        </span>
                    </a>
                    <a href="{{ route('customer-orders', ['id' => $customer_list_row->id]) }}" title="View Order">
                        <span class="mb-1 mt-1 badge border border-success text-success py-1 px-1">
                            View Order
                        </span>
                    </a>
                    <a href="javascript:void(0);"
                        class="btn btn-soft-danger btn-sm"
                        data-customerid="{{$customer_list_row->id}}"
                        data-title="Edit {{ $customer_list_row->name }}"
                        data-editCustomer-popup="true"
                        data-size="lg"
                        title="Edit {{ $customer_list_row->name }}"
                        data-bs-toggle="tooltip"
                        data-url="{{ route('manage-customer.edit', ['manage_customer' => $customer_list_row->id]) }}">
                        <i class="ti ti-edit"></i>
                    </a>
                    <form method="POST" action="{{ route('manage-customer.destroy', ['manage_customer' => $customer_list_row->id]) }}">
                        @csrf
                        @method('DELETE')
                        <a href="javascript:void(0);"
                            title="Delete {{ $customer_list_row->name }}"
                            data-name="{{ $customer_list_row->name }}"
                            class="show_confirm_customer btn btn-soft-danger btn-sm"
                            data-title="Delete {{ $customer_list_row->name }}"
                            data-bs-toggle="tooltip">
                            <i class="ti ti-trash"></i>
                        </a>
                    </form>
                </div>
            </td>
        </tr>
        @php
        $sr_no++;
        @endphp
        @endforeach
    </tbody>
</table>
<div class="my-pagination" id="pagination-links-customer">
    {{ $data['customer_list']->links('vendor.pagination.bootstrap-4') }}
</div>
@endif