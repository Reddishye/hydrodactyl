@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'egg-updater'])

@section('title')
  Egg Updater Settings
@endsection

@section('content-header')
  <h1>Egg Updater<small>Configure automatic egg update checking.</small></h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('admin.index') }}">Admin</a></li>
    <li><a href="{{ route('admin.settings') }}">Settings</a></li>
    <li class="active">Egg Updater</li>
  </ol>
@endsection

@section('content')
  @yield('settings::nav')

  {{-- ALLOWED_EGG_HOSTS warning --}}
  @if(!empty($allowedHosts) && $unallowedEggs->isNotEmpty())
    <div class="row">
      <div class="col-md-10 col-md-offset-1">
        <div class="box box-warning">
          <div class="box-header with-border">
            <i class="fa fa-exclamation-triangle"></i>
            <h3 class="box-title">Hosts Not Allowed</h3>
          </div>
          <div class="box-body">
            <p>
              The following eggs have update URLs that are not allowed by this instance of Hydrodactyl.
              Allowed hosts are configured via the <code>ALLOWED_EGG_HOSTS</code> environment variable.
            </p>
            <p>
              To fix this, add the missing host(s) to your <code>ALLOWED_EGG_HOSTS</code> environment variable
              (comma-separated), or update the egg's <code>update_url</code> to point to an allowed host.
            </p>
            <table class="table table-hover table-striped">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Update URL</th>
                <th>Host</th>
              </tr>
              @foreach($unallowedEggs as $egg)
                @php $host = parse_url($egg->update_url, PHP_URL_HOST); @endphp
                <tr>
                  <td><code>{{ $egg->id }}</code></td>
                  <td>
                    <a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a>
                  </td>
                  <td><code>{{ $egg->update_url }}</code></td>
                  <td><span class="label label-danger">{{ $host ?: 'Invalid URL' }}</span></td>
                </tr>
              @endforeach
            </table>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <div class="box box-primary">
        <div class="box-header with-border">
          <i class="fa fa-refresh"></i>
          <h3 class="box-title">Egg Auto-Updater</h3>
        </div>
        <form action="{{ route('admin.settings.egg-updater') }}" method="POST">
          <input type="hidden" name="_method" value="PATCH">
          @csrf
          <div class="box-body">
            <div class="row">
              <div class="form-group col-md-12">
                <div class="checkbox checkbox-primary no-margin-bottom">
                  <input id="pEnabled" name="egg_updater_enabled" type="checkbox" value="1"
                    @if(old('egg_updater_enabled', $enabled) === '1') checked @endif />
                  <label for="pEnabled" class="strong">Enable automatic egg updates</label>
                  <p class="text-muted small">
                    When enabled, eggs with an <code>update_url</code> configured will be periodically checked for updates.
                  </p>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-12">
                <label class="control-label">Check frequency</label>
                <div class="btn-group btn-group-justified" role="group" data-toggle="buttons">
                  @php $freq = old('egg_updater_frequency', $frequency); @endphp
                  <label class="btn btn-default @if($freq === 'manual') active @endif">
                    <input type="radio" name="egg_updater_frequency" autocomplete="off" value="manual"
                      @if($freq === 'manual') checked @endif> Manual only
                  </label>
                  <label class="btn btn-default @if($freq === 'daily') active @endif">
                    <input type="radio" name="egg_updater_frequency" autocomplete="off" value="daily"
                      @if($freq === 'daily') checked @endif> Daily (04:00)
                  </label>
                  <label class="btn btn-default @if($freq === 'weekly') active @endif">
                    <input type="radio" name="egg_updater_frequency" autocomplete="off" value="weekly"
                      @if($freq === 'weekly') checked @endif> Weekly (Sun 04:00)
                  </label>
                </div>
                <p class="text-muted small">How often to check eggs for updates. "Manual" disables scheduled checks.</p>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-12">
                <div class="checkbox checkbox-primary no-margin-bottom">
                  <input id="pAutoApply" name="egg_updater_auto_apply" type="checkbox" value="1"
                    @if(old('egg_updater_auto_apply', $auto_apply) === '1') checked @endif />
                  <label for="pAutoApply" class="strong">Apply updates automatically</label>
                  <p class="text-muted small">
                    If checked, eggs will be updated automatically when a new version is detected.
                    If unchecked, only a notification will be logged.
                  </p>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-12">
                <div class="checkbox checkbox-primary no-margin-bottom">
                  <input id="pNotify" name="egg_updater_notify" type="checkbox" value="1"
                    @if(old('egg_updater_notify', $notify) === '1') checked @endif />
                  <label for="pNotify" class="strong">Notify on updates</label>
                  <p class="text-muted small">
                    Show admin alerts when updates are found (requires manual review if auto-apply is off).
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div class="box-footer">
            <button type="submit" class="btn btn-primary btn-sm pull-right">Save</button>
          </div>
        </form>
      </div>
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">Eggs with update_url</h3>
        </div>
        <div class="box-body table-responsive no-padding">
          @php /* $eggs passed from controller */ @endphp
          @if($eggs->isEmpty())
            <div class="callout callout-info" style="margin:16px;">
              <p>No eggs have an <code>update_url</code> configured. Configure it in the egg settings to enable auto-updates.</p>
            </div>
          @else
            <table class="table table-hover">
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>URL</th>
                <th>Status</th>
                <th>Last Checked</th>
                <th>Excluded</th>
              </tr>
              @foreach($eggs as $egg)
                <tr>
                  <td><code>{{ $egg->id }}</code></td>
                  <td><a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></td>
                  <td><code>{{ \Str::limit($egg->update_url, 40) }}</code></td>
                  <td>
                    @if($egg->exclude_from_updates)
                      <span class="label label-default">Excluded</span>
                    @elseif($egg->last_update_check_at)
                      <span class="label label-success">Checked</span>
                    @else
                      <span class="label label-warning">Not checked</span>
                    @endif
                  </td>
                  <td>{{ $egg->last_update_check_at ? $egg->last_update_check_at->diffForHumans() : '—' }}</td>
                  <td>
                    @if($egg->exclude_from_updates)
                      <i class="fa fa-times text-danger"></i>
                    @else
                      <i class="fa fa-check text-success"></i>
                    @endif
                  </td>
                </tr>
              @endforeach
            </table>
          @endif
        </div>
        <div class="box-footer">
          <a href="{{ route('admin.nests') }}" class="btn btn-sm btn-default">Manage Nests</a>
          <a href="{{ route('admin.nests.egg.new') }}" class="btn btn-sm btn-success pull-right">New Egg</a>
        </div>
      </div>
    </div>
  </div>
@endsection
