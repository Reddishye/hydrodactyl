@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'egg-updater'])

@section('title')
  Egg Updater
@endsection

@section('content-header')
  <h1>Egg Updater<small>Configure automatic updates for eggs with update URLs.</small></h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('admin.index') }}">Admin</a></li>
    <li><a href="{{ route('admin.settings') }}">Settings</a></li>
    <li class="active">Egg Updater</li>
  </ol>
@endsection

@section('content')
  @yield('settings::nav')
  <style>
    .egg-excluded { background-color: #181818 !important; }
    .egg-updater-table td, .egg-updater-table th { vertical-align: middle !important; }
    .egg-updater-table td { border-top: 1px solid #1d1d1d !important; }
    .egg-updater-table thead th { border-bottom: 2px solid #4d5b69 !important; }
  </style>
  <div id="actionFeedback" style="display:none;"></div>

  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <form action="{{ route('admin.settings.egg-updater') }}" method="POST">
        <div class="box box-primary">
          <div class="box-header with-border">
            <i class="fa fa-gears"></i> <h3 class="box-title" style="display:inline;">Schedule</h3>
          </div>
          <div class="box-body">
            <div class="row">
              <div class="form-group col-md-4">
                <label class="control-label">Enable automatic updates</label>
                <div style="margin-top:6px;">
                  <label>
                    <input type="hidden" name="egg_updater_enabled" value="0">
                    <input type="checkbox" name="egg_updater_enabled" value="1" {{ $enabled === '1' ? 'checked' : '' }}>
                    Enabled
                  </label>
                </div>
                <p class="text-muted small" style="margin-top:4px;">If disabled, the check frequency will be ignored and no eggs will be checked automatically.</p>
              </div>
              <div class="form-group col-md-4">
                <label class="control-label">Check frequency</label>
                <input type="text" name="egg_updater_frequency" class="form-control" value="{{ old('egg_updater_frequency', $frequency) }}" placeholder="0 4 * * *">
                <p class="text-muted small" style="margin-top:4px;">Cron expression (e.g. <code>0 4 * * *</code>). Leave empty and disable the toggle above to skip automatic checks.</p>
              </div>
              <div class="form-group col-md-4">
                <label class="control-label">Auto-apply updates</label>
                <div style="margin-top:6px;">
                  <label>
                    <input type="hidden" name="egg_updater_auto_apply" value="0">
                    <input type="checkbox" name="egg_updater_auto_apply" value="1" {{ $auto_apply === '1' ? 'checked' : '' }}>
                    Enabled
                  </label>
                </div>
                <p class="text-muted small" style="margin-top:4px;">If enabled, available updates are applied automatically. Otherwise, eggs are marked as update available for manual review.</p>
              </div>
            </div>
          </div>
          <div class="box-footer">
            {{ csrf_field() }}
            <input type="hidden" name="_method" value="PATCH">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-save"></i> Save Settings</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <div class="box box-primary">
        <div class="box-header with-border">
          <i class="fa fa-egg"></i> <h3 class="box-title" style="display:inline;">Eggs with update URLs</h3>
          <span class="text-muted" style="margin-left:8px;">Total: {{ $stats['total'] }}</span>
          <span class="text-success" style="margin-left:8px;">OK: {{ $stats['ok'] }}</span>
          <span class="text-danger" style="margin-left:8px;">Errors: {{ $stats['errors'] }}</span>
          <span class="text-warning" style="margin-left:8px;">Pending: {{ $stats['pending'] }}</span>
          <span class="text-muted" style="margin-left:8px;">Excluded: {{ $stats['excluded'] }}</span>
          <div class="box-tools pull-right">
            <button id="checkAllBtn" class="btn btn-info btn-sm" data-url="{{ route('admin.settings.egg-updater.check-all') }}" @if($enabled !== '1' || $eggs->isEmpty()) disabled @endif>
              <i class="fa fa-refresh"></i> Check all
            </button>
          </div>
        </div>
        <div class="box-body table-responsive no-padding">
          @if($eggs->isEmpty())
            <div class="callout callout-info" style="margin-bottom:0;">
              <p>No eggs have an update URL configured.</p>
            </div>
          @else
            <div id="checkAllFeedback" style="display:none;margin:12px;"></div>
            <table class="table table-hover egg-updater-table">
              <thead>
                <tr>
                  <th class="text-center">ID</th>
                  <th>Name</th>
                  <th>Update URL</th>
                  <th class="text-center">Status</th>
                  <th class="text-center">Last Check</th>
                  <th class="text-center" style="width:240px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($eggs as $egg)
                  @php
                    $isExcluded = $egg->exclude_from_updates;
                  @endphp
                  <tr id="egg-row-{{ $egg->id }}" class="{{ $isExcluded ? 'egg-excluded' : '' }}">
                    <td class="text-center"><code>{{ $egg->id }}</code></td>
                    <td>
                      <a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a>
                    </td>
                    <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $egg->update_url }}">
                      <code>{{ $egg->update_url }}</code>
                    </td>
                    <td class="text-center">
                      <span class="egg-status-badge">
                        @include('admin.settings.partials.egg-status-badge', ['egg' => $egg])
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="last-checked-text">
                        {{ $egg->last_update_check_at ? $egg->last_update_check_at->diffForHumans() : 'Never' }}
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-sm btn-primary check-single" data-egg-id="{{ $egg->id }}" data-url="{{ route('admin.settings.egg-updater.check', $egg->id) }}" @if($enabled !== '1' || $isExcluded) disabled @endif>
                          <i class="fa fa-refresh"></i> Check
                        </button>
                        <button class="btn btn-sm btn-success apply-single" data-egg-id="{{ $egg->id }}" data-url="{{ route('admin.settings.egg-updater.apply', $egg->id) }}" style="display:none;" @if($enabled !== '1') disabled @endif>
                          <i class="fa fa-cloud-download"></i> Apply
                        </button>
                        <button class="btn btn-sm btn-{{ $isExcluded ? 'warning' : 'default' }} toggle-exclude" data-egg-id="{{ $egg->id }}" data-url="{{ route('admin.settings.egg-updater.toggle-exclude', $egg->id) }}">
                          <i class="fa fa-{{ $isExcluded ? 'check' : 'ban' }}"></i> {{ $isExcluded ? 'Include' : 'Exclude' }}
                        </button>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @endif
        </div>
        @if($eggs->isNotEmpty() && $enabled !== '1')
          <div class="box-footer">
            <p class="text-muted small" style="margin:0;"><i class="fa fa-info-circle"></i> Enable automatic updates above to check for updates.</p>
          </div>
        @endif
      </div>
    </div>
  </div>

  @if($unallowedEggs->isNotEmpty())
  <div class="row">
    <div class="col-md-8 col-md-offset-2">
      <div class="box box-warning">
        <div class="box-header with-border">
          <i class="fa fa-exclamation-triangle text-warning"></i> <h3 class="box-title" style="display:inline;">Disallowed update URLs</h3>
        </div>
        <div class="box-body">
          <div class="callout callout-warning">
            <p>These eggs reference hosts not listed in <code>ALLOWED_EGG_HOSTS</code>. Add permitted hosts to your <code>.env</code> file to enable checking.</p>
          </div>
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Update URL</th>
                <th>Host</th>
              </tr>
            </thead>
            <tbody>
              @foreach($unallowedEggs as $egg)
              <tr>
                <td><code>{{ $egg->id }}</code></td>
                <td><a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></td>
                <td><code>{{ $egg->update_url }}</code></td>
                <td><span class="label label-danger">{{ parse_url($egg->update_url, PHP_URL_HOST) ?: '(unknown)' }}</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  @endif
@endsection

@section('footer-scripts')
  @parent
  <script>
  (function() {
    var CHECK_ALL_URL = @json(route('admin.settings.egg-updater.check-all'));

    function token() {
      return $('meta[name="csrf-token"]').attr('content') || $('[name="_token"]').first().val();
    }

    function notifyMsg(msg, type) {
      var cls = type === 'error' ? 'alert-danger' : type === 'success' ? 'alert-success' : type === 'warning' ? 'alert-warning' : 'alert-info';
      return '<div class="alert ' + cls + ' alert-dismissible" style="margin:0 0 10px 0;padding:8px 12px;font-size:13px;border-radius:3px;"><button type="button" class="close" data-dismiss="alert">&times;</button>' + $('<span>').text(msg).html() + '</div>';
    }

    function notifyAction(msg, type) {
      if (typeof msg !== 'string') msg = safeStr(msg, 'Error');
      var $fb = $('#actionFeedback');
      if (!$fb.length) return;
      $fb.show();
      $fb.prepend(notifyMsg(msg, type));
      if ($fb.data('timeout')) clearTimeout($fb.data('timeout'));
      $fb.data('timeout', setTimeout(function() { $fb.html(''); $fb.hide(); }, 10000));
    }

    function loading(btn, on) {
      var $b = $(btn); if (!$b.length) return;
      if (on) { $b.data('html', $b.html()).prop('disabled', true).find('i').attr('class', 'fa fa-spinner fa-spin'); }
      else { $b.html($b.data('html')).prop('disabled', false); }
    }

    function safeStr(v, d) {
      if (typeof v === 'string') return v;
      if (v === null || v === undefined) return d || '';
      if (typeof v === 'object') { try { return JSON.stringify(v); } catch(e) { return d || String(v); } }
      return String(v);
    }

    function errMsg(xhr, fallback) {
      try {
        var j = xhr.responseJSON || JSON.parse(xhr.responseText);
        var e = j.error;
        if (typeof e === 'string') return e;
        if (typeof j.message === 'string') return j.message;
        if (j.errors) return Object.values(j.errors).flat().join(', ');
        return fallback;
      } catch(e) { return (xhr.statusText && xhr.statusText !== 'error') ? xhr.status + ' ' + xhr.statusText : fallback; }
    }

    function setBadge(eggId, html) {
      var $b = $('#egg-row-' + eggId).find('.egg-status-badge');
      if ($b.length) $b.html(html);
    }

    function updateRow(eggId, data) {
      var $row = $('#egg-row-' + eggId);
      if (!$row.length) return;

      if (data.excluded !== undefined) {
        $row.toggleClass('egg-excluded', data.excluded);
        var $toggle = $row.find('.toggle-exclude');
        if (data.excluded) {
          $toggle.removeClass('btn-default').addClass('btn-warning').html('<i class="fa fa-check"></i> Include');
          $row.find('.check-single').prop('disabled', true);
          $row.find('.apply-single').hide();
          $row.find('.egg-status-badge').html('<span class="text-muted"><i class="fa fa-ban" style="margin-right:3px;"></i> Excluded</span>');
        } else {
          $toggle.removeClass('btn-warning').addClass('btn-default').html('<i class="fa fa-ban"></i> Exclude');
          $row.find('.check-single').prop('disabled', false);
          $row.find('.egg-status-badge').html('<span class="text-warning"><i class="fa fa-clock-o" style="margin-right:3px;"></i> Pending</span>');
        }
        return;
      }

      var status = data.status || 'error';
      var $badge = $row.find('.egg-status-badge');
      var $lastChecked = $row.find('.last-checked-text');
      var $applyBtn = $row.find('.apply-single');

      if (status === 'up_to_date') {
        $badge.html('<span class="text-success"><i class="fa fa-check-circle" style="margin-right:3px;"></i> OK</span>');
        $applyBtn.hide();
      } else if (status === 'update_available') {
        $badge.html('<span class="text-warning"><i class="fa fa-exclamation-triangle" style="margin-right:3px;"></i> Update Available</span>');
        $applyBtn.show();
      } else {
        $badge.html('<span class="text-danger"><i class="fa fa-exclamation-circle" style="margin-right:3px;"></i> Error</span>');
        $applyBtn.hide();
      }

      if (data.last_update_check_at) {
        $lastChecked.text(data.last_update_check_at);
      }
    }

    // --- Check All ---
    $('#checkAllBtn').on('click', function() {
      var $btn = $(this);
      var $fb = $('#checkAllFeedback');
      loading($btn, true);
      $fb.show().html(notifyMsg('Checking all eggs...', 'info'));

      $.post(CHECK_ALL_URL, {_token: token()})
        .done(function(r) {
          var ok = 0, updates = 0, errs = 0;
          (r.checked || []).forEach(function(item) {
            if (item.status === 'up_to_date') ok++;
            else if (item.status === 'update_available') updates++;
            else errs++;
            updateRow(item.egg_id, item);
          });
          var msg = 'Done: ' + ok + ' up to date, ' + updates + ' update' + (updates === 1 ? '' : 's') + ' available' + (errs ? ', ' + errs + ' error' + (errs === 1 ? '' : 's') : '') + '.';
          $fb.html(notifyMsg(msg, errs ? 'warning' : 'success'));
          setTimeout(function() { $fb.fadeOut('slow'); }, 8000);
        })
        .fail(function(xhr) {
          $fb.html(notifyMsg(errMsg(xhr, 'Check all failed.'), 'error'));
        })
        .always(function() { loading($btn, false); });
    });

    // --- Check single ---
    $(document).on('click', '.check-single', function() {
      var $btn = $(this);
      var eggId = $btn.data('egg-id');
      var url = $btn.data('url');
      loading($btn, true);

      $.ajax({type:'POST', url:url, data:{_token:token()}, dataType:'json'})
        .done(function(r) {
          updateRow(eggId, r);
          if (r && r.status === 'error' && r.error) {
            var _e = typeof r.error === 'string' ? r.error : (typeof r.error === 'object' ? JSON.stringify(r.error) : String(r.error));
            notifyAction('Egg #' + eggId + ': ' + _e, 'error');
          }
        })
        .fail(function(xhr) {
          setBadge(eggId, '<span class="text-danger"><i class="fa fa-exclamation-circle" style="margin-right:3px;"></i> Error</span>');
          var _e = errMsg(xhr, 'Check failed.');
          notifyAction('Egg #' + eggId + ': ' + (typeof _e === 'string' ? _e : JSON.stringify(_e)), 'error');
        })
        .always(function() { loading($btn, false); });
    });

    // --- Apply single ---
    $(document).on('click', '.apply-single', function() {
      var $btn = $(this);
      var eggId = $btn.data('egg-id');
      var url = $btn.data('url');

      if (!confirm('Apply update to egg #' + eggId + '? This will overwrite the local egg configuration.')) {
        return;
      }

      loading($btn, true);

      $.ajax({type:'POST', url:url, data:{_token:token()}, dataType:'json'})
        .done(function(r) {
          if (r && r.status === 'applied') {
            updateRow(eggId, {status: 'up_to_date', last_update_check_at: r.last_update_applied_at || 'just now'});
            notifyAction('Update applied to egg #' + eggId + '.', 'success');
          } else {
            setBadge(eggId, '<span class="text-danger"><i class="fa fa-exclamation-circle" style="margin-right:3px;"></i> Error</span>');
            $('#egg-row-' + eggId).find('.apply-single').hide();
            var _e = r && r.error || 'Apply returned an unknown status.';
            notifyAction('Egg #' + eggId + ': ' + (typeof _e==='string'?_e:JSON.stringify(_e)), 'error');
          }
        })
        .fail(function(xhr) {
          setBadge(eggId, '<span class="text-danger"><i class="fa fa-exclamation-circle" style="margin-right:3px;"></i> Error</span>');
          $('#egg-row-' + eggId).find('.apply-single').hide();
          var _e = errMsg(xhr, 'Apply failed.');
          notifyAction('Egg #' + eggId + ': ' + (typeof _e==='string'?_e:JSON.stringify(_e)), 'error');
        })
        .always(function() { loading($btn, false); });
    });

    // --- Toggle exclude ---
    $(document).on('click', '.toggle-exclude', function() {
      var $btn = $(this);
      var eggId = $btn.data('egg-id');
      var url = $btn.data('url');
      loading($btn, true);

      $.ajax({type:'POST', url:url, data:{_token:token()}, dataType:'json'})
        .done(function(r) {
          updateRow(eggId, {excluded: r && r.excluded});
        })
        .fail(function(xhr) {
          var _e = errMsg(xhr, 'Exclude toggle failed.');
          notifyAction('Egg #' + eggId + ': ' + (typeof _e==='string'?_e:JSON.stringify(_e)), 'error');
        })
        .always(function() { loading($btn, false); });
    });
  })();
  </script>
@endsection