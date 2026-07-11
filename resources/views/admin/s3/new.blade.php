@extends('layouts.admin')

@section('title')
    New Bucket
@endsection

@section('content-header')
    <h1>Create Bucket<small>Add a new S3 bucket configuration.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.buckets') }}">Buckets</a></li>
        <li class="active">Create Bucket</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.buckets') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Bucket Details</h3>
                </div>

                <div class="box-body row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Bucket Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Bucket Name" required>
                            <p class="small text-muted no-margin">A unique name for this S3 bucket configuration.</p>
                        </div>

                        <div class="form-group">
                            <label for="bucket_name">S3 Bucket Name</label>
                            <input type="text" class="form-control" id="bucket_name" name="bucket_name" value="{{ old('bucket_name') }}" placeholder="my-bucket" required>
                            <p class="small text-muted no-margin">The actual S3 bucket name on your S3 provider.</p>
                        </div>

                        <div class="form-group">
                            <label for="endpoint">Endpoint</label>
                            <input type="text" class="form-control" id="endpoint" name="endpoint" value="{{ old('endpoint') }}" placeholder="https://s3.amazonaws.com">
                            <p class="small text-muted no-margin">The S3 endpoint URL. Leave blank for AWS S3.</p>
                        </div>
                        <div class="form-group">
                            <label for="region">Region</label>
                            <input type="text" class="form-control" id="region" name="region" value="{{ old('region', 'us-east-1') }}" placeholder="us-east-1">
                            <p class="small text-muted no-margin">Required for some S3-compatible providers such as Backblaze B2, which uses regions like <code>us-west-004</code>.</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                            <p class="text-muted small">A brief description of this bucket configuration.</p>
                        </div>

                        <div class="form-group">
                            <label for="access_key">Access Key</label>
                            <input type="text" class="form-control" id="access_key" name="access_key" value="{{ old('access_key') }}" placeholder="Access Key" required>
                            <p class="small text-muted no-margin">Your S3 access key.</p>
                        </div>

                        <div class="form-group">
                            <label for="secret_key">Secret Key</label>
                            <input type="password" class="form-control" id="secret_key" name="secret_key" value="{{ old('secret_key') }}" placeholder="Secret Key" required>
                            <p class="small text-muted no-margin">Your S3 secret key.</p>
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="use_path_style_endpoint" value="0" />
                            <input id="use_path_style_endpoint" name="use_path_style_endpoint" type="checkbox" value="1" {{ old('use_path_style_endpoint') ? 'checked' : '' }} />
                            <label for="use_path_style_endpoint" class="strong">Use Path-Style Endpoints</label>
                        </div>
                        <p class="small text-muted no-margin">Enable for S3-compatible services that require path-style endpoints.</p>
                    </div>

                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="enabled" value="0" />
                            <input id="enabled" name="enabled" type="checkbox" value="1" {{ old('enabled', true) ? 'checked' : '' }} />
                            <label for="enabled" class="strong">Enabled</label>
                        </div>
                        <p class="small text-muted no-margin">Whether this bucket configuration is enabled.</p>
                    </div>
                </div>

                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="button" id="test-connection" class="btn btn-sm btn-info">
                        <i class="fa fa-refresh fa-spin" style="display: none;"></i> Test Connection
                    </button>
                    <input type="submit" class="btn btn-success pull-right" value="Create Bucket" />
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#test-connection').click(function () {
        const $button = $(this);
        const $spinner = $button.find('.fa-spin');

        $button.prop('disabled', true);
        $spinner.show();

        $.post('{{ route('admin.buckets.test-connection') }}', {
            _token: '{{ csrf_token() }}',
            access_key: $('#access_key').val(),
            secret_key: $('#secret_key').val(),
            endpoint: $('#endpoint').val(),
            region: $('#region').val(),
            bucket_name: $('#bucket_name').val(),
            use_path_style_endpoint: $('#use_path_style_endpoint').is(':checked') ? '1' : '0',
        })
        .done(function (response) {
            swal({ type: 'success', title: 'Success', text: response.message });
        })
        .fail(function (xhr) {
            const response = xhr.responseJSON || {};
            const message = response.message || xhr.responseText || `Request failed with status ${xhr.status}.`;
            swal({ type: 'error', title: 'Connection Failed', text: message });
        })
        .always(function () {
            $button.prop('disabled', false);
            $spinner.hide();
        });
    });
    </script>
@endsection
