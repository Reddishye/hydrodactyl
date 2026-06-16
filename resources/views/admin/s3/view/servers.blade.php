@extends('layouts.admin')

@section('title')
    {{ $bucket->name }}: Servers
@endsection

@section('content-header')
    <h1>{{ $bucket->name }}<small>Servers using this S3 bucket configuration.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.buckets') }}">S3 Configurations</a></li>
        <li><a href="{{ route('admin.buckets.view', $bucket->id) }}">{{ $bucket->name }}</a></li>
        <li class="active">Servers</li>
    </ol>
@endsection

@section('content')
@include('admin.s3.partials.navigation')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Server List</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>ID</th>
                        <th>Server Name</th>
                        <th>Owner</th>
                        <th>Service</th>
                    </tr>
                    @foreach($servers as $server)
                        <tr>
                            <td><code>{{ $server->uuidShort }}</code></td>
                            <td><a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></td>
                            <td><a href="{{ route('admin.users.view', $server->owner_id) }}">{{ $server->user->username ?? 'N/A' }}</a></td>
                            <td>{{ $server->nest->name ?? 'N/A' }} ({{ $server->egg->name ?? 'N/A' }})</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
