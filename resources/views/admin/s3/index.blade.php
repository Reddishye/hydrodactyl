@extends('layouts.admin')

@section('title')
    List S3 Buckets
@endsection

@section('content-header')
    <h1>S3 Configurations<small>All S3 bucket configurations on the system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">S3</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">S3 Bucket List</h3>
                <div class="box-tools search01">
                    <form action="{{ route('admin.buckets') }}" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" name="filter[name]" class="form-control pull-right" value="{{ request()->input()['filter']['name'] ?? '' }}" placeholder="Search Buckets">
                            <div class="input-group-btn">
                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                <a href="{{ route('admin.buckets.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Bucket Name</th>
                            <th>Enabled</th>
                            <th>Connected Servers</th>
                        </tr>
                        @foreach ($buckets as $bucket)
                            <tr data-server="{{ $bucket->id }}">
                                <td><code>{{ $bucket->id }}</code></td>
                                <td><a href="{{ route('admin.buckets.view', $bucket->id) }}">{{ $bucket->name }}</a></td>
                                <td><code>{{ $bucket->bucket_name }}</code></td>
                                <td>
                                    @if($bucket->enabled)
                                        <span class="label label-success">Enabled</span>
                                    @else
                                        <span class="label label-danger">Disabled</span>
                                    @endif
                                </td>
                                <td>{{ $bucket->server_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
@endsection
